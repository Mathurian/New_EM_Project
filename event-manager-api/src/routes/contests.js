import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { ContestService } from '../services/ContestService.js'

export const contestRoutes = async (fastify) => {
  const contestService = new ContestService()

  // Get all contests for an event
  fastify.get('/event/:eventId', {
    schema: {
      params: Joi.object({
        eventId: Joi.string().uuid().required()
      }),
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(10),
        search: Joi.string().max(100).default(''),
        status: Joi.string().valid('all', 'draft', 'active', 'completed', 'archived').default('all'),
        sortBy: Joi.string().valid('name', 'start_date', 'end_date', 'created_at').default('created_at'),
        sortOrder: Joi.string().valid('asc', 'desc').default('desc')
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const contests = await contestService.getContestsByEvent(request.params.eventId, request.query)
      return reply.send(contests)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contests' })
    }
  })

  // Get contest by ID with details
  fastify.get('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const contest = await contestService.getContestWithDetails(request.params.id)
      if (!contest) {
        return reply.status(404).send({ error: 'Contest not found' })
      }
      return reply.send(contest)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contest' })
    }
  })

  // Create contest
  fastify.post('/', {
    schema: {
      body: Joi.object({
        event_id: Joi.string().uuid().required(),
        name: Joi.string().min(1).max(200).required(),
        description: Joi.string().max(1000).optional(),
        start_date: Joi.date().required(),
        end_date: Joi.date().min(Joi.ref('start_date')).required(),
        status: Joi.string().valid('draft', 'active', 'completed', 'archived').default('draft'),
        settings: Joi.object().default({}),
        categories: Joi.array().items(
          Joi.object({
            name: Joi.string().min(1).max(200).required(),
            description: Joi.string().max(1000).optional(),
            order_index: Joi.number().integer().min(0).default(0)
          })
        ).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { categories, ...contestData } = request.body
      const contest = await contestService.createContestWithCategories(
        contestData,
        categories || [],
        request.user.id
      )
      return reply.status(201).send(contest)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create contest' })
    }
  })

  // Update contest
  fastify.put('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      }),
      body: Joi.object({
        name: Joi.string().min(1).max(200).optional(),
        description: Joi.string().max(1000).optional(),
        start_date: Joi.date().optional(),
        end_date: Joi.date().optional(),
        status: Joi.string().valid('draft', 'active', 'completed', 'archived').optional(),
        settings: Joi.object().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const contest = await contestService.updateById(
        request.params.id,
        request.body,
        request.user.id
      )
      if (!contest) {
        return reply.status(404).send({ error: 'Contest not found' })
      }
      return reply.send(contest)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update contest' })
    }
  })

  // Delete contest
  fastify.delete('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const deleted = await contestService.deleteById(request.params.id, request.user.id)
      if (!deleted) {
        return reply.status(404).send({ error: 'Contest not found' })
      }
      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete contest' })
    }
  })

  // Archive contest
  fastify.post('/:id/archive', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const archived = await contestService.archiveContest(request.params.id, request.user.id)
      if (!archived) {
        return reply.status(404).send({ error: 'Contest not found' })
      }
      return reply.send({ message: 'Contest archived successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to archive contest' })
    }
  })

  // Reactivate contest
  fastify.post('/:id/reactivate', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const reactivated = await contestService.reactivateContest(request.params.id, request.user.id)
      if (!reactivated) {
        return reply.status(404).send({ error: 'Contest not found' })
      }
      return reply.send({ message: 'Contest reactivated successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to reactivate contest' })
    }
  })

  // Get contest statistics
  fastify.get('/:id/stats', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const stats = await contestService.getContestStats(request.params.id)
      if (!stats) {
        return reply.status(404).send({ error: 'Contest not found' })
      }
      return reply.send(stats)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contest statistics' })
    }
  })
}