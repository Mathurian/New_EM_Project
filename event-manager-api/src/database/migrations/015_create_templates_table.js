export async function up(knex) {
  await knex.schema.createTable('subcategory_templates', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('name').notNullable()
    table.text('description').nullable()
    table.uuid('created_by').references('id').inTable('users').onDelete('CASCADE')
    table.uuid('updated_by').references('id').inTable('users').onDelete('SET NULL')
    table.timestamps(true, true)
    
    // Indexes
    table.index(['name'])
    table.index(['created_by'])
  })

  await knex.schema.createTable('template_criteria', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('template_id').references('id').inTable('subcategory_templates').onDelete('CASCADE')
    table.string('name').notNullable()
    table.text('description').nullable()
    table.decimal('max_score', 10, 2).notNullable()
    table.integer('order_index').defaultTo(0)
    table.timestamps(true, true)
    
    // Indexes
    table.index(['template_id'])
    table.index(['order_index'])
  })
}

export async function down(knex) {
  await knex.schema.dropTable('template_criteria')
  await knex.schema.dropTable('subcategory_templates')
}