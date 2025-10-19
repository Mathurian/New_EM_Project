import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { ContestService } from '../services/ContestService.js'

/**
 * Contest management routes
 */
export const contestRoutes = async (fastify) => {
  const contestService = new ContestService()

  // Contest creation schema
  const createContestSchema = {
    body: Joi.object({
      name: Joi.string().min(1).max(255).required(),
      description: Joi.string().optional(),
      start_date: Joi.date().required(),
      end_date: Joi.date().min(Joi.ref('start_date')).required(),
      settings: Joi.object().optional()
    })
  }

  // Contest update schema
  const updateContestSchema = {
    body: Joi.object({
      name: Joi.string().min(1).max(255).optional(),
      description: Joi.string().optional(),
      start_date: Joi.date().optional(),
      end_date: Joi.date().optional(),
      status: Joi.string().valid('draft', 'active', 'completed', 'archived').optional(),
      settings: Joi.object().optional()
    })
  }

  // Get all contests
  fastify.get('/', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const {
        page = 1,
        limit = 20,
        status = null,
        search = null,
        include = []
      } = request.query

      const filters = {}
      if (status) filters.status = status
      if (search) filters.name = `%${search}%`

      const result = await contestService.findMany({
        page: parseInt(page),
        limit: parseInt(limit),
        filters,
        include: include.split(',').filter(Boolean),
        orderBy: 'created_at',
        orderDirection: 'desc'
      })

      return result
    } catch (error) {
      fastify.log.error('Get contests error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get contest by ID
  fastify.get('/:id', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { include = [] } = request.query

      const contest = await contestService.getContestWithDetails(id)

      if (!contest) {
        return reply.status(404).send({
          error: 'Contest not found'
        })
      }

      return contest
    } catch (error) {
      fastify.log.error('Get contest error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Create new contest
  fastify.post('/', {
    schema: createContestSchema,
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const contestData = {
        ...request.body,
        created_by: request.user.id
      }

      // Validate contest data
      const validation = contestService.validate(contestData)
      if (!validation.isValid) {
        return reply.status(400).send({
          error: 'Validation failed',
          details: validation.errors
        })
      }

      const contest = await contestService.create(contestData, request.user.id)

      return reply.status(201).send(contest)
    } catch (error) {
      fastify.log.error('Create contest error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Update contest
  fastify.put('/:id', {
    schema: updateContestSchema,
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const contestData = request.body

      // Validate contest data
      const validation = contestService.validate(contestData, true)
      if (!validation.isValid) {
        return reply.status(400).send({
          error: 'Validation failed',
          details: validation.errors
        })
      }

      const contest = await contestService.update(id, contestData, request.user.id)

      if (!contest) {
        return reply.status(404).send({
          error: 'Contest not found'
        })
      }

      return contest
    } catch (error) {
      fastify.log.error('Update contest error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Delete contest
  fastify.delete('/:id', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      const deleted = await contestService.delete(id, request.user.id)

      if (!deleted) {
        return reply.status(404).send({
          error: 'Contest not found'
        })
      }

      return { message: 'Contest deleted successfully' }
    } catch (error) {
      fastify.log.error('Delete contest error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Archive contest
  fastify.post('/:id/archive', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      await contestService.archiveContest(id, request.user.id)

      return { message: 'Contest archived successfully' }
    } catch (error) {
      fastify.log.error('Archive contest error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Reactivate contest
  fastify.post('/:id/reactivate', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      await contestService.reactivateContest(id, request.user.id)

      return { message: 'Contest reactivated successfully' }
    } catch (error) {
      fastify.log.error('Reactivate contest error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get contest statistics
  fastify.get('/:id/stats', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      const stats = await contestService.getContestStats(id)

      if (!stats) {
        return reply.status(404).send({
          error: 'Contest not found'
        })
      }

      return stats
    } catch (error) {
      fastify.log.error('Get contest stats error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })
}