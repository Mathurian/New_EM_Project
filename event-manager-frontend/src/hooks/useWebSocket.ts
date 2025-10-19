import { useEffect, useRef, useCallback } from 'react'
import { useAuthStore } from '../stores/authStore'
import { io, Socket } from 'socket.io-client'

interface WebSocketMessage {
  type: string
  data?: any
  message?: string
}

export const useWebSocket = () => {
  const socketRef = useRef<Socket | null>(null)
  const { user, token } = useAuthStore()

  const connect = useCallback(() => {
    if (!user || !token || socketRef.current?.connected) return

    const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000'
    
    socketRef.current = io(API_BASE_URL, {
      auth: {
        token
      },
      transports: ['websocket', 'polling']
    })

    socketRef.current.on('connect', () => {
      console.log('WebSocket connected')
    })

    socketRef.current.on('disconnect', () => {
      console.log('WebSocket disconnected')
    })

    socketRef.current.on('error', (error) => {
      console.error('WebSocket error:', error)
    })

    // Join user-specific room
    socketRef.current.emit('join', { userId: user.id })

  }, [user, token])

  const disconnect = useCallback(() => {
    if (socketRef.current) {
      socketRef.current.disconnect()
      socketRef.current = null
    }
  }, [])

  const sendMessage = useCallback((message: WebSocketMessage) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('message', message)
    }
  }, [])

  const joinRoom = useCallback((room: string) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('join-room', { room })
    }
  }, [])

  const leaveRoom = useCallback((room: string) => {
    if (socketRef.current?.connected) {
      socketRef.current.emit('leave-room', { room })
    }
  }, [])

  const onMessage = useCallback((callback: (message: WebSocketMessage) => void) => {
    if (socketRef.current) {
      socketRef.current.on('message', callback)
    }
  }, [])

  const offMessage = useCallback((callback: (message: WebSocketMessage) => void) => {
    if (socketRef.current) {
      socketRef.current.off('message', callback)
    }
  }, [])

  useEffect(() => {
    return () => {
      disconnect()
    }
  }, [disconnect])

  return {
    connect,
    disconnect,
    sendMessage,
    joinRoom,
    leaveRoom,
    onMessage,
    offMessage,
    isConnected: socketRef.current?.connected || false
  }
}