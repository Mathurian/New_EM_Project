import { useQuery } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { Mic, Users, FileText, Play, Download } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'

export const EmceeDashboard = () => {
  const { data: dashboard, isLoading } = useQuery(
    'emcee-dashboard',
    async () => {
      const response = await api.get('/emcee')
      return response.data
    }
  )

  const { data: scripts } = useQuery(
    'emcee-scripts',
    async () => {
      const response = await api.get('/emcee/scripts')
      return response.data
    }
  )

  const { data: contestants } = useQuery(
    'emcee-contestants',
    async () => {
      const response = await api.get('/emcee/contestants')
      return response.data
    }
  )

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Emcee Dashboard</h1>
          <p className="text-muted-foreground mt-2">
            Manage scripts and access contestant information
          </p>
        </div>
        <Button>
          <Mic className="h-4 w-4 mr-2" />
          New Script
        </Button>
      </div>

      {/* Quick Stats */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Scripts</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{scripts?.total_scripts || 0}</div>
            <p className="text-xs text-muted-foreground">
              Available scripts
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Contestants</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{contestants?.total_contestants || 0}</div>
            <p className="text-xs text-muted-foreground">
              Across all events
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Events</CardTitle>
            <Mic className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{dashboard?.events?.length || 0}</div>
            <p className="text-xs text-muted-foreground">
              Currently running
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Scripts */}
      <Card>
        <CardHeader>
          <CardTitle>Available Scripts</CardTitle>
          <CardDescription>
            Access and manage your emcee scripts
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {scripts?.scripts?.map((script: any) => (
              <div key={script.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="space-y-1">
                  <h3 className="font-medium">{script.title}</h3>
                  <p className="text-sm text-muted-foreground">
                    {script.event_name} - {script.contest_name} - {script.subcategory_name}
                  </p>
                  <div className="text-sm text-muted-foreground">
                    {script.content?.length || 0} characters
                  </div>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant={script.is_active ? 'default' : 'secondary'}>
                    {script.is_active ? 'Active' : 'Inactive'}
                  </Badge>
                  <Button variant="outline" size="sm">
                    <Play className="h-4 w-4" />
                  </Button>
                  <Button variant="outline" size="sm">
                    <Download className="h-4 w-4" />
                  </Button>
                </div>
              </div>
            )) || (
              <div className="text-center py-8">
                <FileText className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-medium text-foreground mb-2">No scripts found</h3>
                <p className="text-muted-foreground">
                  No scripts are available for this event.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Contestants */}
      <Card>
        <CardHeader>
          <CardTitle>Contestant Information</CardTitle>
          <CardDescription>
            Access contestant details and bios
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {contestants?.contestants?.slice(0, 10).map((contestant: any) => (
              <div key={contestant.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="space-y-1">
                  <h3 className="font-medium">{contestant.name}</h3>
                  <p className="text-sm text-muted-foreground">
                    Contestant #{contestant.contestant_number}
                  </p>
                  <p className="text-sm text-muted-foreground">
                    {contestant.subcategory_name} - {contestant.category_name}
                  </p>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant="outline">
                    {contestant.event_name}
                  </Badge>
                  <Button variant="outline" size="sm">
                    View Bio
                  </Button>
                </div>
              </div>
            )) || (
              <div className="text-center py-8">
                <Users className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-medium text-foreground mb-2">No contestants found</h3>
                <p className="text-muted-foreground">
                  No contestants are available for this event.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}