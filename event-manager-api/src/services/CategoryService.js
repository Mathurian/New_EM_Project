import { BaseService } from './BaseService.js'

export class CategoryService extends BaseService {
  constructor() {
    super('categories')
  }

  /**
   * Get categories for a contest
   */
  async getCategoriesByContest(contestId, options = {}) {
    const {
      includeSubcategories = false,
      includeContestants = false,
      includeCriteria = false
    } = options

    let query = this.db(this.tableName)
      .where('contest_id', contestId)
      .orderBy('order_index', 'asc')

    const categories = await query

    if (includeSubcategories) {
      for (const category of categories) {
        category.subcategories = await this.getSubcategoriesByCategory(category.id, {
          includeContestants,
          includeCriteria
        })
      }
    }

    return categories
  }

  /**
   * Get subcategories for a category
   */
  async getSubcategoriesByCategory(categoryId, options = {}) {
    const {
      includeContestants = false,
      includeCriteria = false
    } = options

    let query = this.db('subcategories')
      .where('category_id', categoryId)
      .orderBy('order_index', 'asc')

    const subcategories = await query

    if (includeContestants) {
      for (const subcategory of subcategories) {
        subcategory.contestants = await this.getContestantsBySubcategory(subcategory.id)
      }
    }

    if (includeCriteria) {
      for (const subcategory of subcategories) {
        subcategory.criteria = await this.getCriteriaBySubcategory(subcategory.id)
      }
    }

    return subcategories
  }

  /**
   * Get contestants for a subcategory
   */
  async getContestantsBySubcategory(subcategoryId) {
    return this.db('subcategory_contestants')
      .join('contestants', 'subcategory_contestants.contestant_id', 'contestants.id')
      .where('subcategory_contestants.subcategory_id', subcategoryId)
      .select('contestants.*')
  }

  /**
   * Get criteria for a subcategory
   */
  async getCriteriaBySubcategory(subcategoryId) {
    return this.db('criteria')
      .where('subcategory_id', subcategoryId)
      .orderBy('order_index', 'asc')
  }

  /**
   * Create category with subcategories
   */
  async createCategoryWithSubcategories(categoryData, subcategoriesData = [], userId) {
    const trx = await this.db.transaction()

    try {
      // Create category
      const category = await trx(this.tableName)
        .insert({
          ...categoryData,
          created_by: userId
        })
        .returning('*')

      // Create subcategories if provided
      if (subcategoriesData.length > 0) {
        const subcategories = await trx('subcategories')
          .insert(
            subcategoriesData.map(subcategory => ({
              ...subcategory,
              category_id: category[0].id
            }))
          )
          .returning('*')

        category[0].subcategories = subcategories
      }

      await trx.commit()
      return category[0]
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }

  /**
   * Assign contestants to subcategory
   */
  async assignContestantsToSubcategory(subcategoryId, contestantIds, userId) {
    const trx = await this.db.transaction()

    try {
      // Remove existing assignments
      await trx('subcategory_contestants')
        .where('subcategory_id', subcategoryId)
        .del()

      // Add new assignments
      if (contestantIds.length > 0) {
        const assignments = contestantIds.map(contestantId => ({
          subcategory_id: subcategoryId,
          contestant_id: contestantId
        }))

        await trx('subcategory_contestants').insert(assignments)
      }

      await trx.commit()
      return true
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }

  /**
   * Assign judges to subcategory
   */
  async assignJudgesToSubcategory(subcategoryId, judgeIds, userId) {
    const trx = await this.db.transaction()

    try {
      // Remove existing assignments
      await trx('subcategory_judges')
        .where('subcategory_id', subcategoryId)
        .del()

      // Add new assignments
      if (judgeIds.length > 0) {
        const assignments = judgeIds.map(judgeId => ({
          subcategory_id: subcategoryId,
          judge_id: judgeId
        }))

        await trx('subcategory_judges').insert(assignments)
      }

      await trx.commit()
      return true
    } catch (error) {
      await trx.rollback()
      throw error
    }
  }

  /**
   * Get category statistics
   */
  async getCategoryStats(categoryId) {
    const [
      subcategoryCount,
      contestantCount,
      judgeCount,
      scoreCount
    ] = await Promise.all([
      this.db('subcategories').where('category_id', categoryId).count('* as count').first(),
      this.db('subcategory_contestants')
        .join('subcategories', 'subcategory_contestants.subcategory_id', 'subcategories.id')
        .where('subcategories.category_id', categoryId)
        .count('* as count')
        .first(),
      this.db('subcategory_judges')
        .join('subcategories', 'subcategory_judges.subcategory_id', 'subcategories.id')
        .where('subcategories.category_id', categoryId)
        .count('* as count')
        .first(),
      this.db('scores')
        .join('criteria', 'scores.criterion_id', 'criteria.id')
        .join('subcategories', 'criteria.subcategory_id', 'subcategories.id')
        .where('subcategories.category_id', categoryId)
        .count('* as count')
        .first()
    ])

    return {
      category_id: categoryId,
      subcategory_count: parseInt(subcategoryCount.count),
      contestant_count: parseInt(contestantCount.count),
      judge_count: parseInt(judgeCount.count),
      score_count: parseInt(scoreCount.count)
    }
  }
}