/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('subcategories', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('category_id').references('id').inTable('categories').onDelete('CASCADE')
    table.string('name').notNullable()
    table.text('description').nullable()
    table.integer('score_cap').nullable()
    table.integer('order_index').defaultTo(0)
    table.boolean('is_active').defaultTo(true)
    table.timestamps(true, true)
    
    // Indexes
    table.index(['category_id'])
    table.index(['is_active'])
    table.index(['order_index'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('subcategories')
}