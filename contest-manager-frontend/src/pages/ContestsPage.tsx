import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { Link, useParams } from 'react-router-dom'
import { api } from '../lib/api'
import { 
  Plus, 
  Search, 
  Calendar, 
  Trophy, 
  Archive, 
  Trash2, 
  Eye,
  ArrowLeft,
  Target
} from 'lucide-react'
import { formatDate } from '../lib/utils'
import { LoadingSpinner } from '../components/ui/LoadingSpinner'
import toast from 'react-hot-toast'

interface Contest {
  id: string
  name: string
  description?: string
  start_date: string
  end_date: string
  status: 'draft' | 'active' | 'completed' | 'archived'
  created_at: string
  event_id: string
  categories?: Array<{
    id: string
    name: string
    subcategories: Array<{
      id: string
      name: string
    }>
  }>
}

export function ContestsPage() {
  const { eventId } = useParams<{ eventId: string }>()
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState('all')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const queryClient = useQueryClient()

  const { data: contestsData, isLoading } = useQuery(
    ['contests', eventId, searchTerm, statusFilter],
    async () => {
      const params = new URLSearchParams()
      if (searchTerm) params.append('search', searchTerm)
      if (statusFilter !== 'all') params.append('status', statusFilter)
      
      const response = await api.get(`/contests/event/${eventId}?${params.toString()}`)
      return response.data
    },
    { enabled: !!eventId }
  )

  const { data: event } = useQuery(
    ['event', eventId],
    async () => {
      const response = await api.get(`/events/${eventId}`)
      return response.data
    },
    { enabled: !!eventId }
  )

  const deleteContestMutation = useMutation(
    async (contestId: string) => {
      await api.delete(`/contests/${contestId}`)
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['contests', eventId])
        toast.success('Contest deleted successfully')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to delete contest')
      }
    }
  )

  const archiveContestMutation = useMutation(
    async (contestId: string) => {
      await api.post(`/contests/${contestId}/archive`)
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['contests', eventId])
        toast.success('Contest archived successfully')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to archive contest')
      }
    }
  )

  const handleDelete = (contestId: string) => {
    if (window.confirm('Are you sure you want to delete this contest?')) {
      deleteContestMutation.mutate(contestId)
    }
  }

  const handleArchive = (contestId: string) => {
    if (window.confirm('Are you sure you want to archive this contest?')) {
      archiveContestMutation.mutate(contestId)
    }
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'active':
        return 'bg-green-100 text-green-800'
      case 'draft':
        return 'bg-gray-100 text-gray-800'
      case 'completed':
        return 'bg-blue-100 text-blue-800'
      case 'archived':
        return 'bg-red-100 text-red-800'
      default:
        return 'bg-gray-100 text-gray-800'
    }
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  if (!eventId) {
    return (
      <div className="bg-white rounded-lg shadow p-12 text-center">
        <Trophy className="h-12 w-12 text-gray-300 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">Event not found</h3>
        <p className="text-gray-500 mb-6">Please select an event to view its contests.</p>
        <Link
          to="/events"
          className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
        >
          Back to Events
        </Link>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div className="flex items-center space-x-4">
          <Link
            to="/events"
            className="p-2 hover:bg-gray-100 rounded-lg transition-colors"
          >
            <ArrowLeft className="h-5 w-5 text-gray-600" />
          </Link>
          <div>
            <h1 className="text-2xl font-bold text-gray-900">
              {event?.name || 'Contests'}
            </h1>
            <p className="text-gray-600">Manage contests for this event</p>
          </div>
        </div>
        <button
          onClick={() => setShowCreateModal(true)}
          className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors flex items-center space-x-2"
        >
          <Plus className="h-4 w-4" />
          <span>Create Contest</span>
        </button>
      </div>

      {/* Filters */}
      <div className="bg-white rounded-lg shadow p-4">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <div className="relative">
              <Search className="absolute left-3 top-1/2 transform -translate-y-1/2 h-4 w-4 text-gray-400" />
              <input
                type="text"
                placeholder="Search contests..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="w-full pl-10 pr-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
              />
            </div>
          </div>
          <div className="sm:w-48">
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="all">All Status</option>
              <option value="draft">Draft</option>
              <option value="active">Active</option>
              <option value="completed">Completed</option>
              <option value="archived">Archived</option>
            </select>
          </div>
        </div>
      </div>

      {/* Contests Grid */}
      {contestsData?.data && contestsData.data.length > 0 ? (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {contestsData.data.map((contest: Contest) => (
            <div key={contest.id} className="bg-white rounded-lg shadow hover:shadow-md transition-shadow">
              <div className="p-6">
                <div className="flex items-start justify-between mb-4">
                  <div className="flex-1">
                    <h3 className="text-lg font-semibold text-gray-900 mb-1">
                      {contest.name}
                    </h3>
                    {contest.description && (
                      <p className="text-sm text-gray-600 line-clamp-2">
                        {contest.description}
                      </p>
                    )}
                  </div>
                  <span className={`px-2 py-1 text-xs font-medium rounded-full ${getStatusColor(contest.status)}`}>
                    {contest.status}
                  </span>
                </div>

                <div className="space-y-2 mb-4">
                  <div className="flex items-center text-sm text-gray-600">
                    <Calendar className="h-4 w-4 mr-2" />
                    <span>{formatDate(contest.start_date)} - {formatDate(contest.end_date)}</span>
                  </div>
                  {contest.categories && (
                    <div className="flex items-center text-sm text-gray-600">
                      <Trophy className="h-4 w-4 mr-2" />
                      <span>{contest.categories.length} categories</span>
                    </div>
                  )}
                </div>

                <div className="flex items-center justify-between">
                  <Link
                    to={`/contests/${contest.id}`}
                    className="text-blue-600 hover:text-blue-500 text-sm font-medium flex items-center"
                  >
                    <Eye className="h-4 w-4 mr-1" />
                    View Details
                  </Link>
                  <div className="flex items-center space-x-2">
                    <button
                      onClick={() => handleArchive(contest.id)}
                      className="p-2 text-gray-400 hover:text-yellow-600 transition-colors"
                      title="Archive contest"
                    >
                      <Archive className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => handleDelete(contest.id)}
                      className="p-2 text-gray-400 hover:text-red-600 transition-colors"
                      title="Delete contest"
                    >
                      <Trash2 className="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
              <div className="px-6 py-3 bg-gray-50 rounded-b-lg flex items-center justify-between text-sm text-gray-500">
                <span>Created {formatDate(contest.created_at)}</span>
              </div>
            </div>
          ))}
        </div>
      ) : (
        <div className="bg-white rounded-lg shadow p-12 text-center">
          <Trophy className="h-12 w-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">No contests found</h3>
          <p className="text-gray-500 mb-6">
            {searchTerm || statusFilter !== 'all' 
              ? 'Try adjusting your search or filter criteria.'
              : 'Get started by creating your first contest for this event.'
            }
          </p>
          <button
            onClick={() => setShowCreateModal(true)}
            className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 transition-colors"
          >
            Create Contest
          </button>
        </div>
      )}

      {/* Pagination */}
      {contestsData?.pagination && contestsData.pagination.pages > 1 && (
        <div className="flex items-center justify-between bg-white rounded-lg shadow p-4">
          <div className="text-sm text-gray-700">
            Showing {((contestsData.pagination.page - 1) * contestsData.pagination.limit) + 1} to{' '}
            {Math.min(contestsData.pagination.page * contestsData.pagination.limit, contestsData.pagination.total)} of{' '}
            {contestsData.pagination.total} results
          </div>
          <div className="flex space-x-2">
            <button
              disabled={contestsData.pagination.page === 1}
              className="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Previous
            </button>
            <button
              disabled={contestsData.pagination.page === contestsData.pagination.pages}
              className="px-3 py-1 text-sm border border-gray-300 rounded hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
            >
              Next
            </button>
          </div>
        </div>
      )}
    </div>
  )
}