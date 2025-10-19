import { useQuery } from 'react-query'
import { api } from '../lib/api'
import { useAuthStore } from '../stores/authStore'
import { 
  Trophy, 
  Users, 
  Target, 
  BarChart3, 
  Calendar,
  TrendingUp,
  Clock,
  Award
} from 'lucide-react'
import { formatDate } from '../lib/utils'

interface DashboardStats {
  total_contests: number
  active_contests: number
  total_users: number
  total_scores: number
  recent_activity: Array<{
    id: string
    type: string
    description: string
    timestamp: string
  }>
}

export function DashboardPage() {
  const { user } = useAuthStore()

  const { data: stats, isLoading } = useQuery<DashboardStats>(
    'dashboard-stats',
    async () => {
      const response = await api.get('/contests')
      // This would be a dedicated dashboard endpoint in a real app
      return {
        total_contests: response.data.pagination?.total || 0,
        active_contests: 0,
        total_users: 0,
        total_scores: 0,
        recent_activity: []
      }
    }
  )

  const { data: contests } = useQuery(
    'recent-contests',
    async () => {
      const response = await api.get('/contests?limit=5')
      return response.data.data
    }
  )

  const statCards = [
    {
      title: 'Total Contests',
      value: stats?.total_contests || 0,
      icon: Trophy,
      color: 'bg-blue-500',
      change: '+12%',
      changeType: 'positive' as const
    },
    {
      title: 'Active Contests',
      value: stats?.active_contests || 0,
      icon: Calendar,
      color: 'bg-green-500',
      change: '+5%',
      changeType: 'positive' as const
    },
    {
      title: 'Total Users',
      value: stats?.total_users || 0,
      icon: Users,
      color: 'bg-purple-500',
      change: '+8%',
      changeType: 'positive' as const
    },
    {
      title: 'Scores Submitted',
      value: stats?.total_scores || 0,
      icon: Target,
      color: 'bg-orange-500',
      change: '+15%',
      changeType: 'positive' as const
    }
  ]

  const quickActions = [
    {
      title: 'Create Contest',
      description: 'Start a new contest',
      icon: Trophy,
      href: '/contests',
      color: 'bg-blue-50 text-blue-600 hover:bg-blue-100'
    },
    {
      title: 'View Scoring',
      description: 'Access scoring interface',
      icon: Target,
      href: '/scoring',
      color: 'bg-green-50 text-green-600 hover:bg-green-100'
    },
    {
      title: 'View Results',
      description: 'Check contest results',
      icon: BarChart3,
      href: '/results',
      color: 'bg-purple-50 text-purple-600 hover:bg-purple-100'
    },
    {
      title: 'Manage Users',
      description: 'Add or edit users',
      icon: Users,
      href: '/users',
      color: 'bg-orange-50 text-orange-600 hover:bg-orange-100'
    }
  ]

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <div className="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="bg-gradient-to-r from-blue-600 to-purple-600 rounded-lg p-6 text-white">
        <h1 className="text-2xl font-bold">
          Welcome back, {user?.preferred_name || user?.first_name}!
        </h1>
        <p className="text-blue-100 mt-1">
          Here's what's happening with your contests today.
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {statCards.map((stat, index) => (
          <div key={index} className="bg-white rounded-lg shadow p-6">
            <div className="flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-gray-600">{stat.title}</p>
                <p className="text-2xl font-bold text-gray-900">{stat.value}</p>
                <div className="flex items-center mt-2">
                  <TrendingUp className="h-4 w-4 text-green-500 mr-1" />
                  <span className="text-sm text-green-600">{stat.change}</span>
                  <span className="text-sm text-gray-500 ml-1">vs last month</span>
                </div>
              </div>
              <div className={`p-3 rounded-lg ${stat.color}`}>
                <stat.icon className="h-6 w-6 text-white" />
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Quick Actions */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Quick Actions</h2>
          <div className="grid grid-cols-2 gap-4">
            {quickActions.map((action, index) => (
              <a
                key={index}
                href={action.href}
                className={`p-4 rounded-lg border-2 border-dashed transition-colors ${action.color}`}
              >
                <action.icon className="h-8 w-8 mb-2" />
                <h3 className="font-medium">{action.title}</h3>
                <p className="text-sm opacity-75">{action.description}</p>
              </a>
            ))}
          </div>
        </div>

        {/* Recent Contests */}
        <div className="bg-white rounded-lg shadow p-6">
          <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Contests</h2>
          {contests && contests.length > 0 ? (
            <div className="space-y-3">
              {contests.map((contest: any) => (
                <div key={contest.id} className="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                  <div>
                    <h3 className="font-medium text-gray-900">{contest.name}</h3>
                    <p className="text-sm text-gray-500">
                      {formatDate(contest.start_date)} - {formatDate(contest.end_date)}
                    </p>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className={`px-2 py-1 text-xs rounded-full ${
                      contest.status === 'active' 
                        ? 'bg-green-100 text-green-800' 
                        : 'bg-gray-100 text-gray-800'
                    }`}>
                      {contest.status}
                    </span>
                    <Award className="h-4 w-4 text-gray-400" />
                  </div>
                </div>
              ))}
            </div>
          ) : (
            <div className="text-center py-8">
              <Trophy className="h-12 w-12 text-gray-300 mx-auto mb-4" />
              <p className="text-gray-500">No contests yet</p>
              <a
                href="/contests"
                className="text-blue-600 hover:text-blue-500 text-sm font-medium"
              >
                Create your first contest
              </a>
            </div>
          )}
        </div>
      </div>

      {/* Recent Activity */}
      <div className="bg-white rounded-lg shadow p-6">
        <h2 className="text-lg font-semibold text-gray-900 mb-4">Recent Activity</h2>
        {stats?.recent_activity && stats.recent_activity.length > 0 ? (
          <div className="space-y-3">
            {stats.recent_activity.map((activity) => (
              <div key={activity.id} className="flex items-center space-x-3">
                <div className="h-2 w-2 bg-blue-500 rounded-full"></div>
                <div className="flex-1">
                  <p className="text-sm text-gray-900">{activity.description}</p>
                  <p className="text-xs text-gray-500">{formatDate(activity.timestamp)}</p>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="text-center py-8">
            <Clock className="h-12 w-12 text-gray-300 mx-auto mb-4" />
            <p className="text-gray-500">No recent activity</p>
          </div>
        )}
      </div>
    </div>
  )
}