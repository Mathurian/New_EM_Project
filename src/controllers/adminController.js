const { PrismaClient } = require('@prisma/client');
const { validationResult } = require('express-validator');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Get system statistics
const getSystemStats = async (req, res) => {
  try {
    const [
      totalUsers,
      totalEvents,
      totalContests,
      totalCategories,
      totalContestants,
      totalJudges,
      activeUsers,
      recentActivity
    ] = await Promise.all([
      prisma.user.count(),
      prisma.event.count(),
      prisma.contest.count(),
      prisma.category.count(),
      prisma.contestant.count(),
      prisma.judge.count(),
      prisma.user.count({
        where: {
          updatedAt: {
            gte: new Date(Date.now() - 24 * 60 * 60 * 1000) // Last 24 hours
          }
        }
      }),
      prisma.activityLog.findMany({
        take: 10,
        orderBy: { createdAt: 'desc' },
        include: {
          user: {
            select: {
              name: true,
              role: true
            }
          }
        }
      })
    ]);

    const stats = {
      users: {
        total: totalUsers,
        active: activeUsers,
        byRole: await prisma.user.groupBy({
          by: ['role'],
          _count: { role: true }
        })
      },
      events: {
        total: totalEvents,
        active: await prisma.event.count({
          where: {
            archivedEvents: {
              none: {}
            }
          }
        }),
        archived: await prisma.event.count({
          where: {
            archivedEvents: {
              some: {}
            }
          }
        })
      },
      contests: {
        total: totalContests
      },
      categories: {
        total: totalCategories
      },
      contestants: {
        total: totalContestants
      },
      judges: {
        total: totalJudges,
        headJudges: await prisma.judge.count({
          where: { isHeadJudge: true }
        })
      },
      recentActivity
    };

    res.json({ stats });
  } catch (error) {
    logger.error('Get system stats error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get activity logs
const getActivityLogs = async (req, res) => {
  try {
    const { 
      page = 1, 
      limit = 50, 
      search, 
      logLevel, 
      action, 
      resourceType,
      startDate,
      endDate
    } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {};

    if (search) {
      where.OR = [
        { userName: { contains: search, mode: 'insensitive' } },
        { action: { contains: search, mode: 'insensitive' } },
        { details: { contains: search, mode: 'insensitive' } }
      ];
    }

    if (logLevel) {
      where.logLevel = logLevel;
    }

    if (action) {
      where.action = { contains: action, mode: 'insensitive' };
    }

    if (resourceType) {
      where.resourceType = resourceType;
    }

    if (startDate || endDate) {
      where.createdAt = {};
      if (startDate) {
        where.createdAt.gte = new Date(startDate);
      }
      if (endDate) {
        where.createdAt.lte = new Date(endDate);
      }
    }

    const [logs, total] = await Promise.all([
      prisma.activityLog.findMany({
        where,
        include: {
          user: {
            select: {
              name: true,
              role: true
            }
          }
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.activityLog.count({ where })
    ]);

    res.json({
      logs,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    logger.error('Get activity logs error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get system settings
const getSystemSettings = async (req, res) => {
  try {
    const settings = await prisma.systemSetting.findMany({
      orderBy: { settingKey: 'asc' }
    });

    // Convert to key-value object
    const settingsObject = settings.reduce((acc, setting) => {
      acc[setting.settingKey] = {
        value: setting.settingValue,
        description: setting.description,
        updatedAt: setting.updatedAt,
        updatedById: setting.updatedById
      };
      return acc;
    }, {});

    res.json({ settings: settingsObject });
  } catch (error) {
    logger.error('Get system settings error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Update system settings
const updateSystemSettings = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { settings } = req.body;
    const userId = req.user.userId;

    // Start transaction
    await prisma.$transaction(async (tx) => {
      for (const [key, value] of Object.entries(settings)) {
        await tx.systemSetting.upsert({
          where: { settingKey: key },
          update: {
            settingValue: value,
            updatedById: userId
          },
          create: {
            settingKey: key,
            settingValue: value,
            updatedById: userId
          }
        });
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'update_system_settings',
        resourceType: 'system',
        details: `Updated system settings: ${Object.keys(settings).join(', ')}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('System settings updated', { userId, settings: Object.keys(settings) });

    res.json({ message: 'System settings updated successfully' });
  } catch (error) {
    logger.error('Update system settings error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get database statistics
const getDatabaseStats = async (req, res) => {
  try {
    const stats = {
      tables: {
        users: await prisma.user.count(),
        events: await prisma.event.count(),
        contests: await prisma.contest.count(),
        categories: await prisma.category.count(),
        contestants: await prisma.contestant.count(),
        judges: await prisma.judge.count(),
        criteria: await prisma.criterion.count(),
        scores: await prisma.score.count(),
        comments: await prisma.judgeComment.count(),
        certifications: await prisma.judgeCertification.count(),
        tallyMasterCertifications: await prisma.tallyMasterCertification.count(),
        auditorCertifications: await prisma.auditorCertification.count(),
        activityLogs: await prisma.activityLog.count(),
        systemSettings: await prisma.systemSetting.count()
      },
      storage: {
        // This would require raw SQL queries to get actual table sizes
        // For now, we'll return counts as a proxy
        estimatedSize: 'N/A'
      }
    };

    res.json({ stats });
  } catch (error) {
    logger.error('Get database stats error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Clear cache (placeholder - would integrate with Redis)
const clearCache = async (req, res) => {
  try {
    const userId = req.user.userId;

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'clear_cache',
        resourceType: 'system',
        details: 'Cache cleared',
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Cache cleared', { userId });

    res.json({ message: 'Cache cleared successfully' });
  } catch (error) {
    logger.error('Clear cache error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get active users (for real-time monitoring)
const getActiveUsers = async (req, res) => {
  try {
    const activeUsers = await prisma.user.findMany({
      where: {
        updatedAt: {
          gte: new Date(Date.now() - 30 * 60 * 1000) // Last 30 minutes
        }
      },
      select: {
        id: true,
        name: true,
        preferredName: true,
        email: true,
        role: true,
        updatedAt: true,
        judge: {
          select: {
            id: true,
            name: true,
            isHeadJudge: true
          }
        },
        contestant: {
          select: {
            id: true,
            name: true,
            contestantNumber: true
          }
        }
      },
      orderBy: { updatedAt: 'desc' }
    });

    res.json({ users: activeUsers });
  } catch (error) {
    logger.error('Get active users error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Export data (placeholder)
const exportData = async (req, res) => {
  try {
    const { type } = req.params;
    const userId = req.user.userId;

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'export_data',
        resourceType: 'system',
        details: `Exported data: ${type}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Data export requested', { userId, type });

    // This would implement actual data export logic
    res.json({ message: `Data export for ${type} initiated` });
  } catch (error) {
    logger.error('Export data error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

module.exports = {
  getSystemStats,
  getActivityLogs,
  getSystemSettings,
  updateSystemSettings,
  getDatabaseStats,
  clearCache,
  getActiveUsers,
  exportData
};
