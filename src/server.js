const express = require('express')
const cors = require('cors')
const helmet = require('helmet')
const morgan = require('morgan')
const compression = require('compression')
const rateLimit = require('express-rate-limit')
const bcrypt = require('bcryptjs')
const jwt = require('jsonwebtoken')
const multer = require('multer')
const path = require('path')
const fs = require('fs').promises
const { PrismaClient } = require('@prisma/client')
const { Server } = require('socket.io')
const http = require('http')
const nodemailer = require('nodemailer')
const winston = require('winston')
const PDFDocument = require('pdfkit')
const sharp = require('sharp')
const puppeteer = require('puppeteer')
const jsPDF = require('jspdf')
const htmlPdf = require('html-pdf-node')

const app = express()
const server = http.createServer(app)
const io = new Server(server, {
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
})

const prisma = new PrismaClient()
const PORT = process.env.PORT || 3000
const JWT_SECRET = process.env.JWT_SECRET || 'your-secret-key'

// Configure Winston logger
const logger = winston.createLogger({
  level: process.env.LOG_LEVEL || 'info',
  format: winston.format.combine(
    winston.format.timestamp(),
    winston.format.errors({ stack: true }),
    winston.format.json()
  ),
  transports: [
    new winston.transports.File({ filename: 'logs/error.log', level: 'error' }),
    new winston.transports.File({ filename: 'logs/combined.log' }),
    new winston.transports.Console({
      format: winston.format.simple()
    })
  ]
})

// Configure multer for file uploads
const storage = multer.diskStorage({
  destination: async (req, file, cb) => {
    const uploadDir = path.join(__dirname, 'uploads')
    try {
      await fs.mkdir(uploadDir, { recursive: true })
      cb(null, uploadDir)
    } catch (error) {
      cb(error)
    }
  },
  filename: (req, file, cb) => {
    const uniqueSuffix = Date.now() + '-' + Math.round(Math.random() * 1E9)
    cb(null, file.fieldname + '-' + uniqueSuffix + path.extname(file.originalname))
  }
})

const upload = multer({
  storage: storage,
  limits: {
    fileSize: parseInt(process.env.MAX_FILE_SIZE) || 10 * 1024 * 1024 // 10MB default
  },
  fileFilter: (req, file, cb) => {
    const allowedTypes = /jpeg|jpg|png|gif|pdf|doc|docx/
    const extname = allowedTypes.test(path.extname(file.originalname).toLowerCase())
    const mimetype = allowedTypes.test(file.mimetype)
    
    if (mimetype && extname) {
      return cb(null, true)
    } else {
      cb(new Error('Invalid file type. Only images and documents are allowed.'))
    }
  }
})

// Configure nodemailer
const createTransporter = () => {
  if (process.env.SMTP_HOST && process.env.SMTP_USER && process.env.SMTP_PASS) {
    return nodemailer.createTransporter({
      host: process.env.SMTP_HOST,
      port: parseInt(process.env.SMTP_PORT) || 587,
      secure: process.env.SMTP_PORT === '465',
      auth: {
        user: process.env.SMTP_USER,
        pass: process.env.SMTP_PASS
      }
    })
  }
  return null
}

// Middleware
app.use(helmet())
app.use(cors())
app.use(compression())
app.use(morgan('combined'))
app.use(express.json({ limit: '10mb' }))
app.use(express.urlencoded({ extended: true }))

// Rate limiting - Fixed configuration
const limiter = rateLimit({
  windowMs: 15 * 60 * 1000,
  max: 100,
  standardHeaders: true,
  legacyHeaders: false,
  trustProxy: true,
  skip: (req) => {
    // Skip rate limiting for health checks
    return req.path === '/health'
  },
  keyGenerator: (req) => {
    // Use IP address for rate limiting, handling proxy headers properly
    return req.ip || req.connection.remoteAddress
  }
})
app.use('/api/', limiter)

// Auth middleware
const authenticateToken = async (req, res, next) => {
  const authHeader = req.headers['authorization']
  const token = authHeader && authHeader.split(' ')[1]

  if (!token) {
    return res.status(401).json({ error: 'Access token required' })
  }

  try {
    const decoded = jwt.verify(token, JWT_SECRET)
    const user = await prisma.user.findUnique({
      where: { id: decoded.userId },
      include: { judge: true, contestant: true }
    })
    
    if (!user) {
      return res.status(401).json({ error: 'Invalid token' })
    }
    
    req.user = user
    next()
  } catch (error) {
    logger.error('Token verification failed:', error)
    return res.status(403).json({ error: 'Invalid token' })
  }
}

// Role-based authorization middleware
const requireRole = (roles) => {
  return (req, res, next) => {
    if (!req.user) {
      return res.status(401).json({ error: 'Authentication required' })
    }
    
    if (!roles.includes(req.user.role)) {
      return res.status(403).json({ error: 'Insufficient permissions' })
    }
    
    next()
  }
}

// Activity logging middleware
const logActivity = (action, resourceType = null, resourceId = null) => {
  return async (req, res, next) => {
    const originalSend = res.send
    res.send = function(data) {
      // Log activity after response is sent
      if (res.statusCode < 400) {
        prisma.activityLog.create({
          data: {
            userId: req.user?.id,
            userName: req.user?.name,
            userRole: req.user?.role,
            action: action,
            resourceType: resourceType,
            resourceId: resourceId,
            ipAddress: req.ip,
            userAgent: req.get('User-Agent')
          }
        }).catch(err => logger.error('Failed to log activity:', err))
      }
      originalSend.call(this, data)
    }
    next()
  }
}

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ status: 'OK', timestamp: new Date().toISOString() })
})

// Auth routes
app.post('/api/auth/login', async (req, res) => {
  try {
    const { email, password } = req.body
    
    const user = await prisma.user.findUnique({
      where: { email },
      include: { judge: true, contestant: true }
    })
    
    if (!user || !await bcrypt.compare(password, user.password)) {
      logger.warn(`Failed login attempt for email: ${email}`)
      return res.status(401).json({ error: 'Invalid credentials' })
    }
    
    const token = jwt.sign(
      { userId: user.id, email: user.email, role: user.role },
      JWT_SECRET,
      { expiresIn: '24h' }
    )
    
    logger.info(`User logged in: ${user.email} (${user.role})`)
    
    res.json({
      token,
      user: {
        id: user.id,
        name: user.name,
        preferredName: user.preferredName,
        email: user.email,
        role: user.role,
        judge: user.judge,
        contestant: user.contestant
      }
    })
  } catch (error) {
    logger.error('Login error:', error)
    res.status(500).json({ error: 'Login failed' })
  }
})

app.get('/api/auth/profile', authenticateToken, (req, res) => {
  res.json({
    id: req.user.id,
    name: req.user.name,
    preferredName: req.user.preferredName,
    email: req.user.email,
    role: req.user.role,
    judge: req.user.judge,
    contestant: req.user.contestant
  })
})

app.put('/api/auth/profile', authenticateToken, async (req, res) => {
  try {
    const { name, preferredName, gender, pronouns } = req.body
    
    const updatedUser = await prisma.user.update({
      where: { id: req.user.id },
      data: { name, preferredName, gender, pronouns }
    })
    
    res.json(updatedUser)
  } catch (error) {
    logger.error('Profile update error:', error)
    res.status(500).json({ error: 'Failed to update profile' })
  }
})

app.put('/api/auth/change-password', authenticateToken, async (req, res) => {
  try {
    const { currentPassword, newPassword } = req.body
    
    const user = await prisma.user.findUnique({
      where: { id: req.user.id }
    })
    
    if (!await bcrypt.compare(currentPassword, user.password)) {
      return res.status(400).json({ error: 'Current password is incorrect' })
    }
    
    const hashedPassword = await bcrypt.hash(newPassword, 12)
    
    await prisma.user.update({
      where: { id: req.user.id },
      data: { password: hashedPassword }
    })
    
    res.json({ message: 'Password updated successfully' })
  } catch (error) {
    logger.error('Password change error:', error)
    res.status(500).json({ error: 'Failed to change password' })
  }
})

// Events API
app.get('/api/events', authenticateToken, async (req, res) => {
  try {
    const events = await prisma.event.findMany({
      include: {
        contests: {
          include: {
            categories: {
              include: {
                contestants: {
                  include: { contestant: true }
                },
                judges: {
                  include: { judge: true }
                }
              }
            }
          }
        }
      },
      orderBy: { createdAt: 'desc' }
    })
    res.json(events)
  } catch (error) {
    logger.error('Events fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch events' })
  }
})

app.post('/api/events', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('CREATE_EVENT', 'Event'), async (req, res) => {
  try {
    const event = await prisma.event.create({
      data: req.body
    })
    res.json(event)
  } catch (error) {
    logger.error('Event creation error:', error)
    res.status(500).json({ error: 'Failed to create event' })
  }
})

app.get('/api/events/:id', authenticateToken, async (req, res) => {
  try {
    const event = await prisma.event.findUnique({
      where: { id: req.params.id },
      include: {
        contests: {
          include: {
            categories: {
              include: {
                contestants: {
                  include: { contestant: true }
                },
                judges: {
                  include: { judge: true }
                }
              }
            }
          }
        }
      }
    })
    
    if (!event) {
      return res.status(404).json({ error: 'Event not found' })
    }
    
    res.json(event)
  } catch (error) {
    logger.error('Event fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch event' })
  }
})

app.put('/api/events/:id', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('UPDATE_EVENT', 'Event', req.params.id), async (req, res) => {
  try {
    const event = await prisma.event.update({
      where: { id: req.params.id },
      data: req.body
    })
    res.json(event)
  } catch (error) {
    logger.error('Event update error:', error)
    res.status(500).json({ error: 'Failed to update event' })
  }
})

app.delete('/api/events/:id', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('DELETE_EVENT', 'Event', req.params.id), async (req, res) => {
  try {
    await prisma.event.delete({
      where: { id: req.params.id }
    })
    res.json({ message: 'Event deleted successfully' })
  } catch (error) {
    logger.error('Event deletion error:', error)
    res.status(500).json({ error: 'Failed to delete event' })
  }
})

// Contests API
app.get('/api/contests/event/:eventId', authenticateToken, async (req, res) => {
  try {
    const contests = await prisma.contest.findMany({
      where: { eventId: req.params.eventId },
      include: {
        categories: {
          include: {
            contestants: {
              include: { contestant: true }
            },
            judges: {
              include: { judge: true }
            }
          }
        },
        contestants: {
          include: { contestant: true }
        },
        judges: {
          include: { judge: true }
        }
      }
    })
    res.json(contests)
  } catch (error) {
    logger.error('Contests fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch contests' })
  }
})

app.post('/api/contests/event/:eventId', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('CREATE_CONTEST', 'Contest'), async (req, res) => {
  try {
    const contest = await prisma.contest.create({
      data: {
        ...req.body,
        eventId: req.params.eventId
      }
    })
    res.json(contest)
  } catch (error) {
    logger.error('Contest creation error:', error)
    res.status(500).json({ error: 'Failed to create contest' })
  }
})

app.get('/api/contests/:id', authenticateToken, async (req, res) => {
  try {
    const contest = await prisma.contest.findUnique({
      where: { id: req.params.id },
      include: {
        event: true,
        categories: {
          include: {
            contestants: {
              include: { contestant: true }
            },
            judges: {
              include: { judge: true }
            }
          }
        },
        contestants: {
          include: { contestant: true }
        },
        judges: {
          include: { judge: true }
        }
      }
    })
    
    if (!contest) {
      return res.status(404).json({ error: 'Contest not found' })
    }
    
    res.json(contest)
  } catch (error) {
    logger.error('Contest fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch contest' })
  }
})

app.put('/api/contests/:id', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('UPDATE_CONTEST', 'Contest', req.params.id), async (req, res) => {
  try {
    const contest = await prisma.contest.update({
      where: { id: req.params.id },
      data: req.body
    })
    res.json(contest)
  } catch (error) {
    logger.error('Contest update error:', error)
    res.status(500).json({ error: 'Failed to update contest' })
  }
})

// Categories API
app.get('/api/categories/contest/:contestId', authenticateToken, async (req, res) => {
  try {
    const categories = await prisma.category.findMany({
      where: { contestId: req.params.contestId },
      include: {
        criteria: true,
        contestants: {
          include: { contestant: true }
        },
        judges: {
          include: { judge: true }
        },
        scores: {
          include: {
            contestant: true,
            judge: true,
            criterion: true
          }
        }
      }
    })
    res.json(categories)
  } catch (error) {
    logger.error('Categories fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch categories' })
  }
})

app.post('/api/categories/contest/:contestId', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('CREATE_CATEGORY', 'Category'), async (req, res) => {
  try {
    const category = await prisma.category.create({
      data: {
        ...req.body,
        contestId: req.params.contestId
      }
    })
    res.json(category)
  } catch (error) {
    logger.error('Category creation error:', error)
    res.status(500).json({ error: 'Failed to create category' })
  }
})

app.get('/api/categories/:id', authenticateToken, async (req, res) => {
  try {
    const category = await prisma.category.findUnique({
      where: { id: req.params.id },
      include: {
        contest: true,
        criteria: true,
        contestants: {
          include: { contestant: true }
        },
        judges: {
          include: { judge: true }
        },
        scores: {
          include: {
            contestant: true,
            judge: true,
            criterion: true
          }
        }
      }
    })
    
    if (!category) {
      return res.status(404).json({ error: 'Category not found' })
    }
    
    res.json(category)
  } catch (error) {
    logger.error('Category fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch category' })
  }
})

app.put('/api/categories/:id', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('UPDATE_CATEGORY', 'Category', req.params.id), async (req, res) => {
  try {
    const category = await prisma.category.update({
      where: { id: req.params.id },
      data: req.body
    })
    res.json(category)
  } catch (error) {
    logger.error('Category update error:', error)
    res.status(500).json({ error: 'Failed to update category' })
  }
})

// Users API
app.get('/api/users', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), async (req, res) => {
  try {
    const { page = 1, limit = 50, role, search } = req.query
    const skip = (parseInt(page) - 1) * parseInt(limit)
    
    const where = {}
    if (role) where.role = role
    if (search) {
      where.OR = [
        { name: { contains: search, mode: 'insensitive' } },
        { email: { contains: search, mode: 'insensitive' } }
      ]
    }
    
    const [users, total] = await Promise.all([
      prisma.user.findMany({
        where,
        include: { judge: true, contestant: true },
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.user.count({ where })
    ])
    
    res.json({
      users,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    })
  } catch (error) {
    logger.error('Users fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch users' })
  }
})

app.post('/api/users', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('CREATE_USER', 'User'), async (req, res) => {
  try {
    const { password, ...userData } = req.body
    const hashedPassword = await bcrypt.hash(password, 12)
    
    const user = await prisma.user.create({
      data: {
        ...userData,
        password: hashedPassword
      },
      include: { judge: true, contestant: true }
    })
    
    res.json(user)
  } catch (error) {
    logger.error('User creation error:', error)
    res.status(500).json({ error: 'Failed to create user' })
  }
})

app.get('/api/users/:id', authenticateToken, async (req, res) => {
  try {
    const user = await prisma.user.findUnique({
      where: { id: req.params.id },
      include: { judge: true, contestant: true }
    })
    
    if (!user) {
      return res.status(404).json({ error: 'User not found' })
    }
    
    res.json(user)
  } catch (error) {
    logger.error('User fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch user' })
  }
})

app.put('/api/users/:id', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('UPDATE_USER', 'User', req.params.id), async (req, res) => {
  try {
    const { password, ...userData } = req.body
    const updateData = { ...userData }
    
    if (password) {
      updateData.password = await bcrypt.hash(password, 12)
    }
    
    const user = await prisma.user.update({
      where: { id: req.params.id },
      data: updateData,
      include: { judge: true, contestant: true }
    })
    
    res.json(user)
  } catch (error) {
    logger.error('User update error:', error)
    res.status(500).json({ error: 'Failed to update user' })
  }
})

app.delete('/api/users/:id', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('DELETE_USER', 'User', req.params.id), async (req, res) => {
  try {
    await prisma.user.delete({
      where: { id: req.params.id }
    })
    res.json({ message: 'User deleted successfully' })
  } catch (error) {
    logger.error('User deletion error:', error)
    res.status(500).json({ error: 'Failed to delete user' })
  }
})

// Scoring API
app.get('/api/scoring/category/:categoryId/contestant/:contestantId', authenticateToken, async (req, res) => {
  try {
    const scores = await prisma.score.findMany({
      where: {
        categoryId: req.params.categoryId,
        contestantId: req.params.contestantId
      },
      include: {
        criterion: true,
        judge: true
      }
    })
    res.json(scores)
  } catch (error) {
    logger.error('Scores fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch scores' })
  }
})

app.post('/api/scoring/category/:categoryId/contestant/:contestantId', authenticateToken, requireRole(['JUDGE']), logActivity('SUBMIT_SCORE', 'Score'), async (req, res) => {
  try {
    const { criterionId, score } = req.body
    
    const existingScore = await prisma.score.findFirst({
      where: {
        categoryId: req.params.categoryId,
        contestantId: req.params.contestantId,
        judgeId: req.user.id,
        criterionId
      }
    })
    
    let result
    if (existingScore) {
      result = await prisma.score.update({
        where: { id: existingScore.id },
        data: { score }
      })
    } else {
      result = await prisma.score.create({
        data: {
          categoryId: req.params.categoryId,
          contestantId: req.params.contestantId,
          judgeId: req.user.id,
          criterionId,
          score
        }
      })
    }
    
    // Emit real-time update
    io.emit('scoreUpdate', {
      categoryId: req.params.categoryId,
      contestantId: req.params.contestantId,
      judgeId: req.user.id,
      score
    })
    
    res.json(result)
  } catch (error) {
    logger.error('Score submission error:', error)
    res.status(500).json({ error: 'Failed to submit score' })
  }
})

// Certification API
app.post('/api/scoring/category/:categoryId/certify', authenticateToken, requireRole(['JUDGE']), logActivity('CERTIFY_SCORES', 'JudgeCertification'), async (req, res) => {
  try {
    const { signatureName } = req.body
    
    const certification = await prisma.judgeCertification.upsert({
      where: {
        categoryId_judgeId: {
          categoryId: req.params.categoryId,
          judgeId: req.user.id
        }
      },
      update: {
        signatureName,
        certifiedAt: new Date()
      },
      create: {
        categoryId: req.params.categoryId,
        judgeId: req.user.id,
        signatureName,
        certifiedAt: new Date()
      }
    })
    
    // Emit real-time update
    io.emit('certificationUpdate', {
      type: 'judge',
      categoryId: req.params.categoryId,
      judgeId: req.user.id
    })
    
    res.json(certification)
  } catch (error) {
    logger.error('Judge certification error:', error)
    res.status(500).json({ error: 'Failed to certify scores' })
  }
})

app.post('/api/scoring/category/:categoryId/certify-totals', authenticateToken, requireRole(['TALLY_MASTER']), logActivity('CERTIFY_TOTALS', 'TallyMasterCertification'), async (req, res) => {
  try {
    const { signatureName } = req.body
    
    const certification = await prisma.tallyMasterCertification.upsert({
      where: {
        categoryId: req.params.categoryId
      },
      update: {
        signatureName,
        certifiedAt: new Date()
      },
      create: {
        categoryId: req.params.categoryId,
        signatureName,
        certifiedAt: new Date()
      }
    })
    
    // Emit real-time update
    io.emit('certificationUpdate', {
      type: 'tallyMaster',
      categoryId: req.params.categoryId
    })
    
    res.json(certification)
  } catch (error) {
    logger.error('Tally master certification error:', error)
    res.status(500).json({ error: 'Failed to certify totals' })
  }
})

app.post('/api/scoring/category/:categoryId/final-certification', authenticateToken, requireRole(['AUDITOR']), logActivity('FINAL_CERTIFICATION', 'AuditorCertification'), async (req, res) => {
  try {
    const { signatureName } = req.body
    
    const certification = await prisma.auditorCertification.upsert({
      where: {
        categoryId: req.params.categoryId
      },
      update: {
        signatureName,
        certifiedAt: new Date()
      },
      create: {
        categoryId: req.params.categoryId,
        signatureName,
        certifiedAt: new Date()
      }
    })
    
    // Emit real-time update
    io.emit('certificationUpdate', {
      type: 'auditor',
      categoryId: req.params.categoryId
    })
    
    res.json(certification)
  } catch (error) {
    logger.error('Auditor certification error:', error)
    res.status(500).json({ error: 'Failed to perform final certification' })
  }
})

// Results API
app.get('/api/results/category/:categoryId', authenticateToken, async (req, res) => {
  try {
    const category = await prisma.category.findUnique({
      where: { id: req.params.categoryId },
      include: {
        contestants: {
          include: { contestant: true }
        },
        scores: {
          include: {
            contestant: true,
            judge: true,
            criterion: true
          }
        }
      }
    })
    
    if (!category) {
      return res.status(404).json({ error: 'Category not found' })
    }
    
    // Calculate results
    const results = category.contestants.map(({ contestant }) => {
      const contestantScores = category.scores.filter(score => score.contestantId === contestant.id)
      const totalScore = contestantScores.reduce((sum, score) => sum + score.score, 0)
      const averageScore = contestantScores.length > 0 ? totalScore / contestantScores.length : 0
      
      return {
        contestant,
        totalScore,
        averageScore,
        scores: contestantScores
      }
    }).sort((a, b) => b.totalScore - a.totalScore)
    
    res.json({
      category,
      results
    })
  } catch (error) {
    logger.error('Results fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch results' })
  }
})

// Admin API
app.get('/api/admin/stats', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), async (req, res) => {
  try {
    const [eventCount, contestCount, userCount, scoreCount, activeUsers] = await Promise.all([
      prisma.event.count(),
      prisma.contest.count(),
      prisma.user.count(),
      prisma.score.count(),
      prisma.user.count({
        where: {
          updatedAt: {
            gte: new Date(Date.now() - 24 * 60 * 60 * 1000) // Last 24 hours
          }
        }
      })
    ])
    
    res.json({
      events: eventCount,
      contests: contestCount,
      users: userCount,
      scores: scoreCount,
      activeUsers
    })
  } catch (error) {
    logger.error('Stats fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch stats' })
  }
})

app.get('/api/admin/logs', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), async (req, res) => {
  try {
    const { page = 1, limit = 50, level, userId } = req.query
    const skip = (parseInt(page) - 1) * parseInt(limit)
    
    const where = {}
    if (level) where.logLevel = level
    if (userId) where.userId = userId
    
    const [logs, total] = await Promise.all([
      prisma.activityLog.findMany({
        where,
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.activityLog.count({ where })
    ])
    
    res.json({
      logs,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    })
  } catch (error) {
    logger.error('Logs fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch logs' })
  }
})

app.get('/api/admin/active-users', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), async (req, res) => {
  try {
    const activeUsers = await prisma.user.findMany({
      where: {
        updatedAt: {
          gte: new Date(Date.now() - 24 * 60 * 60 * 1000) // Last 24 hours
        }
      },
      include: { judge: true, contestant: true },
      orderBy: { updatedAt: 'desc' }
    })
    
    res.json(activeUsers)
  } catch (error) {
    logger.error('Active users fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch active users' })
  }
})

// Settings API
app.get('/api/admin/settings', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), async (req, res) => {
  try {
    const settings = await prisma.systemSetting.findMany({
      orderBy: { settingKey: 'asc' }
    })
    
    const settingsObject = settings.reduce((acc, setting) => {
      acc[setting.settingKey] = setting.settingValue
      return acc
    }, {})
    
    res.json(settingsObject)
  } catch (error) {
    logger.error('Settings fetch error:', error)
    res.status(500).json({ error: 'Failed to fetch settings' })
  }
})

app.put('/api/admin/settings', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), logActivity('UPDATE_SETTINGS', 'SystemSetting'), async (req, res) => {
  try {
    const settings = req.body
    
    for (const [key, value] of Object.entries(settings)) {
      await prisma.systemSetting.upsert({
        where: { settingKey: key },
        update: { 
          settingValue: value,
          updatedById: req.user.id
        },
        create: {
          settingKey: key,
          settingValue: value,
          updatedById: req.user.id
        }
      })
    }
    
    res.json({ message: 'Settings updated successfully' })
  } catch (error) {
    logger.error('Settings update error:', error)
    res.status(500).json({ error: 'Failed to update settings' })
  }
})

// File Upload API
app.post('/api/upload', authenticateToken, upload.single('file'), async (req, res) => {
  try {
    if (!req.file) {
      return res.status(400).json({ error: 'No file uploaded' })
    }
    
    const fileUrl = `/uploads/${req.file.filename}`
    
    res.json({
      filename: req.file.filename,
      originalName: req.file.originalname,
      size: req.file.size,
      url: fileUrl
    })
  } catch (error) {
    logger.error('File upload error:', error)
    res.status(500).json({ error: 'Failed to upload file' })
  }
})

// Serve uploaded files
app.use('/uploads', express.static(path.join(__dirname, 'uploads')))

// Email API
app.post('/api/email/send', authenticateToken, requireRole(['ORGANIZER', 'BOARD']), async (req, res) => {
  try {
    const { to, subject, text, html } = req.body
    
    const transporter = createTransporter()
    if (!transporter) {
      return res.status(400).json({ error: 'Email not configured' })
    }
    
    await transporter.sendMail({
      from: process.env.SMTP_FROM || 'noreply@eventmanager.com',
      to,
      subject,
      text,
      html
    })
    
    logger.info(`Email sent to ${to}: ${subject}`)
    res.json({ message: 'Email sent successfully' })
  } catch (error) {
    logger.error('Email send error:', error)
    res.status(500).json({ error: 'Failed to send email' })
  }
})

// Socket.IO connection handling
io.on('connection', (socket) => {
  logger.info(`User connected: ${socket.id}`)
  
  socket.on('joinCategory', (categoryId) => {
    socket.join(`category:${categoryId}`)
    logger.info(`User ${socket.id} joined category ${categoryId}`)
  })
  
  socket.on('leaveCategory', (categoryId) => {
    socket.leave(`category:${categoryId}`)
    logger.info(`User ${socket.id} left category ${categoryId}`)
  })
  
  socket.on('disconnect', () => {
    logger.info(`User disconnected: ${socket.id}`)
  })
})

// PDF and Image Generation Routes
app.post('/api/reports/generate-pdf', authenticateToken, async (req, res) => {
  try {
    const { type, data, options = {} } = req.body
    
    let pdfBuffer
    const doc = new PDFDocument()
    const buffers = []
    
    doc.on('data', buffers.push.bind(buffers))
    doc.on('end', () => {
      pdfBuffer = Buffer.concat(buffers)
      res.setHeader('Content-Type', 'application/pdf')
      res.setHeader('Content-Disposition', `attachment; filename="${type}-report.pdf"`)
      res.send(pdfBuffer)
    })
    
    // Add content based on report type
    doc.fontSize(20).text(`${type} Report`, 50, 50)
    doc.fontSize(12).text(`Generated on: ${new Date().toLocaleDateString()}`, 50, 80)
    
    if (data && data.length > 0) {
      let y = 120
      data.forEach((item, index) => {
        if (y > 700) {
          doc.addPage()
          y = 50
        }
        doc.text(`${index + 1}. ${item.name || item.title || 'Item'}`, 50, y)
        y += 20
      })
    }
    
    doc.end()
  } catch (error) {
    logger.error('PDF generation error:', error)
    res.status(500).json({ error: 'Failed to generate PDF' })
  }
})

app.post('/api/reports/generate-image', authenticateToken, async (req, res) => {
  try {
    const { type, data, options = {} } = req.body
    
    // Create a simple image using sharp
    const width = options.width || 800
    const height = options.height || 600
    
    const svg = `
      <svg width="${width}" height="${height}" xmlns="http://www.w3.org/2000/svg">
        <rect width="100%" height="100%" fill="white"/>
        <text x="50%" y="50%" text-anchor="middle" font-family="Arial" font-size="24" fill="black">
          ${type} Report
        </text>
        <text x="50%" y="60%" text-anchor="middle" font-family="Arial" font-size="16" fill="gray">
          Generated on: ${new Date().toLocaleDateString()}
        </text>
      </svg>
    `
    
    const imageBuffer = await sharp(Buffer.from(svg))
      .png()
      .toBuffer()
    
    res.setHeader('Content-Type', 'image/png')
    res.setHeader('Content-Disposition', `attachment; filename="${type}-report.png"`)
    res.send(imageBuffer)
  } catch (error) {
    logger.error('Image generation error:', error)
    res.status(500).json({ error: 'Failed to generate image' })
  }
})

app.post('/api/reports/generate-certificate', authenticateToken, async (req, res) => {
  try {
    const { contestantName, categoryName, score, rank, eventName } = req.body
    
    const doc = new PDFDocument({
      size: 'A4',
      layout: 'landscape'
    })
    
    const buffers = []
    doc.on('data', buffers.push.bind(buffers))
    doc.on('end', () => {
      const pdfBuffer = Buffer.concat(buffers)
      res.setHeader('Content-Type', 'application/pdf')
      res.setHeader('Content-Disposition', `attachment; filename="certificate-${contestantName.replace(/\s+/g, '-')}.pdf"`)
      res.send(pdfBuffer)
    })
    
    // Certificate design
    doc.rect(50, 50, 700, 500).stroke()
    doc.fontSize(36).text('CERTIFICATE OF ACHIEVEMENT', 150, 100, { align: 'center' })
    doc.fontSize(24).text('This is to certify that', 300, 180, { align: 'center' })
    doc.fontSize(32).text(contestantName, 300, 220, { align: 'center' })
    doc.fontSize(20).text(`has achieved ${rank} place in ${categoryName}`, 300, 280, { align: 'center' })
    doc.fontSize(18).text(`at ${eventName}`, 300, 320, { align: 'center' })
    doc.fontSize(16).text(`Score: ${score}`, 300, 380, { align: 'center' })
    doc.fontSize(14).text(`Date: ${new Date().toLocaleDateString()}`, 300, 450, { align: 'center' })
    
    doc.end()
  } catch (error) {
    logger.error('Certificate generation error:', error)
    res.status(500).json({ error: 'Failed to generate certificate' })
  }
})

// Error handling middleware
app.use((err, req, res, next) => {
  logger.error(err.stack)
  res.status(500).json({ error: 'Something went wrong!' })
})

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Route not found' })
})

// Start server
server.listen(PORT, () => {
  logger.info(`🚀 Event Manager API server running on port ${PORT}`)
})

// Graceful shutdown
process.on('SIGTERM', async () => {
  logger.info('SIGTERM received, shutting down gracefully')
  await prisma.$disconnect()
  process.exit(0)
})

process.on('SIGINT', async () => {
  logger.info('SIGINT received, shutting down gracefully')
  await prisma.$disconnect()
  process.exit(0)
})