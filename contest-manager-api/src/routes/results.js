import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { ResultsService } from '../services/ResultsService.js'

/**
 * Results and reporting routes
 */
export const resultsRoutes = async (fastify) => {
  const resultsService = new ResultsService()

  // Get contest results
  fastify.get('/contest/:contestId', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { contestId } = request.params
      const { 
        category_id = null,
        subcategory_id = null,
        format = 'detailed' // detailed, summary, rankings
      } = request.query

      const results = await resultsService.getContestResults(contestId, {
        categoryId: category_id,
        subcategoryId: subcategory_id,
        format
      })

      return results
    } catch (error) {
      fastify.log.error('Get contest results error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get subcategory results
  fastify.get('/subcategory/:subcategoryId', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { subcategoryId } = request.params
      const { 
        format = 'rankings' // rankings, detailed, scores
      } = request.query

      const results = await resultsService.getSubcategoryResults(subcategoryId, {
        format
      })

      return results
    } catch (error) {
      fastify.log.error('Get subcategory results error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get contestant detailed results
  fastify.get('/contestant/:contestantId', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { contestantId } = request.params
      const { 
        contest_id = null,
        include_scores = false
      } = request.query

      const results = await resultsService.getContestantResults(contestantId, {
        contestId: contest_id,
        includeScores: include_scores === 'true'
      })

      return results
    } catch (error) {
      fastify.log.error('Get contestant results error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get judge scoring summary
  fastify.get('/judge/:judgeId', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { judgeId } = request.params
      const { 
        contest_id = null,
        subcategory_id = null
      } = request.query

      const results = await resultsService.getJudgeResults(judgeId, {
        contestId: contest_id,
        subcategoryId: subcategory_id
      })

      return results
    } catch (error) {
      fastify.log.error('Get judge results error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Generate PDF report
  fastify.get('/contest/:contestId/report/pdf', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board', 'tally_master'])]
  }, async (request, reply) => {
    try {
      const { contestId } = request.params
      const { 
        type = 'full', // full, summary, categories
        category_id = null
      } = request.query

      const pdfBuffer = await resultsService.generatePDFReport(contestId, {
        type,
        categoryId: category_id
      })

      reply.type('application/pdf')
      reply.header('Content-Disposition', `attachment; filename="contest-${contestId}-report.pdf"`)
      
      return pdfBuffer
    } catch (error) {
      fastify.log.error('Generate PDF report error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Generate Excel report
  fastify.get('/contest/:contestId/report/excel', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board', 'tally_master'])]
  }, async (request, reply) => {
    try {
      const { contestId } = request.params
      const { 
        type = 'full', // full, summary, scores
        category_id = null
      } = request.query

      const excelBuffer = await resultsService.generateExcelReport(contestId, {
        type,
        categoryId: category_id
      })

      reply.type('application/vnd.openxmlformats-officedocument.spreadsheetml.sheet')
      reply.header('Content-Disposition', `attachment; filename="contest-${contestId}-report.xlsx"`)
      
      return excelBuffer
    } catch (error) {
      fastify.log.error('Generate Excel report error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get leaderboard
  fastify.get('/leaderboard', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { 
        contest_id = null,
        category_id = null,
        limit = 10
      } = request.query

      const leaderboard = await resultsService.getLeaderboard({
        contestId: contest_id,
        categoryId: category_id,
        limit: parseInt(limit)
      })

      return leaderboard
    } catch (error) {
      fastify.log.error('Get leaderboard error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get scoring statistics
  fastify.get('/stats/scoring', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board', 'tally_master'])]
  }, async (request, reply) => {
    try {
      const { 
        contest_id = null,
        subcategory_id = null,
        judge_id = null
      } = request.query

      const stats = await resultsService.getScoringStats({
        contestId: contest_id,
        subcategoryId: subcategory_id,
        judgeId: judge_id
      })

      return stats
    } catch (error) {
      fastify.log.error('Get scoring stats error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Export results data
  fastify.get('/export', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const { 
        contest_id,
        format = 'json', // json, csv
        include_scores = false
      } = request.query

      if (!contest_id) {
        return reply.status(400).send({
          error: 'contest_id is required'
        })
      }

      const exportData = await resultsService.exportResults(contest_id, {
        format,
        includeScores: include_scores === 'true'
      })

      if (format === 'csv') {
        reply.type('text/csv')
        reply.header('Content-Disposition', `attachment; filename="contest-${contest_id}-results.csv"`)
        return exportData
      }

      return exportData
    } catch (error) {
      fastify.log.error('Export results error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })
}