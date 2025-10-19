import { FastifyPluginAsync } from 'fastify'
import Joi from 'joi'
import { UserService } from '../services/UserService.js'

export const authRoutes = async (fastify) => {
  const userService = new UserService()

  // Login
  fastify.post('/login', {
    schema: {
      body: Joi.object({
        email: Joi.string().email().required(),
        password: Joi.string().min(6).required()
      })
    }
  }, async (request, reply) => {
    try {
      const { email, password } = request.body

      const user = await userService.authenticateUser(email, password)
      if (!user) {
        return reply.status(401).send({ error: 'Invalid credentials' })
      }

      const token = fastify.jwt.sign({ 
        userId: user.id,
        role: user.role 
      })

      return reply.send({
        user,
        accessToken: token,
        tokenType: 'Bearer'
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Login failed' })
    }
  })

  // Register
  fastify.post('/register', {
    schema: {
      body: Joi.object({
        email: Joi.string().email().required(),
        password: Joi.string().min(6).required(),
        first_name: Joi.string().min(1).max(100).required(),
        last_name: Joi.string().min(1).max(100).required(),
        preferred_name: Joi.string().max(100).optional(),
        role: Joi.string().valid('organizer', 'judge', 'contestant', 'emcee', 'tally_master', 'auditor', 'board').required(),
        phone: Joi.string().max(20).optional(),
        bio: Joi.string().max(1000).optional(),
        pronouns: Joi.string().max(50).optional(),
        gender: Joi.string().valid('male', 'female', 'non-binary', 'prefer-not-to-say', 'other').optional()
      })
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer'])]
  }, async (request, reply) => {
    try {
      const { password, ...userData } = request.body

      // Check if user already exists
      const existingUser = await fastify.db('users').where('email', userData.email).first()
      if (existingUser) {
        return reply.status(409).send({ error: 'User already exists' })
      }

      const user = await userService.createUser(userData, password, request.user.id)
      
      return reply.status(201).send(user)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Registration failed' })
    }
  })

  // Get current user
  fastify.get('/me', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      return reply.send(request.user)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to get user' })
    }
  })

  // Update profile
  fastify.put('/profile', {
    schema: {
      body: Joi.object({
        first_name: Joi.string().min(1).max(100).optional(),
        last_name: Joi.string().min(1).max(100).optional(),
        preferred_name: Joi.string().max(100).optional(),
        phone: Joi.string().max(20).optional(),
        bio: Joi.string().max(1000).optional(),
        pronouns: Joi.string().max(50).optional(),
        gender: Joi.string().valid('male', 'female', 'non-binary', 'prefer-not-to-say', 'other').optional()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const user = await userService.updateProfile(request.user.id, request.body, request.user.id)
      return reply.send(user)
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update profile' })
    }
  })

  // Change password
  fastify.put('/password', {
    schema: {
      body: Joi.object({
        current_password: Joi.string().required(),
        new_password: Joi.string().min(6).required()
      })
    },
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const { current_password, new_password } = request.body

      // Verify current password
      const user = await fastify.db('users').where('id', request.user.id).first()
      const isValidPassword = await bcrypt.compare(current_password, user.password_hash)
      
      if (!isValidPassword) {
        return reply.status(400).send({ error: 'Current password is incorrect' })
      }

      await userService.updatePassword(request.user.id, new_password, request.user.id)
      
      return reply.send({ message: 'Password updated successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Failed to update password' })
    }
  })

  // Logout (client-side token removal)
  fastify.post('/logout', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      // In a stateless JWT system, logout is handled client-side
      // You could implement a token blacklist here if needed
      return reply.send({ message: 'Logged out successfully' })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Logout failed' })
    }
  })

  // Refresh token
  fastify.post('/refresh', {
    preHandler: [fastify.authenticate]
  }, async (request, reply) => {
    try {
      const token = fastify.jwt.sign({ 
        userId: request.user.id,
        role: request.user.role 
      })

      return reply.send({
        accessToken: token,
        tokenType: 'Bearer'
      })
    } catch (error) {
      fastify.log.error(error)
      return reply.status(500).send({ error: 'Token refresh failed' })
    }
  })
}