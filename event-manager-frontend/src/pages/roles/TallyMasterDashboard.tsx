import { useQuery } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { BarChart3, CheckCircle, Clock, Users, AlertCircle } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'

export const TallyMasterDashboard = () => {
  const { data: dashboard, isLoading } = useQuery(
    'tally-master-dashboard',
    async () => {
      const response = await api.get('/tally-master')
      return response.data
    }
  )

  const { data: stats } = useQuery(
    'tally-master-stats',
    async () => {
      const response = await api.get('/tally-master/stats')
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
          <h1 className="text-3xl font-bold text-foreground">Tally Master Dashboard</h1>
          <p className="text-muted-foreground mt-2">
            Review scores and manage certifications
          </p>
        </div>
        <Button>
          <CheckCircle className="h-4 w-4 mr-2" />
          Review Scores
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Subcategories</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.total_subcategories || 0}</div>
            <p className="text-xs text-muted-foreground">
              Across all events
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Certified</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{stats?.certified_subcategories || 0}</div>
            <p className="text-xs text-muted-foreground">
              {stats?.certification_percentage ? `${Math.round(stats.certification_percentage)}%` : '0%'} complete
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Total Scores</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
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
            <Clock className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {stats?.signing_percentage ? `${Math.round(stats.signing_percentage)}%` : '0%'}
            </div>
            <p className="text-xs text-muted-foreground">
              Scores signed
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Subcategories Status */}
      <Card>
        <CardHeader>
          <CardTitle>Subcategory Status</CardTitle>
          <CardDescription>
            Review and certify subcategory scores
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {dashboard?.subcategories?.map((subcategory: any) => (
              <div key={subcategory.id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="space-y-1">
                  <h3 className="font-medium">{subcategory.subcategory_name}</h3>
                  <p className="text-sm text-muted-foreground">
                    {subcategory.category_name} - {subcategory.contest_name} - {subcategory.event_name}
                  </p>
                  <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                    <span>{subcategory.total_scores || 0} scores</span>
                    <span>{subcategory.expected_scores || 0} expected</span>
                  </div>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge 
                    variant={
                      subcategory.is_certified ? 'default' : 
                      subcategory.is_complete ? 'secondary' : 'destructive'
                    }
                  >
                    {subcategory.is_certified ? 'Certified' : 
                     subcategory.is_complete ? 'Complete' : 'Incomplete'}
                  </Badge>
                  <Button variant="outline" size="sm">
                    Review
                  </Button>
                </div>
              </div>
            )) || (
              <div className="text-center py-8">
                <BarChart3 className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-medium text-foreground mb-2">No subcategories found</h3>
                <p className="text-muted-foreground">
                  No subcategories are available for review.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}