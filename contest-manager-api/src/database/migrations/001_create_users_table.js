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
    table.string('preferred_name')
    table.enum('role', ['organizer', 'emcee', 'judge', 'tally_master', 'auditor', 'board']).notNullable()
    table.string('phone')
    table.text('bio')
    table.string('image_url')
    table.string('pronouns')
    table.boolean('is_active').defaultTo(true)
    table.boolean('is_head_judge').defaultTo(false)
    table.timestamp('email_verified_at')
    table.timestamp('last_login_at')
    table.timestamps(true, true)
    
    // Indexes for performance
    table.index(['email'])
    table.index(['role'])
    table.index(['is_active'])
    table.index(['created_at'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('users')
}