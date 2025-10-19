import { useQuery } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { Eye, CheckCircle, AlertCircle, BarChart3, Users } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'

export const AuditorDashboard = () => {
  const { data: dashboard, isLoading } = useQuery(
    'auditor-dashboard',
    async () => {
      const response = await api.get('/auditor')
      return response.data
    }
  )

  const { data: scores } = useQuery(
    'auditor-scores',
    async () => {
      const response = await api.get('/auditor/scores')
      return response.data
    }
  )

  const { data: tallyMasterStatus } = useQuery(
    'auditor-tally-master-status',
    async () => {
      const response = await api.get('/auditor/tally-master-status')
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
          <h1 className="text-3xl font-bold text-foreground">Auditor Dashboard</h1>
          <p className="text-muted-foreground mt-2">
            Review scores and perform final certifications
          </p>
        </div>
        <Button>
          <CheckCircle className="h-4 w-4 mr-2" />
          Final Certification
        </Button>
      </div>

      {/* Stats Cards */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Subcategories</CardTitle>
            <BarChart3 className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">{scores?.total_groups || 0}</div>
            <p className="text-xs text-muted-foreground">
              Available for audit
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Complete</CardTitle>
            <CheckCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {scores?.scores?.filter((s: any) => s.is_complete).length || 0}
            </div>
            <p className="text-xs text-muted-foreground">
              Fully scored
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Pending</CardTitle>
            <AlertCircle className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {scores?.scores?.filter((s: any) => !s.is_complete).length || 0}
            </div>
            <p className="text-xs text-muted-foreground">
              Awaiting completion
            </p>
          </CardContent>
        </Card>

        <Card>
          <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
            <CardTitle className="text-sm font-medium">Unsigned</CardTitle>
            <Users className="h-4 w-4 text-muted-foreground" />
          </CardHeader>
          <CardContent>
            <div className="text-2xl font-bold">
              {scores?.scores?.filter((s: any) => s.has_unsigned_scores).length || 0}
            </div>
            <p className="text-xs text-muted-foreground">
              Have unsigned scores
            </p>
          </CardContent>
        </Card>
      </div>

      {/* Score Review */}
      <Card>
        <CardHeader>
          <CardTitle>Score Review</CardTitle>
          <CardDescription>
            Review scores for accuracy and completeness
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {scores?.scores?.map((scoreGroup: any) => (
              <div key={`${scoreGroup.subcategory_id}_${scoreGroup.contestant_id}`} className="border rounded-lg p-4">
                <div className="flex items-center justify-between mb-4">
                  <div>
                    <h3 className="font-medium">{scoreGroup.contestant_name}</h3>
                    <p className="text-sm text-muted-foreground">
                      {scoreGroup.subcategory_name} - {scoreGroup.category_name}
                    </p>
                    <p className="text-sm text-muted-foreground">
                      Contestant #{scoreGroup.contestant_number}
                    </p>
                  </div>
                  <div className="flex items-center space-x-2">
                    <Badge 
                      variant={
                        scoreGroup.is_complete && !scoreGroup.has_unsigned_scores ? 'default' : 
                        scoreGroup.is_complete ? 'secondary' : 'destructive'
                      }
                    >
                      {scoreGroup.is_complete && !scoreGroup.has_unsigned_scores ? 'Complete' : 
                       scoreGroup.is_complete ? 'Partial' : 'Incomplete'}
                    </Badge>
                    <Button variant="outline" size="sm">
                      <Eye className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
                
                <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-sm">
                  <div>
                    <span className="text-muted-foreground">Total Score:</span>
                    <span className="ml-2 font-medium">{scoreGroup.total_score?.toFixed(2) || '0.00'}</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">Max Possible:</span>
                    <span className="ml-2 font-medium">{scoreGroup.max_possible_score?.toFixed(2) || '0.00'}</span>
                  </div>
                  <div>
                    <span className="text-muted-foreground">Percentage:</span>
                    <span className="ml-2 font-medium">{scoreGroup.percentage?.toFixed(1) || '0.0'}%</span>
                  </div>
                </div>
              </div>
            )) || (
              <div className="text-center py-8">
                <Eye className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-medium text-foreground mb-2">No scores to review</h3>
                <p className="text-muted-foreground">
                  No scores are available for audit at this time.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>

      {/* Tally Master Status */}
      <Card>
        <CardHeader>
          <CardTitle>Tally Master Status</CardTitle>
          <CardDescription>
            Monitor tally master certification progress
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="space-y-4">
            {tallyMasterStatus?.certifications?.map((cert: any) => (
              <div key={cert.subcategory_id} className="flex items-center justify-between p-4 border rounded-lg">
                <div className="space-y-1">
                  <h3 className="font-medium">{cert.subcategory_name}</h3>
                  <p className="text-sm text-muted-foreground">
                    {cert.category_name} - {cert.contest_name} - {cert.event_name}
                  </p>
                  <div className="flex items-center space-x-4 text-sm text-muted-foreground">
                    <span>{cert.certified_count}/{cert.total_count} judges certified</span>
                    <span>{cert.completion_percentage?.toFixed(1) || '0.0'}% complete</span>
                  </div>
                </div>
                <div className="flex items-center space-x-2">
                  <Badge 
                    variant={
                      cert.is_fully_certified ? 'default' : 
                      cert.certified_count > 0 ? 'secondary' : 'destructive'
                    }
                  >
                    {cert.is_fully_certified ? 'Fully Certified' : 
                     cert.certified_count > 0 ? 'Partially Certified' : 'Not Certified'}
                  </Badge>
                </div>
              </div>
            )) || (
              <div className="text-center py-8">
                <CheckCircle className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
                <h3 className="text-lg font-medium text-foreground mb-2">No certifications found</h3>
                <p className="text-muted-foreground">
                  No tally master certifications are available for review.
                </p>
              </div>
            )}
          </div>
        </CardContent>
      </Card>
    </div>
  )
}