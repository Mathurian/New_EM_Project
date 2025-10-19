/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('criteria', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('subcategory_id').references('id').inTable('subcategories').onDelete('CASCADE')
    table.string('name').notNullable()
    table.text('description').nullable()
    table.integer('max_score').notNullable()
    table.integer('order_index').defaultTo(0)
    table.boolean('is_active').defaultTo(true)
    table.timestamps(true, true)
    
    // Indexes
    table.index(['subcategory_id'])
    table.index(['is_active'])
    table.index(['order_index'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('criteria')
}