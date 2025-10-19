/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  // Subcategory-Contestant assignments
  await knex.schema.createTable('subcategory_contestants', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('subcategory_id').references('id').inTable('subcategories').onDelete('CASCADE')
    table.uuid('contestant_id').references('id').inTable('contestants').onDelete('CASCADE')
    table.timestamps(true, true)
    
    // Unique constraint
    table.unique(['subcategory_id', 'contestant_id'])
    
    // Indexes
    table.index(['subcategory_id'])
    table.index(['contestant_id'])
  })

  // Subcategory-Judge assignments
  await knex.schema.createTable('subcategory_judges', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('subcategory_id').references('id').inTable('subcategories').onDelete('CASCADE')
    table.uuid('judge_id').references('id').inTable('users').onDelete('CASCADE')
    table.boolean('is_certified').defaultTo(false)
    table.timestamp('certified_at')
    table.timestamps(true, true)
    
    // Unique constraint
    table.unique(['subcategory_id', 'judge_id'])
    
    // Indexes
    table.index(['subcategory_id'])
    table.index(['judge_id'])
    table.index(['is_certified'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('subcategory_judges')
  await knex.schema.dropTable('subcategory_contestants')
}