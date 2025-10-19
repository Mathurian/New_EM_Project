import { BaseService } from './BaseService.js'

/**
 * Contest management service
 */
export class ContestService extends BaseService {
  constructor() {
    super('contests')
  }

  /**
   * Get contest with all related data
   */
  async getContestWithDetails(contestId) {
    const contest = await this.db('contests')
      .where('id', contestId)
      .first()

    if (!contest) {
      return null
    }

    // Get categories with subcategories and criteria
    const categories = await this.db('categories')
      .where('contest_id', contestId)
      .where('is_active', true)
      .orderBy('order_index', 'asc')
      .orderBy('name', 'asc')

    for (const category of categories) {
      // Get subcategories
      category.subcategories = await this.db('subcategories')
        .where('category_id', category.id)
        .where('is_active', true)
        .orderBy('order_index', 'asc')
        .orderBy('name', 'asc')

      // Get criteria for each subcategory
      for (const subcategory of category.subcategories) {
        subcategory.criteria = await this.db('criteria')
          .where('subcategory_id', subcategory.id)
          .where('is_active', true)
          .orderBy('order_index', 'asc')
          .orderBy('name', 'asc')
      }
    }

    contest.categories = categories
    return contest
  }

  /**
   * Get contest statistics
   */
  async getContestStats(contestId) {
    const stats = await this.db('contests')
      .leftJoin('categories', 'contests.id', 'categories.contest_id')
      .leftJoin('subcategories', 'categories.id', 'subcategories.category_id')
      .leftJoin('subcategory_contestants', 'subcategories.id', 'subcategory_contestants.subcategory_id')
      .leftJoin('subcategory_judges', 'subcategories.id', 'subcategory_judges.subcategory_id')
      .leftJoin('scores', 'subcategories.id', 'scores.subcategory_id')
      .where('contests.id', contestId)
      .select(
        this.db.raw('COUNT(DISTINCT categories.id) as category_count'),
        this.db.raw('COUNT(DISTINCT subcategories.id) as subcategory_count'),
        this.db.raw('COUNT(DISTINCT subcategory_contestants.contestant_id) as contestant_count'),
        this.db.raw('COUNT(DISTINCT subcategory_judges.judge_id) as judge_count'),
        this.db.raw('COUNT(DISTINCT scores.id) as score_count')
      )
      .first()

    return stats
  }

  /**
   * Archive contest and all related data
   */
  async archiveContest(contestId, userId) {
    return await this.transaction(async (trx) => {
      // Update contest status
      await trx('contests')
        .where('id', contestId)
        .update({ 
          status: 'archived',
          updated_at: new Date()
        })

      // Archive related data
      await trx('categories')
        .where('contest_id', contestId)
        .update({ is_active: false })

      await trx('subcategories')
        .whereIn('category_id', 
          trx('categories').select('id').where('contest_id', contestId)
        )
        .update({ is_active: false })

      await trx('criteria')
        .whereIn('subcategory_id',
          trx('subcategories')
            .select('id')
            .whereIn('category_id',
              trx('categories').select('id').where('contest_id', contestId)
            )
        )
        .update({ is_active: false })

      // Log audit trail
      await this.audit.log({
        userId,
        action: 'contest_archived',
        entityType: 'contest',
        entityId: contestId
      })

      return true
    })
  }

  /**
   * Reactivate archived contest
   */
  async reactivateContest(contestId, userId) {
    return await this.transaction(async (trx) => {
      // Update contest status
      await trx('contests')
        .where('id', contestId)
        .update({ 
          status: 'active',
          updated_at: new Date()
        })

      // Reactivate related data
      await trx('categories')
        .where('contest_id', contestId)
        .update({ is_active: true })

      await trx('subcategories')
        .whereIn('category_id', 
          trx('categories').select('id').where('contest_id', contestId)
        )
        .update({ is_active: true })

      await trx('criteria')
        .whereIn('subcategory_id',
          trx('subcategories')
            .select('id')
            .whereIn('category_id',
              trx('categories').select('id').where('contest_id', contestId)
            )
        )
        .update({ is_active: true })

      // Log audit trail
      await this.audit.log({
        userId,
        action: 'contest_reactivated',
        entityType: 'contest',
        entityId: contestId
      })

      return true
    })
  }

  /**
   * Apply include relations for contests
   */
  applyInclude(query, relation) {
    switch (relation) {
      case 'categories':
        return query.leftJoin('categories', 'contests.id', 'categories.contest_id')
      case 'createdBy':
        return query.leftJoin('users', 'contests.created_by', 'users.id')
      default:
        return query
    }
  }

  /**
   * Validate contest data
   */
  validate(data, isUpdate = false) {
    const errors = []

    if (!isUpdate || data.name !== undefined) {
      if (!data.name || data.name.trim().length === 0) {
        errors.push('Contest name is required')
      }
    }

    if (!isUpdate || data.start_date !== undefined) {
      if (!data.start_date) {
        errors.push('Start date is required')
      }
    }

    if (!isUpdate || data.end_date !== undefined) {
      if (!data.end_date) {
        errors.push('End date is required')
      }
    }

    if (data.start_date && data.end_date) {
      const startDate = new Date(data.start_date)
      const endDate = new Date(data.end_date)
      
      if (startDate >= endDate) {
        errors.push('End date must be after start date')
      }
    }

    return {
      isValid: errors.length === 0,
      errors
    }
  }
}