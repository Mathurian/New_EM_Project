import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { UserService } from '../services/UserService.js'
import { AuthService } from '../services/AuthService.js'

/**
 * Authentication routes
 */
export const authRoutes = async (fastify) => {
  const userService = new UserService()
  const authService = new AuthService()

  // Login schema
  const loginSchema = {
    body: Joi.object({
      email: Joi.string().email().required(),
      password: Joi.string().min(8).required()
    })
  }

  // Register schema
  const registerSchema = {
    body: Joi.object({
      email: Joi.string().email().required(),
      password: Joi.string().min(8).required(),
      first_name: Joi.string().min(1).required(),
      last_name: Joi.string().min(1).required(),
      preferred_name: Joi.string().optional(),
      role: Joi.string().valid('organizer', 'emcee', 'judge', 'tally_master', 'auditor', 'board').required(),
      phone: Joi.string().optional(),
      bio: Joi.string().optional(),
      pronouns: Joi.string().optional()
    })
  }

  // Login endpoint
  fastify.post('/login', {
    schema: loginSchema,
    preHandler: [fastify.rateLimit()]
  }, async (request, reply) => {
    try {
      const { email, password } = request.body

      // Authenticate user
      const user = await userService.authenticate(email, password)
      if (!user) {
        return reply.status(401).send({
          error: 'Invalid credentials'
        })
      }

      // Generate JWT tokens
      const tokens = await authService.generateTokens(user)

      // Set HTTP-only cookie for refresh token
      reply.setCookie('refreshToken', tokens.refreshToken, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 7 * 24 * 60 * 60 * 1000 // 7 days
      })

      return {
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
          is_head_judge: user.is_head_judge,
          last_login_at: user.last_login_at
        },
        accessToken: tokens.accessToken,
        expiresIn: 24 * 60 * 60 // 24 hours in seconds
      }
    } catch (error) {
      fastify.log.error('Login error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Register endpoint
  fastify.post('/register', {
    schema: registerSchema,
    preHandler: [fastify.rateLimit()]
  }, async (request, reply) => {
    try {
      const userData = request.body

      // Check if user already exists
      const existingUser = await userService.findByEmail(userData.email)
      if (existingUser) {
        return reply.status(409).send({
          error: 'User with this email already exists'
        })
      }

      // Validate user data
      const validation = userService.validate(userData)
      if (!validation.isValid) {
        return reply.status(400).send({
          error: 'Validation failed',
          details: validation.errors
        })
      }

      // Create user
      const user = await userService.createUser(userData)

      // Generate JWT tokens
      const tokens = await authService.generateTokens(user)

      // Set HTTP-only cookie for refresh token
      reply.setCookie('refreshToken', tokens.refreshToken, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 7 * 24 * 60 * 60 * 1000 // 7 days
      })

      return {
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
          is_head_judge: user.is_head_judge
        },
        accessToken: tokens.accessToken,
        expiresIn: 24 * 60 * 60 // 24 hours in seconds
      }
    } catch (error) {
      fastify.log.error('Registration error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Refresh token endpoint
  fastify.post('/refresh', async (request, reply) => {
    try {
      const refreshToken = request.cookies.refreshToken

      if (!refreshToken) {
        return reply.status(401).send({
          error: 'Refresh token not provided'
        })
      }

      // Verify refresh token
      const payload = await authService.verifyRefreshToken(refreshToken)
      const user = await userService.findById(payload.userId)

      if (!user || !user.is_active) {
        return reply.status(401).send({
          error: 'Invalid refresh token'
        })
      }

      // Generate new tokens
      const tokens = await authService.generateTokens(user)

      // Set new refresh token cookie
      reply.setCookie('refreshToken', tokens.refreshToken, {
        httpOnly: true,
        secure: process.env.NODE_ENV === 'production',
        sameSite: 'strict',
        maxAge: 7 * 24 * 60 * 60 * 1000 // 7 days
      })

      return {
        accessToken: tokens.accessToken,
        expiresIn: 24 * 60 * 60 // 24 hours in seconds
      }
    } catch (error) {
      fastify.log.error('Token refresh error:', error)
      return reply.status(401).send({
        error: 'Invalid refresh token'
      })
    }
  })

  // Logout endpoint
  fastify.post('/logout', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      // Clear refresh token cookie
      reply.clearCookie('refreshToken')

      // Invalidate refresh token in database (if using token blacklist)
      // await authService.invalidateRefreshToken(request.user.id)

      return { message: 'Logged out successfully' }
    } catch (error) {
      fastify.log.error('Logout error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })

  // Get current user endpoint
  fastify.get('/me', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const user = await userService.findById(request.user.id)

      if (!user) {
        return reply.status(404).send({
          error: 'User not found'
        })
      }

      return {
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
        is_head_judge: user.is_head_judge,
        last_login_at: user.last_login_at,
        created_at: user.created_at
      }
    } catch (error) {
      fastify.log.error('Get user error:', error)
      return reply.status(500).send({
        error: 'Internal server error'
      })
    }
  })
}