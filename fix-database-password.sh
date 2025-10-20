#!/bin/bash

# Fix database password issue - ensure password is passed as string
set -e

INSTALL_DIR="/opt/event-manager"
ENV_FILE="$INSTALL_DIR/.env"
CONNECTION_FILE="$INSTALL_DIR/event-manager-api/src/database/connection.js"

echo "Fixing database password configuration..."

# Check current .env file
echo "Current .env file contents:"
sudo cat "$ENV_FILE" | grep -E "(DB_|REDIS_)"

echo
echo "Checking database connection file..."

# Backup files
sudo cp "$CONNECTION_FILE" "$CONNECTION_FILE.backup"

# Fix the database connection to ensure password is always a string
sudo tee "$CONNECTION_FILE" > /dev/null << 'EOF'
import knex from 'knex'
import { config } from '../config/index.js'

// Ensure password is always a string
const dbPassword = String(config.database.password || '')

export const db = knex({
  client: 'pg',
  connection: {
    host: config.database.host,
    port: config.database.port,
    user: config.database.user,
    password: dbPassword,
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
    console.log('Testing database connection with password type:', typeof dbPassword, 'length:', dbPassword.length)
    await db.raw('SELECT 1')
    console.log('Database connection successful')
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

echo "Database connection file updated"

# Also fix the config file to ensure proper environment loading
CONFIG_FILE="$INSTALL_DIR/event-manager-api/src/config/index.js"
sudo cp "$CONFIG_FILE" "$CONFIG_FILE.backup"

sudo tee "$CONFIG_FILE" > /dev/null << 'EOF'
import dotenv from 'dotenv'
import path from 'path'
import { fileURLToPath } from 'url'

const __filename = fileURLToPath(import.meta.url)
const __dirname = path.dirname(__filename)

// Load environment variables with explicit path
const envPath = path.join(__dirname, '../../.env')
console.log('Loading environment from:', envPath)
dotenv.config({ path: envPath })

// Ensure all database values are properly typed
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
    password: String(process.env.DB_PASSWORD || ''), // Ensure password is always a string
    ssl: process.env.DB_SSL === 'true' ? { rejectUnauthorized: false } : false
  },
  
  redis: {
    host: process.env.REDIS_HOST || 'localhost',
    port: parseInt(process.env.REDIS_PORT) || 6379,
    password: String(process.env.REDIS_PASSWORD || ''), // Ensure password is always a string
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

// Debug logging
console.log('Database config:', {
  host: config.database.host,
  port: config.database.port,
  name: config.database.name,
  user: config.database.user,
  passwordType: typeof config.database.password,
  passwordLength: config.database.password ? config.database.password.length : 0
})
EOF

echo "Config file updated"

# Restart the service
echo "Restarting Event Manager service..."
sudo systemctl restart event-manager

sleep 5

# Check service status
if sudo systemctl is-active --quiet event-manager; then
    echo "✅ Service is running!"
    
    # Test the API endpoint
    echo "Testing API endpoint..."
    sleep 2
    curl -X POST http://localhost:3000/api/auth/login \
      -H "Content-Type: application/json" \
      -d '{"email":"admin@okckinkweekend.com","password":"Dittibop5!"}' \
      -w "\nHTTP Status: %{http_code}\n" || echo "API test failed"
else
    echo "❌ Service failed to start. Checking logs..."
    sudo journalctl -u event-manager --no-pager -l --since "1 minute ago"
fi

echo "Fix completed!"
