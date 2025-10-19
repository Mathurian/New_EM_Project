import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'

export const databaseBrowserRoutes = async (fastify) => {
  // Get database tables
  fastify.get('/tables', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const tables = await fastify.db.raw(`
        SELECT 
          table_name,
          table_type,
          (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as column_count
        FROM information_schema.tables t
        WHERE table_schema = 'public'
        ORDER BY table_name
      `)

      return reply.send({
        tables: tables.rows,
        total_tables: tables.rows.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch database tables' })
    }
  })

  // Get table structure
  fastify.get('/tables/:table', {
    schema: {
      params: Joi.object({
        table: Joi.string().required()
      }),
      querystring: Joi.object({
        page: Joi.number().integer().min(1).default(1),
        limit: Joi.number().integer().min(1).max(1000).default(100)
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { table } = request.params
      const { page, limit } = request.query
      const offset = (page - 1) * limit

      // Get table columns
      const columns = await fastify.db.raw(`
        SELECT 
          column_name,
          data_type,
          is_nullable,
          column_default,
          character_maximum_length
        FROM information_schema.columns
        WHERE table_name = ?
        ORDER BY ordinal_position
      `, [table])

      // Get table data
      const data = await fastify.db(table)
        .limit(limit)
        .offset(offset)

      // Get total count
      const total = await fastify.db(table).count('* as count').first()

      return reply.send({
        table_name: table,
        columns: columns.rows,
        data,
        pagination: {
          page,
          limit,
          total: parseInt(total.count),
          pages: Math.ceil(total.count / limit)
        },
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch table data' })
    }
  })

  // Execute custom query
  fastify.post('/query', {
    schema: {
      body: Joi.object({
        query: Joi.string().min(1).max(5000).required(),
        limit: Joi.number().integer().min(1).max(1000).default(100)
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { query, limit } = request.body

      // Basic security check - only allow SELECT statements
      const trimmedQuery = query.trim().toLowerCase()
      if (!trimmedQuery.startsWith('select')) {
        return reply.status(400).send({ error: 'Only SELECT queries are allowed' })
      }

      // Add limit if not present
      const limitedQuery = trimmedQuery.includes('limit') ? query : `${query} LIMIT ${limit}`

      const result = await fastify.db.raw(limitedQuery)

      return reply.send({
        query: limitedQuery,
        data: result.rows,
        row_count: result.rows.length,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ 
        error: 'Query execution failed',
        details: error.message
      })
    }
  })

  // Get table statistics
  fastify.get('/tables/:table/stats', {
    schema: {
      params: Joi.object({
        table: Joi.string().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { table } = request.params

      // Get table size and row count
      const stats = await fastify.db.raw(`
        SELECT 
          schemaname,
          tablename,
          attname,
          n_distinct,
          correlation
        FROM pg_stats
        WHERE tablename = ?
        ORDER BY attname
      `, [table])

      // Get row count
      const rowCount = await fastify.db(table).count('* as count').first()

      // Get table size
      const size = await fastify.db.raw(`
        SELECT 
          pg_size_pretty(pg_total_relation_size(?)) as total_size,
          pg_size_pretty(pg_relation_size(?)) as table_size
      `, [table, table])

      return reply.send({
        table_name: table,
        row_count: parseInt(rowCount.count),
        size_info: size.rows[0],
        column_stats: stats.rows,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch table statistics' })
    }
  })

  // Get database overview
  fastify.get('/overview', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const [
        tableCount,
        totalSize,
        connectionCount,
        databaseInfo
      ] = await Promise.all([
        fastify.db.raw(`
          SELECT COUNT(*) as count
          FROM information_schema.tables
          WHERE table_schema = 'public'
        `),
        fastify.db.raw(`
          SELECT pg_size_pretty(pg_database_size(current_database())) as size
        `),
        fastify.db.raw(`
          SELECT COUNT(*) as count
          FROM pg_stat_activity
          WHERE state = 'active'
        `),
        fastify.db.raw(`
          SELECT 
            current_database() as database_name,
            version() as version,
            current_user as current_user,
            inet_server_addr() as server_address,
            inet_server_port() as server_port
        `)
      ])

      return reply.send({
        table_count: parseInt(tableCount.rows[0].count),
        total_size: totalSize.rows[0].size,
        active_connections: parseInt(connectionCount.rows[0].count),
        database_info: databaseInfo.rows[0],
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch database overview' })
    }
  })

  // Get table relationships
  fastify.get('/tables/:table/relationships', {
    schema: {
      params: Joi.object({
        table: Joi.string().required()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { table } = request.params

      // Get foreign key relationships
      const foreignKeys = await fastify.db.raw(`
        SELECT
          tc.table_name,
          kcu.column_name,
          ccu.table_name AS foreign_table_name,
          ccu.column_name AS foreign_column_name,
          tc.constraint_name
        FROM information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
          ON tc.constraint_name = kcu.constraint_name
          AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
          ON ccu.constraint_name = tc.constraint_name
          AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY'
          AND tc.table_name = ?
      `, [table])

      // Get tables that reference this table
      const referencedBy = await fastify.db.raw(`
        SELECT
          tc.table_name,
          kcu.column_name,
          tc.constraint_name
        FROM information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
          ON tc.constraint_name = kcu.constraint_name
          AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
          ON ccu.constraint_name = tc.constraint_name
          AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY'
          AND ccu.table_name = ?
      `, [table])

      return reply.send({
        table_name: table,
        foreign_keys: foreignKeys.rows,
        referenced_by: referencedBy.rows,
        generated_at: new Date().toISOString()
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to fetch table relationships' })
    }
  })
}