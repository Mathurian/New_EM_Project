import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'

export const templateRoutes = async (fastify) => {
  // Get all templates
  fastify.get('/', {
    schema: {
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(100).default(20),
        search: Joi.string().max(100).default('')
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { page, limit, search } = request.query
      const offset = (page - 1) * limit

      let query = fastify.db('subcategory_templates')
        .orderBy('name')

      if (search) {
        query = query.where(function() {
          this.where('name', 'ilike', `%${search}%`)
            .orWhere('description', 'ilike', `%${search}%`)
        })
      }

      const templates = await query.limit(limit).offset(offset)
      const total = await fastify.db('subcategory_templates').count('* as count').first()

      return reply.send({
        templates,
        pagination: {
          page,
          limit,
          total: parseInt(total.count),
          pages: Math.ceil(total.count / limit)
        }
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch templates' })
    }
  })

  // Get template by ID
  fastify.get('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const template = await fastify.db('subcategory_templates')
        .where('id', request.params.id)
        .first()

      if (!template) {
        return reply.status(404).send({ error: 'Template not found' })
      }

      // Get template criteria
      const criteria = await fastify.db('template_criteria')
        .where('template_id', request.params.id)
        .orderBy('order_index')

      return reply.send({
        ...template,
        criteria
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch template' })
    }
  })

  // Create template
  fastify.post('/', {
    schema: {
      body: Joi.object({
        name: Joi.string().min(1).max(200).required(),
        description: Joi.string().max(1000).optional(),
        criteria: Joi.array().items(
          Joi.object({
            name: Joi.string().min(1).max(200).required(),
            description: Joi.string().max(500).optional(),
            max_score: Joi.number().min(0).required(),
            order_index: Joi.number().integer().min(0).default(0)
          })
        ).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { criteria, ...templateData } = request.body

      // Create template
      const [template] = await fastify.db('subcategory_templates')
        .insert({
          ...templateData,
          created_by: request.user.id
        })
        .returning('*')

      // Create criteria if provided
      if (criteria && criteria.length > 0) {
        const criteriaData = criteria.map(criterion => ({
          template_id: template.id,
          name: criterion.name,
          description: criterion.description || '',
          max_score: criterion.max_score,
          order_index: criterion.order_index || 0
        }))

        await fastify.db('template_criteria').insert(criteriaData)
      }

      // Get template with criteria
      const templateWithCriteria = await fastify.db('subcategory_templates')
        .where('id', template.id)
        .first()

      const templateCriteria = await fastify.db('template_criteria')
        .where('template_id', template.id)
        .orderBy('order_index')

      return reply.status(201).send({
        ...templateWithCriteria,
        criteria: templateCriteria
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create template' })
    }
  })

  // Update template
  fastify.put('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      }),
      body: Joi.object({
        name: Joi.string().min(1).max(200).optional(),
        description: Joi.string().max(1000).optional(),
        criteria: Joi.array().items(
          Joi.object({
            id: Joi.string().uuid().optional(),
            name: Joi.string().min(1).max(200).required(),
            description: Joi.string().max(500).optional(),
            max_score: Joi.number().min(0).required(),
            order_index: Joi.number().integer().min(0).default(0)
          })
        ).optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { criteria, ...templateData } = request.body

      // Update template
      const [template] = await fastify.db('subcategory_templates')
        .where('id', id)
        .update({
          ...templateData,
          updated_by: request.user.id,
          updated_at: new Date()
        })
        .returning('*')

      if (!template) {
        return reply.status(404).send({ error: 'Template not found' })
      }

      // Update criteria if provided
      if (criteria) {
        // Delete existing criteria
        await fastify.db('template_criteria').where('template_id', id).del()

        // Insert new criteria
        if (criteria.length > 0) {
          const criteriaData = criteria.map(criterion => ({
            template_id: id,
            name: criterion.name,
            description: criterion.description || '',
            max_score: criterion.max_score,
            order_index: criterion.order_index || 0
          }))

          await fastify.db('template_criteria').insert(criteriaData)
        }
      }

      // Get template with criteria
      const templateWithCriteria = await fastify.db('subcategory_templates')
        .where('id', id)
        .first()

      const templateCriteria = await fastify.db('template_criteria')
        .where('template_id', id)
        .orderBy('order_index')

      return reply.send({
        ...templateWithCriteria,
        criteria: templateCriteria
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update template' })
    }
  })

  // Delete template
  fastify.delete('/:id', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params

      // Check if template exists
      const template = await fastify.db('subcategory_templates')
        .where('id', id)
        .first()

      if (!template) {
        return reply.status(404).send({ error: 'Template not found' })
      }

      // Delete criteria first
      await fastify.db('template_criteria').where('template_id', id).del()

      // Delete template
      await fastify.db('subcategory_templates').where('id', id).del()

      return reply.status(204).send()
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to delete template' })
    }
  })

  // Create subcategory from template
  fastify.post('/:id/create-subcategory', {
    schema: {
      params: Joi.object({
        id: Joi.string().uuid().required()
      }),
      body: Joi.object({
        category_id: Joi.string().uuid().required(),
        name: Joi.string().min(1).max(200).required(),
        description: Joi.string().max(1000).optional(),
        score_cap: Joi.number().min(0).optional(),
        order_index: Joi.number().integer().min(0).default(0)
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { id } = request.params
      const { category_id, ...subcategoryData } = request.body

      // Get template
      const template = await fastify.db('subcategory_templates')
        .where('id', id)
        .first()

      if (!template) {
        return reply.status(404).send({ error: 'Template not found' })
      }

      // Get template criteria
      const templateCriteria = await fastify.db('template_criteria')
        .where('template_id', id)
        .orderBy('order_index')

      // Create subcategory
      const [subcategory] = await fastify.db('subcategories')
        .insert({
          ...subcategoryData,
          category_id,
          created_by: request.user.id
        })
        .returning('*')

      // Create criteria from template
      if (templateCriteria.length > 0) {
        const criteriaData = templateCriteria.map(criterion => ({
          subcategory_id: subcategory.id,
          name: criterion.name,
          description: criterion.description,
          max_score: criterion.max_score,
          order_index: criterion.order_index
        }))

        await fastify.db('criteria').insert(criteriaData)
      }

      return reply.status(201).send(subcategory)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to create subcategory from template' })
    }
  })

  // Get template statistics
  fastify.get('/stats/overview', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const [
        totalTemplates,
        totalCriteria,
        templatesWithCriteria
      ] = await Promise.all([
        fastify.db('subcategory_templates').count('* as count').first(),
        fastify.db('template_criteria').count('* as count').first(),
        fastify.db('subcategory_templates')
          .join('template_criteria', 'subcategory_templates.id', 'template_criteria.template_id')
          .count('DISTINCT subcategory_templates.id as count')
          .first()
      ])

      return reply.send({
        total_templates: parseInt(totalTemplates.count),
        total_criteria: parseInt(totalCriteria.count),
        templates_with_criteria: parseInt(templatesWithCriteria.count),
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch template statistics' })
    }
  })
}