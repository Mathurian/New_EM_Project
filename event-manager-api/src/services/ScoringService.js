import { BaseService } from './BaseService.js'

export class ScoringService extends BaseService {
  constructor() {
    super('scores')
  }

  /**
   * Submit score for a criterion
   */
  async submitScore(scoreData, userId) {
    const {
      criterion_id,
      contestant_id,
      score,
      comments = null
    } = scoreData

    // Validate score is within range
    const criterion = await this.db('criteria')
      .where('id', criterion_id)
      .first()

    if (!criterion) {
      throw new Error('Criterion not found')
    }

    if (score < 0 || score > criterion.max_score) {
      throw new Error(`Score must be between 0 and ${criterion.max_score}`)
    }

    // Check if score already exists
    const existingScore = await this.db(this.tableName)
      .where({
        criterion_id,
        judge_id: userId,
        contestant_id
      })
      .first()

    if (existingScore) {
      // Update existing score
      const [updatedScore] = await this.db(this.tableName)
        .where('id', existingScore.id)
        .update({
          score,
          comments,
          is_signed: false,
          signed_at: null,
          updated_at: new Date()
        })
        .returning('*')

      await this.logAction('updated', updatedScore.id, { score, comments }, existingScore, userId)
      return updatedScore
    } else {
      // Create new score
      const [newScore] = await this.db(this.tableName)
        .insert({
          criterion_id,
          judge_id: userId,
          contestant_id,
          score,
          comments,
          is_signed: false
        })
        .returning('*')

      await this.logAction('created', newScore.id, { score, comments }, null, userId)
      return newScore
    }
  }

  /**
   * Sign score (finalize it)
   */
  async signScore(scoreId, userId) {
    const score = await this.getById(scoreId)
    if (!score) {
      throw new Error('Score not found')
    }

    if (score.judge_id !== userId) {
      throw new Error('You can only sign your own scores')
    }

    if (score.is_signed) {
      throw new Error('Score is already signed')
    }

    const [signedScore] = await this.db(this.tableName)
      .where('id', scoreId)
      .update({
        is_signed: true,
        signed_at: new Date(),
        updated_at: new Date()
      })
      .returning('*')

    await this.logAction('signed', scoreId, { is_signed: true, signed_at: new Date() }, score, userId)
    return signedScore
  }

  /**
   * Unsign score (make it editable again)
   */
  async unsignScore(scoreId, userId) {
    const score = await this.getById(scoreId)
    if (!score) {
      throw new Error('Score not found')
    }

    if (score.judge_id !== userId) {
      throw new Error('You can only unsign your own scores')
    }

    const [unsignedScore] = await this.db(this.tableName)
      .where('id', scoreId)
      .update({
        is_signed: false,
        signed_at: null,
        updated_at: new Date()
      })
      .returning('*')

    await this.logAction('unsigned', scoreId, { is_signed: false, signed_at: null }, score, userId)
    return unsignedScore
  }

  /**
   * Get scores for a subcategory
   */
  async getScoresBySubcategory(subcategoryId, options = {}) {
    const {
      groupBy = 'contestant', // 'contestant' or 'judge'
      includeUnsigned = true
    } = options

    let query = this.db(this.tableName)
      .join('criteria', 'scores.criterion_id', 'criteria.id')
      .join('contestants', 'scores.contestant_id', 'contestants.id')
      .join('users', 'scores.judge_id', 'users.id')
      .where('criteria.subcategory_id', subcategoryId)
      .select(
        'scores.*',
        'criteria.name as criterion_name',
        'criteria.max_score',
        'contestants.name as contestant_name',
        'contestants.contestant_number',
        'users.first_name as judge_first_name',
        'users.last_name as judge_last_name'
      )

    if (!includeUnsigned) {
      query = query.where('scores.is_signed', true)
    }

    const scores = await query

    if (groupBy === 'contestant') {
      return this.groupScoresByContestant(scores)
    } else {
      return this.groupScoresByJudge(scores)
    }
  }

  /**
   * Get scores for a contestant in a subcategory
   */
  async getContestantScores(contestantId, subcategoryId) {
    return this.db(this.tableName)
      .join('criteria', 'scores.criterion_id', 'criteria.id')
      .join('users', 'scores.judge_id', 'users.id')
      .where('scores.contestant_id', contestantId)
      .where('criteria.subcategory_id', subcategoryId)
      .select(
        'scores.*',
        'criteria.name as criterion_name',
        'criteria.max_score',
        'users.first_name as judge_first_name',
        'users.last_name as judge_last_name'
      )
  }

  /**
   * Get scores for a judge in a subcategory
   */
  async getJudgeScores(judgeId, subcategoryId) {
    return this.db(this.tableName)
      .join('criteria', 'scores.criterion_id', 'criteria.id')
      .join('contestants', 'scores.contestant_id', 'contestants.id')
      .where('scores.judge_id', judgeId)
      .where('criteria.subcategory_id', subcategoryId)
      .select(
        'scores.*',
        'criteria.name as criterion_name',
        'criteria.max_score',
        'contestants.name as contestant_name',
        'contestants.contestant_number'
      )
  }

  /**
   * Calculate total score for a contestant in a subcategory
   */
  async calculateContestantTotal(contestantId, subcategoryId) {
    const scores = await this.db(this.tableName)
      .join('criteria', 'scores.criterion_id', 'criteria.id')
      .where('scores.contestant_id', contestantId)
      .where('criteria.subcategory_id', subcategoryId)
      .where('scores.is_signed', true)
      .select('scores.score', 'criteria.max_score')

    const totalScore = scores.reduce((sum, score) => sum + parseFloat(score.score), 0)
    const maxPossibleScore = scores.reduce((sum, score) => sum + score.max_score, 0)

    return {
      total_score: totalScore,
      max_possible_score: maxPossibleScore,
      percentage: maxPossibleScore > 0 ? (totalScore / maxPossibleScore) * 100 : 0,
      score_count: scores.length
    }
  }

  /**
   * Get scoring statistics for a subcategory
   */
  async getScoringStats(subcategoryId) {
    const [
      totalScores,
      signedScores,
      unsignedScores,
      contestantCount,
      judgeCount
    ] = await Promise.all([
      this.db(this.tableName)
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .where('criteria.subcategory_id', subcategoryId)
        .count('* as count')
        .first(),
      this.db(this.tableName)
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .where('criteria.subcategory_id', subcategoryId)
        .where('scores.is_signed', true)
        .count('* as count')
        .first(),
      this.db(this.tableName)
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .where('criteria.subcategory_id', subcategoryId)
        .where('scores.is_signed', false)
        .count('* as count')
        .first(),
      this.db('subcategory_contestants')
        .where('subcategory_id', subcategoryId)
        .count('* as count')
        .first(),
      this.db('subcategory_judges')
        .where('subcategory_id', subcategoryId)
        .count('* as count')
        .first()
    ])

    return {
      subcategory_id: subcategoryId,
      total_scores: parseInt(totalScores.count),
      signed_scores: parseInt(signedScores.count),
      unsigned_scores: parseInt(unsignedScores.count),
      contestant_count: parseInt(contestantCount.count),
      judge_count: parseInt(judgeCount.count),
      completion_percentage: parseInt(totalScores.count) > 0 
        ? (parseInt(signedScores.count) / parseInt(totalScores.count)) * 100 
        : 0
    }
  }

  /**
   * Group scores by contestant
   */
  groupScoresByContestant(scores) {
    const grouped = {}
    
    scores.forEach(score => {
      const key = `${score.contestant_id}_${score.contestant_name}`
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
      const key = `${score.judge_id}_${score.judge_first_name}_${score.judge_last_name}`
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
}