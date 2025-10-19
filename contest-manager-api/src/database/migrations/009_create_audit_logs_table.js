/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('audit_logs', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('user_id').references('id').inTable('users').onDelete('SET NULL')
    table.string('action').notNullable() // e.g., 'contest_created', 'score_updated'
    table.string('entity_type').notNullable() // e.g., 'contest', 'score', 'user'
    table.uuid('entity_id') // ID of the affected entity
    table.jsonb('old_values') // Previous values
    table.jsonb('new_values') // New values
    table.string('ip_address')
    table.string('user_agent')
    table.timestamp('created_at').defaultTo(knex.fn.now())
    
    // Indexes for performance
    table.index(['user_id'])
    table.index(['action'])
    table.index(['entity_type', 'entity_id'])
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