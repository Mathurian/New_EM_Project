/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('contests', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('name').notNullable()
    table.text('description')
    table.date('start_date').notNullable()
    table.date('end_date').notNullable()
    table.enum('status', ['draft', 'active', 'completed', 'archived']).defaultTo('draft')
    table.jsonb('settings').defaultTo('{}') // Flexible settings storage
    table.uuid('created_by').references('id').inTable('users').onDelete('CASCADE')
    table.timestamps(true, true)
    
    // Indexes
    table.index(['status'])
    table.index(['start_date', 'end_date'])
    table.index(['created_by'])
    table.index(['created_at'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('contests')
}