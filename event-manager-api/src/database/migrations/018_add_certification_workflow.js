/**
 * Add certification workflow fields to scores table
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function up(knex) {
  await knex.schema.alterTable('scores', (table) => {
    // Add certification status field
    table.enum('score_status', ['draft', 'judge_certified', 'tally_verified', 'auditor_certified'])
      .defaultTo('draft')
      .notNullable()
    
    // Add certification timestamps
    table.timestamp('judge_certified_at').nullable()
    table.timestamp('tally_verified_at').nullable()
    table.timestamp('auditor_certified_at').nullable()
    
    // Add discrepancy resolution fields
    table.enum('discrepancy_status', ['none', 'pending', 'approved', 'rejected'])
      .defaultTo('none')
      .notNullable()
    
    // Multi-signature approval tracking
    table.boolean('tally_master_approved').defaultTo(false)
    table.boolean('auditor_approved').defaultTo(false)
    table.boolean('board_organizer_approved').defaultTo(false)
    table.timestamp('discrepancy_approved_at').nullable()
    
    // Add indexes for performance
    table.index(['score_status'])
    table.index(['discrepancy_status'])
    table.index(['judge_certified_at'])
    table.index(['tally_verified_at'])
    table.index(['auditor_certified_at'])
  })
}

/**
 * @param { import("knex").Knex } knex
 * @returns { Promise<void> }
 */
export async function down(knex) {
  await knex.schema.alterTable('scores', (table) => {
    table.dropColumn('score_status')
    table.dropColumn('judge_certified_at')
    table.dropColumn('tally_verified_at')
    table.dropColumn('auditor_certified_at')
    table.dropColumn('discrepancy_status')
    table.dropColumn('tally_master_approved')
    table.dropColumn('auditor_approved')
    table.dropColumn('board_organizer_approved')
    table.dropColumn('discrepancy_approved_at')
  })
}
