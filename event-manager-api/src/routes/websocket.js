import { FastifyPluginAsync } from 'fastify'
import { WebSocketService } from '../services/WebSocketService.js'

export const websocketRoutes = async (fastify) => {
  const webSocketService = new WebSocketService()

  // WebSocket connection handler
  fastify.register(async function (fastify) {
    fastify.get('/scoring', { websocket: true }, (connection, req) => {
      // Authenticate WebSocket connection
      const token = req.query.token
      if (!token) {
        connection.socket.close(1008, 'Authentication required')
        return
      }

      try {
        // Verify JWT token
        const decoded = fastify.jwt.verify(token)
        
        // Get user from database
        fastify.db('users').where('id', decoded.userId).first()
          .then(user => {
            if (!user || !user.is_active) {
              connection.socket.close(1008, 'Invalid user')
              return
            }

            // Add user to connection
            connection.user = user
            webSocketService.addConnection(connection, user)

            // Send welcome message
            connection.socket.send(JSON.stringify({
              type: 'connected',
              message: 'Connected to real-time scoring',
              user: {
                id: user.id,
                name: `${user.first_name} ${user.last_name}`,
                role: user.role
              }
            }))

            // Handle incoming messages
            connection.socket.on('message', (message) => {
              try {
                const data = JSON.parse(message.toString())
                webSocketService.handleMessage(connection, data)
              } catch (error) {
                connection.socket.send(JSON.stringify({
                  type: 'error',
                  message: 'Invalid message format'
                }))
              }
            })

            // Handle disconnection
            connection.socket.on('close', () => {
              webSocketService.removeConnection(connection)
            })

            // Handle errors
            connection.socket.on('error', (error) => {
              console.error('WebSocket error:', error)
              webSocketService.removeConnection(connection)
            })

          })
          .catch(error => {
            console.error('User verification failed:', error)
            connection.socket.close(1008, 'Authentication failed')
          })

      } catch (error) {
        console.error('JWT verification failed:', error)
        connection.socket.close(1008, 'Invalid token')
      }
    })
  })

  // Join scoring room
  fastify.register(async function (fastify) {
    fastify.get('/scoring/:subcategoryId', { websocket: true }, (connection, req) => {
      const { subcategoryId } = req.params
      const token = req.query.token

      if (!token) {
        connection.socket.close(1008, 'Authentication required')
        return
      }

      try {
        const decoded = fastify.jwt.verify(token)
        
        fastify.db('users').where('id', decoded.userId).first()
          .then(user => {
            if (!user || !user.is_active) {
              connection.socket.close(1008, 'Invalid user')
              return
            }

            connection.user = user
            webSocketService.addConnection(connection, user)
            webSocketService.joinRoom(connection, `scoring_${subcategoryId}`)

            connection.socket.send(JSON.stringify({
              type: 'joined_room',
              room: `scoring_${subcategoryId}`,
              message: `Joined scoring room for subcategory ${subcategoryId}`
            }))

            // Handle messages
            connection.socket.on('message', (message) => {
              try {
                const data = JSON.parse(message.toString())
                data.room = `scoring_${subcategoryId}`
                webSocketService.handleMessage(connection, data)
              } catch (error) {
                connection.socket.send(JSON.stringify({
                  type: 'error',
                  message: 'Invalid message format'
                }))
              }
            })

            connection.socket.on('close', () => {
              webSocketService.leaveRoom(connection, `scoring_${subcategoryId}`)
              webSocketService.removeConnection(connection)
            })

          })
          .catch(error => {
            console.error('User verification failed:', error)
            connection.socket.close(1008, 'Authentication failed')
          })

      } catch (error) {
        console.error('JWT verification failed:', error)
        connection.socket.close(1008, 'Invalid token')
      }
    })
  })

  // Join event room
  fastify.register(async function (fastify) {
    fastify.get('/event/:eventId', { websocket: true }, (connection, req) => {
      const { eventId } = req.params
      const token = req.query.token

      if (!token) {
        connection.socket.close(1008, 'Authentication required')
        return
      }

      try {
        const decoded = fastify.jwt.verify(token)
        
        fastify.db('users').where('id', decoded.userId).first()
          .then(user => {
            if (!user || !user.is_active) {
              connection.socket.close(1008, 'Invalid user')
              return
            }

            connection.user = user
            webSocketService.addConnection(connection, user)
            webSocketService.joinRoom(connection, `event_${eventId}`)

            connection.socket.send(JSON.stringify({
              type: 'joined_room',
              room: `event_${eventId}`,
              message: `Joined event room for event ${eventId}`
            }))

            // Handle messages
            connection.socket.on('message', (message) => {
              try {
                const data = JSON.parse(message.toString())
                data.room = `event_${eventId}`
                webSocketService.handleMessage(connection, data)
              } catch (error) {
                connection.socket.send(JSON.stringify({
                  type: 'error',
                  message: 'Invalid message format'
                }))
              }
            })

            connection.socket.on('close', () => {
              webSocketService.leaveRoom(connection, `event_${eventId}`)
              webSocketService.removeConnection(connection)
            })

          })
          .catch(error => {
            console.error('User verification failed:', error)
            connection.socket.close(1008, 'Authentication failed')
          })

      } catch (error) {
        console.error('JWT verification failed:', error)
        connection.socket.close(1008, 'Invalid token')
      }
    })
  })
}