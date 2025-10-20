/**
 * Database Seeds
 * Initial data for the Event Manager system
 */

export async function seed(knex) {
  // Deletes ALL existing entries
  await knex('system_settings').del()
  await knex('users').del()

  // Inserts seed entries
  await knex('system_settings').insert([
    {
      id: 1,
      key: 'system_name',
      value: 'Event Manager',
      description: 'The name of the event management system',
      created_at: knex.fn.now(),
      updated_at: knex.fn.now()
    },
    {
      id: 2,
      key: 'default_timezone',
      value: 'America/New_York',
      description: 'Default timezone for events',
      created_at: knex.fn.now(),
      updated_at: knex.fn.now()
    },
    {
      id: 3,
      key: 'max_file_size',
      value: '10485760',
      description: 'Maximum file upload size in bytes (10MB)',
      created_at: knex.fn.now(),
      updated_at: knex.fn.now()
    },
    {
      id: 4,
      key: 'session_timeout',
      value: '1800',
      description: 'Session timeout in seconds (30 minutes)',
      created_at: knex.fn.now(),
      updated_at: knex.fn.now()
    }
  ])

  // Create default admin user (password: admin123)
  const bcrypt = await import('bcryptjs')
  const hashedPassword = await bcrypt.hash('admin123', 10)

  await knex('users').insert([
    {
      id: 1,
      email: 'admin@eventmanager.com',
      password_hash: hashedPassword,
      first_name: 'System',
      last_name: 'Administrator',
      role: 'organizer',
      is_active: true,
      created_at: knex.fn.now(),
      updated_at: knex.fn.now()
    }
  ])

  console.log('✅ Seed data inserted successfully')
}
