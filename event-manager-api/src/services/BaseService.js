import { db } from '../database/connection.js'
import { logger } from '../utils/logger.js'

export class BaseService {
  constructor(tableName) {
    this.tableName = tableName
    this.db = db
  }

  /**
   * Get all records with pagination and filtering
   */
  async getAll(options = {}) {
    const {
      page = 1,
      limit = 20,
      search = '',
      filters = {},
      sortBy = 'created_at',
      sortOrder = 'desc'
    } = options

    let query = this.db(this.tableName)

    // Apply search
    if (search) {
      const searchableColumns = this.getSearchableColumns()
      query = query.where(function() {
        searchableColumns.forEach((column, index) => {
          if (index === 0) {
            this.where(column, 'ilike', `%${search}%`)
          } else {
            this.orWhere(column, 'ilike', `%${search}%`)
          }
        })
      })
    }

    // Apply filters
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        if (Array.isArray(value)) {
          query = query.whereIn(key, value)
        } else {
          query = query.where(key, value)
        }
      }
    })

    // Get total count
    const totalQuery = query.clone()
    const [{ count }] = await totalQuery.count('* as count')
    const total = parseInt(count)

    // Apply sorting and pagination
    const offset = (page - 1) * limit
    const records = await query
      .orderBy(sortBy, sortOrder)
      .offset(offset)
      .limit(limit)

    return {
      data: records,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    }
  }

  /**
   * Get record by ID
   */
  async getById(id) {
    const record = await this.db(this.tableName).where('id', id).first()
    return record || null
  }

  /**
   * Create new record
   */
  async create(data, userId = null) {
    const recordData = {
      ...data,
      created_at: new Date(),
      updated_at: new Date()
    }

    if (userId) {
      recordData.created_by = userId
    }

    const [record] = await this.db(this.tableName)
      .insert(recordData)
      .returning('*')

    // Log creation
    if (userId) {
      await this.logAction('created', record.id, data, null, userId)
    }

    return record
  }

  /**
   * Update record by ID
   */
  async updateById(id, data, userId = null) {
    // Get old values for audit log
    const oldRecord = await this.getById(id)
    if (!oldRecord) {
      return null
    }

    const updateData = {
      ...data,
      updated_at: new Date()
    }

    if (userId) {
      updateData.updated_by = userId
    }

    const [record] = await this.db(this.tableName)
      .where('id', id)
      .update(updateData)
      .returning('*')

    // Log update
    if (userId) {
      await this.logAction('updated', id, data, oldRecord, userId)
    }

    return record
  }

  /**
   * Delete record by ID (soft delete if supported)
   */
  async deleteById(id, userId = null) {
    const record = await this.getById(id)
    if (!record) {
      return null
    }

    // Check if table supports soft deletes
    const hasIsActive = await this.hasColumn('is_active')
    
    if (hasIsActive) {
      // Soft delete
      const [deletedRecord] = await this.db(this.tableName)
        .where('id', id)
        .update({ 
          is_active: false,
          updated_at: new Date(),
          ...(userId && { updated_by: userId })
        })
        .returning('*')

      // Log soft delete
      if (userId) {
        await this.logAction('deleted', id, { is_active: false }, record, userId)
      }

      return deletedRecord
    } else {
      // Hard delete
      await this.db(this.tableName).where('id', id).del()

      // Log hard delete
      if (userId) {
        await this.logAction('deleted', id, null, record, userId)
      }

      return { id, deleted: true }
    }
  }

  /**
   * Log action to audit logs
   */
  async logAction(action, resourceId, newValues, oldValues, userId) {
    try {
      await this.db('audit_logs').insert({
        user_id: userId,
        action,
        resource_type: this.tableName,
        resource_id: resourceId,
        old_values: oldValues ? JSON.stringify(oldValues) : null,
        new_values: newValues ? JSON.stringify(newValues) : null,
        created_at: new Date(),
        updated_at: new Date()
      })
    } catch (error) {
      logger.error('Failed to log action:', error)
    }
  }

  /**
   * Check if table has a specific column
   */
  async hasColumn(columnName) {
    try {
      const result = await this.db.raw(`
        SELECT column_name 
        FROM information_schema.columns 
        WHERE table_name = ? AND column_name = ?
      `, [this.tableName, columnName])
      return result.rows.length > 0
    } catch (error) {
      return false
    }
  }

  /**
   * Get searchable columns for this table
   */
  getSearchableColumns() {
    // Override in subclasses for specific searchable columns
    return ['name', 'description']
  }

  /**
   * Get records by foreign key
   */
  async getByForeignKey(foreignKey, foreignId, options = {}) {
    const query = this.db(this.tableName).where(foreignKey, foreignId)
    
    if (options.orderBy) {
      query.orderBy(options.orderBy, options.orderDirection || 'asc')
    }
    
    if (options.limit) {
      query.limit(options.limit)
    }
    
    return await query
  }

  /**
   * Count records with optional filters
   */
  async count(filters = {}) {
    let query = this.db(this.tableName)
    
    Object.entries(filters).forEach(([key, value]) => {
      if (value !== undefined && value !== null && value !== '') {
        if (Array.isArray(value)) {
          query = query.whereIn(key, value)
        } else {
          query = query.where(key, value)
        }
      }
    })
    
    const [{ count }] = await query.count('* as count')
    return parseInt(count)
  }
}