#!/bin/bash
# Final Micro-Fix Script - Fix Last 6 TypeScript Errors
# This script fixes the final 6 remaining TypeScript errors

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

echo "🔧 Final Micro-Fix - Fix Last 6 TypeScript Errors"
echo "================================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix LoginPage.tsx - login function expects 1 argument, not 2
print_status "Step 1: Fixing LoginPage.tsx login function call..."
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

# Step 2: Fix CategoriesPage.tsx - remove unused useState
print_status "Step 2: Fixing CategoriesPage.tsx..."
cat > src/pages/CategoriesPage.tsx << 'EOF'
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

# Step 3: Fix ContestsPage.tsx - remove unused useState and add useParams import
print_status "Step 3: Fixing ContestsPage.tsx..."
cat > src/pages/ContestsPage.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { useParams } from 'react-router-dom'
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

# Step 4: Fix DashboardPage.tsx - remove unused useAuthStore
print_status "Step 4: Fixing DashboardPage.tsx..."
cat > src/pages/DashboardPage.tsx << 'EOF'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, LoadingSpinner } from '../components'
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

# Step 5: Fix ResultsPage.tsx - remove unused useState
print_status "Step 5: Fixing ResultsPage.tsx..."
cat > src/pages/ResultsPage.tsx << 'EOF'
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

print_success "All files fixed"

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
