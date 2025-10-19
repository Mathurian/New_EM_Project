import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { ScoringService } from '../services/ScoringService.js'

export const scoringRoutes = async (fastify) => {
  const scoringService = new ScoringService()

  // Submit score
  fastify.post('/submit', {
    schema: {
      body: Joi.object({
        criterion_id: Joi.string().uuid().required(),
        contestant_id: Joi.string().uuid().required(),
        score: Joi.number().min(0).required(),
        comments: Joi.string().max(500).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['judge'])]
  }, async (request, reply) => {
    try {
      const score = await scoringService.submitScore(request.body, request.user.id)
      return reply.status(201).send(score)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: error.message || 'Failed to submit score' })
    }
  })

  // Sign score (finalize it)
  fastify.put('/:id/sign', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['judge'])]
  }, async (request, reply) => {
    try {
      const score = await scoringService.signScore(request.params.id, request.user.id)
      return reply.send(score)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: error.message || 'Failed to sign score' })
    }
  })

  // Unsign score (make it editable)
  fastify.put('/:id/unsign', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['judge'])]
  }, async (request, reply) => {
    try {
      const score = await scoringService.unsignScore(request.params.id, request.user.id)
      return reply.send(score)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: error.message || 'Failed to unsign score' })
    }
  })

  // Get scores for a subcategory
  fastify.get('/subcategory/:subcategoryId', {
    schema: {
      params: Joi.object({
        subcategoryId: Joi.string().uuid().required()
      }),
      querystring: Joi.object({
        groupBy: Joi.string().valid('contestant', 'judge').default('contestant'),
        includeUnsigned: Joi.boolean().default(true)
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const scores = await scoringService.getScoresBySubcategory(
        request.params.subcategoryId,
        request.query
      )
      return reply.send(scores)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch scores' })
    }
  })

  // Get scores for a contestant in a subcategory
  fastify.get('/contestant/:contestantId/subcategory/:subcategoryId', {
    schema: {
      params: Joi.object({
        contestantId: Joi.string().uuid().required(),
        subcategoryId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const scores = await scoringService.getContestantScores(
        request.params.contestantId,
        request.params.subcategoryId
      )
      return reply.send(scores)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contestant scores' })
    }
  })

  // Get scores for a judge in a subcategory
  fastify.get('/judge/:judgeId/subcategory/:subcategoryId', {
    schema: {
      params: Joi.object({
        judgeId: Joi.string().uuid().required(),
        subcategoryId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const scores = await scoringService.getJudgeScores(
        request.params.judgeId,
        request.params.subcategoryId
      )
      return reply.send(scores)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch judge scores' })
    }
  })

  // Calculate total score for a contestant in a subcategory
  fastify.get('/contestant/:contestantId/subcategory/:subcategoryId/total', {
    schema: {
      params: Joi.object({
        contestantId: Joi.string().uuid().required(),
        subcategoryId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const total = await scoringService.calculateContestantTotal(
        request.params.contestantId,
        request.params.subcategoryId
      )
      return reply.send(total)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to calculate total score' })
    }
  })

  // Get scoring statistics for a subcategory
  fastify.get('/subcategory/:subcategoryId/stats', {
    schema: {
      params: Joi.object({
        subcategoryId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const stats = await scoringService.getScoringStats(request.params.subcategoryId)
      return reply.send(stats)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch scoring statistics' })
    }
  })

  // Get judge's assigned subcategories
  fastify.get('/judge/assignments', {
    preHandler: [fastify.authenticate, fastify.requireRole(['judge'])]
  }, async (request, reply) => {
    try {
      const assignments = await fastify.db('subcategory_judges')
        .join('subcategories', 'subcategory_judges.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('subcategory_judges.judge_id', request.user.id)
        .select(
          'subcategories.*',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )

      return reply.send(assignments)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch judge assignments' })
    }
  })

  // Get judge's scoring interface for a subcategory
  fastify.get('/judge/subcategory/:subcategoryId/interface', {
    schema: {
      params: Joi.object({
        subcategoryId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['judge'])]
  }, async (request, reply) => {
    try {
      // Verify judge is assigned to this subcategory
      const assignment = await fastify.db('subcategory_judges')
        .where('subcategory_id', request.params.subcategoryId)
        .where('judge_id', request.user.id)
        .first()

      if (!assignment) {
        return reply.status(403).send({ error: 'You are not assigned to this subcategory' })
      }

      // Get subcategory details with contestants and criteria
      const subcategory = await fastify.db('subcategories')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('subcategories.id', request.params.subcategoryId)
        .select(
          'subcategories.*',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )
        .first()

      // Get contestants
      const contestants = await fastify.db('subcategory_contestants')
        .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
        .where('subcategory_contestants.subcategory_id', request.params.subcategoryId)
        .select('contestants.*')
        .orderBy('contestants.contestant_number')

      // Get criteria
      const criteria = await fastify.db('criteria')
        .where('subcategory_id', request.params.subcategoryId)
        .orderBy('order_index')

      subcategory.contestants = contestants
      subcategory.criteria = criteria

      return reply.send(subcategory)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch scoring interface' })
    }
  })
}