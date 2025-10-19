import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { UserService } from '../services/UserService.js'

/**
 * User management routes
 */
export const userRoutes = async (fastify) => {
  const userService = new UserService()

  // User creation schema
  const createUserSchema = {
    body: Joi.object({
      email: Joi.string().email().required(),
      password: Joi.string().min(8).required(),
      first_name: Joi.string().min(1).max(100).required(),
      last_name: Joi.string().min(1).max(100).required(),
      preferred_name: Joi.string().max(100).optional(),
      role: Joi.string().valid('organizer', 'emcee', 'judge', 'tally_master', 'auditor', 'board').required(),
      phone: Joi.string().max(20).optional(),
      bio: Joi.string().max(1000).optional(),
      image_url: Joi.string().uri().optional(),
      pronouns: Joi.string().max(50).optional(),
      is_head_judge: Joi.boolean().optional()
    })
  }

  // User update schema
  const updateUserSchema = {
    body: Joi.object({
      email: Joi.string().email().optional(),
      first_name: Joi.string().min(1).max(100).optional(),
      last_name: Joi.string().min(1).max(100).optional(),
      preferred_name: Joi.string().max(100).optional(),
      role: Joi.string().valid('organizer', 'emcee', 'judge', 'tally_master', 'auditor', 'board').optional(),
      phone: Joi.string().max(20).optional(),
      bio: Joi.string().max(1000).optional(),
      image_url: Joi.string().uri().optional(),
      pronouns: Joi.string().max(50).optional(),
      is_head_judge: Joi.boolean().optional(),
      is_active: Joi.boolean().optional()
    })
  }

  // Password change schema
  const changePasswordSchema = {
    body: Joi.object({
      current_password: Joi.string().required(),
      new_password: Joi.string().min(8).required()
    })
  }

  // Get all users
  fastify.get('/', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const {
        page = 1,
        limit = 20,
        role = null,
        search = null,
        is_active = null,
        include = []
      } = request.query

      const filters = {}
      if (role) filters.role = role
      if (is_active !== null) filters.is_active = is_active === 'true'
      if (search) {
        // Search in name or email
        filters.search = `%${search}%`
      }

      const result = await userService.findMany({
        page: parseInt(page),
        limit: parseInt(limit),
        filters,
        include: include.split(',').filter(Boolean),
        orderBy: 'created_at',
        orderDirection: 'desc'
      })

      // Remove password hashes from response
      result.data = result.data.map(user => {
        const { password_hash, ...userWithoutPassword } = user
        return userWithoutPassword
      })

      return result
    } catch (error) {
      fastify.log.error('Get users error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get user by ID
  fastify.get('/:id', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { include = [] } = request.query

      // Users can only view their own profile unless they're organizers/board
      if (request.user.id !== id && !['organizer', 'board'].includes(request.user.role)) {
        return reply.status(403).send({
          error: 'Access denied'
        })
      }

      const user = await userService.findById(id, include.split(',').filter(Boolean))

      if (!user) {
        return reply.status(404).send({
          error: 'User not found'
        })
      }

      // Remove password hash from response
      const { password_hash, ...userWithoutPassword } = user
      return userWithoutPassword
    } catch (error) {
      fastify.log.error('Get user error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Create new user
  fastify.post('/', {
    schema: createUserSchema,
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const userData = request.body

      // Check if user already exists
      const existingUser = await userService.findByEmail(userData.email)
      if (existingUser) {
        return reply.status(409).send({
          error: 'User with this email already exists'
        })
      }

      // Validate user data
      const validation = userService.validate(userData)
      if (!validation.isValid) {
        return reply.status(400).send({
          error: 'Validation failed',
          details: validation.errors
        })
      }

      const user = await userService.createUser(userData, request.user.id)

      // Remove password hash from response
      const { password_hash, ...userWithoutPassword } = user
      return reply.status(201).send(userWithoutPassword)
    } catch (error) {
      fastify.log.error('Create user error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Update user
  fastify.put('/:id', {
    schema: updateUserSchema,
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const userData = request.body

      // Users can only update their own profile unless they're organizers
      if (request.user.id !== id && request.user.role !== 'organizer') {
        return reply.status(403).send({
          error: 'Access denied'
        })
      }

      // Non-organizers cannot change certain fields
      if (request.user.role !== 'organizer') {
        delete userData.role
        delete userData.is_active
      }

      // Check if email is being changed and if it's already taken
      if (userData.email) {
        const existingUser = await userService.findByEmail(userData.email)
        if (existingUser && existingUser.id !== id) {
          return reply.status(409).send({
            error: 'Email already taken by another user'
          })
        }
      }

      // Validate user data
      const validation = userService.validate(userData, true)
      if (!validation.isValid) {
        return reply.status(400).send({
          error: 'Validation failed',
          details: validation.errors
        })
      }

      const user = await userService.updateUser(id, userData, request.user.id)

      if (!user) {
        return reply.status(404).send({
          error: 'User not found'
        })
      }

      // Remove password hash from response
      const { password_hash, ...userWithoutPassword } = user
      return userWithoutPassword
    } catch (error) {
      fastify.log.error('Update user error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Change password
  fastify.post('/:id/change-password', {
    schema: changePasswordSchema,
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { current_password, new_password } = request.body

      // Users can only change their own password
      if (request.user.id !== id) {
        return reply.status(403).send({
          error: 'Access denied'
        })
      }

      // Verify current password
      const user = await userService.findById(id)
      if (!user) {
        return reply.status(404).send({
          error: 'User not found'
        })
      }

      const isValidPassword = await userService.authenticate(user.email, current_password)
      if (!isValidPassword) {
        return reply.status(400).send({
          error: 'Current password is incorrect'
        })
      }

      // Update password
      await userService.updateUser(id, { password: new_password }, request.user.id)

      return { message: 'Password changed successfully' }
    } catch (error) {
      fastify.log.error('Change password error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Delete user (soft delete)
  fastify.delete('/:id', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      // Prevent self-deletion
      if (request.user.id === id) {
        return reply.status(400).send({
          error: 'You cannot delete your own account'
        })
      }

      const deleted = await userService.softDelete(id, request.user.id)

      if (!deleted) {
        return reply.status(404).send({
          error: 'User not found'
        })
      }

      return { message: 'User deleted successfully' }
    } catch (error) {
      fastify.log.error('Delete user error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Restore user
  fastify.post('/:id/restore', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      const restored = await userService.restore(id, request.user.id)

      if (!restored) {
        return reply.status(404).send({
          error: 'User not found'
        })
      }

      return { message: 'User restored successfully' }
    } catch (error) {
      fastify.log.error('Restore user error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get user statistics
  fastify.get('/stats/overview', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const stats = await userService.getUserStats()
      return stats
    } catch (error) {
      fastify.log.error('Get user stats error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Assign judge to subcategory
  fastify.post('/:id/assign-subcategory', {
    schema: {
      body: Joi.object({
        subcategory_id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { subcategory_id } = request.body

      await userService.assignJudgeToSubcategory(id, subcategory_id, request.user.id)

      return { message: 'Judge assigned to subcategory successfully' }
    } catch (error) {
      fastify.log.error('Assign judge error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Certify judge for subcategory
  fastify.post('/:id/certify', {
    schema: {
      body: Joi.object({
        subcategory_id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { subcategory_id } = request.body

      await userService.certifyJudge(id, subcategory_id, request.user.id)

      return { message: 'Judge certified for subcategory successfully' }
    } catch (error) {
      fastify.log.error('Certify judge error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })
}