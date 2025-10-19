/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('audit_logs', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('user_id').references('id').inTable('users').onDelete('SET NULL')
    table.string('user_name').nullable()
    table.string('user_role').nullable()
    table.string('action').notNullable()
    table.string('resource_type').nullable()
    table.uuid('resource_id').nullable()
    table.jsonb('old_values').nullable()
    table.jsonb('new_values').nullable()
    table.text('details').nullable()
    table.string('ip_address').nullable()
    table.string('user_agent').nullable()
    table.enum('log_level', ['debug', 'info', 'warn', 'error']).defaultTo('info')
    table.timestamps(true, true)
    
    // Indexes
    table.index(['user_id'])
    table.index(['action'])
    table.index(['resource_type', 'resource_id'])
    table.index(['log_level'])
    table.index(['created_at'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('audit_logs')
}