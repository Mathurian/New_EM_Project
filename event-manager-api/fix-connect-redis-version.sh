#!/bin/bash

echo "🔧 Fixing connect-redis Version Compatibility"
echo "============================================="

# Navigate to the API directory
cd /opt/event-manager/event-manager-api

echo "[INFO] Current connect-redis version:"
npm list connect-redis

echo "[INFO] Uninstalling connect-redis@8.1.0..."
npm uninstall connect-redis

echo "[INFO] Installing connect-redis@6.1.3 (compatible version)..."
npm install connect-redis@6.1.3

echo "[INFO] Updating server.js with correct imports..."

# Create the corrected server.js with the traditional import syntax
cat > src/server.js << 'EOF'
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

// Redis store for sessions (using traditional syntax)
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
  message: 'Too many requests from this IP, please try again later.',
  standardHeaders: true,
  legacyHeaders: false
})
app.use('/api/', limiter)

// CORS
app.use(cors(config.cors))

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
  app.get('/api/docs', (req, res) => {
    res.send(`
      <!DOCTYPE html>
      <html>
      <head>
        <title>Event Manager API Documentation</title>
        <style>
          body { font-family: Arial, sans-serif; margin: 40px; }
          h1 { color: #333; }
          .endpoint { background: #f5f5f5; padding: 10px; margin: 10px 0; border-radius: 5px; }
          .method { font-weight: bold; color: #007bff; }
        </style>
      </head>
      <body>
        <h1>Event Manager API v${config.app.version}</h1>
        <p>Welcome to the Event Manager API documentation.</p>
        
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
        
        <h2>Health Check</h2>
        <div class="endpoint">
          <span class="method">GET</span> /api/health - System health status
        </div>
        
        <p><strong>Base URL:</strong> ${config.app.url}/api</p>
        <p><strong>Environment:</strong> ${config.app.env}</p>
      </body>
      </html>
    `)
  })
}

// Health check endpoint
app.get('/api/health', async (req, res) => {
  try {
    const dbStatus = await testConnection()
    const redisStatus = redisClient.isReady ? 'connected' : 'disconnected'
    
    res.json({
      status: 'healthy',
      timestamp: new Date().toISOString(),
      version: config.app.version,
      environment: config.app.env,
      services: {
        database: dbStatus ? 'connected' : 'disconnected',
        redis: redisStatus
      }
    })
  } catch (error) {
    logger.error('Health check failed:', error)
    res.status(503).json({
      status: 'unhealthy',
      timestamp: new Date().toISOString(),
      error: error.message
    })
  }
})

// Import and use routes
import('./routes/auth.js').then(({ default: authRoutes }) => {
  app.use('/api/auth', authRoutes)
})

import('./routes/users.js').then(({ default: userRoutes }) => {
  app.use('/api/users', userRoutes)
})

import('./routes/events.js').then(({ default: eventRoutes }) => {
  app.use('/api/events', eventRoutes)
})

import('./routes/contests.js').then(({ default: contestRoutes }) => {
  app.use('/api/contests', contestRoutes)
})

import('./routes/categories.js').then(({ default: categoryRoutes }) => {
  app.use('/api/categories', categoryRoutes)
})

import('./routes/scoring.js').then(({ default: scoringRoutes }) => {
  app.use('/api/scoring', scoringRoutes)
})

import('./routes/results.js').then(({ default: resultRoutes }) => {
  app.use('/api/results', resultRoutes)
})

import('./routes/files.js').then(({ default: fileRoutes }) => {
  app.use('/api/files', fileRoutes)
})

import('./routes/settings.js').then(({ default: settingRoutes }) => {
  app.use('/api/settings', settingRoutes)
})

import('./routes/backup.js').then(({ default: backupRoutes }) => {
  app.use('/api/backup', backupRoutes)
})

import('./routes/auditor.js').then(({ default: auditorRoutes }) => {
  app.use('/api/auditor', auditorRoutes)
})

import('./routes/board.js').then(({ default: boardRoutes }) => {
  app.use('/api/board', boardRoutes)
})

import('./routes/emcee.js').then(({ default: emceeRoutes }) => {
  app.use('/api/emcee', emceeRoutes)
})

import('./routes/tally-master.js').then(({ default: tallyMasterRoutes }) => {
  app.use('/api/tally-master', tallyMasterRoutes)
})

import('./routes/templates.js').then(({ default: templateRoutes }) => {
  app.use('/api/templates', templateRoutes)
})

import('./routes/print.js').then(({ default: printRoutes }) => {
  app.use('/api/print', printRoutes)
})

import('./routes/database-browser.js').then(({ default: dbBrowserRoutes }) => {
  app.use('/api/database-browser', dbBrowserRoutes)
})

// WebSocket connection handler
io.on('connection', (socket) => {
  logger.info('Client connected:', socket.id)
  
  socket.on('disconnect', () => {
    logger.info('Client disconnected:', socket.id)
  })
  
  // Handle scoring updates
  socket.on('scoring-update', (data) => {
    socket.broadcast.emit('scoring-update', data)
  })
})

// Error handling middleware
app.use((err, req, res, next) => {
  logger.error('Unhandled error:', err)
  res.status(500).json({ 
    error: 'Internal server error',
    message: config.app.env === 'development' ? err.message : 'Something went wrong'
  })
})

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Route not found' })
})

// Graceful shutdown
process.on('SIGTERM', async () => {
  logger.info('SIGTERM received, shutting down gracefully')
  server.close(() => {
    logger.info('HTTP server closed')
  })
  
  try {
    await redisClient.quit()
    await closeConnection()
    logger.info('Database connections closed')
    process.exit(0)
  } catch (error) {
    logger.error('Error during shutdown:', error)
    process.exit(1)
  }
})

process.on('SIGINT', async () => {
  logger.info('SIGINT received, shutting down gracefully')
  server.close(() => {
    logger.info('HTTP server closed')
  })
  
  try {
    await redisClient.quit()
    await closeConnection()
    logger.info('Database connections closed')
    process.exit(0)
  } catch (error) {
    logger.error('Error during shutdown:', error)
    process.exit(1)
  }
})

// Start server
const PORT = config.app.port
const HOST = config.app.host

server.listen(PORT, HOST, () => {
  logger.info(`🚀 Event Manager API Server started`)
  logger.info(`📍 Server running on http://${HOST}:${PORT}`)
  logger.info(`🌍 Environment: ${config.app.env}`)
  logger.info(`📊 API Documentation: http://${HOST}:${PORT}/api/docs`)
  logger.info(`❤️  Health Check: http://${HOST}:${PORT}/api/health`)
})

export default app
EOF

echo "[SUCCESS] Updated server.js with traditional connect-redis syntax"
echo "[INFO] Verifying connect-redis version..."
npm list connect-redis

echo "[INFO] Testing server startup..."
timeout 10s node src/server.js 2>&1 | head -20

echo ""
echo "[INFO] If you see 'Server started' above, the fix worked!"
echo "[INFO] You can now run: npm start"
echo "[INFO] Then test login with:"
echo "curl -X POST http://localhost:3000/api/auth/login \\"
echo "  -H \"Content-Type: application/json\" \\"
echo "  -d '{\"email\":\"admin@eventmanager.com\",\"password\":\"admin123\"}'"
