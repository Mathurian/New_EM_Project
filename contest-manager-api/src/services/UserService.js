import bcrypt from 'bcryptjs'
import { BaseService } from './BaseService.js'
import { config } from '../config/index.js'

/**
 * User management service with authentication
 */
export class UserService extends BaseService {
  constructor() {
    super('users')
  }

  /**
   * Create a new user with hashed password
   */
  async createUser(userData, createdBy = null) {
    const { password, ...otherData } = userData

    // Hash password
    const passwordHash = await bcrypt.hash(password, config.security.bcryptRounds)

    return await this.create({
      ...otherData,
      password_hash: passwordHash
    }, createdBy)
  }

  /**
   * Update user with optional password change
   */
  async updateUser(id, userData, updatedBy = null) {
    const { password, ...otherData } = userData

    const updateData = { ...otherData }

    // Hash new password if provided
    if (password) {
      updateData.password_hash = await bcrypt.hash(password, config.security.bcryptRounds)
    }

    return await this.update(id, updateData, updatedBy)
  }

  /**
   * Authenticate user with email and password
   */
  async authenticate(email, password) {
    const user = await this.db('users')
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
    await this.update(user.id, { last_login_at: new Date() })

    // Remove password hash from response
    const { password_hash, ...userWithoutPassword } = user
    return userWithoutPassword
  }

  /**
   * Get user by email
   */
  async findByEmail(email) {
    return await this.db('users')
      .where('email', email)
      .where('is_active', true)
      .first()
  }

  /**
   * Get users by role
   */
  async getUsersByRole(role, options = {}) {
    return await this.findMany({
      ...options,
      filters: { role, is_active: true }
    })
  }

  /**
   * Get judges assigned to a subcategory
   */
  async getSubcategoryJudges(subcategoryId) {
    return await this.db('users')
      .select(
        'users.*',
        'subcategory_judges.is_certified',
        'subcategory_judges.certified_at'
      )
      .leftJoin('subcategory_judges', 'users.id', 'subcategory_judges.judge_id')
      .where('subcategory_judges.subcategory_id', subcategoryId)
      .where('users.role', 'judge')
      .where('users.is_active', true)
  }

  /**
   * Assign judge to subcategory
   */
  async assignJudgeToSubcategory(judgeId, subcategoryId, assignedBy = null) {
    return await this.db('subcategory_judges')
      .insert({
        judge_id: judgeId,
        subcategory_id: subcategoryId,
        created_at: new Date(),
        updated_at: new Date()
      })
      .onConflict(['judge_id', 'subcategory_id'])
      .merge()
  }

  /**
   * Certify judge for subcategory
   */
  async certifyJudge(judgeId, subcategoryId, certifiedBy = null) {
    return await this.db('subcategory_judges')
      .where('judge_id', judgeId)
      .where('subcategory_id', subcategoryId)
      .update({
        is_certified: true,
        certified_at: new Date(),
        updated_at: new Date()
      })
  }

  /**
   * Get user statistics
   */
  async getUserStats() {
    const stats = await this.db('users')
      .select('role')
      .count('* as count')
      .where('is_active', true)
      .groupBy('role')

    return stats.reduce((acc, stat) => {
      acc[stat.role] = parseInt(stat.count)
      return acc
    }, {})
  }

  /**
   * Apply include relations for users
   */
  applyInclude(query, relation) {
    switch (relation) {
      case 'assignments':
        return query.leftJoin('subcategory_judges', 'users.id', 'subcategory_judges.judge_id')
      default:
        return query
    }
  }

  /**
   * Validate user data
   */
  validate(data, isUpdate = false) {
    const errors = []

    if (!isUpdate || data.email !== undefined) {
      if (!data.email || !this.isValidEmail(data.email)) {
        errors.push('Valid email is required')
      }
    }

    if (!isUpdate || data.first_name !== undefined) {
      if (!data.first_name || data.first_name.trim().length === 0) {
        errors.push('First name is required')
      }
    }

    if (!isUpdate || data.last_name !== undefined) {
      if (!data.last_name || data.last_name.trim().length === 0) {
        errors.push('Last name is required')
      }
    }

    if (!isUpdate || data.role !== undefined) {
      const validRoles = ['organizer', 'emcee', 'judge', 'tally_master', 'auditor', 'board']
      if (!data.role || !validRoles.includes(data.role)) {
        errors.push('Valid role is required')
      }
    }

    if (!isUpdate && data.password) {
      if (data.password.length < 8) {
        errors.push('Password must be at least 8 characters long')
      }
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }

  /**
   * Validate email format
   */
  isValidEmail(email) {
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/
    return emailRegex.test(email)
  }
}