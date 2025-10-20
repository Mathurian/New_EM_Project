import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { api } from '../lib/api'

interface User {
  id: string
  email: string
  first_name: string
  last_name: string
  role: string
  is_active: boolean
  created_at: string
  updated_at: string
}

interface LoginCredentials {
  email: string
  password: string
}

interface AuthState {
  user: User | null
  isAuthenticated: boolean
  isLoading: boolean
  error: string | null
  token: string | null
}

interface AuthActions {
  setUser: (user: User | null) => void
  setLoading: (loading: boolean) => void
  setError: (error: string | null) => void
  login: (credentials: LoginCredentials) => Promise<void>
  logout: () => void
  clearError: () => void
  checkAuth: () => Promise<void>
  updateProfile: (data: Partial<User> & Record<string, any>) => Promise<void>
}

type AuthStore = AuthState & AuthActions

export const useAuthStore = create<AuthStore>()(
  persist(
    (set, get) => ({
      // State
      user: null,
      isAuthenticated: false,
      isLoading: false,
      error: null,
      token: null,

      // Actions
      setUser: (user) => {
        set({ user, isAuthenticated: !!user })
      },

      setLoading: (isLoading) => {
        set({ isLoading })
      },

      setError: (error) => {
        set({ error })
      },

      login: async (credentials) => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.post('/auth/login', credentials)
          const { user, token } = response.data
          set({ 
            user, 
            isAuthenticated: true, 
            isLoading: false, 
            error: null,
            token: token || 'session-token' // For session-based auth
          })
        } catch (err: any) {
          set({ 
            error: err.response?.data?.message || 'Login failed', 
            isLoading: false 
          })
          throw err // Re-throw to allow LoginPage to catch and show toast
        }
      },

      logout: () => {
        set({ 
          user: null, 
          isAuthenticated: false, 
          isLoading: false, 
          error: null,
          token: null
        })
      },

      clearError: () => {
        set({ error: null })
      },

      checkAuth: async () => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.get('/auth/me')
          const user = response.data?.user ?? response.data
          if (user) {
            set({ user, isAuthenticated: true, isLoading: false })
          } else {
            set({ isAuthenticated: false, isLoading: false })
          }
        } catch (error) {
          set({ isAuthenticated: false, isLoading: false })
        }
      },
      
      updateProfile: async (data) => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.put('/auth/profile', data)
          const updatedUser = response.data?.user ?? response.data
          if (updatedUser) {
            set({ user: updatedUser, isLoading: false })
          } else {
            set({ isLoading: false })
          }
        } catch (err: any) {
          set({ 
            error: err.response?.data?.message || 'Profile update failed', 
            isLoading: false 
          })
          throw err
        }
      },
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        isAuthenticated: state.isAuthenticated,
      }),
    }
  )
)

// Selectors
export const useUser = () => useAuthStore((state) => state.user)
export const useIsAuthenticated = () => useAuthStore((state) => state.isAuthenticated)
export const useAuthLoading = () => useAuthStore((state) => state.isLoading)
export const useAuthError = () => useAuthStore((state) => state.error)
export const useToken = () => useAuthStore((state) => state.token)