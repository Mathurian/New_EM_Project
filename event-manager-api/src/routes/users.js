import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { UserService } from '../services/UserService.js'

export const userRoutes = async (fastify) => {
  const userService = new UserService()

  // Get all users
  fastify.get('/', {
    schema: {
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(20),
        search: Joi.string().max(100).default(''),
        role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').optional(),
        sortBy: Joi.string().valid('first_name', 'last_name', 'email', 'created_at').default('created_at'),
        sortOrder: Joi.string().valid('asc', 'desc').default('desc')
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const users = await userService.getAll(request.query)
      return reply.send(users)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch users' })
    }
  })

  // Get user by ID
  fastify.get('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const user = await userService.getById(request.params.id)
      if (!user) {
        return reply.status(404).send({ error: 'User not found' })
      }
      // Remove password hash from response
      delete user.password_hash
      return reply.send(user)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch user' })
    }
  })

  // Create user
  fastify.post('/', {
    schema: {
      body: Joi.object({
        email: Joi.string().email().required(),
        password: Joi.string().min(6).required(),
        first_name: Joi.string().min(1).max(100).required(),
        last_name: Joi.string().min(1).max(100).required(),
        preferred_name: Joi.string().max(100).optional(),
        role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').required(),
        phone: Joi.string().max(20).optional(),
        bio: Joi.string().max(1000).optional(),
        pronouns: Joi.string().max(50).optional(),
        gender: Joi.string().valid('male', 'female', 'non-binary', 'prefer-not-to-say', 'other').optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { password, ...userData } = request.body

      // Check if user already exists
      const existingUser = await fastify.db('users').where('email', userData.email).first()
      if (existingUser) {
        return reply.status(409).send({ error: 'User already exists' })
      }

      const user = await userService.createUser(userData, password, request.user.id)
      return reply.status(201).send(user)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create user' })
    }
  })

  // Update user
  fastify.put('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      }),
      body: Joi.object({
        first_name: Joi.string().min(1).max(100).optional(),
        last_name: Joi.string().min(1).max(100).optional(),
        preferred_name: Joi.string().max(100).optional(),
        role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').optional(),
        phone: Joi.string().max(20).optional(),
        bio: Joi.string().max(1000).optional(),
        pronouns: Joi.string().max(50).optional(),
        gender: Joi.string().valid('male', 'female', 'non-binary', 'prefer-not-to-say', 'other').optional(),
        is_active: Joi.boolean().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const user = await userService.updateById(
        request.params.id,
        request.body,
        request.user.id
      )
      if (!user) {
        return reply.status(404).send({ error: 'User not found' })
      }
      // Remove password hash from response
      delete user.password_hash
      return reply.send(user)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update user' })
    }
  })

  // Delete user
  fastify.delete('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const deleted = await userService.deleteById(request.params.id, request.user.id)
      if (!deleted) {
        return reply.status(404).send({ error: 'User not found' })
      }
      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete user' })
    }
  })

  // Get users by role
  fastify.get('/role/:role', {
    schema: {
      params: Joi.object({
        role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').required()
      }),
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(20),
        search: Joi.string().max(100).default('')
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const users = await userService.getUsersByRole(request.params.role, request.query)
      return reply.send(users)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch users by role' })
    }
  })

  // Get judges
  fastify.get('/judges', {
    schema: {
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(20),
        search: Joi.string().max(100).default('')
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const judges = await userService.getJudges(request.query)
      return reply.send(judges)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch judges' })
    }
  })

  // Get contestants
  fastify.get('/contestants', {
    schema: {
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(20),
        search: Joi.string().max(100).default('')
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const contestants = await userService.getContestants(request.query)
      return reply.send(contestants)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contestants' })
    }
  })

  // Search users
  fastify.get('/search', {
    schema: {
      querystring: Joi.object({
        q: Joi.string().min(1).required(),
        role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').optional(),
        limit: Joi.number().integer().min(1).max(50).default(20)
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const users = await userService.searchUsers(request.query.q, {
        role: request.query.role,
        limit: request.query.limit
      })
      return reply.send(users)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to search users' })
    }
  })

  // Deactivate user
  fastify.post('/:id/deactivate', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const user = await userService.deactivateUser(request.params.id, request.user.id)
      if (!user) {
        return reply.status(404).send({ error: 'User not found' })
      }
      return reply.send({ message: 'User deactivated successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to deactivate user' })
    }
  })

  // Reactivate user
  fastify.post('/:id/reactivate', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const user = await userService.reactivateUser(request.params.id, request.user.id)
      if (!user) {
        return reply.status(404).send({ error: 'User not found' })
      }
      return reply.send({ message: 'User reactivated successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to reactivate user' })
    }
  })

  // Get user statistics
  fastify.get('/stats/overview', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const stats = await userService.getUserStats()
      return reply.send(stats)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch user statistics' })
    }
  })

  // Bulk operations
  fastify.post('/bulk/remove-role', {
    schema: {
      body: Joi.object({
        role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').required(),
        confirm: Joi.boolean().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      if (!request.body.confirm) {
        return reply.status(400).send({ error: 'Confirmation required for bulk operations' })
      }

      const deleted = await fastify.db('users')
        .where('role', request.body.role)
        .del()

      return reply.send({ 
        message: `Removed ${deleted} users with role ${request.body.role}`,
        count: deleted
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to remove users by role' })
    }
  })
}