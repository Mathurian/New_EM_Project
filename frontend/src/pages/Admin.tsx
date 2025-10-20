import React from 'react'
import { useSystemStats, useActivityLogs, useActiveUsers, useSystemSettings } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  ChartBarIcon,
  UsersIcon,
  CalendarIcon,
  TrophyIcon,
  TagIcon,
  ClockIcon,
  CogIcon,
  DocumentTextIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'

const Admin: React.FC = () => {
  const { user } = useAuth()
  const { data: stats, isLoading: statsLoading } = useSystemStats()
  const { data: activityLogs, isLoading: logsLoading } = useActivityLogs({ limit: 10 })
  const { data: activeUsers, isLoading: usersLoading } = useActiveUsers()
  const { data: settings, isLoading: settingsLoading } = useSystemSettings()

  const canAccessAdmin = user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  if (!canAccessAdmin) {
    return (
      <div className="text-center py-12">
        <CogIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          Access Denied
        </h3>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          You don't have permission to access the admin section.
        </p>
      </div>
    )
  }

  if (statsLoading) {
    return <LoadingSpinner size="lg" className="flex justify-center items-center h-64" />
  }

  const statCards = [
    {
      name: 'Total Users',
      value: stats?.users?.total || 0,
      icon: UsersIcon,
      color: 'bg-blue-500',
      change: stats?.users?.active || 0,
      changeLabel: 'Active'
    },
    {
      name: 'Total Events',
      value: stats?.events?.total || 0,
      icon: CalendarIcon,
      color: 'bg-green-500',
      change: stats?.events?.active || 0,
      changeLabel: 'Active'
    },
    {
      name: 'Total Contests',
      value: stats?.contests?.total || 0,
      icon: TrophyIcon,
      color: 'bg-purple-500',
      change: stats?.categories?.total || 0,
      changeLabel: 'Categories'
    },
    {
      name: 'Total Contestants',
      value: stats?.contestants?.total || 0,
      icon: UsersIcon,
      color: 'bg-orange-500',
      change: stats?.judges?.total || 0,
      changeLabel: 'Judges'
    },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div className="flex items-center space-x-3">
          <div className="p-3 rounded-md bg-blue-500">
            <CogIcon className="h-6 w-6 text-white" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
              Admin Dashboard
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mt-1">
              System overview and administration tools
            </p>
          </div>
        </div>
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
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  {card.change} {card.changeLabel}
                </p>
              </div>
            </div>
          </div>
        ))}
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        {/* Active Users */}
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
          <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <div className="flex items-center justify-between">
              <h2 className="text-lg font-medium text-gray-900 dark:text-white">
                Active Users
              </h2>
              <div className="flex items-center text-sm text-gray-500 dark:text-gray-400">
                <ClockIcon className="h-4 w-4 mr-1" />
                Last 30 minutes
              </div>
            </div>
          </div>
          <div className="p-6">
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
        </div>

        {/* Recent Activity */}
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
          <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Recent Activity
            </h2>
          </div>
          <div className="p-6">
            {logsLoading ? (
              <LoadingSpinner size="md" className="flex justify-center" />
            ) : (
              <div className="space-y-3">
                {activityLogs?.logs?.length === 0 ? (
                  <p className="text-gray-500 dark:text-gray-400 text-center py-4">
                    No recent activity
                  </p>
                ) : (
                  activityLogs?.logs?.slice(0, 5).map((activity: any) => (
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
            )}
          </div>
        </div>
      </div>

      {/* System Settings */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-medium text-gray-900 dark:text-white">
            System Settings
          </h2>
        </div>
        <div className="p-6">
          {settingsLoading ? (
            <LoadingSpinner size="md" className="flex justify-center" />
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {Object.entries(settings || {}).map(([key, setting]: [string, any]) => (
                <div key={key} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                  <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                    {key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                  </h3>
                  <p className="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    {setting.value}
                  </p>
                  {setting.description && (
                    <p className="text-xs text-gray-400 dark:text-gray-500 mt-2">
                      {setting.description}
                    </p>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-medium text-gray-900 dark:text-white">
            Quick Actions
          </h2>
        </div>
        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <button className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <UsersIcon className="h-8 w-8 text-blue-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Manage Users
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Add, edit, or remove users
                </p>
              </div>
            </button>

            <button className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <DocumentTextIcon className="h-8 w-8 text-green-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  View Logs
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  System activity logs
                </p>
              </div>
            </button>

            <button className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <CogIcon className="h-8 w-8 text-purple-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  System Settings
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Configure system options
                </p>
              </div>
            </button>

            <button className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
              <ChartBarIcon className="h-8 w-8 text-orange-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Reports
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Generate system reports
                </p>
              </div>
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Admin
