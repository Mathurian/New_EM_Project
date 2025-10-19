import { FastifyPluginAsync } from 'fastify'
import { WebSocketService } from '../services/WebSocketService.js'

/**
 * WebSocket routes for real-time features
 */
export const websocketRoutes = async (fastify) => {
  const wsService = new WebSocketService()

  // WebSocket connection handler
  fastify.register(async function (fastify) {
    fastify.get('/', { websocket: true }, (connection, req) => {
      const userId = req.query.userId
      const token = req.query.token

      // Authenticate WebSocket connection
      if (!token) {
        connection.socket.close(1008, 'Authentication required')
        return
      }

      try {
        // Verify JWT token
        const decoded = fastify.jwt.verify(token)
        const authenticatedUserId = decoded.userId

        // Add connection to service
        wsService.addConnection(authenticatedUserId, connection)

        // Send welcome message
        connection.socket.send(JSON.stringify({
          type: 'connected',
          message: 'Connected to real-time updates',
          userId: authenticatedUserId
        }))

        // Handle incoming messages
        connection.socket.on('message', (message) => {
          try {
            const data = JSON.parse(message.toString())
            wsService.handleMessage(authenticatedUserId, data, connection)
          } catch (error) {
            fastify.log.error('WebSocket message error:', error)
            connection.socket.send(JSON.stringify({
              type: 'error',
              message: 'Invalid message format'
            }))
          }
        })

        // Handle connection close
        connection.socket.on('close', () => {
          wsService.removeConnection(authenticatedUserId)
        })

        // Handle connection error
        connection.socket.on('error', (error) => {
          fastify.log.error('WebSocket error:', error)
          wsService.removeConnection(authenticatedUserId)
        })

      } catch (error) {
        fastify.log.error('WebSocket authentication error:', error)
        connection.socket.close(1008, 'Invalid token')
      }
    })
  })

  // Broadcast scoring updates
  fastify.post('/broadcast/scoring', {
    preHandler: [fastify.authenticate, fastify.requireRole(['judge', 'organizer'])]
  }, async (request, reply) => {
    try {
      const { type, data, targetUsers = [] } = request.body

      const message = {
        type: 'scoring_update',
        data: {
          ...data,
          timestamp: new Date().toISOString(),
          from: request.user.id
        }
      }

      if (targetUsers.length > 0) {
        // Send to specific users
        wsService.broadcastToUsers(targetUsers, message)
      } else {
        // Broadcast to all connected users
        wsService.broadcastToAll(message)
      }

      return { message: 'Broadcast sent successfully' }
    } catch (error) {
      fastify.log.error('Broadcast error:', error)
      return reply.status(500).send({
        error: 'Failed to send broadcast'
      })
    }
  })

  // Get connected users
  fastify.get('/connections', {
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const connections = wsService.getConnections()
      return {
        total_connections: connections.size,
        users: Array.from(connections.keys())
      }
    } catch (error) {
      fastify.log.error('Get connections error:', error)
      return reply.status(500).send({
        error: 'Failed to get connections'
      })
    }
  })

  // Send message to specific user
  fastify.post('/send/:userId', {
    schema: {
      body: {
        type: 'object',
        required: ['type', 'data'],
        properties: {
          type: { type: 'string' },
          data: { type: 'object' }
        }
      }
    },
    preHandler: [fastify.authenticate, fastify.requireRole(['organizer', 'board'])]
  }, async (request, reply) => {
    try {
      const { userId } = request.params
      const { type, data } = request.body

      const message = {
        type,
        data: {
          ...data,
          timestamp: new Date().toISOString(),
          from: request.user.id
        }
      }

      const sent = wsService.sendToUser(userId, message)

      if (!sent) {
        return reply.status(404).send({
          error: 'User not connected'
        })
      }

      return { message: 'Message sent successfully' }
    } catch (error) {
      fastify.log.error('Send message error:', error)
      return reply.status(500).send({
        error: 'Failed to send message'
      })
    }
  })
}