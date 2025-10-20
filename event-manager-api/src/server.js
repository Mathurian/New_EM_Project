import express from 'express'
import session from 'express-session'
import connectRedis from 'connect-redis'
import cors from 'cors'
import helmet from 'helmet'
import compression from 'compression'
import morgan from 'morgan'
import cookieParser from 'cookie-parser'
import flash from 'express-flash'
import rateLimit from 'express-rate-limit'
import { createServer } from 'http'
import { Server } from 'socket.io'
import { config } from './config/index.js'
import { testConnection, closeConnection } from './database/connection.js'
import { logger } from './utils/logger.js'
import { redisClient } from './utils/redis.js'

const app = express()
const server = createServer(app)
const io = new Server(server, {
  cors: {
    origin: config.cors.origin,
    methods: ['GET', 'POST']
  }
})

// Redis store for sessions
const RedisStore = connectRedis(session)

// Trust proxy for Apache
app.set('trust proxy', 1)

// Security middleware
app.use(helmet({
  contentSecurityPolicy: false,
  crossOriginEmbedderPolicy: false
}))

// Compression
app.use(compression())

// Logging
if (config.app.env === 'development') {
  app.use(morgan('dev'))
} else {
  app.use(morgan('combined'))
}

// Rate limiting
const limiter = rateLimit({
  windowMs: config.security.rateLimitWindowMs,
  max: config.security.rateLimitMax,
  message: { error: 'Too many requests from this IP' },
  standardHeaders: true,
  legacyHeaders: false
})
app.use('/api/', limiter)

// CORS
app.use(cors({
  origin: config.cors.origin,
  credentials: true,
  methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
  allowedHeaders: ['Content-Type', 'Authorization', 'X-CSRF-Token']
}))

// Body parsing
app.use(express.json({ limit: '10mb' }))
app.use(express.urlencoded({ extended: true, limit: '10mb' }))
app.use(cookieParser())

// Session configuration
app.use(session({
  store: new RedisStore({ client: redisClient }),
  secret: config.session.secret,
  resave: false,
  saveUninitialized: false,
  cookie: {
    secure: config.app.env === 'production',
    httpOnly: true,
    maxAge: config.session.maxAge,
    sameSite: 'lax'
  },
  name: 'event-manager-session'
}))

// Flash messages
app.use(flash())

// Static files
app.use('/uploads', express.static('uploads'))

// Make io available to routes
app.use((req, res, next) => {
  req.io = io
  next()
})

// Authentication middleware
app.use((req, res, next) => {
  // Make user available to all routes
  req.isAuthenticated = () => {
    return !!req.session.userId
  }
  
  req.login = (user) => {
    req.session.userId = user.id
    req.session.userRole = user.role
    req.session.userEmail = user.email
  }
  
  req.logout = (callback) => {
    req.session.destroy(callback)
  }
  
  next()
})

// API Documentation (simple HTML)
if (config.features.apiDocumentation) {
  app.get('/docs', (req, res) => {
    res.send(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Event Manager API Documentation</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 40px; }
          .endpoint { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
          .method { font-weight: bold; color: #007bff; }
        </style>
      </head>
      <body>
        <h1>Event Manager API Documentation</h1>
        <h2>Authentication Endpoints</h2>
        <div class="endpoint">
          <span class="method">POST</span> /api/auth/login - User login
        </div>
        <div class="endpoint">
          <span class="method">POST</span> /api/auth/logout - User logout
        </div>
        <div class="endpoint">
          <span class="method">GET</span> /api/auth/me - Get current user
        </div>
        <h2>Event Endpoints</h2>
        <div class="endpoint">
          <span class="method">GET</span> /api/events - List events
        </div>
        <div class="endpoint">
          <span class="method">POST</span> /api/events - Create event
        </div>
        <div class="endpoint">
          <span class="method">GET</span> /api/events/:id - Get event details
        </div>
        <div class="endpoint">
          <span class="method">PUT</span> /api/events/:id - Update event
        </div>
        <div class="endpoint">
          <span class="method">DELETE</span> /api/events/:id - Delete event
        </div>
        <h2>Health Check</h2>
        <div class="endpoint">
          <span class="method">GET</span> /api/health - Application health
        </div>
      </body>
      </html>
    `)
  })
}

// Health check endpoint
app.get('/api/health', async (req, res) => {
  try {
    const dbHealth = await checkDatabaseHealth()
    
    res.json({
      status: 'ok',
      timestamp: new Date().toISOString(),
      version: config.app.version,
      environment: config.app.env,
      database: dbHealth.status,
      uptime: process.uptime()
    })
  } catch (error) {
    logger.error('Health check failed:', error)
    res.status(500).json({ error: 'Health check failed' })
  }
})

// API Routes
app.use('/api/auth', (await import('./routes/auth.js')).default)
app.use('/api/events', (await import('./routes/events.js')).default)
app.use('/api/contests', (await import('./routes/contests.js')).default)
app.use('/api/categories', (await import('./routes/categories.js')).default)
app.use('/api/scoring', (await import('./routes/scoring.js')).default)
app.use('/api/users', (await import('./routes/users.js')).default)
app.use('/api/results', (await import('./routes/results.js')).default)
app.use('/api/files', (await import('./routes/files.js')).default)
app.use('/api/settings', (await import('./routes/settings.js')).default)
app.use('/api/backup', (await import('./routes/backup.js')).default)
app.use('/api/print', (await import('./routes/print.js')).default)
app.use('/api/templates', (await import('./routes/templates.js')).default)
app.use('/api/tally-master', (await import('./routes/tally-master.js')).default)
app.use('/api/emcee', (await import('./routes/emcee.js')).default)
app.use('/api/auditor', (await import('./routes/auditor.js')).default)
app.use('/api/board', (await import('./routes/board.js')).default)
app.use('/api/database', (await import('./routes/database-browser.js')).default)

// Serve frontend
app.use(express.static('../event-manager-frontend/dist'))

// Catch-all handler for SPA
app.get('*', (req, res) => {
  res.sendFile('index.html', { root: '../event-manager-frontend/dist' })
})

// Error handling middleware
app.use((error, req, res, next) => {
  logger.error('Unhandled error:', error)
  
  if (res.headersSent) {
    return next(error)
  }
  
  const statusCode = error.statusCode || 500
  const message = config.app.env === 'production' 
    ? 'Internal server error' 
    : error.message
  
  res.status(statusCode).json({ error: message })
})

// 404 handler
app.use((req, res) => {
  res.status(404).json({ error: 'Route not found' })
})

// Socket.IO connection handling
io.on('connection', (socket) => {
  logger.info('Client connected:', socket.id)
  
  socket.on('disconnect', () => {
    logger.info('Client disconnected:', socket.id)
  })
  
  // Join room for real-time updates
  socket.on('join-room', (room) => {
    socket.join(room)
    logger.info(`Client ${socket.id} joined room: ${room}`)
  })
  
  socket.on('leave-room', (room) => {
    socket.leave(room)
    logger.info(`Client ${socket.id} left room: ${room}`)
  })
})

// Database health check
async function checkDatabaseHealth() {
  try {
    const { db } = await import('./database/connection.js')
    await db.raw('SELECT 1')
    return { status: 'healthy' }
  } catch (error) {
    return { status: 'unhealthy', error: error.message }
  }
}

// Graceful shutdown
const gracefulShutdown = async (signal) => {
  logger.info(`Received ${signal}, shutting down gracefully...`)
  
  try {
    server.close(async () => {
      await closeConnection()
      await redisClient.quit()
      process.exit(0)
    })
  } catch (error) {
    logger.error('Error during shutdown:', error)
    process.exit(1)
  }
}

process.on('SIGTERM', () => gracefulShutdown('SIGTERM'))
process.on('SIGINT', () => gracefulShutdown('SIGINT'))

// Start server
async function startServer() {
  try {
    // Test database connection
    const dbConnected = await testConnection()
    if (!dbConnected) {
      throw new Error('Database connection failed')
    }

    // Start server
    server.listen(config.app.port, config.app.host, () => {
      logger.info(`🚀 Server running at http://${config.app.host}:${config.app.port}`)
      logger.info(`📚 API Documentation: http://${config.app.host}:${config.app.port}/docs`)
      logger.info(`🏥 Health Check: http://${config.app.host}:${config.app.port}/api/health`)
      logger.info(`🔌 WebSocket: ws://${config.app.host}:${config.app.port}`)
    })

  } catch (error) {
    logger.error('Failed to start server:', error)
    process.exit(1)
  }
}

// Start the server
startServer()

export default app