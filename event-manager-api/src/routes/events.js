import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { EventService } from '../services/EventService.js'

export const eventRoutes = async (fastify) => {
  const eventService = new EventService()

  // Get all events
  fastify.get('/', {
    schema: {
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
      const events = await eventService.getAllEvents(request.query)
      return reply.send(events)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch events' })
    }
  })

  // Get event by ID with details
  fastify.get('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const event = await eventService.getEventWithDetails(request.params.id)
      if (!event) {
        return reply.status(404).send({ error: 'Event not found' })
      }
      return reply.send(event)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch event' })
    }
  })

  // Create event
  fastify.post('/', {
    schema: {
      body: Joi.object({
        name: Joi.string().min(1).max(200).required(),
        description: Joi.string().max(1000).optional(),
        start_date: Joi.date().required(),
        end_date: Joi.date().min(Joi.ref('start_date')).required(),
        status: Joi.string().valid('draft', 'active', 'completed', 'archived').default('draft'),
        settings: Joi.object().default({}),
        contests: Joi.array().items(
          Joi.object({
            name: Joi.string().min(1).max(200).required(),
            description: Joi.string().max(1000).optional(),
            start_date: Joi.date().required(),
            end_date: Joi.date().min(Joi.ref('start_date')).required(),
            status: Joi.string().valid('draft', 'active', 'completed', 'archived').default('draft'),
            settings: Joi.object().default({})
          })
        ).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { contests, ...eventData } = request.body
      const event = await eventService.createEventWithContests(
        eventData,
        contests || [],
        request.user.id
      )
      return reply.status(201).send(event)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create event' })
    }
  })

  // Update event
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
      const event = await eventService.updateById(
        request.params.id,
        request.body,
        request.user.id
      )
      if (!event) {
        return reply.status(404).send({ error: 'Event not found' })
      }
      return reply.send(event)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update event' })
    }
  })

  // Delete event
  fastify.delete('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const deleted = await eventService.deleteById(request.params.id, request.user.id)
      if (!deleted) {
        return reply.status(404).send({ error: 'Event not found' })
      }
      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete event' })
    }
  })

  // Archive event
  fastify.post('/:id/archive', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const archived = await eventService.archiveEvent(request.params.id, request.user.id)
      if (!archived) {
        return reply.status(404).send({ error: 'Event not found' })
      }
      return reply.send({ message: 'Event archived successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to archive event' })
    }
  })

  // Reactivate event
  fastify.post('/:id/reactivate', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const reactivated = await eventService.reactivateEvent(request.params.id, request.user.id)
      if (!reactivated) {
        return reply.status(404).send({ error: 'Event not found' })
      }
      return reply.send({ message: 'Event reactivated successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to reactivate event' })
    }
  })

  // Get event statistics
  fastify.get('/:id/stats', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const stats = await eventService.getEventStats(request.params.id)
      if (!stats) {
        return reply.status(404).send({ error: 'Event not found' })
      }
      return reply.send(stats)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch event statistics' })
    }
  })

  // Get archived events
  fastify.get('/archived', {
    schema: {
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(10),
        search: Joi.string().max(100).default('')
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const events = await eventService.getArchivedEvents(request.query)
      return reply.send(events)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch archived events' })
    }
  })

  // Get active events
  fastify.get('/active', {
    schema: {
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(10),
        search: Joi.string().max(100).default('')
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const events = await eventService.getActiveEvents(request.query)
      return reply.send(events)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch active events' })
    }
  })
}