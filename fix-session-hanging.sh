#!/bin/bash

# Fix session hanging by temporarily using memory sessions instead of Redis
set -e

INSTALL_DIR="/opt/event-manager"
SERVER_FILE="$INSTALL_DIR/event-manager-api/src/server.js"

echo "Fixing session hanging issue by using memory sessions..."

# Backup the server file
sudo cp "$SERVER_FILE" "$SERVER_FILE.backup"

# Create a simplified server.js that uses memory sessions
sudo tee "$SERVER_FILE" > /dev/null << 'EOF'
import express from 'express'
import session from 'express-session'
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

// Import routes synchronously
import authRoutes from './routes/auth.js'
import eventsRoutes from './routes/events.js'
import contestsRoutes from './routes/contests.js'
import categoriesRoutes from './routes/categories.js'
import scoringRoutes from './routes/scoring.js'
import usersRoutes from './routes/users.js'
import resultsRoutes from './routes/results.js'
import filesRoutes from './routes/files.js'
import settingsRoutes from './routes/settings.js'
import backupRoutes from './routes/backup.js'
import printRoutes from './routes/print.js'
import templatesRoutes from './routes/templates.js'
import tallyMasterRoutes from './routes/tally-master.js'
import emceeRoutes from './routes/emcee.js'
import auditorRoutes from './routes/auditor.js'
import boardRoutes from './routes/board.js'
import databaseRoutes from './routes/database-browser.js'

const app = express()
const server = createServer(app)
const io = new Server(server, {
  cors: {
    origin: config.cors.origin,
    methods: ['GET', 'POST']
  }
})

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

// Session configuration - USING MEMORY STORE INSTEAD OF REDIS
app.use(session({
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
    console.log('Creating session for user:', user.email)
    req.session.userId = user.id
    req.session.userRole = user.role
    req.session.userEmail = user.email
    console.log('Session created successfully')
  }
  
  req.logout = (callback) => {
    req.session.destroy(callback)
  }
  
  next()
})

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

// API Routes - Synchronous imports
app.use('/api/auth', authRoutes)
app.use('/api/events', eventsRoutes)
app.use('/api/contests', contestsRoutes)
app.use('/api/categories', categoriesRoutes)
app.use('/api/scoring', scoringRoutes)
app.use('/api/users', usersRoutes)
app.use('/api/results', resultsRoutes)
app.use('/api/files', filesRoutes)
app.use('/api/settings', settingsRoutes)
app.use('/api/backup', backupRoutes)
app.use('/api/print', printRoutes)
app.use('/api/templates', templatesRoutes)
app.use('/api/tally-master', tallyMasterRoutes)
app.use('/api/emcee', emceeRoutes)
app.use('/api/auditor', auditorRoutes)
app.use('/api/board', boardRoutes)
app.use('/api/database', databaseRoutes)

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
    logger.info('Client ' + socket.id + ' joined room: ' + room)
  })
  
  socket.on('leave-room', (room) => {
    socket.leave(room)
    logger.info('Client ' + socket.id + ' left room: ' + room)
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
  logger.info('Received ' + signal + ', shutting down gracefully...')
  
  try {
    server.close(async () => {
      await closeConnection()
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
      logger.info('🚀 Server running at http://' + config.app.host + ':' + config.app.port)
      logger.info('📚 API Documentation: http://' + config.app.host + ':' + config.app.port + '/docs')
      logger.info('🏥 Health Check: http://' + config.app.host + ':' + config.app.port + '/api/health')
      logger.info('🔌 WebSocket: ws://' + config.app.host + ':' + config.app.port)
    })

  } catch (error) {
    logger.error('Failed to start server:', error)
    process.exit(1)
  }
}

// Start the server
startServer()

export default app
EOF

echo "Server updated to use memory sessions instead of Redis"

# Restart the service
echo "Restarting Event Manager service..."
sudo systemctl restart event-manager

sleep 5

# Check service status
if sudo systemctl is-active --quiet event-manager; then
    echo "✅ Service is running!"
    
    # Test the API endpoint
    echo "Testing API endpoint..."
    sleep 3
    curl -X POST http://localhost:3000/api/auth/login \
      -H "Content-Type: application/json" \
      -d '{"email":"admin@okckinkweekend.com","password":"Dittibop5!"}' \
      -w "\nHTTP Status: %{http_code}\n" --max-time 10 || echo "API test failed"
else
    echo "❌ Service failed to start. Checking logs..."
    sudo journalctl -u event-manager --no-pager -l --since "1 minute ago"
fi

echo "Fix completed!"
