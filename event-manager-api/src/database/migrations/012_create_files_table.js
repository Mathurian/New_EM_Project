/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('files', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('original_name').notNullable()
    table.string('file_name').notNullable()
    table.string('file_path').notNullable()
    table.string('thumbnail_path').nullable()
    table.string('mime_type').notNullable()
    table.bigInteger('file_size').notNullable()
    table.string('entity_type').notNullable() // 'event', 'contest', 'category', 'contestant', 'judge', 'document'
    table.uuid('entity_id').notNullable()
    table.string('category').defaultTo('general') // 'profile_image', 'document', 'event_image', etc.
    table.uuid('uploaded_by').references('id').inTable('users').onDelete('CASCADE')
    table.boolean('is_image').defaultTo(false)
    table.boolean('is_public').defaultTo(false)
    table.text('description').nullable()
    table.timestamps(true, true)

    // Indexes for performance
    table.index(['entity_type', 'entity_id'])
    table.index(['uploaded_by'])
    table.index(['category'])
    table.index(['is_image'])
    table.index(['is_public'])
    table.index(['created_at'])
    table.index(['entity_type', 'entity_id', 'category']) // Composite index
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('files')
}