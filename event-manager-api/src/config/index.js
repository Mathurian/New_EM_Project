import dotenv from 'dotenv'
import { fileURLToPath } from 'url'
import { dirname, join } from 'path'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

// Load environment variables
dotenv.config({ path: join(__dirname, '../../.env') })

/**
 * Application configuration with environment-specific settings
 */
export const config = {
  app: {
    name: process.env.APP_NAME || 'Event Manager',
    version: process.env.APP_VERSION || '2.0.0',
    env: process.env.NODE_ENV || 'development',
    port: parseInt(process.env.PORT) || 3000,
    host: process.env.HOST || '0.0.0.0',
    url: process.env.APP_URL || 'http://localhost:3000',
    timezone: process.env.TZ || 'UTC',
    debug: process.env.DEBUG === 'true'
  },

  database: {
    host: process.env.DB_HOST || 'localhost',
    port: parseInt(process.env.DB_PORT) || 5432,
    name: process.env.DB_NAME || 'event_manager',
    user: process.env.DB_USER || 'event_manager',
    password: process.env.DB_PASSWORD || 'password',
    ssl: process.env.DB_SSL === 'true',
    maxConnections: parseInt(process.env.DB_MAX_CONNECTIONS) || 20,
    minConnections: parseInt(process.env.DB_MIN_CONNECTIONS) || 2
  },

  redis: {
    host: process.env.REDIS_HOST || 'localhost',
    port: parseInt(process.env.REDIS_PORT) || 6379,
    password: process.env.REDIS_PASSWORD || null,
    db: parseInt(process.env.REDIS_DB) || 0,
    keyPrefix: process.env.REDIS_KEY_PREFIX || 'event_manager:',
    ttl: parseInt(process.env.REDIS_TTL) || 3600
  },

  session: {
    secret: process.env.SESSION_SECRET || 'your-super-secret-session-key-change-in-production',
    maxAge: parseInt(process.env.SESSION_MAX_AGE) || 86400000, // 24 hours
    secure: process.env.SESSION_SECURE === 'true',
    httpOnly: true,
    sameSite: 'lax'
  },

  security: {
    bcryptRounds: parseInt(process.env.BCRYPT_ROUNDS) || 12,
    csrfSecret: process.env.CSRF_SECRET || 'your-csrf-secret-key',
    rateLimitMax: parseInt(process.env.RATE_LIMIT_MAX) || 100,
    rateLimitWindowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 900000, // 15 minutes
    sessionTimeout: parseInt(process.env.SESSION_TIMEOUT) || 1800000, // 30 minutes
    maxFileSize: parseInt(process.env.MAX_FILE_SIZE) || 5242880, // 5MB
    allowedFileTypes: (process.env.ALLOWED_FILE_TYPES || 'image/jpeg,image/png,image/gif,application/pdf').split(',')
  },

  email: {
    host: process.env.EMAIL_HOST || 'localhost',
    port: parseInt(process.env.EMAIL_PORT) || 587,
    secure: process.env.EMAIL_SECURE === 'true',
    auth: {
      user: process.env.EMAIL_USER || '',
      pass: process.env.EMAIL_PASS || ''
    },
    from: process.env.EMAIL_FROM || 'noreply@eventmanager.com'
  },

  logging: {
    level: process.env.LOG_LEVEL || 'info',
    file: process.env.LOG_FILE || './logs/app.log',
    maxSize: process.env.LOG_MAX_SIZE || '10m',
    maxFiles: parseInt(process.env.LOG_MAX_FILES) || 5,
    datePattern: process.env.LOG_DATE_PATTERN || 'YYYY-MM-DD'
  },

  features: {
    realTimeScoring: process.env.FEATURE_REALTIME_SCORING === 'true',
    emailNotifications: process.env.FEATURE_EMAIL_NOTIFICATIONS === 'true',
    fileUploads: process.env.FEATURE_FILE_UPLOADS === 'true',
    auditLogging: process.env.FEATURE_AUDIT_LOGGING === 'true',
    backupAutomation: process.env.FEATURE_BACKUP_AUTOMATION === 'true',
    apiDocumentation: process.env.FEATURE_API_DOCS === 'true'
  },

  cors: {
    origin: process.env.CORS_ORIGIN ? process.env.CORS_ORIGIN.split(',') : true,
    credentials: true,
    methods: ['GET', 'POST', 'PUT', 'DELETE', 'PATCH', 'OPTIONS'],
    allowedHeaders: ['Content-Type', 'Authorization', 'X-CSRF-Token']
  },

  apache: {
    enabled: process.env.APACHE_ENABLED === 'true',
    documentRoot: process.env.APACHE_DOCUMENT_ROOT || '/var/www/html',
    configPath: process.env.APACHE_CONFIG_PATH || '/etc/apache2/sites-available/event-manager.conf',
    sslEnabled: process.env.APACHE_SSL_ENABLED === 'true',
    sslCertPath: process.env.APACHE_SSL_CERT_PATH || '/etc/ssl/certs/event-manager.crt',
    sslKeyPath: process.env.APACHE_SSL_KEY_PATH || '/etc/ssl/private/event-manager.key'
  }
}

// Validation
const requiredEnvVars = ['SESSION_SECRET', 'DB_PASSWORD']
const missingVars = requiredEnvVars.filter(varName => !process.env[varName])

if (missingVars.length > 0 && config.app.env === 'production') {
  throw new Error(`Missing required environment variables: ${missingVars.join(', ')}`)
}

export default config