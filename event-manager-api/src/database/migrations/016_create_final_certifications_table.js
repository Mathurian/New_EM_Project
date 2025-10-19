export async function up(knex) {
  await knex.schema.createTable('final_certifications', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('subcategory_id').references('id').inTable('subcategories').onDelete('CASCADE')
    table.uuid('certified_by').references('id').inTable('users').onDelete('CASCADE')
    table.text('notes').nullable()
    table.timestamp('certified_at').notNullable()
    table.timestamps(true, true)
    
    // Indexes
    table.index(['subcategory_id'])
    table.index(['certified_by'])
    table.index(['certified_at'])
    
    // Ensure only one final certification per subcategory
    table.unique(['subcategory_id'])
  })
}

export async function down(knex) {
  await knex.schema.dropTable('final_certifications')
}