import { BaseService } from './BaseService.js'

export class SettingsService extends BaseService {
  constructor() {
    super('system_settings')
  }

  /**
   * Get all settings
   */
  async getAllSettings(publicOnly = false) {
    let query = this.db(this.tableName).orderBy('setting_key')

    if (publicOnly) {
      query = query.where('is_public', true)
    }

    const settings = await query
    return settings
  }

  /**
   * Get setting by key
   */
  async getSettingByKey(key) {
    const setting = await this.db(this.tableName)
      .where('setting_key', key)
      .first()

    if (!setting) {
      return null
    }

    // Convert value based on type
    return {
      ...setting,
      value: this.convertSettingValue(setting.setting_value, setting.setting_type)
    }
  }

  /**
   * Update setting
   */
  async updateSetting(key, value, description = null, userId) {
    const existingSetting = await this.getSettingByKey(key)
    
    if (!existingSetting) {
      return null
    }

    const updateData = {
      setting_value: this.serializeSettingValue(value),
      updated_by: userId,
      updated_at: new Date()
    }

    if (description !== null) {
      updateData.description = description
    }

    const [setting] = await this.db(this.tableName)
      .where('setting_key', key)
      .update(updateData)
      .returning('*')

    return {
      ...setting,
      value: this.convertSettingValue(setting.setting_value, setting.setting_type)
    }
  }

  /**
   * Create setting
   */
  async createSetting(settingData, userId) {
    const { key, value, description, setting_type, is_public } = settingData

    // Check if setting already exists
    const existing = await this.getSettingByKey(key)
    if (existing) {
      throw new Error('Setting already exists')
    }

    const setting = await this.create({
      setting_key: key,
      setting_value: this.serializeSettingValue(value),
      description: description || '',
      setting_type: setting_type || 'string',
      is_public: is_public || false,
      created_by: userId
    }, userId)

    return {
      ...setting,
      value: this.convertSettingValue(setting.setting_value, setting.setting_type)
    }
  }

  /**
   * Delete setting
   */
  async deleteSetting(key, userId) {
    const deleted = await this.db(this.tableName)
      .where('setting_key', key)
      .del()

    return deleted > 0
  }

  /**
   * Bulk update settings
   */
  async bulkUpdateSettings(settings, userId) {
    let updated = 0

    for (const setting of settings) {
      const result = await this.updateSetting(
        setting.key,
        setting.value,
        null,
        userId
      )
      if (result) {
        updated++
      }
    }

    return updated
  }

  /**
   * Export settings
   */
  async exportSettings() {
    const settings = await this.getAllSettings()
    return {
      exported_at: new Date().toISOString(),
      settings: settings.map(setting => ({
        key: setting.setting_key,
        value: this.convertSettingValue(setting.setting_value, setting.setting_type),
        description: setting.description,
        setting_type: setting.setting_type,
        is_public: setting.is_public
      }))
    }
  }

  /**
   * Import settings
   */
  async importSettings(settings, userId) {
    let imported = 0

    for (const setting of settings) {
      try {
        const existing = await this.getSettingByKey(setting.key)
        if (existing) {
          // Update existing
          await this.updateSetting(
            setting.key,
            setting.value,
            setting.description,
            userId
          )
        } else {
          // Create new
          await this.createSetting(setting, userId)
        }
        imported++
      } catch (error) {
        console.warn(`Failed to import setting ${setting.key}:`, error.message)
      }
    }

    return imported
  }

  /**
   * Get database statistics
   */
  async getDatabaseStats() {
    const [
      totalUsers,
      totalEvents,
      totalContests,
      totalCategories,
      totalSubcategories,
      totalContestants,
      totalScores,
      totalFiles
    ] = await Promise.all([
      this.db('users').count('* as count').first(),
      this.db('events').count('* as count').first(),
      this.db('contests').count('* as count').first(),
      this.db('categories').count('* as count').first(),
      this.db('subcategories').count('* as count').first(),
      this.db('contestants').count('* as count').first(),
      this.db('scores').count('* as count').first(),
      this.db('files').count('* as count').first()
    ])

    return {
      users: parseInt(totalUsers.count),
      events: parseInt(totalEvents.count),
      contests: parseInt(totalContests.count),
      categories: parseInt(totalCategories.count),
      subcategories: parseInt(totalSubcategories.count),
      contestants: parseInt(totalContestants.count),
      scores: parseInt(totalScores.count),
      files: parseInt(totalFiles.count),
      generated_at: new Date().toISOString()
    }
  }

  /**
   * Convert setting value based on type
   */
  convertSettingValue(value, type) {
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
   * Serialize setting value for storage
   */
  serializeSettingValue(value) {
    if (typeof value === 'object') {
      return JSON.stringify(value)
    }
    return String(value)
  }

  /**
   * Get searchable columns for settings
   */
  getSearchableColumns() {
    return ['setting_key', 'description']
  }
}