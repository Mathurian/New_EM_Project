const { seed } = require('./migrate')

const main = async () => {
  try {
    await seed()
  } catch (error) {
    console.error('❌ Seeding failed:', error)
    process.exit(1)
  }
}

if (require.main === module) {
  main()
}

module.exports = { seed }
