import Fastify from 'fastify'
import { fileURLToPath } from 'url'
import { dirname, join } from 'path'
import { config } from './config/index.js'
import { logger } from './utils/logger.js'
import { checkDatabaseHealth, closeDatabase } from './config/database.js'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

/**
 * Create Fastify server instance
 */
const fastify = Fastify({
  logger: {
    level: config.logging.level,
    transport: config.app.env === 'development' ? {
      target: 'pino-pretty',
      options: {
        colorize: true
      }
    } : undefined
  },
  trustProxy: true,
  bodyLimit: 10 * 1024 * 1024, // 10MB
  disableRequestLogging: false
})

/**
 * Register plugins and routes
 */
async function registerPlugins() {
  // CORS
  await fastify.register(import('@fastify/cors'), config.cors)

  // Helmet for security headers
  await fastify.register(import('@fastify/helmet'), {
    contentSecurityPolicy: false // Disable CSP for development
  })

  // Rate limiting
  await fastify.register(import('@fastify/rate-limit'), {
    max: config.security.rateLimitMax,
    timeWindow: config.security.rateLimitWindowMs
  })

  // JWT authentication
  await fastify.register(import('@fastify/jwt'), {
    secret: config.jwt.secret,
    sign: {
      expiresIn: config.jwt.expiresIn,
      issuer: config.jwt.issuer,
      audience: config.jwt.audience
    },
    verify: {
      issuer: config.jwt.issuer,
      audience: config.jwt.audience
    }
  })

  // Redis for caching and sessions
  if (config.redis.host) {
    await fastify.register(import('@fastify/redis'), {
      host: config.redis.host,
      port: config.redis.port,
      password: config.redis.password,
      db: config.redis.db,
      keyPrefix: config.redis.keyPrefix
    })
  }

  // Multipart for file uploads
  await fastify.register(import('@fastify/multipart'), {
    limits: {
      fileSize: config.security.maxFileSize
    }
  })

  // Static file serving
  await fastify.register(import('@fastify/static'), {
    root: join(__dirname, '../uploads'),
    prefix: '/uploads/'
  })

  // API documentation
  if (config.features.apiDocumentation) {
    await fastify.register(import('@fastify/swagger'), {
      swagger: {
        info: {
          title: 'Event Manager API',
          description: 'High-performance event management system API',
          version: config.app.version
        },
        host: config.app.url.replace(/^https?:\/\//, ''),
        schemes: [config.app.url.startsWith('https') ? 'https' : 'http'],
        consumes: ['application/json', 'multipart/form-data'],
        produces: ['application/json'],
        securityDefinitions: {
          bearer: {
            type: 'apiKey',
            name: 'Authorization',
            in: 'header'
          }
        }
      }
    })

    await fastify.register(import('@fastify/swagger-ui'), {
      routePrefix: '/docs',
      uiConfig: {
        docExpansion: 'list',
        deepLinking: false
      },
      uiHooks: {
        onRequest: function (request, reply, next) {
          next()
        },
        preHandler: function (request, reply, next) {
          next()
        }
      },
      staticCSP: true,
      transformStaticCSP: (header) => header,
      transformSpecification: (swaggerObject, request, reply) => {
        return swaggerObject
      },
      transformSpecificationClone: true
    })
  }

  // WebSocket support for real-time features
  if (config.features.realTimeScoring) {
    await fastify.register(import('@fastify/websocket'), {
      options: {
        maxPayload: 16 * 1024 // 16KB
      }
    })
  }
}

/**
 * Register authentication decorators
 */
async function registerAuthDecorators() {
  // Authentication decorator
  fastify.decorate('authenticate', async function (request, reply) {
    try {
      const token = request.headers.authorization?.replace('Bearer ', '')
      
      if (!token) {
        return reply.status(401).send({ error: 'No token provided' })
      }

      const decoded = fastify.jwt.verify(token)
      request.user = decoded
    } catch (error) {
      return reply.status(401).send({ error: 'Invalid token' })
    }
  })

  // Role-based access control decorator
  fastify.decorate('requireRole', function (roles) {
    return async function (request, reply) {
      if (!request.user) {
        return reply.status(401).send({ error: 'Authentication required' })
      }

      const userRole = request.user.role
      const allowedRoles = Array.isArray(roles) ? roles : [roles]

      if (!allowedRoles.includes(userRole)) {
        return reply.status(403).send({ error: 'Insufficient permissions' })
      }
    }
  })

  // Permission-based access control decorator
  fastify.decorate('requirePermission', function (permission) {
    return async function (request, reply) {
      if (!request.user) {
        return reply.status(401).send({ error: 'Authentication required' })
      }

      // Import AuthService here to avoid circular dependency
      const { AuthService } = await import('./services/AuthService.js')
      const authService = new AuthService()

      if (!authService.hasPermission(request.user, permission)) {
        return reply.status(403).send({ error: 'Insufficient permissions' })
      }
    }
  }
}

/**
 * Register routes
 */
async function registerRoutes() {
  // Health check
  fastify.get('/health', async (request, reply) => {
    const dbHealth = await checkDatabaseHealth()
    
    return {
      status: 'ok',
      timestamp: new Date().toISOString(),
      version: config.app.version,
      environment: config.app.env,
      database: dbHealth.status,
      uptime: process.uptime()
    }
  })

  // API routes
  await fastify.register(import('./routes/auth.js'), { prefix: '/api/auth' })
  await fastify.register(import('./routes/contests.js'), { prefix: '/api/contests' })
  await fastify.register(import('./routes/scoring.js'), { prefix: '/api/scoring' })
  await fastify.register(import('./routes/users.js'), { prefix: '/api/users' })
  await fastify.register(import('./routes/results.js'), { prefix: '/api/results' })
  await fastify.register(import('./routes/files.js'), { prefix: '/api/files' })
  await fastify.register(import('./routes/settings.js'), { prefix: '/api/settings' })

  // WebSocket routes for real-time features
  if (config.features.realTimeScoring) {
    await fastify.register(import('./routes/websocket.js'), { prefix: '/ws' })
  }
}

/**
 * Register error handlers
 */
async function registerErrorHandlers() {
  // Global error handler
  fastify.setErrorHandler(async (error, request, reply) => {
    fastify.log.error(error)

    // JWT errors
    if (error.code === 'FST_JWT_AUTHORIZATION_TOKEN_INVALID') {
      return reply.status(401).send({ error: 'Invalid token' })
    }

    if (error.code === 'FST_JWT_AUTHORIZATION_TOKEN_EXPIRED') {
      return reply.status(401).send({ error: 'Token expired' })
    }

    // Validation errors
    if (error.validation) {
      return reply.status(400).send({
        error: 'Validation failed',
        details: error.validation
      })
    }

    // File upload errors
    if (error.code === 'LIMIT_FILE_SIZE') {
      return reply.status(413).send({ error: 'File too large' })
    }

    // Default error response
    const statusCode = error.statusCode || 500
    const message = config.app.env === 'production' 
      ? 'Internal server error' 
      : error.message

    return reply.status(statusCode).send({
      error: message,
      ...(config.app.env === 'development' && { stack: error.stack })
    })
  })

  // 404 handler
  fastify.setNotFoundHandler(async (request, reply) => {
    return reply.status(404).send({
      error: 'Route not found',
      path: request.url,
      method: request.method
    })
  })
}

/**
 * Register graceful shutdown handlers
 */
async function registerShutdownHandlers() {
  const gracefulShutdown = async (signal) => {
    fastify.log.info(`Received ${signal}, shutting down gracefully...`)
    
    try {
      await fastify.close()
      await closeDatabase()
      process.exit(0)
    } catch (error) {
      fastify.log.error('Error during shutdown:', error)
      process.exit(1)
    }
  }

  process.on('SIGTERM', () => gracefulShutdown('SIGTERM'))
  process.on('SIGINT', () => gracefulShutdown('SIGINT'))
}

/**
 * Start server
 */
async function startServer() {
  try {
    // Register all plugins and routes
    await registerPlugins()
    await registerAuthDecorators()
    await registerRoutes()
    await registerErrorHandlers()
    await registerShutdownHandlers()

    // Start server
    const address = await fastify.listen({
      port: config.app.port,
      host: config.app.host
    })

    fastify.log.info(`🚀 Server running at ${address}`)
    fastify.log.info(`📚 API Documentation: ${config.app.url}/docs`)
    fastify.log.info(`🏥 Health Check: ${config.app.url}/health`)
    
    if (config.features.realTimeScoring) {
      fastify.log.info(`🔌 WebSocket: ${config.app.url.replace('http', 'ws')}/ws`)
    }

  } catch (error) {
    fastify.log.error('Error starting server:', error)
    process.exit(1)
  }
}

// Start the server
startServer()