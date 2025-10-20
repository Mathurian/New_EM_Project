#!/bin/bash
# Complete Frontend Fix - Updates ALL Files
# This script updates every single file to use the new single-file approach

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

echo "🔧 Complete Frontend Fix - Update ALL Files"
echo "==========================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Update ALL remaining page files
print_status "Step 1: Updating ALL remaining page files..."

# Update EventsPage.tsx
cat > src/pages/EventsPage.tsx << 'EOF'
import React, { useState } from 'react'
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
          onChange={(e) => setSearchTerm(e.target.value)}
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

# Update ProfilePage.tsx
cat > src/pages/ProfilePage.tsx << 'EOF'
import React, { useState } from 'react'
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

# Update ResultsPage.tsx
cat > src/pages/ResultsPage.tsx << 'EOF'
import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../components'

export const ResultsPage = () => {
  const [searchTerm, setSearchTerm] = useState('')

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
                  View Results
                </Button>
                <Button variant="outline" size="sm">
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

# Update ScoringPage.tsx
cat > src/pages/ScoringPage.tsx << 'EOF'
import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Input, Badge, LoadingSpinner } from '../components'
import { useAuthStore } from '../auth'

export const ScoringPage = () => {
  const { user } = useAuthStore()
  const [searchTerm, setSearchTerm] = useState('')

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

      <div className="relative">
        <Input
          placeholder="Search assignments..."
          value={searchTerm}
          onChange={(e) => setSearchTerm(e.target.value)}
        />
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
                  Score
                </Button>
                <Button variant="outline" size="sm">
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

# Update SettingsPage.tsx
cat > src/pages/SettingsPage.tsx << 'EOF'
import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Input, LoadingSpinner, Badge } from '../components'
import { Settings, Mail, Shield, Database, Save, RefreshCw } from 'lucide-react'

export const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState('general')
  const [settings, setSettings] = useState({})

  const tabs = [
    { id: 'general', name: 'General', icon: Settings },
    { id: 'email', name: 'Email', icon: Mail },
    { id: 'security', name: 'Security', icon: Shield },
    { id: 'database', name: 'Database', icon: Database },
  ]

  const { data: settingsData, isLoading } = useQuery({
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

# Update UsersPage.tsx
cat > src/pages/UsersPage.tsx << 'EOF'
import React, { useState } from 'react'
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
          onChange={(e) => setSearchTerm(e.target.value)}
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

# Update all role dashboard files
print_status "Step 2: Updating all role dashboard files..."

# Update AuditorDashboard.tsx
cat > src/pages/roles/AuditorDashboard.tsx << 'EOF'
import React from 'react'
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

      {/* Audit Reports */}
      <Card>
        <CardHeader>
          <CardTitle>Audit Reports</CardTitle>
          <CardDescription>Available audit reports</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {dashboard?.reports?.map((report: any) => (
              <div key={report.id} className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">{report.name}</h4>
                  <p className="text-sm text-muted-foreground">{report.description}</p>
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

# Update BoardDashboard.tsx
cat > src/pages/roles/BoardDashboard.tsx << 'EOF'
import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../../components'
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

      {/* Recent Activities */}
      <Card>
        <CardHeader>
          <CardTitle>Recent Activities</CardTitle>
          <CardDescription>Latest board activities</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {dashboard?.recentActivities?.map((activity: any) => (
              <div key={activity.id} className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">{activity.name}</h4>
                  <p className="text-sm text-muted-foreground">{activity.description}</p>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant="secondary">{activity.status}</Badge>
                  <Button variant="outline" size="sm">
                    <Download className="h-4 w-4" />
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

      {/* Analytics Overview */}
      <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
        <Card className="text-center">
          <CardContent className="pt-6">
            <BarChart3 className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Analytics</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <FileText className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Reports</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Users className="h-6 w-6 mb-2" />
            <h3 className="font-medium">User Management</h3>
          </CardContent>
        </Card>
        <Card className="text-center">
          <CardContent className="pt-6">
            <Trophy className="h-6 w-6 mb-2" />
            <h3 className="font-medium">Event Overview</h3>
          </CardContent>
        </Card>
      </div>

      {/* Board Actions */}
      <Card>
        <CardHeader>
          <CardTitle>Board Actions</CardTitle>
          <CardDescription>Administrative actions available to board members</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="flex gap-2">
            <Button variant="outline">
              <Crown className="h-4 w-4 mr-2" />
              Manage Users
            </Button>
            <Button variant="outline">
              <BarChart3 className="h-4 w-4 mr-2" />
              View Analytics
            </Button>
            <Button variant="outline">
              <FileText className="h-4 w-4 mr-2" />
              Generate Reports
            </Button>
          </div>
        </CardContent>
      </Card>

      {/* Empty State */}
      {dashboard?.recentActivities?.length === 0 && (
        <Card>
          <CardContent className="text-center py-12">
            <Crown className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium mb-2">No recent activities</h3>
            <p className="text-muted-foreground mb-4">Board activities will appear here.</p>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
EOF

# Update EmceeDashboard.tsx
cat > src/pages/roles/EmceeDashboard.tsx << 'EOF'
import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../../components'
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

      {/* Current Event */}
      <Card>
        <CardHeader>
          <CardTitle>Current Event</CardTitle>
          <CardDescription>Currently active event details</CardDescription>
        </CardHeader>
        <CardContent>
          {dashboard?.currentEvent ? (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">{dashboard.currentEvent.name}</h4>
                  <p className="text-sm text-muted-foreground">{dashboard.currentEvent.description}</p>
                </div>
                <Badge variant="secondary">{dashboard.currentEvent.status}</Badge>
              </div>
              <div className="flex gap-2">
                <Button variant="outline" size="sm">
                  <Play className="h-4 w-4" />
                </Button>
                <Button variant="outline" size="sm">
                  View Script
                </Button>
              </div>
            </div>
          ) : (
            <div className="text-center py-8">
              <p className="text-muted-foreground">No active event</p>
            </div>
          )}
        </CardContent>
      </Card>

      {/* Upcoming Events */}
      <Card>
        <CardHeader>
          <CardTitle>Upcoming Events</CardTitle>
          <CardDescription>Scheduled events to host</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {dashboard?.upcomingEvents?.map((event: any) => (
              <div key={event.id} className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">{event.name}</h4>
                  <p className="text-sm text-muted-foreground">{event.description}</p>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant="secondary">{event.status}</Badge>
                  <Button variant="outline" size="sm">
                    <Play className="h-4 w-4" />
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
      {dashboard?.upcomingEvents?.length === 0 && (
        <Card>
          <CardContent className="text-center py-12">
            <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium mb-2">No upcoming events</h3>
            <p className="text-muted-foreground mb-4">Scheduled events will appear here.</p>
          </CardContent>
        </Card>
      )}

      {/* Empty State for Users */}
      {dashboard?.upcomingEvents?.length === 0 && (
        <Card>
          <CardContent className="text-center py-12">
            <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium mb-2">No participants</h3>
            <p className="text-muted-foreground mb-4">Event participants will appear here.</p>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
EOF

# Update JudgeDashboard.tsx
cat > src/pages/roles/JudgeDashboard.tsx << 'EOF'
import React from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../../components'
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

      {/* Current Assignments */}
      <Card>
        <CardHeader>
          <CardTitle>Current Assignments</CardTitle>
          <CardDescription>Your current judging assignments</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {dashboard?.currentAssignments?.map((assignment: any) => (
              <div key={assignment.id} className="flex items-center justify-between">
                <div>
                  <h4 className="font-medium">{assignment.name}</h4>
                  <p className="text-sm text-muted-foreground">{assignment.description}</p>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant="secondary">{assignment.status}</Badge>
                  <Button variant="outline" size="sm">
                    <Gavel className="h-4 w-4" />
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
      {dashboard?.currentAssignments?.length === 0 && (
        <Card>
          <CardContent className="text-center py-12">
            <Gavel className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium mb-2">No current assignments</h3>
            <p className="text-muted-foreground mb-4">Your judging assignments will appear here.</p>
          </CardContent>
        </Card>
      )}
    </div>
  )
}
EOF

print_success "All role dashboard files updated"

# Step 3: Fix the components.tsx file to use absolute import
print_status "Step 3: Fixing components.tsx import..."
cat > src/components.tsx << 'EOF'
import * as React from "react"
import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import { cn } from "./utils"

// Button component
const buttonVariants = cva(
  "inline-flex items-center justify-center whitespace-nowrap rounded-md text-sm font-medium ring-offset-background transition-colors focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:pointer-events-none disabled:opacity-50",
  {
    variants: {
      variant: {
        default: "bg-primary text-primary-foreground hover:bg-primary/90",
        destructive: "bg-destructive text-destructive-foreground hover:bg-destructive/90",
        outline: "border border-input bg-background hover:bg-accent hover:text-accent-foreground",
        secondary: "bg-secondary text-secondary-foreground hover:bg-secondary/80",
        ghost: "hover:bg-accent hover:text-accent-foreground",
        link: "text-primary underline-offset-4 hover:underline",
      },
      size: {
        default: "h-10 px-4 py-2",
        sm: "h-9 rounded-md px-3",
        lg: "h-11 rounded-md px-8",
        icon: "h-10 w-10",
      },
    },
    defaultVariants: {
      variant: "default",
      size: "default",
    },
  }
)

export interface ButtonProps
  extends React.ButtonHTMLAttributes<HTMLButtonElement>,
    VariantProps<typeof buttonVariants> {
  asChild?: boolean
}

export const Button = React.forwardRef<HTMLButtonElement, ButtonProps>(
  ({ className, variant, size, asChild = false, ...props }, ref) => {
    const Comp = asChild ? Slot : "button"
    return (
      <Comp
        className={cn(buttonVariants({ variant, size, className }))}
        ref={ref}
        {...props}
      />
    )
  }
)
Button.displayName = "Button"

// Card component
export const Card = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn(
      "rounded-lg border bg-card text-card-foreground shadow-sm",
      className
    )}
    {...props}
  />
))
Card.displayName = "Card"

export const CardHeader = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn("flex flex-col space-y-1.5 p-6", className)}
    {...props}
  />
))
CardHeader.displayName = "CardHeader"

export const CardTitle = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLHeadingElement>
>(({ className, ...props }, ref) => (
  <h3
    ref={ref}
    className={cn(
      "text-2xl font-semibold leading-none tracking-tight",
      className
    )}
    {...props}
  />
))
CardTitle.displayName = "CardTitle"

export const CardDescription = React.forwardRef<
  HTMLParagraphElement,
  React.HTMLAttributes<HTMLParagraphElement>
>(({ className, ...props }, ref) => (
  <p
    ref={ref}
    className={cn("text-sm text-muted-foreground", className)}
    {...props}
  />
))
CardDescription.displayName = "CardDescription"

export const CardContent = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div ref={ref} className={cn("p-6 pt-0", className)} {...props} />
))
CardContent.displayName = "CardContent"

export const CardFooter = React.forwardRef<
  HTMLDivElement,
  React.HTMLAttributes<HTMLDivElement>
>(({ className, ...props }, ref) => (
  <div
    ref={ref}
    className={cn("flex items-center p-6 pt-0", className)}
    {...props}
  />
))
CardFooter.displayName = "CardFooter"

// Input component
export interface InputProps
  extends React.InputHTMLAttributes<HTMLInputElement> {}

export const Input = React.forwardRef<HTMLInputElement, InputProps>(
  ({ className, type, ...props }, ref) => {
    return (
      <input
        type={type}
        className={cn(
          "flex h-10 w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background file:border-0 file:bg-transparent file:text-sm file:font-medium placeholder:text-muted-foreground focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50",
          className
        )}
        ref={ref}
        {...props}
      />
    )
  }
)
Input.displayName = "Input"

// Badge component
const badgeVariants = cva(
  "inline-flex items-center rounded-full border px-2.5 py-0.5 text-xs font-semibold transition-colors focus:outline-none focus:ring-2 focus:ring-ring focus:ring-offset-2",
  {
    variants: {
      variant: {
        default: "border-transparent bg-primary text-primary-foreground hover:bg-primary/80",
        secondary: "border-transparent bg-secondary text-secondary-foreground hover:bg-secondary/80",
        destructive: "border-transparent bg-destructive text-destructive-foreground hover:bg-destructive/80",
        outline: "text-foreground",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

export interface BadgeProps
  extends React.HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof badgeVariants> {}

export const Badge = React.forwardRef<HTMLDivElement, BadgeProps>(
  ({ className, variant, ...props }, ref) => {
    return (
      <div className={cn(badgeVariants({ variant }), className)} ref={ref} {...props} />
    )
  }
)
Badge.displayName = "Badge"

// LoadingSpinner component
interface LoadingSpinnerProps {
  size?: 'small' | 'medium' | 'large';
  className?: string;
}

export const LoadingSpinner: React.FC<LoadingSpinnerProps> = ({ size = 'medium', className }) => {
  const spinnerSize = {
    small: 'h-4 w-4',
    medium: 'h-8 w-8',
    large: 'h-12 w-12',
  };

  return (
    <div className={cn("flex items-center justify-center", className)}>
      <div
        className={cn(
          "animate-spin rounded-full border-4 border-t-4 border-t-primary border-gray-200",
          spinnerSize[size]
        )}
      ></div>
    </div>
  );
};
EOF

print_success "Components file fixed"

# Step 4: Run type check
print_status "Step 4: Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed!"
else
    print_warning "TypeScript type check still has issues, but continuing..."
fi

# Step 5: Try building
print_status "Step 5: Attempting to build..."
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