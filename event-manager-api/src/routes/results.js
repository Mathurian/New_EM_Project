import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { ScoringService } from '../services/ScoringService.js'

export const resultsRoutes = async (fastify) => {
  const scoringService = new ScoringService()

  // Get results for an event
  fastify.get('/event/:eventId', {
    schema: {
      params: Joi.object({
        eventId: Joi.string().uuid().required()
      }),
      querystring: Joi.object({
        format: Joi.string().valid('summary', 'detailed', 'leaderboard').default('summary'),
        categoryId: Joi.string().uuid().optional(),
        subcategoryId: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { eventId } = request.params
      const { format, categoryId, subcategoryId } = request.query

      // Get event details
      const event = await fastify.db('events').where('id', eventId).first()
      if (!event) {
        return reply.status(404).send({ error: 'Event not found' })
      }

      // Get contests for the event
      const contests = await fastify.db('contests')
        .where('event_id', eventId)
        .orderBy('created_at', 'desc')

      const results = []

      for (const contest of contests) {
        // Get categories for this contest
        const categories = await fastify.db('categories')
          .where('contest_id', contest.id)
          .orderBy('order_index')

        const contestResults = {
          contest,
          categories: []
        }

        for (const category of categories) {
          // Filter by category if specified
          if (categoryId && category.id !== categoryId) continue

          // Get subcategories for this category
          const subcategories = await fastify.db('subcategories')
            .where('category_id', category.id)
            .orderBy('order_index')

          const categoryResults = {
            category,
            subcategories: []
          }

          for (const subcategory of subcategories) {
            // Filter by subcategory if specified
            if (subcategoryId && subcategory.id !== subcategoryId) continue

            // Get contestants for this subcategory
            const contestants = await fastify.db('subcategory_contestants')
              .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
              .where('subcategory_contestants.subcategory_id', subcategory.id)
              .select('contestants.*')
              .orderBy('contestants.contestant_number')

            // Calculate scores for each contestant
            const contestantResults = []
            for (const contestant of contestants) {
              const total = await scoringService.calculateContestantTotal(
                contestant.id,
                subcategory.id
              )
              contestantResults.push({
                ...contestant,
                ...total
              })
            }

            // Sort by total score (descending)
            contestantResults.sort((a, b) => b.total_score - a.total_score)

            // Add ranking
            contestantResults.forEach((result, index) => {
              result.rank = index + 1
            })

            categoryResults.subcategories.push({
              ...subcategory,
              contestants: contestantResults
            })
          }

          contestResults.categories.push(categoryResults)
        }

        results.push(contestResults)
      }

      return reply.send({
        event,
        results,
        format,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch event results' })
    }
  })

  // Get results for a contest
  fastify.get('/contest/:contestId', {
    schema: {
      params: Joi.object({
        contestId: Joi.string().uuid().required()
      }),
      querystring: Joi.object({
        format: Joi.string().valid('summary', 'detailed', 'leaderboard').default('summary')
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { contestId } = request.params
      const { format } = request.query

      // Get contest details
      const contest = await fastify.db('contests')
        .join('events', 'contests.event_id', 'events.id')
        .where('contests.id', contestId)
        .select('contests.*', 'events.name as event_name')
        .first()

      if (!contest) {
        return reply.status(404).send({ error: 'Contest not found' })
      }

      // Get categories for this contest
      const categories = await fastify.db('categories')
        .where('contest_id', contestId)
        .orderBy('order_index')

      const results = []

      for (const category of categories) {
        // Get subcategories for this category
        const subcategories = await fastify.db('subcategories')
          .where('category_id', category.id)
          .orderBy('order_index')

        const categoryResults = {
          category,
          subcategories: []
        }

        for (const subcategory of subcategories) {
          // Get contestants for this subcategory
          const contestants = await fastify.db('subcategory_contestants')
            .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
            .where('subcategory_contestants.subcategory_id', subcategory.id)
            .select('contestants.*')
            .orderBy('contestants.contestant_number')

          // Calculate scores for each contestant
          const contestantResults = []
          for (const contestant of contestants) {
            const total = await scoringService.calculateContestantTotal(
              contestant.id,
              subcategory.id
            )
            contestantResults.push({
              ...contestant,
              ...total
            })
          }

          // Sort by total score (descending)
          contestantResults.sort((a, b) => b.total_score - a.total_score)

          // Add ranking
          contestantResults.forEach((result, index) => {
            result.rank = index + 1
          })

          categoryResults.subcategories.push({
            ...subcategory,
            contestants: contestantResults
          })
        }

        results.push(categoryResults)
      }

      return reply.send({
        contest,
        results,
        format,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contest results' })
    }
  })

  // Get leaderboard
  fastify.get('/leaderboard', {
    schema: {
      querystring: Joi.object({
        eventId: Joi.string().uuid().optional(),
        contestId: Joi.string().uuid().optional(),
        categoryId: Joi.string().uuid().optional(),
        subcategoryId: Joi.string().uuid().optional(),
        limit: Joi.number().integer().min(1).max(100).default(50)
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { eventId, contestId, categoryId, subcategoryId, limit } = request.query

      let query = fastify.db('scores')
        .join('contestants', 'scores.contestant_id', 'contestants.id')
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .join('subcategories', 'criteria.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('scores.is_signed', true)
        .select(
          'contestants.id as contestant_id',
          'contestants.name as contestant_name',
          'contestants.contestant_number',
          'subcategories.id as subcategory_id',
          'subcategories.name as subcategory_name',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )

      // Apply filters
      if (eventId) query = query.where('events.id', eventId)
      if (contestId) query = query.where('contests.id', contestId)
      if (categoryId) query = query.where('categories.id', categoryId)
      if (subcategoryId) query = query.where('subcategories.id', subcategoryId)

      const scores = await query

      // Group by contestant and calculate totals
      const contestantTotals = {}
      scores.forEach(score => {
        const key = `${score.contestant_id}_${score.subcategory_id}`
        if (!contestantTotals[key]) {
          contestantTotals[key] = {
            contestant_id: score.contestant_id,
            contestant_name: score.contestant_name,
            contestant_number: score.contestant_number,
            subcategory_id: score.subcategory_id,
            subcategory_name: score.subcategory_name,
            category_name: score.category_name,
            contest_name: score.contest_name,
            event_name: score.event_name,
            total_score: 0,
            max_possible_score: 0
          }
        }
        contestantTotals[key].total_score += parseFloat(score.score)
        contestantTotals[key].max_possible_score += score.max_score
      })

      // Convert to array and calculate percentages
      const leaderboard = Object.values(contestantTotals).map(entry => ({
        ...entry,
        percentage: entry.max_possible_score > 0 
          ? (entry.total_score / entry.max_possible_score) * 100 
          : 0
      }))

      // Sort by total score (descending)
      leaderboard.sort((a, b) => b.total_score - a.total_score)

      // Add ranking
      leaderboard.forEach((entry, index) => {
        entry.rank = index + 1
      })

      return reply.send({
        leaderboard: leaderboard.slice(0, limit),
        total_entries: leaderboard.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch leaderboard' })
    }
  })

  // Get contestant overview
  fastify.get('/contestant/:contestantId', {
    schema: {
      params: Joi.object({
        contestantId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { contestantId } = request.params

      // Get contestant details
      const contestant = await fastify.db('contestants').where('id', contestantId).first()
      if (!contestant) {
        return reply.status(404).send({ error: 'Contestant not found' })
      }

      // Get all subcategories this contestant is assigned to
      const subcategories = await fastify.db('subcategory_contestants')
        .join('subcategories', 'subcategory_contestants.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('subcategory_contestants.contestant_id', contestantId)
        .select(
          'subcategories.*',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )

      // Calculate scores for each subcategory
      const results = []
      for (const subcategory of subcategories) {
        const total = await scoringService.calculateContestantTotal(
          contestantId,
          subcategory.id
        )
        results.push({
          ...subcategory,
          ...total
        })
      }

      return reply.send({
        contestant,
        results,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contestant overview' })
    }
  })

  // Generate PDF report
  fastify.get('/event/:eventId/report/pdf', {
    schema: {
      params: Joi.object({
        eventId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'tally_master', 'auditor', 'board'])]
  }, async (request, reply) => {
    try {
      // This would integrate with a PDF generation library like PDFKit
      // For now, return a placeholder response
      return reply.send({ 
        message: 'PDF report generation not yet implemented',
        eventId: request.params.eventId
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to generate PDF report' })
    }
  })

  // Generate Excel report
  fastify.get('/event/:eventId/report/excel', {
    schema: {
      params: Joi.object({
        eventId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'tally_master', 'auditor', 'board'])]
  }, async (request, reply) => {
    try {
      // This would integrate with an Excel generation library like ExcelJS
      // For now, return a placeholder response
      return reply.send({ 
        message: 'Excel report generation not yet implemented',
        eventId: request.params.eventId
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to generate Excel report' })
    }
  })

  // Add deduction
  fastify.post('/contestant/:contestantId/subcategory/:subcategoryId/deduction', {
    schema: {
      params: Joi.object({
        contestantId: Joi.string().uuid().required(),
        subcategoryId: Joi.string().uuid().required()
      }),
      body: Joi.object({
        amount: Joi.number().min(0).required(),
        comment: Joi.string().max(500).optional(),
        reason: Joi.string().max(200).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'tally_master'])]
  }, async (request, reply) => {
    try {
      const { contestantId, subcategoryId } = request.params
      const { amount, comment, reason } = request.body

      // Create deduction record
      const deduction = await fastify.db('overall_deductions').insert({
        subcategory_id: subcategoryId,
        contestant_id: contestantId,
        amount: amount.toString(),
        comment: comment || '',
        reason: reason || '',
        created_by: request.user.id
      }).returning('*')

      return reply.status(201).send(deduction[0])
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to add deduction' })
    }
  })

  // Unsign all scores for a subcategory
  fastify.post('/subcategory/:subcategoryId/unsign-all', {
    schema: {
      params: Joi.object({
        subcategoryId: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'tally_master'])]
  }, async (request, reply) => {
    try {
      const { subcategoryId } = request.params

      // Get all criteria for this subcategory
      const criteria = await fastify.db('criteria')
        .where('subcategory_id', subcategoryId)
        .select('id')

      const criterionIds = criteria.map(c => c.id)

      // Unsign all scores for these criteria
      const updated = await fastify.db('scores')
        .whereIn('criterion_id', criterionIds)
        .update({
          is_signed: false,
          signed_at: null,
          updated_at: new Date()
        })

      return reply.send({ 
        message: `Unsign ${updated} scores`,
        count: updated
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to unsign scores' })
    }
  })
}