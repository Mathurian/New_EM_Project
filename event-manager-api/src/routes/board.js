import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'

export const boardRoutes = async (fastify) => {
  // Get board dashboard
  fastify.get('/', {
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      // Get all events
      const events = await fastify.db('events')
        .orderBy('created_at', 'desc')

      // Get system statistics
      const [
        totalUsers,
        totalEvents,
        totalContests,
        totalContestants,
        totalJudges,
        totalScores
      ] = await Promise.all([
        fastify.db('users').count('* as count').first(),
        fastify.db('events').count('* as count').first(),
        fastify.db('contests').count('* as count').first(),
        fastify.db('contestants').count('* as count').first(),
        fastify.db('users').where('role', 'judge').count('* as count').first(),
        fastify.db('scores').count('* as count').first()
      ])

      return reply.send({
        events,
        statistics: {
          total_users: parseInt(totalUsers.count),
          total_events: parseInt(totalEvents.count),
          total_contests: parseInt(totalContests.count),
          total_contestants: parseInt(totalContestants.count),
          total_judges: parseInt(totalJudges.count),
          total_scores: parseInt(totalScores.count)
        },
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch board dashboard' })
    }
  })

  // Get certification status
  fastify.get('/certification-status', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      const { event_id, contest_id } = request.query

      let query = fastify.db('subcategories')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .select(
          'subcategories.*',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name'
        )
        .orderBy('events.created_at', 'desc')
        .orderBy('contests.created_at', 'desc')
        .orderBy('categories.order_index')
        .orderBy('subcategories.order_index')

      // Apply filters
      if (event_id) query = query.where('events.id', event_id)
      if (contest_id) query = query.where('contests.id', contest_id)

      const subcategories = await query

      // Get certification status for each subcategory
      const certificationData = []
      for (const subcategory of subcategories) {
        const [
          judgeCertifications,
          finalCertifications,
          totalScores,
          signedScores
        ] = await Promise.all([
          fastify.db('subcategory_judges')
            .where('subcategory_id', subcategory.id)
            .select('is_certified'),
          fastify.db('final_certifications')
            .where('subcategory_id', subcategory.id)
            .count('* as count')
            .first(),
          fastify.db('scores')
            .where('criterion_id', 'in', function() {
              this.select('id').from('criteria').where('subcategory_id', subcategory.id)
            })
            .count('* as count')
            .first(),
          fastify.db('scores')
            .where('criterion_id', 'in', function() {
              this.select('id').from('criteria').where('subcategory_id', subcategory.id)
            })
            .where('is_signed', true)
            .count('* as count')
            .first()
        ])

        const totalJudges = judgeCertifications.length
        const certifiedJudges = judgeCertifications.filter(c => c.is_certified).length
        const hasFinalCertification = parseInt(finalCertifications.count) > 0

        certificationData.push({
          ...subcategory,
          total_judges,
          certified_judges,
          total_scores: parseInt(totalScores.count),
          signed_scores: parseInt(signedScores.count),
          is_fully_certified: certifiedJudges === totalJudges,
          is_fully_signed: parseInt(signedScores.count) === parseInt(totalScores.count),
          has_final_certification: hasFinalCertification,
          status: hasFinalCertification ? 'final_certified' :
                  certifiedJudges === totalJudges && parseInt(signedScores.count) === parseInt(totalScores.count) ? 'ready_for_final' :
                  certifiedJudges > 0 ? 'partial' : 'pending'
        })
      }

      return reply.send({
        subcategories: certificationData,
        total_subcategories: certificationData.length,
        final_certified: certificationData.filter(s => s.has_final_certification).length,
        ready_for_final: certificationData.filter(s => s.status === 'ready_for_final').length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch certification status' })
    }
  })

  // Get emcee scripts
  fastify.get('/emcee-scripts', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional(),
        subcategory_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
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

  // Upload emcee script
  fastify.post('/emcee-scripts', {
    schema: {
      body: Joi.object({
        event_id: Joi.string().uuid().required(),
        contest_id: Joi.string().uuid().required(),
        subcategory_id: Joi.string().uuid().required(),
        title: Joi.string().min(1).max(200).required(),
        content: Joi.string().required(),
        is_active: Joi.boolean().default(true)
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      const script = await fastify.db('emcee_scripts').insert({
        ...request.body,
        created_by: request.user.id
      }).returning('*')

      return reply.status(201).send(script[0])
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to upload emcee script' })
    }
  })

  // Toggle emcee script
  fastify.put('/emcee-scripts/:id/toggle', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      const script = await fastify.db('emcee_scripts')
        .where('id', request.params.id)
        .first()

      if (!script) {
        return reply.status(404).send({ error: 'Script not found' })
      }

      const updatedScript = await fastify.db('emcee_scripts')
        .where('id', request.params.id)
        .update({
          is_active: !script.is_active,
          updated_by: request.user.id,
          updated_at: new Date()
        })
        .returning('*')

      return reply.send(updatedScript[0])
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to toggle emcee script' })
    }
  })

  // Delete emcee script
  fastify.delete('/emcee-scripts/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      const deleted = await fastify.db('emcee_scripts')
        .where('id', request.params.id)
        .del()

      if (deleted === 0) {
        return reply.status(404).send({ error: 'Script not found' })
      }

      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete emcee script' })
    }
  })

  // Get print reports
  fastify.get('/print-reports', {
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      // Get available reports
      const reports = [
        {
          id: 'contestants',
          name: 'Contestants Report',
          description: 'List of all contestants with their information',
          endpoint: '/api/print/contestants'
        },
        {
          id: 'judges',
          name: 'Judges Report',
          description: 'List of all judges with their assignments',
          endpoint: '/api/print/judges'
        },
        {
          id: 'categories',
          name: 'Categories Report',
          description: 'List of all categories and subcategories',
          endpoint: '/api/print/categories'
        },
        {
          id: 'scores',
          name: 'Scores Report',
          description: 'Complete scoring report for all subcategories',
          endpoint: '/api/print/scores'
        }
      ]

      return reply.send({
        reports,
        total_reports: reports.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch print reports' })
    }
  })

  // Get contest summary
  fastify.get('/contest-summary/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
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

      return reply.send({
        contest,
        categories: categoryResults,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch contest summary' })
    }
  })

  // Remove judge scores
  fastify.post('/remove-judge-scores', {
    schema: {
      body: Joi.object({
        judge_id: Joi.string().uuid().required(),
        subcategory_id: Joi.string().uuid().optional(),
        confirm: Joi.boolean().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      const { judge_id, subcategory_id, confirm } = request.body

      if (!confirm) {
        return reply.status(400).send({ error: 'Confirmation required for score removal' })
      }

      let query = fastify.db('scores').where('judge_id', judge_id)
      
      if (subcategory_id) {
        query = query.where('criterion_id', 'in', function() {
          this.select('id').from('criteria').where('subcategory_id', subcategory_id)
        })
      }

      const deleted = await query.del()

      return reply.send({
        message: `Removed ${deleted} scores`,
        judge_id,
        subcategory_id,
        deleted_count: deleted
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to remove judge scores' })
    }
  })

  // Get board statistics
  fastify.get('/stats', {
    preHandler: [fastify.authenticate, fastify.requireRole(['board'])]
  }, async (request, reply) => {
    try {
      const [
        totalUsers,
        totalEvents,
        totalContests,
        totalCategories,
        totalSubcategories,
        totalContestants,
        totalJudges,
        totalScores,
        signedScores,
        certifiedSubcategories
      ] = await Promise.all([
        fastify.db('users').count('* as count').first(),
        fastify.db('events').count('* as count').first(),
        fastify.db('contests').count('* as count').first(),
        fastify.db('categories').count('* as count').first(),
        fastify.db('subcategories').count('* as count').first(),
        fastify.db('contestants').count('* as count').first(),
        fastify.db('users').where('role', 'judge').count('* as count').first(),
        fastify.db('scores').count('* as count').first(),
        fastify.db('scores').where('is_signed', true).count('* as count').first(),
        fastify.db('subcategory_judges').where('is_certified', true).count('* as count').first()
      ])

      return reply.send({
        total_users: parseInt(totalUsers.count),
        total_events: parseInt(totalEvents.count),
        total_contests: parseInt(totalContests.count),
        total_categories: parseInt(totalCategories.count),
        total_subcategories: parseInt(totalSubcategories.count),
        total_contestants: parseInt(totalContestants.count),
        total_judges: parseInt(totalJudges.count),
        total_scores: parseInt(totalScores.count),
        signed_scores: parseInt(signedScores.count),
        certified_subcategories: parseInt(certifiedSubcategories.count),
        signing_percentage: parseInt(totalScores.count) > 0 
          ? (parseInt(signedScores.count) / parseInt(totalScores.count)) * 100 
          : 0,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch board statistics' })
    }
  })
}