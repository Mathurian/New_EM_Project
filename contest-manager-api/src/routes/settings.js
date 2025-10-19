import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { SettingsService } from '../services/SettingsService.js'

/**
 * System settings routes
 */
export const settingsRoutes = async (fastify) => {
  const settingsService = new SettingsService()

  // Get all settings
  fastify.get('/', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const { public_only = false } = request.query

      const settings = await settingsService.getAllSettings({
        publicOnly: public_only === 'true'
      })

      return settings
    } catch (error) {
      fastify.log.error('Get settings error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get setting by key
  fastify.get('/:key', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { key } = request.params

      const setting = await settingsService.getSetting(key)

      if (!setting) {
        return reply.status(404).send({
          error: 'Setting not found'
        })
      }

      // Check if setting is public or user has permission
      if (!setting.is_public && !['organizer', 'board'].includes(request.user.role)) {
        return reply.status(403).send({
          error: 'Access denied'
        })
      }

      return setting
    } catch (error) {
      fastify.log.error('Get setting error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Update setting
  fastify.put('/:key', {
    schema: {
      body: Joi.object({
        value: Joi.any().required(),
        description: Joi.string().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { key } = request.params
      const { value, description } = request.body

      const setting = await settingsService.updateSetting(key, {
        value,
        description
      }, request.user.id)

      if (!setting) {
        return reply.status(404).send({
          error: 'Setting not found'
        })
      }

      return setting
    } catch (error) {
      fastify.log.error('Update setting error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Create new setting
  fastify.post('/', {
    schema: {
      body: Joi.object({
        key: Joi.string().min(1).max(100).required(),
        value: Joi.any().required(),
        type: Joi.string().valid('string', 'number', 'boolean', 'json').default('string'),
        description: Joi.string().max(500).optional(),
        is_public: Joi.boolean().default(false)
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const settingData = request.body

      // Check if setting already exists
      const existingSetting = await settingsService.getSetting(settingData.key)
      if (existingSetting) {
        return reply.status(409).send({
          error: 'Setting with this key already exists'
        })
      }

      const setting = await settingsService.createSetting(settingData, request.user.id)

      return reply.status(201).send(setting)
    } catch (error) {
      fastify.log.error('Create setting error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Delete setting
  fastify.delete('/:key', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { key } = request.params

      const deleted = await settingsService.deleteSetting(key, request.user.id)

      if (!deleted) {
        return reply.status(404).send({
          error: 'Setting not found'
        })
      }

      return { message: 'Setting deleted successfully' }
    } catch (error) {
      fastify.log.error('Delete setting error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Bulk update settings
  fastify.put('/bulk', {
    schema: {
      body: Joi.object({
        settings: Joi.array().items(
          Joi.object({
            key: Joi.string().required(),
            value: Joi.any().required()
          })
        ).required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { settings } = request.body

      const results = await settingsService.bulkUpdateSettings(settings, request.user.id)

      return {
        updated: results.updated,
        failed: results.failed,
        message: `Updated ${results.updated} settings, ${results.failed} failed`
      }
    } catch (error) {
      fastify.log.error('Bulk update settings error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Reset settings to defaults
  fastify.post('/reset-defaults', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const resetCount = await settingsService.resetToDefaults(request.user.id)

      return {
        message: 'Settings reset to defaults',
        reset_count: resetCount
      }
    } catch (error) {
      fastify.log.error('Reset settings error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Export settings
  fastify.get('/export/backup', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const backup = await settingsService.exportSettings()

      reply.type('application/json')
      reply.header('Content-Disposition', 'attachment; filename="settings-backup.json"')
      
      return backup
    } catch (error) {
      fastify.log.error('Export settings error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Import settings
  fastify.post('/import/backup', {
    schema: {
      body: Joi.object({
        settings: Joi.array().items(
          Joi.object({
            key: Joi.string().required(),
            value: Joi.any().required(),
            type: Joi.string().valid('string', 'number', 'boolean', 'json').required(),
            description: Joi.string().optional(),
            is_public: Joi.boolean().default(false)
          })
        ).required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { settings } = request.body

      const results = await settingsService.importSettings(settings, request.user.id)

      return {
        imported: results.imported,
        updated: results.updated,
        failed: results.failed,
        message: `Imported ${results.imported} settings, updated ${results.updated}, ${results.failed} failed`
      }
    } catch (error) {
      fastify.log.error('Import settings error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })
}