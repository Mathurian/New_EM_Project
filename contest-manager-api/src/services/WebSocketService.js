import { logger } from '../utils/logger.js'

/**
 * WebSocket service for real-time communication
 */
export class WebSocketService {
  constructor() {
    this.connections = new Map() // userId -> connection
    this.rooms = new Map() // roomId -> Set of userIds
  }

  /**
   * Add a new WebSocket connection
   */
  addConnection(userId, connection) {
    this.connections.set(userId, connection)
    logger.info(`WebSocket connection added for user ${userId}`)
    
    // Send connection count update to all users
    this.broadcastToAll({
      type: 'connection_count',
      data: {
        count: this.connections.size
      }
    })
  }

  /**
   * Remove a WebSocket connection
   */
  removeConnection(userId) {
    const connection = this.connections.get(userId)
    if (connection) {
      this.connections.delete(userId)
      logger.info(`WebSocket connection removed for user ${userId}`)
      
      // Send connection count update to all users
      this.broadcastToAll({
        type: 'connection_count',
        data: {
          count: this.connections.size
        }
      })
    }
  }

  /**
   * Get all connections
   */
  getConnections() {
    return this.connections
  }

  /**
   * Send message to specific user
   */
  sendToUser(userId, message) {
    const connection = this.connections.get(userId)
    if (!connection) {
      return false
    }

    try {
      connection.socket.send(JSON.stringify(message))
      return true
    } catch (error) {
      logger.error(`Error sending message to user ${userId}:`, error)
      this.removeConnection(userId)
      return false
    }
  }

  /**
   * Broadcast message to multiple users
   */
  broadcastToUsers(userIds, message) {
    let sentCount = 0
    
    userIds.forEach(userId => {
      if (this.sendToUser(userId, message)) {
        sentCount++
      }
    })

    logger.info(`Broadcast sent to ${sentCount}/${userIds.length} users`)
    return sentCount
  }

  /**
   * Broadcast message to all connected users
   */
  broadcastToAll(message) {
    let sentCount = 0
    
    this.connections.forEach((connection, userId) => {
      if (this.sendToUser(userId, message)) {
        sentCount++
      }
    })

    logger.info(`Broadcast sent to ${sentCount} users`)
    return sentCount
  }

  /**
   * Broadcast to users in a specific room
   */
  broadcastToRoom(roomId, message) {
    const roomUsers = this.rooms.get(roomId)
    if (!roomUsers) {
      return 0
    }

    return this.broadcastToUsers(Array.from(roomUsers), message)
  }

  /**
   * Add user to room
   */
  addUserToRoom(userId, roomId) {
    if (!this.rooms.has(roomId)) {
      this.rooms.set(roomId, new Set())
    }
    
    this.rooms.get(roomId).add(userId)
    logger.info(`User ${userId} added to room ${roomId}`)
  }

  /**
   * Remove user from room
   */
  removeUserFromRoom(userId, roomId) {
    const roomUsers = this.rooms.get(roomId)
    if (roomUsers) {
      roomUsers.delete(userId)
      
      // Clean up empty rooms
      if (roomUsers.size === 0) {
        this.rooms.delete(roomId)
      }
      
      logger.info(`User ${userId} removed from room ${roomId}`)
    }
  }

  /**
   * Handle incoming WebSocket messages
   */
  handleMessage(userId, data, connection) {
    const { type, payload } = data

    switch (type) {
      case 'join_room':
        this.addUserToRoom(userId, payload.roomId)
        this.sendToUser(userId, {
          type: 'room_joined',
          data: { roomId: payload.roomId }
        })
        break

      case 'leave_room':
        this.removeUserFromRoom(userId, payload.roomId)
        this.sendToUser(userId, {
          type: 'room_left',
          data: { roomId: payload.roomId }
        })
        break

      case 'ping':
        this.sendToUser(userId, {
          type: 'pong',
          data: { timestamp: new Date().toISOString() }
        })
        break

      case 'get_connection_count':
        this.sendToUser(userId, {
          type: 'connection_count',
          data: { count: this.connections.size }
        })
        break

      default:
        logger.warn(`Unknown WebSocket message type: ${type}`)
        this.sendToUser(userId, {
          type: 'error',
          data: { message: 'Unknown message type' }
        })
    }
  }

  /**
   * Broadcast scoring update
   */
  broadcastScoringUpdate(updateData) {
    const message = {
      type: 'scoring_update',
      data: {
        ...updateData,
        timestamp: new Date().toISOString()
      }
    }

    // Broadcast to all users interested in scoring
    this.broadcastToAll(message)
  }

  /**
   * Broadcast contest update
   */
  broadcastContestUpdate(contestId, updateData) {
    const message = {
      type: 'contest_update',
      data: {
        contestId,
        ...updateData,
        timestamp: new Date().toISOString()
      }
    }

    // Broadcast to all users
    this.broadcastToAll(message)
  }

  /**
   * Broadcast user update
   */
  broadcastUserUpdate(userId, updateData) {
    const message = {
      type: 'user_update',
      data: {
        userId,
        ...updateData,
        timestamp: new Date().toISOString()
      }
    }

    // Send to specific user and broadcast to admins
    this.sendToUser(userId, message)
    
    // TODO: Send to admin users
    // this.broadcastToAdmins(message)
  }

  /**
   * Broadcast system notification
   */
  broadcastNotification(notification) {
    const message = {
      type: 'notification',
      data: {
        ...notification,
        timestamp: new Date().toISOString()
      }
    }

    this.broadcastToAll(message)
  }

  /**
   * Get connection statistics
   */
  getStats() {
    return {
      total_connections: this.connections.size,
      total_rooms: this.rooms.size,
      room_details: Array.from(this.rooms.entries()).map(([roomId, users]) => ({
        roomId,
        user_count: users.size,
        users: Array.from(users)
      }))
    }
  }

  /**
   * Clean up inactive connections
   */
  cleanupInactiveConnections() {
    const now = Date.now()
    const inactiveThreshold = 5 * 60 * 1000 // 5 minutes

    this.connections.forEach((connection, userId) => {
      try {
        // Send ping to check if connection is alive
        connection.socket.ping()
      } catch (error) {
        // Connection is dead, remove it
        this.removeConnection(userId)
      }
    })
  }

  /**
   * Start periodic cleanup
   */
  startCleanup() {
    setInterval(() => {
      this.cleanupInactiveConnections()
    }, 60000) // Run every minute
  }
}