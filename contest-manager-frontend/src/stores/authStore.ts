import { create } from 'zustand'
import { persist } from 'zustand/middleware'
import { api } from '../lib/api'
import toast from 'react-hot-toast'

interface User {
  id: string
  email: string
  first_name: string
  last_name: string
  preferred_name?: string
  role: 'organizer' | 'emcee' | 'judge' | 'tally_master' | 'auditor' | 'board'
  phone?: string
  bio?: string
  image_url?: string
  pronouns?: string
  is_head_judge: boolean
  last_login_at?: string
  created_at: string
}

interface AuthState {
  user: User | null
  accessToken: string | null
  isLoading: boolean
  login: (email: string, password: string) => Promise<void>
  register: (userData: RegisterData) => Promise<void>
  logout: () => void
  checkAuth: () => Promise<void>
  updateUser: (userData: Partial<User>) => void
}

interface RegisterData {
  email: string
  password: string
  first_name: string
  last_name: string
  preferred_name?: string
  role: string
  phone?: string
  bio?: string
  pronouns?: string
}

export const useAuthStore = create<AuthState>()(
  persist(
    (set, get) => ({
      user: null,
      accessToken: null,
      isLoading: false,

      login: async (email: string, password: string) => {
        set({ isLoading: true })
        try {
          const response = await api.post('/auth/login', { email, password })
          const { user, accessToken } = response.data

          set({ user, accessToken, isLoading: false })
          
          // Set default authorization header
          api.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`
          
          toast.success(`Welcome back, ${user.first_name}!`)
        } catch (error: any) {
          set({ isLoading: false })
          const message = error.response?.data?.error || 'Login failed'
          toast.error(message)
          throw error
        }
      },

      register: async (userData: RegisterData) => {
        set({ isLoading: true })
        try {
          const response = await api.post('/auth/register', userData)
          const { user, accessToken } = response.data

          set({ user, accessToken, isLoading: false })
          
          // Set default authorization header
          api.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`
          
          toast.success(`Welcome, ${user.first_name}!`)
        } catch (error: any) {
          set({ isLoading: false })
          const message = error.response?.data?.error || 'Registration failed'
          toast.error(message)
          throw error
        }
      },

      logout: () => {
        set({ user: null, accessToken: null })
        delete api.defaults.headers.common['Authorization']
        toast.success('Logged out successfully')
      },

      checkAuth: async () => {
        const { accessToken } = get()
        if (!accessToken) {
          set({ isLoading: false })
          return
        }

        set({ isLoading: true })
        try {
          // Set authorization header
          api.defaults.headers.common['Authorization'] = `Bearer ${accessToken}`
          
          const response = await api.get('/auth/me')
          set({ user: response.data, isLoading: false })
        } catch (error) {
          // Token is invalid, clear auth state
          set({ user: null, accessToken: null, isLoading: false })
          delete api.defaults.headers.common['Authorization']
        }
      },

      updateUser: (userData: Partial<User>) => {
        const { user } = get()
        if (user) {
          set({ user: { ...user, ...userData } })
        }
      }
    }),
    {
      name: 'auth-storage',
      partialize: (state) => ({
        user: state.user,
        accessToken: state.accessToken
      })
    }
  )
)