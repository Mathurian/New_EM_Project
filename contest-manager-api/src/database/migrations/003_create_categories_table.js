/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('categories', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('contest_id').references('id').inTable('contests').onDelete('CASCADE')
    table.string('name').notNullable()
    table.text('description')
    table.integer('order_index').defaultTo(0)
    table.boolean('is_active').defaultTo(true)
    table.timestamps(true, true)
    
    // Indexes
    table.index(['contest_id'])
    table.index(['is_active'])
    table.index(['order_index'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('categories')
}