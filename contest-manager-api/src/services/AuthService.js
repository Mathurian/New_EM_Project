import jwt from 'jsonwebtoken'
import { config } from '../config/index.js'
import { UserService } from './UserService.js'

/**
 * Authentication service for JWT token management
 */
export class AuthService {
  constructor() {
    this.userService = new UserService()
  }

  /**
   * Generate access and refresh tokens
   */
  async generateTokens(user) {
    const payload = {
      userId: user.id,
      email: user.email,
      role: user.role
    }

    const accessToken = jwt.sign(payload, config.jwt.secret, {
      expiresIn: config.jwt.expiresIn,
      issuer: config.jwt.issuer,
      audience: config.jwt.audience
    })

    const refreshToken = jwt.sign(
      { userId: user.id },
      config.jwt.secret,
      {
        expiresIn: config.jwt.refreshExpiresIn,
        issuer: config.jwt.issuer,
        audience: config.jwt.audience
      }
    )

    return {
      accessToken,
      refreshToken
    }
  }

  /**
   * Verify access token
   */
  async verifyAccessToken(token) {
    try {
      return jwt.verify(token, config.jwt.secret, {
        issuer: config.jwt.issuer,
        audience: config.jwt.audience
      })
    } catch (error) {
      throw new Error('Invalid access token')
    }
  }

  /**
   * Verify refresh token
   */
  async verifyRefreshToken(token) {
    try {
      return jwt.verify(token, config.jwt.secret, {
        issuer: config.jwt.issuer,
        audience: config.jwt.audience
      })
    } catch (error) {
      throw new Error('Invalid refresh token')
    }
  }

  /**
   * Extract token from Authorization header
   */
  extractTokenFromHeader(authHeader) {
    if (!authHeader || !authHeader.startsWith('Bearer ')) {
      return null
    }
    return authHeader.substring(7)
  }

  /**
   * Check if user has required role
   */
  hasRole(user, requiredRoles) {
    if (Array.isArray(requiredRoles)) {
      return requiredRoles.includes(user.role)
    }
    return user.role === requiredRoles
  }

  /**
   * Check if user has permission for action
   */
  hasPermission(user, action, resource = null) {
    const permissions = {
      organizer: ['*'], // All permissions
      emcee: ['read:contests', 'read:contestants', 'read:judges', 'read:scores'],
      judge: ['read:contests', 'read:contestants', 'create:scores', 'update:scores'],
      tally_master: ['read:contests', 'read:scores', 'read:results'],
      auditor: ['read:contests', 'read:scores', 'read:audit_logs'],
      board: ['read:contests', 'read:results', 'read:reports']
    }

    const userPermissions = permissions[user.role] || []
    
    // Check for wildcard permission
    if (userPermissions.includes('*')) {
      return true
    }

    // Check for specific permission
    return userPermissions.includes(action)
  }

  /**
   * Generate password reset token
   */
  async generatePasswordResetToken(userId) {
    const payload = {
      userId,
      type: 'password_reset'
    }

    return jwt.sign(payload, config.jwt.secret, {
      expiresIn: '1h', // 1 hour
      issuer: config.jwt.issuer,
      audience: config.jwt.audience
    })
  }

  /**
   * Verify password reset token
   */
  async verifyPasswordResetToken(token) {
    try {
      const payload = jwt.verify(token, config.jwt.secret, {
        issuer: config.jwt.issuer,
        audience: config.jwt.audience
      })

      if (payload.type !== 'password_reset') {
        throw new Error('Invalid token type')
      }

      return payload
    } catch (error) {
      throw new Error('Invalid or expired password reset token')
    }
  }

  /**
   * Generate email verification token
   */
  async generateEmailVerificationToken(userId) {
    const payload = {
      userId,
      type: 'email_verification'
    }

    return jwt.sign(payload, config.jwt.secret, {
      expiresIn: '24h', // 24 hours
      issuer: config.jwt.issuer,
      audience: config.jwt.audience
    })
  }

  /**
   * Verify email verification token
   */
  async verifyEmailVerificationToken(token) {
    try {
      const payload = jwt.verify(token, config.jwt.secret, {
        issuer: config.jwt.issuer,
        audience: config.jwt.audience
      })

      if (payload.type !== 'email_verification') {
        throw new Error('Invalid token type')
      }

      return payload
    } catch (error) {
      throw new Error('Invalid or expired email verification token')
    }
  }
}