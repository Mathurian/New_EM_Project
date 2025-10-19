import { BaseService } from './BaseService.js'

/**
 * Audit logging service
 */
export class AuditService extends BaseService {
  constructor() {
    super('audit_logs')
  }

  /**
   * Log an audit event
   */
  async log(auditData) {
    const {
      userId,
      action,
      entityType,
      entityId,
      oldValues = null,
      newValues = null,
      ipAddress = null,
      userAgent = null
    } = auditData

    try {
      return await this.db('audit_logs')
        .insert({
          user_id: userId,
          action,
          entity_type: entityType,
          entity_id: entityId,
          old_values: oldValues ? JSON.stringify(oldValues) : null,
          new_values: newValues ? JSON.stringify(newValues) : null,
          ip_address: ipAddress,
          user_agent: userAgent,
          created_at: new Date()
        })
    } catch (error) {
      // Don't throw errors for audit logging failures
      this.logger.error('Audit logging failed:', error)
    }
  }

  /**
   * Get audit logs with filtering
   */
  async getAuditLogs(options = {}) {
    const {
      page = 1,
      limit = 50,
      userId = null,
      action = null,
      entityType = null,
      entityId = null,
      startDate = null,
      endDate = null
    } = options

    let query = this.db('audit_logs')
      .select(
        'audit_logs.*',
        'users.first_name',
        'users.last_name',
        'users.email'
      )
      .leftJoin('users', 'audit_logs.user_id', 'users.id')
      .orderBy('audit_logs.created_at', 'desc')

    // Apply filters
    if (userId) {
      query = query.where('audit_logs.user_id', userId)
    }

    if (action) {
      query = query.where('audit_logs.action', action)
    }

    if (entityType) {
      query = query.where('audit_logs.entity_type', entityType)
    }

    if (entityId) {
      query = query.where('audit_logs.entity_id', entityId)
    }

    if (startDate) {
      query = query.where('audit_logs.created_at', '>=', startDate)
    }

    if (endDate) {
      query = query.where('audit_logs.created_at', '<=', endDate)
    }

    // Get total count
    const countQuery = query.clone()
    const [{ count }] = await countQuery.count('* as count')

    // Apply pagination
    const offset = (page - 1) * limit
    const logs = await query.limit(limit).offset(offset)

    return {
      data: logs,
      pagination: {
        page,
        limit,
        total: parseInt(count),
        pages: Math.ceil(count / limit)
      }
    }
  }

  /**
   * Get audit trail for a specific entity
   */
  async getEntityAuditTrail(entityType, entityId) {
    return await this.db('audit_logs')
      .select(
        'audit_logs.*',
        'users.first_name',
        'users.last_name',
        'users.email'
      )
      .leftJoin('users', 'audit_logs.user_id', 'users.id')
      .where('audit_logs.entity_type', entityType)
      .where('audit_logs.entity_id', entityId)
      .orderBy('audit_logs.created_at', 'desc')
  }

  /**
   * Get user activity summary
   */
  async getUserActivitySummary(userId, days = 30) {
    const startDate = new Date()
    startDate.setDate(startDate.getDate() - days)

    const activities = await this.db('audit_logs')
      .select('action')
      .count('* as count')
      .where('user_id', userId)
      .where('created_at', '>=', startDate)
      .groupBy('action')
      .orderBy('count', 'desc')

    return activities
  }

  /**
   * Clean up old audit logs
   */
  async cleanupOldLogs(daysToKeep = 365) {
    const cutoffDate = new Date()
    cutoffDate.setDate(cutoffDate.getDate() - daysToKeep)

    const deleted = await this.db('audit_logs')
      .where('created_at', '<', cutoffDate)
      .del()

    this.logger.info(`Cleaned up ${deleted} old audit logs`)
    return deleted
  }
}