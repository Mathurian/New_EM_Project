import { logger } from '../utils/logger.js'

/**
 * Authentication middleware
 * Checks if user is authenticated via session
 */
export const requireAuth = (req, res, next) => {
  if (!req.isAuthenticated()) {
    return res.status(401).json({ error: 'Authentication required' })
  }
  next()
}

/**
 * Role-based access control middleware
 * @param {string[]} allowedRoles - Array of allowed roles
 */
export const requireRole = (allowedRoles) => {
  return (req, res, next) => {
    if (!req.isAuthenticated()) {
      return res.status(401).json({ error: 'Authentication required' })
    }

    if (!allowedRoles.includes(req.session.userRole)) {
      return res.status(403).json({ error: 'Insufficient permissions' })
    }

    next()
  }
}

/**
 * Permission-based access control middleware
 * @param {string} permission - Required permission
 */
export const requirePermission = (permission) => {
  return (req, res, next) => {
    if (!req.isAuthenticated()) {
      return res.status(401).json({ error: 'Authentication required' })
    }

    const hasPermission = checkUserPermission(req.session.userRole, permission)
    
    if (!hasPermission) {
      return res.status(403).json({ error: 'Insufficient permissions' })
    }

    next()
  }
}

/**
 * Check if user role has specific permission
 * @param {string} role - User role
 * @param {string} permission - Required permission
 * @returns {boolean}
 */
function checkUserPermission(role, permission) {
  const rolePermissions = {
    organizer: ['*'], // All permissions
    judge: ['scoring:read', 'scoring:write', 'results:read'],
    contestant: ['results:read'],
    emcee: ['results:read'],
    tally_master: ['scoring:read', 'results:read', 'results:write'],
    auditor: ['scoring:read', 'results:read'],
    board: ['results:read', 'reports:read']
  }

  const permissions = rolePermissions[role] || []
  return permissions.includes('*') || permissions.includes(permission)
}

/**
 * Optional authentication middleware
 * Sets req.user if authenticated, but doesn't require it
 */
export const optionalAuth = (req, res, next) => {
  if (req.isAuthenticated()) {
    // User is authenticated, continue
    next()
  } else {
    // User is not authenticated, but that's okay
    next()
  }
}

/**
 * Admin only middleware
 * Requires organizer role
 */
export const requireAdmin = requireRole(['organizer'])

/**
 * Judge or higher middleware
 * Requires judge, tally_master, auditor, organizer, or board role
 */
export const requireJudgeOrHigher = requireRole(['judge', 'tally_master', 'auditor', 'organizer', 'board'])

/**
 * Tally master or higher middleware
 * Requires tally_master, auditor, organizer, or board role
 */
export const requireTallyMasterOrHigher = requireRole(['tally_master', 'auditor', 'organizer', 'board'])

/**
 * Auditor or higher middleware
 * Requires auditor, organizer, or board role
 */
export const requireAuditorOrHigher = requireRole(['auditor', 'organizer', 'board'])

/**
 * Board or organizer middleware
 * Requires board or organizer role
 */
export const requireBoardOrOrganizer = requireRole(['board', 'organizer'])

export default {
  requireAuth,
  requireRole,
  requirePermission,
  optionalAuth,
  requireAdmin,
  requireJudgeOrHigher,
  requireTallyMasterOrHigher,
  requireAuditorOrHigher,
  requireBoardOrOrganizer
}
