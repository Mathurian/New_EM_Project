#!/bin/bash

# Event Manager - One-Click Installation Script for Ubuntu 24.04
# This script creates the entire application and installs all dependencies

set -e  # Exit on any error

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
PURPLE='\033[0;35m'
CYAN='\033[0;36m'
NC='\033[0m' # No Color

# Configuration
APP_NAME="Event Manager"
APP_VERSION="2.0.0"
INSTALL_DIR="/opt/event-manager"
SERVICE_USER="eventmanager"
DB_NAME="event_manager"
DB_USER="event_manager"

# User configuration variables
DB_PASSWORD=""
REDIS_PASSWORD=""
SESSION_SECRET=""
DOMAIN_NAME=""
EMAIL_HOST=""
EMAIL_PORT=""
EMAIL_USER=""
EMAIL_PASS=""
ADMIN_EMAIL=""
ADMIN_PASSWORD=""

# Logging functions
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

log_prompt() {
    echo -e "${PURPLE}[PROMPT]${NC} $1"
}

log_step() {
    echo -e "${CYAN}[STEP]${NC} $1"
}

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        log_error "This script should not be run as root. Please run as a regular user with sudo privileges."
        exit 1
    fi
}

# Check Ubuntu version
check_ubuntu_version() {
    if ! lsb_release -d | grep -q "Ubuntu 24.04"; then
        log_warning "This script is designed for Ubuntu 24.04. Other versions may work but are not tested."
        read -p "Continue anyway? (y/N): " -n 1 -r
        echo
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            exit 1
        fi
    fi
}

# Collect user configuration
collect_configuration() {
    log_step "Collecting Configuration Information"
    echo "=========================================="
    echo "Event Manager Configuration Setup"
    echo "=========================================="
    echo
    
    # Database configuration
    log_prompt "Database Configuration:"
    read -p "Enter PostgreSQL password for '$DB_USER' user: " -s DB_PASSWORD
    echo
    read -p "Confirm PostgreSQL password: " -s DB_PASSWORD_CONFIRM
    echo
    if [[ "$DB_PASSWORD" != "$DB_PASSWORD_CONFIRM" ]]; then
        log_error "Passwords do not match!"
        exit 1
    fi
    
    # Redis configuration
    log_prompt "Redis Configuration:"
    read -p "Enter Redis password: " -s REDIS_PASSWORD
    echo
    read -p "Confirm Redis password: " -s REDIS_PASSWORD_CONFIRM
    echo
    if [[ "$REDIS_PASSWORD" != "$REDIS_PASSWORD_CONFIRM" ]]; then
        log_error "Passwords do not match!"
        exit 1
    fi
    
    # Session secret
    log_prompt "Security Configuration:"
    read -p "Enter session secret (or press Enter for auto-generated): " SESSION_SECRET
    if [[ -z "$SESSION_SECRET" ]]; then
        SESSION_SECRET="$(openssl rand -base64 64)"
        log_info "Auto-generated session secret"
    fi
    
    # Domain configuration
    log_prompt "Web Server Configuration:"
    read -p "Enter domain name (e.g., localhost, yourdomain.com): " DOMAIN_NAME
    if [[ -z "$DOMAIN_NAME" ]]; then
        DOMAIN_NAME="localhost"
    fi
    
    # Email configuration (optional)
    log_prompt "Email Configuration (optional - press Enter to skip):"
    read -p "Enter SMTP host: " EMAIL_HOST
    if [[ -n "$EMAIL_HOST" ]]; then
        read -p "Enter SMTP port (default 587): " EMAIL_PORT
        if [[ -z "$EMAIL_PORT" ]]; then
            EMAIL_PORT="587"
        fi
        read -p "Enter SMTP username: " EMAIL_USER
        read -p "Enter SMTP password: " -s EMAIL_PASS
        echo
    fi
    
    # Admin user configuration
    log_prompt "Admin User Configuration:"
    read -p "Enter admin email (default: admin@eventmanager.com): " ADMIN_EMAIL
    if [[ -z "$ADMIN_EMAIL" ]]; then
        ADMIN_EMAIL="admin@eventmanager.com"
    fi
    read -p "Enter admin password: " -s ADMIN_PASSWORD
    echo
    read -p "Confirm admin password: " -s ADMIN_PASSWORD_CONFIRM
    echo
    if [[ "$ADMIN_PASSWORD" != "$ADMIN_PASSWORD_CONFIRM" ]]; then
        log_error "Passwords do not match!"
        exit 1
    fi
    
    log_success "Configuration collected successfully"
    echo
}

# Update system packages
update_system() {
    log_step "Updating System Packages"
    sudo apt update
    sudo apt upgrade -y
    log_success "System packages updated"
}

# Install system dependencies
install_system_dependencies() {
    log_step "Installing System Dependencies"
    
    # Essential build tools
    sudo apt install -y \
        build-essential \
        curl \
        wget \
        git \
        unzip \
        software-properties-common \
        apt-transport-https \
        ca-certificates \
        gnupg \
        lsb-release
    
    # Python and development tools
    sudo apt install -y \
        python3 \
        python3-pip \
        python3-dev \
        python3-venv
    
    # Image processing libraries
    sudo apt install -y \
        libjpeg-dev \
        libpng-dev \
        libwebp-dev \
        libtiff-dev \
        libgif-dev \
        libfreetype6-dev \
        libfontconfig1-dev
    
    # PostgreSQL
    sudo apt install -y \
        postgresql \
        postgresql-contrib \
        postgresql-client \
        libpq-dev
    
    # Redis
    sudo apt install -y \
        redis-server \
        redis-tools
    
    # Apache
    sudo apt install -y \
        apache2 \
        apache2-utils
    
    # Node.js (using NodeSource repository for LTS version)
    curl -fsSL https://deb.nodesource.com/setup_lts.x | sudo -E bash -
    sudo apt install -y nodejs
    
    # PM2 for process management
    sudo npm install -g pm2
    
    log_success "System dependencies installed"
}

# Create application user
create_app_user() {
    log_step "Creating Application User"
    
    if ! id "$SERVICE_USER" &>/dev/null; then
        sudo useradd -r -s /bin/false -d "$INSTALL_DIR" -m "$SERVICE_USER"
        log_success "Application user created: $SERVICE_USER"
    else
        log_info "Application user already exists: $SERVICE_USER"
    fi
}

# Setup PostgreSQL
setup_postgresql() {
    log_step "Setting up PostgreSQL"
    
    # Start and enable PostgreSQL
    sudo systemctl start postgresql
    sudo systemctl enable postgresql
    
    # Create database and user
    sudo -u postgres psql << EOF
-- Create database
CREATE DATABASE $DB_NAME;

-- Create user
CREATE USER $DB_USER WITH PASSWORD '$DB_PASSWORD';

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
GRANT ALL PRIVILEGES ON SCHEMA public TO $DB_USER;

-- Exit
\q
EOF
    
    log_success "PostgreSQL setup completed"
}

# Setup Redis
setup_redis() {
    log_step "Setting up Redis"
    
    # Configure Redis
    sudo tee /etc/redis/redis.conf > /dev/null << EOF
# Redis configuration for Event Manager
bind 127.0.0.1
port 6379
timeout 0
tcp-keepalive 300
daemonize yes
supervised systemd
pidfile /var/run/redis/redis-server.pid
loglevel notice
logfile /var/log/redis/redis-server.log
databases 16
save 900 1
save 300 10
save 60 10000
stop-writes-on-bgsave-error yes
rdbcompression yes
rdbchecksum yes
dbfilename dump.rdb
dir /var/lib/redis
requirepass $REDIS_PASSWORD
maxmemory 256mb
maxmemory-policy allkeys-lru
appendonly yes
appendfilename "appendonly.aof"
appendfsync everysec
no-appendfsync-on-rewrite no
auto-aof-rewrite-percentage 100
auto-aof-rewrite-min-size 64mb
aof-load-truncated yes
aof-use-rdb-preamble yes
EOF
    
    # Start and enable Redis
    sudo systemctl restart redis-server
    sudo systemctl enable redis-server
    
    log_success "Redis setup completed"
}

# Create application structure
create_application_structure() {
    log_step "Creating Application Structure"
    
    # Create installation directory
    sudo mkdir -p "$INSTALL_DIR"
    sudo chown "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR"
    
    # Create backend directory structure
    sudo mkdir -p "$INSTALL_DIR/event-manager-api/src"/{config,database/{migrations,seeds},routes,services,utils}
    sudo mkdir -p "$INSTALL_DIR/event-manager-api/scripts"
    
    # Create frontend directory structure
    sudo mkdir -p "$INSTALL_DIR/event-manager-frontend/src"/{components/{ui,layout},pages/{auth,roles},stores,lib,hooks,styles}
    sudo mkdir -p "$INSTALL_DIR/event-manager-frontend/public"
    
    # Create logs directory
    sudo mkdir -p "$INSTALL_DIR/logs"
    sudo chown -R "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR"
    
    log_success "Application structure created"
}

# Generate backend package.json
generate_backend_package_json() {
    log_step "Generating Backend Package Configuration"
    
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/package.json" > /dev/null << EOF
{
  "name": "event-manager-api",
  "version": "2.0.0",
  "description": "Stable Event Management System built with Express.js and PostgreSQL",
  "main": "src/server.js",
  "type": "module",
  "scripts": {
    "start": "node src/server.js",
    "dev": "nodemon src/server.js",
    "test": "jest",
    "test:watch": "jest --watch",
    "test:coverage": "jest --coverage",
    "lint": "eslint src/",
    "lint:fix": "eslint src/ --fix",
    "db:migrate": "node scripts/migrate.js",
    "db:migrate:rollback": "node scripts/rollback.js",
    "db:seed": "node scripts/seed.js",
    "build": "npm run build:frontend",
    "build:frontend": "cd ../event-manager-frontend && npm run build"
  },
  "keywords": [
    "event",
    "management",
    "scoring",
    "judging",
    "express",
    "nodejs"
  ],
  "author": "Event Manager Team",
  "license": "MIT",
  "dependencies": {
    "express": "^4.18.2",
    "express-session": "^1.17.3",
    "express-rate-limit": "^7.1.5",
    "joi": "^17.11.0",
    "cors": "^2.8.5",
    "helmet": "^7.1.0",
    "compression": "^1.7.4",
    "morgan": "^1.10.0",
    "knex": "^3.0.1",
    "pg": "^8.11.3",
    "bcryptjs": "^2.4.3",
    "uuid": "^9.0.1",
    "nodemailer": "^6.9.8",
    "sharp": "^0.32.6",
    "multer": "^1.4.5-lts.1",
    "winston": "^3.11.0",
    "dotenv": "^16.3.1",
    "node-cron": "^3.0.3",
    "socket.io": "^4.7.4",
    "connect-redis": "^7.1.0",
    "redis": "^4.6.10",
    "cookie-parser": "^1.4.6",
    "express-flash": "^0.0.2"
  },
  "devDependencies": {
    "nodemon": "^3.0.2",
    "jest": "^29.7.0",
    "supertest": "^6.3.3",
    "eslint": "^8.57.0",
    "@eslint/js": "^8.57.0",
    "eslint-plugin-import": "^2.29.1",
    "eslint-plugin-node": "^11.1.0",
    "eslint-plugin-promise": "^6.1.1"
  },
  "engines": {
    "node": ">=18.0.0"
  }
}
EOF
    
    log_success "Backend package.json created"
}

# Generate frontend package.json
generate_frontend_package_json() {
    log_step "Generating Frontend Package Configuration"
    
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/package.json" > /dev/null << EOF
{
  "name": "event-manager-frontend",
  "version": "2.0.0",
  "description": "Stable responsive frontend for Event Manager",
  "type": "module",
  "scripts": {
    "dev": "vite",
    "build": "tsc && vite build",
    "preview": "vite preview",
    "lint": "eslint . --ext ts,tsx --report-unused-disable-directives --max-warnings 0",
    "type-check": "tsc --noEmit"
  },
  "dependencies": {
    "react": "^18.2.0",
    "react-dom": "^18.2.0",
    "react-router-dom": "^6.20.1",
    "axios": "^1.6.2",
    "zustand": "^4.4.7",
    "@tanstack/react-query": "^5.8.4",
    "@tanstack/react-query-devtools": "^5.8.4",
    "react-hook-form": "^7.48.2",
    "react-hot-toast": "^2.4.1",
    "lucide-react": "^0.294.0",
    "clsx": "^2.0.0",
    "tailwind-merge": "^2.0.0",
    "date-fns": "^2.30.0",
    "recharts": "^2.8.0",
    "socket.io-client": "^4.7.4",
    "framer-motion": "^10.16.5",
    "react-dropzone": "^14.2.3",
    "@tanstack/react-table": "^8.10.7",
    "react-select": "^5.8.0",
    "react-datepicker": "^4.21.0",
    "@radix-ui/react-slot": "^1.0.2",
    "class-variance-authority": "^0.7.0"
  },
  "devDependencies": {
    "@types/react": "^18.2.43",
    "@types/react-dom": "^18.2.17",
    "@types/node": "^20.10.4",
    "@typescript-eslint/eslint-plugin": "^6.14.0",
    "@typescript-eslint/parser": "^6.14.0",
    "@vitejs/plugin-react": "^4.2.1",
    "autoprefixer": "^10.4.16",
    "eslint": "^8.55.0",
    "eslint-plugin-react-hooks": "^4.6.0",
    "eslint-plugin-react-refresh": "^0.4.5",
    "postcss": "^8.4.32",
    "tailwindcss": "^3.3.6",
    "typescript": "^5.2.2",
    "vite": "^5.0.8"
  }
}
EOF
    
    log_success "Frontend package.json created"
}

# Generate backend configuration
generate_backend_config() {
    log_step "Generating Backend Configuration"
    
    # Main server file
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/server.js" > /dev/null << 'EOF'
import express from 'express'
import session from 'express-session'
import { createClient } from 'redis'
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

// Redis store for sessions
const RedisStore = connectRedis(session)
const redisStore = new RedisStore({ client: redisClient })

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
  store: redisStore,
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
    logger.info(\`Client \${socket.id} joined room: \${room}\`)
  })
  
  socket.on('leave-room', (room) => {
    socket.leave(room)
    logger.info(\`Client \${socket.id} left room: \${room}\`)
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
  logger.info(\`Received \${signal}, shutting down gracefully...\`)
  
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
      logger.info(\`🚀 Server running at http://\${config.app.host}:\${config.app.port}\`)
      logger.info(\`📚 API Documentation: http://\${config.app.host}:\${config.app.port}/docs\`)
      logger.info(\`🏥 Health Check: http://\${config.app.host}:\${config.app.port}/api/health\`)
      logger.info(\`🔌 WebSocket: ws://\${config.app.host}:\${config.app.port}\`)
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

    # Configuration file
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/config/index.js" > /dev/null << EOF
import dotenv from 'dotenv'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Load environment variables
dotenv.config({ path: path.join(__dirname, '../../.env') })

export const config = {
  app: {
    name: process.env.APP_NAME || 'Event Manager',
    version: process.env.APP_VERSION || '2.0.0',
    env: process.env.NODE_ENV || 'production',
    port: parseInt(process.env.PORT) || 3000,
    host: process.env.HOST || '0.0.0.0',
    url: process.env.APP_URL || 'http://localhost'
  },
  
  database: {
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT) || 5432,
    name: process.env.DB_NAME || 'event_manager',
    user: process.env.DB_USER || 'event_manager',
    password: process.env.DB_PASSWORD || '',
    ssl: process.env.DB_SSL === 'true' ? { rejectUnauthorized: false } : false
  },
  
  redis: {
    host: process.env.REDIS_HOST || 'localhost',
    port: parseInt(process.env.REDIS_PORT) || 6379,
    password: process.env.REDIS_PASSWORD || '',
    db: parseInt(process.env.REDIS_DB) || 0
  },
  
  session: {
    secret: process.env.SESSION_SECRET || 'your-secret-key',
    maxAge: parseInt(process.env.SESSION_MAX_AGE) || 86400000 // 24 hours
  },
  
  security: {
    bcryptRounds: parseInt(process.env.BCRYPT_ROUNDS) || 12,
    rateLimitMax: parseInt(process.env.RATE_LIMIT_MAX) || 100,
    rateLimitWindowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 900000 // 15 minutes
  },
  
  cors: {
    origin: process.env.CORS_ORIGIN || 'http://localhost'
  },
  
  features: {
    realtimeScoring: process.env.FEATURE_REALTIME_SCORING === 'true',
    emailNotifications: process.env.FEATURE_EMAIL_NOTIFICATIONS === 'true',
    fileUploads: process.env.FEATURE_FILE_UPLOADS === 'true',
    auditLogging: process.env.FEATURE_AUDIT_LOGGING === 'true',
    backupAutomation: process.env.FEATURE_BACKUP_AUTOMATION === 'true',
    apiDocumentation: process.env.FEATURE_API_DOCS === 'true'
  },
  
  email: {
    host: process.env.EMAIL_HOST || '',
    port: parseInt(process.env.EMAIL_PORT) || 587,
    user: process.env.EMAIL_USER || '',
    pass: process.env.EMAIL_PASS || '',
    secure: process.env.EMAIL_SECURE === 'true'
  }
}
EOF

    log_success "Backend configuration generated"
}

# Generate essential backend files
generate_essential_backend_files() {
    log_step "Generating Essential Backend Files"
    
    # Database connection
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/database/connection.js" > /dev/null << 'EOF'
import knex from 'knex'
import { config } from '../config/index.js'

export const db = knex({
  client: 'pg',
  connection: {
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: config.database.password,
    database: config.database.name,
    ssl: config.database.ssl
  },
  pool: {
    min: 2,
    max: 10,
    acquireTimeoutMillis: 30000,
    createTimeoutMillis: 30000,
    destroyTimeoutMillis: 5000,
    idleTimeoutMillis: 30000,
    reapIntervalMillis: 1000,
    createRetryIntervalMillis: 200
  },
  migrations: {
    directory: './src/database/migrations'
  },
  seeds: {
    directory: './src/database/seeds'
  }
})

export async function testConnection() {
  try {
    await db.raw('SELECT 1')
    return true
  } catch (error) {
    console.error('Database connection failed:', error)
    return false
  }
}

export async function closeConnection() {
  try {
    await db.destroy()
  } catch (error) {
    console.error('Error closing database connection:', error)
  }
}
EOF

    # Redis client
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/utils/redis.js" > /dev/null << 'EOF'
import { createClient } from 'redis'
import { config } from '../config/index.js'
import { logger } from './logger.js'

// Create Redis client with stable configuration
export const redisClient = createClient({
  url: `redis://${config.redis.host}:${config.redis.port}`,
  password: config.redis.password || undefined,
  database: config.redis.db,
  legacyMode: false // Use modern Redis client mode
})

// Redis event handlers
redisClient.on('connect', () => {
  logger.info('Redis client connected')
})

redisClient.on('error', (error) => {
  logger.error('Redis client error:', error)
})

redisClient.on('end', () => {
  logger.info('Redis client disconnected')
})

// Connect to Redis
try {
  await redisClient.connect()
} catch (error) {
  logger.error('Failed to connect to Redis:', error)
}

export default redisClient
EOF

    # Logger
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/utils/logger.js" > /dev/null << 'EOF'
import winston from 'winston'
import { config } from '../config/index.js'

const logFormat = winston.format.combine(
  winston.format.timestamp(),
  winston.format.errors({ stack: true }),
  winston.format.json()
)

export const logger = winston.createLogger({
  level: config.app.env === 'production' ? 'info' : 'debug',
  format: logFormat,
  defaultMeta: { service: 'event-manager-api' },
  transports: [
    new winston.transports.File({ 
      filename: '../../logs/error.log', 
      level: 'error' 
    }),
    new winston.transports.File({ 
      filename: '../../logs/combined.log' 
    })
  ]
})

if (config.app.env !== 'production') {
  logger.add(new winston.transports.Console({
    format: winston.format.combine(
      winston.format.colorize(),
      winston.format.simple()
    )
  }))
}
EOF

    # Basic auth route
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/routes/auth.js" > /dev/null << 'EOF'
import express from 'express'
import bcrypt from 'bcryptjs'
import { UserService } from '../services/UserService.js'
import { logger } from '../utils/logger.js'

const router = express.Router()
const userService = new UserService()

// Login
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body

    const user = await userService.authenticateUser(email, password)
    if (!user) {
      req.flash('error', 'Invalid credentials')
      return res.status(401).json({ error: 'Invalid credentials' })
    }

    // Create session
    req.login(user, (err) => {
      if (err) {
        logger.error('Login session error:', err)
        return res.status(500).json({ error: 'Login failed' })
      }

      res.json({
        message: 'Login successful',
        user: {
          id: user.id,
          email: user.email,
          first_name: user.first_name,
          last_name: user.last_name,
          preferred_name: user.preferred_name,
          role: user.role,
          phone: user.phone,
          bio: user.bio,
          image_url: user.image_url,
          pronouns: user.pronouns,
          gender: user.gender,
          is_active: user.is_active,
          last_login: user.last_login
        }
      })
    })
  } catch (error) {
    logger.error('Login error:', error)
    res.status(500).json({ error: 'Login failed' })
  }
})

// Logout
router.post('/logout', (req, res) => {
  req.logout((err) => {
    if (err) {
      logger.error('Logout error:', err)
      return res.status(500).json({ error: 'Logout failed' })
    }
    res.json({ message: 'Logout successful' })
  })
})

// Get current user
router.get('/me', async (req, res) => {
  try {
    if (!req.isAuthenticated()) {
      return res.status(401).json({ error: 'Not authenticated' })
    }

    const user = await userService.getUserById(req.session.userId)
    if (!user) {
      return res.status(404).json({ error: 'User not found' })
    }

    res.json({
      user: {
        id: user.id,
        email: user.email,
        first_name: user.first_name,
        last_name: user.last_name,
        preferred_name: user.preferred_name,
        role: user.role,
        phone: user.phone,
        bio: user.bio,
        image_url: user.image_url,
        pronouns: user.pronouns,
        gender: user.gender,
        is_active: user.is_active,
        last_login: user.last_login
      }
    })
  } catch (error) {
    logger.error('Get user error:', error)
    res.status(500).json({ error: 'Failed to get user' })
  }
})

export default router
EOF

    # User service
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/services/UserService.js" > /dev/null << 'EOF'
import bcrypt from 'bcryptjs'
import { db } from '../database/connection.js'
import { logger } from '../utils/logger.js'

export class UserService {
  async authenticateUser(email, password) {
    try {
      const user = await db('users')
        .where({ email: email.toLowerCase(), is_active: true })
        .first()

      if (!user) {
        return null
      }

      const isValidPassword = await bcrypt.compare(password, user.password_hash)
      if (!isValidPassword) {
        return null
      }

      // Update last login
      await db('users')
        .where({ id: user.id })
        .update({ last_login: new Date() })

      return user
    } catch (error) {
      logger.error('Authentication error:', error)
      return null
    }
  }

  async getUserById(id) {
    try {
      return await db('users')
        .where({ id, is_active: true })
        .first()
    } catch (error) {
      logger.error('Get user by ID error:', error)
      return null
    }
  }

  async createUser(userData) {
    try {
      const hashedPassword = await bcrypt.hash(userData.password, 12)
      
      const [user] = await db('users')
        .insert({
          ...userData,
          password_hash: hashedPassword,
          email: userData.email.toLowerCase(),
          created_at: new Date(),
          updated_at: new Date()
        })
        .returning('*')

      return user
    } catch (error) {
      logger.error('Create user error:', error)
      throw error
    }
  }
}
EOF

    # Create placeholder route files
    for route in events contests categories scoring users results files settings backup print templates tally-master emcee auditor board database-browser; do
      sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/routes/$route.js" > /dev/null << EOF
import express from 'express'
const router = express.Router()

// Placeholder route
router.get('/', (req, res) => {
  res.json({ message: '$route API endpoint' })
})

export default router
EOF
    done

    log_success "Essential backend files generated"
}

# Generate database migrations and seeds
generate_database_files() {
    log_step "Generating Database Files"
    
    # Users table migration
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/database/migrations/001_create_users_table.js" > /dev/null << 'EOF'
export function up(knex) {
  return knex.schema.createTable('users', function (table) {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('email').unique().notNullable()
    table.string('password_hash').notNullable()
    table.string('first_name').notNullable()
    table.string('last_name').notNullable()
    table.string('preferred_name')
    table.string('role').notNullable().defaultTo('contestant')
    table.string('phone')
    table.text('bio')
    table.string('image_url')
    table.string('pronouns')
    table.string('gender')
    table.boolean('is_active').defaultTo(true)
    table.timestamp('last_login')
    table.timestamps(true, true)
  })
}

export function down(knex) {
  return knex.schema.dropTable('users')
}
EOF

    # Events table migration
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/database/migrations/002_create_events_table.js" > /dev/null << 'EOF'
export function up(knex) {
  return knex.schema.createTable('events', function (table) {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('name').notNullable()
    table.text('description')
    table.date('start_date').notNullable()
    table.date('end_date').notNullable()
    table.string('location')
    table.string('status').defaultTo('active')
    table.uuid('created_by').references('id').inTable('users')
    table.timestamps(true, true)
  })
}

export function down(knex) {
  return knex.schema.dropTable('events')
}
EOF

    # Seed file
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/src/database/seeds/001_initial_data.js" > /dev/null << EOF
import bcrypt from 'bcryptjs'

export async function seed(knex) {
  // Deletes ALL existing entries
  await knex('users').del()
  
  // Inserts seed entries
  const hashedPassword = await bcrypt.hash('$ADMIN_PASSWORD', 12)
  
  await knex('users').insert([
    {
      id: knex.raw('gen_random_uuid()'),
      email: '$ADMIN_EMAIL',
      password_hash: hashedPassword,
      first_name: 'Admin',
      last_name: 'User',
      preferred_name: 'Admin',
      role: 'organizer',
      is_active: true,
      created_at: new Date(),
      updated_at: new Date()
    }
  ])
}
EOF

    # Migration script
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/scripts/migrate.js" > /dev/null << 'EOF'
import knex from 'knex'
import { config } from '../src/config/index.js'

const db = knex({
  client: 'pg',
  connection: {
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: config.database.password,
    database: config.database.name,
    ssl: config.database.ssl
  },
  migrations: {
    directory: './src/database/migrations'
  }
})

async function migrate() {
  try {
    console.log('Running migrations...')
    await db.migrate.latest()
    console.log('Migrations completed successfully')
  } catch (error) {
    console.error('Migration failed:', error)
    process.exit(1)
  } finally {
    await db.destroy()
  }
}

migrate()
EOF

    # Seed script
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/scripts/seed.js" > /dev/null << 'EOF'
import knex from 'knex'
import { config } from '../src/config/index.js'

const db = knex({
  client: 'pg',
  connection: {
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: config.database.password,
    database: config.database.name,
    ssl: config.database.ssl
  },
  seeds: {
    directory: './src/database/seeds'
  }
})

async function seed() {
  try {
    console.log('Running seeds...')
    await db.seed.run()
    console.log('Seeds completed successfully')
  } catch (error) {
    console.error('Seeding failed:', error)
    process.exit(1)
  } finally {
    await db.destroy()
  }
}

seed()
EOF

    # Rollback script
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-api/scripts/rollback.js" > /dev/null << 'EOF'
import knex from 'knex'
import { config } from '../src/config/index.js'

const db = knex({
  client: 'pg',
  connection: {
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: config.database.password,
    database: config.database.name,
    ssl: config.database.ssl
  },
  migrations: {
    directory: './src/database/migrations'
  }
})

async function rollback() {
  try {
    console.log('Rolling back migrations...')
    await db.migrate.rollback()
    console.log('Rollback completed successfully')
  } catch (error) {
    console.error('Rollback failed:', error)
    process.exit(1)
  } finally {
    await db.destroy()
  }
}

rollback()
EOF

    log_success "Database files generated"
}

# Generate frontend files
generate_frontend_files() {
    log_step "Generating Frontend Files"
    
    # Vite config
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/vite.config.ts" > /dev/null << 'EOF'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'
import path from 'path'

export default defineConfig({
  plugins: [react()],
  resolve: {
    alias: {
      '@': path.resolve(__dirname, './src'),
    },
  },
  server: {
    port: 5173,
    proxy: {
      '/api': {
        target: 'http://localhost:3000',
        changeOrigin: true,
      },
    },
  },
  build: {
    outDir: 'dist',
    sourcemap: false,
  },
})
EOF

    # TypeScript config
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/tsconfig.json" > /dev/null << 'EOF'
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": false,
    "noUnusedLocals": false,
    "noUnusedParameters": false,
    "noFallthroughCasesInSwitch": true,
    "baseUrl": ".",
    "paths": {
      "@/*": ["./src/*"]
    }
  },
  "include": ["src"],
  "references": [{ "path": "./tsconfig.node.json" }]
}
EOF

    # Tailwind config
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/tailwind.config.js" > /dev/null << 'EOF'
/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./index.html",
    "./src/**/*.{js,ts,jsx,tsx}",
  ],
  theme: {
    extend: {},
  },
  plugins: [],
}
EOF

    # Main HTML file
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/index.html" > /dev/null << 'EOF'
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Manager</title>
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
EOF

    # Main React entry point
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/main.tsx" > /dev/null << 'EOF'
import React from 'react'
import ReactDOM from 'react-dom/client'
import { QueryClient, QueryClientProvider } from '@tanstack/react-query'
import { ReactQueryDevtools } from '@tanstack/react-query-devtools'
import App from './App.tsx'
import './index.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      staleTime: 5 * 60 * 1000, // 5 minutes
      retry: 1,
    },
  },
})

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <QueryClientProvider client={queryClient}>
      <App />
      <ReactQueryDevtools initialIsOpen={false} />
    </QueryClientProvider>
  </React.StrictMode>,
)
EOF

    # Main App component
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/App.tsx" > /dev/null << 'EOF'
import React, { useEffect } from 'react'
import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './stores/authStore'
import LoginPage from './pages/auth/LoginPage'
import DashboardPage from './pages/DashboardPage'
import EventsPage from './pages/EventsPage'
import ContestsPage from './pages/ContestsPage'
import CategoriesPage from './pages/CategoriesPage'
import ScoringPage from './pages/ScoringPage'
import ResultsPage from './pages/ResultsPage'
import UsersPage from './pages/UsersPage'
import SettingsPage from './pages/SettingsPage'
import ProfilePage from './pages/ProfilePage'
import Layout from './components/layout/Layout'
import AuthLayout from './components/layout/AuthLayout'

function App() {
  const { checkAuth, isAuthenticated, isLoading } = useAuthStore()

  useEffect(() => {
    checkAuth()
  }, [checkAuth])

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <div className="animate-spin rounded-full h-32 w-32 border-b-2 border-blue-600"></div>
      </div>
    )
  }

  return (
    <Router>
      <Routes>
        <Route path="/login" element={
          <AuthLayout>
            <LoginPage />
          </AuthLayout>
        } />
        
        {isAuthenticated ? (
          <Route path="/" element={<Layout />}>
            <Route index element={<Navigate to="/dashboard" replace />} />
            <Route path="dashboard" element={<DashboardPage />} />
            <Route path="events" element={<EventsPage />} />
            <Route path="contests" element={<ContestsPage />} />
            <Route path="categories" element={<CategoriesPage />} />
            <Route path="scoring" element={<ScoringPage />} />
            <Route path="results" element={<ResultsPage />} />
            <Route path="users" element={<UsersPage />} />
            <Route path="settings" element={<SettingsPage />} />
            <Route path="profile" element={<ProfilePage />} />
          </Route>
        ) : (
          <Route path="*" element={<Navigate to="/login" replace />} />
        )}
      </Routes>
    </Router>
  )
}

export default App
EOF

    # CSS file
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/index.css" > /dev/null << 'EOF'
@tailwind base;
@tailwind components;
@tailwind utilities;

body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

code {
  font-family: source-code-pro, Menlo, Monaco, Consolas, 'Courier New',
    monospace;
}
EOF

    # Auth store
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/stores/authStore.ts" > /dev/null << 'EOF'
import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { api } from '../lib/api'

interface User {
  id: string
  email: string
  first_name: string
  last_name: string
  role: string
  is_active: boolean
  created_at: string
  updated_at: string
}

interface LoginCredentials {
  email: string
  password: string
}

interface AuthState {
  user: User | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
  token: string | null
}

interface AuthActions {
  setUser: (user: User | null) => void
  setLoading: (loading: boolean) => void
  setError: (error: string | null) => void
  login: (credentials: LoginCredentials) => Promise<void>
  logout: () => void
  clearError: () => void
  checkAuth: () => Promise<void>
  updateProfile: (data: Partial<User> & Record<string, any>) => Promise<void>
}

type AuthStore = AuthState & AuthActions

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      // State
      user: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,
      token: null,

      // Actions
      setUser: (user) => {
        set({ user, isAuthenticated: !!user })
      },

      setLoading: (isLoading) => {
        set({ isLoading })
      },

      setError: (error) => {
        set({ error })
      },

      login: async (credentials) => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.post('/auth/login', credentials)
          const { user, token } = response.data
          set({ 
            user, 
            isAuthenticated: true, 
            isLoading: false, 
            error: null,
            token: token || 'session-token'
          })
        } catch (err: any) {
          set({ 
            error: err.response?.data?.message || 'Login failed', 
            isLoading: false 
          })
          throw err
        }
      },

      logout: () => {
        set({ 
          user: null, 
          isAuthenticated: false, 
          isLoading: false, 
          error: null,
          token: null
        })
      },

      clearError: () => {
        set({ error: null })
      },

      checkAuth: async () => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.get('/auth/me')
          const user = response.data?.user ?? response.data
          if (user) {
            set({ user, isAuthenticated: true, isLoading: false })
          } else {
            set({ isAuthenticated: false, isLoading: false })
          }
        } catch (error) {
          set({ isAuthenticated: false, isLoading: false })
        }
      },
      
      updateProfile: async (data) => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.put('/auth/profile', data)
          const updatedUser = response.data?.user ?? response.data
          if (updatedUser) {
            set({ user: updatedUser, isLoading: false })
          } else {
            set({ isLoading: false })
          }
        } catch (err: any) {
          set({ 
            error: err.response?.data?.message || 'Profile update failed', 
            isLoading: false 
          })
          throw err
        }
      },
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)

// Selectors
export const useUser = () => useAuthStore((state) => state.user)
export const useIsAuthenticated = () => useAuthStore((state) => state.isAuthenticated)
export const useAuthLoading = () => useAuthStore((state) => state.isLoading)
export const useAuthError = () => useAuthStore((state) => state.error)
export const useToken = () => useAuthStore((state) => state.token)
EOF

    # API client
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/lib/api.ts" > /dev/null << 'EOF'
import axios from 'axios'

// Ensure all requests include credentials (session cookies)
axios.defaults.withCredentials = true

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000/api'

// Create axios instance
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  withCredentials: true,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor
apiClient.interceptors.request.use(
  (config) => {
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// API functions
export const api = {
  // Generic methods
  get: (url: string, config?: any) =>
    apiClient.get(url, config),
  
  post: (url: string, data?: any, config?: any) =>
    apiClient.post(url, data, config),
  
  put: (url: string, data?: any, config?: any) =>
    apiClient.put(url, data, config),
  
  delete: (url: string, config?: any) =>
    apiClient.delete(url, config),

  // Auth
  login: (credentials: { email: string; password: string }) =>
    apiClient.post('/auth/login', credentials),
  
  logout: () =>
    apiClient.post('/auth/logout'),
  
  getProfile: () =>
    apiClient.get('/auth/me'),
}

export default apiClient
EOF

    log_success "Frontend files generated"
}

# Generate essential frontend components
generate_frontend_components() {
    log_step "Generating Frontend Components"
    
    # Login page
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/pages/auth/LoginPage.tsx" > /dev/null << 'EOF'
import React, { useState } from 'react'
import { useAuthStore } from '../../stores/authStore'
import toast from 'react-hot-toast'

const LoginPage = () => {
  const [formData, setFormData] = useState({
    email: '',
    password: ''
  })
  const { login, isLoading, error } = useAuthStore()

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      await login(formData)
      toast.success('Login successful!')
    } catch (err) {
      toast.error('Login failed')
    }
  }

  const handleChange = (e: React.ChangeEvent<HTMLInputElement>) => {
    setFormData(prev => ({
      ...prev,
      [e.target.name]: e.target.value
    }))
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50 py-12 px-4 sm:px-6 lg:px-8">
      <div className="max-w-md w-full space-y-8">
        <div>
          <h2 className="mt-6 text-center text-3xl font-extrabold text-gray-900">
            Sign in to Event Manager
          </h2>
        </div>
        <form className="mt-8 space-y-6" onSubmit={handleSubmit}>
          <div className="rounded-md shadow-sm -space-y-px">
            <div>
              <label htmlFor="email" className="sr-only">
                Email address
              </label>
              <input
                id="email"
                name="email"
                type="email"
                required
                className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-t-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Email address"
                value={formData.email}
                onChange={handleChange}
              />
            </div>
            <div>
              <label htmlFor="password" className="sr-only">
                Password
              </label>
              <input
                id="password"
                name="password"
                type="password"
                required
                className="appearance-none rounded-none relative block w-full px-3 py-2 border border-gray-300 placeholder-gray-500 text-gray-900 rounded-b-md focus:outline-none focus:ring-blue-500 focus:border-blue-500 focus:z-10 sm:text-sm"
                placeholder="Password"
                value={formData.password}
                onChange={handleChange}
              />
            </div>
          </div>

          {error && (
            <div className="text-red-600 text-sm text-center">
              {error}
            </div>
          )}

          <div>
            <button
              type="submit"
              disabled={isLoading}
              className="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 disabled:opacity-50"
            >
              {isLoading ? 'Signing in...' : 'Sign in'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default LoginPage
EOF

    # Layout components
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/components/layout/Layout.tsx" > /dev/null << 'EOF'
import React from 'react'
import { Outlet } from 'react-router-dom'
import Header from './Header'
import Sidebar from './Sidebar'

const Layout = () => {
  return (
    <div className="min-h-screen bg-gray-50">
      <Header />
      <div className="flex">
        <Sidebar />
        <main className="flex-1 p-6">
          <Outlet />
        </main>
      </div>
    </div>
  )
}

export default Layout
EOF

    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/components/layout/AuthLayout.tsx" > /dev/null << 'EOF'
import React from 'react'

interface AuthLayoutProps {
  children: React.ReactNode
}

const AuthLayout: React.FC<AuthLayoutProps> = ({ children }) => {
  return (
    <div className="min-h-screen bg-gray-50">
      {children}
    </div>
  )
}

export default AuthLayout
EOF

    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/components/layout/Header.tsx" > /dev/null << 'EOF'
import React from 'react'
import { useAuthStore } from '../../stores/authStore'

const Header = () => {
  const { user, logout } = useAuthStore()

  const handleLogout = () => {
    logout()
  }

  return (
    <header className="bg-white shadow">
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div className="flex justify-between items-center py-6">
          <div className="flex items-center">
            <h1 className="text-2xl font-bold text-gray-900">Event Manager</h1>
          </div>
          <div className="flex items-center space-x-4">
            <span className="text-sm text-gray-700">
              Welcome, {user?.first_name} {user?.last_name}
            </span>
            <button
              onClick={handleLogout}
              className="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-md text-sm font-medium"
            >
              Logout
            </button>
          </div>
        </div>
      </div>
    </header>
  )
}

export default Header
EOF

    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/components/layout/Sidebar.tsx" > /dev/null << 'EOF'
import React from 'react'
import { NavLink } from 'react-router-dom'

const Sidebar = () => {
  const navigation = [
    { name: 'Dashboard', href: '/dashboard' },
    { name: 'Events', href: '/events' },
    { name: 'Contests', href: '/contests' },
    { name: 'Categories', href: '/categories' },
    { name: 'Scoring', href: '/scoring' },
    { name: 'Results', href: '/results' },
    { name: 'Users', href: '/users' },
    { name: 'Settings', href: '/settings' },
    { name: 'Profile', href: '/profile' },
  ]

  return (
    <div className="w-64 bg-white shadow-lg">
      <nav className="mt-5 px-2">
        <div className="space-y-1">
          {navigation.map((item) => (
            <NavLink
              key={item.name}
              to={item.href}
              className={({ isActive }) =>
                `group flex items-center px-2 py-2 text-sm font-medium rounded-md ${
                  isActive
                    ? 'bg-blue-100 text-blue-900'
                    : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                }`
              }
            >
              {item.name}
            </NavLink>
          ))}
        </div>
      </nav>
    </div>
  )
}

export default Sidebar
EOF

    # Placeholder pages
    for page in DashboardPage EventsPage ContestsPage CategoriesPage ScoringPage ResultsPage UsersPage SettingsPage ProfilePage; do
      # Derive a human-readable title by trimming the trailing 'Page'
      display_title="${page%Page}"

      sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/event-manager-frontend/src/pages/$page.tsx" > /dev/null << EOF
import React from 'react'

const $page = () => {
  return (
    <div className="max-w-7xl mx-auto">
      <h1 className="text-2xl font-bold text-gray-900 mb-6">
        $display_title
      </h1>
      <div className="bg-white shadow rounded-lg p-6">
        <p className="text-gray-600">
          Welcome to the $display_title page. This is a placeholder component.
        </p>
      </div>
    </div>
  )
}

export default $page
EOF
    done

    log_success "Frontend components generated"
}

# Install Node.js dependencies
install_node_dependencies() {
    log_step "Installing Node.js Dependencies"
    
    # Backend dependencies
    cd "$INSTALL_DIR/event-manager-api"
    sudo -u "$SERVICE_USER" npm install --omit=dev
    
    # Frontend dependencies
    cd "$INSTALL_DIR/event-manager-frontend"
    sudo -u "$SERVICE_USER" npm install --omit=dev
    
    log_success "Node.js dependencies installed"
}

# Create environment configuration
create_environment_config() {
    log_step "Creating Environment Configuration"
    
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/.env" > /dev/null << EOF
# Event Manager Environment Configuration
NODE_ENV=production
PORT=3000
HOST=0.0.0.0

# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=$DB_NAME
DB_USER=$DB_USER
DB_PASSWORD=$DB_PASSWORD

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=$REDIS_PASSWORD
REDIS_DB=0

# Session Configuration
SESSION_SECRET=$SESSION_SECRET
SESSION_MAX_AGE=86400000

# Security Configuration
BCRYPT_ROUNDS=12
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW_MS=900000

# Application Configuration
APP_NAME=$APP_NAME
APP_VERSION=$APP_VERSION
APP_URL=http://$DOMAIN_NAME
TZ=UTC

# Features
FEATURE_REALTIME_SCORING=true
FEATURE_EMAIL_NOTIFICATIONS=false
FEATURE_FILE_UPLOADS=true
FEATURE_AUDIT_LOGGING=true
FEATURE_BACKUP_AUTOMATION=true
FEATURE_API_DOCS=true

# CORS Configuration
CORS_ORIGIN=http://$DOMAIN_NAME

# Email Configuration
EMAIL_HOST=$EMAIL_HOST
EMAIL_PORT=$EMAIL_PORT
EMAIL_USER=$EMAIL_USER
EMAIL_PASS=$EMAIL_PASS
EMAIL_SECURE=false

# Apache Configuration
APACHE_ENABLED=true
APACHE_DOCUMENT_ROOT=$INSTALL_DIR/event-manager-frontend/dist
APACHE_CONFIG_PATH=/etc/apache2/sites-available/event-manager.conf
APACHE_SSL_ENABLED=false

# Logging Configuration
LOG_LEVEL=info
LOG_FILE=$INSTALL_DIR/logs/app.log
LOG_MAX_SIZE=10m
LOG_MAX_FILES=5
EOF
    
    log_success "Environment configuration created"
}

# Setup database
setup_database() {
    log_step "Setting up Database"
    
    cd "$INSTALL_DIR/event-manager-api"
    
    # Run migrations
    sudo -u "$SERVICE_USER" npm run db:migrate
    
    # Seed database
    sudo -u "$SERVICE_USER" npm run db:seed
    
    log_success "Database setup completed"
}

# Build frontend
build_frontend() {
    log_step "Building Frontend"
    
    cd "$INSTALL_DIR/event-manager-frontend"
    
    # Create production environment file
    sudo -u "$SERVICE_USER" tee .env > /dev/null << EOF
VITE_API_URL=/api
EOF
    
    # Build frontend
    sudo -u "$SERVICE_USER" npm run build
    
    log_success "Frontend built successfully"
}

# Configure Apache
configure_apache() {
    log_step "Configuring Apache"
    
    # Enable required modules
    sudo a2enmod proxy
    sudo a2enmod proxy_http
    sudo a2enmod proxy_wstunnel
    sudo a2enmod proxy_html
    sudo a2enmod rewrite
    sudo a2enmod headers
    sudo a2enmod ssl
    
    # Create Apache virtual host
    sudo tee /etc/apache2/sites-available/event-manager.conf > /dev/null << EOF
<VirtualHost *:80>
    ServerName $DOMAIN_NAME
    ServerAlias *
    DocumentRoot $INSTALL_DIR/event-manager-frontend/dist
    
    # Proxy API requests to Node.js backend
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:3000/api/
    ProxyPassReverse /api/ http://localhost:3000/api/
    
    # Proxy WebSocket connections
    ProxyPass /socket.io/ ws://localhost:3000/socket.io/
    ProxyPassReverse /socket.io/ ws://localhost:3000/socket.io/
    
    # Serve static files
    <Directory "$INSTALL_DIR/event-manager-frontend/dist">
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Handle client-side routing
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    Header always set Referrer-Policy "strict-origin-when-cross-origin"
    Header always set Permissions-Policy "geolocation=(), microphone=(), camera=()"
    
    # CORS headers for API
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-CSRF-Token"
    Header always set Access-Control-Allow-Credentials "true"
    
    # Logging
    ErrorLog \${APACHE_LOG_DIR}/event-manager_error.log
    CustomLog \${APACHE_LOG_DIR}/event-manager_access.log combined
</VirtualHost>
EOF
    
    # Enable site and disable default
    sudo a2ensite event-manager
    sudo a2dissite 000-default
    
    # Test configuration
    sudo apache2ctl configtest
    
    # Restart Apache
    sudo systemctl restart apache2
    sudo systemctl enable apache2
    
    log_success "Apache configured successfully"
}

# Setup PM2 process management
setup_pm2() {
    log_step "Setting up PM2 Process Management"
    
    # Create PM2 ecosystem file
    sudo -u "$SERVICE_USER" tee "$INSTALL_DIR/ecosystem.config.js" > /dev/null << EOF
module.exports = {
  apps: [{
    name: 'event-manager-api',
    script: 'src/server.js',
    cwd: '$INSTALL_DIR/event-manager-api',
    instances: 1,
    exec_mode: 'fork',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    log_file: '$INSTALL_DIR/logs/combined.log',
    out_file: '$INSTALL_DIR/logs/out.log',
    error_file: '$INSTALL_DIR/logs/error.log',
    log_date_format: 'YYYY-MM-DD HH:mm:ss Z',
    merge_logs: true,
    max_memory_restart: '1G',
    node_args: '--max-old-space-size=1024',
    restart_delay: 4000,
    max_restarts: 10,
    min_uptime: '10s'
  }]
};
EOF
    
    # Install PM2 globally for the service user
    sudo -u "$SERVICE_USER" npm install -g pm2
    
    # Start application with PM2
    cd "$INSTALL_DIR"
    sudo -u "$SERVICE_USER" pm2 start ecosystem.config.js
    sudo -u "$SERVICE_USER" pm2 save
    sudo -u "$SERVICE_USER" pm2 startup systemd -u "$SERVICE_USER" --hp "$INSTALL_DIR"
    
    log_success "PM2 process management configured"
}

# Setup firewall
setup_firewall() {
    log_step "Setting up Firewall"
    
    # Enable UFW if not already enabled
    sudo ufw --force enable
    
    # Allow SSH
    sudo ufw allow ssh
    
    # Allow HTTP and HTTPS
    sudo ufw allow 80/tcp
    sudo ufw allow 443/tcp
    
    # Allow internal communication
    sudo ufw allow from 127.0.0.1 to any port 3000
    sudo ufw allow from 127.0.0.1 to any port 5432
    sudo ufw allow from 127.0.0.1 to any port 6379
    
    log_success "Firewall configured"
}

# Create systemd service
create_systemd_service() {
    log_step "Creating Systemd Service"
    
    sudo tee /etc/systemd/system/event-manager.service > /dev/null << EOF
[Unit]
Description=Event Manager API Server
After=network.target postgresql.service redis.service

[Service]
Type=forking
User=$SERVICE_USER
Group=$SERVICE_USER
WorkingDirectory=$INSTALL_DIR
ExecStart=/usr/bin/pm2 start ecosystem.config.js
ExecReload=/usr/bin/pm2 reload ecosystem.config.js
ExecStop=/usr/bin/pm2 stop ecosystem.config.js
Restart=always
RestartSec=10

[Install]
WantedBy=multi-user.target
EOF
    
    sudo systemctl daemon-reload
    sudo systemctl enable event-manager
    
    log_success "Systemd service created"
}

# Final setup and verification
final_setup() {
    log_step "Performing Final Setup and Verification"
    
    # Set proper permissions
    sudo chown -R "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR"
    sudo chmod -R 755 "$INSTALL_DIR"
    
    # Create uploads directory
    sudo mkdir -p "$INSTALL_DIR/uploads"
    sudo chown "$SERVICE_USER:$SERVICE_USER" "$INSTALL_DIR/uploads"
    
    # Start services
    sudo systemctl start event-manager
    sudo systemctl start apache2
    
    # Wait for services to start
    sleep 5
    
    # Verify services are running
    if systemctl is-active --quiet event-manager; then
        log_success "Event Manager service is running"
    else
        log_error "Event Manager service failed to start"
        sudo systemctl status event-manager
    fi
    
    if systemctl is-active --quiet apache2; then
        log_success "Apache service is running"
    else
        log_error "Apache service failed to start"
        sudo systemctl status apache2
    fi
    
    # Test API health
    if curl -s http://localhost:3000/api/health > /dev/null; then
        log_success "API health check passed"
    else
        log_warning "API health check failed - service may still be starting"
    fi
    
    # Test frontend
    if curl -s http://localhost/ > /dev/null; then
        log_success "Frontend is accessible"
    else
        log_warning "Frontend may not be accessible yet"
    fi
}

# Display installation summary
display_summary() {
    log_success "Installation completed successfully!"
    echo
    echo "=========================================="
    echo "Event Manager Installation Summary"
    echo "=========================================="
    echo
    echo "Application Details:"
    echo "  Name: $APP_NAME"
    echo "  Version: $APP_VERSION"
    echo "  Installation Directory: $INSTALL_DIR"
    echo "  Service User: $SERVICE_USER"
    echo
    echo "Access Information:"
    echo "  Frontend URL: http://$DOMAIN_NAME"
    echo "  API URL: http://$DOMAIN_NAME/api"
    echo "  Health Check: http://$DOMAIN_NAME/api/health"
    echo
    echo "Default Login Credentials:"
    echo "  Email: $ADMIN_EMAIL"
    echo "  Password: [as configured]"
    echo
    echo "Database Information:"
    echo "  Database: $DB_NAME"
    echo "  User: $DB_USER"
    echo "  Password: [as configured]"
    echo
    echo "Redis Information:"
    echo "  Password: [as configured]"
    echo
    echo "Service Management:"
    echo "  Start: sudo systemctl start event-manager"
    echo "  Stop: sudo systemctl stop event-manager"
    echo "  Restart: sudo systemctl restart event-manager"
    echo "  Status: sudo systemctl status event-manager"
    echo
    echo "Logs:"
    echo "  Application: $INSTALL_DIR/logs/"
    echo "  Apache: /var/log/apache2/"
    echo "  System: sudo journalctl -u event-manager"
    echo
    echo "Configuration Files:"
    echo "  Environment: $INSTALL_DIR/.env"
    echo "  Apache: /etc/apache2/sites-available/event-manager.conf"
    echo "  PM2: $INSTALL_DIR/ecosystem.config.js"
    echo
    echo "Next Steps:"
    echo "  1. Access the application at http://$DOMAIN_NAME"
    echo "  2. Login with the configured credentials"
    echo "  3. Configure SSL certificates if needed"
    echo "  4. Review and adjust configuration in $INSTALL_DIR/.env"
    echo
    echo "For support, check the logs or visit the documentation."
    echo "=========================================="
}

# Main installation function
main() {
    echo "=========================================="
    echo "Event Manager One-Click Installation Script"
    echo "Version: $APP_VERSION"
    echo "Target: Ubuntu 24.04"
    echo "=========================================="
    echo
    
    check_root
    check_ubuntu_version
    collect_configuration
    
    log_info "Starting installation process..."
    
    update_system
    install_system_dependencies
    create_app_user
    setup_postgresql
    setup_redis
    create_application_structure
    generate_backend_package_json
    generate_frontend_package_json
    generate_backend_config
    generate_essential_backend_files
    generate_database_files
    generate_frontend_files
    generate_frontend_components
    install_node_dependencies
    create_environment_config
    setup_database
    build_frontend
    configure_apache
    setup_pm2
    setup_firewall
    create_systemd_service
    final_setup
    display_summary
    
    log_success "Installation completed successfully!"
}

# Run main function
main "$@"
