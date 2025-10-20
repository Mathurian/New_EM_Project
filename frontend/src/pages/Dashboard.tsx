import React from 'react'
import { useSystemStats, useActiveUsers } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  CalendarIcon,
  TrophyIcon,
  TagIcon,
  UsersIcon,
  ChartBarIcon,
  ClockIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'

const Dashboard: React.FC = () => {
  const { user } = useAuth()
  const { data: stats, isLoading: statsLoading } = useSystemStats()
  const { data: activeUsers, isLoading: usersLoading } = useActiveUsers()

  if (statsLoading) {
    return <LoadingSpinner size="lg" className="flex justify-center items-center h-64" />
  }

  const statCards = [
    {
      name: 'Total Events',
      value: stats?.events?.total || 0,
      icon: CalendarIcon,
      color: 'bg-blue-500',
    },
    {
      name: 'Total Contests',
      value: stats?.contests?.total || 0,
      icon: TrophyIcon,
      color: 'bg-green-500',
    },
    {
      name: 'Total Categories',
      value: stats?.categories?.total || 0,
      icon: TagIcon,
      color: 'bg-purple-500',
    },
    {
      name: 'Total Users',
      value: stats?.users?.total || 0,
      icon: UsersIcon,
      color: 'bg-orange-500',
    },
  ]

  const roleBasedStats = () => {
    switch (user?.role) {
      case 'JUDGE':
        return (
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Judge Dashboard
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-blue-800 dark:text-blue-200">
                  Assigned Categories
                </h4>
                <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                  {stats?.categories?.total || 0}
                </p>
              </div>
              <div className="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-green-800 dark:text-green-200">
                  Scores Submitted
                </h4>
                <p className="text-2xl font-bold text-green-900 dark:text-green-100">
                  {stats?.scores?.total || 0}
                </p>
              </div>
            </div>
          </div>
        )
      
      case 'CONTESTANT':
        return (
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
              Contestant Dashboard
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div className="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-purple-800 dark:text-purple-200">
                  Participating Categories
                </h4>
                <p className="text-2xl font-bold text-purple-900 dark:text-purple-100">
                  {stats?.categories?.total || 0}
                </p>
              </div>
              <div className="bg-orange-50 dark:bg-orange-900/20 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-orange-800 dark:text-orange-200">
                  Total Score
                </h4>
                <p className="text-2xl font-bold text-orange-900 dark:text-orange-100">
                  {stats?.scores?.total || 0}
                </p>
              </div>
            </div>
          </div>
        )
      
      case 'ORGANIZER':
      case 'BOARD':
        return (
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
              System Overview
            </h3>
            <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
              <div className="bg-blue-50 dark:bg-blue-900/20 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-blue-800 dark:text-blue-200">
                  Active Events
                </h4>
                <p className="text-2xl font-bold text-blue-900 dark:text-blue-100">
                  {stats?.events?.active || 0}
                </p>
              </div>
              <div className="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-green-800 dark:text-green-200">
                  Active Users
                </h4>
                <p className="text-2xl font-bold text-green-900 dark:text-green-100">
                  {stats?.users?.active || 0}
                </p>
              </div>
              <div className="bg-purple-50 dark:bg-purple-900/20 p-4 rounded-lg">
                <h4 className="text-sm font-medium text-purple-800 dark:text-purple-200">
                  Head Judges
                </h4>
                <p className="text-2xl font-bold text-purple-900 dark:text-purple-100">
                  {stats?.judges?.headJudges || 0}
                </p>
              </div>
            </div>
          </div>
        )
      
      default:
        return null
    }
  }

  return (
    <div className="space-y-6">
      {/* Welcome Section */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          Welcome back, {user?.preferredName || user?.name}!
        </h1>
        <p className="text-gray-600 dark:text-gray-400 mt-2">
          Here's what's happening with your events today.
        </p>
      </div>

      {/* Stats Grid */}
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        {statCards.map((card) => (
          <div key={card.name} className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center">
              <div className={`p-3 rounded-md ${card.color}`}>
                <card.icon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                  {card.name}
                </p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  {card.value}
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>

      {/* Role-based Content */}
      {roleBasedStats()}

      {/* Active Users */}
      {user?.role === 'ORGANIZER' || user?.role === 'BOARD' ? (
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <div className="flex items-center justify-between mb-4">
            <h3 className="text-lg font-medium text-gray-900 dark:text-white">
              Active Users
            </h3>
            <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
              <ClockIcon className="h-4 w-4 mr-1" />
              Last 30 minutes
            </div>
          </div>
          
          {usersLoading ? (
            <LoadingSpinner size="md" className="flex justify-center" />
          ) : (
            <div className="space-y-3">
              {activeUsers?.length === 0 ? (
                <p className="text-gray-500 dark:text-gray-400 text-center py-4">
                  No active users
                </p>
              ) : (
                activeUsers?.map((activeUser: any) => (
                  <div key={activeUser.id} className="flex items-center space-x-3">
                    <div className="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                      <span className="text-sm font-medium text-white">
                        {activeUser.preferredName?.charAt(0) || activeUser.name?.charAt(0) || 'U'}
                      </span>
                    </div>
                    <div className="flex-1 min-w-0">
                      <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                        {activeUser.preferredName || activeUser.name}
                      </p>
                      <p className="text-sm text-gray-500 dark:text-gray-400 capitalize">
                        {activeUser.role?.toLowerCase().replace('_', ' ')}
                      </p>
                    </div>
                    <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                      <div className="h-2 w-2 bg-green-400 rounded-full mr-2" />
                      Online
                    </div>
                  </div>
                ))
              )}
            </div>
          )}
        </div>
      ) : null}

      {/* Recent Activity */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-4">
          Recent Activity
        </h3>
        <div className="space-y-3">
          {stats?.recentActivity?.length === 0 ? (
            <p className="text-gray-500 dark:text-gray-400 text-center py-4">
              No recent activity
            </p>
          ) : (
            stats?.recentActivity?.slice(0, 5).map((activity: any) => (
              <div key={activity.id} className="flex items-center space-x-3">
                <div className="h-8 w-8 rounded-full bg-gray-200 dark:bg-gray-600 flex items-center justify-center">
                  <ChartBarIcon className="h-4 w-4 text-gray-600 dark:text-gray-300" />
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm text-gray-900 dark:text-white">
                    {activity.userName} {activity.action.replace('_', ' ')}
                  </p>
                  <p className="text-sm text-gray-500 dark:text-gray-400">
                    {new Date(activity.createdAt).toLocaleString()}
                  </p>
                </div>
              </div>
            ))
          )}
        </div>
      </div>
    </div>
  )
}

export default Dashboard
