create_middleware_files() {
    print_status "Creating middleware files..."
    
    # Authentication middleware
    cat > "$APP_DIR/src/middleware/auth.js" << 'EOF'
const jwt = require('jsonwebtoken')
const { PrismaClient } = require('@prisma/client')

const prisma = new PrismaClient()
const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key'

const authenticateToken = async (req, res, next) => {
  const authHeader = req.headers['authorization']
  const token = authHeader && authHeader.split(' ')[1]

  if (!token) {
    return res.status(401).json({ error: 'Access token required' })
  }

  try {
    const decoded = jwt.verify(token, JWT_SECRET)
    const user = await prisma.user.findUnique({
      where: { id: decoded.userId },
      include: {
        judge: true,
        contestant: true
      }
    })

    if (!user) {
      return res.status(401).json({ error: 'Invalid token' })
    }

    req.user = user
    next()
  } catch (error) {
    return res.status(403).json({ error: 'Invalid or expired token' })
  }
}

const requireRole = (roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' })
    }

    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'Insufficient permissions' })
    }

    next()
  }
}

module.exports = {
  authenticateToken,
  requireRole
}
EOF

    # Rate limiting middleware
    cat > "$APP_DIR/src/middleware/rateLimiting.js" << 'EOF'
const rateLimit = require('express-rate-limit')

// General API rate limiter
const generalLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 5000, // 5000 requests per 15 minutes
  standardHeaders: true,
  legacyHeaders: false,
  trustProxy: true,
  skip: (req) => {
    return req.path === '/health' || 
           req.path.startsWith('/api/auth/') ||
           req.path.startsWith('/api/admin/')
  }
})

// Auth endpoints rate limiter
const authLimiter = rateLimit({
  windowMs: 15 * 60 * 1000, // 15 minutes
  max: 10000, // 10000 requests per 15 minutes for auth
  standardHeaders: true,
  legacyHeaders: false,
  trustProxy: true
})

module.exports = {
  generalLimiter,
  authLimiter
}
EOF

    # Error handling middleware
    cat > "$APP_DIR/src/middleware/errorHandler.js" << 'EOF'
const logActivity = (action, resourceType = null, resourceId = null) => {
  return async (req, res, next) => {
    const originalSend = res.send
    
    res.send = function(data) {
      // Log activity after response is sent
      if (req.user && res.statusCode < 400) {
        // Log to database asynchronously
        setImmediate(async () => {
          try {
            await prisma.activityLog.create({
              data: {
                userId: req.user.id,
                action: action,
                resourceType: resourceType,
                resourceId: resourceId,
                ipAddress: req.ip || req.connection.remoteAddress,
                userAgent: req.get('User-Agent') || 'Unknown',
                details: {
                  method: req.method,
                  path: req.path,
                  timestamp: new Date().toISOString()
                }
              }
            })
          } catch (error) {
            console.error('Failed to log activity:', error)
          }
        })
      }
      
      return originalSend.call(this, data)
    }
    
    next()
  }
}

const errorHandler = (err, req, res, next) => {
  console.error('Error:', err)
  
  if (err.name === 'ValidationError') {
    return res.status(400).json({ error: 'Validation error', details: err.message })
  }
  
  if (err.name === 'UnauthorizedError') {
    return res.status(401).json({ error: 'Unauthorized' })
  }
  
  if (err.name === 'ForbiddenError') {
    return res.status(403).json({ error: 'Forbidden' })
  }
  
  if (err.name === 'NotFoundError') {
    return res.status(404).json({ error: 'Not found' })
  }
  
  res.status(500).json({ error: 'Internal server error' })
}

module.exports = {
  logActivity,
  errorHandler
}
EOF

    # Validation middleware
    cat > "$APP_DIR/src/middleware/validation.js" << 'EOF'
const validateEvent = (req, res, next) => {
  const { name, description, startDate, endDate, location, maxContestants } = req.body
  
  if (!name || !description || !startDate || !endDate || !location) {
    return res.status(400).json({ error: 'Missing required fields' })
  }
  
  if (new Date(startDate) >= new Date(endDate)) {
    return res.status(400).json({ error: 'End date must be after start date' })
  }
  
  if (maxContestants && maxContestants < 1) {
    return res.status(400).json({ error: 'Max contestants must be at least 1' })
  }
  
  next()
}

const validateContest = (req, res, next) => {
  const { name, description, startDate, endDate, maxContestants, eventId } = req.body
  
  if (!name || !description || !startDate || !endDate || !eventId) {
    return res.status(400).json({ error: 'Missing required fields' })
  }
  
  if (new Date(startDate) >= new Date(endDate)) {
    return res.status(400).json({ error: 'End date must be after start date' })
  }
  
  if (maxContestants && maxContestants < 1) {
    return res.status(400).json({ error: 'Max contestants must be at least 1' })
  }
  
  next()
}

const validateCategory = (req, res, next) => {
  const { name, description, maxScore, contestId } = req.body
  
  if (!name || !description || !maxScore || !contestId) {
    return res.status(400).json({ error: 'Missing required fields' })
  }
  
  if (maxScore < 1) {
    return res.status(400).json({ error: 'Max score must be at least 1' })
  }
  
  next()
}

const validateUser = (req, res, next) => {
  const { name, email, role } = req.body
  
  if (!name || !email || !role) {
    return res.status(400).json({ error: 'Missing required fields' })
  }
  
  const validRoles = ['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD']
  if (!validRoles.includes(role)) {
    return res.status(400).json({ error: 'Invalid role' })
  }
  
  const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
  if (!emailRegex.test(email)) {
    return res.status(400).json({ error: 'Invalid email format' })
  }
  
  next()
}

module.exports = {
  validateEvent,
  validateContest,
  validateCategory,
  validateUser
}
EOF

    print_success "Middleware files created successfully"
}
