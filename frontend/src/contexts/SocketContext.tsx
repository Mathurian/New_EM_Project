import React, { createContext, useContext, useEffect, useState } from 'react'
import { io, Socket } from 'socket.io-client'
import { useAuth } from './AuthContext'
import toast from 'react-hot-toast'

interface SocketContextType {
  socket: Socket | null
  isConnected: boolean
  joinRoom: (room: string) => void
  leaveRoom: (room: string) => void
  emitEvent: (event: string, data: any) => void
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
  children: React.ReactNode
}

export const SocketProvider: React.FC<SocketProviderProps> = ({ children }) => {
  const [socket, setSocket] = useState<Socket | null>(null)
  const [isConnected, setIsConnected] = useState(false)
  const { user } = useAuth()

  useEffect(() => {
    if (!user) return

    const token = localStorage.getItem('token')
    if (!token) return

    // Initialize socket connection
    const newSocket = io(import.meta.env.VITE_API_URL || 'http://localhost:3000', {
      auth: {
        token
      }
    })

    // Connection event handlers
    newSocket.on('connect', () => {
      console.log('Socket connected')
      setIsConnected(true)
    })

    newSocket.on('disconnect', () => {
      console.log('Socket disconnected')
      setIsConnected(false)
    })

    newSocket.on('connect_error', (error) => {
      console.error('Socket connection error:', error)
      setIsConnected(false)
    })

    // Event handlers
    newSocket.on('score-updated', (data) => {
      console.log('Score updated:', data)
      // You can emit custom events or update state here
    })

    newSocket.on('certification-updated', (data) => {
      console.log('Certification updated:', data)
      toast.success(`${data.type} certification completed`)
    })

    newSocket.on('user-activity', (data) => {
      console.log('User activity:', data)
    })

    newSocket.on('system-notification', (data) => {
      console.log('System notification:', data)
      toast.info(data.message)
    })

    newSocket.on('user-disconnected', (data) => {
      console.log('User disconnected:', data)
    })

    setSocket(newSocket)

    // Cleanup on unmount
    return () => {
      newSocket.close()
    }
  }, [user])

  const joinRoom = (room: string) => {
    if (socket) {
      socket.emit('join-room', room)
    }
  }

  const leaveRoom = (room: string) => {
    if (socket) {
      socket.emit('leave-room', room)
    }
  }

  const emitEvent = (event: string, data: any) => {
    if (socket) {
      socket.emit(event, data)
    }
  }

  const value: SocketContextType = {
    socket,
    isConnected,
    joinRoom,
    leaveRoom,
    emitEvent
  }

  return (
    <SocketContext.Provider value={value}>
      {children}
    </SocketContext.Provider>
  )
}
