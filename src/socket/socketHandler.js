const { Server } = require('socket.io');
const { PrismaClient } = require('@prisma/client');
const logger = require('../utils/logger');

const prisma = new PrismaClient();

// Socket.IO handler
const socketHandler = (server) => {
  const io = new Server(server, {
    cors: {
      origin: process.env.NODE_ENV === 'production' 
        ? ['https://yourdomain.com'] 
        : ['http://localhost:3000', 'http://localhost:3001'],
      methods: ['GET', 'POST'],
      credentials: true
    }
  });

  // Authentication middleware for Socket.IO
  io.use(async (socket, next) => {
    try {
      const token = socket.handshake.auth.token;
      
      if (!token) {
        return next(new Error('Authentication error: No token provided'));
      }

      const jwt = require('jsonwebtoken');
      const decoded = jwt.verify(token, process.env.JWT_SECRET);
      
      // Verify user exists
      const user = await prisma.user.findUnique({
        where: { id: decoded.userId },
        select: {
          id: true,
          name: true,
          preferredName: true,
          email: true,
          role: true,
          sessionVersion: true
        }
      });

      if (!user || user.sessionVersion !== decoded.sessionVersion) {
        return next(new Error('Authentication error: Invalid session'));
      }

      socket.userId = user.id;
      socket.userName = user.preferredName || user.name;
      socket.userRole = user.role;
      next();
    } catch (error) {
      next(new Error('Authentication error: Invalid token'));
    }
  });

  io.on('connection', (socket) => {
    logger.info('User connected via WebSocket', {
      userId: socket.userId,
      userName: socket.userName,
      userRole: socket.userRole,
      socketId: socket.id
    });

    // Join user to their role-specific room
    socket.join(`role:${socket.userRole}`);
    
    // Join user to their personal room
    socket.join(`user:${socket.userId}`);

    // Join organizer/board to admin room
    if (['ORGANIZER', 'BOARD'].includes(socket.userRole)) {
      socket.join('admin');
    }

    // Handle joining specific rooms
    socket.on('join-room', (room) => {
      socket.join(room);
      logger.debug('User joined room', {
        userId: socket.userId,
        room,
        socketId: socket.id
      });
    });

    socket.on('leave-room', (room) => {
      socket.leave(room);
      logger.debug('User left room', {
        userId: socket.userId,
        room,
        socketId: socket.id
      });
    });

    // Handle real-time scoring updates
    socket.on('score-updated', (data) => {
      const { categoryId, contestantId, judgeId } = data;
      
      // Notify all users in the category room
      socket.to(`category:${categoryId}`).emit('score-updated', {
        categoryId,
        contestantId,
        judgeId,
        updatedBy: socket.userId,
        timestamp: new Date().toISOString()
      });

      logger.info('Score updated via WebSocket', {
        categoryId,
        contestantId,
        judgeId,
        updatedBy: socket.userId
      });
    });

    // Handle certification updates
    socket.on('certification-updated', (data) => {
      const { categoryId, type, certifiedBy } = data;
      
      // Notify all users in the category room
      socket.to(`category:${categoryId}`).emit('certification-updated', {
        categoryId,
        type,
        certifiedBy,
        timestamp: new Date().toISOString()
      });

      logger.info('Certification updated via WebSocket', {
        categoryId,
        type,
        certifiedBy
      });
    });

    // Handle user activity updates
    socket.on('user-activity', (data) => {
      const { action, resourceType, resourceId } = data;
      
      // Notify admin users
      socket.to('admin').emit('user-activity', {
        userId: socket.userId,
        userName: socket.userName,
        userRole: socket.userRole,
        action,
        resourceType,
        resourceId,
        timestamp: new Date().toISOString()
      });

      logger.debug('User activity via WebSocket', {
        userId: socket.userId,
        action,
        resourceType,
        resourceId
      });
    });

    // Handle system notifications
    socket.on('system-notification', (data) => {
      const { message, type, targetRoles } = data;
      
      // Only organizers/board can send system notifications
      if (!['ORGANIZER', 'BOARD'].includes(socket.userRole)) {
        socket.emit('error', { message: 'Insufficient permissions' });
        return;
      }

      if (targetRoles && Array.isArray(targetRoles)) {
        // Send to specific roles
        targetRoles.forEach(role => {
          io.to(`role:${role}`).emit('system-notification', {
            message,
            type,
            from: socket.userName,
            timestamp: new Date().toISOString()
          });
        });
      } else {
        // Send to all users
        io.emit('system-notification', {
          message,
          type,
          from: socket.userName,
          timestamp: new Date().toISOString()
        });
      }

      logger.info('System notification sent via WebSocket', {
        message,
        type,
        targetRoles,
        sentBy: socket.userId
      });
    });

    // Handle disconnect
    socket.on('disconnect', (reason) => {
      logger.info('User disconnected from WebSocket', {
        userId: socket.userId,
        userName: socket.userName,
        userRole: socket.userRole,
        socketId: socket.id,
        reason
      });

      // Notify admin users about disconnection
      socket.to('admin').emit('user-disconnected', {
        userId: socket.userId,
        userName: socket.userName,
        userRole: socket.userRole,
        timestamp: new Date().toISOString()
      });
    });

    // Handle errors
    socket.on('error', (error) => {
      logger.error('Socket error', {
        userId: socket.userId,
        socketId: socket.id,
        error: error.message
      });
    });
  });

  // Broadcast functions for server-side events
  const broadcastScoreUpdate = (categoryId, contestantId, judgeId, updatedBy) => {
    io.to(`category:${categoryId}`).emit('score-updated', {
      categoryId,
      contestantId,
      judgeId,
      updatedBy,
      timestamp: new Date().toISOString()
    });
  };

  const broadcastCertificationUpdate = (categoryId, type, certifiedBy) => {
    io.to(`category:${categoryId}`).emit('certification-updated', {
      categoryId,
      type,
      certifiedBy,
      timestamp: new Date().toISOString()
    });
  };

  const broadcastSystemNotification = (message, type, targetRoles = null) => {
    if (targetRoles && Array.isArray(targetRoles)) {
      targetRoles.forEach(role => {
        io.to(`role:${role}`).emit('system-notification', {
          message,
          type,
          timestamp: new Date().toISOString()
        });
      });
    } else {
      io.emit('system-notification', {
        message,
        type,
        timestamp: new Date().toISOString()
      });
    }
  };

  const broadcastUserActivity = (userId, userName, userRole, action, resourceType, resourceId) => {
    io.to('admin').emit('user-activity', {
      userId,
      userName,
      userRole,
      action,
      resourceType,
      resourceId,
      timestamp: new Date().toISOString()
    });
  };

  // Export broadcast functions for use in controllers
  global.socketBroadcast = {
    scoreUpdate: broadcastScoreUpdate,
    certificationUpdate: broadcastCertificationUpdate,
    systemNotification: broadcastSystemNotification,
    userActivity: broadcastUserActivity
  };

  return io;
};

module.exports = socketHandler;
