import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'

export const auditorRoutes = async (fastify) => {
  // Get auditor dashboard
  fastify.get('/', {
    preHandler: [fastify.authenticate, fastify.requireRole(['auditor'])]
  }, async (request, reply) => {
    try {
      // Get active events
      const events = await fastify.db('events')
        .where('status', 'active')
        .orderBy('created_at', 'desc')

      // Get subcategories for audit
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
      return reply.status(500).send({ error: 'Failed to fetch auditor dashboard' })
    }
  })

  // Get scores for audit
  fastify.get('/scores', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional(),
        category_id: Joi.string().uuid().optional(),
        subcategory_id: Joi.string().uuid().optional(),
        judge_id: Joi.string().uuid().optional(),
        contestant_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['auditor'])]
  }, async (request, reply) => {
    try {
      const { event_id, contest_id, category_id, subcategory_id, judge_id, contestant_id } = request.query

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
        .orderBy('events.created_at', 'desc')
        .orderBy('contests.created_at', 'desc')
        .orderBy('categories.order_index')
        .orderBy('subcategories.order_index')
        .orderBy('contestants.contestant_number')

      // Apply filters
      if (event_id) query = query.where('events.id', event_id)
      if (contest_id) query = query.where('contests.id', contest_id)
      if (category_id) query = query.where('categories.id', category_id)
      if (subcategory_id) query = query.where('subcategories.id', subcategory_id)
      if (judge_id) query = query.where('scores.judge_id', judge_id)
      if (contestant_id) query = query.where('scores.contestant_id', contestant_id)

      const scores = await query

      // Group scores by subcategory and contestant for analysis
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
            max_possible_score: 0,
            judges: new Set()
          }
        }
        groupedScores[key].scores.push(score)
        groupedScores[key].total_score += parseFloat(score.score)
        groupedScores[key].max_possible_score += parseFloat(score.max_score)
        groupedScores[key].judges.add(score.judge_id)
      })

      // Convert to array and add analysis
      const auditData = Object.values(groupedScores).map(group => ({
        ...group,
        judges: Array.from(group.judges),
        judge_count: group.judges.size,
        percentage: group.max_possible_score > 0 
          ? (group.total_score / group.max_possible_score) * 100 
          : 0,
        is_complete: group.scores.length > 0,
        has_unsigned_scores: group.scores.some(score => !score.is_signed)
      }))

      return reply.send({
        scores: auditData,
        total_groups: auditData.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch scores for audit' })
    }
  })

  // Get tally master status
  fastify.get('/tally-master-status', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['auditor'])]
  }, async (request, reply) => {
    try {
      const { event_id, contest_id } = request.query

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

      // Apply filters
      if (event_id) query = query.where('events.id', event_id)
      if (contest_id) query = query.where('contests.id', contest_id)

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

      // Convert to array and add status
      const statusData = Object.values(groupedCertifications).map(group => ({
        ...group,
        is_fully_certified: group.certified_count === group.total_count,
        completion_percentage: group.total_count > 0 
          ? (group.certified_count / group.total_count) * 100 
          : 0,
        status: group.certified_count === group.total_count ? 'complete' : 
                group.certified_count > 0 ? 'partial' : 'pending'
      }))

      return reply.send({
        certifications: statusData,
        total_subcategories: statusData.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch tally master status' })
    }
  })

  // Get final certification
  fastify.get('/final-certification', {
    schema: {
      querystring: Joi.object({
        event_id: Joi.string().uuid().optional(),
        contest_id: Joi.string().uuid().optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['auditor'])]
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

      // Check certification status for each subcategory
      const certificationData = []
      for (const subcategory of subcategories) {
        const certifications = await fastify.db('subcategory_judges')
          .where('subcategory_id', subcategory.id)
          .select('is_certified')

        const totalJudges = certifications.length
        const certifiedJudges = certifications.filter(c => c.is_certified).length

        // Check if all scores are signed
        const totalScores = await fastify.db('scores')
          .where('criterion_id', 'in', function() {
            this.select('id').from('criteria').where('subcategory_id', subcategory.id)
          })
          .count('* as count')
          .first()

        const signedScores = await fastify.db('scores')
          .where('criterion_id', 'in', function() {
            this.select('id').from('criteria').where('subcategory_id', subcategory.id)
          })
          .where('is_signed', true)
          .count('* as count')
          .first()

        certificationData.push({
          ...subcategory,
          total_judges,
          certified_judges,
          total_scores: parseInt(totalScores.count),
          signed_scores: parseInt(signedScores.count),
          is_fully_certified: certifiedJudges === totalJudges,
          is_fully_signed: parseInt(signedScores.count) === parseInt(totalScores.count),
          ready_for_final_certification: certifiedJudges === totalJudges && 
                                        parseInt(signedScores.count) === parseInt(totalScores.count)
        })
      }

      return reply.send({
        subcategories: certificationData,
        total_subcategories: certificationData.length,
        ready_for_certification: certificationData.filter(s => s.ready_for_final_certification).length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch final certification status' })
    }
  })

  // Perform final certification
  fastify.post('/final-certification', {
    schema: {
      body: Joi.object({
        subcategory_id: Joi.string().uuid().required(),
        notes: Joi.string().max(1000).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['auditor'])]
  }, async (request, reply) => {
    try {
      const { subcategory_id, notes } = request.body

      // Verify subcategory exists
      const subcategory = await fastify.db('subcategories')
        .where('id', subcategory_id)
        .first()

      if (!subcategory) {
        return reply.status(404).send({ error: 'Subcategory not found' })
      }

      // Check if ready for final certification
      const certifications = await fastify.db('subcategory_judges')
        .where('subcategory_id', subcategory_id)
        .select('is_certified')

      const totalJudges = certifications.length
      const certifiedJudges = certifications.filter(c => c.is_certified).length

      if (certifiedJudges !== totalJudges) {
        return reply.status(400).send({ error: 'Not all judges have certified their scores' })
      }

      // Check if all scores are signed
      const totalScores = await fastify.db('scores')
        .where('criterion_id', 'in', function() {
          this.select('id').from('criteria').where('subcategory_id', subcategory_id)
        })
        .count('* as count')
        .first()

      const signedScores = await fastify.db('scores')
        .where('criterion_id', 'in', function() {
          this.select('id').from('criteria').where('subcategory_id', subcategory_id)
        })
        .where('is_signed', true)
        .count('* as count')
        .first()

      if (parseInt(signedScores.count) !== parseInt(totalScores.count)) {
        return reply.status(400).send({ error: 'Not all scores have been signed' })
      }

      // Create final certification record
      const certification = await fastify.db('final_certifications').insert({
        subcategory_id,
        certified_by: request.user.id,
        notes: notes || '',
        certified_at: new Date()
      }).returning('*')

      return reply.status(201).send({
        message: 'Final certification completed successfully',
        certification: certification[0]
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to perform final certification' })
    }
  })

  // Get auditor summary
  fastify.get('/summary', {
    preHandler: [fastify.authenticate, fastify.requireRole(['auditor'])]
  }, async (request, reply) => {
    try {
      const [
        totalSubcategories,
        certifiedSubcategories,
        totalScores,
        signedScores,
        totalJudges,
        certifiedJudges
      ] = await Promise.all([
        fastify.db('subcategories').count('* as count').first(),
        fastify.db('subcategory_judges').where('is_certified', true).count('* as count').first(),
        fastify.db('scores').count('* as count').first(),
        fastify.db('scores').where('is_signed', true).count('* as count').first(),
        fastify.db('subcategory_judges').count('* as count').first(),
        fastify.db('subcategory_judges').where('is_certified', true).count('* as count').first()
      ])

      return reply.send({
        total_subcategories: parseInt(totalSubcategories.count),
        certified_subcategories: parseInt(certifiedSubcategories.count),
        total_scores: parseInt(totalScores.count),
        signed_scores: parseInt(signedScores.count),
        total_judges: parseInt(totalJudges.count),
        certified_judges: parseInt(certifiedJudges.count),
        certification_percentage: parseInt(totalJudges.count) > 0 
          ? (parseInt(certifiedJudges.count) / parseInt(totalJudges.count)) * 100 
          : 0,
        signing_percentage: parseInt(totalScores.count) > 0 
          ? (parseInt(signedScores.count) / parseInt(totalScores.count)) * 100 
          : 0,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch auditor summary' })
    }
  })
}