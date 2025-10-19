import { useQuery } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { Gavel, Clock, CheckCircle, Users, Trophy } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { formatDate } from '../../lib/utils'

export const JudgeDashboard = () => {
  const { data: assignments, isLoading } = useQuery(
    'judge-assignments',
    async () => {
      const response = await api.get('/scoring/assignments')
      return response.data
    }
  )

  const { data: stats } = useQuery(
    'judge-stats',
    async () => {
      const response = await api.get('/scoring/stats')
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
          <h1 className="text-3xl font-bold text-foreground">Judge Dashboard</h1>
          <p className="text-muted-foreground mt-2">
            Manage your scoring assignments and track progress
          </p>
        </div>
        <Button>
          <Gavel className="h-4 w-4 mr-2" />
          Start Scoring
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Assigned Subcategories</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{assignments?.length || 0}</div>
            <p className="text-xs text-muted-foreground">
              Active assignments
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Scores Submitted</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.submitted_scores || 0}</div>
            <p className="text-xs text-muted-foreground">
              Total scores entered
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending Scores</CardTitle>
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.pending_scores || 0}</div>
            <p className="text-xs text-muted-foreground">
              Awaiting completion
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Completion Rate</CardTitle>
            <Trophy className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {stats?.completion_rate ? `${Math.round(stats.completion_rate)}%` : '0%'}
            </div>
            <p className="text-xs text-muted-foreground">
              Overall progress
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Assignments */}
      <Card>
        <CardHeader>
          <CardTitle>Your Assignments</CardTitle>
          <CardDescription>
            Subcategories you're assigned to score
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {assignments?.map((assignment: any) => (
              <div key={assignment.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="space-y-1">
                  <h3 className="font-medium">{assignment.subcategory_name}</h3>
                  <p className="text-sm text-muted-foreground">
                    {assignment.category_name} - {assignment.contest_name}
                  </p>
                  <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                    <span>{assignment.contestants_count || 0} contestants</span>
                    <span>{assignment.criteria_count || 0} criteria</span>
                  </div>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge variant={assignment.is_complete ? 'default' : 'outline'}>
                    {assignment.is_complete ? 'Complete' : 'In Progress'}
                  </Badge>
                  <Button variant="outline" size="sm">
                    Score
                  </Button>
                </div>
              </div>
            )) || (
              <div className="text-center py-8">
                <Gavel className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-medium text-foreground mb-2">No assignments</h3>
                <p className="text-muted-foreground">
                  You haven't been assigned to any subcategories yet.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}