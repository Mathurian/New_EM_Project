import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { CategoryService } from '../services/CategoryService.js'

export const categoryRoutes = async (fastify) => {
  const categoryService = new CategoryService()

  // Get categories for a contest
  fastify.get('/contest/:contestId', {
    schema: {
      params: Joi.object({
        contestId: Joi.string().uuid().required()
      }),
      querystring: Joi.object({
        includeSubcategories: Joi.boolean().default(false),
        includeContestants: Joi.boolean().default(false),
        includeCriteria: Joi.boolean().default(false)
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const categories = await categoryService.getCategoriesByContest(
        request.params.contestId,
        request.query
      )
      return reply.send(categories)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch categories' })
    }
  })

  // Get category by ID
  fastify.get('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const category = await categoryService.getById(request.params.id)
      if (!category) {
        return reply.status(404).send({ error: 'Category not found' })
      }
      return reply.send(category)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch category' })
    }
  })

  // Create category
  fastify.post('/', {
    schema: {
      body: Joi.object({
        contest_id: Joi.string().uuid().required(),
        name: Joi.string().min(1).max(200).required(),
        description: Joi.string().max(1000).optional(),
        order_index: Joi.number().integer().min(0).default(0),
        subcategories: Joi.array().items(
          Joi.object({
            name: Joi.string().min(1).max(200).required(),
            description: Joi.string().max(1000).optional(),
            score_cap: Joi.number().integer().min(0).optional(),
            order_index: Joi.number().integer().min(0).default(0)
          })
        ).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { subcategories, ...categoryData } = request.body
      const category = await categoryService.createCategoryWithSubcategories(
        categoryData,
        subcategories || [],
        request.user.id
      )
      return reply.status(201).send(category)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create category' })
    }
  })

  // Update category
  fastify.put('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      }),
      body: Joi.object({
        name: Joi.string().min(1).max(200).optional(),
        description: Joi.string().max(1000).optional(),
        order_index: Joi.number().integer().min(0).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const category = await categoryService.updateById(
        request.params.id,
        request.body,
        request.user.id
      )
      if (!category) {
        return reply.status(404).send({ error: 'Category not found' })
      }
      return reply.send(category)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update category' })
    }
  })

  // Delete category
  fastify.delete('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const deleted = await categoryService.deleteById(request.params.id, request.user.id)
      if (!deleted) {
        return reply.status(404).send({ error: 'Category not found' })
      }
      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete category' })
    }
  })

  // Assign contestants to subcategory
  fastify.post('/:id/subcategories/:subcategoryId/assign-contestants', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required(),
        subcategoryId: Joi.string().uuid().required()
      }),
      body: Joi.object({
        contestant_ids: Joi.array().items(Joi.string().uuid()).required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const assigned = await categoryService.assignContestantsToSubcategory(
        request.params.subcategoryId,
        request.body.contestant_ids,
        request.user.id
      )
      return reply.send({ message: 'Contestants assigned successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to assign contestants' })
    }
  })

  // Assign judges to subcategory
  fastify.post('/:id/subcategories/:subcategoryId/assign-judges', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required(),
        subcategoryId: Joi.string().uuid().required()
      }),
      body: Joi.object({
        judge_ids: Joi.array().items(Joi.string().uuid()).required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const assigned = await categoryService.assignJudgesToSubcategory(
        request.params.subcategoryId,
        request.body.judge_ids,
        request.user.id
      )
      return reply.send({ message: 'Judges assigned successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to assign judges' })
    }
  })

  // Get category statistics
  fastify.get('/:id/stats', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const stats = await categoryService.getCategoryStats(request.params.id)
      if (!stats) {
        return reply.status(404).send({ error: 'Category not found' })
      }
      return reply.send(stats)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch category statistics' })
    }
  })
}