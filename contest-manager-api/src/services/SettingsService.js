import { BaseService } from './BaseService.js'

/**
 * System settings management service
 */
export class SettingsService extends BaseService {
  constructor() {
    super('system_settings')
  }

  /**
   * Get all settings
   */
  async getAllSettings(options = {}) {
    const { publicOnly = false } = options

    let query = this.db('system_settings')
      .orderBy('key', 'asc')

    if (publicOnly) {
      query = query.where('is_public', true)
    }

    const settings = await query

    // Parse values based on type
    return settings.map(setting => ({
      ...setting,
      value: this.parseValue(setting.value, setting.type)
    }))
  }

  /**
   * Get setting by key
   */
  async getSetting(key) {
    const setting = await this.db('system_settings')
      .where('key', key)
      .first()

    if (!setting) {
      return null
    }

    return {
      ...setting,
      value: this.parseValue(setting.value, setting.type)
    }
  }

  /**
   * Update setting
   */
  async updateSetting(key, updateData, userId) {
    const { value, description } = updateData

    // Get existing setting to determine type
    const existingSetting = await this.getSetting(key)
    if (!existingSetting) {
      return null
    }

    const stringValue = this.stringifyValue(value, existingSetting.type)

    const [updatedSetting] = await this.db('system_settings')
      .where('key', key)
      .update({
        value: stringValue,
        description: description || existingSetting.description,
        updated_at: new Date()
      })
      .returning('*')

    // Log audit trail
    await this.audit.log({
      userId,
      action: 'setting_updated',
      entityType: 'system_setting',
      entityId: key,
      oldValues: { value: existingSetting.value },
      newValues: { value: stringValue }
    })

    return {
      ...updatedSetting,
      value: this.parseValue(updatedSetting.value, updatedSetting.type)
    }
  }

  /**
   * Create new setting
   */
  async createSetting(settingData, userId) {
    const { key, value, type, description, is_public } = settingData

    const stringValue = this.stringifyValue(value, type)

    const [setting] = await this.db('system_settings')
      .insert({
        key,
        value: stringValue,
        type,
        description,
        is_public,
        created_at: new Date(),
        updated_at: new Date()
      })
      .returning('*')

    // Log audit trail
    await this.audit.log({
      userId,
      action: 'setting_created',
      entityType: 'system_setting',
      entityId: key,
      newValues: setting
    })

    return {
      ...setting,
      value: this.parseValue(setting.value, setting.type)
    }
  }

  /**
   * Delete setting
   */
  async deleteSetting(key, userId) {
    const existingSetting = await this.getSetting(key)
    if (!existingSetting) {
      return false
    }

    const deleted = await this.db('system_settings')
      .where('key', key)
      .del()

    if (deleted) {
      // Log audit trail
      await this.audit.log({
        userId,
        action: 'setting_deleted',
        entityType: 'system_setting',
        entityId: key,
        oldValues: existingSetting
      })
    }

    return deleted > 0
  }

  /**
   * Bulk update settings
   */
  async bulkUpdateSettings(settings, userId) {
    let updated = 0
    let failed = 0

    for (const setting of settings) {
      try {
        const result = await this.updateSetting(setting.key, { value: setting.value }, userId)
        if (result) {
          updated++
        } else {
          failed++
        }
      } catch (error) {
        this.logger.error(`Failed to update setting ${setting.key}:`, error)
        failed++
      }
    }

    return { updated, failed }
  }

  /**
   * Reset settings to defaults
   */
  async resetToDefaults(userId) {
    const defaultSettings = [
      {
        key: 'session_timeout',
        value: '1800',
        type: 'number',
        description: 'Session timeout in seconds',
        is_public: false
      },
      {
        key: 'max_file_size',
        value: '5242880',
        type: 'number',
        description: 'Maximum file upload size in bytes',
        is_public: true
      },
      {
        key: 'allowed_file_types',
        value: 'image/jpeg,image/png,image/gif,image/webp,application/pdf,application/msword,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        type: 'string',
        description: 'Comma-separated list of allowed file types',
        is_public: true
      },
      {
        key: 'app_name',
        value: 'Contest Manager',
        type: 'string',
        description: 'Application name',
        is_public: true
      },
      {
        key: 'enable_real_time_scoring',
        value: 'true',
        type: 'boolean',
        description: 'Enable real-time scoring updates',
        is_public: true
      },
      {
        key: 'enable_email_notifications',
        value: 'true',
        type: 'boolean',
        description: 'Enable email notifications',
        is_public: true
      },
      {
        key: 'backup_retention_days',
        value: '30',
        type: 'number',
        description: 'Number of days to retain backups',
        is_public: false
      }
    ]

    let resetCount = 0

    for (const setting of defaultSettings) {
      try {
        const existing = await this.getSetting(setting.key)
        if (existing) {
          await this.updateSetting(setting.key, { 
            value: setting.value,
            description: setting.description 
          }, userId)
        } else {
          await this.createSetting(setting, userId)
        }
        resetCount++
      } catch (error) {
        this.logger.error(`Failed to reset setting ${setting.key}:`, error)
      }
    }

    return resetCount
  }

  /**
   * Export settings
   */
  async exportSettings() {
    const settings = await this.getAllSettings()
    
    return {
      exported_at: new Date().toISOString(),
      version: '1.0',
      settings: settings.map(setting => ({
        key: setting.key,
        value: setting.value,
        type: setting.type,
        description: setting.description,
        is_public: setting.is_public
      }))
    }
  }

  /**
   * Import settings
   */
  async importSettings(settings, userId) {
    let imported = 0
    let updated = 0
    let failed = 0

    for (const setting of settings) {
      try {
        const existing = await this.getSetting(setting.key)
        if (existing) {
          await this.updateSetting(setting.key, {
            value: setting.value,
            description: setting.description
          }, userId)
          updated++
        } else {
          await this.createSetting(setting, userId)
          imported++
        }
      } catch (error) {
        this.logger.error(`Failed to import setting ${setting.key}:`, error)
        failed++
      }
    }

    return { imported, updated, failed }
  }

  /**
   * Get setting value with type conversion
   */
  async getSettingValue(key, defaultValue = null) {
    const setting = await this.getSetting(key)
    return setting ? setting.value : defaultValue
  }

  /**
   * Set setting value
   */
  async setSettingValue(key, value, userId) {
    const existing = await this.getSetting(key)
    if (existing) {
      return await this.updateSetting(key, { value }, userId)
    } else {
      // Determine type from value
      const type = this.getValueType(value)
      return await this.createSetting({
        key,
        value,
        type,
        is_public: false
      }, userId)
    }
  }

  /**
   * Parse value based on type
   */
  parseValue(value, type) {
    switch (type) {
      case 'number':
        return parseFloat(value)
      case 'boolean':
        return value === 'true' || value === true
      case 'json':
        try {
          return JSON.parse(value)
        } catch {
          return value
        }
      default:
        return value
    }
  }

  /**
   * Stringify value based on type
   */
  stringifyValue(value, type) {
    switch (type) {
      case 'json':
        return JSON.stringify(value)
      default:
        return String(value)
    }
  }

  /**
   * Get value type
   */
  getValueType(value) {
    if (typeof value === 'number') return 'number'
    if (typeof value === 'boolean') return 'boolean'
    if (typeof value === 'object') return 'json'
    return 'string'
  }

  /**
   * Get public settings for frontend
   */
  async getPublicSettings() {
    return await this.getAllSettings({ publicOnly: true })
  }
}