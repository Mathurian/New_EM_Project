#!/bin/bash

# Complete fix for reportsController.js and reportsRoutes.js
APP_DIR="/var/www/event-manager"

echo "Fixing reportsController.js and reportsRoutes.js..."

# Fix reportsController.js
cat > "$APP_DIR/src/controllers/reportsController.js" << 'EOF'
const { PrismaClient } = require('@prisma/client')
const fs = require('fs').promises
const path = require('path')

const prisma = new PrismaClient()

const getTemplates = async (req, res) => {
  try {
    const templates = await prisma.reportTemplate.findMany({
      orderBy: { createdAt: 'desc' }
    })

    res.json(templates)
  } catch (error) {
    console.error('Get report templates error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

const createTemplate = async (req, res) => {
  try {
    const { name, description, template, parameters } = req.body

    const reportTemplate = await prisma.reportTemplate.create({
      data: {
        name,
        description,
        template,
        parameters,
        createdBy: req.user.id
      }
    })

    res.status(201).json(reportTemplate)
  } catch (error) {
    console.error('Create report template error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

const updateTemplate = async (req, res) => {
  try {
    const { id } = req.params
    const { name, description, template, parameters } = req.body

    const reportTemplate = await prisma.reportTemplate.update({
      where: { id: parseInt(id) },
      data: {
        name,
        description,
        template,
        parameters
      }
    })

    res.json(reportTemplate)
  } catch (error) {
    console.error('Update report template error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

const deleteTemplate = async (req, res) => {
  try {
    const { id } = req.params

    await prisma.reportTemplate.delete({
      where: { id: parseInt(id) }
    })

    res.status(204).send()
  } catch (error) {
    console.error('Delete report template error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

const generateReport = async (req, res) => {
  try {
    const { templateId, parameters, eventId, contestId, categoryId } = req.body

    // Get template
    const template = await prisma.reportTemplate.findUnique({
      where: { id: templateId }
    })

    if (!template) {
      return res.status(404).json({ error: 'Template not found' })
    }

    // Generate report based on template
    let reportData = {}
    
    if (eventId) {
      const event = await prisma.event.findUnique({
        where: { id: eventId },
        include: {
          contests: {
            include: {
              categories: {
                include: {
                  contestants: {
                    include: {
                      user: true,
                      scores: {
                        include: {
                          judge: true,
                          criterion: true
                        }
                      }
                    }
                  },
                  judges: {
                    include: {
                      user: true
                    }
                  },
                  criteria: true
                }
              }
            }
          }
        }
      })
      reportData.event = event
    }

    if (contestId) {
      const contest = await prisma.contest.findUnique({
        where: { id: contestId },
        include: {
          event: true,
          categories: {
            include: {
              contestants: {
                include: {
                  user: true,
                  scores: {
                    include: {
                      judge: true,
                      criterion: true
                    }
                  }
                }
              },
              judges: {
                include: {
                  user: true
                }
              },
              criteria: true
            }
          }
        }
      })
      reportData.contest = contest
    }

    if (categoryId) {
      const category = await prisma.category.findUnique({
        where: { id: categoryId },
        include: {
          contest: {
            include: {
              event: true
            }
          },
          contestants: {
            include: {
              user: true,
              scores: {
                include: {
                  judge: true,
                  criterion: true
                }
              }
            }
          },
          judges: {
            include: {
              user: true
            }
          },
          criteria: true
        }
      })
      reportData.category = category
    }

    // Process template with data
    let processedTemplate = template.template
    
    // Replace placeholders with actual data
    Object.keys(parameters || {}).forEach(key => {
      const placeholder = `{{${key}}}`
      processedTemplate = processedTemplate.replace(new RegExp(placeholder, 'g'), parameters[key])
    })

    // Generate report instance
    const reportInstance = await prisma.reportInstance.create({
      data: {
        name: `Report_${Date.now()}`,
        templateId: template.id,
        generatedBy: req.user.id,
        parameters: parameters || {},
        content: processedTemplate,
        data: reportData
      },
      include: {
        template: true,
        user: true
      }
    })

    res.json(reportInstance)
  } catch (error) {
    console.error('Generate report error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

const sendReportEmail = async (req, res) => {
  try {
    const { reportInstanceId, recipients, subject, message } = req.body

    // Get report instance
    const reportInstance = await prisma.reportInstance.findUnique({
      where: { id: reportInstanceId },
      include: {
        template: true,
        user: true
      }
    })

    if (!reportInstance) {
      return res.status(404).json({ error: 'Report instance not found' })
    }

    // Import email controller
    const emailController = require('./emailController')
    
    // Create email content
    const emailContent = `
      <h2>Report: ${reportInstance.name}</h2>
      <p>${message || 'Please find the attached report.'}</p>
      <hr>
      <p><strong>Report Details:</strong></p>
      <ul>
        <li><strong>Template:</strong> ${reportInstance.template.name}</li>
        <li><strong>Generated By:</strong> ${reportInstance.user.name}</li>
        <li><strong>Generated At:</strong> ${new Date(reportInstance.createdAt).toLocaleString()}</li>
      </ul>
      <p>This report was generated from the Event Manager system.</p>
    `

    // Send email with report attachment
    await emailController.sendReportEmail({
      to: recipients,
      subject: subject || `Report: ${reportInstance.name}`,
      html: emailContent,
      attachments: reportInstance.fileUrl ? [{
        filename: `${reportInstance.name}.pdf`,
        path: reportInstance.fileUrl
      }] : []
    })

    res.json({
      message: 'Report email sent successfully',
      recipients: recipients
    })
  } catch (error) {
    console.error('Send report email error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

const getReportInstances = async (req, res) => {
  try {
    const instances = await prisma.reportInstance.findMany({
      include: {
        template: true,
        user: true
      },
      orderBy: { createdAt: 'desc' }
    })

    res.json(instances)
  } catch (error) {
    console.error('Get report instances error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

const deleteReportInstance = async (req, res) => {
  try {
    const { id } = req.params

    await prisma.reportInstance.delete({
      where: { id: parseInt(id) }
    })

    res.status(204).send()
  } catch (error) {
    console.error('Delete report instance error:', error)
    res.status(500).json({ error: 'Internal server error' })
  }
}

module.exports = {
  getTemplates,
  createTemplate,
  updateTemplate,
  deleteTemplate,
  generateReport,
  sendReportEmail,
  getReportInstances,
  deleteReportInstance
}
EOF

# Fix reportsRoutes.js
cat > "$APP_DIR/src/routes/reportsRoutes.js" << 'EOF'
const express = require('express')
const { 
  getTemplates, 
  createTemplate,
  updateTemplate,
  deleteTemplate,
  generateReport,
  sendReportEmail,
  getReportInstances,
  deleteReportInstance
} = require('../controllers/reportsController')
const { authenticateToken, requireRole } = require('../middleware/auth')
const { logActivity } = require('../middleware/errorHandler')

const router = express.Router()

// Apply authentication to all routes
router.use(authenticateToken)

// Report templates
router.get('/templates', getTemplates)
router.post('/templates', requireRole(['ORGANIZER', 'BOARD']), logActivity('CREATE_REPORT_TEMPLATE', 'REPORT'), createTemplate)
router.put('/templates/:id', requireRole(['ORGANIZER', 'BOARD']), logActivity('UPDATE_REPORT_TEMPLATE', 'REPORT'), updateTemplate)
router.delete('/templates/:id', requireRole(['ORGANIZER', 'BOARD']), logActivity('DELETE_REPORT_TEMPLATE', 'REPORT'), deleteTemplate)

// Report generation and management
router.post('/generate', requireRole(['ORGANIZER', 'BOARD', 'JUDGE']), logActivity('GENERATE_REPORT', 'REPORT'), generateReport)
router.get('/instances', getReportInstances)
router.delete('/instances/:id', requireRole(['ORGANIZER', 'BOARD']), logActivity('DELETE_REPORT_INSTANCE', 'REPORT'), deleteReportInstance)
router.post('/send-email', requireRole(['ORGANIZER', 'BOARD', 'AUDITOR']), logActivity('EMAIL_REPORT', 'REPORT'), sendReportEmail)

module.exports = router
EOF

echo "Fixed both reportsController.js and reportsRoutes.js!"
echo "Restarting event-manager service..."

# Restart the service
sudo systemctl restart event-manager

echo "Service restarted. Checking status..."
sudo systemctl status event-manager --no-pager -l

echo "Fix completed!"
