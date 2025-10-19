import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { api } from '../lib/api'

interface User {
  id: string
  email: string
  first_name: string
  last_name: string
  role: 'organizer' | 'judge' | 'contestant' | 'emcee' | 'tally_master' | 'auditor' | 'board'
  is_active: boolean
  created_at: string
  updated_at: string
}

interface AuthState {
  user: User | null
  token: string | null
  isLoading: boolean
  error: string | null
  login: (email: string, password: string) => Promise<void>
  logout: () => void
  checkAuth: () => Promise<void>
  updateProfile: (data: Partial<User>) => Promise<void>
  clearError: () => void
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      token: null,
      isLoading: false,
      error: null,

      login: async (email: string, password: string) => {
        set({ isLoading: true, error: null })
        
        try {
          const response = await api.post('/auth/login', { email, password })
          const { user, token } = response.data
          
          // Set token in API client
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          set({ user, token, isLoading: false })
        } catch (error: any) {
          set({ 
            error: error.response?.data?.error || 'Login failed', 
            isLoading: false 
          })
          throw error
        }
      },

      logout: () => {
        // Clear token from API client
        delete api.defaults.headers.common['Authorization']
        
        set({ user: null, token: null, error: null })
      },

      checkAuth: async () => {
        const { token } = get()
        if (!token) {
          set({ isLoading: false })
          return
        }

        set({ isLoading: true })
        
        try {
          // Set token in API client
          api.defaults.headers.common['Authorization'] = `Bearer ${token}`
          
          const response = await api.get('/auth/me')
          set({ user: response.data, isLoading: false })
        } catch (error) {
          // Token is invalid, clear auth state
          delete api.defaults.headers.common['Authorization']
          set({ user: null, token: null, isLoading: false })
        }
      },

      updateProfile: async (data: Partial<User>) => {
        const { user } = get()
        if (!user) return

        set({ isLoading: true })
        
        try {
          const response = await api.put('/auth/profile', data)
          set({ user: response.data, isLoading: false })
        } catch (error: any) {
          set({ 
            error: error.response?.data?.error || 'Profile update failed', 
            isLoading: false 
          })
          throw error
        }
      },

      clearError: () => set({ error: null })
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({ 
        user: state.user, 
        token: state.token 
      })
    }
  )
)