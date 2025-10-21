import React, { useState } from 'react'
import { useQuery } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { useSocket } from '../contexts/SocketContext'
import { adminAPI, eventsAPI, contestsAPI, usersAPI, scoringAPI, api } from '../services/api'
import {
  CalendarIcon,
  TrophyIcon,
  UsersIcon,
  ChartBarIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  EyeIcon,
  PlusIcon,
  ArrowTrendingUpIcon,
  ArrowTrendingDownIcon,
  MinusIcon,
  DocumentTextIcon,
  ClipboardDocumentListIcon,
  UserGroupIcon,
  StarIcon,
  BellIcon,
  CogIcon,
  ShieldCheckIcon,
  DocumentArrowDownIcon,
  PrinterIcon,
} from '@heroicons/react/24/outline'
import { format, subDays, subWeeks, subMonths } from 'date-fns'

const Dashboard: React.FC = () => {
  const { user } = useAuth()
  const { isConnected } = useSocket()
  const [timeRange, setTimeRange] = useState<'7d' | '30d' | '90d' | '1y'>('30d')

  // Admin/Board queries
  const { data: stats, isLoading: statsLoading } = useQuery(
    'admin-stats',
    () => adminAPI.getStats().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
      refetchInterval: 30000, // Refresh every 30 seconds
    }
  )

  const { data: events, isLoading: eventsLoading } = useQuery(
    'recent-events',
    () => eventsAPI.getAll().then(res => res.data.slice(0, 5)),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
      refetchInterval: 60000, // Refresh every minute
    }
  )

  const { data: contests, isLoading: contestsLoading } = useQuery(
    'recent-contests',
    () => contestsAPI.getAll().then(res => res.data.slice(0, 5)),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
      refetchInterval: 60000,
    }
  )

  const { data: users, isLoading: usersLoading } = useQuery(
    'recent-users',
    () => usersAPI.getAll().then(res => res.data.slice(0, 5)),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
      refetchInterval: 120000, // Refresh every 2 minutes
    }
  )

  const { data: activityLogs, isLoading: activityLoading } = useQuery(
    'activity-logs',
    () => adminAPI.getActivityLogs().then(res => res.data.slice(0, 10)),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
      refetchInterval: 30000,
    }
  )

  // Judge queries
  const { data: judgeAssignments, isLoading: assignmentsLoading } = useQuery(
    'judge-assignments',
    () => api.get('/judges/assignments').then(res => res.data),
    {
      enabled: user?.role === 'JUDGE',
      refetchInterval: 60000,
    }
  )

  const { data: judgeScores, isLoading: scoresLoading } = useQuery(
    'judge-scores',
    () => scoringAPI.getScores('', '').then(res => res.data),
    {
      enabled: user?.role === 'JUDGE',
      refetchInterval: 30000,
    }
  )

  // Contestant queries
  const { data: contestantContests, isLoading: contestantContestsLoading } = useQuery(
    'contestant-contests',
    () => api.get('/contestants/contests').then(res => res.data),
    {
      enabled: user?.role === 'CONTESTANT',
      refetchInterval: 60000,
    }
  )

  const { data: contestantResults, isLoading: contestantResultsLoading } = useQuery(
    'contestant-results',
    () => api.get('/contestants/results').then(res => res.data),
    {
      enabled: user?.role === 'CONTESTANT',
      refetchInterval: 30000,
    }
  )

  // Tally Master/Auditor queries
  const { data: certificationQueue, isLoading: certificationLoading } = useQuery(
    'certification-queue',
    () => api.get('/certifications/queue').then(res => res.data),
    {
      enabled: user?.role === 'TALLY_MASTER' || user?.role === 'AUDITOR',
      refetchInterval: 30000,
    }
  )

  const { data: pendingCertifications, isLoading: pendingCertLoading } = useQuery(
    'pending-certifications',
    () => api.get('/certifications/pending').then(res => res.data),
    {
      enabled: user?.role === 'TALLY_MASTER' || user?.role === 'AUDITOR',
      refetchInterval: 30000,
    }
  )

  // Emcee queries
  const { data: emceeScripts, isLoading: scriptsLoading } = useQuery(
    'emcee-scripts',
    () => api.get('/emcee/scripts').then(res => res.data.slice(0, 5)),
    {
      enabled: user?.role === 'EMCEE',
      refetchInterval: 60000,
    }
  )

  const getTrendIcon = (trend: number) => {
    if (trend > 0) return <ArrowTrendingUpIcon className="h-4 w-4 text-green-500" />
    if (trend < 0) return <ArrowTrendingDownIcon className="h-4 w-4 text-red-500" />
    return <MinusIcon className="h-4 w-4 text-gray-500" />
  }

  const getTrendColor = (trend: number) => {
    if (trend > 0) return 'text-green-600 dark:text-green-400'
    if (trend < 0) return 'text-red-600 dark:text-red-400'
    return 'text-gray-600 dark:text-gray-400'
  }

  const getRoleSpecificContent = () => {
    switch (user?.role) {
      case 'ORGANIZER':
      case 'BOARD':
        return (
          <div className="space-y-6">
            {/* Time Range Selector */}
            <div className="flex justify-end">
              <div className="flex space-x-1 bg-gray-100 dark:bg-gray-800 rounded-lg p-1">
                {(['7d', '30d', '90d', '1y'] as const).map((range) => (
                  <button
                    key={range}
                    onClick={() => setTimeRange(range)}
                    className={`px-3 py-1 text-sm rounded-md transition-colors ${
                      timeRange === range
                        ? 'bg-white dark:bg-gray-700 text-gray-900 dark:text-white shadow-sm'
                        : 'text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white'
                    }`}
                  >
                    {range === '7d' ? '7 Days' : range === '30d' ? '30 Days' : range === '90d' ? '90 Days' : '1 Year'}
                  </button>
                ))}
              </div>
            </div>

            {/* System Overview Cards */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <div className="w-8 h-8 bg-blue-500 rounded-md flex items-center justify-center">
                        <CalendarIcon className="h-5 w-5 text-white" />
                      </div>
                    </div>
                    <div className="ml-3 flex-1">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Events</p>
                      <div className="flex items-center">
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {statsLoading ? '--' : stats?.events || 0}
                        </p>
                        {stats?.eventsTrend && (
                          <div className="ml-2 flex items-center">
                            {getTrendIcon(stats.eventsTrend)}
                            <span className={`text-sm ${getTrendColor(stats.eventsTrend)}`}>
                              {Math.abs(stats.eventsTrend)}%
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <div className="w-8 h-8 bg-green-500 rounded-md flex items-center justify-center">
                        <TrophyIcon className="h-5 w-5 text-white" />
                      </div>
                    </div>
                    <div className="ml-3 flex-1">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Contests</p>
                      <div className="flex items-center">
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {statsLoading ? '--' : stats?.contests || 0}
                        </p>
                        {stats?.contestsTrend && (
                          <div className="ml-2 flex items-center">
                            {getTrendIcon(stats.contestsTrend)}
                            <span className={`text-sm ${getTrendColor(stats.contestsTrend)}`}>
                              {Math.abs(stats.contestsTrend)}%
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <div className="w-8 h-8 bg-yellow-500 rounded-md flex items-center justify-center">
                        <UsersIcon className="h-5 w-5 text-white" />
                      </div>
                    </div>
                    <div className="ml-3 flex-1">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Users</p>
                      <div className="flex items-center">
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {statsLoading ? '--' : stats?.users || 0}
                        </p>
                        {stats?.usersTrend && (
                          <div className="ml-2 flex items-center">
                            {getTrendIcon(stats.usersTrend)}
                            <span className={`text-sm ${getTrendColor(stats.usersTrend)}`}>
                              {Math.abs(stats.usersTrend)}%
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <div className="w-8 h-8 bg-purple-500 rounded-md flex items-center justify-center">
                        <StarIcon className="h-5 w-5 text-white" />
                      </div>
                    </div>
                    <div className="ml-3 flex-1">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Scores</p>
                      <div className="flex items-center">
                        <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                          {statsLoading ? '--' : stats?.scores || 0}
                        </p>
                        {stats?.scoresTrend && (
                          <div className="ml-2 flex items-center">
                            {getTrendIcon(stats.scoresTrend)}
                            <span className={`text-sm ${getTrendColor(stats.scoresTrend)}`}>
                              {Math.abs(stats.scoresTrend)}%
                            </span>
                          </div>
                        )}
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Main Content Grid */}
            <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
              {/* Recent Events */}
              <div className="lg:col-span-2">
                <div className="card">
                  <div className="card-header">
                    <div className="flex items-center justify-between">
                      <h3 className="card-title text-lg">Recent Events</h3>
                      <button className="btn btn-outline btn-sm">
                        <EyeIcon className="h-4 w-4 mr-1" />
                        View All
                      </button>
                    </div>
                  </div>
                  <div className="card-content">
                    {eventsLoading ? (
                      <div className="flex items-center justify-center py-8">
                        <div className="loading-spinner"></div>
                      </div>
                    ) : events && events.length > 0 ? (
                      <div className="space-y-3">
                        {events.map((event: any) => (
                          <div key={event.id} className="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <CalendarIcon className="h-5 w-5 text-gray-400" />
                            <div className="flex-1 min-w-0">
                              <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                                {event.name}
                              </p>
                              <p className="text-xs text-gray-500 dark:text-gray-400">
                                {format(new Date(event.startDate), 'MMM dd, yyyy')} - {format(new Date(event.endDate), 'MMM dd, yyyy')}
                              </p>
                            </div>
                            <div className="flex items-center space-x-2">
                              <span className={`badge ${event.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary'}`}>
                                {event.status}
                              </span>
                              <button className="btn btn-ghost btn-sm">
                                <EyeIcon className="h-4 w-4" />
                              </button>
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                        <CalendarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                        <p>No events found</p>
                        <button className="btn btn-primary btn-sm mt-2">
                          <PlusIcon className="h-4 w-4 mr-1" />
                          Create Event
                        </button>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* System Status */}
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">System Status</h3>
                </div>
                <div className="card-content">
                  <div className="space-y-4">
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600 dark:text-gray-400">Database</span>
                      <div className="flex items-center space-x-2">
                        <CheckCircleIcon className="h-4 w-4 text-green-500" />
                        <span className="text-sm text-green-600 dark:text-green-400">Connected</span>
                      </div>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600 dark:text-gray-400">WebSocket</span>
                      <div className="flex items-center space-x-2">
                        {isConnected ? (
                          <>
                            <CheckCircleIcon className="h-4 w-4 text-green-500" />
                            <span className="text-sm text-green-600 dark:text-green-400">Connected</span>
                          </>
                        ) : (
                          <>
                            <ExclamationTriangleIcon className="h-4 w-4 text-yellow-500" />
                            <span className="text-sm text-yellow-600 dark:text-yellow-400">Disconnected</span>
                          </>
                        )}
                      </div>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600 dark:text-gray-400">Active Users</span>
                      <span className="text-sm font-medium">{stats?.activeUsers || 0}</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600 dark:text-gray-400">Server Load</span>
                      <span className="text-sm font-medium">{stats?.serverLoad || 'Low'}</span>
                    </div>
                    <div className="flex items-center justify-between">
                      <span className="text-sm text-gray-600 dark:text-gray-400">Storage Used</span>
                      <span className="text-sm font-medium">{stats?.storageUsed || '45%'}</span>
                    </div>
                  </div>
                </div>
              </div>

              {/* Quick Actions */}
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">Quick Actions</h3>
                </div>
                <div className="card-content">
                  <div className="grid grid-cols-2 gap-3">
                    <button className="btn btn-outline btn-sm">
                      <CalendarIcon className="h-4 w-4 mr-2" />
                      New Event
                    </button>
                    <button className="btn btn-outline btn-sm">
                      <TrophyIcon className="h-4 w-4 mr-2" />
                      New Contest
                    </button>
                    <button className="btn btn-outline btn-sm">
                      <UsersIcon className="h-4 w-4 mr-2" />
                      Add User
                    </button>
                    <button className="btn btn-outline btn-sm">
                      <ChartBarIcon className="h-4 w-4 mr-2" />
                      Reports
                    </button>
                    <button className="btn btn-outline btn-sm">
                      <DocumentArrowDownIcon className="h-4 w-4 mr-2" />
                      Export Data
                    </button>
                    <button className="btn btn-outline btn-sm">
                      <CogIcon className="h-4 w-4 mr-2" />
                      Settings
                    </button>
                  </div>
                </div>
              </div>

              {/* Recent Activity */}
              <div className="lg:col-span-2">
                <div className="card">
                  <div className="card-header">
                    <div className="flex items-center justify-between">
                      <h3 className="card-title text-lg">Recent Activity</h3>
                      <button className="btn btn-outline btn-sm">
                        <BellIcon className="h-4 w-4 mr-1" />
                        View All
                      </button>
                    </div>
                  </div>
                  <div className="card-content">
                    {activityLoading ? (
                      <div className="flex items-center justify-center py-8">
                        <div className="loading-spinner"></div>
                      </div>
                    ) : activityLogs && activityLogs.length > 0 ? (
                      <div className="space-y-3">
                        {activityLogs.map((log: any, index: number) => (
                          <div key={index} className="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                            <div className="flex-shrink-0">
                              <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                <span className="text-white text-xs font-medium">
                                  {log.user?.name?.charAt(0).toUpperCase()}
                                </span>
                              </div>
                            </div>
                            <div className="flex-1 min-w-0">
                              <p className="text-sm text-gray-900 dark:text-white">
                                <span className="font-medium">{log.user?.name}</span> {log.action}
                              </p>
                              <p className="text-xs text-gray-500 dark:text-gray-400">
                                {format(new Date(log.createdAt), 'MMM dd, yyyy HH:mm')}
                              </p>
                            </div>
                            <div className="flex-shrink-0">
                              <span className={`badge ${log.type === 'CREATE' ? 'badge-success' : log.type === 'UPDATE' ? 'badge-warning' : 'badge-destructive'}`}>
                                {log.type}
                              </span>
                            </div>
                          </div>
                        ))}
                      </div>
                    ) : (
                      <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                        <BellIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                        <p>No recent activity</p>
                      </div>
                    )}
                  </div>
                </div>
              </div>

              {/* Performance Metrics */}
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">Performance</h3>
                </div>
                <div className="card-content">
                  <div className="space-y-4">
                    <div>
                      <div className="flex justify-between text-sm mb-1">
                        <span className="text-gray-600 dark:text-gray-400">Response Time</span>
                        <span className="text-gray-900 dark:text-white">{stats?.responseTime || '120ms'}</span>
                      </div>
                      <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div className="bg-green-500 h-2 rounded-full" style={{ width: '75%' }}></div>
                      </div>
                    </div>
                    <div>
                      <div className="flex justify-between text-sm mb-1">
                        <span className="text-gray-600 dark:text-gray-400">Uptime</span>
                        <span className="text-gray-900 dark:text-white">{stats?.uptime || '99.9%'}</span>
                      </div>
                      <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div className="bg-green-500 h-2 rounded-full" style={{ width: '99%' }}></div>
                      </div>
                    </div>
                    <div>
                      <div className="flex justify-between text-sm mb-1">
                        <span className="text-gray-600 dark:text-gray-400">Memory Usage</span>
                        <span className="text-gray-900 dark:text-white">{stats?.memoryUsage || '68%'}</span>
                      </div>
                      <div className="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-2">
                        <div className="bg-yellow-500 h-2 rounded-full" style={{ width: '68%' }}></div>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )

      case 'JUDGE':
        return (
          <div className="space-y-6">
            {/* Judge Dashboard Header */}
            <div className="card">
              <div className="card-content">
                <div className="flex items-center space-x-4">
                  <div className="w-12 h-12 bg-green-500 rounded-full flex items-center justify-center">
                    <TrophyIcon className="h-6 w-6 text-white" />
                  </div>
                  <div>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Judge Dashboard</h2>
                    <p className="text-gray-600 dark:text-gray-400">Manage your scoring assignments</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Judge Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <ClipboardDocumentListIcon className="h-8 w-8 text-blue-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Assigned Categories</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {assignmentsLoading ? '--' : judgeAssignments?.length || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <CheckCircleIcon className="h-8 w-8 text-green-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Completed Scores</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {scoresLoading ? '--' : judgeScores?.completed || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <ClockIcon className="h-8 w-8 text-yellow-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Scores</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {scoresLoading ? '--' : judgeScores?.pending || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Assignments */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">My Assignments</h3>
                </div>
                <div className="card-content">
                  {assignmentsLoading ? (
                    <div className="flex items-center justify-center py-8">
                      <div className="loading-spinner"></div>
                    </div>
                  ) : judgeAssignments && judgeAssignments.length > 0 ? (
                    <div className="space-y-3">
                      {judgeAssignments.map((assignment: any) => (
                        <div key={assignment.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                          <div>
                            <h4 className="font-medium text-gray-900 dark:text-white">{assignment.categoryName}</h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{assignment.contestName}</p>
                          </div>
                          <div className="flex items-center space-x-2">
                            <span className={`badge ${assignment.status === 'COMPLETED' ? 'badge-success' : 'badge-warning'}`}>
                              {assignment.status}
                            </span>
                            <button className="btn btn-primary btn-sm">
                              <EyeIcon className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <TrophyIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                      <p>No active assignments</p>
                      <p className="text-sm mt-2">Contact your organizer for category assignments</p>
                    </div>
                  )}
                </div>
              </div>

              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">Quick Actions</h3>
                </div>
                <div className="card-content">
                  <div className="space-y-3">
                    <button className="btn btn-primary w-full">
                      <TrophyIcon className="h-4 w-4 mr-2" />
                      Start Scoring
                    </button>
                    <button className="btn btn-outline w-full">
                      <ChartBarIcon className="h-4 w-4 mr-2" />
                      View Results
                    </button>
                    <button className="btn btn-outline w-full">
                      <DocumentTextIcon className="h-4 w-4 mr-2" />
                      Score History
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )

      case 'CONTESTANT':
        return (
          <div className="space-y-6">
            {/* Contestant Dashboard Header */}
            <div className="card">
              <div className="card-content">
                <div className="flex items-center space-x-4">
                  <div className="w-12 h-12 bg-yellow-500 rounded-full flex items-center justify-center">
                    <UserGroupIcon className="h-6 w-6 text-white" />
                  </div>
                  <div>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Contestant Dashboard</h2>
                    <p className="text-gray-600 dark:text-gray-400">Track your contest performance</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Contestant Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <TrophyIcon className="h-8 w-8 text-blue-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Active Contests</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {contestantContestsLoading ? '--' : contestantContests?.length || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <StarIcon className="h-8 w-8 text-green-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Average Score</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {contestantResultsLoading ? '--' : contestantResults?.averageScore?.toFixed(1) || '--'}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <ChartBarIcon className="h-8 w-8 text-purple-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Current Rank</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {contestantResultsLoading ? '--' : contestantResults?.rank || '--'}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Contestant Content */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">My Contests</h3>
                </div>
                <div className="card-content">
                  {contestantContestsLoading ? (
                    <div className="flex items-center justify-center py-8">
                      <div className="loading-spinner"></div>
                    </div>
                  ) : contestantContests && contestantContests.length > 0 ? (
                    <div className="space-y-3">
                      {contestantContests.map((contest: any) => (
                        <div key={contest.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                          <div>
                            <h4 className="font-medium text-gray-900 dark:text-white">{contest.name}</h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{contest.eventName}</p>
                          </div>
                          <div className="flex items-center space-x-2">
                            <span className={`badge ${contest.status === 'ACTIVE' ? 'badge-success' : 'badge-secondary'}`}>
                              {contest.status}
                            </span>
                            <button className="btn btn-primary btn-sm">
                              <EyeIcon className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <TrophyIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                      <p>No active contests</p>
                      <p className="text-sm mt-2">Contact your organizer for contest assignments</p>
                    </div>
                  )}
                </div>
              </div>

              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">My Performance</h3>
                </div>
                <div className="card-content">
                  {contestantResultsLoading ? (
                    <div className="flex items-center justify-center py-8">
                      <div className="loading-spinner"></div>
                    </div>
                  ) : contestantResults ? (
                    <div className="space-y-4">
                      <div className="flex justify-between">
                        <span className="text-gray-600 dark:text-gray-400">Overall Score</span>
                        <span className="font-semibold text-gray-900 dark:text-white">
                          {contestantResults.totalScore?.toFixed(1) || '--'}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600 dark:text-gray-400">Ranking</span>
                        <span className="font-semibold text-gray-900 dark:text-white">
                          #{contestantResults.rank || '--'}
                        </span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-600 dark:text-gray-400">Categories</span>
                        <span className="font-semibold text-gray-900 dark:text-white">
                          {contestantResults.categoriesScored || 0}
                        </span>
                      </div>
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <ChartBarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                      <p>No results available</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          </div>
        )

      case 'EMCEE':
        return (
          <div className="space-y-6">
            {/* Emcee Dashboard Header */}
            <div className="card">
              <div className="card-content">
                <div className="flex items-center space-x-4">
                  <div className="w-12 h-12 bg-pink-500 rounded-full flex items-center justify-center">
                    <ClockIcon className="h-6 w-6 text-white" />
                  </div>
                  <div>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">Emcee Dashboard</h2>
                    <p className="text-gray-600 dark:text-gray-400">Manage your scripts and announcements</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Emcee Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <DocumentTextIcon className="h-8 w-8 text-blue-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Total Scripts</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {scriptsLoading ? '--' : emceeScripts?.length || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <CheckCircleIcon className="h-8 w-8 text-green-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Active Scripts</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {scriptsLoading ? '--' : emceeScripts?.filter((s: any) => s.isActive).length || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <CalendarIcon className="h-8 w-8 text-purple-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Events Today</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {scriptsLoading ? '--' : emceeScripts?.filter((s: any) => s.eventId).length || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Emcee Content */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">Recent Scripts</h3>
                </div>
                <div className="card-content">
                  {scriptsLoading ? (
                    <div className="flex items-center justify-center py-8">
                      <div className="loading-spinner"></div>
                    </div>
                  ) : emceeScripts && emceeScripts.length > 0 ? (
                    <div className="space-y-3">
                      {emceeScripts.map((script: any) => (
                        <div key={script.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                          <div>
                            <h4 className="font-medium text-gray-900 dark:text-white">{script.title}</h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{script.type}</p>
                          </div>
                          <div className="flex items-center space-x-2">
                            <span className={`badge ${script.isActive ? 'badge-success' : 'badge-secondary'}`}>
                              {script.isActive ? 'Active' : 'Inactive'}
                            </span>
                            <button className="btn btn-primary btn-sm">
                              <EyeIcon className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <ClockIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                      <p>No scripts available</p>
                      <p className="text-sm mt-2">Contact your organizer for script assignments</p>
                    </div>
                  )}
                </div>
              </div>

              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">Quick Actions</h3>
                </div>
                <div className="card-content">
                  <div className="space-y-3">
                    <button className="btn btn-primary w-full">
                      <ClockIcon className="h-4 w-4 mr-2" />
                      View Scripts
                    </button>
                    <button className="btn btn-outline w-full">
                      <PlusIcon className="h-4 w-4 mr-2" />
                      Create Script
                    </button>
                    <button className="btn btn-outline w-full">
                      <InformationCircleIcon className="h-4 w-4 mr-2" />
                      Event Info
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )

      case 'TALLY_MASTER':
      case 'AUDITOR':
        return (
          <div className="space-y-6">
            {/* Tally Master/Auditor Dashboard Header */}
            <div className="card">
              <div className="card-content">
                <div className="flex items-center space-x-4">
                  <div className="w-12 h-12 bg-indigo-500 rounded-full flex items-center justify-center">
                    <ShieldCheckIcon className="h-6 w-6 text-white" />
                  </div>
                  <div>
                    <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
                      {user?.role === 'TALLY_MASTER' ? 'Tally Master' : 'Auditor'} Dashboard
                    </h2>
                    <p className="text-gray-600 dark:text-gray-400">Review and certify contest scores</p>
                  </div>
                </div>
              </div>
            </div>

            {/* Certification Stats */}
            <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <ClockIcon className="h-8 w-8 text-yellow-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Review</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {certificationLoading ? '--' : certificationQueue?.length || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <CheckCircleIcon className="h-8 w-8 text-green-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Certified</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {pendingCertLoading ? '--' : pendingCertifications?.certified || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>

              <div className="card">
                <div className="card-content">
                  <div className="flex items-center">
                    <ExclamationTriangleIcon className="h-8 w-8 text-red-500" />
                    <div className="ml-3">
                      <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Issues Found</p>
                      <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                        {pendingCertLoading ? '--' : pendingCertifications?.issues || 0}
                      </p>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            {/* Certification Content */}
            <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">Certification Queue</h3>
                </div>
                <div className="card-content">
                  {certificationLoading ? (
                    <div className="flex items-center justify-center py-8">
                      <div className="loading-spinner"></div>
                    </div>
                  ) : certificationQueue && certificationQueue.length > 0 ? (
                    <div className="space-y-3">
                      {certificationQueue.map((item: any) => (
                        <div key={item.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                          <div>
                            <h4 className="font-medium text-gray-900 dark:text-white">{item.categoryName}</h4>
                            <p className="text-sm text-gray-600 dark:text-gray-400">{item.contestName}</p>
                          </div>
                          <div className="flex items-center space-x-2">
                            <span className={`badge ${item.status === 'PENDING' ? 'badge-warning' : 'badge-success'}`}>
                              {item.status}
                            </span>
                            <button className="btn btn-primary btn-sm">
                              <EyeIcon className="h-4 w-4" />
                            </button>
                          </div>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <CheckCircleIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                      <p>No pending certifications</p>
                      <p className="text-sm mt-2">Categories will appear here when judges complete scoring</p>
                    </div>
                  )}
                </div>
              </div>

              <div className="card">
                <div className="card-header">
                  <h3 className="card-title text-lg">Quick Actions</h3>
                </div>
                <div className="card-content">
                  <div className="space-y-3">
                    <button className="btn btn-primary w-full">
                      <CheckCircleIcon className="h-4 w-4 mr-2" />
                      Review Scores
                    </button>
                    <button className="btn btn-outline w-full">
                      <ChartBarIcon className="h-4 w-4 mr-2" />
                      View Results
                    </button>
                    <button className="btn btn-outline w-full">
                      <DocumentTextIcon className="h-4 w-4 mr-2" />
                      Audit Report
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        )

      default:
        return (
          <div className="card">
            <div className="card-content text-center py-12">
              <InformationCircleIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Welcome to Event Manager
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                Your role is being configured. Please contact your administrator.
              </p>
            </div>
          </div>
        )
    }
  }

  return (
    <div className="space-y-6">
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
          Welcome back, {user?.preferredName || user?.name}!
        </h1>
        <p className="text-gray-600 dark:text-gray-400">
          Here's what's happening with your contests today.
        </p>
      </div>

      {getRoleSpecificContent()}
    </div>
  )
}

export default Dashboard
