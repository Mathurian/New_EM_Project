const { PrismaClient } = require('@prisma/client');
const { validationResult } = require('express-validator');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Get all contests for an event
const getContests = async (req, res) => {
  try {
    const { eventId } = req.params;
    const { page = 1, limit = 10, search } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = { eventId };
    
    if (search) {
      where.name = {
        contains: search,
        mode: 'insensitive'
      };
    }

    const [contests, total] = await Promise.all([
      prisma.contest.findMany({
        where,
        include: {
          event: {
            select: {
              id: true,
              name: true,
              startDate: true,
              endDate: true
            }
          },
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
              judges: true,
              categories: true
            }
          }
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.contest.count({ where })
    ]);

    res.json({
      contests,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    logger.error('Get contests error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get single contest
const getContest = async (req, res) => {
  try {
    const { id } = req.params;

    const contest = await prisma.contest.findUnique({
      where: { id },
      include: {
        event: {
          select: {
            id: true,
            name: true,
            startDate: true,
            endDate: true
          }
        },
        categories: {
          include: {
            contestants: {
              include: {
                contestant: {
                  select: {
                    id: true,
                    name: true,
                    contestantNumber: true,
                    imagePath: true
                  }
                }
              }
            },
            judges: {
              include: {
                judge: {
                  select: {
                    id: true,
                    name: true,
                    isHeadJudge: true,
                    imagePath: true
                  }
                }
              }
            },
            criteria: true,
            _count: {
              select: {
                contestants: true,
                judges: true,
                criteria: true
              }
            }
          }
        },
        contestants: {
          include: {
            contestant: {
              select: {
                id: true,
                name: true,
                contestantNumber: true,
                imagePath: true
              }
            }
          }
        },
        judges: {
          include: {
            judge: {
              select: {
                id: true,
                name: true,
                isHeadJudge: true,
                imagePath: true
              }
            }
          }
        },
        _count: {
          select: {
            contestants: true,
            judges: true,
            categories: true
          }
        }
      }
    });

    if (!contest) {
      return res.status(404).json({ error: 'Contest not found' });
    }

    res.json({ contest });
  } catch (error) {
    logger.error('Get contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Create new contest
const createContest = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { eventId } = req.params;
    const { name, description } = req.body;
    const userId = req.user.userId;

    // Verify event exists
    const event = await prisma.event.findUnique({
      where: { id: eventId }
    });

    if (!event) {
      return res.status(404).json({ error: 'Event not found' });
    }

    const contest = await prisma.contest.create({
      data: {
        eventId,
        name,
        description
      },
      include: {
        event: {
          select: {
            id: true,
            name: true
          }
        },
        _count: {
          select: {
            contestants: true,
            judges: true,
            categories: true
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
        action: 'create_contest',
        resourceType: 'contest',
        resourceId: contest.id,
        details: `Created contest: ${contest.name} in event: ${event.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Contest created', { contestId: contest.id, name: contest.name, eventId, userId });

    res.status(201).json({
      message: 'Contest created successfully',
      contest
    });
  } catch (error) {
    logger.error('Create contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Update contest
const updateContest = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { name, description } = req.body;
    const userId = req.user.userId;

    // Check if contest exists
    const existingContest = await prisma.contest.findUnique({
      where: { id },
      include: {
        event: {
          select: {
            name: true
          }
        }
      }
    });

    if (!existingContest) {
      return res.status(404).json({ error: 'Contest not found' });
    }

    const contest = await prisma.contest.update({
      where: { id },
      data: {
        name,
        description
      },
      include: {
        event: {
          select: {
            id: true,
            name: true
          }
        },
        _count: {
          select: {
            contestants: true,
            judges: true,
            categories: true
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
        action: 'update_contest',
        resourceType: 'contest',
        resourceId: contest.id,
        details: `Updated contest: ${contest.name} in event: ${existingContest.event.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Contest updated', { contestId: contest.id, name: contest.name, userId });

    res.json({
      message: 'Contest updated successfully',
      contest
    });
  } catch (error) {
    logger.error('Update contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Delete contest
const deleteContest = async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.userId;

    // Check if contest exists
    const existingContest = await prisma.contest.findUnique({
      where: { id },
      include: {
        event: {
          select: {
            name: true
          }
        },
        categories: true
      }
    });

    if (!existingContest) {
      return res.status(404).json({ error: 'Contest not found' });
    }

    // Check if contest has categories
    if (existingContest.categories.length > 0) {
      return res.status(400).json({ 
        error: 'Cannot delete contest with categories. Please delete categories first.' 
      });
    }

    await prisma.contest.delete({
      where: { id }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'delete_contest',
        resourceType: 'contest',
        resourceId: id,
        details: `Deleted contest: ${existingContest.name} from event: ${existingContest.event.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Contest deleted', { contestId: id, name: existingContest.name, userId });

    res.json({ message: 'Contest deleted successfully' });
  } catch (error) {
    logger.error('Delete contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Add contestant to contest
const addContestant = async (req, res) => {
  try {
    const { id } = req.params;
    const { contestantId } = req.body;
    const userId = req.user.userId;

    // Check if contest exists
    const contest = await prisma.contest.findUnique({
      where: { id },
      include: {
        event: {
          select: {
            name: true
          }
        }
      }
    });

    if (!contest) {
      return res.status(404).json({ error: 'Contest not found' });
    }

    // Check if contestant exists
    const contestant = await prisma.contestant.findUnique({
      where: { id: contestantId }
    });

    if (!contestant) {
      return res.status(404).json({ error: 'Contestant not found' });
    }

    // Check if already added
    const existingRelation = await prisma.contestContestant.findUnique({
      where: {
        contestId_contestantId: {
          contestId: id,
          contestantId
        }
      }
    });

    if (existingRelation) {
      return res.status(400).json({ error: 'Contestant already added to contest' });
    }

    await prisma.contestContestant.create({
      data: {
        contestId: id,
        contestantId
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'add_contestant_to_contest',
        resourceType: 'contest',
        resourceId: id,
        details: `Added contestant ${contestant.name} to contest ${contest.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Contestant added to contest', { contestId: id, contestantId, userId });

    res.json({ message: 'Contestant added to contest successfully' });
  } catch (error) {
    logger.error('Add contestant to contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Remove contestant from contest
const removeContestant = async (req, res) => {
  try {
    const { id, contestantId } = req.params;
    const userId = req.user.userId;

    // Check if relation exists
    const existingRelation = await prisma.contestContestant.findUnique({
      where: {
        contestId_contestantId: {
          contestId: id,
          contestantId
        }
      },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        },
        contestant: {
          select: {
            name: true
          }
        }
      }
    });

    if (!existingRelation) {
      return res.status(404).json({ error: 'Contestant not found in contest' });
    }

    await prisma.contestContestant.delete({
      where: {
        contestId_contestantId: {
          contestId: id,
          contestantId
        }
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'remove_contestant_from_contest',
        resourceType: 'contest',
        resourceId: id,
        details: `Removed contestant ${existingRelation.contestant.name} from contest ${existingRelation.contest.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Contestant removed from contest', { contestId: id, contestantId, userId });

    res.json({ message: 'Contestant removed from contest successfully' });
  } catch (error) {
    logger.error('Remove contestant from contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Add judge to contest
const addJudge = async (req, res) => {
  try {
    const { id } = req.params;
    const { judgeId } = req.body;
    const userId = req.user.userId;

    // Check if contest exists
    const contest = await prisma.contest.findUnique({
      where: { id },
      include: {
        event: {
          select: {
            name: true
          }
        }
      }
    });

    if (!contest) {
      return res.status(404).json({ error: 'Contest not found' });
    }

    // Check if judge exists
    const judge = await prisma.judge.findUnique({
      where: { id: judgeId }
    });

    if (!judge) {
      return res.status(404).json({ error: 'Judge not found' });
    }

    // Check if already added
    const existingRelation = await prisma.contestJudge.findUnique({
      where: {
        contestId_judgeId: {
          contestId: id,
          judgeId
        }
      }
    });

    if (existingRelation) {
      return res.status(400).json({ error: 'Judge already added to contest' });
    }

    await prisma.contestJudge.create({
      data: {
        contestId: id,
        judgeId
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'add_judge_to_contest',
        resourceType: 'contest',
        resourceId: id,
        details: `Added judge ${judge.name} to contest ${contest.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Judge added to contest', { contestId: id, judgeId, userId });

    res.json({ message: 'Judge added to contest successfully' });
  } catch (error) {
    logger.error('Add judge to contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Remove judge from contest
const removeJudge = async (req, res) => {
  try {
    const { id, judgeId } = req.params;
    const userId = req.user.userId;

    // Check if relation exists
    const existingRelation = await prisma.contestJudge.findUnique({
      where: {
        contestId_judgeId: {
          contestId: id,
          judgeId
        }
      },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        },
        judge: {
          select: {
            name: true
          }
        }
      }
    });

    if (!existingRelation) {
      return res.status(404).json({ error: 'Judge not found in contest' });
    }

    await prisma.contestJudge.delete({
      where: {
        contestId_judgeId: {
          contestId: id,
          judgeId
        }
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'remove_judge_from_contest',
        resourceType: 'contest',
        resourceId: id,
        details: `Removed judge ${existingRelation.judge.name} from contest ${existingRelation.contest.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Judge removed from contest', { contestId: id, judgeId, userId });

    res.json({ message: 'Judge removed from contest successfully' });
  } catch (error) {
    logger.error('Remove judge from contest error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

module.exports = {
  getContests,
  getContest,
  createContest,
  updateContest,
  deleteContest,
  addContestant,
  removeContestant,
  addJudge,
  removeJudge
};
