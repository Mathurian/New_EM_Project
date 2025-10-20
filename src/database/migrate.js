const { PrismaClient } = require('@prisma/client');
const bcrypt = require('bcryptjs');

const prisma = new PrismaClient();

// Database migration script
const migrate = async () => {
  try {
    console.log('Starting database migration...');
    
    // Run Prisma migrations
    const { execSync } = require('child_process');
    execSync('npx prisma migrate deploy', { stdio: 'inherit' });
    
    console.log('Database migration completed successfully');
  } catch (error) {
    console.error('Migration failed:', error);
    process.exit(1);
  }
};

// Database seeding script
const seed = async () => {
  try {
    console.log('Starting database seeding...');

    // Create default organizer user
    const organizerPassword = await bcrypt.hash('admin123', 12);
    const organizer = await prisma.user.upsert({
      where: { email: 'admin@eventmanager.com' },
      update: {},
      create: {
        name: 'System Administrator',
        email: 'admin@eventmanager.com',
        passwordHash: organizerPassword,
        role: 'ORGANIZER',
        preferredName: 'Admin'
      }
    });

    // Create sample event
    const sampleEvent = await prisma.event.upsert({
      where: { id: 'sample-event-1' },
      update: {},
      create: {
        id: 'sample-event-1',
        name: 'Sample Event 2024',
        startDate: new Date('2024-06-01'),
        endDate: new Date('2024-06-03')
      }
    });

    // Create sample contest
    const sampleContest = await prisma.contest.upsert({
      where: { id: 'sample-contest-1' },
      update: {},
      create: {
        id: 'sample-contest-1',
        eventId: sampleEvent.id,
        name: 'Sample Contest',
        description: 'A sample contest for demonstration purposes'
      }
    });

    // Create sample category
    const sampleCategory = await prisma.category.upsert({
      where: { id: 'sample-category-1' },
      update: {},
      create: {
        id: 'sample-category-1',
        contestId: sampleContest.id,
        name: 'Sample Category',
        description: 'A sample category for demonstration purposes',
        scoreCap: 100
      }
    });

    // Create sample contestants
    const contestants = [];
    for (let i = 1; i <= 5; i++) {
      const contestant = await prisma.contestant.upsert({
        where: { id: `sample-contestant-${i}` },
        update: {},
        create: {
          id: `sample-contestant-${i}`,
          name: `Contestant ${i}`,
          email: `contestant${i}@example.com`,
          contestantNumber: i,
          bio: `This is contestant ${i}'s bio`
        }
      });
      contestants.push(contestant);
    }

    // Create sample judges
    const judges = [];
    for (let i = 1; i <= 3; i++) {
      const judge = await prisma.judge.upsert({
        where: { id: `sample-judge-${i}` },
        update: {},
        create: {
          id: `sample-judge-${i}`,
          name: `Judge ${i}`,
          email: `judge${i}@example.com`,
          isHeadJudge: i === 1
        }
      });
      judges.push(judge);
    }

    // Create sample criteria
    const criteria = [];
    const criterionNames = ['Performance', 'Technique', 'Presentation', 'Creativity'];
    for (let i = 0; i < criterionNames.length; i++) {
      const criterion = await prisma.criterion.upsert({
        where: { id: `sample-criterion-${i + 1}` },
        update: {},
        create: {
          id: `sample-criterion-${i + 1}`,
          categoryId: sampleCategory.id,
          name: criterionNames[i],
          maxScore: 25
        }
      });
      criteria.push(criterion);
    }

    // Link contestants to category
    for (const contestant of contestants) {
      await prisma.categoryContestant.upsert({
        where: {
          categoryId_contestantId: {
            categoryId: sampleCategory.id,
            contestantId: contestant.id
          }
        },
        update: {},
        create: {
          categoryId: sampleCategory.id,
          contestantId: contestant.id
        }
      });
    }

    // Link judges to category
    for (const judge of judges) {
      await prisma.categoryJudge.upsert({
        where: {
          categoryId_judgeId: {
            categoryId: sampleCategory.id,
            judgeId: judge.id
          }
        },
        update: {},
        create: {
          categoryId: sampleCategory.id,
          judgeId: judge.id
        }
      });
    }

    // Create system settings
    const defaultSettings = [
      { key: 'app_name', value: 'Event Manager', description: 'Application name' },
      { key: 'app_version', value: '1.0.0', description: 'Application version' },
      { key: 'max_file_size', value: '10485760', description: 'Maximum file upload size in bytes' },
      { key: 'session_timeout', value: '1800000', description: 'Session timeout in milliseconds' },
      { key: 'enable_registration', value: 'true', description: 'Allow new user registration' },
      { key: 'maintenance_mode', value: 'false', description: 'Enable maintenance mode' }
    ];

    for (const setting of defaultSettings) {
      await prisma.systemSetting.upsert({
        where: { settingKey: setting.key },
        update: {},
        create: {
          settingKey: setting.key,
          settingValue: setting.value,
          description: setting.description,
          updatedById: organizer.id
        }
      });
    }

    console.log('Database seeding completed successfully');
    console.log('Default credentials:');
    console.log('Email: admin@eventmanager.com');
    console.log('Password: admin123');
  } catch (error) {
    console.error('Seeding failed:', error);
    process.exit(1);
  }
};

// Main execution
const main = async () => {
  const command = process.argv[2];
  
  switch (command) {
    case 'migrate':
      await migrate();
      break;
    case 'seed':
      await seed();
      break;
    case 'reset':
      console.log('Resetting database...');
      await prisma.$executeRaw`DROP SCHEMA IF EXISTS public CASCADE`;
      await prisma.$executeRaw`CREATE SCHEMA public`;
      await migrate();
      await seed();
      break;
    default:
      console.log('Usage: node migrate.js [migrate|seed|reset]');
      process.exit(1);
  }
  
  await prisma.$disconnect();
};

if (require.main === module) {
  main().catch((error) => {
    console.error('Script failed:', error);
    process.exit(1);
  });
}

module.exports = { migrate, seed };
