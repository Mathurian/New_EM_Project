import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { ScoringService } from '../services/ScoringService.js'

/**
 * Scoring management routes
 */
export const scoringRoutes = async (fastify) => {
  const scoringService = new ScoringService()

  // Score submission schema
  const submitScoreSchema = {
    body: Joi.object({
      subcategory_id: Joi.string().uuid().required(),
      contestant_id: Joi.string().uuid().required(),
      criterion_id: Joi.string().uuid().required(),
      score: Joi.number().min(0).required(),
      comments: Joi.string().max(1000).optional()
    })
  }

  // Submit score
  fastify.post('/submit', {
    schema: submitScoreSchema,
    preHandler: [fastify.authenticate, fastify.requireRole(['judge'])]
  }, async (request, reply) => {
    try {
      const scoreData = request.body
      const userId = request.user.id

      const score = await scoringService.submitScore(scoreData, userId)

      // Emit real-time update if WebSocket is enabled
      if (fastify.websocket) {
        fastify.websocket.broadcast({
          type: 'score_submitted',
          data: {
            subcategory_id: scoreData.subcategory_id,
            contestant_id: scoreData.contestant_id,
            score: score.score,
            judge_id: userId
          }
        })
      }

      return reply.status(201).send(score)
    } catch (error) {
      fastify.log.error('Submit score error:', error)
      
      if (error.message.includes('not found') || error.message.includes('not assigned')) {
        return reply.status(400).send({
          error: error.message
        })
      }

      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get scores for subcategory
  fastify.get('/subcategory/:subcategoryId', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { subcategoryId } = request.params
      const { 
        include_comments = false, 
        group_by = 'contestant' 
      } = request.query

      const scores = await scoringService.getSubcategoryScores(subcategoryId, {
        includeComments: include_comments === 'true',
        groupBy: group_by
      })

      return scores
    } catch (error) {
      fastify.log.error('Get subcategory scores error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get contestant score tabulation
  fastify.get('/contestant/:contestantId/tabulation', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { contestantId } = request.params
      const { contest_id } = request.query

      const tabulation = await scoringService.calculateContestantTabulation(
        contestantId, 
        contest_id
      )

      return tabulation
    } catch (error) {
      fastify.log.error('Get contestant tabulation error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get judge score tabulation
  fastify.get('/judge/:judgeId/tabulation', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { judgeId } = request.params
      const { contest_id } = request.query

      const tabulation = await scoringService.calculateJudgeTabulation(
        judgeId, 
        contest_id
      )

      return tabulation
    } catch (error) {
      fastify.log.error('Get judge tabulation error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get subcategory results
  fastify.get('/subcategory/:subcategoryId/results', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { subcategoryId } = request.params

      const results = await scoringService.getSubcategoryResults(subcategoryId)

      return results
    } catch (error) {
      fastify.log.error('Get subcategory results error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Update score
  fastify.put('/:scoreId', {
    schema: {
      body: Joi.object({
        score: Joi.number().min(0).required(),
        comments: Joi.string().max(1000).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['judge'])]
  }, async (request, reply) => {
    try {
      const { scoreId } = request.params
      const { score, comments } = request.body
      const userId = request.user.id

      // Verify the score belongs to this judge
      const existingScore = await scoringService.findById(scoreId)
      if (!existingScore || existingScore.judge_id !== userId) {
        return reply.status(403).send({
          error: 'You can only update your own scores'
        })
      }

      const updatedScore = await scoringService.update(scoreId, {
        score,
        comments
      }, userId)

      // Emit real-time update
      if (fastify.websocket) {
        fastify.websocket.broadcast({
          type: 'score_updated',
          data: {
            score_id: scoreId,
            subcategory_id: existingScore.subcategory_id,
            contestant_id: existingScore.contestant_id,
            score: score,
            judge_id: userId
          }
        })
      }

      return updatedScore
    } catch (error) {
      fastify.log.error('Update score error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Delete score
  fastify.delete('/:scoreId', {
    preHandler: [fastify.authenticate, fastify.requireRole(['judge', 'organizer'])]
  }, async (request, reply) => {
    try {
      const { scoreId } = request.params
      const userId = request.user.id

      // Verify the score belongs to this judge (or user is organizer)
      const existingScore = await scoringService.findById(scoreId)
      if (!existingScore) {
        return reply.status(404).send({
          error: 'Score not found'
        })
      }

      if (request.user.role !== 'organizer' && existingScore.judge_id !== userId) {
        return reply.status(403).send({
          error: 'You can only delete your own scores'
        })
      }

      await scoringService.delete(scoreId, userId)

      // Emit real-time update
      if (fastify.websocket) {
        fastify.websocket.broadcast({
          type: 'score_deleted',
          data: {
            score_id: scoreId,
            subcategory_id: existingScore.subcategory_id,
            contestant_id: existingScore.contestant_id,
            judge_id: existingScore.judge_id
          }
        })
      }

      return { message: 'Score deleted successfully' }
    } catch (error) {
      fastify.log.error('Delete score error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })
}