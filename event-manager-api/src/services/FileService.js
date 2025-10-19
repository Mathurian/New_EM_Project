import { BaseService } from './BaseService.js'
import sharp from 'sharp'
import { promises as fs } from 'fs'
import path from 'path'
import { fileURLToPath } from 'url'
import { dirname } from 'path'

const __filename = fileURLToPath(import.meta.url)
const __dirname = dirname(__filename)

export class FileService extends BaseService {
  constructor() {
    super('files')
  }

  /**
   * Create file record
   */
  async createFile(fileData, userId) {
    // Ensure uploads directory exists
    const uploadsDir = path.join(__dirname, '../../uploads')
    await fs.mkdir(uploadsDir, { recursive: true })

    // Generate thumbnail for images
    if (fileData.is_image) {
      try {
        const thumbnailPath = await this.generateThumbnail(fileData.file_path)
        fileData.thumbnail_path = thumbnailPath
      } catch (error) {
        console.warn('Failed to generate thumbnail:', error.message)
      }
    }

    return this.create(fileData, userId)
  }

  /**
   * Generate thumbnail for image
   */
  async generateThumbnail(imagePath) {
    try {
      const thumbnailDir = path.join(path.dirname(imagePath), 'thumbnails')
      await fs.mkdir(thumbnailDir, { recursive: true })

      const thumbnailPath = path.join(
        thumbnailDir,
        'thumb_' + path.basename(imagePath)
      )

      await sharp(imagePath)
        .resize(300, 300, { fit: 'inside', withoutEnlargement: true })
        .jpeg({ quality: 80 })
        .toFile(thumbnailPath)

      return thumbnailPath
    } catch (error) {
      console.error('Thumbnail generation failed:', error)
      return null
    }
  }

  /**
   * Get files by entity
   */
  async getFilesByEntity(entityType, entityId, options = {}) {
    const { category, is_image } = options

    let query = this.db(this.tableName)
      .where('entity_type', entityType)
      .where('entity_id', entityId)
      .orderBy('created_at', 'desc')

    if (category) {
      query = query.where('category', category)
    }

    if (is_image !== undefined) {
      query = query.where('is_image', is_image)
    }

    return query
  }

  /**
   * Get file statistics
   */
  async getFileStats() {
    const [
      totalFiles,
      imageFiles,
      documentFiles,
      filesByEntity,
      totalSize
    ] = await Promise.all([
      this.db(this.tableName).count('* as count').first(),
      this.db(this.tableName).where('is_image', true).count('* as count').first(),
      this.db(this.tableName).where('is_image', false).count('* as count').first(),
      this.db(this.tableName)
        .select('entity_type')
        .count('* as count')
        .groupBy('entity_type'),
      this.db(this.tableName)
        .sum('file_size as total_size')
        .first()
    ])

    return {
      total_files: parseInt(totalFiles.count),
      image_files: parseInt(imageFiles.count),
      document_files: parseInt(documentFiles.count),
      total_size: parseInt(totalSize.total_size) || 0,
      files_by_entity: filesByEntity.reduce((acc, row) => {
        acc[row.entity_type] = parseInt(row.count)
        return acc
      }, {})
    }
  }

  /**
   * Clean up orphaned files
   */
  async cleanupOrphanedFiles() {
    try {
      // Get all files
      const files = await this.db(this.tableName).select('*')
      const orphanedFiles = []

      for (const file of files) {
        // Check if the referenced entity still exists
        let entityExists = false

        switch (file.entity_type) {
          case 'event':
            entityExists = await this.db('events').where('id', file.entity_id).first()
            break
          case 'contest':
            entityExists = await this.db('contests').where('id', file.entity_id).first()
            break
          case 'category':
            entityExists = await this.db('categories').where('id', file.entity_id).first()
            break
          case 'contestant':
            entityExists = await this.db('contestants').where('id', file.entity_id).first()
            break
          case 'judge':
            entityExists = await this.db('users').where('id', file.entity_id).first()
            break
        }

        if (!entityExists) {
          orphanedFiles.push(file)
        }
      }

      // Delete orphaned file records and physical files
      for (const file of orphanedFiles) {
        try {
          if (await fs.access(file.file_path).then(() => true).catch(() => false)) {
            await fs.unlink(file.file_path)
          }
          if (file.thumbnail_path && await fs.access(file.thumbnail_path).then(() => true).catch(() => false)) {
            await fs.unlink(file.thumbnail_path)
          }
        } catch (error) {
          console.warn('Failed to delete physical file:', error.message)
        }

        await this.db(this.tableName).where('id', file.id).del()
      }

      return {
        orphaned_count: orphanedFiles.length,
        cleaned_files: orphanedFiles.map(f => f.id)
      }
    } catch (error) {
      console.error('Cleanup failed:', error)
      throw error
    }
  }

  /**
   * Get searchable columns for files
   */
  getSearchableColumns() {
    return ['original_name', 'description', 'category']
  }
}