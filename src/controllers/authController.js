const jwt = require('jsonwebtoken');
const bcrypt = require('bcryptjs');
const { PrismaClient } = require('@prisma/client');
const { validationResult } = require('express-validator');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Generate JWT token
const generateToken = (userId, role) => {
  return jwt.sign(
    { userId, role },
    process.env.JWT_SECRET,
    { expiresIn: process.env.JWT_EXPIRES_IN || '24h' }
  );
};

// Register new user
const register = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { name, email, password, role, preferredName, gender, pronouns } = req.body;

    // Check if user already exists
    const existingUser = await prisma.user.findUnique({
      where: { email }
    });

    if (existingUser) {
      return res.status(400).json({ error: 'User with this email already exists' });
    }

    // Hash password
    const passwordHash = await bcrypt.hash(password, parseInt(process.env.BCRYPT_ROUNDS) || 12);

    // Create user
    const user = await prisma.user.create({
      data: {
        name,
        email,
        passwordHash,
        role,
        preferredName,
        gender,
        pronouns
      },
      select: {
        id: true,
        name: true,
        preferredName: true,
        email: true,
        role: true,
        gender: true,
        pronouns: true,
        createdAt: true
      }
    });

    // Generate token
    const token = generateToken(user.id, user.role);

    logger.info('User registered', { userId: user.id, email: user.email, role: user.role });

    res.status(201).json({
      message: 'User registered successfully',
      user,
      token
    });
  } catch (error) {
    logger.error('Registration error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Login user
const login = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const { email, password } = req.body;

    // Find user
    const user = await prisma.user.findUnique({
      where: { email },
      include: {
        judge: true,
        contestant: true
      }
    });

    if (!user || !user.passwordHash) {
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Verify password
    const isValidPassword = await bcrypt.compare(password, user.passwordHash);
    if (!isValidPassword) {
      logger.warn('Failed login attempt', { email, ip: req.ip });
      return res.status(401).json({ error: 'Invalid credentials' });
    }

    // Update last login
    await prisma.user.update({
      where: { id: user.id },
      data: { sessionVersion: { increment: 1 } }
    });

    // Generate token
    const token = generateToken(user.id, user.role);

    // Log successful login
    await prisma.activityLog.create({
      data: {
        userId: user.id,
        userName: user.preferredName || user.name,
        userRole: user.role,
        action: 'login_success',
        resourceType: 'user',
        resourceId: user.id,
        details: 'User logged in successfully',
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('User logged in', { userId: user.id, email: user.email, role: user.role });

    res.json({
      message: 'Login successful',
      user: {
        id: user.id,
        name: user.name,
        preferredName: user.preferredName,
        email: user.email,
        role: user.role,
        gender: user.gender,
        pronouns: user.pronouns,
        judgeId: user.judgeId,
        contestantId: user.contestantId,
        sessionVersion: user.sessionVersion
      },
      token
    });
  } catch (error) {
    logger.error('Login error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Logout user
const logout = async (req, res) => {
  try {
    const userId = req.user.userId;

    // Log logout
    await prisma.activityLog.create({
      data: {
        userId,
        userName: req.user.userName,
        userRole: req.user.role,
        action: 'logout',
        resourceType: 'user',
        resourceId: userId,
        details: 'User logged out',
        ipAddress: req.ip,
        userAgent: req.get('User-Agent'),
        logLevel: 'INFO'
      }
    });

    logger.info('User logged out', { userId });

    res.json({ message: 'Logout successful' });
  } catch (error) {
    logger.error('Logout error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Get current user profile
const getProfile = async (req, res) => {
  try {
    const userId = req.user.userId;

    const user = await prisma.user.findUnique({
      where: { id: userId },
      include: {
        judge: true,
        contestant: true
      },
      select: {
        id: true,
        name: true,
        preferredName: true,
        email: true,
        role: true,
        gender: true,
        pronouns: true,
        judgeId: true,
        contestantId: true,
        sessionVersion: true,
        createdAt: true,
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
      }
    });

    if (!user) {
      return res.status(404).json({ error: 'User not found' });
    }

    res.json({ user });
  } catch (error) {
    logger.error('Get profile error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Update user profile
const updateProfile = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const userId = req.user.userId;
    const { name, preferredName, email, gender, pronouns } = req.body;

    // Check if email is already taken by another user
    if (email) {
      const existingUser = await prisma.user.findFirst({
        where: {
          email,
          NOT: { id: userId }
        }
      });

      if (existingUser) {
        return res.status(400).json({ error: 'Email already taken' });
      }
    }

    const updatedUser = await prisma.user.update({
      where: { id: userId },
      data: {
        name,
        preferredName,
        email,
        gender,
        pronouns
      },
      select: {
        id: true,
        name: true,
        preferredName: true,
        email: true,
        role: true,
        gender: true,
        pronouns: true,
        updatedAt: true
      }
    });

    logger.info('User profile updated', { userId });

    res.json({
      message: 'Profile updated successfully',
      user: updatedUser
    });
  } catch (error) {
    logger.error('Update profile error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

// Change password
const changePassword = async (req, res) => {
  try {
    const errors = validationResult(req);
    if (!errors.isEmpty()) {
      return res.status(400).json({ errors: errors.array() });
    }

    const userId = req.user.userId;
    const { currentPassword, newPassword } = req.body;

    // Get user with password hash
    const user = await prisma.user.findUnique({
      where: { id: userId },
      select: { passwordHash: true }
    });

    if (!user || !user.passwordHash) {
      return res.status(400).json({ error: 'User not found or no password set' });
    }

    // Verify current password
    const isValidPassword = await bcrypt.compare(currentPassword, user.passwordHash);
    if (!isValidPassword) {
      return res.status(400).json({ error: 'Current password is incorrect' });
    }

    // Hash new password
    const newPasswordHash = await bcrypt.hash(newPassword, parseInt(process.env.BCRYPT_ROUNDS) || 12);

    // Update password
    await prisma.user.update({
      where: { id: userId },
      data: { passwordHash: newPasswordHash }
    });

    logger.info('User password changed', { userId });

    res.json({ message: 'Password changed successfully' });
  } catch (error) {
    logger.error('Change password error', error);
    res.status(500).json({ error: 'Internal server error' });
  }
};

module.exports = {
  register,
  login,
  logout,
  getProfile,
  updateProfile,
  changePassword
};
