const { PrismaClient } = require('@prisma/client');
const { validationResult } = require('express-validator');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Get all categories for a contest
const getCategories = async (req, res) => {
  try {
    const { contestId } = req.params;
    const { page = 1, limit = 10, search } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = { contestId };
    
    if (search) {
      where.name = {
        contains: search,
        mode: 'insensitive'
      };
    }

    const [categories, total] = await Promise.all([
      prisma.category.findMany({
        where,
        include: {
          contest: {
            select: {
              id: true,
              name: true,
              event: {
                select: {
                  id: true,
                  name: true
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
          criteria: true,
          _count: {
            select: {
              contestants: true,
              judges: true,
              criteria: true,
              scores: true
            }
          }
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.category.count({ where })
    ]);

    res.json({
      categories,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    logger.error('Get categories error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get single category
const getCategory = async (req, res) => {
  try {
    const { id } = req.params;

    const category = await prisma.category.findUnique({
      where: { id },
      include: {
        contest: {
          include: {
            event: {
              select: {
                id: true,
                name: true
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
        criteria: {
          include: {
            scores: {
              include: {
                contestant: {
                  select: {
                    id: true,
                    name: true,
                    contestantNumber: true
                  }
                },
                judge: {
                  select: {
                    id: true,
                    name: true
                  }
                }
              }
            }
          }
        },
        scores: {
          include: {
            contestant: {
              select: {
                id: true,
                name: true,
                contestantNumber: true
              }
            },
            judge: {
              select: {
                id: true,
                name: true
              }
            },
            criterion: {
              select: {
                id: true,
                name: true,
                maxScore: true
              }
            }
          }
        },
        comments: {
          include: {
            contestant: {
              select: {
                id: true,
                name: true,
                contestantNumber: true
              }
            },
            judge: {
              select: {
                id: true,
                name: true
              }
            }
          }
        },
        certifications: true,
        auditorCertifications: true,
        _count: {
          select: {
            contestants: true,
            judges: true,
            criteria: true,
            scores: true
          }
        }
      }
    });

    if (!category) {
      return res.status(404).json({ error: 'Category not found' });
    }

    res.json({ category });
  } catch (error) {
    logger.error('Get category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Create new category
const createCategory = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { contestId } = req.params;
    const { name, description, scoreCap } = req.body;
    const userId = req.user.userId;

    // Verify contest exists
    const contest = await prisma.contest.findUnique({
      where: { id: contestId },
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

    const category = await prisma.category.create({
      data: {
        contestId,
        name,
        description,
        scoreCap: scoreCap ? parseFloat(scoreCap) : null
      },
      include: {
        contest: {
          select: {
            id: true,
            name: true,
            event: {
              select: {
                name: true
              }
            }
          }
        },
        _count: {
          select: {
            contestants: true,
            judges: true,
            criteria: true
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
        action: 'create_category',
        resourceType: 'category',
        resourceId: category.id,
        details: `Created category: ${category.name} in contest: ${contest.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Category created', { categoryId: category.id, name: category.name, contestId, userId });

    res.status(201).json({
      message: 'Category created successfully',
      category
    });
  } catch (error) {
    logger.error('Create category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Update category
const updateCategory = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { name, description, scoreCap } = req.body;
    const userId = req.user.userId;

    // Check if category exists
    const existingCategory = await prisma.category.findUnique({
      where: { id },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        }
      }
    });

    if (!existingCategory) {
      return res.status(404).json({ error: 'Category not found' });
    }

    const category = await prisma.category.update({
      where: { id },
      data: {
        name,
        description,
        scoreCap: scoreCap ? parseFloat(scoreCap) : null
      },
      include: {
        contest: {
          select: {
            id: true,
            name: true,
            event: {
              select: {
                name: true
              }
            }
          }
        },
        _count: {
          select: {
            contestants: true,
            judges: true,
            criteria: true
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
        action: 'update_category',
        resourceType: 'category',
        resourceId: category.id,
        details: `Updated category: ${category.name} in contest: ${existingCategory.contest.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Category updated', { categoryId: category.id, name: category.name, userId });

    res.json({
      message: 'Category updated successfully',
      category
    });
  } catch (error) {
    logger.error('Update category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Delete category
const deleteCategory = async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.userId;

    // Check if category exists
    const existingCategory = await prisma.category.findUnique({
      where: { id },
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
        criteria: true,
        scores: true
      }
    });

    if (!existingCategory) {
      return res.status(404).json({ error: 'Category not found' });
    }

    // Check if category has criteria or scores
    if (existingCategory.criteria.length > 0 || existingCategory.scores.length > 0) {
      return res.status(400).json({ 
        error: 'Cannot delete category with criteria or scores. Please delete criteria and scores first.' 
      });
    }

    await prisma.category.delete({
      where: { id }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'delete_category',
        resourceType: 'category',
        resourceId: id,
        details: `Deleted category: ${existingCategory.name} from contest: ${existingCategory.contest.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Category deleted', { categoryId: id, name: existingCategory.name, userId });

    res.json({ message: 'Category deleted successfully' });
  } catch (error) {
    logger.error('Delete category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Add contestant to category
const addContestant = async (req, res) => {
  try {
    const { id } = req.params;
    const { contestantId } = req.body;
    const userId = req.user.userId;

    // Check if category exists
    const category = await prisma.category.findUnique({
      where: { id },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        }
      }
    });

    if (!category) {
      return res.status(404).json({ error: 'Category not found' });
    }

    // Check if contestant exists
    const contestant = await prisma.contestant.findUnique({
      where: { id: contestantId }
    });

    if (!contestant) {
      return res.status(404).json({ error: 'Contestant not found' });
    }

    // Check if already added
    const existingRelation = await prisma.categoryContestant.findUnique({
      where: {
        categoryId_contestantId: {
          categoryId: id,
          contestantId
        }
      }
    });

    if (existingRelation) {
      return res.status(400).json({ error: 'Contestant already added to category' });
    }

    await prisma.categoryContestant.create({
      data: {
        categoryId: id,
        contestantId
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'add_contestant_to_category',
        resourceType: 'category',
        resourceId: id,
        details: `Added contestant ${contestant.name} to category ${category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Contestant added to category', { categoryId: id, contestantId, userId });

    res.json({ message: 'Contestant added to category successfully' });
  } catch (error) {
    logger.error('Add contestant to category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Remove contestant from category
const removeContestant = async (req, res) => {
  try {
    const { id, contestantId } = req.params;
    const userId = req.user.userId;

    // Check if relation exists
    const existingRelation = await prisma.categoryContestant.findUnique({
      where: {
        categoryId_contestantId: {
          categoryId: id,
          contestantId
        }
      },
      include: {
        category: {
          include: {
            contest: {
              include: {
                event: {
                  select: {
                    name: true
                  }
                }
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
      return res.status(404).json({ error: 'Contestant not found in category' });
    }

    await prisma.categoryContestant.delete({
      where: {
        categoryId_contestantId: {
          categoryId: id,
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
        action: 'remove_contestant_from_category',
        resourceType: 'category',
        resourceId: id,
        details: `Removed contestant ${existingRelation.contestant.name} from category ${existingRelation.category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Contestant removed from category', { categoryId: id, contestantId, userId });

    res.json({ message: 'Contestant removed from category successfully' });
  } catch (error) {
    logger.error('Remove contestant from category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Add judge to category
const addJudge = async (req, res) => {
  try {
    const { id } = req.params;
    const { judgeId } = req.body;
    const userId = req.user.userId;

    // Check if category exists
    const category = await prisma.category.findUnique({
      where: { id },
      include: {
        contest: {
          include: {
            event: {
              select: {
                name: true
              }
            }
          }
        }
      }
    });

    if (!category) {
      return res.status(404).json({ error: 'Category not found' });
    }

    // Check if judge exists
    const judge = await prisma.judge.findUnique({
      where: { id: judgeId }
    });

    if (!judge) {
      return res.status(404).json({ error: 'Judge not found' });
    }

    // Check if already added
    const existingRelation = await prisma.categoryJudge.findUnique({
      where: {
        categoryId_judgeId: {
          categoryId: id,
          judgeId
        }
      }
    });

    if (existingRelation) {
      return res.status(400).json({ error: 'Judge already added to category' });
    }

    await prisma.categoryJudge.create({
      data: {
        categoryId: id,
        judgeId
      }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'add_judge_to_category',
        resourceType: 'category',
        resourceId: id,
        details: `Added judge ${judge.name} to category ${category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Judge added to category', { categoryId: id, judgeId, userId });

    res.json({ message: 'Judge added to category successfully' });
  } catch (error) {
    logger.error('Add judge to category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Remove judge from category
const removeJudge = async (req, res) => {
  try {
    const { id, judgeId } = req.params;
    const userId = req.user.userId;

    // Check if relation exists
    const existingRelation = await prisma.categoryJudge.findUnique({
      where: {
        categoryId_judgeId: {
          categoryId: id,
          judgeId
        }
      },
      include: {
        category: {
          include: {
            contest: {
              include: {
                event: {
                  select: {
                    name: true
                  }
                }
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
      return res.status(404).json({ error: 'Judge not found in category' });
    }

    await prisma.categoryJudge.delete({
      where: {
        categoryId_judgeId: {
          categoryId: id,
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
        action: 'remove_judge_from_category',
        resourceType: 'category',
        resourceId: id,
        details: `Removed judge ${existingRelation.judge.name} from category ${existingRelation.category.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('Judge removed from category', { categoryId: id, judgeId, userId });

    res.json({ message: 'Judge removed from category successfully' });
  } catch (error) {
    logger.error('Remove judge from category error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

module.exports = {
  getCategories,
  getCategory,
  createCategory,
  updateCategory,
  deleteCategory,
  addContestant,
  removeContestant,
  addJudge,
  removeJudge
};
