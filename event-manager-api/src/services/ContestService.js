import { BaseService } from './BaseService.js'

export class ContestService extends BaseService {
  constructor() {
    super('contests')
  }

  /**
   * Get all contests for an event
   */
  async getContestsByEvent(eventId, options = {}) {
    const {
      page = 1,
      limit = 10,
      search = '',
      status = 'all',
      sortBy = 'created_at',
      sortOrder = 'desc'
    } = options

    let query = this.db(this.tableName)
      .where('event_id', eventId)
      .select('*')
      .orderBy(sortBy, sortOrder)

    // Apply search filter
    if (search) {
      query = query.where(function() {
        this.where('name', 'ilike', `%${search}%`)
          .orWhere('description', 'ilike', `%${search}%`)
      })
    }

    // Apply status filter
    if (status !== 'all') {
      query = query.where('status', status)
    }

    // Get total count for pagination
    const totalQuery = query.clone()
    const [{ count }] = await totalQuery.count('* as count')
    const total = parseInt(count)

    // Apply pagination
    const offset = (page - 1) * limit
    const contests = await query.offset(offset).limit(limit)

    return {
      data: contests,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    }
  }

  /**
   * Get contest with categories and subcategories
   */
  async getContestWithDetails(contestId) {
    const contest = await this.getById(contestId)
    if (!contest) return null

    // Get event details
    const event = await this.db('events')
      .where('id', contest.event_id)
      .first()

    // Get categories for this contest
    const categories = await this.db('categories')
      .where('contest_id', contestId)
      .orderBy('order_index', 'asc')

    // Get subcategories for each category
    for (const category of categories) {
      const subcategories = await this.db('subcategories')
        .where('category_id', category.id)
        .orderBy('order_index', 'asc')

      // Get contestants for each subcategory
      for (const subcategory of subcategories) {
        const contestants = await this.db('subcategory_contestants')
          .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
          .where('subcategory_contestants.subcategory_id', subcategory.id)
          .select('contestants.*')

        subcategory.contestants = contestants
      }

      category.subcategories = subcategories
    }

    contest.event = event
    contest.categories = categories
    return contest
  }

  /**
   * Create contest with categories
   */
  async createContestWithCategories(contestData, categoriesData = [], userId) {
    const trx = await this.db.transaction()

    try {
      // Create contest
      const contest = await trx(this.tableName)
        .insert({
          ...contestData,
          created_by: userId
        })
        .returning('*')

      // Create categories if provided
      if (categoriesData.length > 0) {
        const categories = await trx('categories')
          .insert(
            categoriesData.map(category => ({
              ...category,
              contest_id: contest[0].id
            }))
          )
          .returning('*')

        contest[0].categories = categories
      }

      await trx.commit()
      return contest[0]
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }

  /**
   * Get contest statistics
   */
  async getContestStats(contestId) {
    const contest = await this.getById(contestId)
    if (!contest) return null

    const [
      categoryCount,
      subcategoryCount,
      contestantCount,
      scoreCount
    ] = await Promise.all([
      this.db('categories').where('contest_id', contestId).count('* as count').first(),
      this.db('subcategories')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .where('categories.contest_id', contestId)
        .count('* as count')
        .first(),
      this.db('subcategory_contestants')
        .join('subcategories', 'subcategory_contestants.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .where('categories.contest_id', contestId)
        .count('* as count')
        .first(),
      this.db('scores')
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .join('subcategories', 'criteria.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .where('categories.contest_id', contestId)
        .count('* as count')
        .first()
    ])

    return {
      contest_id: contestId,
      category_count: parseInt(categoryCount.count),
      subcategory_count: parseInt(subcategoryCount.count),
      contestant_count: parseInt(contestantCount.count),
      score_count: parseInt(scoreCount.count)
    }
  }

  /**
   * Archive contest and all related categories
   */
  async archiveContest(contestId, userId) {
    const trx = await this.db.transaction()

    try {
      // Archive contest
      await trx(this.tableName)
        .where('id', contestId)
        .update({ status: 'archived' })

      // Archive all categories
      await trx('categories')
        .where('contest_id', contestId)
        .update({ is_active: false })

      await trx.commit()
      return true
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }

  /**
   * Reactivate contest and all related categories
   */
  async reactivateContest(contestId, userId) {
    const trx = await this.db.transaction()

    try {
      // Reactivate contest
      await trx(this.tableName)
        .where('id', contestId)
        .update({ status: 'active' })

      // Reactivate all categories
      await trx('categories')
        .where('contest_id', contestId)
        .update({ is_active: true })

      await trx.commit()
      return true
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }

  /**
   * Get contests by event with basic info
   */
  async getContestsByEventBasic(eventId) {
    return this.db(this.tableName)
      .where('event_id', eventId)
      .select('id', 'name', 'description', 'status', 'start_date', 'end_date')
      .orderBy('created_at', 'desc')
  }
}