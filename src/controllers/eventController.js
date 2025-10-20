const { PrismaClient } = require('@prisma/client');
const { validationResult } = require('express-validator');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Get all events
const getEvents = async (req, res) => {
  try {
    const { page = 1, limit = 10, search, archived } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {};
    
    if (search) {
      where.name = {
        contains: search,
        mode: 'insensitive'
      };
    }

    if (archived === 'true') {
      where.archivedEvents = {
        some: {}
      };
    } else if (archived === 'false') {
      where.archivedEvents = {
        none: {}
      };
    }

    const [events, total] = await Promise.all([
      prisma.event.findMany({
        where,
        include: {
          contests: {
            include: {
              categories: true,
              _count: {
                select: {
                  contestants: true,
                  judges: true
                }
              }
            }
          },
          archivedEvents: true,
          _count: {
            select: {
              contests: true
            }
          }
        },
        orderBy: { startDate: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.event.count({ where })
    ]);

    res.json({
      events,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    logger.error('Get events error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get single event
const getEvent = async (req, res) => {
  try {
    const { id } = req.params;

    const event = await prisma.event.findUnique({
      where: { id },
      include: {
        contests: {
          include: {
            categories: {
              include: {
                _count: {
                  select: {
                    contestants: true,
                    judges: true,
                    criteria: true
                  }
                }
              }
            },
            _count: {
              select: {
                contestants: true,
                judges: true
              }
            }
          }
        },
        archivedEvents: true
      }
    });

    if (!event) {
      return res.status(404).json({ error: 'Event not found' });
    }

    res.json({ event });
  } catch (error) {
    logger.error('Get event error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Create new event
const createEvent = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { name, startDate, endDate } = req.body;
    const userId = req.user.userId;

    const event = await prisma.event.create({
      data: {
        name,
        startDate: new Date(startDate),
        endDate: new Date(endDate)
      },
      include: {
        contests: true,
        _count: {
          select: {
            contests: true
          }
        }
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'create_event',
        resourceType: 'event',
        resourceId: event.id,
        details: `Created event: ${event.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Event created', { eventId: event.id, name: event.name, userId });

    res.status(201).json({
      message: 'Event created successfully',
      event
    });
  } catch (error) {
    logger.error('Create event error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Update event
const updateEvent = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { name, startDate, endDate } = req.body;
    const userId = req.user.userId;

    // Check if event exists
    const existingEvent = await prisma.event.findUnique({
      where: { id }
    });

    if (!existingEvent) {
      return res.status(404).json({ error: 'Event not found' });
    }

    const event = await prisma.event.update({
      where: { id },
      data: {
        name,
        startDate: new Date(startDate),
        endDate: new Date(endDate)
      },
      include: {
        contests: true,
        _count: {
          select: {
            contests: true
          }
        }
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'update_event',
        resourceType: 'event',
        resourceId: event.id,
        details: `Updated event: ${event.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Event updated', { eventId: event.id, name: event.name, userId });

    res.json({
      message: 'Event updated successfully',
      event
    });
  } catch (error) {
    logger.error('Update event error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Delete event
const deleteEvent = async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.userId;

    // Check if event exists
    const existingEvent = await prisma.event.findUnique({
      where: { id },
      include: {
        contests: {
          include: {
            categories: true
          }
        }
      }
    });

    if (!existingEvent) {
      return res.status(404).json({ error: 'Event not found' });
    }

    // Check if event has contests with categories
    const hasContestsWithCategories = existingEvent.contests.some(contest => 
      contest.categories.length > 0
    );

    if (hasContestsWithCategories) {
      return res.status(400).json({ 
        error: 'Cannot delete event with contests that have categories. Please delete categories first.' 
      });
    }

    await prisma.event.delete({
      where: { id }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'delete_event',
        resourceType: 'event',
        resourceId: id,
        details: `Deleted event: ${existingEvent.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Event deleted', { eventId: id, name: existingEvent.name, userId });

    res.json({ message: 'Event deleted successfully' });
  } catch (error) {
    logger.error('Delete event error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Archive event
const archiveEvent = async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.userId;

    // Check if event exists
    const existingEvent = await prisma.event.findUnique({
      where: { id }
    });

    if (!existingEvent) {
      return res.status(404).json({ error: 'Event not found' });
    }

    // Check if already archived
    const existingArchive = await prisma.archivedEvent.findFirst({
      where: { eventId: id }
    });

    if (existingArchive) {
      return res.status(400).json({ error: 'Event is already archived' });
    }

    // Create archived event record
    const archivedEvent = await prisma.archivedEvent.create({
      data: {
        eventId: id,
        name: existingEvent.name,
        startDate: existingEvent.startDate,
        endDate: existingEvent.endDate,
        archivedById: userId
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'archive_event',
        resourceType: 'event',
        resourceId: id,
        details: `Archived event: ${existingEvent.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Event archived', { eventId: id, name: existingEvent.name, userId });

    res.json({
      message: 'Event archived successfully',
      archivedEvent
    });
  } catch (error) {
    logger.error('Archive event error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Restore archived event
const restoreEvent = async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.userId;

    // Check if archived event exists
    const archivedEvent = await prisma.archivedEvent.findFirst({
      where: { eventId: id }
    });

    if (!archivedEvent) {
      return res.status(404).json({ error: 'Archived event not found' });
    }

    // Delete archived record
    await prisma.archivedEvent.delete({
      where: { id: archivedEvent.id }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'restore_event',
        resourceType: 'event',
        resourceId: id,
        details: `Restored event: ${archivedEvent.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Event restored', { eventId: id, name: archivedEvent.name, userId });

    res.json({ message: 'Event restored successfully' });
  } catch (error) {
    logger.error('Restore event error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

module.exports = {
  getEvents,
  getEvent,
  createEvent,
  updateEvent,
  deleteEvent,
  archiveEvent,
  restoreEvent
};
