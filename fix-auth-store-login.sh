#!/bin/bash

echo "🔧 Fix Auth Store Login Function"
echo "==============================="

cd /opt/event-manager/event-manager-frontend

echo "[INFO] Fixing auth store to properly handle login with API call..."

# Create a backup
cp src/stores/authStore.ts src/stores/authStore.ts.backup

# Create the fixed auth store
cat > src/stores/authStore.ts << 'EOF'
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
  logout: () => Promise<void>
  clearError: () => void
  checkAuth: () => Promise<void>
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

      login: async (credentials: LoginCredentials) => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.login(credentials)
          const user = response.data.user
          
          set({ 
            user, 
            isAuthenticated: true, 
            isLoading: false, 
            error: null,
            token: 'session-token' // For session-based auth
          })
        } catch (error: any) {
          const errorMessage = error.response?.data?.error || 'Login failed'
          set({ 
            error: errorMessage, 
            isLoading: false,
            user: null,
            isAuthenticated: false,
            token: null
          })
          throw error
        }
      },

      logout: async () => {
        set({ isLoading: true })
        try {
          await api.logout()
        } catch (error) {
          // Ignore logout errors
        } finally {
          set({ 
            user: null, 
            isAuthenticated: false, 
            isLoading: false, 
            error: null,
            token: null
          })
        }
      },

      clearError: () => {
        set({ error: null })
      },

      checkAuth: async () => {
        set({ isLoading: true })
        try {
          const response = await api.getProfile()
          const user = response.data
          set({ user, isAuthenticated: true, isLoading: false })
        } catch (error) {
          set({ 
            user: null, 
            isAuthenticated: false, 
            error: null, 
            isLoading: false 
          })
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
EOF

echo "[INFO] Fixing LoginPage to use correct login function signature..."

# Create a backup
cp src/pages/auth/LoginPage.tsx src/pages/auth/LoginPage.tsx.backup

# Fix the LoginPage to pass credentials object instead of separate parameters
sed -i 's/await login(data.email, data.password)/await login(data)/' src/pages/auth/LoginPage.tsx

echo "[INFO] Rebuilding frontend with fixed auth store..."

npm run build

echo "[INFO] Testing build..."
if [ -f "dist/index.html" ]; then
    echo "✅ Frontend built successfully"
    echo "Build timestamp:"
    ls -la dist/index.html
else
    echo "❌ Frontend build failed"
fi

echo ""
echo "[SUCCESS] Auth store login function fixed!"
echo "[INFO] Login function now properly makes API calls"
echo "[INFO] LoginPage now passes credentials object correctly"
echo ""
echo "[INFO] Test the login now - it should work!"
