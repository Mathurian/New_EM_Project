export async function up(knex) {
  await knex.schema.createTable('backups', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('filename').notNullable()
    table.string('file_path').notNullable()
    table.enum('backup_type', ['schema', 'full']).notNullable()
    table.bigInteger('file_size').notNullable()
    table.uuid('created_by').references('id').inTable('users').onDelete('CASCADE')
    table.timestamps(true, true)
    
    // Indexes
    table.index(['backup_type'])
    table.index(['created_at'])
    table.index(['created_by'])
  })
}

export async function down(knex) {
  await knex.schema.dropTable('backups')
}