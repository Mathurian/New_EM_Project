const { PrismaClient } = require('@prisma/client');
const { validationResult } = require('express-validator');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Get all users
const getUsers = async (req, res) => {
  try {
    const { page = 1, limit = 10, search, role } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {};
    
    if (search) {
      where.OR = [
        { name: { contains: search, mode: 'insensitive' } },
        { email: { contains: search, mode: 'insensitive' } }
      ];
    }

    if (role) {
      where.role = role;
    }

    const [users, total] = await Promise.all([
      prisma.user.findMany({
        where,
        include: {
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
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.user.count({ where })
    ]);

    res.json({
      users,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    logger.error('Get users error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get single user
const getUser = async (req, res) => {
  try {
    const { id } = req.params;

    const user = await prisma.user.findUnique({
      where: { id },
      include: {
        judge: {
          select: {
            id: true,
            name: true,
            email: true,
            gender: true,
            pronouns: true,
            bio: true,
            imagePath: true,
            isHeadJudge: true,
            createdAt: true,
            updatedAt: true
          }
        },
        contestant: {
          select: {
            id: true,
            name: true,
            email: true,
            gender: true,
            pronouns: true,
            contestantNumber: true,
            bio: true,
            imagePath: true,
            createdAt: true,
            updatedAt: true
          }
        }
      }
    });

    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json({ user });
  } catch (error) {
    logger.error('Get user error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Create new user
const createUser = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { 
      name, 
      email, 
      password, 
      role, 
      preferredName, 
      gender, 
      pronouns,
      judgeData,
      contestantData
    } = req.body;
    const userId = req.user.userId;

    // Check if user already exists
    const existingUser = await prisma.user.findUnique({
      where: { email }
    });

    if (existingUser) {
      return res.status(400).json({ error: 'User with this email already exists' });
    }

    // Hash password
    const bcrypt = require('bcryptjs');
    const passwordHash = await bcrypt.hash(password, parseInt(process.env.BCRYPT_ROUNDS) || 12);

    // Start transaction
    const result = await prisma.$transaction(async (tx) => {
      // Create user
      const user = await tx.user.create({
        data: {
          name,
          email,
          passwordHash,
          role,
          preferredName,
          gender,
          pronouns
        }
      });

      let judge = null;
      let contestant = null;

      // Create judge if role is JUDGE and judgeData provided
      if (role === 'JUDGE' && judgeData) {
        judge = await tx.judge.create({
          data: {
            name: judgeData.name || name,
            email: judgeData.email || email,
            gender: judgeData.gender || gender,
            pronouns: judgeData.pronouns || pronouns,
            bio: judgeData.bio,
            imagePath: judgeData.imagePath,
            isHeadJudge: judgeData.isHeadJudge || false
          }
        });

        // Link judge to user
        await tx.user.update({
          where: { id: user.id },
          data: { judgeId: judge.id }
        });
      }

      // Create contestant if role is CONTESTANT and contestantData provided
      if (role === 'CONTESTANT' && contestantData) {
        contestant = await tx.contestant.create({
          data: {
            name: contestantData.name || name,
            email: contestantData.email || email,
            gender: contestantData.gender || gender,
            pronouns: contestantData.pronouns || pronouns,
            contestantNumber: contestantData.contestantNumber,
            bio: contestantData.bio,
            imagePath: contestantData.imagePath
          }
        });

        // Link contestant to user
        await tx.user.update({
          where: { id: user.id },
          data: { contestantId: contestant.id }
        });
      }

      return { user, judge, contestant };
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'create_user',
        resourceType: 'user',
        resourceId: result.user.id,
        details: `Created user: ${result.user.name} with role: ${result.user.role}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('User created', { 
      userId: result.user.id, 
      name: result.user.name, 
      role: result.user.role,
      createdBy: userId 
    });

    res.status(201).json({
      message: 'User created successfully',
      user: {
        ...result.user,
        judge: result.judge,
        contestant: result.contestant
      }
    });
  } catch (error) {
    logger.error('Create user error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Update user
const updateUser = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { id } = req.params;
    const { 
      name, 
      email, 
      role, 
      preferredName, 
      gender, 
      pronouns,
      judgeData,
      contestantData
    } = req.body;
    const userId = req.user.userId;

    // Check if user exists
    const existingUser = await prisma.user.findUnique({
      where: { id },
      include: {
        judge: true,
        contestant: true
      }
    });

    if (!existingUser) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Check if email is already taken by another user
    if (email && email !== existingUser.email) {
      const emailTaken = await prisma.user.findFirst({
        where: {
          email,
          NOT: { id }
        }
      });

      if (emailTaken) {
        return res.status(400).json({ error: 'Email already taken' });
      }
    }

    // Start transaction
    const result = await prisma.$transaction(async (tx) => {
      // Update user
      const user = await tx.user.update({
        where: { id },
        data: {
          name,
          email,
          role,
          preferredName,
          gender,
          pronouns
        }
      });

      let judge = existingUser.judge;
      let contestant = existingUser.contestant;

      // Handle judge data
      if (role === 'JUDGE' && judgeData) {
        if (judge) {
          // Update existing judge
          judge = await tx.judge.update({
            where: { id: judge.id },
            data: {
              name: judgeData.name || name,
              email: judgeData.email || email,
              gender: judgeData.gender || gender,
              pronouns: judgeData.pronouns || pronouns,
              bio: judgeData.bio,
              imagePath: judgeData.imagePath,
              isHeadJudge: judgeData.isHeadJudge
            }
          });
        } else {
          // Create new judge
          judge = await tx.judge.create({
            data: {
              name: judgeData.name || name,
              email: judgeData.email || email,
              gender: judgeData.gender || gender,
              pronouns: judgeData.pronouns || pronouns,
              bio: judgeData.bio,
              imagePath: judgeData.imagePath,
              isHeadJudge: judgeData.isHeadJudge || false
            }
          });

          // Link judge to user
          await tx.user.update({
            where: { id },
            data: { judgeId: judge.id }
          });
        }
      } else if (role !== 'JUDGE' && judge) {
        // Remove judge link if role changed
        await tx.user.update({
          where: { id },
          data: { judgeId: null }
        });
        judge = null;
      }

      // Handle contestant data
      if (role === 'CONTESTANT' && contestantData) {
        if (contestant) {
          // Update existing contestant
          contestant = await tx.contestant.update({
            where: { id: contestant.id },
            data: {
              name: contestantData.name || name,
              email: contestantData.email || email,
              gender: contestantData.gender || gender,
              pronouns: contestantData.pronouns || pronouns,
              contestantNumber: contestantData.contestantNumber,
              bio: contestantData.bio,
              imagePath: contestantData.imagePath
            }
          });
        } else {
          // Create new contestant
          contestant = await tx.contestant.create({
            data: {
              name: contestantData.name || name,
              email: contestantData.email || email,
              gender: contestantData.gender || gender,
              pronouns: contestantData.pronouns || pronouns,
              contestantNumber: contestantData.contestantNumber,
              bio: contestantData.bio,
              imagePath: contestantData.imagePath
            }
          });

          // Link contestant to user
          await tx.user.update({
            where: { id },
            data: { contestantId: contestant.id }
          });
        }
      } else if (role !== 'CONTESTANT' && contestant) {
        // Remove contestant link if role changed
        await tx.user.update({
          where: { id },
          data: { contestantId: null }
        });
        contestant = null;
      }

      return { user, judge, contestant };
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'update_user',
        resourceType: 'user',
        resourceId: id,
        details: `Updated user: ${result.user.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('User updated', { userId: id, name: result.user.name, updatedBy: userId });

    res.json({
      message: 'User updated successfully',
      user: {
        ...result.user,
        judge: result.judge,
        contestant: result.contestant
      }
    });
  } catch (error) {
    logger.error('Update user error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Delete user
const deleteUser = async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.user.userId;

    // Check if user exists
    const existingUser = await prisma.user.findUnique({
      where: { id },
      include: {
        judge: true,
        contestant: true
      }
    });

    if (!existingUser) {
      return res.status(404).json({ error: 'User not found' });
    }

    // Prevent self-deletion
    if (id === userId) {
      return res.status(400).json({ error: 'Cannot delete your own account' });
    }

    await prisma.user.delete({
      where: { id }
    });

    // Log activity
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'delete_user',
        resourceType: 'user',
        resourceId: id,
        details: `Deleted user: ${existingUser.name}`,
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('User deleted', { userId: id, name: existingUser.name, deletedBy: userId });

    res.json({ message: 'User deleted successfully' });
  } catch (error) {
    logger.error('Delete user error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get all contestants
const getContestants = async (req, res) => {
  try {
    const { page = 1, limit = 10, search } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {};
    
    if (search) {
      where.OR = [
        { name: { contains: search, mode: 'insensitive' } },
        { email: { contains: search, mode: 'insensitive' } }
      ];
    }

    const [contestants, total] = await Promise.all([
      prisma.contestant.findMany({
        where,
        include: {
          users: {
            select: {
              id: true,
              name: true,
              email: true,
              role: true
            }
          },
          _count: {
            select: {
              contestContestants: true,
              categoryContestants: true
            }
          }
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.contestant.count({ where })
    ]);

    res.json({
      contestants,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    logger.error('Get contestants error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get all judges
const getJudges = async (req, res) => {
  try {
    const { page = 1, limit = 10, search } = req.query;
    const skip = (parseInt(page) - 1) * parseInt(limit);

    const where = {};
    
    if (search) {
      where.OR = [
        { name: { contains: search, mode: 'insensitive' } },
        { email: { contains: search, mode: 'insensitive' } }
      ];
    }

    const [judges, total] = await Promise.all([
      prisma.judge.findMany({
        where,
        include: {
          users: {
            select: {
              id: true,
              name: true,
              email: true,
              role: true
            }
          },
          _count: {
            select: {
              contestJudges: true,
              categoryJudges: true
            }
          }
        },
        orderBy: { createdAt: 'desc' },
        skip,
        take: parseInt(limit)
      }),
      prisma.judge.count({ where })
    ]);

    res.json({
      judges,
      pagination: {
        page: parseInt(page),
        limit: parseInt(limit),
        total,
        pages: Math.ceil(total / parseInt(limit))
      }
    });
  } catch (error) {
    logger.error('Get judges error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

module.exports = {
  getUsers,
  getUser,
  createUser,
  updateUser,
  deleteUser,
  getContestants,
  getJudges
};
