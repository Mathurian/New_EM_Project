import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'

export const printRoutes = async (fastify) => {
  // Print contestant
  fastify.get('/contestant/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const contestant = await fastify.db('contestants')
        .where('id', request.params.id)
        .first()

      if (!contestant) {
        return reply.status(404).send({ error: 'Contestant not found' })
      }

      // Get contestant's subcategories and scores
      const subcategories = await fastify.db('subcategory_contestants')
        .join('subcategories', 'subcategory_contestants.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('subcategory_contestants.contestant_id', request.params.id)
        .select(
          'subcategories.*',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )

      // Calculate scores for each subcategory
      const subcategoryResults = []
      for (const subcategory of subcategories) {
        const scores = await fastify.db('scores')
          .join('criteria', 'scores.criterion_id', 'criteria.id')
          .join('users', 'scores.judge_id', 'users.id')
          .where('scores.contestant_id', request.params.id)
          .where('criteria.subcategory_id', subcategory.id)
          .where('scores.is_signed', true)
          .select(
            'scores.*',
            'criteria.name as criterion_name',
            'criteria.max_score',
            'users.first_name as judge_first_name',
            'users.last_name as judge_last_name'
          )

        const totalScore = scores.reduce((sum, score) => sum + parseFloat(score.score), 0)
        const maxPossibleScore = scores.reduce((sum, score) => sum + parseFloat(score.max_score), 0)

        subcategoryResults.push({
          ...subcategory,
          scores,
          total_score: totalScore,
          max_possible_score: maxPossibleScore,
          percentage: maxPossibleScore > 0 ? (totalScore / maxPossibleScore) * 100 : 0
        })
      }

      const printData = {
        contestant,
        subcategories: subcategoryResults,
        generated_at: new Date().toISOString(),
        generated_by: request.user.first_name + ' ' + request.user.last_name
      }

      // Set headers for PDF generation
      reply.header('Content-Type', 'application/pdf')
      reply.header('Content-Disposition', `attachment; filename="contestant_${contestant.contestant_number}.pdf"`)

      // This would integrate with a PDF generation library like PDFKit
      // For now, return JSON data
      return reply.send(printData)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to generate contestant print' })
    }
  })

  // Print judge
  fastify.get('/judge/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const judge = await fastify.db('users')
        .where('id', request.params.id)
        .where('role', 'judge')
        .first()

      if (!judge) {
        return reply.status(404).send({ error: 'Judge not found' })
      }

      // Get judge's assigned subcategories
      const subcategories = await fastify.db('subcategory_judges')
        .join('subcategories', 'subcategory_judges.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('subcategory_judges.judge_id', request.params.id)
        .select(
          'subcategories.*',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name',
          'subcategory_judges.is_certified'
        )

      // Get judge's scores
      const scores = await fastify.db('scores')
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .join('contestants', 'scores.contestant_id', 'contestants.id')
        .join('subcategories', 'criteria.subcategory_id', 'subcategories.id')
        .where('scores.judge_id', request.params.id)
        .where('scores.is_signed', true)
        .select(
          'scores.*',
          'criteria.name as criterion_name',
          'contestants.name as contestant_name',
          'contestants.contestant_number',
          'subcategories.name as subcategory_name'
        )
        .orderBy('subcategories.name')
        .orderBy('contestants.contestant_number')

      const printData = {
        judge,
        subcategories,
        scores,
        generated_at: new Date().toISOString(),
        generated_by: request.user.first_name + ' ' + request.user.last_name
      }

      // Set headers for PDF generation
      reply.header('Content-Type', 'application/pdf')
      reply.header('Content-Disposition', `attachment; filename="judge_${judge.id}.pdf"`)

      return reply.send(printData)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to generate judge print' })
    }
  })

  // Print category
  fastify.get('/category/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const category = await fastify.db('categories')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .where('categories.id', request.params.id)
        .select(
          'categories.*',
          'contests.name as contest_name',
          'events.name as event_name'
        )
        .first()

      if (!category) {
        return reply.status(404).send({ error: 'Category not found' })
      }

      // Get subcategories for this category
      const subcategories = await fastify.db('subcategories')
        .where('category_id', request.params.id)
        .orderBy('order_index')

      // Get contestants for each subcategory
      const subcategoryResults = []
      for (const subcategory of subcategories) {
        const contestants = await fastify.db('subcategory_contestants')
          .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
          .where('subcategory_contestants.subcategory_id', subcategory.id)
          .select('contestants.*')
          .orderBy('contestants.contestant_number')

        // Calculate scores for each contestant
        const contestantResults = []
        for (const contestant of contestants) {
          const scores = await fastify.db('scores')
            .join('criteria', 'scores.criterion_id', 'criteria.id')
            .where('scores.contestant_id', contestant.id)
            .where('criteria.subcategory_id', subcategory.id)
            .where('scores.is_signed', true)
            .select(
              'scores.*',
              'criteria.name as criterion_name',
              'criteria.max_score'
            )

          const totalScore = scores.reduce((sum, score) => sum + parseFloat(score.score), 0)
          const maxPossibleScore = scores.reduce((sum, score) => sum + parseFloat(score.max_score), 0)

          contestantResults.push({
            ...contestant,
            scores,
            total_score: totalScore,
            max_possible_score: maxPossibleScore,
            percentage: maxPossibleScore > 0 ? (totalScore / maxPossibleScore) * 100 : 0
          })
        }

        // Sort by total score (descending)
        contestantResults.sort((a, b) => b.total_score - a.total_score)

        subcategoryResults.push({
          ...subcategory,
          contestants: contestantResults
        })
      }

      const printData = {
        category,
        subcategories: subcategoryResults,
        generated_at: new Date().toISOString(),
        generated_by: request.user.first_name + ' ' + request.user.last_name
      }

      // Set headers for PDF generation
      reply.header('Content-Type', 'application/pdf')
      reply.header('Content-Disposition', `attachment; filename="category_${category.name}.pdf"`)

      return reply.send(printData)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to generate category print' })
    }
  })

  // Print contest summary
  fastify.get('/contest/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const contest = await fastify.db('contests')
        .join('events', 'contests.event_id', 'events.id')
        .where('contests.id', request.params.id)
        .select(
          'contests.*',
          'events.name as event_name'
        )
        .first()

      if (!contest) {
        return reply.status(404).send({ error: 'Contest not found' })
      }

      // Get categories for this contest
      const categories = await fastify.db('categories')
        .where('contest_id', request.params.id)
        .orderBy('order_index')

      // Get detailed results for each category
      const categoryResults = []
      for (const category of categories) {
        const subcategories = await fastify.db('subcategories')
          .where('category_id', category.id)
          .orderBy('order_index')

        const subcategoryResults = []
        for (const subcategory of subcategories) {
          const contestants = await fastify.db('subcategory_contestants')
            .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
            .where('subcategory_contestants.subcategory_id', subcategory.id)
            .select('contestants.*')
            .orderBy('contestants.contestant_number')

          const contestantResults = []
          for (const contestant of contestants) {
            const scores = await fastify.db('scores')
              .join('criteria', 'scores.criterion_id', 'criteria.id')
              .where('scores.contestant_id', contestant.id)
              .where('criteria.subcategory_id', subcategory.id)
              .where('scores.is_signed', true)
              .select(
                'scores.*',
                'criteria.name as criterion_name',
                'criteria.max_score'
              )

            const totalScore = scores.reduce((sum, score) => sum + parseFloat(score.score), 0)
            const maxPossibleScore = scores.reduce((sum, score) => sum + parseFloat(score.max_score), 0)

            contestantResults.push({
              ...contestant,
              scores,
              total_score: totalScore,
              max_possible_score: maxPossibleScore,
              percentage: maxPossibleScore > 0 ? (totalScore / maxPossibleScore) * 100 : 0
            })
          }

          // Sort by total score (descending)
          contestantResults.sort((a, b) => b.total_score - a.total_score)

          subcategoryResults.push({
            ...subcategory,
            contestants: contestantResults
          })
        }

        categoryResults.push({
          ...category,
          subcategories: subcategoryResults
        })
      }

      const printData = {
        contest,
        categories: categoryResults,
        generated_at: new Date().toISOString(),
        generated_by: request.user.first_name + ' ' + request.user.last_name
      }

      // Set headers for PDF generation
      reply.header('Content-Type', 'application/pdf')
      reply.header('Content-Disposition', `attachment; filename="contest_${contest.name}.pdf"`)

      return reply.send(printData)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to generate contest print' })
    }
  })

  // Get print statistics
  fastify.get('/stats', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const [
        totalContestants,
        totalJudges,
        totalCategories,
        totalContests
      ] = await Promise.all([
        fastify.db('contestants').count('* as count').first(),
        fastify.db('users').where('role', 'judge').count('* as count').first(),
        fastify.db('categories').count('* as count').first(),
        fastify.db('contests').count('* as count').first()
      ])

      return reply.send({
        contestants: parseInt(totalContestants.count),
        judges: parseInt(totalJudges.count),
        categories: parseInt(totalCategories.count),
        contests: parseInt(totalContests.count),
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch print statistics' })
    }
  })
}