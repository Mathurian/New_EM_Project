import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { SettingsService } from '../services/SettingsService.js'

export const settingsRoutes = async (fastify) => {
  const settingsService = new SettingsService()

  // Get all settings
  fastify.get('/', {
    schema: {
      querystring: Joi.object({
        public_only: Joi.boolean().default(false)
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { public_only } = request.query
      const settings = await settingsService.getAllSettings(public_only)
      return reply.send(settings)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch settings' })
    }
  })

  // Get setting by key
  fastify.get('/:key', {
    schema: {
      params: Joi.object({
        key: Joi.string().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const setting = await settingsService.getSettingByKey(request.params.key)
      if (!setting) {
        return reply.status(404).send({ error: 'Setting not found' })
      }
      return reply.send(setting)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch setting' })
    }
  })

  // Update setting
  fastify.put('/:key', {
    schema: {
      params: Joi.object({
        key: Joi.string().required()
      }),
      body: Joi.object({
        value: Joi.any().required(),
        description: Joi.string().max(500).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { key } = request.params
      const { value, description } = request.body

      const setting = await settingsService.updateSetting(key, value, description, request.user.id)
      if (!setting) {
        return reply.status(404).send({ error: 'Setting not found' })
      }
      return reply.send(setting)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update setting' })
    }
  })

  // Create setting
  fastify.post('/', {
    schema: {
      body: Joi.object({
        key: Joi.string().min(1).max(100).required(),
        value: Joi.any().required(),
        description: Joi.string().max(500).optional(),
        setting_type: Joi.string().valid('string', 'number', 'boolean', 'json').default('string'),
        is_public: Joi.boolean().default(false)
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const setting = await settingsService.createSetting(request.body, request.user.id)
      return reply.status(201).send(setting)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create setting' })
    }
  })

  // Delete setting
  fastify.delete('/:key', {
    schema: {
      params: Joi.object({
        key: Joi.string().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const deleted = await settingsService.deleteSetting(request.params.key, request.user.id)
      if (!deleted) {
        return reply.status(404).send({ error: 'Setting not found' })
      }
      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete setting' })
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
      const updated = await settingsService.bulkUpdateSettings(settings, request.user.id)
      return reply.send({ 
        message: `Updated ${updated} settings`,
        count: updated
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to bulk update settings' })
    }
  })

  // Export settings
  fastify.get('/export/json', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const settings = await settingsService.exportSettings()
      return reply.send(settings)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to export settings' })
    }
  })

  // Import settings
  fastify.post('/import/json', {
    schema: {
      body: Joi.object({
        settings: Joi.array().items(
          Joi.object({
            key: Joi.string().required(),
            value: Joi.any().required(),
            description: Joi.string().optional(),
            setting_type: Joi.string().valid('string', 'number', 'boolean', 'json').optional(),
            is_public: Joi.boolean().optional()
          })
        ).required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { settings } = request.body
      const imported = await settingsService.importSettings(settings, request.user.id)
      return reply.send({ 
        message: `Imported ${imported} settings`,
        count: imported
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to import settings' })
    }
  })

  // Test email configuration
  fastify.post('/test-email', {
    schema: {
      body: Joi.object({
        to: Joi.string().email().required(),
        subject: Joi.string().max(200).optional(),
        message: Joi.string().max(1000).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { to, subject = 'Test Email', message = 'This is a test email from Event Manager.' } = request.body
      
      // This would integrate with the email service
      // For now, return a placeholder response
      return reply.send({ 
        message: 'Email test not yet implemented',
        to,
        subject,
        message
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to test email' })
    }
  })

  // Get system information
  fastify.get('/system/info', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const info = {
        app_version: process.env.APP_VERSION || '1.0.0',
        node_version: process.version,
        platform: process.platform,
        arch: process.arch,
        uptime: process.uptime(),
        memory_usage: process.memoryUsage(),
        cpu_usage: process.cpuUsage(),
        timestamp: new Date().toISOString()
      }

      return reply.send(info)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch system info' })
    }
  })

  // Get database statistics
  fastify.get('/database/stats', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const stats = await settingsService.getDatabaseStats()
      return reply.send(stats)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch database statistics' })
    }
  })
}