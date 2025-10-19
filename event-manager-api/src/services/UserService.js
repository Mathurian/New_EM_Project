import { BaseService } from './BaseService.js'
import bcrypt from 'bcryptjs'
import { config } from '../config/index.js'

export class UserService extends BaseService {
  constructor() {
    super('users')
  }

  /**
   * Create user with hashed password
   */
  async createUser(userData, password, createdBy = null) {
    const hashedPassword = await bcrypt.hash(password, config.security.bcryptRounds)
    
    const user = await this.create({
      ...userData,
      password_hash: hashedPassword
    }, createdBy)

    // Remove password hash from response
    delete user.password_hash
    return user
  }

  /**
   * Authenticate user with email and password
   */
  async authenticateUser(email, password) {
    const user = await this.db(this.tableName)
      .where('email', email)
      .where('is_active', true)
      .first()

    if (!user) {
      return null
    }

    const isValidPassword = await bcrypt.compare(password, user.password_hash)
    if (!isValidPassword) {
      return null
    }

    // Update last login
    await this.db(this.tableName)
      .where('id', user.id)
      .update({ last_login: new Date() })

    // Remove password hash from response
    delete user.password_hash
    return user
  }

  /**
   * Get users by role
   */
  async getUsersByRole(role, options = {}) {
    return this.getAll({
      ...options,
      filters: { role, is_active: true }
    })
  }

  /**
   * Get judges
   */
  async getJudges(options = {}) {
    return this.getUsersByRole('judge', options)
  }

  /**
   * Get contestants
   */
  async getContestants(options = {}) {
    return this.getUsersByRole('contestant', options)
  }

  /**
   * Get organizers
   */
  async getOrganizers(options = {}) {
    return this.getUsersByRole('organizer', options)
  }

  /**
   * Update user password
   */
  async updatePassword(userId, newPassword, updatedBy = null) {
    const hashedPassword = await bcrypt.hash(newPassword, config.security.bcryptRounds)
    
    return this.updateById(userId, {
      password_hash: hashedPassword
    }, updatedBy)
  }

  /**
   * Update user profile
   */
  async updateProfile(userId, profileData, updatedBy = null) {
    // Remove sensitive fields that shouldn't be updated via profile
    const { password, password_hash, role, is_active, ...safeData } = profileData
    
    return this.updateById(userId, safeData, updatedBy)
  }

  /**
   * Deactivate user (soft delete)
   */
  async deactivateUser(userId, deactivatedBy) {
    return this.updateById(userId, {
      is_active: false
    }, deactivatedBy)
  }

  /**
   * Reactivate user
   */
  async reactivateUser(userId, reactivatedBy) {
    return this.updateById(userId, {
      is_active: true
    }, reactivatedBy)
  }

  /**
   * Get user statistics
   */
  async getUserStats() {
    const [
      totalUsers,
      activeUsers,
      usersByRole
    ] = await Promise.all([
      this.db(this.tableName).count('* as count').first(),
      this.db(this.tableName).where('is_active', true).count('* as count').first(),
      this.db(this.tableName)
        .select('role')
        .count('* as count')
        .where('is_active', true)
        .groupBy('role')
    ])

    return {
      total_users: parseInt(totalUsers.count),
      active_users: parseInt(activeUsers.count),
      users_by_role: usersByRole.reduce((acc, row) => {
        acc[row.role] = parseInt(row.count)
        return acc
      }, {})
    }
  }

  /**
   * Search users
   */
  async searchUsers(searchTerm, options = {}) {
    const {
      role = null,
      limit = 20
    } = options

    let query = this.db(this.tableName)
      .where('is_active', true)
      .where(function() {
        this.where('first_name', 'ilike', `%${searchTerm}%`)
          .orWhere('last_name', 'ilike', `%${searchTerm}%`)
          .orWhere('preferred_name', 'ilike', `%${searchTerm}%`)
          .orWhere('email', 'ilike', `%${searchTerm}%`)
      })

    if (role) {
      query = query.where('role', role)
    }

    return query.limit(limit).select('id', 'first_name', 'last_name', 'preferred_name', 'email', 'role')
  }

  /**
   * Get user permissions based on role
   */
  getUserPermissions(role) {
    const permissions = {
      organizer: [
        'events:create', 'events:read', 'events:update', 'events:delete', 'events:archive',
        'contests:create', 'contests:read', 'contests:update', 'contests:delete', 'contests:archive',
        'categories:create', 'categories:read', 'categories:update', 'categories:delete',
        'users:create', 'users:read', 'users:update', 'users:delete',
        'scoring:read', 'results:read', 'settings:read', 'settings:update'
      ],
      judge: [
        'events:read', 'contests:read', 'categories:read',
        'scoring:create', 'scoring:read', 'scoring:update',
        'results:read'
      ],
      contestant: [
        'events:read', 'contests:read', 'categories:read',
        'results:read'
      ],
      emcee: [
        'events:read', 'contests:read', 'categories:read',
        'results:read'
      ],
      tally_master: [
        'events:read', 'contests:read', 'categories:read',
        'scoring:read', 'results:read', 'results:update'
      ],
      auditor: [
        'events:read', 'contests:read', 'categories:read',
        'scoring:read', 'results:read', 'audit:read'
      ],
      board: [
        'events:read', 'contests:read', 'categories:read',
        'results:read', 'reports:read'
      ]
    }

    return permissions[role] || []
  }

  /**
   * Check if user has permission
   */
  hasPermission(user, permission) {
    const permissions = this.getUserPermissions(user.role)
    return permissions.includes(permission)
  }

  /**
   * Get searchable columns for users
   */
  getSearchableColumns() {
    return ['first_name', 'last_name', 'preferred_name', 'email']
  }
}