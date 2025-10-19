import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'

export const tallyMasterRoutes = async (fastify) => {
  // Get tally master dashboard
  fastify.get('/', {
    preHandler: [fastify.authenticate, fastify.requireRole(['tally_master'])]
  }, async (request, reply) => {
    try {
      // Get active events
      const events = await fastify.db('events')
        .where('status', 'active')
        .orderBy('created_at', 'desc')

      // Get subcategories that need certification
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

      // Get certification status for each subcategory
      const subcategoryStatus = []
      for (const subcategory of subcategories) {
        const scores = await fastify.db('scores')
          .where('criterion_id', 'in', function() {
            this.select('id').from('criteria').where('subcategory_id', subcategory.id)
          })
          .where('is_signed', true)

        const totalScores = scores.length
        const expectedScores = await fastify.db('subcategory_contestants')
          .join('criteria', 'criteria.subcategory_id', 'subcategories.id')
          .where('subcategory_contestants.subcategory_id', subcategory.id)
          .count('* as count')
          .first()

        const isComplete = totalScores >= parseInt(expectedScores.count)
        const isCertified = await fastify.db('subcategory_judges')
          .where('subcategory_id', subcategory.id)
          .where('is_certified', true)
          .count('* as count')
          .first()

        subcategoryStatus.push({
          ...subcategory,
          total_scores: totalScores,
          expected_scores: parseInt(expectedScores.count),
          is_complete: isComplete,
          is_certified: parseInt(isCertified.count) > 0,
          certification_count: parseInt(isCertified.count)
        })
      }

      return reply.send({
        events,
        subcategories: subcategoryStatus,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch tally master dashboard' })
    }
  })

  // Get score review
  fastify.get('/score-review', {
    schema: {
      querystring: Joi.object({
        subcategory_id: Joi.string().uuid().optional(),
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional(),
        category_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['tally_master'])]
  }, async (request, reply) => {
    try {
      const { subcategory_id, event_id, contest_id, category_id } = request.query

      let query = fastify.db('scores')
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .join('subcategories', 'criteria.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .join('contestants', 'scores.contestant_id', 'contestants.id')
        .join('users', 'scores.judge_id', 'users.id')
        .select(
          'scores.*',
          'criteria.name as criterion_name',
          'criteria.max_score',
          'subcategories.name as subcategory_name',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name',
          'contestants.name as contestant_name',
          'contestants.contestant_number',
          'users.first_name as judge_first_name',
          'users.last_name as judge_last_name'
        )
        .where('scores.is_signed', true)
        .orderBy('events.created_at', 'desc')
        .orderBy('contests.created_at', 'desc')
        .orderBy('categories.order_index')
        .orderBy('subcategories.order_index')
        .orderBy('contestants.contestant_number')

      // Apply filters
      if (subcategory_id) query = query.where('subcategories.id', subcategory_id)
      if (category_id) query = query.where('categories.id', category_id)
      if (contest_id) query = query.where('contests.id', contest_id)
      if (event_id) query = query.where('events.id', event_id)

      const scores = await query

      // Group scores by subcategory and contestant
      const groupedScores = {}
      scores.forEach(score => {
        const key = `${score.subcategory_id}_${score.contestant_id}`
        if (!groupedScores[key]) {
          groupedScores[key] = {
            subcategory_id: score.subcategory_id,
            subcategory_name: score.subcategory_name,
            category_name: score.category_name,
            contest_name: score.contest_name,
            event_name: score.event_name,
            contestant_id: score.contestant_id,
            contestant_name: score.contestant_name,
            contestant_number: score.contestant_number,
            scores: [],
            total_score: 0,
            max_possible_score: 0
          }
        }
        groupedScores[key].scores.push(score)
        groupedScores[key].total_score += parseFloat(score.score)
        groupedScores[key].max_possible_score += parseFloat(score.max_score)
      })

      // Convert to array and calculate percentages
      const reviewData = Object.values(groupedScores).map(group => ({
        ...group,
        percentage: group.max_possible_score > 0 
          ? (group.total_score / group.max_possible_score) * 100 
          : 0
      }))

      return reply.send({
        scores: reviewData,
        total_groups: reviewData.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch score review' })
    }
  })

  // Get certification status
  fastify.get('/certification', {
    schema: {
      querystring: Joi.object({
        subcategory_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['tally_master'])]
  }, async (request, reply) => {
    try {
      const { subcategory_id } = request.query

      let query = fastify.db('subcategory_judges')
        .join('subcategories', 'subcategory_judges.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .join('events', 'contests.event_id', 'events.id')
        .join('users', 'subcategory_judges.judge_id', 'users.id')
        .select(
          'subcategory_judges.*',
          'subcategories.name as subcategory_name',
          'categories.name as category_name',
          'contests.name as contest_name',
          'events.name as event_name',
          'users.first_name',
          'users.last_name'
        )
        .orderBy('events.created_at', 'desc')
        .orderBy('contests.created_at', 'desc')
        .orderBy('categories.order_index')
        .orderBy('subcategories.order_index')

      if (subcategory_id) {
        query = query.where('subcategories.id', subcategory_id)
      }

      const certifications = await query

      // Group by subcategory
      const groupedCertifications = {}
      certifications.forEach(cert => {
        const key = cert.subcategory_id
        if (!groupedCertifications[key]) {
          groupedCertifications[key] = {
            subcategory_id: cert.subcategory_id,
            subcategory_name: cert.subcategory_name,
            category_name: cert.category_name,
            contest_name: cert.contest_name,
            event_name: cert.event_name,
            judges: [],
            certified_count: 0,
            total_count: 0
          }
        }
        groupedCertifications[key].judges.push(cert)
        groupedCertifications[key].total_count++
        if (cert.is_certified) {
          groupedCertifications[key].certified_count++
        }
      })

      // Convert to array and add completion status
      const certificationData = Object.values(groupedCertifications).map(group => ({
        ...group,
        is_fully_certified: group.certified_count === group.total_count,
        completion_percentage: group.total_count > 0 
          ? (group.certified_count / group.total_count) * 100 
          : 0
      }))

      return reply.send({
        certifications: certificationData,
        total_subcategories: certificationData.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch certification status' })
    }
  })

  // Certify totals
  fastify.post('/certify-totals', {
    schema: {
      body: Joi.object({
        subcategory_id: Joi.string().uuid().required(),
        judge_ids: Joi.array().items(Joi.string().uuid()).required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['tally_master'])]
  }, async (request, reply) => {
    try {
      const { subcategory_id, judge_ids } = request.body

      // Verify subcategory exists
      const subcategory = await fastify.db('subcategories')
        .where('id', subcategory_id)
        .first()

      if (!subcategory) {
        return reply.status(404).send({ error: 'Subcategory not found' })
      }

      // Verify all judges are assigned to this subcategory
      const assignedJudges = await fastify.db('subcategory_judges')
        .where('subcategory_id', subcategory_id)
        .whereIn('judge_id', judge_ids)
        .select('judge_id')

      if (assignedJudges.length !== judge_ids.length) {
        return reply.status(400).send({ error: 'Some judges are not assigned to this subcategory' })
      }

      // Update certification status
      await fastify.db('subcategory_judges')
        .where('subcategory_id', subcategory_id)
        .whereIn('judge_id', judge_ids)
        .update({
          is_certified: true,
          certified_at: new Date(),
          certified_by: request.user.id
        })

      return reply.send({ 
        message: `Certified totals for ${judge_ids.length} judges`,
        subcategory_id,
        certified_judges: judge_ids
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to certify totals' })
    }
  })

  // Get tally master statistics
  fastify.get('/stats', {
    preHandler: [fastify.authenticate, fastify.requireRole(['tally_master'])]
  }, async (request, reply) => {
    try {
      const [
        totalSubcategories,
        certifiedSubcategories,
        totalScores,
        signedScores
      ] = await Promise.all([
        fastify.db('subcategories').count('* as count').first(),
        fastify.db('subcategory_judges').where('is_certified', true).count('* as count').first(),
        fastify.db('scores').count('* as count').first(),
        fastify.db('scores').where('is_signed', true).count('* as count').first()
      ])

      return reply.send({
        total_subcategories: parseInt(totalSubcategories.count),
        certified_subcategories: parseInt(certifiedSubcategories.count),
        total_scores: parseInt(totalScores.count),
        signed_scores: parseInt(signedScores.count),
        certification_percentage: parseInt(totalSubcategories.count) > 0 
          ? (parseInt(certifiedSubcategories.count) / parseInt(totalSubcategories.count)) * 100 
          : 0,
        signing_percentage: parseInt(totalScores.count) > 0 
          ? (parseInt(signedScores.count) / parseInt(totalScores.count)) * 100 
          : 0,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch tally master statistics' })
    }
  })
}