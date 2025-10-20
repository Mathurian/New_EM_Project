#!/bin/bash
# Ultimate Final Fix - Fix Login Function Type Error
# This script fixes the final login function type error

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "🔧 Ultimate Final Fix - Fix Login Function Type Error"
echo "====================================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix the auth store to have proper login function
print_status "Step 1: Fixing auth store login function..."
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
  logout: () => void
  clearError: () => void
  checkAuth: () => Promise<void>
  updateProfile: (data: Partial<User>) => Promise<void>
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
            token: token || 'session-token'
          })
        } catch (error: any) {
          set({ 
            error: error.response?.data?.message || 'Login failed', 
            isLoading: false 
          })
          throw error
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
        set({ isLoading: true })
        try {
          const response = await api.get('/auth/me')
          const user = response.data
          set({ user, isAuthenticated: true, isLoading: false })
        } catch (error) {
          set({ isAuthenticated: false, isLoading: false })
        }
      },

      updateProfile: async (data) => {
        set({ isLoading: true, error: null })
        try {
          const response = await api.put('/auth/profile', data)
          const updatedUser = response.data
          set({ user: updatedUser, isLoading: false })
        } catch (error: any) {
          set({ 
            error: error.response?.data?.message || 'Profile update failed', 
            isLoading: false 
          })
          throw error
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

# Step 2: Fix LoginPage.tsx to use the correct login function
print_status "Step 2: Fixing LoginPage.tsx..."
cat > src/pages/auth/LoginPage.tsx << 'EOF'
import { useState } from 'react'
import { useAuthStore } from '../../stores/authStore'
import { Button, Input, Card, CardContent, CardDescription, CardHeader, CardTitle } from '../components'
import { Mail, Lock } from 'lucide-react'

export const LoginPage = () => {
  const { login, isLoading, error } = useAuthStore()
  const [formData, setFormData] = useState({
    email: '',
    password: '',
  })

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault()
    try {
      await login(formData)
    } catch (error) {
      console.error('Login failed:', error)
    }
  }

  return (
    <div className="min-h-screen flex items-center justify-center bg-gray-50">
      <Card className="w-full max-w-md">
        <CardHeader className="text-center">
          <CardTitle className="text-2xl font-bold">Sign In</CardTitle>
          <CardDescription>Enter your credentials to access your account</CardDescription>
        </CardHeader>
        <CardContent>
          <form onSubmit={handleSubmit} className="space-y-4">
            <div className="space-y-2">
              <label className="text-sm font-medium">Email</label>
              <div className="relative">
                <Mail className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  type="email"
                  placeholder="Enter your email"
                  value={formData.email}
                  onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  className="pl-10"
                  required
                />
              </div>
            </div>
            <div className="space-y-2">
              <label className="text-sm font-medium">Password</label>
              <div className="relative">
                <Lock className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  type="password"
                  placeholder="Enter your password"
                  value={formData.password}
                  onChange={(e) => setFormData({ ...formData, password: e.target.value })}
                  className="pl-10"
                  required
                />
              </div>
            </div>
            {error && (
              <div className="text-sm text-red-600">{error}</div>
            )}
            <Button type="submit" className="w-full" disabled={isLoading}>
              {isLoading ? 'Signing in...' : 'Sign In'}
            </Button>
          </form>
        </CardContent>
      </Card>
    </div>
  )
}
EOF

# Step 3: Fix ProfilePage.tsx to use the correct import
print_status "Step 3: Fixing ProfilePage.tsx..."
cat > src/pages/ProfilePage.tsx << 'EOF'
import { useState } from 'react'
import { useAuthStore } from '../stores/authStore'
import { Button, Input, Card, CardContent, CardDescription, CardHeader, CardTitle, Badge } from '../components'
import { Save, Shield, User, Mail } from 'lucide-react'

export const ProfilePage = () => {
  const { user, updateProfile } = useAuthStore()
  const [isEditing, setIsEditing] = useState(false)
  const [formData, setFormData] = useState({
    first_name: user?.first_name || '',
    last_name: user?.last_name || '',
    email: user?.email || '',
  })

  const handleSave = async () => {
    try {
      await updateProfile(formData)
      setIsEditing(false)
    } catch (error) {
      console.error('Failed to update profile:', error)
    }
  }

  const handleCancel = () => {
    setFormData({
      first_name: user?.first_name || '',
      last_name: user?.last_name || '',
      email: user?.email || '',
    })
    setIsEditing(false)
  }

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Profile</h1>

      <div className="grid gap-6 md:grid-cols-2">
        {/* Profile Information */}
        <Card>
          <CardHeader>
            <CardTitle>Profile Information</CardTitle>
            <CardDescription>Manage your personal information</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-3">
                <div className="h-12 w-12 rounded-full bg-primary flex items-center justify-center">
                  <User className="h-6 w-6 text-primary-foreground" />
                </div>
                <div>
                  <h3 className="font-medium">{user?.first_name} {user?.last_name}</h3>
                  <p className="text-sm text-muted-foreground">{user?.email}</p>
                </div>
              </div>
              <Button
                variant="outline"
                onClick={() => setIsEditing(!isEditing)}
              >
                {isEditing ? 'Cancel' : 'Edit'}
              </Button>
            </div>

            {isEditing ? (
              <div className="space-y-4">
                <div>
                  <label className="text-sm font-medium">First Name</label>
                  <Input
                    value={formData.first_name}
                    onChange={(e) => setFormData({ ...formData, first_name: e.target.value })}
                  />
                </div>
                <div>
                  <label className="text-sm font-medium">Last Name</label>
                  <Input
                    value={formData.last_name}
                    onChange={(e) => setFormData({ ...formData, last_name: e.target.value })}
                  />
                </div>
                <div>
                  <label className="text-sm font-medium">Email</label>
                  <Input
                    value={formData.email}
                    onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                  />
                </div>
                <div className="flex gap-2">
                  <Button onClick={handleSave}>
                    <Save className="h-4 w-4 mr-2" />
                    Save Changes
                  </Button>
                  <Button variant="outline" onClick={handleCancel}>
                    Cancel
                  </Button>
                </div>
              </div>
            ) : (
              <div className="space-y-2">
                <div className="flex items-center text-sm">
                  <Mail className="h-4 w-4 mr-2 text-muted-foreground" />
                  {user?.email}
                </div>
                <div className="flex items-center text-sm">
                  <Shield className="h-4 w-4 mr-2 text-muted-foreground" />
                  Role: {user?.role}
                </div>
              </div>
            )}
          </CardContent>
        </Card>

        {/* Account Status */}
        <Card>
          <CardHeader>
            <CardTitle>Account Status</CardTitle>
            <CardDescription>Your account information and status</CardDescription>
          </CardHeader>
          <CardContent className="space-y-4">
            <div className="flex items-center justify-between">
              <span className="text-sm font-medium">Account Status</span>
              <Badge variant={user?.is_active ? "default" : "destructive"}>
                {user?.is_active ? "Active" : "Inactive"}
              </Badge>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm font-medium">Role</span>
              <Badge variant="secondary">{user?.role}</Badge>
            </div>
            <div className="flex items-center justify-between">
              <span className="text-sm font-medium">Member Since</span>
              <span className="text-sm text-muted-foreground">
                {user?.created_at ? new Date(user.created_at).toLocaleDateString() : 'N/A'}
              </span>
            </div>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
EOF

# Step 4: Fix DashboardPage.tsx to use the correct import
print_status "Step 4: Fixing DashboardPage.tsx..."
cat > src/pages/DashboardPage.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, LoadingSpinner } from '../components'
import { useAuthStore } from '../stores/authStore'
import { Plus, Calendar, Trophy, Users, BarChart3, Eye } from 'lucide-react'

export const DashboardPage = () => {
  const { user } = useAuthStore()
  
  const { data: stats, isLoading, error } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => api.getDashboardStats().then(res => res.data),
  })

  const { data: events } = useQuery({
    queryKey: ['events'],
    queryFn: () => api.getEvents().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading dashboard</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Dashboard</h1>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add Event
        </Button>
      </div>

      {/* Welcome Message */}
      {user && (
        <Card>
          <CardContent className="pt-6">
            <h2 className="text-lg font-semibold">Welcome back, {user.first_name}!</h2>
            <p className="text-muted-foreground">Here's what's happening with your events.</p>
          </CardContent>
        </Card>
      )}

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Events</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.totalEvents || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Contests</CardTitle>
            <Trophy className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.activeContests || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.totalUsers || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Completed Scores</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.completedScores || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Events */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Events</CardTitle>
          <CardDescription>Latest events in the system</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {events?.slice(0, 5).map((event: any) => (
              <div key={event.id} className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">{event.name}</h4>
                  <p className="text-sm text-muted-foreground">
                    {formatDate(event.start_date)} - {formatDate(event.end_date)}
                  </p>
                </div>
                <Button variant="outline" size="sm">
                  <Eye className="h-4 w-4" />
                </Button>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Quick Actions */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="text-center">
          <CardContent className="pt-6">
            <Calendar className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Create Event</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Trophy className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Add Contest</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Users className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Manage Users</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <BarChart3 className="h-6 w-6 mb-2" />
            <h3 className="font-medium">View Reports</h3>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
EOF

print_success "All files fixed"

# Step 5: Run type check
print_status "Step 5: Running TypeScript type check..."
if npm run type-check; then
    print_success "🎉 TypeScript type check passed!"
else
    print_warning "TypeScript type check still has issues, but continuing..."
fi

# Step 6: Try building
print_status "Step 6: Attempting to build..."
if npm run build; then
    print_success "🎉 Build completed successfully!"
    print_status "📁 Build output is in the 'dist' directory"
    echo ""
    print_status "🎯 Frontend build is now working!"
    print_status "You can now:"
    print_status "  - Serve the frontend with: npm run preview"
    print_status "  - Integrate with your backend server"
    print_status "  - Deploy the application"
else
    print_error "Build failed"
    print_status "Remaining build errors:"
    npm run build 2>&1 | head -20
fi
