import { BaseService } from './BaseService.js'

/**
 * Scoring management service with complex business logic
 */
export class ScoringService extends BaseService {
  constructor() {
    super('scores')
  }

  /**
   * Submit a score for a contestant
   */
  async submitScore(scoreData, userId) {
    const { subcategoryId, contestantId, criterionId, score, comments } = scoreData

    return await this.transaction(async (trx) => {
      // Validate score
      const criterion = await trx('criteria')
        .where('id', criterionId)
        .where('is_active', true)
        .first()

      if (!criterion) {
        throw new Error('Criterion not found or inactive')
      }

      if (score < 0 || score > criterion.max_score) {
        throw new Error(`Score must be between 0 and ${criterion.max_score}`)
      }

      // Check if judge is assigned to this subcategory
      const assignment = await trx('subcategory_judges')
        .where('subcategory_id', subcategoryId)
        .where('judge_id', userId)
        .where('is_certified', true)
        .first()

      if (!assignment) {
        throw new Error('Judge not assigned or not certified for this subcategory')
      }

      // Check if contestant is assigned to this subcategory
      const contestantAssignment = await trx('subcategory_contestants')
        .where('subcategory_id', subcategoryId)
        .where('contestant_id', contestantId)
        .first()

      if (!contestantAssignment) {
        throw new Error('Contestant not assigned to this subcategory')
      }

      // Upsert score
      const existingScore = await trx('scores')
        .where('subcategory_id', subcategoryId)
        .where('contestant_id', contestantId)
        .where('judge_id', userId)
        .where('criterion_id', criterionId)
        .first()

      let result
      if (existingScore) {
        // Update existing score
        [result] = await trx('scores')
          .where('id', existingScore.id)
          .update({
            score,
            comments,
            updated_at: new Date()
          })
          .returning('*')
      } else {
        // Create new score
        [result] = await trx('scores')
          .insert({
            subcategory_id: subcategoryId,
            contestant_id: contestantId,
            judge_id: userId,
            criterion_id: criterionId,
            score,
            comments,
            created_at: new Date(),
            updated_at: new Date()
          })
          .returning('*')
      }

      // Log audit trail
      await this.audit.log({
        userId,
        action: 'score_submitted',
        entityType: 'score',
        entityId: result.id,
        newValues: result
      })

      return result
    })
  }

  /**
   * Get scores for a subcategory
   */
  async getSubcategoryScores(subcategoryId, options = {}) {
    const { includeComments = false, groupBy = 'contestant' } = options

    let query = this.db('scores')
      .select(
        'scores.*',
        'contestants.name as contestant_name',
        'contestants.contestant_number',
        'users.first_name as judge_first_name',
        'users.last_name as judge_last_name',
        'criteria.name as criterion_name',
        'criteria.max_score'
      )
      .leftJoin('contestants', 'scores.contestant_id', 'contestants.id')
      .leftJoin('users', 'scores.judge_id', 'users.id')
      .leftJoin('criteria', 'scores.criterion_id', 'criteria.id')
      .where('scores.subcategory_id', subcategoryId)

    if (!includeComments) {
      query = query.select(this.db.raw('scores.* EXCEPT (comments)'))
    }

    const scores = await query

    if (groupBy === 'contestant') {
      return this.groupScoresByContestant(scores)
    } else if (groupBy === 'judge') {
      return this.groupScoresByJudge(scores)
    }

    return scores
  }

  /**
   * Calculate score tabulation for a contestant
   */
  async calculateContestantTabulation(contestantId, contestId = null) {
    let query = this.db('scores')
      .select(
        'scores.score',
        'criteria.max_score',
        'contests.name as contest_name',
        'categories.name as category_name',
        'subcategories.name as subcategory_name'
      )
      .leftJoin('criteria', 'scores.criterion_id', 'criteria.id')
      .leftJoin('subcategories', 'scores.subcategory_id', 'subcategories.id')
      .leftJoin('categories', 'subcategories.category_id', 'categories.id')
      .leftJoin('contests', 'categories.contest_id', 'contests.id')
      .where('scores.contestant_id', contestantId)

    if (contestId) {
      query = query.where('contests.id', contestId)
    }

    const scores = await query

    return this.calculateTabulation(scores)
  }

  /**
   * Calculate score tabulation for a judge
   */
  async calculateJudgeTabulation(judgeId, contestId = null) {
    let query = this.db('scores')
      .select(
        'scores.score',
        'criteria.max_score',
        'contests.name as contest_name',
        'categories.name as category_name',
        'subcategories.name as subcategory_name'
      )
      .leftJoin('criteria', 'scores.criterion_id', 'criteria.id')
      .leftJoin('subcategories', 'scores.subcategory_id', 'subcategories.id')
      .leftJoin('categories', 'subcategories.category_id', 'categories.id')
      .leftJoin('contests', 'categories.contest_id', 'contests.id')
      .where('scores.judge_id', judgeId)

    if (contestId) {
      query = query.where('contests.id', contestId)
    }

    const scores = await query

    return this.calculateTabulation(scores)
  }

  /**
   * Get final results for a subcategory
   */
  async getSubcategoryResults(subcategoryId) {
    const scores = await this.db('scores')
      .select(
        'scores.contestant_id',
        'contestants.name as contestant_name',
        'contestants.contestant_number',
        this.db.raw('SUM(scores.score) as total_score'),
        this.db.raw('SUM(criteria.max_score) as max_possible_score'),
        this.db.raw('COUNT(DISTINCT scores.judge_id) as judge_count'),
        this.db.raw('COUNT(scores.id) as score_count')
      )
      .leftJoin('contestants', 'scores.contestant_id', 'contestants.id')
      .leftJoin('criteria', 'scores.criterion_id', 'criteria.id')
      .where('scores.subcategory_id', subcategoryId)
      .groupBy('scores.contestant_id', 'contestants.name', 'contestants.contestant_number')
      .orderBy('total_score', 'desc')
      .orderBy('contestant_name', 'asc')

    return scores
  }

  /**
   * Group scores by contestant
   */
  groupScoresByContestant(scores) {
    const grouped = {}
    
    scores.forEach(score => {
      const key = score.contestant_id
      if (!grouped[key]) {
        grouped[key] = {
          contestant_id: score.contestant_id,
          contestant_name: score.contestant_name,
          contestant_number: score.contestant_number,
          scores: []
        }
      }
      grouped[key].scores.push(score)
    })

    return Object.values(grouped)
  }

  /**
   * Group scores by judge
   */
  groupScoresByJudge(scores) {
    const grouped = {}
    
    scores.forEach(score => {
      const key = score.judge_id
      if (!grouped[key]) {
        grouped[key] = {
          judge_id: score.judge_id,
          judge_name: `${score.judge_first_name} ${score.judge_last_name}`,
          scores: []
        }
      }
      grouped[key].scores.push(score)
    })

    return Object.values(grouped)
  }

  /**
   * Calculate tabulation from scores array
   */
  calculateTabulation(scores) {
    const tabulation = {
      total_current: 0,
      total_possible: 0,
      by_contest: {},
      by_category: {},
      by_subcategory: {}
    }

    scores.forEach(score => {
      const current = parseFloat(score.score) || 0
      const possible = parseFloat(score.max_score) || 0

      // Overall totals
      tabulation.total_current += current
      tabulation.total_possible += possible

      // By contest
      const contestName = score.contest_name || 'Unknown Contest'
      if (!tabulation.by_contest[contestName]) {
        tabulation.by_contest[contestName] = { current: 0, possible: 0 }
      }
      tabulation.by_contest[contestName].current += current
      tabulation.by_contest[contestName].possible += possible

      // By category
      const categoryName = score.category_name || 'Unknown Category'
      if (!tabulation.by_category[categoryName]) {
        tabulation.by_category[categoryName] = { current: 0, possible: 0 }
      }
      tabulation.by_category[categoryName].current += current
      tabulation.by_category[categoryName].possible += possible

      // By subcategory
      const subcategoryName = score.subcategory_name || 'Unknown Subcategory'
      if (!tabulation.by_subcategory[subcategoryName]) {
        tabulation.by_subcategory[subcategoryName] = { current: 0, possible: 0 }
      }
      tabulation.by_subcategory[subcategoryName].current += current
      tabulation.by_subcategory[subcategoryName].possible += possible
    })

    return tabulation
  }

  /**
   * Format tabulation for display
   */
  formatTabulation(tabulation, level = 'overall') {
    if (level === 'overall') {
      const percentage = tabulation.total_possible > 0 
        ? (tabulation.total_current / tabulation.total_possible * 100).toFixed(1)
        : '0.0'
      return `${tabulation.total_current}/${tabulation.total_possible} (${percentage}%)`
    }

    const data = tabulation[`by_${level}`] || {}
    const results = Object.entries(data).map(([name, scores]) => {
      const percentage = scores.possible > 0 
        ? (scores.current / scores.possible * 100).toFixed(1)
        : '0.0'
      return `${name}: ${scores.current}/${scores.possible} (${percentage}%)`
    })

    return results.join(', ')
  }
}