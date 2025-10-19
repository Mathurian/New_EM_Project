import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { promises as fs } from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
import { dirname } from 'path'
import { exec } from 'child_process'
import { promisify } from 'util'

const execAsync = promisify(exec)
const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

export const backupRoutes = async (fastify) => {
  // Get backup settings
  fastify.get('/settings', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const settings = await fastify.db('system_settings')
        .whereIn('setting_key', [
          'backup_enabled',
          'backup_schedule',
          'backup_retention_days',
          'backup_location',
          'backup_include_files'
        ])
        .select('setting_key', 'setting_value', 'setting_type')

      const backupSettings = {}
      settings.forEach(setting => {
        let value = setting.setting_value
        if (setting.setting_type === 'boolean') {
          value = value === 'true'
        } else if (setting.setting_type === 'number') {
          value = parseFloat(value)
        } else if (setting.setting_type === 'json') {
          try {
            value = JSON.parse(value)
          } catch {
            // Keep as string if JSON parsing fails
          }
        }
        backupSettings[setting.setting_key] = value
      })

      return reply.send(backupSettings)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch backup settings' })
    }
  })

  // Update backup settings
  fastify.put('/settings', {
    schema: {
      body: Joi.object({
        backup_enabled: Joi.boolean().optional(),
        backup_schedule: Joi.string().optional(),
        backup_retention_days: Joi.number().integer().min(1).max(365).optional(),
        backup_location: Joi.string().optional(),
        backup_include_files: Joi.boolean().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const settings = request.body
      const updated = []

      for (const [key, value] of Object.entries(settings)) {
        const existing = await fastify.db('system_settings')
          .where('setting_key', key)
          .first()

        if (existing) {
          await fastify.db('system_settings')
            .where('setting_key', key)
            .update({
              setting_value: String(value),
              updated_by: request.user.id,
              updated_at: new Date()
            })
        } else {
          await fastify.db('system_settings').insert({
            setting_key: key,
            setting_value: String(value),
            setting_type: typeof value === 'boolean' ? 'boolean' : 
                         typeof value === 'number' ? 'number' : 'string',
            is_public: false,
            created_by: request.user.id
          })
        }
        updated.push(key)
      }

      return reply.send({ 
        message: `Updated ${updated.length} backup settings`,
        updated_settings: updated
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update backup settings' })
    }
  })

  // Create schema backup
  fastify.post('/schema', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-')
      const filename = `schema_backup_${timestamp}.sql`
      const backupPath = path.join(__dirname, '../../backups', filename)

      // Ensure backups directory exists
      await fs.mkdir(path.dirname(backupPath), { recursive: true })

      // Create schema backup using pg_dump
      const { DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD } = process.env
      const pgDumpCmd = `PGPASSWORD="${DB_PASSWORD}" pg_dump -h ${DB_HOST} -p ${DB_PORT} -U ${DB_USER} -d ${DB_NAME} --schema-only > "${backupPath}"`
      
      await execAsync(pgDumpCmd)

      // Record backup in database
      const backup = await fastify.db('backups').insert({
        filename,
        file_path: backupPath,
        backup_type: 'schema',
        file_size: (await fs.stat(backupPath)).size,
        created_by: request.user.id
      }).returning('*')

      return reply.status(201).send(backup[0])
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create schema backup' })
    }
  })

  // Create full backup
  fastify.post('/full', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const timestamp = new Date().toISOString().replace(/[:.]/g, '-')
      const filename = `full_backup_${timestamp}.sql`
      const backupPath = path.join(__dirname, '../../backups', filename)

      // Ensure backups directory exists
      await fs.mkdir(path.dirname(backupPath), { recursive: true })

      // Create full backup using pg_dump
      const { DB_HOST, DB_PORT, DB_NAME, DB_USER, DB_PASSWORD } = process.env
      const pgDumpCmd = `PGPASSWORD="${DB_PASSWORD}" pg_dump -h ${DB_HOST} -p ${DB_PORT} -U ${DB_USER} -d ${DB_NAME} > "${backupPath}"`
      
      await execAsync(pgDumpCmd)

      // Record backup in database
      const backup = await fastify.db('backups').insert({
        filename,
        file_path: backupPath,
        backup_type: 'full',
        file_size: (await fs.stat(backupPath)).size,
        created_by: request.user.id
      }).returning('*')

      return reply.status(201).send(backup[0])
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create full backup' })
    }
  })

  // List backups
  fastify.get('/', {
    schema: {
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(20),
        type: Joi.string().valid('schema', 'full').optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { page, limit, type } = request.query
      const offset = (page - 1) * limit

      let query = fastify.db('backups')
        .join('users', 'backups.created_by', 'users.id')
        .select(
          'backups.*',
          'users.first_name',
          'users.last_name'
        )
        .orderBy('backups.created_at', 'desc')

      if (type) {
        query = query.where('backup_type', type)
      }

      const backups = await query.limit(limit).offset(offset)
      const total = await fastify.db('backups').count('* as count').first()

      return reply.send({
        backups,
        pagination: {
          page,
          limit,
          total: parseInt(total.count),
          pages: Math.ceil(total.count / limit)
        }
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch backups' })
    }
  })

  // Download backup
  fastify.get('/:id/download', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const backup = await fastify.db('backups').where('id', request.params.id).first()
      if (!backup) {
        return reply.status(404).send({ error: 'Backup not found' })
      }

      // Check if file exists
      try {
        await fs.access(backup.file_path)
      } catch {
        return reply.status(404).send({ error: 'Backup file not found' })
      }

      return reply.download(backup.file_path, backup.filename)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to download backup' })
    }
  })

  // Delete backup
  fastify.delete('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const backup = await fastify.db('backups').where('id', request.params.id).first()
      if (!backup) {
        return reply.status(404).send({ error: 'Backup not found' })
      }

      // Delete physical file
      try {
        await fs.unlink(backup.file_path)
      } catch (error) {
        fastify.log.warn('Failed to delete backup file:', error.message)
      }

      // Delete database record
      await fastify.db('backups').where('id', request.params.id).del()

      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete backup' })
    }
  })

  // Run scheduled backups
  fastify.post('/run-scheduled', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      // This would typically be run by a cron job
      // For now, just return a placeholder response
      return reply.send({ 
        message: 'Scheduled backup execution not yet implemented',
        note: 'This would typically be handled by a cron job'
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to run scheduled backups' })
    }
  })

  // Get backup statistics
  fastify.get('/stats', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const [
        totalBackups,
        schemaBackups,
        fullBackups,
        totalSize
      ] = await Promise.all([
        fastify.db('backups').count('* as count').first(),
        fastify.db('backups').where('backup_type', 'schema').count('* as count').first(),
        fastify.db('backups').where('backup_type', 'full').count('* as count').first(),
        fastify.db('backups').sum('file_size as total_size').first()
      ])

      return reply.send({
        total_backups: parseInt(totalBackups.count),
        schema_backups: parseInt(schemaBackups.count),
        full_backups: parseInt(fullBackups.count),
        total_size: parseInt(totalSize.total_size) || 0,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch backup statistics' })
    }
  })
}