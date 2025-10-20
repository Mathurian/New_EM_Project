const jwt = require('jsonwebtoken');
const { PrismaClient } = require('@prisma/client');

const prisma = new PrismaClient();

// Middleware to verify JWT token
const authenticateToken = async (req, res, next) => {
  try {
    const authHeader = req.headers['authorization'];
    const token = authHeader && authHeader.split(' ')[1]; // Bearer TOKEN

    if (!token) {
      return res.status(401).json({ error: 'Access token required' });
    }

    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    
    // Verify user still exists and session is valid
    const user = await prisma.user.findUnique({
      where: { id: decoded.userId },
      select: {
        id: true,
        name: true,
        preferredName: true,
        email: true,
        role: true,
        sessionVersion: true
      }
    });

    if (!user) {
      return res.status(401).json({ error: 'User not found' });
    }

    // Check session version (for logout functionality)
    if (user.sessionVersion !== decoded.sessionVersion) {
      return res.status(401).json({ error: 'Session expired' });
    }

    req.user = {
      userId: user.id,
      userName: user.preferredName || user.name,
      email: user.email,
      role: user.role,
      sessionVersion: user.sessionVersion
    };

    next();
  } catch (error) {
    if (error.name === 'JsonWebTokenError') {
      return res.status(401).json({ error: 'Invalid token' });
    }
    if (error.name === 'TokenExpiredError') {
      return res.status(401).json({ error: 'Token expired' });
    }
    return res.status(500).json({ error: 'Authentication error' });
  }
};

// Middleware to check if user has required role
const requireRole = (roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' });
    }

    const userRole = req.user.role;
    const allowedRoles = Array.isArray(roles) ? roles : [roles];

    if (!allowedRoles.includes(userRole)) {
      return res.status(403).json({ 
        error: 'Insufficient permissions',
        required: allowedRoles,
        current: userRole
      });
    }

    next();
  };
};

// Middleware to check if user is organizer or higher
const requireOrganizer = requireRole(['ORGANIZER']);

// Middleware to check if user is board member or higher
const requireBoard = requireRole(['BOARD', 'ORGANIZER']);

// Middleware to check if user is judge
const requireJudge = requireRole(['JUDGE', 'ORGANIZER', 'BOARD']);

// Middleware to check if user is tally master
const requireTallyMaster = requireRole(['TALLY_MASTER', 'ORGANIZER', 'BOARD']);

// Middleware to check if user is auditor
const requireAuditor = requireRole(['AUDITOR', 'ORGANIZER', 'BOARD']);

// Middleware to check if user is emcee
const requireEmcee = requireRole(['EMCEE', 'ORGANIZER', 'BOARD']);

// Middleware to check if user is contestant
const requireContestant = requireRole(['CONTESTANT', 'ORGANIZER', 'BOARD']);

// Middleware to check if user can access their own data or is admin
const requireOwnershipOrAdmin = (resourceUserIdField = 'userId') => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' });
    }

    const userRole = req.user.role;
    const userId = req.user.userId;
    const resourceUserId = req.params[resourceUserIdField] || req.body[resourceUserIdField];

    // Allow if user is organizer/board or accessing their own data
    if (['ORGANIZER', 'BOARD'].includes(userRole) || userId === resourceUserId) {
      return next();
    }

    return res.status(403).json({ 
      error: 'Access denied - insufficient permissions'
    });
  };
};

// Middleware to validate request body
const validateRequest = (validationRules) => {
  return (req, res, next) => {
    const errors = [];

    for (const [field, rules] of Object.entries(validationRules)) {
      const value = req.body[field];

      if (rules.required && (!value || value.toString().trim() === '')) {
        errors.push(`${field} is required`);
        continue;
      }

      if (value && rules.type) {
        if (rules.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
          errors.push(`${field} must be a valid email address`);
        }
        if (rules.type === 'number' && isNaN(Number(value))) {
          errors.push(`${field} must be a number`);
        }
        if (rules.type === 'date' && isNaN(Date.parse(value))) {
          errors.push(`${field} must be a valid date`);
        }
      }

      if (value && rules.minLength && value.length < rules.minLength) {
        errors.push(`${field} must be at least ${rules.minLength} characters`);
      }

      if (value && rules.maxLength && value.length > rules.maxLength) {
        errors.push(`${field} must be no more than ${rules.maxLength} characters`);
      }

      if (value && rules.min && Number(value) < rules.min) {
        errors.push(`${field} must be at least ${rules.min}`);
      }

      if (value && rules.max && Number(value) > rules.max) {
        errors.push(`${field} must be no more than ${rules.max}`);
      }

      if (value && rules.enum && !rules.enum.includes(value)) {
        errors.push(`${field} must be one of: ${rules.enum.join(', ')}`);
      }
    }

    if (errors.length > 0) {
      return res.status(400).json({ errors });
    }

    next();
  };
};

// Middleware to sanitize input
const sanitizeInput = (req, res, next) => {
  const sanitize = (obj) => {
    if (typeof obj === 'string') {
      return obj.trim();
    }
    if (typeof obj === 'object' && obj !== null) {
      const sanitized = {};
      for (const [key, value] of Object.entries(obj)) {
        sanitized[key] = sanitize(value);
      }
      return sanitized;
    }
    return obj;
  };

  if (req.body) {
    req.body = sanitize(req.body);
  }

  next();
};

// Middleware to log requests
const logRequest = (req, res, next) => {
  const start = Date.now();
  
  res.on('finish', () => {
    const duration = Date.now() - start;
    const logData = {
      method: req.method,
      url: req.originalUrl,
      status: res.statusCode,
      duration: `${duration}ms`,
      ip: req.ip,
      userAgent: req.get('User-Agent'),
      userId: req.user?.userId
    };

    if (res.statusCode >= 400) {
      console.error('Request Error:', logData);
    } else {
      console.log('Request:', logData);
    }
  });

  next();
};

module.exports = {
  authenticateToken,
  requireRole,
  requireOrganizer,
  requireBoard,
  requireJudge,
  requireTallyMaster,
  requireAuditor,
  requireEmcee,
  requireContestant,
  requireOwnershipOrAdmin,
  validateRequest,
  sanitizeInput,
  logRequest
};
