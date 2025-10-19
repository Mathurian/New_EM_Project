export class WebSocketService {
  constructor() {
    this.connections = new Map()
    this.rooms = new Map()
  }

  /**
   * Add a WebSocket connection
   */
  addConnection(connection, user) {
    this.connections.set(connection, {
      user,
      rooms: new Set(),
      lastActivity: Date.now()
    })
  }

  /**
   * Remove a WebSocket connection
   */
  removeConnection(connection) {
    const connectionData = this.connections.get(connection)
    if (connectionData) {
      // Leave all rooms
      connectionData.rooms.forEach(room => {
        this.leaveRoom(connection, room)
      })
      this.connections.delete(connection)
    }
  }

  /**
   * Join a room
   */
  joinRoom(connection, roomName) {
    const connectionData = this.connections.get(connection)
    if (connectionData) {
      connectionData.rooms.add(roomName)
      
      if (!this.rooms.has(roomName)) {
        this.rooms.set(roomName, new Set())
      }
      this.rooms.get(roomName).add(connection)
    }
  }

  /**
   * Leave a room
   */
  leaveRoom(connection, roomName) {
    const connectionData = this.connections.get(connection)
    if (connectionData) {
      connectionData.rooms.delete(roomName)
      
      const room = this.rooms.get(roomName)
      if (room) {
        room.delete(connection)
        if (room.size === 0) {
          this.rooms.delete(roomName)
        }
      }
    }
  }

  /**
   * Broadcast message to a room
   */
  broadcastToRoom(roomName, message, excludeConnection = null) {
    const room = this.rooms.get(roomName)
    if (room) {
      room.forEach(connection => {
        if (connection !== excludeConnection && connection.socket.readyState === 1) {
          try {
            connection.socket.send(JSON.stringify(message))
          } catch (error) {
            console.error('Failed to send message to connection:', error)
            this.removeConnection(connection)
          }
        }
      })
    }
  }

  /**
   * Broadcast message to all connections
   */
  broadcastToAll(message, excludeConnection = null) {
    this.connections.forEach((connectionData, connection) => {
      if (connection !== excludeConnection && connection.socket.readyState === 1) {
        try {
          connection.socket.send(JSON.stringify(message))
        } catch (error) {
          console.error('Failed to send message to connection:', error)
          this.removeConnection(connection)
        }
      }
    })
  }

  /**
   * Send message to specific user
   */
  sendToUser(userId, message) {
    this.connections.forEach((connectionData, connection) => {
      if (connectionData.user.id === userId && connection.socket.readyState === 1) {
        try {
          connection.socket.send(JSON.stringify(message))
        } catch (error) {
          console.error('Failed to send message to user:', error)
          this.removeConnection(connection)
        }
      }
    })
  }

  /**
   * Handle incoming WebSocket message
   */
  handleMessage(connection, data) {
    const connectionData = this.connections.get(connection)
    if (!connectionData) return

    connectionData.lastActivity = Date.now()

    switch (data.type) {
      case 'ping':
        connection.socket.send(JSON.stringify({ type: 'pong' }))
        break

      case 'score_submitted':
        this.handleScoreSubmitted(connection, data)
        break

      case 'score_updated':
        this.handleScoreUpdated(connection, data)
        break

      case 'score_deleted':
        this.handleScoreDeleted(connection, data)
        break

      case 'user_joined':
        this.handleUserJoined(connection, data)
        break

      case 'user_left':
        this.handleUserLeft(connection, data)
        break

      case 'typing':
        this.handleTyping(connection, data)
        break

      default:
        console.log('Unknown message type:', data.type)
    }
  }

  /**
   * Handle score submitted event
   */
  handleScoreSubmitted(connection, data) {
    const message = {
      type: 'score_submitted',
      data: {
        subcategory_id: data.subcategory_id,
        contestant_id: data.contestant_id,
        judge_id: connection.user.id,
        judge_name: `${connection.user.first_name} ${connection.user.last_name}`,
        score: data.score,
        timestamp: new Date().toISOString()
      }
    }

    // Broadcast to scoring room
    if (data.room) {
      this.broadcastToRoom(data.room, message, connection)
    } else if (data.subcategory_id) {
      this.broadcastToRoom(`scoring_${data.subcategory_id}`, message, connection)
    }
  }

  /**
   * Handle score updated event
   */
  handleScoreUpdated(connection, data) {
    const message = {
      type: 'score_updated',
      data: {
        subcategory_id: data.subcategory_id,
        contestant_id: data.contestant_id,
        judge_id: connection.user.id,
        judge_name: `${connection.user.first_name} ${connection.user.last_name}`,
        old_score: data.old_score,
        new_score: data.new_score,
        timestamp: new Date().toISOString()
      }
    }

    if (data.room) {
      this.broadcastToRoom(data.room, message, connection)
    } else if (data.subcategory_id) {
      this.broadcastToRoom(`scoring_${data.subcategory_id}`, message, connection)
    }
  }

  /**
   * Handle score deleted event
   */
  handleScoreDeleted(connection, data) {
    const message = {
      type: 'score_deleted',
      data: {
        subcategory_id: data.subcategory_id,
        contestant_id: data.contestant_id,
        judge_id: connection.user.id,
        judge_name: `${connection.user.first_name} ${connection.user.last_name}`,
        timestamp: new Date().toISOString()
      }
    }

    if (data.room) {
      this.broadcastToRoom(data.room, message, connection)
    } else if (data.subcategory_id) {
      this.broadcastToRoom(`scoring_${data.subcategory_id}`, message, connection)
    }
  }

  /**
   * Handle user joined event
   */
  handleUserJoined(connection, data) {
    const message = {
      type: 'user_joined',
      data: {
        user_id: connection.user.id,
        user_name: `${connection.user.first_name} ${connection.user.last_name}`,
        user_role: connection.user.role,
        timestamp: new Date().toISOString()
      }
    }

    if (data.room) {
      this.broadcastToRoom(data.room, message, connection)
    }
  }

  /**
   * Handle user left event
   */
  handleUserLeft(connection, data) {
    const message = {
      type: 'user_left',
      data: {
        user_id: connection.user.id,
        user_name: `${connection.user.first_name} ${connection.user.last_name}`,
        timestamp: new Date().toISOString()
      }
    }

    if (data.room) {
      this.broadcastToRoom(data.room, message, connection)
    }
  }

  /**
   * Handle typing indicator
   */
  handleTyping(connection, data) {
    const message = {
      type: 'typing',
      data: {
        user_id: connection.user.id,
        user_name: `${connection.user.first_name} ${connection.user.last_name}`,
        is_typing: data.is_typing,
        timestamp: new Date().toISOString()
      }
    }

    if (data.room) {
      this.broadcastToRoom(data.room, message, connection)
    }
  }

  /**
   * Get connection statistics
   */
  getStats() {
    return {
      total_connections: this.connections.size,
      total_rooms: this.rooms.size,
      rooms: Array.from(this.rooms.keys()),
      connections_by_room: Array.from(this.rooms.entries()).map(([room, connections]) => ({
        room,
        connection_count: connections.size
      }))
    }
  }

  /**
   * Clean up inactive connections
   */
  cleanupInactiveConnections(timeout = 300000) { // 5 minutes
    const now = Date.now()
    this.connections.forEach((connectionData, connection) => {
      if (now - connectionData.lastActivity > timeout) {
        console.log('Removing inactive connection')
        this.removeConnection(connection)
      }
    })
  }

  /**
   * Start cleanup interval
   */
  startCleanupInterval(interval = 60000) { // 1 minute
    setInterval(() => {
      this.cleanupInactiveConnections()
    }, interval)
  }
}