import Fastify from 'fastify'
import { config } from './config/index.js'
import { testConnection, closeConnection } from './database/connection.js'
import { logger } from './utils/logger.js'

const fastify = Fastify({
  logger: {
    level: config.logging.level,
    transport: config.app.env === 'development' ? {
      target: 'pino-pretty',
      options: {
        colorize: true,
        translateTime: 'HH:MM:ss Z',
        ignore: 'pid,hostname'
      }
    } : undefined
  },
  trustProxy: true,
  bodyLimit: 10485760 // 10MB
})

/**
 * Register plugins
 */
async function registerPlugins() {
  // CORS
  await fastify.register(import('@fastify/cors'), config.cors)

  // Security headers
  await fastify.register(import('@fastify/helmet'), {
    contentSecurityPolicy: false // We'll handle this in our security middleware
  })

  // Rate limiting
  await fastify.register(import('@fastify/rate-limit'), {
    max: config.security.rateLimitMax,
    timeWindow: config.security.rateLimitWindowMs
  })

  // JWT
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

  // Redis
  await fastify.register(import('@fastify/redis'), {
    host: config.redis.host,
    port: config.redis.port,
    password: config.redis.password,
    db: config.redis.db,
    keyPrefix: config.redis.keyPrefix
  })

  // Multipart for file uploads
  await fastify.register(import('@fastify/multipart'), {
    limits: {
      fileSize: config.security.maxFileSize
    }
  })

  // Static files
  await fastify.register(import('@fastify/static'), {
    root: './uploads',
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
        onRequest: function (request, reply, next) { next() },
        preHandler: function (request, reply, next) { next() }
      },
      staticCSP: true,
      transformStaticCSP: (header) => header,
      transformSpecification: (swaggerObject, request, reply) => {
        return swaggerObject
      },
      transformSpecificationClone: true
    })
  }

  // WebSocket for real-time features
  if (config.features.realTimeScoring) {
    await fastify.register(import('@fastify/websocket'))
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
      const user = await fastify.db('users').where('id', decoded.userId).first()
      
      if (!user || !user.is_active) {
        return reply.status(401).send({ error: 'Invalid token' })
      }

      request.user = user
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

      if (!roles.includes(request.user.role)) {
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

      // This would need to be implemented with a proper permission system
      // For now, we'll use role-based checks
      const hasPermission = checkUserPermission(request.user.role, permission)
      
      if (!hasPermission) {
        return reply.status(403).send({ error: 'Insufficient permissions' })
      }
    }
  })
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
  await fastify.register(import('./routes/events.js'), { prefix: '/api/events' })
  await fastify.register(import('./routes/contests.js'), { prefix: '/api/contests' })
  await fastify.register(import('./routes/categories.js'), { prefix: '/api/categories' })
  await fastify.register(import('./routes/scoring.js'), { prefix: '/api/scoring' })
  await fastify.register(import('./routes/users.js'), { prefix: '/api/users' })
  await fastify.register(import('./routes/results.js'), { prefix: '/api/results' })
  await fastify.register(import('./routes/files.js'), { prefix: '/api/files' })
  await fastify.register(import('./routes/settings.js'), { prefix: '/api/settings' })
  await fastify.register(import('./routes/backup.js'), { prefix: '/api/backup' })
  await fastify.register(import('./routes/print.js'), { prefix: '/api/print' })
  await fastify.register(import('./routes/templates.js'), { prefix: '/api/templates' })
  await fastify.register(import('./routes/tally-master.js'), { prefix: '/api/tally-master' })
  await fastify.register(import('./routes/emcee.js'), { prefix: '/api/emcee' })
  await fastify.register(import('./routes/auditor.js'), { prefix: '/api/auditor' })
  await fastify.register(import('./routes/board.js'), { prefix: '/api/board' })
  await fastify.register(import('./routes/database-browser.js'), { prefix: '/api/database' })

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
    if (error.code === 'FST_ERR_REQ_FILE_TOO_LARGE') {
      return reply.status(413).send({ error: 'File too large' })
    }

    // Rate limit errors
    if (error.statusCode === 429) {
      return reply.status(429).send({ error: 'Too many requests' })
    }

    // Default error
    const statusCode = error.statusCode || 500
    const message = config.app.env === 'production' 
      ? 'Internal server error' 
      : error.message

    return reply.status(statusCode).send({ error: message })
  })

  // 404 handler
  fastify.setNotFoundHandler(async (request, reply) => {
    return reply.status(404).send({ error: 'Route not found' })
  })
}

/**
 * Register shutdown handlers
 */
async function registerShutdownHandlers() {
  const gracefulShutdown = async (signal) => {
    fastify.log.info(`Received ${signal}, shutting down gracefully...`)
    
    try {
      await fastify.close()
      await closeConnection()
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
 * Check database health
 */
async function checkDatabaseHealth() {
  try {
    await fastify.db.raw('SELECT 1')
    return { status: 'healthy' }
  } catch (error) {
    return { status: 'unhealthy', error: error.message }
  }
}

/**
 * Check user permission (simplified)
 */
function checkUserPermission(role, permission) {
  // This is a simplified permission check
  // In a real application, you'd have a more sophisticated permission system
  const rolePermissions = {
    organizer: ['*'], // All permissions
    judge: ['scoring:read', 'scoring:write', 'results:read'],
    contestant: ['results:read'],
    emcee: ['results:read'],
    tally_master: ['scoring:read', 'results:read', 'results:write'],
    auditor: ['scoring:read', 'results:read', 'audit:read'],
    board: ['results:read', 'reports:read']
  }

  const permissions = rolePermissions[role] || []
  return permissions.includes('*') || permissions.includes(permission)
}

/**
 * Start server
 */
async function startServer() {
  try {
    // Test database connection
    const dbConnected = await testConnection()
    if (!dbConnected) {
      throw new Error('Database connection failed')
    }

    // Register plugins
    await registerPlugins()
    
    // Register authentication decorators
    await registerAuthDecorators()
    
    // Register routes
    await registerRoutes()
    
    // Register error handlers
    await registerErrorHandlers()
    
    // Register shutdown handlers
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
    fastify.log.error('Failed to start server:', error)
    process.exit(1)
  }
}

// Start the server
startServer()