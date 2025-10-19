import { useQuery } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { Crown, Users, Trophy, BarChart3, FileText, Download } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'

export const BoardDashboard = () => {
  const { data: dashboard, isLoading } = useQuery(
    'board-dashboard',
    async () => {
      const response = await api.get('/board')
      return response.data
    }
  )

  const { data: certificationStatus } = useQuery(
    'board-certification-status',
    async () => {
      const response = await api.get('/board/certification-status')
      return response.data
    }
  )

  const { data: stats } = useQuery(
    'board-stats',
    async () => {
      const response = await api.get('/board/stats')
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
          <h1 className="text-3xl font-bold text-foreground">Board Dashboard</h1>
          <p className="text-muted-foreground mt-2">
            System overview and administrative controls
          </p>
        </div>
        <Button>
          <Crown className="h-4 w-4 mr-2" />
          System Report
        </Button>
      </div>

      {/* System Stats */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Users</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_users || 0}</div>
            <p className="text-xs text-muted-foreground">
              Registered users
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Active Events</CardTitle>
            <Trophy className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_events || 0}</div>
            <p className="text-xs text-muted-foreground">
              Currently running
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Scores</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_scores || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.signed_scores || 0} signed
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Signing Rate</CardTitle>
            <FileText className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {stats?.signing_percentage ? `${Math.round(stats.signing_percentage)}%` : '0%'}
            </div>
            <p className="text-xs text-muted-foreground">
              Overall completion
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Certification Status */}
      <Card>
        <CardHeader>
          <CardTitle>Certification Status</CardTitle>
          <CardDescription>
            Monitor certification progress across all subcategories
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {certificationStatus?.subcategories?.map((subcategory: any) => (
              <div key={subcategory.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="space-y-1">
                  <h3 className="font-medium">{subcategory.subcategory_name}</h3>
                  <p className="text-sm text-muted-foreground">
                    {subcategory.category_name} - {subcategory.contest_name} - {subcategory.event_name}
                  </p>
                  <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                    <span>{subcategory.certified_judges}/{subcategory.total_judges} judges certified</span>
                    <span>{subcategory.signed_scores}/{subcategory.total_scores} scores signed</span>
                  </div>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge 
                    variant={
                      subcategory.status === 'final_certified' ? 'default' :
                      subcategory.status === 'ready_for_final' ? 'secondary' :
                      subcategory.status === 'partial' ? 'outline' : 'destructive'
                    }
                  >
                    {subcategory.status === 'final_certified' ? 'Final Certified' :
                     subcategory.status === 'ready_for_final' ? 'Ready for Final' :
                     subcategory.status === 'partial' ? 'Partial' : 'Pending'}
                  </Badge>
                </div>
              </div>
            )) || (
              <div className="text-center py-8">
                <Crown className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-medium text-foreground mb-2">No certifications found</h3>
                <p className="text-muted-foreground">
                  No subcategories are available for certification review.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Quick Actions */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
        <Card>
          <CardHeader>
            <CardTitle>System Reports</CardTitle>
            <CardDescription>
              Generate and download system reports
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            <Button variant="outline" className="w-full justify-start">
              <Download className="h-4 w-4 mr-2" />
              User Report
            </Button>
            <Button variant="outline" className="w-full justify-start">
              <Download className="h-4 w-4 mr-2" />
              Event Report
            </Button>
            <Button variant="outline" className="w-full justify-start">
              <Download className="h-4 w-4 mr-2" />
              Score Report
            </Button>
            <Button variant="outline" className="w-full justify-start">
              <Download className="h-4 w-4 mr-2" />
              System Logs
            </Button>
          </CardContent>
        </Card>

        <Card>
          <CardHeader>
            <CardTitle>Administrative Actions</CardTitle>
            <CardDescription>
              System maintenance and management
            </CardDescription>
          </CardHeader>
          <CardContent className="space-y-2">
            <Button variant="outline" className="w-full justify-start">
              <BarChart3 className="h-4 w-4 mr-2" />
              Database Maintenance
            </Button>
            <Button variant="outline" className="w-full justify-start">
              <FileText className="h-4 w-4 mr-2" />
              Backup System
            </Button>
            <Button variant="outline" className="w-full justify-start">
              <Users className="h-4 w-4 mr-2" />
              User Management
            </Button>
            <Button variant="outline" className="w-full justify-start">
              <Trophy className="h-4 w-4 mr-2" />
              Event Management
            </Button>
          </CardContent>
        </Card>
      </div>
    </div>
  )
}