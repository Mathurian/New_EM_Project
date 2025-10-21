const { PrismaClient } = require('@prisma/client')
const bcrypt = require('bcryptjs')

const prisma = new PrismaClient()

const migrate = async () => {
  console.log('🔄 Running database migrations...')
  
  try {
    // Generate Prisma client
    console.log('📦 Generating Prisma client...')
    const { execSync } = require('child_process')
    
    try {
      execSync('npx prisma generate', { stdio: 'inherit' })
    } catch (error) {
      console.log('Falling back to direct prisma binary...')
      execSync('node node_modules/.bin/prisma generate', { stdio: 'inherit' })
    }
    
    // Push schema to database
    console.log('🗄️ Pushing schema to database...')
    try {
      execSync('npx prisma db push', { stdio: 'inherit' })
    } catch (error) {
      console.log('Falling back to direct prisma binary...')
      execSync('node node_modules/.bin/prisma db push', { stdio: 'inherit' })
    }
    
    console.log('✅ Database migrations completed successfully!')
  } catch (error) {
    console.error('❌ Migration failed:', error)
    throw error
  }
}

const seed = async () => {
  console.log('🌱 Seeding database with initial data...')
  
  try {
    // Create admin user
    const adminPassword = await bcrypt.hash('admin123', 12)
    const adminUser = await prisma.user.upsert({
      where: { email: 'admin@eventmanager.com' },
      update: {},
      create: {
        name: 'System Administrator',
        email: 'admin@eventmanager.com',
        password: adminPassword,
        role: 'ORGANIZER'
      }
    })
    console.log('✅ Admin user created:', adminUser.email)
    
    // Create sample contestants (use upsert to avoid duplicates)
    const contestants = await Promise.all([
      prisma.contestant.upsert({
        where: { email: 'john@example.com' },
        update: {},
        create: {
          name: 'John Doe',
          email: 'john@example.com',
          gender: 'Male',
          pronouns: 'he/him',
          contestantNumber: 1,
          bio: 'Sample contestant 1'
        }
      }),
      prisma.contestant.upsert({
        where: { email: 'jane@example.com' },
        update: {},
        create: {
          name: 'Jane Smith',
          email: 'jane@example.com',
          gender: 'Female',
          pronouns: 'she/her',
          contestantNumber: 2,
          bio: 'Sample contestant 2'
        }
      }),
      prisma.contestant.upsert({
        where: { email: 'mike@example.com' },
        update: {},
        create: {
          name: 'Mike Johnson',
          email: 'mike@example.com',
          gender: 'Male',
          pronouns: 'he/him',
          contestantNumber: 3,
          bio: 'Sample contestant 3'
        }
      })
    ])
    console.log('✅ Sample contestants created')
    
    // Create sample judges (use upsert to avoid duplicates)
    const judges = await Promise.all([
      prisma.judge.upsert({
        where: { email: 'judge1@example.com' },
        update: {},
        create: {
          name: 'Judge Johnson',
          email: 'judge1@example.com',
          gender: 'Male',
          pronouns: 'he/him',
          isHeadJudge: true,
          bio: 'Head judge with extensive experience'
        }
      }),
      prisma.judge.upsert({
        where: { email: 'judge2@example.com' },
        update: {},
        create: {
          name: 'Judge Williams',
          email: 'judge2@example.com',
          gender: 'Female',
          pronouns: 'she/her',
          isHeadJudge: false,
          bio: 'Assistant judge specializing in technical criteria'
        }
      }),
      prisma.judge.upsert({
        where: { email: 'judge3@example.com' },
        update: {},
        create: {
          name: 'Judge Brown',
          email: 'judge3@example.com',
          gender: 'Non-binary',
          pronouns: 'they/them',
          isHeadJudge: false,
          bio: 'Creative judge with focus on artistic expression'
        }
      })
    ])
    console.log('✅ Sample judges created')
    
    // Create users for contestants and judges
    const contestantUsers = await Promise.all([
      prisma.user.upsert({
        where: { email: 'john@example.com' },
        update: {},
        create: {
          name: 'John Doe',
          email: 'john@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'CONTESTANT',
          contestantId: contestants[0].id
        }
      }),
      prisma.user.upsert({
        where: { email: 'jane@example.com' },
        update: {},
        create: {
          name: 'Jane Smith',
          email: 'jane@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'CONTESTANT',
          contestantId: contestants[1].id
        }
      }),
      prisma.user.upsert({
        where: { email: 'mike@example.com' },
        update: {},
        create: {
          name: 'Mike Johnson',
          email: 'mike@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'CONTESTANT',
          contestantId: contestants[2].id
        }
      })
    ])
    
    const judgeUsers = await Promise.all([
      prisma.user.upsert({
        where: { email: 'judge1@example.com' },
        update: {},
        create: {
          name: 'Judge Johnson',
          email: 'judge1@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'JUDGE',
          judgeId: judges[0].id
        }
      }),
      prisma.user.upsert({
        where: { email: 'judge2@example.com' },
        update: {},
        create: {
          name: 'Judge Williams',
          email: 'judge2@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'JUDGE',
          judgeId: judges[1].id
        }
      }),
      prisma.user.upsert({
        where: { email: 'judge3@example.com' },
        update: {},
        create: {
          name: 'Judge Brown',
          email: 'judge3@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'JUDGE',
          judgeId: judges[2].id
        }
      })
    ])
    
    // Create additional role users
    await Promise.all([
      prisma.user.upsert({
        where: { email: 'tally@example.com' },
        update: {},
        create: {
          name: 'Tally Master',
          email: 'tally@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'TALLY_MASTER'
        }
      }),
      prisma.user.upsert({
        where: { email: 'auditor@example.com' },
        update: {},
        create: {
          name: 'Auditor',
          email: 'auditor@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'AUDITOR'
        }
      }),
      prisma.user.upsert({
        where: { email: 'emcee@example.com' },
        update: {},
        create: {
          name: 'Emcee',
          email: 'emcee@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'EMCEE'
        }
      }),
      prisma.user.upsert({
        where: { email: 'board@example.com' },
        update: {},
        create: {
          name: 'Board Member',
          email: 'board@example.com',
          password: await bcrypt.hash('password123', 12),
          role: 'BOARD'
        }
      })
    ])
    console.log('✅ Sample users created')
    
    // Create sample event
    const event = await prisma.event.upsert({
      where: { name: 'Sample Event 2024' },
      update: {},
      create: {
        name: 'Sample Event 2024',
        description: 'A comprehensive sample event showcasing all features',
        startDate: new Date('2024-01-15'),
        endDate: new Date('2024-01-17')
      }
    })
    console.log('✅ Sample event created:', event.name)
    
    // Create sample contest
    const contest = await prisma.contest.upsert({
      where: { 
        eventId_name: {
          eventId: event.id,
          name: 'Sample Contest'
        }
      },
      update: {},
      create: {
        eventId: event.id,
        name: 'Sample Contest',
        description: 'A sample contest with multiple categories'
      }
    })
    console.log('✅ Sample contest created:', contest.name)
    
    // Create sample categories
    const categories = await Promise.all([
      prisma.category.upsert({
        where: {
          contestId_name: {
            contestId: contest.id,
            name: 'Technical Excellence'
          }
        },
        update: {},
        create: {
          contestId: contest.id,
          name: 'Technical Excellence',
          description: 'Judging technical skills and execution',
          scoreCap: 100.0
        }
      }),
      prisma.category.upsert({
        where: {
          contestId_name: {
            contestId: contest.id,
            name: 'Creative Expression'
          }
        },
        update: {},
        create: {
          contestId: contest.id,
          name: 'Creative Expression',
          description: 'Judging creativity and artistic expression',
          scoreCap: 100.0
        }
      }),
      prisma.category.upsert({
        where: {
          contestId_name: {
            contestId: contest.id,
            name: 'Presentation'
          }
        },
        update: {},
        create: {
          contestId: contest.id,
          name: 'Presentation',
          description: 'Judging presentation and communication skills',
          scoreCap: 100.0
        }
      })
    ])
    console.log('✅ Sample categories created')
    
    // Create criteria for each category
    const criteria = await Promise.all([
      // Technical Excellence criteria
      prisma.criterion.create({
        data: {
          categoryId: categories[0].id,
          name: 'Technical Accuracy',
          maxScore: 40
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: categories[0].id,
          name: 'Innovation',
          maxScore: 30
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: categories[0].id,
          name: 'Problem Solving',
          maxScore: 30
        }
      }),
      // Creative Expression criteria
      prisma.criterion.create({
        data: {
          categoryId: categories[1].id,
          name: 'Originality',
          maxScore: 35
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: categories[1].id,
          name: 'Artistic Merit',
          maxScore: 35
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: categories[1].id,
          name: 'Emotional Impact',
          maxScore: 30
        }
      }),
      // Presentation criteria
      prisma.criterion.create({
        data: {
          categoryId: categories[2].id,
          name: 'Clarity',
          maxScore: 40
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: categories[2].id,
          name: 'Engagement',
          maxScore: 30
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: categories[2].id,
          name: 'Professionalism',
          maxScore: 30
        }
      })
    ])
    console.log('✅ Sample criteria created')
    
    // Assign contestants to contest
    await Promise.all(
      contestants.map(contestant =>
        prisma.contestContestant.upsert({
          where: {
            contestId_contestantId: {
              contestId: contest.id,
              contestantId: contestant.id
            }
          },
          update: {},
          create: {
            contestId: contest.id,
            contestantId: contestant.id
          }
        })
      )
    )
    
    // Assign judges to contest
    await Promise.all(
      judges.map(judge =>
        prisma.contestJudge.upsert({
          where: {
            contestId_judgeId: {
              contestId: contest.id,
              judgeId: judge.id
            }
          },
          update: {},
          create: {
            contestId: contest.id,
            judgeId: judge.id
          }
        })
      )
    )
    
    // Assign contestants and judges to categories
    for (const category of categories) {
      // Assign all contestants to each category
      await Promise.all(
        contestants.map(contestant =>
          prisma.categoryContestant.upsert({
            where: {
              categoryId_contestantId: {
                categoryId: category.id,
                contestantId: contestant.id
              }
            },
            update: {},
            create: {
              categoryId: category.id,
              contestantId: contestant.id
            }
          })
        )
      )
      
      // Assign all judges to each category
      await Promise.all(
        judges.map(judge =>
          prisma.categoryJudge.upsert({
            where: {
              categoryId_judgeId: {
                categoryId: category.id,
                judgeId: judge.id
              }
            },
            update: {},
            create: {
              categoryId: category.id,
              judgeId: judge.id
            }
          })
        )
      )
    }
    console.log('✅ Contest assignments created')
    
    // Create system settings (use upsert to avoid duplicates)
    const settings = await Promise.all([
      prisma.systemSetting.upsert({
        where: { settingKey: 'app_name' },
        update: {},
        create: {
          settingKey: 'app_name',
          settingValue: 'Event Manager',
          description: 'Application name'
        }
      }),
      prisma.systemSetting.upsert({
        where: { settingKey: 'app_version' },
        update: {},
        create: {
          settingKey: 'app_version',
          settingValue: '1.0.0',
          description: 'Application version'
        }
      }),
      prisma.systemSetting.upsert({
        where: { settingKey: 'max_file_size' },
        update: {},
        create: {
          settingKey: 'max_file_size',
          settingValue: '10485760',
          description: 'Maximum file upload size in bytes'
        }
      }),
      prisma.systemSetting.upsert({
        where: { settingKey: 'default_score_cap' },
        update: {},
        create: {
          settingKey: 'default_score_cap',
          settingValue: '100',
          description: 'Default maximum score for categories'
        }
      }),
      prisma.systemSetting.upsert({
        where: { settingKey: 'email_enabled' },
        update: {},
        create: {
          settingKey: 'email_enabled',
          settingValue: 'false',
          description: 'Whether email notifications are enabled'
        }
      })
    ])
    console.log('✅ System settings created')
    
    // Create emcee scripts
    const emceeScripts = await Promise.all([
      prisma.emceeScript.create({
        data: {
          eventId: event.id,
          title: 'Welcome Announcement',
          content: 'Welcome to our annual event! We have an exciting lineup of contestants ready to showcase their talents.',
          order: 1
        }
      }),
      prisma.emceeScript.create({
        data: {
          eventId: event.id,
          title: 'Contest Introduction',
          content: 'Today we will be judging contestants in three categories: Technical Excellence, Creative Expression, and Presentation.',
          order: 2
        }
      }),
      prisma.emceeScript.create({
        data: {
          eventId: event.id,
          title: 'Results Announcement',
          content: 'The judges have completed their evaluations. Results will be announced shortly.',
          order: 3
        }
      })
    ])
    console.log('✅ Emcee scripts created')
    
    // Create category templates
    const templates = await Promise.all([
      prisma.categoryTemplate.create({
        data: {
          name: 'Technical Contest Template',
          description: 'Standard template for technical competitions',
          criteria: {
            create: [
              { name: 'Technical Accuracy', maxScore: 40 },
              { name: 'Innovation', maxScore: 30 },
              { name: 'Problem Solving', maxScore: 30 }
            ]
          }
        }
      }),
      prisma.categoryTemplate.create({
        data: {
          name: 'Creative Contest Template',
          description: 'Standard template for creative competitions',
          criteria: {
            create: [
              { name: 'Originality', maxScore: 35 },
              { name: 'Artistic Merit', maxScore: 35 },
              { name: 'Emotional Impact', maxScore: 30 }
            ]
          }
        }
      })
    ])
    console.log('✅ Category templates created')
    
    console.log('✅ Database seeding completed successfully!')
    
  } catch (error) {
    console.error('❌ Seeding failed:', error)
    throw error
  }
}

const main = async () => {
  try {
    await migrate()
    await seed()
    console.log('🎉 Database setup completed successfully!')
  } catch (error) {
    console.error('💥 Database setup failed:', error)
    process.exit(1)
  } finally {
    await prisma.$disconnect()
  }
}

if (require.main === module) {
  main()
}

module.exports = { migrate, seed }