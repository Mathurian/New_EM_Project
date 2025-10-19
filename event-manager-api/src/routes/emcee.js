import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'

export const emceeRoutes = async (fastify) => {
  // Get emcee dashboard
  fastify.get('/', {
    preHandler: [fastify.authenticate, fastify.requireRole(['emcee'])]
  }, async (request, reply) => {
    try {
      // Get active events
      const events = await fastify.db('events')
        .where('status', 'active')
        .orderBy('created_at', 'desc')

      // Get subcategories for emcee
      const subcategories = await fastify.db('subcategories')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('events.status', 'active')
        .select(
          'subcategories.*',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )
        .orderBy('events.created_at', 'desc')

      return reply.send({
        events,
        subcategories,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch emcee dashboard' })
    }
  })

  // Get emcee scripts
  fastify.get('/scripts', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional(),
        subcategory_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['emcee'])]
  }, async (request, reply) => {
    try {
      const { event_id, contest_id, subcategory_id } = request.query

      let query = fastify.db('emcee_scripts')
        .join('events', 'emcee_scripts.event_id', 'events.id')
        .join('contests', 'emcee_scripts.contest_id', 'contests.id')
        .join('subcategories', 'emcee_scripts.subcategory_id', 'subcategories.id')
        .select(
          'emcee_scripts.*',
          'events.name as event_name',
          'contests.name as contest_name',
          'subcategories.name as subcategory_name'
        )
        .where('emcee_scripts.is_active', true)
        .orderBy('events.created_at', 'desc')
        .orderBy('contests.created_at', 'desc')
        .orderBy('subcategories.order_index')

      // Apply filters
      if (event_id) query = query.where('events.id', event_id)
      if (contest_id) query = query.where('contests.id', contest_id)
      if (subcategory_id) query = query.where('subcategories.id', subcategory_id)

      const scripts = await query

      return reply.send({
        scripts,
        total_scripts: scripts.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch emcee scripts' })
    }
  })

  // Get contestants for emcee
  fastify.get('/contestants', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional(),
        subcategory_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['emcee'])]
  }, async (request, reply) => {
    try {
      const { event_id, contest_id, subcategory_id } = request.query

      let query = fastify.db('subcategory_contestants')
        .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
        .join('subcategories', 'subcategory_contestants.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .select(
          'contestants.*',
          'subcategories.name as subcategory_name',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )
        .orderBy('events.created_at', 'desc')
        .orderBy('contests.created_at', 'desc')
        .orderBy('categories.order_index')
        .orderBy('subcategories.order_index')
        .orderBy('contestants.contestant_number')

      // Apply filters
      if (event_id) query = query.where('events.id', event_id)
      if (contest_id) query = query.where('contests.id', contest_id)
      if (subcategory_id) query = query.where('subcategories.id', subcategory_id)

      const contestants = await query

      return reply.send({
        contestants,
        total_contestants: contestants.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contestants' })
    }
  })

  // Get judges by category
  fastify.get('/judges', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional(),
        category_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['emcee'])]
  }, async (request, reply) => {
    try {
      const { event_id, contest_id, category_id } = request.query

      let query = fastify.db('subcategory_judges')
        .join('users', 'subcategory_judges.judge_id', 'users.id')
        .join('subcategories', 'subcategory_judges.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .select(
          'users.*',
          'subcategories.name as subcategory_name',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name',
          'subcategory_judges.is_certified'
        )
        .orderBy('events.created_at', 'desc')
        .orderBy('contests.created_at', 'desc')
        .orderBy('categories.order_index')
        .orderBy('subcategories.order_index')
        .orderBy('users.last_name')
        .orderBy('users.first_name')

      // Apply filters
      if (event_id) query = query.where('events.id', event_id)
      if (contest_id) query = query.where('contests.id', contest_id)
      if (category_id) query = query.where('categories.id', category_id)

      const judges = await query

      return reply.send({
        judges,
        total_judges: judges.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch judges' })
    }
  })

  // Stream script
  fastify.get('/scripts/:id/stream', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['emcee'])]
  }, async (request, reply) => {
    try {
      const script = await fastify.db('emcee_scripts')
        .join('events', 'emcee_scripts.event_id', 'events.id')
        .join('contests', 'emcee_scripts.contest_id', 'contests.id')
        .join('subcategories', 'emcee_scripts.subcategory_id', 'subcategories.id')
        .where('emcee_scripts.id', request.params.id)
        .select(
          'emcee_scripts.*',
          'events.name as event_name',
          'contests.name as contest_name',
          'subcategories.name as subcategory_name'
        )
        .first()

      if (!script) {
        return reply.status(404).send({ error: 'Script not found' })
      }

      // Set headers for streaming
      reply.header('Content-Type', 'text/plain')
      reply.header('Content-Disposition', `inline; filename="${script.title}.txt"`)

      return reply.send(script.content)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to stream script' })
    }
  })

  // Get contestant bio
  fastify.get('/contestant/:number', {
    schema: {
      params: Joi.object({
        number: Joi.string().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['emcee'])]
  }, async (request, reply) => {
    try {
      const contestant = await fastify.db('contestants')
        .where('contestant_number', request.params.number)
        .first()

      if (!contestant) {
        return reply.status(404).send({ error: 'Contestant not found' })
      }

      // Get contestant's subcategories
      const subcategories = await fastify.db('subcategory_contestants')
        .join('subcategories', 'subcategory_contestants.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('subcategory_contestants.contestant_id', contestant.id)
        .select(
          'subcategories.name as subcategory_name',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )

      return reply.send({
        ...contestant,
        subcategories,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contestant bio' })
    }
  })

  // Get emcee statistics
  fastify.get('/stats', {
    preHandler: [fastify.authenticate, fastify.requireRole(['emcee'])]
  }, async (request, reply) => {
    try {
      const [
        totalScripts,
        activeScripts,
        totalContestants,
        totalJudges
      ] = await Promise.all([
        fastify.db('emcee_scripts').count('* as count').first(),
        fastify.db('emcee_scripts').where('is_active', true).count('* as count').first(),
        fastify.db('contestants').count('* as count').first(),
        fastify.db('users').where('role', 'judge').count('* as count').first()
      ])

      return reply.send({
        total_scripts: parseInt(totalScripts.count),
        active_scripts: parseInt(activeScripts.count),
        total_contestants: parseInt(totalContestants.count),
        total_judges: parseInt(totalJudges.count),
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch emcee statistics' })
    }
  })
}