import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { FileService } from '../services/FileService.js'
import multer from 'multer'
import sharp from 'sharp'

/**
 * File upload and management routes
 */
export const fileRoutes = async (fastify) => {
  const fileService = new FileService()

  // Configure multer for file uploads
  const upload = multer({
    storage: multer.memoryStorage(),
    limits: {
      fileSize: 10 * 1024 * 1024 // 10MB limit
    },
    fileFilter: (req, file, cb) => {
      const allowedTypes = [
        // Images
        'image/jpeg',
        'image/png', 
        'image/gif',
        'image/webp',
        // Documents
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'application/vnd.ms-excel',
        'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        'application/vnd.ms-powerpoint',
        'application/vnd.openxmlformats-officedocument.presentationml.presentation',
        'text/plain',
        'text/csv'
      ]

      if (allowedTypes.includes(file.mimetype)) {
        cb(null, true)
      } else {
        cb(new Error('Invalid file type. Allowed types: images (JPEG, PNG, GIF, WebP) and documents (PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, TXT, CSV)'), false)
      }
    }
  })

  // Upload file
  fastify.post('/upload', {
    preHandler: [
      fastify.authenticate,
      upload.single('file')
    ]
  }, async (request, reply) => {
    try {
      if (!request.file) {
        return reply.status(400).send({
          error: 'No file provided'
        })
      }

      const { 
        entity_type, // 'contestant', 'judge', 'contest', 'document'
        entity_id,
        category = 'general' // 'profile_image', 'document', 'contest_image', etc.
      } = request.body

      if (!entity_type || !entity_id) {
        return reply.status(400).send({
          error: 'entity_type and entity_id are required'
        })
      }

      // Process file based on type
      let processedFile = request.file
      
      // Process images (resize, optimize)
      if (request.file.mimetype.startsWith('image/')) {
        processedFile = await fileService.processImage(request.file)
      }

      // Upload file to storage
      const fileInfo = await fileService.uploadFile({
        file: processedFile,
        entityType: entity_type,
        entityId: entity_id,
        category,
        uploadedBy: request.user.id
      })

      return reply.status(201).send(fileInfo)
    } catch (error) {
      fastify.log.error('File upload error:', error)
      return reply.status(500).send({
        error: error.message || 'File upload failed'
      })
    }
  })

  // Upload multiple files
  fastify.post('/upload-multiple', {
    preHandler: [
      fastify.authenticate,
      upload.array('files', 10) // Max 10 files
    ]
  }, async (request, reply) => {
    try {
      if (!request.files || request.files.length === 0) {
        return reply.status(400).send({
          error: 'No files provided'
        })
      }

      const { 
        entity_type,
        entity_id,
        category = 'general'
      } = request.body

      if (!entity_type || !entity_id) {
        return reply.status(400).send({
          error: 'entity_type and entity_id are required'
        })
      }

      const uploadPromises = request.files.map(async (file) => {
        let processedFile = file
        
        // Process images
        if (file.mimetype.startsWith('image/')) {
          processedFile = await fileService.processImage(file)
        }

        return fileService.uploadFile({
          file: processedFile,
          entityType: entity_type,
          entityId: entity_id,
          category,
          uploadedBy: request.user.id
        })
      })

      const fileInfos = await Promise.all(uploadPromises)

      return reply.status(201).send({
        files: fileInfos,
        count: fileInfos.length
      })
    } catch (error) {
      fastify.log.error('Multiple file upload error:', error)
      return reply.status(500).send({
        error: error.message || 'File upload failed'
      })
    }
  })

  // Get file by ID
  fastify.get('/:id', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      const file = await fileService.getFileById(id)

      if (!file) {
        return reply.status(404).send({
          error: 'File not found'
        })
      }

      return file
    } catch (error) {
      fastify.log.error('Get file error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Download file
  fastify.get('/:id/download', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      const fileStream = await fileService.getFileStream(id)

      if (!fileStream) {
        return reply.status(404).send({
          error: 'File not found'
        })
      }

      const file = await fileService.getFileById(id)
      
      reply.type(file.mime_type)
      reply.header('Content-Disposition', `attachment; filename="${file.original_name}"`)
      
      return fileStream
    } catch (error) {
      fastify.log.error('Download file error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get files by entity
  fastify.get('/entity/:entityType/:entityId', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { entityType, entityId } = request.params
      const { 
        category = null,
        page = 1,
        limit = 20
      } = request.query

      const files = await fileService.getFilesByEntity(entityType, entityId, {
        category,
        page: parseInt(page),
        limit: parseInt(limit)
      })

      return files
    } catch (error) {
      fastify.log.error('Get entity files error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Update file metadata
  fastify.put('/:id', {
    schema: {
      body: Joi.object({
        name: Joi.string().max(255).optional(),
        description: Joi.string().max(1000).optional(),
        category: Joi.string().max(100).optional(),
        is_public: Joi.boolean().optional()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const updateData = request.body

      // Check if user owns the file or is organizer
      const file = await fileService.getFileById(id)
      if (!file) {
        return reply.status(404).send({
          error: 'File not found'
        })
      }

      if (file.uploaded_by !== request.user.id && request.user.role !== 'organizer') {
        return reply.status(403).send({
          error: 'Access denied'
        })
      }

      const updatedFile = await fileService.updateFile(id, updateData, request.user.id)

      return updatedFile
    } catch (error) {
      fastify.log.error('Update file error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Delete file
  fastify.delete('/:id', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      // Check if user owns the file or is organizer
      const file = await fileService.getFileById(id)
      if (!file) {
        return reply.status(404).send({
          error: 'File not found'
        })
      }

      if (file.uploaded_by !== request.user.id && request.user.role !== 'organizer') {
        return reply.status(403).send({
          error: 'Access denied'
        })
      }

      await fileService.deleteFile(id, request.user.id)

      return { message: 'File deleted successfully' }
    } catch (error) {
      fastify.log.error('Delete file error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get file statistics
  fastify.get('/stats/overview', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const stats = await fileService.getFileStats()
      return stats
    } catch (error) {
      fastify.log.error('Get file stats error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Generate file thumbnail (for images)
  fastify.get('/:id/thumbnail', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { size = 'medium' } = request.query // small, medium, large

      const thumbnailStream = await fileService.getThumbnail(id, size)

      if (!thumbnailStream) {
        return reply.status(404).send({
          error: 'Thumbnail not found'
        })
      }

      const file = await fileService.getFileById(id)
      reply.type('image/jpeg')
      
      return thumbnailStream
    } catch (error) {
      fastify.log.error('Get thumbnail error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })
}