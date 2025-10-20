import express from 'express'
import bcrypt from 'bcryptjs'
import { UserService } from '../services/UserService.js'
import { logger } from '../utils/logger.js'
import { 
  validateLogin, 
  validateRegister, 
  validateProfileUpdate, 
  validatePasswordChange 
} from '../utils/validation.js'

const router = express.Router()
const userService = new UserService()

// Login
router.post('/login', validateLogin, async (req, res) => {
  try {
    const { email, password } = req.body

    const user = await userService.authenticateUser(email, password)
    if (!user) {
      req.flash('error', 'Invalid credentials')
      return res.status(401).json({ error: 'Invalid credentials' })
    }

    // Create session
    req.login(user, (err) => {
      if (err) {
        logger.error('Login session error:', err)
        return res.status(500).json({ error: 'Login failed' })
      }

      // Update last login
      userService.updateLastLogin(user.id)

      res.json({
        message: 'Login successful',
        user: {
          id: user.id,
          email: user.email,
          first_name: user.first_name,
          last_name: user.last_name,
          preferred_name: user.preferred_name,
          role: user.role,
          phone: user.phone,
          bio: user.bio,
          image_url: user.image_url,
          pronouns: user.pronouns,
          gender: user.gender,
          is_active: user.is_active,
          last_login: user.last_login
        }
      })
    })
  } catch (error) {
    logger.error('Login error:', error)
    res.status(500).json({ error: 'Login failed' })
  }
})

// Register (Organizer only)
router.post('/register', validateRegister, async (req, res) => {
  try {
    // Check if user is authenticated and has organizer role
    if (!req.isAuthenticated() || req.session.userRole !== 'organizer') {
      return res.status(403).json({ error: 'Access denied. Organizer role required.' })
    }

    const { password, ...userData } = req.body

    // Check if user already exists
    const existingUser = await userService.getUserByEmail(userData.email)
    if (existingUser) {
      return res.status(409).json({ error: 'User already exists' })
    }

    const user = await userService.createUser(userData, password, req.session.userId)
    
    res.status(201).json({
      message: 'User created successfully',
      user: {
        id: user.id,
        email: user.email,
        first_name: user.first_name,
        last_name: user.last_name,
        preferred_name: user.preferred_name,
        role: user.role,
        phone: user.phone,
        bio: user.bio,
        image_url: user.image_url,
        pronouns: user.pronouns,
        gender: user.gender,
        is_active: user.is_active
      }
    })
  } catch (error) {
    logger.error('Registration error:', error)
    res.status(500).json({ error: 'Registration failed' })
  }
})

// Get current user
router.get('/me', async (req, res) => {
  try {
    if (!req.isAuthenticated()) {
      return res.status(401).json({ error: 'Not authenticated' })
    }

    const user = await userService.getUserById(req.session.userId)
    if (!user) {
      req.logout()
      return res.status(401).json({ error: 'User not found' })
    }

    res.json({
      id: user.id,
      email: user.email,
      first_name: user.first_name,
      last_name: user.last_name,
      preferred_name: user.preferred_name,
      role: user.role,
      phone: user.phone,
      bio: user.bio,
      image_url: user.image_url,
      pronouns: user.pronouns,
      gender: user.gender,
      is_active: user.is_active,
      last_login: user.last_login,
      created_at: user.created_at,
      updated_at: user.updated_at
    })
  } catch (error) {
    logger.error('Get user error:', error)
    res.status(500).json({ error: 'Failed to get user' })
  }
})

// Update profile
router.put('/profile', validateProfileUpdate, async (req, res) => {
  try {
    if (!req.isAuthenticated()) {
      return res.status(401).json({ error: 'Not authenticated' })
    }

    const user = await userService.updateProfile(req.session.userId, req.body, req.session.userId)
    res.json({
      message: 'Profile updated successfully',
      user: {
        id: user.id,
        email: user.email,
        first_name: user.first_name,
        last_name: user.last_name,
        preferred_name: user.preferred_name,
        role: user.role,
        phone: user.phone,
        bio: user.bio,
        image_url: user.image_url,
        pronouns: user.pronouns,
        gender: user.gender,
        is_active: user.is_active,
        updated_at: user.updated_at
      }
    })
  } catch (error) {
    logger.error('Update profile error:', error)
    res.status(500).json({ error: 'Failed to update profile' })
  }
})

// Change password
router.put('/password', validatePasswordChange, async (req, res) => {
  try {
    if (!req.isAuthenticated()) {
      return res.status(401).json({ error: 'Not authenticated' })
    }

    const { current_password, new_password } = req.body

    // Verify current password
    const user = await userService.getUserById(req.session.userId)
    const isValidPassword = await bcrypt.compare(current_password, user.password_hash)
    
    if (!isValidPassword) {
      return res.status(400).json({ error: 'Current password is incorrect' })
    }

    await userService.updatePassword(req.session.userId, new_password, req.session.userId)
    
    res.json({ message: 'Password updated successfully' })
  } catch (error) {
    logger.error('Change password error:', error)
    res.status(500).json({ error: 'Failed to update password' })
  }
})

// Logout
router.post('/logout', async (req, res) => {
  try {
    if (req.isAuthenticated()) {
      req.logout((err) => {
        if (err) {
          logger.error('Logout error:', err)
          return res.status(500).json({ error: 'Logout failed' })
        }
        res.json({ message: 'Logged out successfully' })
      })
    } else {
      res.json({ message: 'Not logged in' })
    }
  } catch (error) {
    logger.error('Logout error:', error)
    res.status(500).json({ error: 'Logout failed' })
  }
})

// Check authentication status
router.get('/status', async (req, res) => {
  try {
    if (req.isAuthenticated()) {
      const user = await userService.getUserById(req.session.userId)
      if (user) {
        res.json({
          authenticated: true,
          user: {
            id: user.id,
            email: user.email,
            first_name: user.first_name,
            last_name: user.last_name,
            preferred_name: user.preferred_name,
            role: user.role,
            is_active: user.is_active
          }
        })
      } else {
        req.logout()
        res.json({ authenticated: false })
      }
    } else {
      res.json({ authenticated: false })
    }
  } catch (error) {
    logger.error('Auth status error:', error)
    res.json({ authenticated: false })
  }
})

export default router