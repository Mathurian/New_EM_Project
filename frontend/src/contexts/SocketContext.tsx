import React, { createContext, useContext, useEffect, useState, ReactNode, useCallback } from 'react'
import { io, Socket } from 'socket.io-client'
import { useAuth } from './AuthContext'

interface ActiveUser {
  id: string
  name: string
  role: string
  lastSeen: string
  isOnline: boolean
}

interface ScoreUpdate {
  id: string
  contestantId: string
  categoryId: string
  judgeId: string
  score: number
  timestamp: string
}

interface CertificationUpdate {
  id: string
  categoryId: string
  status: 'PENDING' | 'CERTIFIED' | 'REJECTED'
  certifiedBy: string
  timestamp: string
}

interface NotificationData {
  id: string
  type: 'SCORE_UPDATE' | 'CERTIFICATION' | 'SYSTEM' | 'EVENT'
  title: string
  message: string
  timestamp: string
  read: boolean
  userId: string
}

interface SocketContextType {
  socket: Socket | null
  isConnected: boolean
  activeUsers: ActiveUser[]
  notifications: NotificationData[]
  emit: (event: string, data?: any) => void
  on: (event: string, callback: (...args: any[]) => void) => void
  off: (event: string, callback?: (...args: any[]) => void) => void
  joinRoom: (room: string) => void
  leaveRoom: (room: string) => void
  markNotificationRead: (notificationId: string) => void
  clearNotifications: () => void
}

const SocketContext = createContext<SocketContextType | undefined>(undefined)

export const useSocket = () => {
  const context = useContext(SocketContext)
  if (context === undefined) {
    throw new Error('useSocket must be used within a SocketProvider')
  }
  return context
}

interface SocketProviderProps {
  children: ReactNode
}

export const SocketProvider: React.FC<SocketProviderProps> = ({ children }) => {
  const [socket, setSocket] = useState<Socket | null>(null)
  const [isConnected, setIsConnected] = useState(false)
  const [activeUsers, setActiveUsers] = useState<ActiveUser[]>([])
  const [notifications, setNotifications] = useState<NotificationData[]>([])
  const { user } = useAuth()

  useEffect(() => {
    if (user) {
      const newSocket = io(import.meta.env.VITE_WS_URL || window.location.origin, {
        transports: ['websocket', 'polling'],
        auth: {
          userId: user.id,
          role: user.role,
        }
      })

      newSocket.on('connect', () => {
        setIsConnected(true)
        console.log('Connected to server')
        
        // Join user-specific room
        newSocket.emit('join_user_room', { userId: user.id })
        
        // Join role-based room
        newSocket.emit('join_role_room', { role: user.role })
      })

      newSocket.on('disconnect', () => {
        setIsConnected(false)
        console.log('Disconnected from server')
      })

      newSocket.on('connect_error', (error) => {
        console.error('Connection error:', error)
        setIsConnected(false)
      })

      // Real-time event handlers
      newSocket.on('active_users_update', (users: ActiveUser[]) => {
        setActiveUsers(users)
      })

      newSocket.on('score_update', (scoreData: ScoreUpdate) => {
        console.log('Score update received:', scoreData)
        
        // Add notification
        const notification: NotificationData = {
          id: `score_${scoreData.id}`,
          type: 'SCORE_UPDATE',
          title: 'New Score Submitted',
          message: `Judge ${scoreData.judgeId} submitted a score for category ${scoreData.categoryId}`,
          timestamp: scoreData.timestamp,
          read: false,
          userId: user.id,
        }
        
        setNotifications(prev => [notification, ...prev])
        
        // Show browser notification if permission granted
        if (Notification.permission === 'granted') {
          new Notification(notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
          })
        }
      })

      newSocket.on('certification_update', (certData: CertificationUpdate) => {
        console.log('Certification update received:', certData)
        
        const notification: NotificationData = {
          id: `cert_${certData.id}`,
          type: 'CERTIFICATION',
          title: 'Certification Status Updated',
          message: `Category ${certData.categoryId} certification status: ${certData.status}`,
          timestamp: certData.timestamp,
          read: false,
          userId: user.id,
        }
        
        setNotifications(prev => [notification, ...prev])
        
        if (Notification.permission === 'granted') {
          new Notification(notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
          })
        }
      })

      newSocket.on('system_notification', (notification: NotificationData) => {
        console.log('System notification received:', notification)
        setNotifications(prev => [notification, ...prev])
        
        if (Notification.permission === 'granted') {
          new Notification(notification.title, {
            body: notification.message,
            icon: '/favicon.ico',
          })
        }
      })

      newSocket.on('event_update', (eventData: any) => {
        console.log('Event update received:', eventData)
        
        const notification: NotificationData = {
          id: `event_${eventData.id}`,
          type: 'EVENT',
          title: 'Event Update',
          message: eventData.message,
          timestamp: new Date().toISOString(),
          read: false,
          userId: user.id,
        }
        
        setNotifications(prev => [notification, ...prev])
      })

      // Request notification permission
      if (Notification.permission === 'default') {
        Notification.requestPermission()
      }

      setSocket(newSocket)

      return () => {
        newSocket.close()
      }
    }
  }, [user])

  const emit = useCallback((event: string, data?: any) => {
    if (socket && isConnected) {
      socket.emit(event, data)
    }
  }, [socket, isConnected])

  const on = useCallback((event: string, callback: (...args: any[]) => void) => {
    if (socket) {
      socket.on(event, callback)
    }
  }, [socket])

  const off = useCallback((event: string, callback?: (...args: any[]) => void) => {
    if (socket) {
      socket.off(event, callback)
    }
  }, [socket])

  const joinRoom = useCallback((room: string) => {
    if (socket && isConnected) {
      socket.emit('join_room', { room })
    }
  }, [socket, isConnected])

  const leaveRoom = useCallback((room: string) => {
    if (socket && isConnected) {
      socket.emit('leave_room', { room })
    }
  }, [socket, isConnected])

  const markNotificationRead = useCallback((notificationId: string) => {
    setNotifications(prev => 
      prev.map(notification => 
        notification.id === notificationId 
          ? { ...notification, read: true }
          : notification
      )
    )
  }, [])

  const clearNotifications = useCallback(() => {
    setNotifications([])
  }, [])

  const value = {
    socket,
    isConnected,
    activeUsers,
    notifications,
    emit,
    on,
    off,
    joinRoom,
    leaveRoom,
    markNotificationRead,
    clearNotifications,
  }

  return (
    <SocketContext.Provider value={value}>
      {children}
    </SocketContext.Provider>
  )
}
