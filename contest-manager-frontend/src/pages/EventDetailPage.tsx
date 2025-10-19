import { useParams } from 'react-router-dom'
import { useQuery } from 'react-query'
import { api } from '../lib/api'
import { 
  Calendar, 
  Users, 
  Trophy, 
  Target, 
  BarChart3,
  Edit,
  Archive,
  Trash2,
  ArrowLeft
} from 'lucide-react'
import { formatDate } from '../lib/utils'
import { LoadingSpinner } from '../components/ui/LoadingSpinner'
import { Link } from 'react-router-dom'

interface Event {
  id: string
  name: string
  description?: string
  start_date: string
  end_date: string
  status: 'draft' | 'active' | 'completed' | 'archived'
  created_at: string
  categories?: Array<{
    id: string
    name: string
    subcategories: Array<{
      id: string
      name: string
      contestants: Array<{
        id: string
        name: string
        contestant_number?: number
      }>
    }>
  }>
}

export function EventDetailPage() {
  const { id } = useParams<{ id: string }>()

  const { data: event, isLoading } = useQuery<Event>(
    ['event', id],
    async () => {
      const response = await api.get(`/events/${id}`)
      return response.data
    },
    { enabled: !!id }
  )

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

  if (!event) {
    return (
      <div className="bg-white rounded-lg shadow p-12 text-center">
        <Trophy className="h-12 w-12 text-gray-300 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">Event not found</h3>
        <p className="text-gray-500 mb-6">The event you're looking for doesn't exist.</p>
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
            <h1 className="text-2xl font-bold text-gray-900">{event.name}</h1>
            <p className="text-gray-600">Event details and management</p>
          </div>
        </div>
        <div className="flex items-center space-x-2">
          <span className={`px-3 py-1 text-sm font-medium rounded-full ${getStatusColor(event.status)}`}>
            {event.status}
          </span>
          <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <Edit className="h-5 w-5 text-gray-600" />
          </button>
          <button className="p-2 hover:bg-gray-100 rounded-lg transition-colors">
            <Archive className="h-5 w-5 text-gray-600" />
          </button>
          <button className="p-2 hover:bg-red-100 rounded-lg transition-colors">
            <Trash2 className="h-5 w-5 text-red-600" />
          </button>
        </div>
      </div>

      {/* Event Information */}
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Event Information</h2>
        <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
          <div>
            <h3 className="text-sm font-medium text-gray-700 mb-2">Description</h3>
            <p className="text-gray-900">{event.description || 'No description provided'}</p>
          </div>
          <div>
            <h3 className="text-sm font-medium text-gray-700 mb-2">Duration</h3>
            <div className="flex items-center space-x-2 text-gray-900">
              <Calendar className="h-4 w-4" />
              <span>{formatDate(event.start_date)} - {formatDate(event.end_date)}</span>
            </div>
          </div>
        </div>
      </div>

      {/* Contests */}
      {event.contests && event.contests.length > 0 && (
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Contests</h2>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {event.contests.map((contest) => (
              <div key={contest.id} className="border border-gray-200 rounded-lg p-4 hover:shadow-md transition-shadow">
                <h3 className="font-medium text-gray-900 mb-2">{contest.name}</h3>
                {contest.description && (
                  <p className="text-sm text-gray-600 mb-3 line-clamp-2">{contest.description}</p>
                )}
                <div className="flex items-center justify-between">
                  <div className="flex items-center text-sm text-gray-600">
                    <Calendar className="h-4 w-4 mr-1" />
                    <span>{formatDate(contest.start_date)} - {formatDate(contest.end_date)}</span>
                  </div>
                  <span className={`px-2 py-1 text-xs font-medium rounded-full ${
                    contest.status === 'active' 
                      ? 'bg-green-100 text-green-800' 
                      : 'bg-gray-100 text-gray-800'
                  }`}>
                    {contest.status}
                  </span>
                </div>
                <div className="mt-3">
                  <Link
                    to={`/contests/${contest.id}`}
                    className="text-blue-600 hover:text-blue-500 text-sm font-medium"
                  >
                    View Contest Details →
                  </Link>
                </div>
              </div>
            ))}
          </div>
        </div>
      )}

      {/* Quick Actions */}
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <Link
            to={`/scoring?event=${event.id}`}
            className="flex items-center space-x-3 p-4 bg-blue-50 rounded-lg hover:bg-blue-100 transition-colors"
          >
            <Target className="h-6 w-6 text-blue-600" />
            <div>
              <h3 className="font-medium text-blue-900">Start Scoring</h3>
              <p className="text-sm text-blue-700">Begin scoring contestants</p>
            </div>
          </Link>
          
          <Link
            to={`/results?event=${event.id}`}
            className="flex items-center space-x-3 p-4 bg-green-50 rounded-lg hover:bg-green-100 transition-colors"
          >
            <BarChart3 className="h-6 w-6 text-green-600" />
            <div>
              <h3 className="font-medium text-green-900">View Results</h3>
              <p className="text-sm text-green-700">Check event results</p>
            </div>
          </Link>
          
          <Link
            to={`/users?event=${event.id}`}
            className="flex items-center space-x-3 p-4 bg-purple-50 rounded-lg hover:bg-purple-100 transition-colors"
          >
            <Users className="h-6 w-6 text-purple-600" />
            <div>
              <h3 className="font-medium text-purple-900">Manage Users</h3>
              <p className="text-sm text-purple-700">Assign judges and contestants</p>
            </div>
          </Link>
        </div>
      </div>

      {/* Event Statistics */}
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Event Statistics</h2>
        <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {event.categories?.length || 0}
            </div>
            <div className="text-sm text-gray-600">Categories</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {event.categories?.reduce((total, cat) => total + cat.subcategories.length, 0) || 0}
            </div>
            <div className="text-sm text-gray-600">Subcategories</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {event.categories?.reduce((total, cat) => 
                total + cat.subcategories.reduce((subTotal, sub) => subTotal + sub.contestants.length, 0), 0) || 0}
            </div>
            <div className="text-sm text-gray-600">Contestants</div>
          </div>
          <div className="text-center">
            <div className="text-2xl font-bold text-gray-900">
              {event.status === 'active' ? 'In Progress' : 'Completed'}
            </div>
            <div className="text-sm text-gray-600">Status</div>
          </div>
        </div>
      </div>
    </div>
  )
}