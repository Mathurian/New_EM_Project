export async function up(knex) {
  await knex.schema.createTable('emcee_scripts', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('title').notNullable()
    table.text('content').notNullable()
    table.uuid('event_id').references('id').inTable('events').onDelete('CASCADE')
    table.uuid('contest_id').references('id').inTable('contests').onDelete('CASCADE')
    table.uuid('subcategory_id').references('id').inTable('subcategories').onDelete('CASCADE')
    table.boolean('is_active').defaultTo(true)
    table.uuid('created_by').references('id').inTable('users').onDelete('CASCADE')
    table.uuid('updated_by').references('id').inTable('users').onDelete('SET NULL')
    table.timestamps(true, true)
    
    // Indexes
    table.index(['event_id'])
    table.index(['contest_id'])
    table.index(['subcategory_id'])
    table.index(['is_active'])
    table.index(['created_by'])
  })
}

export async function down(knex) {
  await knex.schema.dropTable('emcee_scripts')
}