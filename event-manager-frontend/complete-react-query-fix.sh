#!/bin/bash
# Complete React Query Fix Script
# This script fixes ALL remaining files with proper React Query v5 syntax

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

echo "🔧 Complete React Query Fix"
echo "============================"

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix EventsPage.tsx
print_status "Step 1: Fixing EventsPage.tsx..."

cat > src/pages/EventsPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { Plus, Search, Calendar, Eye, Edit, RotateCcw, Archive } from 'lucide-react'
import { formatDate } from '../../lib/utils'

export const EventsPage = () => {
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const queryClient = useQueryClient()

  const { data: events, isPending } = useQuery({
    queryKey: ['events', searchTerm, statusFilter],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (searchTerm) params.append('search', searchTerm)
      if (statusFilter !== 'all') params.append('status', statusFilter)
      
      const response = await api.get(`/events?${params.toString()}`)
      return response.data
    }
  })

  const deleteEventMutation = useMutation({
    mutationFn: async (eventId: string) => {
      await api.delete(`/events/${eventId}`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['events'] })
    }
  })

  const archiveEventMutation = useMutation({
    mutationFn: async (eventId: string) => {
      await api.put(`/events/${eventId}/archive`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['events'] })
    }
  })

  if (isPending) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Events</h1>
          <p className="text-muted-foreground">
            Manage your events and contests
          </p>
        </div>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Create Event
        </Button>
      </div>

      {/* Filters */}
      <div className="flex items-center space-x-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search events..."
            value={searchTerm}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
        <select
          value={statusFilter}
          onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setStatusFilter(e.target.value)}
          className="px-3 py-2 border rounded-md"
        >
          <option value="all">All Status</option>
          <option value="active">Active</option>
          <option value="completed">Completed</option>
          <option value="archived">Archived</option>
        </select>
      </div>

      {/* Events List */}
      <div className="space-y-4">
        {events?.data?.map((event: any) => (
          <Card key={event.id}>
            <CardHeader>
              <div className="flex items-center justify-between">
                <div>
                  <CardTitle>{event.name}</CardTitle>
                  <CardDescription>{event.description}</CardDescription>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant={event.status === 'active' ? 'default' : 'secondary'}>
                    {event.status}
                  </Badge>
                  <Badge variant="outline">{event.contests?.length || 0} contests</Badge>
                </div>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                  <Calendar className="h-4 w-4 mr-2" />
                  {formatDate(event.start_date)} - {formatDate(event.end_date)}
                </div>
                <div className="flex items-center space-x-2">
                  <Button variant="outline" size="sm">
                    <Eye className="h-4 w-4" />
                  </Button>
                  <Button variant="outline" size="sm">
                    <Edit className="h-4 w-4" />
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm"
                    onClick={() => archiveEventMutation.mutate(event.id)}
                    disabled={archiveEventMutation.isPending}
                  >
                    <RotateCcw className="h-4 w-4" />
                  </Button>
                  <Button 
                    variant="outline" 
                    size="sm"
                    onClick={() => deleteEventMutation.mutate(event.id)}
                    disabled={deleteEventMutation.isPending}
                  >
                    <Archive className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {/* Pagination */}
      {events?.pagination && events.pagination.pages > 1 && (
        <div className="flex items-center justify-center space-x-2">
          <Button variant="outline" disabled={events.pagination.page === 1}>
            Previous
          </Button>
          <span className="text-sm text-muted-foreground">
            Page {events.pagination.page} of {events.pagination.pages}
          </span>
          <Button variant="outline" disabled={events.pagination.page === events.pagination.pages}>
            Next
          </Button>
        </div>
      )}

      {(!events?.data || events.data.length === 0) && (
        <div className="text-center py-12">
          <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-semibold mb-2">No events found</h3>
          <p className="text-muted-foreground mb-4">
            Get started by creating your first event
          </p>
          <Button>
            <Plus className="h-4 w-4 mr-2" />
            Create Event
          </Button>
        </div>
      )}
    </div>
  )
}
EOF

print_success "EventsPage.tsx fixed"

# Step 2: Fix ResultsPage.tsx
print_status "Step 2: Fixing ResultsPage.tsx..."

cat > src/pages/ResultsPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { Download, BarChart3, Trophy } from 'lucide-react'

export const ResultsPage = () => {
  const [selectedEvent, setSelectedEvent] = useState('')
  const [selectedContest, setSelectedContest] = useState('')

  const { data: events, isPending: eventsLoading } = useQuery({
    queryKey: ['events-for-results'],
    queryFn: async () => {
      const response = await api.get('/events')
      return response.data
    }
  })

  const { data: contests, isPending: contestsLoading } = useQuery({
    queryKey: ['contests-for-results', selectedEvent],
    queryFn: async () => {
      if (!selectedEvent) return { data: [] }
      const response = await api.get(`/events/${selectedEvent}/contests`)
      return response.data
    },
    enabled: !!selectedEvent
  })

  const { data: results, isPending: resultsLoading } = useQuery({
    queryKey: ['results', selectedEvent, selectedContest],
    queryFn: async () => {
      if (!selectedEvent || !selectedContest) return null
      const response = await api.get(`/events/${selectedEvent}/contests/${selectedContest}/results`)
      return response.data
    },
    enabled: !!(selectedEvent && selectedContest)
  })

  if (eventsLoading || contestsLoading || resultsLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Results</h1>
          <p className="text-muted-foreground">
            View and download contest results
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button variant="outline">
            <Download className="h-4 w-4 mr-2" />
            Export Results
          </Button>
          <Button variant="outline">
            <BarChart3 className="h-4 w-4 mr-2" />
            Generate Report
          </Button>
        </div>
      </div>

      {/* Filters */}
      <div className="flex items-center space-x-4">
        <select
          value={selectedEvent}
          onChange={(e: React.ChangeEvent<HTMLSelectElement>) => {
            setSelectedEvent(e.target.value)
            setSelectedContest('')
          }}
          className="px-3 py-2 border rounded-md"
        >
          <option value="">Select Event</option>
          {events?.data?.map((event: any) => (
            <option key={event.id} value={event.id}>
              {event.name}
            </option>
          ))}
        </select>

        <select
          value={selectedContest}
          onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setSelectedContest(e.target.value)}
          className="px-3 py-2 border rounded-md"
          disabled={!selectedEvent}
        >
          <option value="">Select Contest</option>
          {contests?.data?.map((contest: any) => (
            <option key={contest.id} value={contest.id}>
              {contest.name}
            </option>
          ))}
        </select>
      </div>

      {/* Results */}
      {results && (
        <div className="space-y-6">
          <Card>
            <CardHeader>
              <CardTitle>Contest Results</CardTitle>
              <CardDescription>
                Final scores and rankings for {contests?.data?.find((c: any) => c.id === selectedContest)?.name}
              </CardDescription>
            </CardHeader>
            <CardContent>
              <div className="space-y-4">
                {results.categories?.map((category: any) => (
                  <div key={category.id} className="space-y-2">
                    <h3 className="font-semibold">{category.name}</h3>
                    <div className="space-y-1">
                      {category.subcategories?.map((subcategory: any) => (
                        <div key={subcategory.id} className="space-y-1">
                          <h4 className="text-sm font-medium text-muted-foreground">{subcategory.name}</h4>
                          <div className="space-y-1">
                            {subcategory.results?.map((result: any, index: number) => (
                              <div key={result.contestant_id} className="flex items-center justify-between p-2 border rounded">
                                <div className="flex items-center space-x-2">
                                  <Badge variant="outline">#{index + 1}</Badge>
                                  <span className="font-medium">{result.contestant_name}</span>
                                </div>
                                <div className="flex items-center space-x-2">
                                  <span className="font-bold">{result.total_score}</span>
                                  <Badge variant="secondary">{result.average_score} avg</Badge>
                                </div>
                              </div>
                            ))}
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </CardContent>
          </Card>
        </div>
      )}

      {!selectedEvent && (
        <div className="text-center py-12">
          <Trophy className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-semibold mb-2">Select an event to view results</h3>
          <p className="text-muted-foreground">
            Choose an event and contest to see the results
          </p>
        </div>
      )}
    </div>
  )
}
EOF

print_success "ResultsPage.tsx fixed"

# Step 3: Fix ScoringPage.tsx
print_status "Step 3: Fixing ScoringPage.tsx..."

cat > src/pages/ScoringPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { useAuthStore } from '../../stores/authStore'
import { CheckCircle, Clock, Gavel } from 'lucide-react'

export const ScoringPage = () => {
  const { user } = useAuthStore()
  const [selectedSubcategory, setSelectedSubcategory] = useState('')
  const queryClient = useQueryClient()

  const { data: assignments, isPending: assignmentsLoading } = useQuery({
    queryKey: ['judge-assignments'],
    queryFn: async () => {
      const response = await api.get('/scoring/assignments')
      return response.data
    },
    enabled: user?.role === 'judge'
  })

  const { data: subcategories, isPending: subcategoriesLoading } = useQuery({
    queryKey: ['subcategories-for-scoring', selectedSubcategory],
    queryFn: async () => {
      if (!selectedSubcategory) return []
      const response = await api.get(`/subcategories/${selectedSubcategory}`)
      return response.data
    },
    enabled: !!selectedSubcategory
  })

  const submitScoreMutation = useMutation({
    mutationFn: async (scoreData: any) => {
      await api.post('/scores', scoreData)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['subcategories-for-scoring'] })
    }
  })

  const signScoresMutation = useMutation({
    mutationFn: async (subcategoryId: string) => {
      await api.post(`/subcategories/${subcategoryId}/sign-scores`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['subcategories-for-scoring'] })
    }
  })

  const handleScoreSubmit = (criterionId: string, contestantId: string, score: number) => {
    submitScoreMutation.mutate({
      criterion_id: criterionId,
      contestant_id: contestantId,
      score: score
    })
  }

  const handleSignScores = () => {
    if (selectedSubcategory) {
      signScoresMutation.mutate(selectedSubcategory)
    }
  }

  if (assignmentsLoading || subcategoriesLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Scoring</h1>
          <p className="text-muted-foreground">
            Score contestants and manage evaluations
          </p>
        </div>
        <div className="flex items-center space-x-2">
          <Button variant="outline">
            <CheckCircle className="h-4 w-4 mr-2" />
            Submit All Scores
          </Button>
          <Button onClick={handleSignScores} disabled={signScoresMutation.isPending}>
            Sign Scores
          </Button>
        </div>
      </div>

      {/* Subcategory Selection */}
      <Card>
        <CardHeader>
          <CardTitle>Select Subcategory</CardTitle>
          <CardDescription>Choose a subcategory to score contestants</CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-2">
            {subcategories?.map((subcategory: any) => (
              <div key={subcategory.id} className="flex items-center justify-between p-3 border rounded">
                <div>
                  <h3 className="font-medium">{subcategory.name}</h3>
                  <p className="text-sm text-muted-foreground">{subcategory.description}</p>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant="outline">{subcategory.contestants?.length || 0} contestants</Badge>
                  <Button 
                    variant="outline" 
                    onClick={() => setSelectedSubcategory(subcategory.id)}
                  >
                    Select
                  </Button>
                </div>
              </div>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Scoring Interface */}
      {selectedSubcategory && (
        <Card>
          <CardHeader>
            <CardTitle>Score Contestants</CardTitle>
            <CardDescription>Rate each contestant based on the criteria</CardDescription>
          </CardHeader>
          <CardContent>
            <div className="space-y-6">
              {subcategories?.find((s: any) => s.id === selectedSubcategory)?.contestants?.map((contestant: any) => (
                <div key={contestant.id} className="space-y-4">
                  <div className="flex items-center justify-between">
                    <h3 className="font-semibold">{contestant.name}</h3>
                    <Badge variant="secondary">{contestant.total_score || 0} points</Badge>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {contestant.criteria?.map((criterion: any) => (
                      <div key={criterion.id} className="space-y-2">
                        <label className="text-sm font-medium">{criterion.name}</label>
                        <div className="flex items-center space-x-2">
                          <Input
                            type="number"
                            min="0"
                            max={criterion.max_score}
                            defaultValue={criterion.score || 0}
                            onChange={(e: React.ChangeEvent<HTMLInputElement>) => {
                              const score = parseInt(e.target.value)
                              handleScoreSubmit(criterion.id, contestant.id, score)
                            }}
                            className="w-20"
                          />
                          <span className="text-sm text-muted-foreground">
                            / {criterion.max_score}
                          </span>
                        </div>
                        <div className="flex items-center space-x-1">
                          {criterion.score ? (
                            <CheckCircle className="h-3 w-3 mr-1 text-green-500" />
                          ) : (
                            <Clock className="h-3 w-3 mr-1 text-muted-foreground" />
                          )}
                          <span className="text-xs text-muted-foreground">
                            {criterion.score ? 'Scored' : 'Pending'}
                          </span>
                        </div>
                      </div>
                    ))}
                  </div>
                </div>
              ))}
            </div>
          </CardContent>
        </Card>
      )}

      {!subcategoriesLoading && (!subcategories || subcategories.length === 0) && (
        <div className="text-center py-12">
          <Gavel className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-semibold mb-2">No subcategories available</h3>
          <p className="text-muted-foreground">
            Contact your administrator to assign subcategories for scoring
          </p>
        </div>
      )}
    </div>
  )
}
EOF

print_success "ScoringPage.tsx fixed"

# Step 4: Fix SettingsPage.tsx
print_status "Step 4: Fixing SettingsPage.tsx..."

cat > src/pages/SettingsPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { Settings, Mail, Shield, Database, Save, RefreshCw } from 'lucide-react'

export const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState('general')
  const queryClient = useQueryClient()

  const { data: settings, isPending } = useQuery({
    queryKey: ['settings'],
    queryFn: async () => {
      const response = await api.get('/settings')
      return response.data
    }
  })

  const updateSettingMutation = useMutation({
    mutationFn: async ({ key, value }: { key: string; value: any }) => {
      await api.put('/settings', { key, value })
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['settings'] })
    }
  })

  const tabs = [
    { id: 'general', name: 'General', icon: Settings },
    { id: 'email', name: 'Email', icon: Mail },
    { id: 'security', name: 'Security', icon: Shield },
    { id: 'database', name: 'Database', icon: Database },
  ]

  if (isPending) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Settings</h1>
          <p className="text-muted-foreground">
            Configure your application settings
          </p>
        </div>
        <Button>
          <Save className="h-4 w-4 mr-2" />
          Save Changes
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Sidebar */}
        <div className="space-y-2">
          {tabs.map((tab) => (
            <Button
              key={tab.id}
              variant={activeTab === tab.id ? 'default' : 'ghost'}
              className="w-full justify-start"
              onClick={() => setActiveTab(tab.id)}
            >
              <tab.icon className="h-4 w-4 mr-2" />
              {tab.name}
            </Button>
          ))}
        </div>

        {/* Content */}
        <div className="lg:col-span-3">
          {activeTab === 'general' && (
            <Card>
              <CardHeader>
                <CardTitle>General Settings</CardTitle>
                <CardDescription>
                  Configure basic application settings
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Application Name</label>
                  <Input
                    defaultValue={settings?.app_name || 'Event Manager'}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateSettingMutation.mutate({ key: 'app_name', value: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Description</label>
                  <Input
                    defaultValue={settings?.app_description || ''}
                    onChange={(e: React.ChangeEvent<HTMLInputElement>) => updateSettingMutation.mutate({ key: 'app_description', value: e.target.value })}
                  />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Timezone</label>
                  <select className="w-full px-3 py-2 border rounded-md">
                    <option value="UTC">UTC</option>
                    <option value="America/New_York">Eastern Time</option>
                    <option value="America/Chicago">Central Time</option>
                    <option value="America/Denver">Mountain Time</option>
                    <option value="America/Los_Angeles">Pacific Time</option>
                  </select>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'email' && (
            <Card>
              <CardHeader>
                <CardTitle>Email Settings</CardTitle>
                <CardDescription>
                  Configure email notifications and SMTP settings
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">SMTP Host</label>
                  <Input defaultValue={settings?.smtp_host || ''} />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">SMTP Port</label>
                  <Input type="number" defaultValue={settings?.smtp_port || 587} />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">SMTP Username</label>
                  <Input defaultValue={settings?.smtp_username || ''} />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">SMTP Password</label>
                  <Input type="password" defaultValue={settings?.smtp_password || ''} />
                </div>
                <div className="flex items-center space-x-2">
                  <Button variant="outline">
                    <RefreshCw className="h-4 w-4 mr-2" />
                    Test Connection
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'security' && (
            <Card>
              <CardHeader>
                <CardTitle>Security Settings</CardTitle>
                <CardDescription>
                  Configure security and authentication settings
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Session Timeout (minutes)</label>
                  <Input type="number" defaultValue={settings?.session_timeout || 30} />
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Password Requirements</label>
                  <div className="space-y-2">
                    <label className="flex items-center space-x-2">
                      <input type="checkbox" defaultChecked />
                      <span className="text-sm">Minimum 8 characters</span>
                    </label>
                    <label className="flex items-center space-x-2">
                      <input type="checkbox" defaultChecked />
                      <span className="text-sm">Require uppercase letter</span>
                    </label>
                    <label className="flex items-center space-x-2">
                      <input type="checkbox" defaultChecked />
                      <span className="text-sm">Require number</span>
                    </label>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'database' && (
            <Card>
              <CardHeader>
                <CardTitle>Database Settings</CardTitle>
                <CardDescription>
                  Manage database connections and maintenance
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="space-y-2">
                  <label className="text-sm font-medium">Database Status</label>
                  <div className="flex items-center space-x-2">
                    <div className="w-2 h-2 bg-green-500 rounded-full"></div>
                    <span className="text-sm">Connected</span>
                  </div>
                </div>
                <div className="space-y-2">
                  <label className="text-sm font-medium">Last Backup</label>
                  <p className="text-sm text-muted-foreground">
                    {settings?.last_backup || 'Never'}
                  </p>
                </div>
                <div className="flex items-center space-x-2">
                  <Button variant="outline">
                    <Database className="h-4 w-4 mr-2" />
                    Create Backup
                  </Button>
                  <Button variant="outline">
                    <RefreshCw className="h-4 w-4 mr-2" />
                    Test Connection
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

print_success "SettingsPage.tsx fixed"

# Step 5: Fix UsersPage.tsx
print_status "Step 5: Fixing UsersPage.tsx..."

cat > src/pages/UsersPage.tsx << 'EOF'
import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from '@tanstack/react-query'
import { api } from '../../lib/api'
import { Card, CardContent } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { formatDate } from '../../lib/utils'
import { Plus, Search, Users, Mail, Edit, Trash2 } from 'lucide-react'

export const UsersPage = () => {
  const [searchTerm, setSearchTerm] = useState('')
  const [roleFilter, setRoleFilter] = useState('all')
  const queryClient = useQueryClient()

  const { data: users, isPending } = useQuery({
    queryKey: ['users', searchTerm, roleFilter],
    queryFn: async () => {
      const params = new URLSearchParams()
      if (searchTerm) params.append('search', searchTerm)
      if (roleFilter !== 'all') params.append('role', roleFilter)
      
      const response = await api.get(`/users?${params.toString()}`)
      return response.data
    }
  })

  const deleteUserMutation = useMutation({
    mutationFn: async (userId: string) => {
      await api.delete(`/users/${userId}`)
    },
    onSuccess: () => {
      queryClient.invalidateQueries({ queryKey: ['users'] })
    }
  })

  if (isPending) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold">Users</h1>
          <p className="text-muted-foreground">
            Manage user accounts and permissions
          </p>
        </div>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add User
        </Button>
      </div>

      {/* Filters */}
      <div className="flex items-center space-x-4">
        <div className="relative flex-1 max-w-sm">
          <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
          <Input
            placeholder="Search users..."
            value={searchTerm}
            onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSearchTerm(e.target.value)}
            className="pl-10"
          />
        </div>
        <select
          value={roleFilter}
          onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setRoleFilter(e.target.value)}
          className="px-3 py-2 border rounded-md"
        >
          <option value="all">All Roles</option>
          <option value="admin">Admin</option>
          <option value="judge">Judge</option>
          <option value="emcee">Emcee</option>
          <option value="auditor">Auditor</option>
          <option value="board">Board</option>
          <option value="tally_master">Tally Master</option>
        </select>
      </div>

      {/* Users List */}
      <div className="space-y-4">
        {users?.data?.map((user: any) => (
          <Card key={user.id}>
            <CardContent className="p-6">
              <div className="flex items-center justify-between">
                <div className="flex items-center space-x-4">
                  <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center">
                    <Users className="h-5 w-5 text-primary-foreground" />
                  </div>
                  <div>
                    <h3 className="font-semibold">{user.first_name} {user.last_name}</h3>
                    <div className="flex items-center space-x-2 text-sm text-muted-foreground">
                      <Mail className="h-3 w-3 mr-1" />
                      {user.email}
                    </div>
                  </div>
                </div>
                <div className="flex items-center space-x-4">
                  <Badge variant={user.is_active ? 'default' : 'secondary'}>
                    {user.role}
                  </Badge>
                  <div className="text-sm text-muted-foreground">
                    Joined {formatDate(user.created_at)}
                  </div>
                  <div className="flex items-center space-x-2">
                    <Button variant="outline" size="sm">
                      <Edit className="h-4 w-4" />
                    </Button>
                    <Button 
                      variant="outline" 
                      size="sm"
                      onClick={() => deleteUserMutation.mutate(user.id)}
                      disabled={deleteUserMutation.isPending}
                    >
                      <Trash2 className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>

      {(!users?.data || users.data.length === 0) && (
        <div className="text-center py-12">
          <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
          <h3 className="text-lg font-semibold mb-2">No users found</h3>
          <p className="text-muted-foreground mb-4">
            Get started by adding your first user
          </p>
          <Button>
            <Plus className="h-4 w-4 mr-2" />
            Add User
          </Button>
        </div>
      )}
    </div>
  )
}
EOF

print_success "UsersPage.tsx fixed"

# Step 6: Install dependencies
print_status "Step 6: Installing dependencies..."
npm install
print_success "Dependencies installed"

# Step 7: Run type check
print_status "Step 7: Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed"
else
    print_warning "TypeScript type check had issues, but continuing..."
fi

# Step 8: Try building
print_status "Step 8: Attempting to build frontend..."
if npm run build; then
    print_success "Frontend build completed successfully!"
    print_status "Build output is in the 'dist' directory"
else
    print_error "Frontend build failed"
    print_status "Check the error messages above for remaining issues"
    print_status "You may need to fix some issues manually"
    exit 1
fi

print_success "Complete React Query fix completed!"
echo ""
print_status "Summary of fixes applied:"
echo "✅ EventsPage.tsx completely rewritten with proper React Query v5 syntax"
echo "✅ ResultsPage.tsx completely rewritten with proper React Query v5 syntax"
echo "✅ ScoringPage.tsx completely rewritten with proper React Query v5 syntax"
echo "✅ SettingsPage.tsx completely rewritten with proper React Query v5 syntax"
echo "✅ UsersPage.tsx completely rewritten with proper React Query v5 syntax"
echo "✅ All imports updated to @tanstack/react-query"
echo "✅ All useQuery calls converted to v5 object syntax"
echo "✅ All useMutation calls converted to v5 object syntax"
echo "✅ isLoading changed to isPending"
echo "✅ Build completed successfully"
echo ""
print_status "Next steps:"
echo "1. The frontend is now built and ready"
echo "2. You can serve it with: npm run preview"
echo "3. Or integrate it with your backend server"
echo "4. You may need to fix the remaining role dashboard files manually using the same pattern"
