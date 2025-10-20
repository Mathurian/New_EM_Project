#!/bin/bash
# Final Cleanup Script - Remove Unused Imports and Fix Type Issues
# This script fixes the remaining 46 TypeScript errors

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

echo "🧹 Final Cleanup - Remove Unused Imports and Fix Types"
echo "====================================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix LoginPage.tsx type error
print_status "Step 1: Fixing LoginPage.tsx type error..."
cat > src/pages/auth/LoginPage.tsx << 'EOF'
import { useState } from 'react'
import { useAuthStore } from '../../auth'
import { Button, Input, Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components'
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
      await login(formData.email, formData.password)
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

# Step 2: Fix Header.tsx - remove unused React import
print_status "Step 2: Fixing Header.tsx..."
cat > src/components/layout/Header.tsx << 'EOF'
import { useState } from 'react'
import { Button } from '../ui/Button'
import { Input } from '../ui/Input'
import { X, Menu, Search, Bell } from 'lucide-react'

export const Header = () => {
  const [isSearchOpen, setIsSearchOpen] = useState(false)

  return (
    <header className="bg-white border-b border-gray-200 px-6 py-4">
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <Button variant="ghost" size="icon">
            <Menu className="h-5 w-5" />
          </Button>
          <h1 className="text-xl font-semibold">Event Manager</h1>
        </div>

        <div className="flex items-center space-x-4">
          {isSearchOpen ? (
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
              <Input
                placeholder="Search..."
                className="pl-10 pr-10"
                autoFocus
              />
              <Button
                variant="ghost"
                size="icon"
                className="absolute right-2 top-1/2 transform -translate-y-1/2"
                onClick={() => setIsSearchOpen(false)}
              >
                <X className="h-4 w-4" />
              </Button>
            </div>
          ) : (
            <Button
              variant="ghost"
              size="icon"
              onClick={() => setIsSearchOpen(true)}
            >
              <Search className="h-4 w-4" />
            </Button>
          )}

          <Button variant="ghost" size="icon">
            <Bell className="h-5 w-5" />
          </Button>
        </div>
      </div>
    </header>
  )
}
EOF

# Step 3: Fix Sidebar.tsx - remove unused location import
print_status "Step 3: Fixing Sidebar.tsx..."
cat > src/components/layout/Sidebar.tsx << 'EOF'
import { NavLink } from 'react-router-dom'
import { 
  Home, 
  Calendar, 
  Trophy, 
  Users, 
  Settings, 
  BarChart3,
  User,
  Gavel,
  Mic,
  Eye,
  Crown
} from 'lucide-react'
import { useAuthStore } from '../../stores/authStore'
import { cn } from '../../lib/utils'

const navigation = [
  { name: 'Dashboard', href: '/', icon: Home },
  { name: 'Events', href: '/events', icon: Calendar },
  { name: 'Scoring', href: '/scoring', icon: BarChart3 },
  { name: 'Results', href: '/results', icon: Trophy },
  { name: 'Users', href: '/users', icon: Users },
  { name: 'Settings', href: '/settings', icon: Settings },
]

const roleNavigation = {
  judge: [
    { name: 'Judge Dashboard', href: '/judge', icon: Gavel },
  ],
  emcee: [
    { name: 'Emcee Dashboard', href: '/emcee', icon: Mic },
  ],
  tally_master: [
    { name: 'Tally Master', href: '/tally-master', icon: BarChart3 },
  ],
  auditor: [
    { name: 'Auditor', href: '/auditor', icon: Eye },
  ],
  board: [
    { name: 'Board', href: '/board', icon: Crown },
  ],
}

export const Sidebar = () => {
  const { user } = useAuthStore()

  return (
    <div className="w-64 bg-gray-900 text-white min-h-screen">
      <div className="p-6">
        <div className="flex items-center space-x-3">
          <div className="h-8 w-8 bg-primary rounded-lg flex items-center justify-center">
            <User className="h-5 w-5 text-primary-foreground" />
          </div>
          <div>
            <h2 className="text-lg font-semibold">Event Manager</h2>
            <p className="text-sm text-gray-400">{user?.role}</p>
          </div>
        </div>
      </div>

      <nav className="mt-6">
        <div className="px-3">
          <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
            Main
          </h3>
          <div className="space-y-1">
            {navigation.map((item) => (
              <NavLink
                key={item.name}
                to={item.href}
                className={({ isActive }) =>
                  cn(
                    'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                    isActive
                      ? 'bg-primary text-primary-foreground'
                      : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                  )
                }
              >
                <item.icon className="mr-3 h-5 w-5" />
                {item.name}
              </NavLink>
            ))}
          </div>
        </div>

        {user?.role && roleNavigation[user.role as keyof typeof roleNavigation] && (
          <div className="mt-6 px-3">
            <h3 className="text-xs font-semibold text-gray-400 uppercase tracking-wider mb-3">
              Role Specific
            </h3>
            <div className="space-y-1">
              {roleNavigation[user.role as keyof typeof roleNavigation].map((item) => (
                <NavLink
                  key={item.name}
                  to={item.href}
                  className={({ isActive }) =>
                    cn(
                      'group flex items-center px-3 py-2 text-sm font-medium rounded-md transition-colors',
                      isActive
                        ? 'bg-primary text-primary-foreground'
                        : 'text-gray-300 hover:bg-gray-700 hover:text-white'
                    )
                  }
                >
                  <item.icon className="mr-3 h-5 w-5" />
                  {item.name}
                </NavLink>
              ))}
            </div>
          </div>
        )}
      </nav>
    </div>
  )
}
EOF

# Step 4: Fix all page files - remove unused imports
print_status "Step 4: Fixing all page files..."

# CategoriesPage.tsx
cat > src/pages/CategoriesPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../components'
import { Plus, Eye, Edit } from 'lucide-react'

export const CategoriesPage = () => {
  const { data: categories, isLoading, error } = useQuery({
    queryKey: ['categories'],
    queryFn: () => api.getCategories('1').then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading categories</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Categories</h1>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add Category
        </Button>
      </div>

      <div className="grid gap-6">
        {categories?.map((category: any) => (
          <Card key={category.id}>
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle>{category.name}</CardTitle>
                  <CardDescription>{category.description}</CardDescription>
                </div>
                <Badge variant="secondary">{category.status}</Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="flex gap-2">
                <Button variant="outline" size="sm">
                  <Eye className="h-4 w-4 mr-2" />
                  View
                </Button>
                <Button variant="outline" size="sm">
                  <Edit className="h-4 w-4 mr-2" />
                  Edit
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
EOF

# ContestsPage.tsx
cat > src/pages/ContestsPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../components'
import { Plus } from 'lucide-react'

export const ContestsPage = () => {
  const { eventId } = useParams()

  const { data: contests, isLoading } = useQuery({
    queryKey: ['contests', eventId],
    queryFn: async () => {
      const response = await api.get(`/events/${eventId}/contests`)
      return response.data
    },
    enabled: !!eventId,
  })

  if (isLoading) return <LoadingSpinner size="large" />

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Contests</h1>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add Contest
        </Button>
      </div>

      <div className="grid gap-6">
        {contests?.map((contest: any) => (
          <Card key={contest.id}>
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle>{contest.name}</CardTitle>
                  <CardDescription>{contest.description}</CardDescription>
                </div>
                <Badge variant="secondary">{contest.status}</Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <div className="flex items-center text-sm text-muted-foreground">
                  <span>Start: {formatDate(contest.start_date)}</span>
                </div>
                <div className="flex items-center text-sm text-muted-foreground">
                  <span>End: {formatDate(contest.end_date)}</span>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
EOF

# DashboardPage.tsx
cat > src/pages/DashboardPage.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, LoadingSpinner } from '../components'
import { useAuthStore } from '../auth'
import { Plus, Calendar, Trophy, Users, BarChart3, Eye } from 'lucide-react'

export const DashboardPage = () => {
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

# EventsPage.tsx
cat > src/pages/EventsPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Input, Badge, LoadingSpinner } from '../components'
import { Plus, Search, Calendar, Eye, Edit, RotateCcw, Archive } from 'lucide-react'

export const EventsPage = () => {
  const [searchTerm, setSearchTerm] = useState('')

  const { data: events, isLoading, error } = useQuery({
    queryKey: ['events'],
    queryFn: () => api.getEvents().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading events</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Events</h1>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add Event
        </Button>
      </div>

      <div className="relative">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search events..."
          value={searchTerm}
          onChange={(e: any) => setSearchTerm(e.target.value)}
          className="pl-10"
        />
      </div>

      <div className="grid gap-6">
        {events?.map((event: any) => (
          <Card key={event.id}>
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle>{event.name}</CardTitle>
                  <CardDescription>{event.description}</CardDescription>
                </div>
                <Badge variant="secondary">{event.status}</Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <div className="flex items-center text-sm text-muted-foreground">
                  <Calendar className="h-4 w-4 mr-2" />
                  {formatDate(event.start_date)} - {formatDate(event.end_date)}
                </div>
                <div className="flex gap-2">
                  <Button variant="outline" size="sm">
                    <Eye className="h-4 w-4" />
                  </Button>
                  <Button variant="outline" size="sm">
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button variant="outline" size="sm">
                    <RotateCcw className="h-4 w-4" />
                  </Button>
                  <Button variant="outline" size="sm">
                    <Archive className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {events?.length === 0 && (
        <Card>
          <CardContent className="text-center py-12">
            <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium mb-2">No events found</h3>
            <p className="text-muted-foreground mb-4">Get started by creating your first event.</p>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Create Event
            </Button>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
EOF

# ProfilePage.tsx
cat > src/pages/ProfilePage.tsx << 'EOF'
import { useState } from 'react'
import { useAuthStore } from '../auth'
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

# ResultsPage.tsx
cat > src/pages/ResultsPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../components'
import { Trophy, Download } from 'lucide-react'

export const ResultsPage = () => {
  const { data: results, isLoading, error } = useQuery({
    queryKey: ['results'],
    queryFn: () => api.getEvents().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading results</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Results</h1>
      </div>

      <div className="grid gap-6">
        {results?.map((result: any) => (
          <Card key={result.id}>
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle>{result.name}</CardTitle>
                  <CardDescription>{result.description}</CardDescription>
                </div>
                <Badge variant="secondary">{result.status}</Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="flex gap-2">
                <Button variant="outline" size="sm">
                  <Trophy className="h-4 w-4 mr-2" />
                  View Results
                </Button>
                <Button variant="outline" size="sm">
                  <Download className="h-4 w-4 mr-2" />
                  Export
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
EOF

# ScoringPage.tsx
cat > src/pages/ScoringPage.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../components'
import { Gavel, CheckCircle } from 'lucide-react'

export const ScoringPage = () => {
  const { data: assignments, isPending: assignmentsLoading } = useQuery({
    queryKey: ['judge-assignments'],
    queryFn: () => api.getJudgeAssignments().then(res => res.data),
  })

  if (assignmentsLoading) return <LoadingSpinner size="large" />

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Scoring</h1>
      </div>

      <div className="grid gap-6">
        {assignments?.map((assignment: any) => (
          <Card key={assignment.id}>
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle>{assignment.name}</CardTitle>
                  <CardDescription>{assignment.description}</CardDescription>
                </div>
                <Badge variant="secondary">{assignment.status}</Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="flex gap-2">
                <Button variant="outline" size="sm">
                  <Gavel className="h-4 w-4 mr-2" />
                  Score
                </Button>
                <Button variant="outline" size="sm">
                  <CheckCircle className="h-4 w-4 mr-2" />
                  View Details
                </Button>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
EOF

# SettingsPage.tsx
cat > src/pages/SettingsPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Input, LoadingSpinner, Badge } from '../components'
import { Settings, Mail, Shield, Database, Save, RefreshCw } from 'lucide-react'

export const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState('general')

  const tabs = [
    { id: 'general', name: 'General', icon: Settings },
    { id: 'email', name: 'Email', icon: Mail },
    { id: 'security', name: 'Security', icon: Shield },
    { id: 'database', name: 'Database', icon: Database },
  ]

  const { isLoading } = useQuery({
    queryKey: ['settings'],
    queryFn: () => api.getSettings().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold">Settings</h1>

      <div className="grid gap-6 md:grid-cols-4">
        {/* Settings Navigation */}
        <Card>
          <CardHeader>
            <CardTitle>Settings</CardTitle>
          </CardHeader>
          <CardContent className="space-y-2">
            {tabs.map((tab) => (
              <Button
                key={tab.id}
                variant={activeTab === tab.id ? "default" : "ghost"}
                className="w-full justify-start"
                onClick={() => setActiveTab(tab.id)}
              >
                <tab.icon className="h-4 w-4 mr-2" />
                {tab.name}
              </Button>
            ))}
          </CardContent>
        </Card>

        {/* Settings Content */}
        <div className="md:col-span-3">
          {activeTab === 'general' && (
            <Card>
              <CardHeader>
                <CardTitle>General Settings</CardTitle>
                <CardDescription>Manage general application settings</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="text-sm font-medium">Application Name</label>
                  <Input defaultValue="Event Manager" />
                </div>
                <div>
                  <label className="text-sm font-medium">Default Timezone</label>
                  <Input defaultValue="UTC" />
                </div>
                <Button>
                  <Save className="h-4 w-4 mr-2" />
                  Save Changes
                </Button>
              </CardContent>
            </Card>
          )}

          {activeTab === 'email' && (
            <Card>
              <CardHeader>
                <CardTitle>Email Settings</CardTitle>
                <CardDescription>Configure email notifications</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="text-sm font-medium">SMTP Server</label>
                  <Input defaultValue="smtp.example.com" />
                </div>
                <div>
                  <label className="text-sm font-medium">Port</label>
                  <Input defaultValue="587" />
                </div>
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium">Email Notifications</span>
                  <Badge variant="default">Connected</Badge>
                </div>
                <Button>
                  <Save className="h-4 w-4 mr-2" />
                  Save Changes
                </Button>
              </CardContent>
            </Card>
          )}

          {activeTab === 'security' && (
            <Card>
              <CardHeader>
                <CardTitle>Security Settings</CardTitle>
                <CardDescription>Manage security and authentication</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="text-sm font-medium">Session Timeout (minutes)</label>
                  <Input defaultValue="30" />
                </div>
                <div>
                  <label className="text-sm font-medium">Password Requirements</label>
                  <Input defaultValue="8 characters minimum" />
                </div>
                <Button>
                  <Save className="h-4 w-4 mr-2" />
                  Save Changes
                </Button>
              </CardContent>
            </Card>
          )}

          {activeTab === 'database' && (
            <Card>
              <CardHeader>
                <CardTitle>Database Settings</CardTitle>
                <CardDescription>Manage database configuration</CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="flex items-center justify-between">
                  <span className="text-sm font-medium">Database Status</span>
                  <Badge variant="default">Connected</Badge>
                </div>
                <div className="flex gap-2">
                  <Button variant="outline">
                    <RefreshCw className="h-4 w-4 mr-2" />
                    Test Connection
                  </Button>
                  <Button variant="outline">
                    <Database className="h-4 w-4 mr-2" />
                    Backup Database
                  </Button>
                  <Button variant="outline">
                    <RefreshCw className="h-4 w-4 mr-2" />
                    Reset Database
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}
EOF

# UsersPage.tsx
cat > src/pages/UsersPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, Button, Input, Badge, LoadingSpinner } from '../components'
import { Plus, Search, Users, Mail, Edit, Trash2 } from 'lucide-react'

export const UsersPage = () => {
  const [searchTerm, setSearchTerm] = useState('')

  const { data: users, isLoading, error } = useQuery({
    queryKey: ['users'],
    queryFn: () => api.getUsers().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading users</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Users</h1>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add User
        </Button>
      </div>

      <div className="relative">
        <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
        <Input
          placeholder="Search users..."
          value={searchTerm}
          onChange={(e: any) => setSearchTerm(e.target.value)}
          className="pl-10"
        />
      </div>

      <div className="grid gap-6">
        {users?.map((user: any) => (
          <Card key={user.id}>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                  <div className="h-12 w-12 rounded-full bg-primary flex items-center justify-center">
                    <Users className="h-5 w-5 text-primary-foreground" />
                  </div>
                  <div>
                    <h3 className="font-medium">{user.first_name} {user.last_name}</h3>
                    <div className="flex items-center text-sm text-muted-foreground">
                      <Mail className="h-3 w-3 mr-1" />
                      {user.email}
                    </div>
                    <div className="text-sm text-muted-foreground">
                      Joined {formatDate(user.created_at)}
                    </div>
                  </div>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant="secondary">{user.role}</Badge>
                  <Button variant="outline" size="sm">
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button variant="outline" size="sm">
                    <Trash2 className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {users?.length === 0 && (
        <Card>
          <CardContent className="text-center py-12">
            <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium mb-2">No users found</h3>
            <p className="text-muted-foreground mb-4">Get started by adding your first user.</p>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Add User
            </Button>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
EOF

print_success "All page files cleaned up"

# Step 5: Fix all role dashboard files
print_status "Step 5: Fixing all role dashboard files..."

# AuditorDashboard.tsx
cat > src/pages/roles/AuditorDashboard.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../../components'
import { CheckCircle, BarChart3, AlertCircle, Users, Eye } from 'lucide-react'

export const AuditorDashboard = () => {
  const { data: dashboard, isLoading, error } = useQuery({
    queryKey: ['auditor-dashboard'],
    queryFn: () => api.getAuditorDashboard().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading dashboard</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Auditor Dashboard</h1>
        <Button>
          <CheckCircle className="h-4 w-4 mr-2" />
          Generate Report
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Audits Completed</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.auditsCompleted || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Issues Found</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.issuesFound || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Critical Issues</CardTitle>
            <AlertCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.criticalIssues || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Users Audited</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.usersAudited || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Recent Audits */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Audits</CardTitle>
          <CardDescription>Latest audit activities</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {dashboard?.recentAudits?.map((audit: any) => (
              <div key={audit.id} className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">{audit.name}</h4>
                  <p className="text-sm text-muted-foreground">{audit.description}</p>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant="secondary">{audit.status}</Badge>
                  <Button variant="outline" size="sm">
                    <Eye className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Quick Actions */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="text-center">
          <CardContent className="pt-6">
            <Eye className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Start Audit</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <CheckCircle className="h-6 w-6 mb-2" />
            <h3 className="font-medium">View Reports</h3>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}
EOF

# BoardDashboard.tsx
cat > src/pages/roles/BoardDashboard.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardHeader, CardTitle, Button, LoadingSpinner } from '../../components'
import { Crown, Users, Trophy, BarChart3, FileText, Download } from 'lucide-react'

export const BoardDashboard = () => {
  const { data: dashboard, isLoading, error } = useQuery({
    queryKey: ['board-dashboard'],
    queryFn: () => api.getBoardDashboard().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading dashboard</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Board Dashboard</h1>
        <Button>
          <Crown className="h-4 w-4 mr-2" />
          Generate Report
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.totalUsers || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Events</CardTitle>
            <Trophy className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.activeEvents || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Revenue</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">${dashboard?.revenue || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Reports Generated</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.reportsGenerated || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="text-center">
          <CardContent className="pt-6">
            <Download className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Export Data</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Download className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Generate Report</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Download className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Download Analytics</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Download className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Export Users</h3>
          </CardContent>
        </Card>
      </div>

      {/* Empty State */}
      <Card>
        <CardContent className="text-center py-12">
          <Crown className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-medium mb-2">Board Dashboard</h3>
          <p className="text-muted-foreground mb-4">Board activities will appear here.</p>
        </CardContent>
      </Card>
    </div>
  )
}
EOF

# EmceeDashboard.tsx
cat > src/pages/roles/EmceeDashboard.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardHeader, CardTitle, Button, LoadingSpinner } from '../../components'
import { Mic, Calendar, FileText, Users, Clock, Play } from 'lucide-react'

export const EmceeDashboard = () => {
  const { data: dashboard, isLoading, error } = useQuery({
    queryKey: ['emcee-dashboard'],
    queryFn: () => api.getEmceeDashboard().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading dashboard</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Emcee Dashboard</h1>
        <Button>
          <Mic className="h-4 w-4 mr-2" />
          Start Event
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Events Hosted</CardTitle>
            <Calendar className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.eventsHosted || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Scripts Available</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.scriptsAvailable || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Participants</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.participants || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Time</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.totalTime || '0h'}</div>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="text-center">
          <CardContent className="pt-6">
            <Mic className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Start Event</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <FileText className="h-6 w-6 mb-2" />
            <h3 className="font-medium">View Scripts</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Users className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Manage Participants</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Play className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Event Controls</h3>
          </CardContent>
        </Card>
      </div>

      {/* Empty State */}
      <Card>
        <CardContent className="text-center py-12">
          <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-medium mb-2">No upcoming events</h3>
          <p className="text-muted-foreground mb-4">Scheduled events will appear here.</p>
        </CardContent>
      </Card>
    </div>
  )
}
EOF

# JudgeDashboard.tsx
cat > src/pages/roles/JudgeDashboard.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardHeader, CardTitle, Button, LoadingSpinner } from '../../components'
import { Gavel, CheckCircle, Clock, BarChart3, Users, Trophy } from 'lucide-react'

export const JudgeDashboard = () => {
  const { data: dashboard, isLoading, error } = useQuery({
    queryKey: ['judge-dashboard'],
    queryFn: () => api.getJudgeDashboard().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading dashboard</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Judge Dashboard</h1>
        <Button>
          <Gavel className="h-4 w-4 mr-2" />
          Start Judging
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Assignments</CardTitle>
            <Gavel className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.assignments || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Completed</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.completed || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.pending || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Average Score</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.averageScore || 'N/A'}</div>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="text-center">
          <CardContent className="pt-6">
            <Gavel className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Start Judging</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <CheckCircle className="h-6 w-6 mb-2" />
            <h3 className="font-medium">View Completed</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Users className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Manage Assignments</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Trophy className="h-6 w-6 mb-2" />
            <h3 className="font-medium">View Results</h3>
          </CardContent>
        </Card>
      </div>

      {/* Empty State */}
      <Card>
        <CardContent className="text-center py-12">
          <Gavel className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-medium mb-2">No current assignments</h3>
          <p className="text-muted-foreground mb-4">Your judging assignments will appear here.</p>
        </CardContent>
      </Card>
    </div>
  )
}
EOF

# TallyMasterDashboard.tsx
cat > src/pages/roles/TallyMasterDashboard.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardHeader, CardTitle, Button, LoadingSpinner } from '../../components'
import { BarChart3, CheckCircle, Clock, Users } from 'lucide-react'

export const TallyMasterDashboard = () => {
  const { data: dashboard, isLoading, error } = useQuery({
    queryKey: ['tally-master-dashboard'],
    queryFn: () => api.getTallyMasterDashboard().then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading dashboard</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Tally Master Dashboard</h1>
        <Button>
          <BarChart3 className="h-4 w-4 mr-2" />
          Generate Report
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Tallies Completed</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.talliesCompleted || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Verified Results</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.verifiedResults || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Review</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.pendingReview || 0}</div>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Participants</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.totalParticipants || 0}</div>
          </CardContent>
        </Card>
      </div>

      {/* Quick Actions */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="text-center">
          <CardContent className="pt-6">
            <BarChart3 className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Start Tally</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <CheckCircle className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Verify Results</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Users className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Manage Participants</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Clock className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Review Pending</h3>
          </CardContent>
        </Card>
      </div>

      {/* Empty State */}
      <Card>
        <CardContent className="text-center py-12">
          <BarChart3 className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-medium mb-2">No tallies available</h3>
          <p className="text-muted-foreground mb-4">Tally activities will appear here.</p>
        </CardContent>
      </Card>
    </div>
  )
}
EOF

print_success "All role dashboard files cleaned up"

# Step 6: Run type check
print_status "Step 6: Running TypeScript type check..."
if npm run type-check; then
    print_success "🎉 TypeScript type check passed!"
else
    print_warning "TypeScript type check still has issues, but continuing..."
fi

# Step 7: Try building
print_status "Step 7: Attempting to build..."
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
