/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.createTable('system_settings', (table) => {
    table.uuid('id').primary().defaultTo(knex.raw('gen_random_uuid()'))
    table.string('key').unique().notNullable()
    table.text('value').notNullable()
    table.string('type').defaultTo('string') // string, number, boolean, json
    table.text('description')
    table.boolean('is_public').defaultTo(false) // Can be accessed by frontend
    table.timestamps(true, true)
    
    // Indexes
    table.index(['key'])
    table.index(['is_public'])
  })
  
  // Insert default settings
  await knex('system_settings').insert([
    {
      key: 'session_timeout',
      value: '1800',
      type: 'number',
      description: 'Session timeout in seconds',
      is_public: false
    },
    {
      key: 'max_file_size',
      value: '5242880',
      type: 'number',
      description: 'Maximum file upload size in bytes',
      is_public: true
    },
    {
      key: 'allowed_file_types',
      value: 'image/jpeg,image/png,image/gif,application/pdf',
      type: 'string',
      description: 'Comma-separated list of allowed file types',
      is_public: true
    },
    {
      key: 'app_name',
      value: 'Contest Manager',
      type: 'string',
      description: 'Application name',
      is_public: true
    },
    {
      key: 'enable_real_time_scoring',
      value: 'true',
      type: 'boolean',
      description: 'Enable real-time scoring updates',
      is_public: true
    }
  ])
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.dropTable('system_settings')
}