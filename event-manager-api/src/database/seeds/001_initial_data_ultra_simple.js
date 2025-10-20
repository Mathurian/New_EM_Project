/**
 * Database Seeds - Ultra Simple Version
 * Initial data for the Event Manager system
 */

export async function seed(knex) {
  console.log('🌱 Starting seed process...')
  
  // Deletes ALL existing entries
  await knex('system_settings').del()
  await knex('users').del()
  console.log('🗑️  Cleared existing data')

  // Inserts seed entries
  await knex('system_settings').insert([
    {
      setting_key: 'system_name',
      setting_value: 'Event Manager',
      description: 'The name of the event management system',
      setting_type: 'string',
      is_public: true
    },
    {
      setting_key: 'default_timezone',
      setting_value: 'America/New_York',
      description: 'Default timezone for events',
      setting_type: 'string',
      is_public: true
    },
    {
      setting_key: 'max_file_size',
      setting_value: '10485760',
      description: 'Maximum file upload size in bytes (10MB)',
      setting_type: 'number',
      is_public: false
    },
    {
      setting_key: 'session_timeout',
      setting_value: '1800',
      description: 'Session timeout in seconds (30 minutes)',
      setting_type: 'number',
      is_public: false
    }
  ])
  console.log('⚙️  System settings created')

  // Create default admin user with pre-hashed password (admin123)
  // This is the bcrypt hash for 'admin123' with 10 rounds
  const hashedPassword = '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi'

  await knex('users').insert([
    {
      email: 'admin@eventmanager.com',
      password_hash: hashedPassword,
      first_name: 'System',
      last_name: 'Administrator',
      role: 'organizer',
      is_active: true
    }
  ])
  console.log('👤 Admin user created')

  console.log('✅ Seed data inserted successfully')
  console.log('🔐 Default admin user created:')
  console.log('   Email: admin@eventmanager.com')
  console.log('   Password: admin123')
}
