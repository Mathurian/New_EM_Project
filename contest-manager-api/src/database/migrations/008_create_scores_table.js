/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('scores', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('subcategory_id').references('id').inTable('subcategories').onDelete('CASCADE')
    table.uuid('contestant_id').references('id').inTable('contestants').onDelete('CASCADE')
    table.uuid('judge_id').references('id').inTable('users').onDelete('CASCADE')
    table.uuid('criterion_id').references('id').inTable('criteria').onDelete('CASCADE')
    table.decimal('score', 5, 2).notNullable() // Supports scores like 8.75
    table.text('comments')
    table.boolean('is_final').defaultTo(false)
    table.timestamps(true, true)
    
    // Unique constraint to prevent duplicate scores
    table.unique(['subcategory_id', 'contestant_id', 'judge_id', 'criterion_id'])
    
    // Indexes for performance
    table.index(['subcategory_id'])
    table.index(['contestant_id'])
    table.index(['judge_id'])
    table.index(['criterion_id'])
    table.index(['is_final'])
    table.index(['created_at'])
    
    // Composite indexes for common queries
    table.index(['subcategory_id', 'contestant_id'])
    table.index(['subcategory_id', 'judge_id'])
    table.index(['judge_id', 'is_final'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('scores')
}