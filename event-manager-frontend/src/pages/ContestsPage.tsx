import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../components'
import { Plus, Tag, Eye, Edit } from 'lucide-react'

export const ContestsPage = () => {
  const { eventId } = useParams()
  const [searchTerm, setSearchTerm] = useState('')

  const { data: contests, isLoading } = useQuery(
    ['contests', eventId, searchTerm],
    async () => {
      const params = new URLSearchParams()
      if (searchTerm) params.append('search', searchTerm)
      
      const response = await api.get(`/events/${eventId}/contests?${params.toString()}`)
      return response.data
    },
    {
      enabled: !!eventId
    }
  )

  const getStatusBadge = (status: string) => {
    const variants = {
      draft: 'secondary',
      active: 'default',
      completed: 'outline',
      archived: 'destructive'
    } as const

    return (
      <Badge variant={variants[status as keyof typeof variants] || 'secondary'}>
        {status}
      </Badge>
    )
  }

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
          <h1 className="text-3xl font-bold text-foreground">Contests</h1>
          <p className="text-muted-foreground mt-2">
            Manage contests for this event
          </p>
        </div>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          New Contest
        </Button>
      </div>

      {/* Search */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <input
                type="text"
                placeholder="Search contests..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full h-10 px-3 py-2 border border-input bg-background rounded-md text-sm"
              />
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Contests Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {contests?.data?.map((contest: any) => (
          <Card key={contest.id} className="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="space-y-1">
                  <CardTitle className="text-lg">{contest.name}</CardTitle>
                  <CardDescription className="text-sm">
                    {contest.description || 'No description'}
                  </CardDescription>
                </div>
                {getStatusBadge(contest.status)}
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex items-center text-sm text-muted-foreground">
                  <Calendar className="h-4 w-4 mr-2" />
                  {formatDate(contest.start_date)} - {formatDate(contest.end_date)}
                </div>
                
                <div className="flex items-center justify-between pt-4">
                  <div className="text-sm text-muted-foreground">
                    {contest.categories_count || 0} categories
                  </div>
                  <div className="flex space-x-2">
                    <Button variant="ghost" size="sm">
                      <Eye className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm">
                      <Edit className="h-4 w-4" />
                    </Button>
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        )) || (
          <div className="col-span-full text-center py-12">
            <Trophy className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium text-foreground mb-2">No contests found</h3>
            <p className="text-muted-foreground mb-4">
              Get started by creating your first contest
            </p>
            <Button>
              <Plus className="h-4 w-4 mr-2" />
              Create Contest
            </Button>
          </div>
        )}
      </div>
    </div>
  )
}