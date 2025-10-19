import db from '../config/database.js'
import { logger } from '../utils/logger.js'
import { AuditService } from './AuditService.js'

/**
 * Base service class with common functionality
 */
export class BaseService {
  constructor(tableName) {
    this.tableName = tableName
    this.db = db
    this.logger = logger
    this.audit = new AuditService()
  }

  /**
   * Find records with pagination and filtering
   */
  async findMany(options = {}) {
    const {
      page = 1,
      limit = 20,
      filters = {},
      orderBy = 'created_at',
      orderDirection = 'desc',
      include = []
    } = options

    const offset = (page - 1) * limit
    let query = this.db(this.tableName)

    // Apply filters
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        if (Array.isArray(value)) {
          query = query.whereIn(key, value)
        } else if (typeof value === 'string' && value.includes('%')) {
          query = query.where(key, 'like', value)
        } else {
          query = query.where(key, value)
        }
      }
    })

    // Apply includes (joins)
    include.forEach(relation => {
      query = this.applyInclude(query, relation)
    })

    // Get total count
    const countQuery = query.clone()
    const [{ count }] = await countQuery.count('* as count')

    // Apply ordering and pagination
    const records = await query
      .orderBy(orderBy, orderDirection)
      .limit(limit)
      .offset(offset)

    return {
      data: records,
      pagination: {
        page,
        limit,
        total: parseInt(count),
        pages: Math.ceil(count / limit)
      }
    }
  }

  /**
   * Find a single record by ID
   */
  async findById(id, include = []) {
    let query = this.db(this.tableName).where('id', id)

    include.forEach(relation => {
      query = this.applyInclude(query, relation)
    })

    return await query.first()
  }

  /**
   * Create a new record
   */
  async create(data, userId = null) {
    try {
      const [record] = await this.db(this.tableName)
        .insert({
          ...data,
          created_at: new Date(),
          updated_at: new Date()
        })
        .returning('*')

      // Log audit trail
      if (userId) {
        await this.audit.log({
          userId,
          action: `${this.tableName}_created`,
          entityType: this.tableName,
          entityId: record.id,
          newValues: record
        })
      }

      return record
    } catch (error) {
      this.logger.error(`Error creating ${this.tableName}:`, error)
      throw error
    }
  }

  /**
   * Update a record by ID
   */
  async update(id, data, userId = null) {
    try {
      // Get old values for audit
      const oldRecord = userId ? await this.findById(id) : null

      const [record] = await this.db(this.tableName)
        .where('id', id)
        .update({
          ...data,
          updated_at: new Date()
        })
        .returning('*')

      if (!record) {
        throw new Error(`${this.tableName} not found`)
      }

      // Log audit trail
      if (userId && oldRecord) {
        await this.audit.log({
          userId,
          action: `${this.tableName}_updated`,
          entityType: this.tableName,
          entityId: id,
          oldValues: oldRecord,
          newValues: record
        })
      }

      return record
    } catch (error) {
      this.logger.error(`Error updating ${this.tableName}:`, error)
      throw error
    }
  }

  /**
   * Delete a record by ID
   */
  async delete(id, userId = null) {
    try {
      // Get old values for audit
      const oldRecord = userId ? await this.findById(id) : null

      const deleted = await this.db(this.tableName)
        .where('id', id)
        .del()

      if (!deleted) {
        throw new Error(`${this.tableName} not found`)
      }

      // Log audit trail
      if (userId && oldRecord) {
        await this.audit.log({
          userId,
          action: `${this.tableName}_deleted`,
          entityType: this.tableName,
          entityId: id,
          oldValues: oldRecord
        })
      }

      return true
    } catch (error) {
      this.logger.error(`Error deleting ${this.tableName}:`, error)
      throw error
    }
  }

  /**
   * Soft delete a record by ID
   */
  async softDelete(id, userId = null) {
    return await this.update(id, { is_active: false }, userId)
  }

  /**
   * Restore a soft-deleted record
   */
  async restore(id, userId = null) {
    return await this.update(id, { is_active: true }, userId)
  }

  /**
   * Apply include relations (joins)
   * Override in subclasses for specific relations
   */
  applyInclude(query, relation) {
    // Default implementation - override in subclasses
    return query
  }

  /**
   * Validate data before create/update
   * Override in subclasses for specific validation
   */
  validate(data, isUpdate = false) {
    // Default implementation - override in subclasses
    return { isValid: true, errors: [] }
  }

  /**
   * Get database transaction
   */
  async transaction(callback) {
    return await this.db.transaction(callback)
  }
}