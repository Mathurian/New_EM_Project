import { useState } from 'react'
import { useQuery } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Badge } from '../../components/ui/Badge'
import { Trophy, Download, Filter, BarChart3 } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { formatDate } from '../../lib/utils'

export const ResultsPage = () => {
  const [selectedEvent, setSelectedEvent] = useState('')
  const [selectedContest, setSelectedContest] = useState('')

  const { data: events } = useQuery(
    'events-for-results',
    async () => {
      const response = await api.get('/events?status=active')
      return response.data
    }
  )

  const { data: contests } = useQuery(
    ['contests-for-results', selectedEvent],
    async () => {
      if (!selectedEvent) return { data: [] }
      const response = await api.get(`/events/${selectedEvent}/contests`)
      return response.data
    },
    {
      enabled: !!selectedEvent
    }
  )

  const { data: results, isLoading: resultsLoading } = useQuery(
    ['results', selectedEvent, selectedContest],
    async () => {
      if (selectedContest) {
        const response = await api.get(`/results/contest/${selectedContest}`)
        return response.data
      } else if (selectedEvent) {
        const response = await api.get(`/results/event/${selectedEvent}`)
        return response.data
      }
      return null
    },
    {
      enabled: !!(selectedEvent || selectedContest)
    }
  )

  if (resultsLoading) {
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
          <h1 className="text-3xl font-bold text-foreground">Results</h1>
          <p className="text-muted-foreground mt-2">
            View and analyze competition results
          </p>
        </div>
        <div className="flex space-x-2">
          <Button variant="outline">
            <Download className="h-4 w-4 mr-2" />
            Export PDF
          </Button>
          <Button variant="outline">
            <BarChart3 className="h-4 w-4 mr-2" />
            Analytics
          </Button>
        </div>
      </div>

      {/* Filters */}
      <Card>
        <CardHeader>
          <CardTitle>Filter Results</CardTitle>
          <CardDescription>
            Select an event and contest to view results
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="text-sm font-medium mb-2 block">Event</label>
              <select
                value={selectedEvent}
                onChange={(e) => {
                  setSelectedEvent(e.target.value)
                  setSelectedContest('')
                }}
                className="w-full h-10 px-3 py-2 border border-input bg-background rounded-md text-sm"
              >
                <option value="">Select an event</option>
                {events?.data?.map((event: any) => (
                  <option key={event.id} value={event.id}>
                    {event.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="text-sm font-medium mb-2 block">Contest</label>
              <select
                value={selectedContest}
                onChange={(e) => setSelectedContest(e.target.value)}
                className="w-full h-10 px-3 py-2 border border-input bg-background rounded-md text-sm"
                disabled={!selectedEvent}
              >
                <option value="">Select a contest</option>
                {contests?.data?.map((contest: any) => (
                  <option key={contest.id} value={contest.id}>
                    {contest.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
        </CardContent>
      </Card>

      {/* Results */}
      {results && (
        <div className="space-y-6">
          {results.categories?.map((category: any) => (
            <Card key={category.id}>
              <CardHeader>
                <CardTitle>{category.name}</CardTitle>
                <CardDescription>
                  {category.description || 'No description'}
                </CardDescription>
              </CardHeader>
              <CardContent>
                <div className="space-y-4">
                  {category.subcategories?.map((subcategory: any) => (
                    <div key={subcategory.id} className="border rounded-lg p-4">
                      <div className="flex items-center justify-between mb-4">
                        <h3 className="font-medium">{subcategory.name}</h3>
                        <Badge variant="outline">
                          {subcategory.contestants?.length || 0} contestants
                        </Badge>
                      </div>
                      
                      {/* Leaderboard */}
                      <div className="space-y-2">
                        {subcategory.contestants
                          ?.sort((a: any, b: any) => b.total_score - a.total_score)
                          .map((contestant: any, index: number) => (
                            <div
                              key={contestant.id}
                              className="flex items-center justify-between p-3 bg-muted rounded-md"
                            >
                              <div className="flex items-center space-x-3">
                                <div className="flex items-center justify-center w-8 h-8 rounded-full bg-primary text-primary-foreground text-sm font-medium">
                                  {index + 1}
                                </div>
                                <div>
                                  <div className="font-medium">{contestant.name}</div>
                                  <div className="text-sm text-muted-foreground">
                                    Contestant #{contestant.contestant_number}
                                  </div>
                                </div>
                              </div>
                              <div className="text-right">
                                <div className="font-medium">
                                  {contestant.total_score?.toFixed(2) || '0.00'}
                                </div>
                                <div className="text-sm text-muted-foreground">
                                  {contestant.percentage?.toFixed(1) || '0.0'}%
                                </div>
                              </div>
                            </div>
                          ))}
                      </div>
                    </div>
                  ))}
                </div>
              </CardContent>
            </Card>
          ))}
        </div>
      )}

      {/* No results */}
      {!selectedEvent && !selectedContest && (
        <Card>
          <CardContent className="text-center py-12">
            <Trophy className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium text-foreground mb-2">No results selected</h3>
            <p className="text-muted-foreground">
              Select an event and contest to view results
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  )
}