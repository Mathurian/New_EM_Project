import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import { Plus, Search, Calendar, Eye, Edit, Archive, RotateCcw } from 'lucide-react'
import { formatDate } from '../../lib/utils'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { toast } from 'react-hot-toast'

export const EventsPage = () => {
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const queryClient = useQueryClient()

  const { data: events, isLoading } = useQuery(
    ['events', searchTerm, statusFilter],
    async () => {
      const params = new URLSearchParams()
      if (searchTerm) params.append('search', searchTerm)
      if (statusFilter !== 'all') params.append('status', statusFilter)
      
      const response = await api.get(`/events?${params.toString()}`)
      return response.data
    }
  )

  const archiveMutation = useMutation(
    async (eventId: string) => {
      await api.post(`/events/${eventId}/archive`)
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries('events')
        toast.success('Event archived successfully')
      },
      onError: () => {
        toast.error('Failed to archive event')
      }
    }
  )

  const reactivateMutation = useMutation(
    async (eventId: string) => {
      await api.post(`/events/${eventId}/reactivate`)
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries('events')
        toast.success('Event reactivated successfully')
      },
      onError: () => {
        toast.error('Failed to reactivate event')
      }
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
          <h1 className="text-3xl font-bold text-foreground">Events</h1>
          <p className="text-muted-foreground mt-2">
            Manage your events and contests
          </p>
        </div>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          New Event
        </Button>
      </div>

      {/* Filters */}
      <Card>
        <CardContent className="pt-6">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <div className="relative">
                <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-muted-foreground" />
                <Input
                  placeholder="Search events..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="pl-10"
                />
              </div>
            </div>
            <div className="sm:w-48">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="w-full h-10 px-3 py-2 border border-input bg-background rounded-md text-sm"
              >
                <option value="all">All Status</option>
                <option value="draft">Draft</option>
                <option value="active">Active</option>
                <option value="completed">Completed</option>
                <option value="archived">Archived</option>
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Events Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {events?.data?.map((event: any) => (
          <Card key={event.id} className="hover:shadow-lg transition-shadow">
            <CardHeader>
              <div className="flex items-start justify-between">
                <div className="space-y-1">
                  <CardTitle className="text-lg">{event.name}</CardTitle>
                  <CardDescription className="text-sm">
                    {event.description || 'No description'}
                  </CardDescription>
                </div>
                {getStatusBadge(event.status)}
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-3">
                <div className="flex items-center text-sm text-muted-foreground">
                  <Calendar className="h-4 w-4 mr-2" />
                  {formatDate(event.start_date)} - {formatDate(event.end_date)}
                </div>
                
                <div className="flex items-center justify-between pt-4">
                  <div className="text-sm text-muted-foreground">
                    {event.contests_count || 0} contests
                  </div>
                  <div className="flex space-x-2">
                    <Button variant="ghost" size="sm">
                      <Eye className="h-4 w-4" />
                    </Button>
                    <Button variant="ghost" size="sm">
                      <Edit className="h-4 w-4" />
                    </Button>
                    {event.status === 'archived' ? (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => reactivateMutation.mutate(event.id)}
                        loading={reactivateMutation.isLoading}
                      >
                        <RotateCcw className="h-4 w-4" />
                      </Button>
                    ) : (
                      <Button
                        variant="ghost"
                        size="sm"
                        onClick={() => archiveMutation.mutate(event.id)}
                        loading={archiveMutation.isLoading}
                      >
                        <Archive className="h-4 w-4" />
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            </CardContent>
          </Card>
        )) || (
          <div className="col-span-full text-center py-12">
            <Calendar className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium text-foreground mb-2">No events found</h3>
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

      {/* Pagination */}
      {events?.pagination && events.pagination.pages > 1 && (
        <div className="flex items-center justify-center space-x-2">
          <Button variant="outline" size="sm" disabled>
            Previous
          </Button>
          <span className="text-sm text-muted-foreground">
            Page {events.pagination.page} of {events.pagination.pages}
          </span>
          <Button variant="outline" size="sm" disabled>
            Next
          </Button>
        </div>
      )}
    </div>
  )
}