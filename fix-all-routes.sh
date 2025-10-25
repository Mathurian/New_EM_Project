#!/bin/bash

# Complete fix for all route callback issues
APP_DIR="/var/www/event-manager"

echo "Fixing all route callback issues..."

# Fix advancedReportingController.js - simplified version
cat > "$APP_DIR/src/controllers/advancedReportingController.js" << 'EOF'
const { PrismaClient } = require('@prisma/client')
const fs = require('fs').promises
const path = require('path')

const prisma = new PrismaClient()

// Generate comprehensive event report
const generateEventReport = async (req, res) => {
  try {
    const { eventId, format = 'json' } = req.query

    if (!eventId) {
      return res.status(400).json({ error: 'Event ID is required' })
    }

    // Get event data with all related information
    const event = await prisma.event.findUnique({
      where: { id: eventId },
      include: {
        contests: {
          include: {
            categories: {
              include: {
                contestants: {
                  include: {
                    contestant: {
                      include: {
                        user: true
                      }
                    }
                  }
                },
                assignments: {
                  include: {
                    judge: {
                      include: {
                        user: true
                      }
                    }
                  }
                },
                criteria: true
              }
            }
          }
        },
        organizer: {
          include: {
            user: true
          }
        }
      }
    })

    if (!event) {
      return res.status(404).json({ error: 'Event not found' })
    }

    // Calculate statistics
    const stats = {
      totalContests: event.contests.length,
      totalCategories: event.contests.reduce((total, contest) => total + contest.categories.length, 0),
      totalContestants: event.contests.reduce((total, contest) => 
        total + contest.categories.reduce((catTotal, category) => 
          catTotal + category.contestants.length, 0), 0),
      totalJudges: event.contests.reduce((total, contest) => 
        total + contest.categories.reduce((catTotal, category) => 
          catTotal + category.assignments.length, 0), 0),
      totalScores: 0,
      averageScore: 0
    }

    // Return report data
    const reportData = {
      event: {
        id: event.id,
        name: event.name,
        description: event.description,
        startDate: event.startDate,
        endDate: event.endDate,
        location: event.location,
        status: event.status,
        organizer: event.organizer?.user ? {
          name: `${event.organizer.user.firstName} ${event.organizer.user.lastName}`,
          email: event.organizer.user.email
        } : null
      },
      statistics: stats,
      contests: event.contests.map(contest => ({
        id: contest.id,
        name: contest.name,
        description: contest.description,
        status: contest.status,
        categories: contest.categories.map(category => ({
          id: category.id,
          name: category.name,
          description: category.description,
          contestantCount: category.contestants.length,
          judgeCount: category.assignments.length
        }))
      })),
      generatedAt: new Date().toISOString(),
      generatedBy: req.user.id
    }

    res.json(reportData)
  } catch (error) {
    console.error('Generate event report error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

// Generate contest results report
const generateContestResultsReport = async (req, res) => {
  try {
    const { contestId } = req.query

    if (!contestId) {
      return res.status(400).json({ error: 'Contest ID is required' })
    }

    const contest = await prisma.contest.findUnique({
      where: { id: contestId },
      include: {
        event: true,
        categories: {
          include: {
            contestants: {
              include: {
                contestant: {
                  include: {
                    user: true
                  }
                }
              }
            }
          }
        }
      }
    })

    if (!contest) {
      return res.status(404).json({ error: 'Contest not found' })
    }

    const reportData = {
      contest: {
        id: contest.id,
        name: contest.name,
        description: contest.description,
        event: {
          id: contest.event.id,
          name: contest.event.name
        }
      },
      categories: contest.categories.map(category => ({
        id: category.id,
        name: category.name,
        description: category.description,
        contestantCount: category.contestants.length
      })),
      generatedAt: new Date().toISOString(),
      generatedBy: req.user.id
    }

    res.json(reportData)
  } catch (error) {
    console.error('Generate contest results report error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

// Generate judge performance report
const generateJudgePerformanceReport = async (req, res) => {
  try {
    const { eventId, contestId, categoryId } = req.query

    let whereClause = {}
    if (categoryId) {
      whereClause.categoryId = categoryId
    } else if (contestId) {
      whereClause.category = { contestId }
    } else if (eventId) {
      whereClause.category = { contest: { eventId } }
    }

    const scores = await prisma.score.findMany({
      where: whereClause,
      include: {
        judge: {
          include: {
            user: true
          }
        },
        criterion: {
          include: {
            category: {
              include: {
                contest: {
                  include: {
                    event: true
                  }
                }
              }
            }
          }
        }
      }
    })

    // Group scores by judge
    const judgeStats = {}
    scores.forEach(score => {
      const judgeId = score.judge.id
      const judgeName = `${score.judge.user.firstName} ${score.judge.user.lastName}`
      
      if (!judgeStats[judgeId]) {
        judgeStats[judgeId] = {
          id: judgeId,
          name: judgeName,
          email: score.judge.user.email,
          totalScores: 0,
          averageScore: 0
        }
      }

      judgeStats[judgeId].totalScores++
    })

    // Calculate averages
    const judgePerformance = Object.values(judgeStats).map(judge => {
      const scoresForJudge = scores.filter(s => s.judge.id === judge.id)
      const totalScore = scoresForJudge.reduce((sum, score) => sum + score.score, 0)
      
      return {
        ...judge,
        averageScore: scoresForJudge.length > 0 ? totalScore / scoresForJudge.length : 0
      }
    })

    const reportData = {
      judges: judgePerformance,
      summary: {
        totalJudges: judgePerformance.length,
        totalScores: scores.length,
        averageScore: scores.length > 0 ? 
          scores.reduce((sum, score) => sum + score.score, 0) / scores.length : 0
      },
      generatedAt: new Date().toISOString(),
      generatedBy: req.user.id
    }

    res.json(reportData)
  } catch (error) {
    console.error('Generate judge performance report error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

// Generate system analytics report
const generateSystemAnalyticsReport = async (req, res) => {
  try {
    const [
      totalEvents,
      totalContests,
      totalCategories,
      totalContestants,
      totalJudges,
      totalScores,
      totalUsers
    ] = await Promise.all([
      prisma.event.count(),
      prisma.contest.count(),
      prisma.category.count(),
      prisma.contestant.count(),
      prisma.judge.count(),
      prisma.score.count(),
      prisma.user.count()
    ])

    const recentEvents = await prisma.event.findMany({
      take: 5,
      orderBy: { createdAt: 'desc' },
      include: {
        organizer: {
          include: {
            user: true
          }
        }
      }
    })

    const reportData = {
      overview: {
        totalEvents,
        totalContests,
        totalCategories,
        totalContestants,
        totalJudges,
        totalScores,
        totalUsers
      },
      recentActivity: {
        recentEvents: recentEvents.map(event => ({
          id: event.id,
          name: event.name,
          createdAt: event.createdAt,
          organizer: event.organizer?.user ? 
            `${event.organizer.user.firstName} ${event.organizer.user.lastName}` : 'Unknown'
        }))
      },
      generatedAt: new Date().toISOString(),
      generatedBy: req.user.id
    }

    res.json(reportData)
  } catch (error) {
    console.error('Generate system analytics report error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

module.exports = {
  generateEventReport,
  generateContestResultsReport,
  generateJudgePerformanceReport,
  generateSystemAnalyticsReport
}
EOF

# Fix advancedReportingRoutes.js
cat > "$APP_DIR/src/routes/advancedReportingRoutes.js" << 'EOF'
const express = require('express')
const { 
  generateEventReport,
  generateJudgePerformanceReport,
  generateSystemAnalyticsReport,
  generateContestResultsReport
} = require('../controllers/advancedReportingController')
const { authenticateToken, requireRole } = require('../middleware/auth')
const { logActivity } = require('../middleware/errorHandler')

const router = express.Router()

// Apply authentication to all routes
router.use(authenticateToken)

// Advanced reporting endpoints
router.get('/event', requireRole(['ORGANIZER', 'BOARD', 'ADMIN']), logActivity('GENERATE_EVENT_REPORT', 'REPORT'), generateEventReport)
router.get('/judge-performance', requireRole(['ORGANIZER', 'BOARD', 'ADMIN']), logActivity('GENERATE_JUDGE_REPORT', 'REPORT'), generateJudgePerformanceReport)
router.get('/system-analytics', requireRole(['ADMIN']), logActivity('GENERATE_SYSTEM_REPORT', 'REPORT'), generateSystemAnalyticsReport)
router.get('/contest-results', requireRole(['ADMIN', 'BOARD', 'EMCEE']), logActivity('GENERATE_CONTEST_RESULTS_REPORT', 'REPORT'), generateContestResultsReport)

module.exports = router
EOF

echo "Fixed all route callback issues!"
echo "Restarting event-manager service..."

# Restart the service
sudo systemctl restart event-manager

echo "Service restarted. Checking status..."
sudo systemctl status event-manager --no-pager -l

echo "Fix completed!"
