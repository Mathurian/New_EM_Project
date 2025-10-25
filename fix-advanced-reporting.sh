#!/bin/bash

# Fix for advancedReportingController.js syntax error
APP_DIR="/var/www/event-manager"

echo "Fixing advancedReportingController.js syntax error..."

# Create a simplified version of advancedReportingController.js that avoids the template literal issues
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
      totalScores: 0, // Would need to calculate from scores
      averageScore: 0 // Would need to calculate from scores
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
          judgeCount: category.assignments.length,
          contestants: category.contestants.map(c => ({
            id: c.contestant.id,
            name: `${c.contestant.user.firstName} ${c.contestant.user.lastName}`,
            email: c.contestant.user.email
          })),
          judges: category.assignments.map(a => ({
            id: a.judge.id,
            name: `${a.judge.user.firstName} ${a.judge.user.lastName}`,
            email: a.judge.user.email
          }))
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
                    user: true,
                    scores: {
                      include: {
                        criterion: true,
                        judge: {
                          include: {
                            user: true
                          }
                        }
                      }
                    }
                  }
                }
              }
            },
            criteria: true
          }
        }
      }
    })

    if (!contest) {
      return res.status(404).json({ error: 'Contest not found' })
    }

    // Calculate results for each category
    const categoryResults = contest.categories.map(category => {
      const contestants = category.contestants.map(c => {
        const scores = c.contestant.scores.filter(s => 
          s.criterion.categoryId === category.id
        )
        
        const totalScore = scores.reduce((sum, score) => sum + score.score, 0)
        const averageScore = scores.length > 0 ? totalScore / scores.length : 0

        return {
          id: c.contestant.id,
          name: `${c.contestant.user.firstName} ${c.contestant.user.lastName}`,
          scores: scores.map(s => ({
            criterion: s.criterion.name,
            score: s.score,
            judge: `${s.judge.user.firstName} ${s.judge.user.lastName}`
          })),
          totalScore,
          averageScore,
          rank: 0 // Would need to calculate ranking
        }
      })

      // Sort by average score (descending)
      contestants.sort((a, b) => b.averageScore - a.averageScore)
      
      // Assign ranks
      contestants.forEach((contestant, index) => {
        contestant.rank = index + 1
      })

      return {
        id: category.id,
        name: category.name,
        description: category.description,
        contestants
      }
    })

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
      categories: categoryResults,
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
        },
        contestant: {
          include: {
            user: true
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
          averageScore: 0,
          scoreDistribution: {},
          categories: new Set(),
          events: new Set()
        }
      }

      judgeStats[judgeId].totalScores++
      judgeStats[judgeId].categories.add(score.criterion.category.name)
      judgeStats[judgeId].events.add(score.criterion.category.contest.event.name)
      
      // Track score distribution
      const scoreRange = Math.floor(score.score / 10) * 10
      judgeStats[judgeId].scoreDistribution[scoreRange] = 
        (judgeStats[judgeId].scoreDistribution[scoreRange] || 0) + 1
    })

    // Calculate averages and convert sets to arrays
    const judgePerformance = Object.values(judgeStats).map(judge => {
      const scoresForJudge = scores.filter(s => s.judge.id === judge.id)
      const totalScore = scoresForJudge.reduce((sum, score) => sum + score.score, 0)
      
      return {
        ...judge,
        averageScore: scoresForJudge.length > 0 ? totalScore / scoresForJudge.length : 0,
        categories: Array.from(judge.categories),
        events: Array.from(judge.events)
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

echo "Fixed advancedReportingController.js syntax error!"
echo "Restarting event-manager service..."

# Restart the service
sudo systemctl restart event-manager

echo "Service restarted. Checking status..."
sudo systemctl status event-manager --no-pager -l

echo "Fix completed!"
