import { BaseService } from './BaseService.js'
import { config } from '../config/index.js'
import sharp from 'sharp'
import { v4 as uuidv4 } from 'uuid'
import { createReadStream, createWriteStream, unlink, mkdir } from 'fs'
import { promisify } from 'util'
import { join, dirname, extname, basename } from 'path'
import { pipeline } from 'stream/promises'

const unlinkAsync = promisify(unlink)
const mkdirAsync = promisify(mkdir)

/**
 * File management service with support for images and documents
 */
export class FileService extends BaseService {
  constructor() {
    super('files')
    this.uploadDir = join(process.cwd(), 'uploads')
    this.ensureUploadDir()
  }

  /**
   * Ensure upload directory exists
   */
  async ensureUploadDir() {
    try {
      await mkdirAsync(this.uploadDir, { recursive: true })
      await mkdirAsync(join(this.uploadDir, 'images'), { recursive: true })
      await mkdirAsync(join(this.uploadDir, 'documents'), { recursive: true })
      await mkdirAsync(join(this.uploadDir, 'thumbnails'), { recursive: true })
    } catch (error) {
      this.logger.error('Error creating upload directories:', error)
    }
  }

  /**
   * Process image file (resize, optimize)
   */
  async processImage(file) {
    try {
      const processedBuffer = await sharp(file.buffer)
        .resize(1920, 1920, { 
          fit: 'inside',
          withoutEnlargement: true 
        })
        .jpeg({ quality: 85 })
        .toBuffer()

      return {
        ...file,
        buffer: processedBuffer,
        size: processedBuffer.length
      }
    } catch (error) {
      this.logger.error('Image processing error:', error)
      throw new Error('Failed to process image')
    }
  }

  /**
   * Generate thumbnail for image
   */
  async generateThumbnail(file, size = 'medium') {
    const sizes = {
      small: { width: 150, height: 150 },
      medium: { width: 300, height: 300 },
      large: { width: 600, height: 600 }
    }

    const { width, height } = sizes[size] || sizes.medium

    try {
      const thumbnailBuffer = await sharp(file.buffer)
        .resize(width, height, { 
          fit: 'cover',
          position: 'center'
        })
        .jpeg({ quality: 80 })
        .toBuffer()

      return thumbnailBuffer
    } catch (error) {
      this.logger.error('Thumbnail generation error:', error)
      throw new Error('Failed to generate thumbnail')
    }
  }

  /**
   * Upload file to storage
   */
  async uploadFile({ file, entityType, entityId, category, uploadedBy }) {
    const fileId = uuidv4()
    const fileExtension = extname(file.originalname)
    const fileName = `${fileId}${fileExtension}`
    
    // Determine storage path based on file type
    const isImage = file.mimetype.startsWith('image/')
    const storageDir = isImage ? 'images' : 'documents'
    const filePath = join(this.uploadDir, storageDir, fileName)

    try {
      // Write file to disk
      await pipeline(
        require('stream').Readable.from(file.buffer),
        createWriteStream(filePath)
      )

      // Generate thumbnail for images
      let thumbnailPath = null
      if (isImage) {
        const thumbnailBuffer = await this.generateThumbnail(file)
        thumbnailPath = join(this.uploadDir, 'thumbnails', fileName)
        
        await pipeline(
          require('stream').Readable.from(thumbnailBuffer),
          createWriteStream(thumbnailPath)
        )
      }

      // Save file metadata to database
      const fileRecord = await this.create({
        id: fileId,
        original_name: file.originalname,
        file_name: fileName,
        file_path: filePath,
        thumbnail_path: thumbnailPath,
        mime_type: file.mimetype,
        file_size: file.size,
        entity_type: entityType,
        entity_id: entityId,
        category,
        uploaded_by: uploadedBy,
        is_image: isImage,
        is_public: false
      }, uploadedBy)

      return fileRecord
    } catch (error) {
      // Clean up file if database save fails
      try {
        await unlinkAsync(filePath)
        if (thumbnailPath) {
          await unlinkAsync(thumbnailPath)
        }
      } catch (cleanupError) {
        this.logger.error('File cleanup error:', cleanupError)
      }

      this.logger.error('File upload error:', error)
      throw new Error('Failed to upload file')
    }
  }

  /**
   * Get file by ID
   */
  async getFileById(id) {
    return await this.findById(id)
  }

  /**
   * Get file stream for download
   */
  async getFileStream(id) {
    const file = await this.getFileById(id)
    if (!file) {
      return null
    }

    try {
      return createReadStream(file.file_path)
    } catch (error) {
      this.logger.error('File stream error:', error)
      return null
    }
  }

  /**
   * Get files by entity
   */
  async getFilesByEntity(entityType, entityId, options = {}) {
    const { category, page = 1, limit = 20 } = options

    const filters = {
      entity_type: entityType,
      entity_id: entityId
    }

    if (category) {
      filters.category = category
    }

    return await this.findMany({
      page,
      limit,
      filters,
      orderBy: 'created_at',
      orderDirection: 'desc'
    })
  }

  /**
   * Update file metadata
   */
  async updateFile(id, updateData, userId) {
    return await this.update(id, updateData, userId)
  }

  /**
   * Delete file
   */
  async deleteFile(id, userId) {
    const file = await this.getFileById(id)
    if (!file) {
      throw new Error('File not found')
    }

    try {
      // Delete physical files
      await unlinkAsync(file.file_path)
      if (file.thumbnail_path) {
        await unlinkAsync(file.thumbnail_path)
      }

      // Delete database record
      await this.delete(id, userId)

      return true
    } catch (error) {
      this.logger.error('File deletion error:', error)
      throw new Error('Failed to delete file')
    }
  }

  /**
   * Get thumbnail stream
   */
  async getThumbnail(id, size = 'medium') {
    const file = await this.getFileById(id)
    if (!file || !file.is_image) {
      return null
    }

    try {
      // Check if thumbnail exists
      const thumbnailPath = file.thumbnail_path
      if (thumbnailPath) {
        return createReadStream(thumbnailPath)
      }

      // Generate thumbnail on demand
      const originalFile = createReadStream(file.file_path)
      const chunks = []
      
      for await (const chunk of originalFile) {
        chunks.push(chunk)
      }
      
      const buffer = Buffer.concat(chunks)
      const thumbnailBuffer = await this.generateThumbnail({ buffer }, size)
      
      return require('stream').Readable.from(thumbnailBuffer)
    } catch (error) {
      this.logger.error('Thumbnail stream error:', error)
      return null
    }
  }

  /**
   * Get file statistics
   */
  async getFileStats() {
    const stats = await this.db('files')
      .select(
        this.db.raw('COUNT(*) as total_files'),
        this.db.raw('SUM(file_size) as total_size'),
        this.db.raw('COUNT(CASE WHEN is_image = true THEN 1 END) as image_count'),
        this.db.raw('COUNT(CASE WHEN is_image = false THEN 1 END) as document_count'),
        this.db.raw('AVG(file_size) as average_size')
      )
      .first()

    const categoryStats = await this.db('files')
      .select('category')
      .count('* as count')
      .groupBy('category')
      .orderBy('count', 'desc')

    const typeStats = await this.db('files')
      .select('mime_type')
      .count('* as count')
      .groupBy('mime_type')
      .orderBy('count', 'desc')

    return {
      ...stats,
      categories: categoryStats,
      types: typeStats
    }
  }

  /**
   * Clean up orphaned files
   */
  async cleanupOrphanedFiles() {
    try {
      // Find files that don't have corresponding entities
      const orphanedFiles = await this.db('files')
        .leftJoin('contests', function() {
          this.on('files.entity_id', '=', 'contests.id')
            .andOn('files.entity_type', '=', this.db.raw("'contest'"))
        })
        .leftJoin('contestants', function() {
          this.on('files.entity_id', '=', 'contestants.id')
            .andOn('files.entity_type', '=', this.db.raw("'contestant'"))
        })
        .leftJoin('users', function() {
          this.on('files.entity_id', '=', 'users.id')
            .andOn('files.entity_type', '=', this.db.raw("'judge'"))
        })
        .whereNull('contests.id')
        .whereNull('contestants.id')
        .whereNull('users.id')
        .select('files.*')

      let deletedCount = 0
      for (const file of orphanedFiles) {
        try {
          await this.deleteFile(file.id, null)
          deletedCount++
        } catch (error) {
          this.logger.error(`Error deleting orphaned file ${file.id}:`, error)
        }
      }

      this.logger.info(`Cleaned up ${deletedCount} orphaned files`)
      return deletedCount
    } catch (error) {
      this.logger.error('Orphaned files cleanup error:', error)
      throw error
    }
  }

  /**
   * Get file by entity and category
   */
  async getFileByEntityAndCategory(entityType, entityId, category) {
    return await this.db('files')
      .where('entity_type', entityType)
      .where('entity_id', entityId)
      .where('category', category)
      .first()
  }

  /**
   * Get all files for an entity
   */
  async getAllFilesByEntity(entityType, entityId) {
    return await this.db('files')
      .where('entity_type', entityType)
      .where('entity_id', entityId)
      .orderBy('created_at', 'desc')
  }

  /**
   * Validate file type
   */
  validateFileType(mimeType) {
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

    return allowedTypes.includes(mimeType)
  }

  /**
   * Get file size limit based on type
   */
  getFileSizeLimit(mimeType) {
    if (mimeType.startsWith('image/')) {
      return 5 * 1024 * 1024 // 5MB for images
    }
    return 10 * 1024 * 1024 // 10MB for documents
  }
}