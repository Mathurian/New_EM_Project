/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('scores', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('criterion_id').references('id').inTable('criteria').onDelete('CASCADE')
    table.uuid('judge_id').references('id').inTable('users').onDelete('CASCADE')
    table.uuid('contestant_id').references('id').inTable('contestants').onDelete('CASCADE')
    table.decimal('score', 5, 2).notNullable()
    table.text('comments').nullable()
    table.boolean('is_signed').defaultTo(false)
    table.timestamp('signed_at').nullable()
    table.timestamps(true, true)
    
    // Unique constraint - one score per judge per criterion per contestant
    table.unique(['criterion_id', 'judge_id', 'contestant_id'])
    
    // Indexes
    table.index(['criterion_id'])
    table.index(['judge_id'])
    table.index(['contestant_id'])
    table.index(['is_signed'])
    table.index(['created_at'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('scores')
}