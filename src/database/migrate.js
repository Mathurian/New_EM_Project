const migrate = async () => {
  try {
    console.log('🔄 Running database migrations...')
    
    // Generate Prisma client
    const { execSync } = require('child_process')
    console.log('📦 Generating Prisma client...')
    execSync('npx prisma generate', { stdio: 'inherit' })
    
    // Push schema to database
    console.log('🗄️ Pushing schema to database...')
    execSync('npx prisma db push', { stdio: 'inherit' })
    
    console.log('✅ Database migrations completed successfully!')
  } catch (error) {
    console.error('❌ Migration failed:', error)
    process.exit(1)
  }
}

const seed = async () => {
  try {
    // Import Prisma client after generation
    const { PrismaClient } = require('@prisma/client')
    const bcrypt = require('bcryptjs')
    
    const prisma = new PrismaClient()
    
    console.log('🌱 Seeding database with initial data...')
    
    // Create default admin user
    const hashedPassword = await bcrypt.hash('admin123', 12)
    
    const adminUser = await prisma.user.upsert({
      where: { email: 'admin@eventmanager.com' },
      update: {},
      create: {
        name: 'System Administrator',
        preferredName: 'Admin',
        email: 'admin@eventmanager.com',
        password: hashedPassword,
        role: 'ORGANIZER',
        gender: 'Other',
        pronouns: 'they/them'
      }
    })
    
    console.log('✅ Admin user created:', adminUser.email)
    
    // Create sample event
    const sampleEvent = await prisma.event.create({
      data: {
        name: 'Sample Event 2024',
        description: 'A sample event for testing the system',
        startDate: new Date('2024-01-01'),
        endDate: new Date('2024-01-02')
      }
    })
    
    console.log('✅ Sample event created:', sampleEvent.name)
    
    // Create sample contest
    const sampleContest = await prisma.contest.create({
      data: {
        eventId: sampleEvent.id,
        name: 'Sample Contest',
        description: 'A sample contest for testing'
      }
    })
    
    console.log('✅ Sample contest created:', sampleContest.name)
    
    // Create sample category
    const sampleCategory = await prisma.category.create({
      data: {
        contestId: sampleContest.id,
        name: 'Sample Category',
        description: 'A sample category for testing',
        scoreCap: 100
      }
    })
    
    console.log('✅ Sample category created:', sampleCategory.name)
    
    // Create sample contestants
    const contestants = await Promise.all([
      prisma.contestant.create({
        data: {
          name: 'John Doe',
          email: 'john@example.com',
          gender: 'Male',
          pronouns: 'he/him',
          contestantNumber: 1,
          bio: 'Sample contestant 1'
        }
      }),
      prisma.contestant.create({
        data: {
          name: 'Jane Smith',
          email: 'jane@example.com',
          gender: 'Female',
          pronouns: 'she/her',
          contestantNumber: 2,
          bio: 'Sample contestant 2'
        }
      })
    ])
    
    console.log('✅ Sample contestants created:', contestants.length)
    
    // Create sample judges
    const judges = await Promise.all([
      prisma.judge.create({
        data: {
          name: 'Judge Johnson',
          email: 'judge1@example.com',
          gender: 'Male',
          pronouns: 'he/him',
          isHeadJudge: true,
          bio: 'Head judge'
        }
      }),
      prisma.judge.create({
        data: {
          name: 'Judge Williams',
          email: 'judge2@example.com',
          gender: 'Female',
          pronouns: 'she/her',
          isHeadJudge: false,
          bio: 'Assistant judge'
        }
      })
    ])
    
    console.log('✅ Sample judges created:', judges.length)
    
    // Create sample criteria
    const criteria = await Promise.all([
      prisma.criterion.create({
        data: {
          categoryId: sampleCategory.id,
          name: 'Technical Skill',
          maxScore: 40
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: sampleCategory.id,
          name: 'Presentation',
          maxScore: 30
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: sampleCategory.id,
          name: 'Creativity',
          maxScore: 30
        }
      })
    ])
    
    console.log('✅ Sample criteria created:', criteria.length)
    
    // Add contestants to category
    await Promise.all([
      prisma.categoryContestant.create({
        data: {
          categoryId: sampleCategory.id,
          contestantId: contestants[0].id
        }
      }),
      prisma.categoryContestant.create({
        data: {
          categoryId: sampleCategory.id,
          contestantId: contestants[1].id
        }
      })
    ])
    
    // Add judges to category
    await Promise.all([
      prisma.categoryJudge.create({
        data: {
          categoryId: sampleCategory.id,
          judgeId: judges[0].id
        }
      }),
      prisma.categoryJudge.create({
        data: {
          categoryId: sampleCategory.id,
          judgeId: judges[1].id
        }
      })
    ])
    
    // Create system settings
    const settings = await Promise.all([
      prisma.systemSetting.create({
        data: {
          settingKey: 'app_name',
          settingValue: 'Event Manager',
          description: 'Application name'
        }
      }),
      prisma.systemSetting.create({
        data: {
          settingKey: 'app_version',
          settingValue: '1.0.0',
          description: 'Application version'
        }
      }),
      prisma.systemSetting.create({
        data: {
          settingKey: 'max_file_size',
          settingValue: '10485760',
          description: 'Maximum file upload size in bytes'
        }
      })
    ])
    
    console.log('✅ System settings created:', settings.length)
    
    console.log('🎉 Database seeding completed successfully!')
    console.log('')
    console.log('📋 Default login credentials:')
    console.log('   Email: admin@eventmanager.com')
    console.log('   Password: admin123')
    
    await prisma.$disconnect()
    
  } catch (error) {
    console.error('❌ Seeding failed:', error)
    process.exit(1)
  }
}

const main = async () => {
  try {
    await migrate()
    await seed()
  } catch (error) {
    console.error('❌ Setup failed:', error)
    process.exit(1)
  }
}

if (require.main === module) {
  main()
}

module.exports = { migrate, seed }