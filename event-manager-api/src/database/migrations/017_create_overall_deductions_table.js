export async function up(knex) {
  await knex.schema.createTable('overall_deductions', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.uuid('subcategory_id').references('id').inTable('subcategories').onDelete('CASCADE')
    table.uuid('contestant_id').references('id').inTable('contestants').onDelete('CASCADE')
    table.decimal('amount', 10, 2).notNullable()
    table.string('reason', 200).nullable()
    table.text('comment').nullable()
    table.uuid('created_by').references('id').inTable('users').onDelete('CASCADE')
    table.timestamps(true, true)
    
    // Indexes
    table.index(['subcategory_id'])
    table.index(['contestant_id'])
    table.index(['created_by'])
    
    // Ensure only one deduction per contestant per subcategory
    table.unique(['subcategory_id', 'contestant_id'])
  })
}

export async function down(knex) {
  await knex.schema.dropTable('overall_deductions')
}