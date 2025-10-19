/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('users', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('email').unique().notNullable()
    table.string('password_hash').notNullable()
    table.string('first_name').notNullable()
    table.string('last_name').notNullable()
    table.string('preferred_name').nullable()
    table.enum('role', ['organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board']).notNullable()
    table.string('phone').nullable()
    table.text('bio').nullable()
    table.string('image_url').nullable()
    table.string('pronouns').nullable()
    table.enum('gender', ['male', 'female', 'non-binary', 'prefer-not-to-say', 'other']).nullable()
    table.boolean('is_active').defaultTo(true)
    table.timestamp('last_login').nullable()
    table.timestamps(true, true)
    
    // Indexes
    table.index(['email'])
    table.index(['role'])
    table.index(['is_active'])
    table.index(['last_login'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('users')
}