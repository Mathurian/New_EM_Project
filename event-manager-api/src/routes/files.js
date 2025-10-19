import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { FileService } from '../services/FileService.js'
import multer from 'multer'
import path from 'path'
import { fileURLToPath } from 'url'
import { dirname } from 'path'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

export const fileRoutes = async (fastify) => {
  const fileService = new FileService()

  // Configure multer for file uploads
  const storage = multer.diskStorage({
    destination: (req, file, cb) => {
      cb(null, path.join(__dirname, '../../uploads'))
    },
    filename: (req, file, cb) => {
      const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9)
      cb(null, file.fieldname + '-' + uniqueSuffix + path.extname(file.originalname))
    }
  })

  const upload = multer({
    storage,
    limits: {
      fileSize: 5 * 1024 * 1024 // 5MB limit
    },
    fileFilter: (req, file, cb) => {
      const allowedTypes = [
        'image/jpeg',
        'image/png',
        'image/gif',
        'image/webp',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document'
      ]
      
      if (allowedTypes.includes(file.mimetype)) {
        cb(null, true)
      } else {
        cb(new Error('Invalid file type'), false)
      }
    }
  })

  // Upload file
  fastify.post('/upload', {
    preHandler: [fastify.authenticate, upload.single('file')]
  }, async (request, reply) => {
    try {
      if (!request.file) {
        return reply.status(400).send({ error: 'No file uploaded' })
      }

      const { entity_type, entity_id, category = 'general', description } = request.body

      if (!entity_type || !entity_id) {
        return reply.status(400).send({ error: 'Entity type and ID are required' })
      }

      const fileData = {
        original_name: request.file.originalname,
        file_name: request.file.filename,
        file_path: request.file.path,
        mime_type: request.file.mimetype,
        file_size: request.file.size,
        entity_type,
        entity_id,
        category,
        description: description || '',
        uploaded_by: request.user.id,
        is_image: request.file.mimetype.startsWith('image/')
      }

      const file = await fileService.createFile(fileData, request.user.id)

      return reply.status(201).send(file)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to upload file' })
    }
  })

  // Get files for an entity
  fastify.get('/entity/:entityType/:entityId', {
    schema: {
      params: Joi.object({
        entityType: Joi.string().valid('event', 'contest', 'category', 'contestant', 'judge', 'document').required(),
        entityId: Joi.string().uuid().required()
      }),
      querystring: Joi.object({
        category: Joi.string().optional(),
        is_image: Joi.boolean().optional()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { entityType, entityId } = request.params
      const { category, is_image } = request.query

      const files = await fileService.getFilesByEntity(entityType, entityId, {
        category,
        is_image
      })

      return reply.send(files)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch files' })
    }
  })

  // Get file by ID
  fastify.get('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const file = await fileService.getById(request.params.id)
      if (!file) {
        return reply.status(404).send({ error: 'File not found' })
      }
      return reply.send(file)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch file' })
    }
  })

  // Download file
  fastify.get('/:id/download', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const file = await fileService.getById(request.params.id)
      if (!file) {
        return reply.status(404).send({ error: 'File not found' })
      }

      // Check if file exists on disk
      const fs = await import('fs')
      if (!fs.existsSync(file.file_path)) {
        return reply.status(404).send({ error: 'File not found on disk' })
      }

      return reply.download(file.file_path, file.original_name)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to download file' })
    }
  })

  // Get file thumbnail
  fastify.get('/:id/thumbnail', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const file = await fileService.getById(request.params.id)
      if (!file) {
        return reply.status(404).send({ error: 'File not found' })
      }

      if (!file.is_image) {
        return reply.status(400).send({ error: 'File is not an image' })
      }

      // Check if thumbnail exists
      const fs = await import('fs')
      if (file.thumbnail_path && fs.existsSync(file.thumbnail_path)) {
        return reply.download(file.thumbnail_path, `thumb_${file.original_name}`)
      }

      // Generate thumbnail if it doesn't exist
      const thumbnailPath = await fileService.generateThumbnail(file.file_path)
      if (thumbnailPath) {
        // Update file record with thumbnail path
        await fileService.updateById(file.id, { thumbnail_path: thumbnailPath }, request.user.id)
        return reply.download(thumbnailPath, `thumb_${file.original_name}`)
      }

      return reply.status(404).send({ error: 'Thumbnail not available' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to get thumbnail' })
    }
  })

  // Update file metadata
  fastify.put('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      }),
      body: Joi.object({
        description: Joi.string().max(500).optional(),
        category: Joi.string().max(50).optional(),
        is_public: Joi.boolean().optional()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const file = await fileService.updateById(
        request.params.id,
        request.body,
        request.user.id
      )
      if (!file) {
        return reply.status(404).send({ error: 'File not found' })
      }
      return reply.send(file)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update file' })
    }
  })

  // Delete file
  fastify.delete('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const file = await fileService.getById(request.params.id)
      if (!file) {
        return reply.status(404).send({ error: 'File not found' })
      }

      // Check if user can delete this file
      if (file.uploaded_by !== request.user.id && !['organizer', 'admin'].includes(request.user.role)) {
        return reply.status(403).send({ error: 'Insufficient permissions' })
      }

      const deleted = await fileService.deleteById(request.params.id, request.user.id)
      if (!deleted) {
        return reply.status(404).send({ error: 'File not found' })
      }

      // Delete physical file
      const fs = await import('fs')
      if (fs.existsSync(file.file_path)) {
        fs.unlinkSync(file.file_path)
      }
      if (file.thumbnail_path && fs.existsSync(file.thumbnail_path)) {
        fs.unlinkSync(file.thumbnail_path)
      }

      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete file' })
    }
  })

  // Get file statistics
  fastify.get('/stats/overview', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const stats = await fileService.getFileStats()
      return reply.send(stats)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch file statistics' })
    }
  })
}