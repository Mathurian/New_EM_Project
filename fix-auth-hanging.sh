#!/bin/bash

# Fix authentication hanging issue
set -e

INSTALL_DIR="/opt/event-manager"
AUTH_FILE="$INSTALL_DIR/event-manager-api/src/routes/auth.js"
USER_SERVICE_FILE="$INSTALL_DIR/event-manager-api/src/services/UserService.js"

echo "Fixing authentication hanging issue..."

# Backup files
sudo cp "$AUTH_FILE" "$AUTH_FILE.backup"
sudo cp "$USER_SERVICE_FILE" "$USER_SERVICE_FILE.backup"

# Fix the auth route to add proper error handling and timeouts
sudo tee "$AUTH_FILE" > /dev/null << 'EOF'
import express from 'express'
import bcrypt from 'bcryptjs'
import { UserService } from '../services/UserService.js'
import { logger } from '../utils/logger.js'

const router = express.Router()
const userService = new UserService()

// Login
router.post('/login', async (req, res) => {
  try {
    console.log('Login attempt received:', { email: req.body.email })
    
    const { email, password } = req.body

    if (!email || !password) {
      console.log('Missing email or password')
      return res.status(400).json({ error: 'Email and password are required' })
    }

    console.log('Attempting authentication for:', email)
    const user = await userService.authenticateUser(email, password)
    
    if (!user) {
      console.log('Authentication failed for:', email)
      req.flash('error', 'Invalid credentials')
      return res.status(401).json({ error: 'Invalid credentials' })
    }

    console.log('Authentication successful for:', email)
    
    // Create session
    req.login(user, (err) => {
      if (err) {
        console.log('Login session error:', err)
        logger.error('Login session error:', err)
        return res.status(500).json({ error: 'Login failed' })
      }

      console.log('Session created successfully for:', email)
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
    console.log('Login error:', error)
    logger.error('Login error:', error)
    res.status(500).json({ error: 'Login failed' })
  }
})

// Logout
router.post('/logout', (req, res) => {
  req.logout((err) => {
    if (err) {
      logger.error('Logout error:', err)
      return res.status(500).json({ error: 'Logout failed' })
    }
    res.json({ message: 'Logout successful' })
  })
})

// Get current user
router.get('/me', async (req, res) => {
  try {
    if (!req.isAuthenticated()) {
      return res.status(401).json({ error: 'Not authenticated' })
    }

    const user = await userService.getUserById(req.session.userId)
    if (!user) {
      return res.status(404).json({ error: 'User not found' })
    }

    res.json({
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
  } catch (error) {
    logger.error('Get user error:', error)
    res.status(500).json({ error: 'Failed to get user' })
  }
})

export default router
EOF

# Fix the UserService to add proper error handling
sudo tee "$USER_SERVICE_FILE" > /dev/null << 'EOF'
import bcrypt from 'bcryptjs'
import { db } from '../database/connection.js'
import { logger } from '../utils/logger.js'

export class UserService {
  async authenticateUser(email, password) {
    try {
      console.log('UserService: Starting authentication for:', email)
      
      const user = await db('users')
        .where({ email: email.toLowerCase(), is_active: true })
        .first()

      console.log('UserService: User lookup result:', user ? 'found' : 'not found')

      if (!user) {
        console.log('UserService: User not found')
        return null
      }

      console.log('UserService: Comparing password...')
      const isValidPassword = await bcrypt.compare(password, user.password_hash)
      console.log('UserService: Password comparison result:', isValidPassword)
      
      if (!isValidPassword) {
        console.log('UserService: Invalid password')
        return null
      }

      console.log('UserService: Authentication successful, updating last login')
      
      // Update last login
      await db('users')
        .where({ id: user.id })
        .update({ last_login: new Date() })

      console.log('UserService: Last login updated')
      return user
    } catch (error) {
      console.log('UserService: Authentication error:', error)
      logger.error('Authentication error:', error)
      return null
    }
  }

  async getUserById(id) {
    try {
      return await db('users')
        .where({ id, is_active: true })
        .first()
    } catch (error) {
      logger.error('Get user by ID error:', error)
      return null
    }
  }

  async createUser(userData) {
    try {
      const hashedPassword = await bcrypt.hash(userData.password, 12)
      
      const [user] = await db('users')
        .insert({
          ...userData,
          password_hash: hashedPassword,
          email: userData.email.toLowerCase(),
          created_at: new Date(),
          updated_at: new Date()
        })
        .returning('*')

      return user
    } catch (error) {
      logger.error('Create user error:', error)
      throw error
    }
  }
}
EOF

echo "Auth files updated with better error handling and logging"

# Restart the service
echo "Restarting Event Manager service..."
sudo systemctl restart event-manager

sleep 5

# Check service status
if sudo systemctl is-active --quiet event-manager; then
    echo "✅ Service is running!"
    
    # Test the API endpoint
    echo "Testing API endpoint..."
    sleep 3
    curl -X POST http://localhost:3000/api/auth/login \
      -H "Content-Type: application/json" \
      -d '{"email":"admin@okckinkweekend.com","password":"Dittibop5!"}' \
      -w "\nHTTP Status: %{http_code}\n" --max-time 15 || echo "API test failed"
else
    echo "❌ Service failed to start. Checking logs..."
    sudo journalctl -u event-manager --no-pager -l --since "1 minute ago"
fi

echo "Fix completed!"
