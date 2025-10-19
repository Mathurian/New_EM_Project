/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('contestants', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('name').notNullable()
    table.string('email').nullable()
    table.string('phone').nullable()
    table.enum('gender', ['male', 'female', 'non-binary', 'prefer-not-to-say', 'other']).nullable()
    table.integer('contestant_number').nullable()
    table.text('bio').nullable()
    table.string('image_url').nullable()
    table.string('pronouns').nullable()
    table.boolean('is_active').defaultTo(true)
    table.timestamps(true, true)
    
    // Indexes
    table.index(['is_active'])
    table.index(['contestant_number'])
    table.index(['email'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('contestants')
}