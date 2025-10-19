import { BaseService } from './BaseService.js'

export class EventService extends BaseService {
  constructor() {
    super('events')
  }

  /**
   * Get all events with optional filtering
   */
  async getAllEvents(options = {}) {
    const {
      page = 1,
      limit = 10,
      search = '',
      status = 'all',
      sortBy = 'created_at',
      sortOrder = 'desc'
    } = options

    let query = this.db(this.tableName)
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
    const events = await query.offset(offset).limit(limit)

    return {
      data: events,
      pagination: {
        page,
        limit,
        total,
        pages: Math.ceil(total / limit)
      }
    }
  }

  /**
   * Get event with contests and categories
   */
  async getEventWithDetails(eventId) {
    const event = await this.getById(eventId)
    if (!event) return null

    // Get contests for this event
    const contests = await this.db('contests')
      .where('event_id', eventId)
      .orderBy('created_at', 'desc')

    // Get categories for each contest
    for (const contest of contests) {
      const categories = await this.db('categories')
        .where('contest_id', contest.id)
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

      contest.categories = categories
    }

    event.contests = contests
    return event
  }

  /**
   * Create event with contests
   */
  async createEventWithContests(eventData, contestsData = [], userId) {
    const trx = await this.db.transaction()

    try {
      // Create event
      const event = await trx(this.tableName)
        .insert({
          ...eventData,
          created_by: userId
        })
        .returning('*')

      // Create contests if provided
      if (contestsData.length > 0) {
        const contests = await trx('contests')
          .insert(
            contestsData.map(contest => ({
              ...contest,
              event_id: event[0].id,
              created_by: userId
            }))
          )
          .returning('*')

        event[0].contests = contests
      }

      await trx.commit()
      return event[0]
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }

  /**
   * Get event statistics
   */
  async getEventStats(eventId) {
    const event = await this.getById(eventId)
    if (!event) return null

    const [
      contestCount,
      categoryCount,
      subcategoryCount,
      contestantCount
    ] = await Promise.all([
      this.db('contests').where('event_id', eventId).count('* as count').first(),
      this.db('categories')
        .join('contests', 'categories.contest_id', 'contests.id')
        .where('contests.event_id', eventId)
        .count('* as count')
        .first(),
      this.db('subcategories')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .where('contests.event_id', eventId)
        .count('* as count')
        .first(),
      this.db('subcategory_contestants')
        .join('subcategories', 'subcategory_contestants.subcategory_id', 'subcategories.id')
        .join('categories', 'subcategories.category_id', 'categories.id')
        .join('contests', 'categories.contest_id', 'contests.id')
        .where('contests.event_id', eventId)
        .count('* as count')
        .first()
    ])

    return {
      event_id: eventId,
      contest_count: parseInt(contestCount.count),
      category_count: parseInt(categoryCount.count),
      subcategory_count: parseInt(subcategoryCount.count),
      contestant_count: parseInt(contestantCount.count)
    }
  }

  /**
   * Archive event and all related contests
   */
  async archiveEvent(eventId, userId) {
    const trx = await this.db.transaction()

    try {
      // Archive event
      await trx(this.tableName)
        .where('id', eventId)
        .update({ status: 'archived' })

      // Archive all contests
      await trx('contests')
        .where('event_id', eventId)
        .update({ status: 'archived' })

      await trx.commit()
      return true
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }
}