/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('system_settings', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('setting_key').unique().notNullable()
    table.text('setting_value').notNullable()
    table.text('description').nullable()
    table.enum('setting_type', ['string', 'number', 'boolean', 'json']).defaultTo('string')
    table.boolean('is_public').defaultTo(false)
    table.uuid('updated_by').references('id').inTable('users').onDelete('SET NULL')
    table.timestamps(true, true)
    
    // Indexes
    table.index(['setting_key'])
    table.index(['is_public'])
    table.index(['updated_by'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('system_settings')
}