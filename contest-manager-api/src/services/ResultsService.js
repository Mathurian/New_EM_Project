import { BaseService } from './BaseService.js'
import { ScoringService } from './ScoringService.js'
import { ContestService } from './ContestService.js'
import { UserService } from './UserService.js'
import PDFDocument from 'pdfkit'
import ExcelJS from 'exceljs'

/**
 * Results and reporting service
 */
export class ResultsService extends BaseService {
  constructor() {
    super('scores')
    this.scoringService = new ScoringService()
    this.contestService = new ContestService()
    this.userService = new UserService()
  }

  /**
   * Get comprehensive contest results
   */
  async getContestResults(contestId, options = {}) {
    const { categoryId, subcategoryId, format } = options

    // Get contest details
    const contest = await this.contestService.getContestWithDetails(contestId)
    if (!contest) {
      throw new Error('Contest not found')
    }

    // Get all scores for the contest
    let scoresQuery = this.db('scores')
      .select(
        'scores.*',
        'contestants.name as contestant_name',
        'contestants.contestant_number',
        'users.first_name as judge_first_name',
        'users.last_name as judge_last_name',
        'criteria.name as criterion_name',
        'criteria.max_score',
        'subcategories.name as subcategory_name',
        'categories.name as category_name'
      )
      .leftJoin('contestants', 'scores.contestant_id', 'contestants.id')
      .leftJoin('users', 'scores.judge_id', 'users.id')
      .leftJoin('criteria', 'scores.criterion_id', 'criteria.id')
      .leftJoin('subcategories', 'scores.subcategory_id', 'subcategories.id')
      .leftJoin('categories', 'subcategories.category_id', 'categories.id')
      .where('categories.contest_id', contestId)

    if (categoryId) {
      scoresQuery = scoresQuery.where('categories.id', categoryId)
    }

    if (subcategoryId) {
      scoresQuery = scoresQuery.where('subcategories.id', subcategoryId)
    }

    const scores = await scoresQuery

    // Process results based on format
    switch (format) {
      case 'summary':
        return this.formatSummaryResults(contest, scores)
      case 'rankings':
        return this.formatRankingsResults(contest, scores)
      default:
        return this.formatDetailedResults(contest, scores)
    }
  }

  /**
   * Get subcategory results
   */
  async getSubcategoryResults(subcategoryId, options = {}) {
    const { format } = options

    const results = await this.scoringService.getSubcategoryResults(subcategoryId)

    if (format === 'detailed') {
      // Get detailed scores
      const scores = await this.scoringService.getSubcategoryScores(subcategoryId, {
        includeComments: true,
        groupBy: 'contestant'
      })

      return {
        results,
        scores
      }
    }

    return results
  }

  /**
   * Get contestant results
   */
  async getContestantResults(contestantId, options = {}) {
    const { contestId, includeScores } = options

    // Get contestant details
    const contestant = await this.db('contestants')
      .where('id', contestantId)
      .first()

    if (!contestant) {
      throw new Error('Contestant not found')
    }

    // Get tabulation
    const tabulation = await this.scoringService.calculateContestantTabulation(
      contestantId, 
      contestId
    )

    const result = {
      contestant,
      tabulation
    }

    if (includeScores) {
      // Get detailed scores
      let scoresQuery = this.db('scores')
        .select(
          'scores.*',
          'criteria.name as criterion_name',
          'criteria.max_score',
          'subcategories.name as subcategory_name',
          'categories.name as category_name',
          'contests.name as contest_name',
          'users.first_name as judge_first_name',
          'users.last_name as judge_last_name'
        )
        .leftJoin('criteria', 'scores.criterion_id', 'criteria.id')
        .leftJoin('subcategories', 'scores.subcategory_id', 'subcategories.id')
        .leftJoin('categories', 'subcategories.category_id', 'categories.id')
        .leftJoin('contests', 'categories.contest_id', 'contests.id')
        .leftJoin('users', 'scores.judge_id', 'users.id')
        .where('scores.contestant_id', contestantId)

      if (contestId) {
        scoresQuery = scoresQuery.where('contests.id', contestId)
      }

      const scores = await scoresQuery
      result.scores = scores
    }

    return result
  }

  /**
   * Get judge results
   */
  async getJudgeResults(judgeId, options = {}) {
    const { contestId, subcategoryId } = options

    // Get judge details
    const judge = await this.userService.findById(judgeId)
    if (!judge) {
      throw new Error('Judge not found')
    }

    // Get tabulation
    const tabulation = await this.scoringService.calculateJudgeTabulation(
      judgeId, 
      contestId
    )

    // Get scoring statistics
    let statsQuery = this.db('scores')
      .select(
        this.db.raw('COUNT(*) as total_scores'),
        this.db.raw('AVG(score) as average_score'),
        this.db.raw('MIN(score) as min_score'),
        this.db.raw('MAX(score) as max_score'),
        this.db.raw('COUNT(DISTINCT subcategory_id) as subcategories_scored'),
        this.db.raw('COUNT(DISTINCT contestant_id) as contestants_scored')
      )
      .where('judge_id', judgeId)

    if (contestId) {
      statsQuery = statsQuery
        .leftJoin('subcategories', 'scores.subcategory_id', 'subcategories.id')
        .leftJoin('categories', 'subcategories.category_id', 'categories.id')
        .where('categories.contest_id', contestId)
    }

    if (subcategoryId) {
      statsQuery = statsQuery.where('subcategory_id', subcategoryId)
    }

    const stats = await statsQuery.first()

    return {
      judge,
      tabulation,
      stats
    }
  }

  /**
   * Generate PDF report
   */
  async generatePDFReport(contestId, options = {}) {
    const { type, categoryId } = options

    const contest = await this.contestService.getContestWithDetails(contestId)
    if (!contest) {
      throw new Error('Contest not found')
    }

    const doc = new PDFDocument()
    const buffers = []
    
    doc.on('data', buffers.push.bind(buffers))
    
    return new Promise((resolve, reject) => {
      doc.on('end', () => {
        const pdfData = Buffer.concat(buffers)
        resolve(pdfData)
      })

      doc.on('error', reject)

      // Add content based on type
      this.addPDFContent(doc, contest, type, categoryId)
      doc.end()
    })
  }

  /**
   * Generate Excel report
   */
  async generateExcelReport(contestId, options = {}) {
    const { type, categoryId } = options

    const contest = await this.contestService.getContestWithDetails(contestId)
    if (!contest) {
      throw new Error('Contest not found')
    }

    const workbook = new ExcelJS.Workbook()
    const worksheet = workbook.addWorksheet('Contest Results')

    // Add content based on type
    await this.addExcelContent(worksheet, contest, type, categoryId)

    const buffer = await workbook.xlsx.writeBuffer()
    return buffer
  }

  /**
   * Get leaderboard
   */
  async getLeaderboard(options = {}) {
    const { contestId, categoryId, limit } = options

    let query = this.db('scores')
      .select(
        'contestants.id',
        'contestants.name',
        'contestants.contestant_number',
        this.db.raw('SUM(scores.score) as total_score'),
        this.db.raw('SUM(criteria.max_score) as max_possible_score'),
        this.db.raw('COUNT(DISTINCT scores.subcategory_id) as subcategories_count')
      )
      .leftJoin('contestants', 'scores.contestant_id', 'contestants.id')
      .leftJoin('criteria', 'scores.criterion_id', 'criteria.id')
      .leftJoin('subcategories', 'scores.subcategory_id', 'subcategories.id')
      .leftJoin('categories', 'subcategories.category_id', 'categories.id')
      .where('contestants.is_active', true)

    if (contestId) {
      query = query.where('categories.contest_id', contestId)
    }

    if (categoryId) {
      query = query.where('categories.id', categoryId)
    }

    const results = await query
      .groupBy('contestants.id', 'contestants.name', 'contestants.contestant_number')
      .orderBy('total_score', 'desc')
      .limit(limit)

    return results.map((result, index) => ({
      ...result,
      rank: index + 1,
      percentage: result.max_possible_score > 0 
        ? (result.total_score / result.max_possible_score * 100).toFixed(1)
        : 0
    }))
  }

  /**
   * Get scoring statistics
   */
  async getScoringStats(options = {}) {
    const { contestId, subcategoryId, judgeId } = options

    let query = this.db('scores')
      .select(
        this.db.raw('COUNT(*) as total_scores'),
        this.db.raw('AVG(score) as average_score'),
        this.db.raw('MIN(score) as min_score'),
        this.db.raw('MAX(score) as max_score'),
        this.db.raw('COUNT(DISTINCT contestant_id) as contestants_scored'),
        this.db.raw('COUNT(DISTINCT judge_id) as judges_scoring'),
        this.db.raw('COUNT(DISTINCT subcategory_id) as subcategories_scored')
      )

    if (contestId) {
      query = query
        .leftJoin('subcategories', 'scores.subcategory_id', 'subcategories.id')
        .leftJoin('categories', 'subcategories.category_id', 'categories.id')
        .where('categories.contest_id', contestId)
    }

    if (subcategoryId) {
      query = query.where('subcategory_id', subcategoryId)
    }

    if (judgeId) {
      query = query.where('judge_id', judgeId)
    }

    return await query.first()
  }

  /**
   * Export results data
   */
  async exportResults(contestId, options = {}) {
    const { format, includeScores } = options

    const results = await this.getContestResults(contestId, {
      format: 'detailed'
    })

    if (format === 'csv') {
      return this.convertToCSV(results)
    }

    return results
  }

  /**
   * Format detailed results
   */
  formatDetailedResults(contest, scores) {
    const categories = {}
    
    scores.forEach(score => {
      const categoryName = score.category_name
      const subcategoryName = score.subcategory_name
      
      if (!categories[categoryName]) {
        categories[categoryName] = {
          name: categoryName,
          subcategories: {}
        }
      }
      
      if (!categories[categoryName].subcategories[subcategoryName]) {
        categories[categoryName].subcategories[subcategoryName] = {
          name: subcategoryName,
          contestants: {}
        }
      }
      
      const contestantName = score.contestant_name
      if (!categories[categoryName].subcategories[subcategoryName].contestants[contestantName]) {
        categories[categoryName].subcategories[subcategoryName].contestants[contestantName] = {
          name: contestantName,
          contestant_number: score.contestant_number,
          scores: []
        }
      }
      
      categories[categoryName].subcategories[subcategoryName].contestants[contestantName].scores.push({
        criterion: score.criterion_name,
        score: score.score,
        max_score: score.max_score,
        judge: `${score.judge_first_name} ${score.judge_last_name}`,
        comments: score.comments
      })
    })

    return {
      contest,
      categories: Object.values(categories).map(category => ({
        ...category,
        subcategories: Object.values(category.subcategories)
      }))
    }
  }

  /**
   * Format summary results
   */
  formatSummaryResults(contest, scores) {
    const summary = {
      contest,
      total_scores: scores.length,
      contestants: {},
      judges: {},
      subcategories: {}
    }

    scores.forEach(score => {
      // Contestant summary
      if (!summary.contestants[score.contestant_id]) {
        summary.contestants[score.contestant_id] = {
          name: score.contestant_name,
          total_score: 0,
          max_possible: 0,
          score_count: 0
        }
      }
      summary.contestants[score.contestant_id].total_score += score.score
      summary.contestants[score.contestant_id].max_possible += score.max_score
      summary.contestants[score.contestant_id].score_count++

      // Judge summary
      if (!summary.judges[score.judge_id]) {
        summary.judges[score.judge_id] = {
          name: `${score.judge_first_name} ${score.judge_last_name}`,
          score_count: 0
        }
      }
      summary.judges[score.judge_id].score_count++

      // Subcategory summary
      if (!summary.subcategories[score.subcategory_id]) {
        summary.subcategories[score.subcategory_id] = {
          name: score.subcategory_name,
          score_count: 0
        }
      }
      summary.subcategories[score.subcategory_id].score_count++
    })

    return summary
  }

  /**
   * Format rankings results
   */
  formatRankingsResults(contest, scores) {
    const contestantTotals = {}

    scores.forEach(score => {
      if (!contestantTotals[score.contestant_id]) {
        contestantTotals[score.contestant_id] = {
          contestant_id: score.contestant_id,
          name: score.contestant_name,
          contestant_number: score.contestant_number,
          total_score: 0,
          max_possible: 0
        }
      }
      contestantTotals[score.contestant_id].total_score += score.score
      contestantTotals[score.contestant_id].max_possible += score.max_score
    })

    const rankings = Object.values(contestantTotals)
      .map(contestant => ({
        ...contestant,
        percentage: contestant.max_possible > 0 
          ? (contestant.total_score / contestant.max_possible * 100).toFixed(1)
          : 0
      }))
      .sort((a, b) => b.total_score - a.total_score)
      .map((contestant, index) => ({
        ...contestant,
        rank: index + 1
      }))

    return {
      contest,
      rankings
    }
  }

  /**
   * Add PDF content
   */
  addPDFContent(doc, contest, type, categoryId) {
    doc.fontSize(20).text(contest.name, 50, 50)
    doc.fontSize(12).text(`Start Date: ${contest.start_date}`, 50, 80)
    doc.text(`End Date: ${contest.end_date}`, 50, 95)
    
    // Add content based on type
    if (type === 'full') {
      // Add full results
      doc.text('Full Results', 50, 120)
    } else if (type === 'summary') {
      // Add summary
      doc.text('Summary', 50, 120)
    }
  }

  /**
   * Add Excel content
   */
  async addExcelContent(worksheet, contest, type, categoryId) {
    worksheet.addRow(['Contest Results'])
    worksheet.addRow([contest.name])
    worksheet.addRow([`Start Date: ${contest.start_date}`])
    worksheet.addRow([`End Date: ${contest.end_date}`])
    worksheet.addRow([])

    // Add content based on type
    if (type === 'full') {
      // Add full results
      worksheet.addRow(['Full Results'])
    } else if (type === 'summary') {
      // Add summary
      worksheet.addRow(['Summary'])
    }
  }

  /**
   * Convert results to CSV
   */
  convertToCSV(results) {
    const headers = ['Contestant', 'Category', 'Subcategory', 'Criterion', 'Score', 'Max Score', 'Judge']
    const rows = [headers.join(',')]

    results.categories.forEach(category => {
      category.subcategories.forEach(subcategory => {
        Object.values(subcategory.contestants).forEach(contestant => {
          contestant.scores.forEach(score => {
            rows.push([
              contestant.name,
              category.name,
              subcategory.name,
              score.criterion,
              score.score,
              score.max_score,
              score.judge
            ].join(','))
          })
        })
      })
    })

    return rows.join('\n')
  }
}