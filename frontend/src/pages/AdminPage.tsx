import React, { useState } from 'react'
import { useQuery } from 'react-query'
import { adminAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  ChartBarIcon,
  UsersIcon,
  CalendarIcon,
  TrophyIcon,
  DocumentTextIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
  EyeIcon,
  PrinterIcon,
  DocumentArrowDownIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface AdminStats {
  events: number
  contests: number
  categories: number
  users: number
  contestants: number
  judges: number
  scores: number
  activeUsers: number
  totalScores: number
  averageScore: number
  completedCategories: number
  pendingCertifications: number
}

interface ActivityLog {
  id: string
  action: string
  resourceType: string
  resourceId: string
  userId: string
  user: {
    id: string
    name: string
    role: string
  }
  createdAt: string
  details?: any
}

const AdminPage: React.FC = () => {
  const { user } = useAuth()
  const [activeTab, setActiveTab] = useState<'overview' | 'users' | 'events' | 'contests' | 'categories' | 'scores' | 'logs'>('overview')
  const [showExportModal, setShowExportModal] = useState(false)

  const { data: stats, isLoading: statsLoading } = useQuery(
    'admin-stats',
    () => adminAPI.getStats().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: users } = useQuery(
    'admin-users',
    () => adminAPI.getUsers().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: events } = useQuery(
    'admin-events',
    () => adminAPI.getEvents().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: contests } = useQuery(
    'admin-contests',
    () => adminAPI.getContests().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: categories } = useQuery(
    'admin-categories',
    () => adminAPI.getCategories().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: scores } = useQuery(
    'admin-scores',
    () => adminAPI.getScores().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: activityLogs } = useQuery(
    'admin-activity-logs',
    () => adminAPI.getActivityLogs().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const tabs = [
    { id: 'overview', name: 'Overview', icon: ChartBarIcon },
    { id: 'users', name: 'Users', icon: UsersIcon },
    { id: 'events', name: 'Events', icon: CalendarIcon },
    { id: 'contests', name: 'Contests', icon: TrophyIcon },
    { id: 'categories', name: 'Categories', icon: DocumentTextIcon },
    { id: 'scores', name: 'Scores', icon: ChartBarIcon },
    { id: 'logs', name: 'Activity Logs', icon: ClockIcon },
  ]

  const getRoleColor = (role: string) => {
    switch (role) {
      case 'ORGANIZER': return 'role-organizer'
      case 'BOARD': return 'role-board'
      case 'JUDGE': return 'role-judge'
      case 'CONTESTANT': return 'role-contestant'
      case 'EMCEE': return 'role-emcee'
      case 'TALLY_MASTER': return 'role-tally-master'
      case 'AUDITOR': return 'role-auditor'
      default: return 'role-board'
    }
  }

  const getRoleDisplayName = (role: string) => {
    switch (role) {
      case 'ORGANIZER': return 'Organizer'
      case 'BOARD': return 'Board'
      case 'JUDGE': return 'Judge'
      case 'CONTESTANT': return 'Contestant'
      case 'EMCEE': return 'Emcee'
      case 'TALLY_MASTER': return 'Tally Master'
      case 'AUDITOR': return 'Auditor'
      default: return role
    }
  }

  if (statsLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="loading-spinner"></div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Admin Dashboard</h1>
          <p className="text-gray-600 dark:text-gray-400">
            System administration and monitoring
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowExportModal(true)}
            className="btn btn-outline"
          >
            <DocumentArrowDownIcon className="h-5 w-5 mr-2" />
            Export Data
          </button>
        </div>
      </div>

      {/* Tabs */}
      <div className="card">
        <div className="card-content p-0">
          <div className="border-b border-gray-200 dark:border-gray-700">
            <nav className="flex space-x-8 px-6">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id as any)}
                  className={`py-4 px-1 border-b-2 font-medium text-sm ${
                    activeTab === tab.id
                      ? 'border-primary text-primary'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  <tab.icon className="h-5 w-5 inline mr-2" />
                  {tab.name}
                </button>
              ))}
            </nav>
          </div>
        </div>
      </div>

      {/* Tab Content */}
      <div className="card">
        <div className="card-content">
          {activeTab === 'overview' && (
            <div className="space-y-6">
              {/* System Stats */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                <div className="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <CalendarIcon className="h-8 w-8 text-blue-600 dark:text-blue-400" />
                    </div>
                    <div className="ml-3">
                      <p className="text-sm font-medium text-blue-600 dark:text-blue-400">Events</p>
                      <p className="text-2xl font-semibold text-blue-900 dark:text-blue-100">{stats?.events || 0}</p>
                    </div>
                  </div>
                </div>
                <div className="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <TrophyIcon className="h-8 w-8 text-green-600 dark:text-green-400" />
                    </div>
                    <div className="ml-3">
                      <p className="text-sm font-medium text-green-600 dark:text-green-400">Contests</p>
                      <p className="text-2xl font-semibold text-green-900 dark:text-green-100">{stats?.contests || 0}</p>
                    </div>
                  </div>
                </div>
                <div className="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <UsersIcon className="h-8 w-8 text-yellow-600 dark:text-yellow-400" />
                    </div>
                    <div className="ml-3">
                      <p className="text-sm font-medium text-yellow-600 dark:text-yellow-400">Users</p>
                      <p className="text-2xl font-semibold text-yellow-900 dark:text-yellow-100">{stats?.users || 0}</p>
                    </div>
                  </div>
                </div>
                <div className="bg-purple-50 dark:bg-purple-900 p-4 rounded-lg">
                  <div className="flex items-center">
                    <div className="flex-shrink-0">
                      <ChartBarIcon className="h-8 w-8 text-purple-600 dark:text-purple-400" />
                    </div>
                    <div className="ml-3">
                      <p className="text-sm font-medium text-purple-600 dark:text-purple-400">Scores</p>
                      <p className="text-2xl font-semibold text-purple-900 dark:text-purple-100">{stats?.scores || 0}</p>
                    </div>
                  </div>
                </div>
              </div>

              {/* Performance Metrics */}
              <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                <div className="card">
                  <div className="card-content">
                    <h3 className="card-title text-lg">Active Users</h3>
                    <div className="text-3xl font-bold text-primary mb-2">{stats?.activeUsers || 0}</div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">Currently online</p>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <h3 className="card-title text-lg">Average Score</h3>
                    <div className="text-3xl font-bold text-primary mb-2">
                      {stats?.averageScore ? stats.averageScore.toFixed(1) : '0.0'}
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">Across all categories</p>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <h3 className="card-title text-lg">Completed Categories</h3>
                    <div className="text-3xl font-bold text-primary mb-2">{stats?.completedCategories || 0}</div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">Fully scored</p>
                  </div>
                </div>
              </div>

              {/* Recent Activity */}
              <div className="card">
                <div className="card-header">
                  <h3 className="card-title">Recent Activity</h3>
                </div>
                <div className="card-content">
                  {activityLogs && activityLogs.length > 0 ? (
                    <div className="space-y-3">
                      {activityLogs.slice(0, 5).map((log: ActivityLog) => (
                        <div key={log.id} className="flex items-center space-x-3 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                          <div className="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-medium">
                            {log.user.name.charAt(0).toUpperCase()}
                          </div>
                          <div className="flex-1 min-w-0">
                            <p className="text-sm font-medium text-gray-900 dark:text-white">
                              {log.user.name} {log.action} {log.resourceType}
                            </p>
                            <p className="text-xs text-gray-500 dark:text-gray-400">
                              {format(new Date(log.createdAt), 'MMM dd, yyyy HH:mm')}
                            </p>
                          </div>
                          <span className={`role-badge ${getRoleColor(log.user.role)}`}>
                            {getRoleDisplayName(log.user.role)}
                          </span>
                        </div>
                      ))}
                    </div>
                  ) : (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <ClockIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                      <p>No recent activity</p>
                    </div>
                  )}
                </div>
              </div>
            </div>
          )}

          {activeTab === 'users' && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">User Management</h3>
                <button className="btn btn-primary btn-sm">
                  <UsersIcon className="h-4 w-4 mr-2" />
                  Add User
                </button>
              </div>
              {users && users.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="table">
                    <thead className="table-header">
                      <tr>
                        <th className="table-head">Name</th>
                        <th className="table-head">Email</th>
                        <th className="table-head">Role</th>
                        <th className="table-head">Status</th>
                        <th className="table-head">Last Login</th>
                        <th className="table-head">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="table-body">
                      {users.map((user: any) => (
                        <tr key={user.id} className="table-row">
                          <td className="table-cell">{user.preferredName || user.name}</td>
                          <td className="table-cell">{user.email}</td>
                          <td className="table-cell">
                            <span className={`role-badge ${getRoleColor(user.role)}`}>
                              {getRoleDisplayName(user.role)}
                            </span>
                          </td>
                          <td className="table-cell">
                            <span className={`status-indicator ${user.isActive ? 'status-online' : 'status-offline'}`}>
                              {user.isActive ? 'Active' : 'Inactive'}
                            </span>
                          </td>
                          <td className="table-cell">
                            {user.lastLoginAt
                              ? format(new Date(user.lastLoginAt), 'MMM dd, yyyy')
                              : 'Never'}
                          </td>
                          <td className="table-cell">
                            <button className="btn btn-outline btn-sm">
                              <EyeIcon className="h-4 w-4" />
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <UsersIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No users found</p>
                </div>
              )}
            </div>
          )}

          {activeTab === 'events' && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Event Management</h3>
                <button className="btn btn-primary btn-sm">
                  <CalendarIcon className="h-4 w-4 mr-2" />
                  Add Event
                </button>
              </div>
              {events && events.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {events.map((event: any) => (
                    <div key={event.id} className="card">
                      <div className="card-content">
                        <h4 className="font-medium text-gray-900 dark:text-white mb-2">{event.name}</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">{event.description}</p>
                        <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                          <span>{event._count?.contests || 0} contests</span>
                          <span>{format(new Date(event.createdAt), 'MMM dd, yyyy')}</span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <CalendarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No events found</p>
                </div>
              )}
            </div>
          )}

          {activeTab === 'contests' && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Contest Management</h3>
                <button className="btn btn-primary btn-sm">
                  <TrophyIcon className="h-4 w-4 mr-2" />
                  Add Contest
                </button>
              </div>
              {contests && contests.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {contests.map((contest: any) => (
                    <div key={contest.id} className="card">
                      <div className="card-content">
                        <h4 className="font-medium text-gray-900 dark:text-white mb-2">{contest.name}</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">{contest.description}</p>
                        <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                          <span>{contest._count?.categories || 0} categories</span>
                          <span>{format(new Date(contest.createdAt), 'MMM dd, yyyy')}</span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <TrophyIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No contests found</p>
                </div>
              )}
            </div>
          )}

          {activeTab === 'categories' && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Category Management</h3>
                <button className="btn btn-primary btn-sm">
                  <DocumentTextIcon className="h-4 w-4 mr-2" />
                  Add Category
                </button>
              </div>
              {categories && categories.length > 0 ? (
                <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                  {categories.map((category: any) => (
                    <div key={category.id} className="card">
                      <div className="card-content">
                        <h4 className="font-medium text-gray-900 dark:text-white mb-2">{category.name}</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">{category.description}</p>
                        <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                          <span>Max Score: {category.maxScore}</span>
                          <span>{format(new Date(category.createdAt), 'MMM dd, yyyy')}</span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No categories found</p>
                </div>
              )}
            </div>
          )}

          {activeTab === 'scores' && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Score Management</h3>
                <button className="btn btn-outline btn-sm">
                  <PrinterIcon className="h-4 w-4 mr-2" />
                  Export Scores
                </button>
              </div>
              {scores && scores.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="table">
                    <thead className="table-header">
                      <tr>
                        <th className="table-head">Contestant</th>
                        <th className="table-head">Category</th>
                        <th className="table-head">Judge</th>
                        <th className="table-head">Score</th>
                        <th className="table-head">Date</th>
                        <th className="table-head">Actions</th>
                      </tr>
                    </thead>
                    <tbody className="table-body">
                      {scores.slice(0, 10).map((score: any) => (
                        <tr key={score.id} className="table-row">
                          <td className="table-cell">{score.contestant.name}</td>
                          <td className="table-cell">{score.category.name}</td>
                          <td className="table-cell">{score.judge.name}</td>
                          <td className="table-cell">
                            <span className="font-medium text-primary">{score.score}</span>
                          </td>
                          <td className="table-cell">
                            {format(new Date(score.createdAt), 'MMM dd, yyyy')}
                          </td>
                          <td className="table-cell">
                            <button className="btn btn-outline btn-sm">
                              <EyeIcon className="h-4 w-4" />
                            </button>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <ChartBarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No scores found</p>
                </div>
              )}
            </div>
          )}

          {activeTab === 'logs' && (
            <div className="space-y-4">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Activity Logs</h3>
                <button className="btn btn-outline btn-sm">
                  <DocumentArrowDownIcon className="h-4 w-4 mr-2" />
                  Export Logs
                </button>
              </div>
              {activityLogs && activityLogs.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="table">
                    <thead className="table-header">
                      <tr>
                        <th className="table-head">User</th>
                        <th className="table-head">Action</th>
                        <th className="table-head">Resource</th>
                        <th className="table-head">Date</th>
                        <th className="table-head">Details</th>
                      </tr>
                    </thead>
                    <tbody className="table-body">
                      {activityLogs.map((log: ActivityLog) => (
                        <tr key={log.id} className="table-row">
                          <td className="table-cell">
                            <div className="flex items-center space-x-2">
                              <div className="w-6 h-6 bg-primary rounded-full flex items-center justify-center text-white text-xs font-medium">
                                {log.user.name.charAt(0).toUpperCase()}
                              </div>
                              <span>{log.user.name}</span>
                            </div>
                          </td>
                          <td className="table-cell">{log.action}</td>
                          <td className="table-cell">{log.resourceType}</td>
                          <td className="table-cell">
                            {format(new Date(log.createdAt), 'MMM dd, yyyy HH:mm')}
                          </td>
                          <td className="table-cell">
                            {log.details && (
                              <button className="btn btn-outline btn-sm">
                                <EyeIcon className="h-4 w-4" />
                              </button>
                            )}
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <ClockIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No activity logs found</p>
                </div>
              )}
            </div>
          )}
        </div>
      </div>

      {/* Export Modal */}
      {showExportModal && (
        <ExportModal
          onClose={() => setShowExportModal(false)}
          onExport={(type) => {
            // Handle export
            setShowExportModal(false)
          }}
        />
      )}
    </div>
  )
}

// Export Modal Component
interface ExportModalProps {
  onClose: () => void
  onExport: (type: string) => void
}

const ExportModal: React.FC<ExportModalProps> = ({ onClose, onExport }) => {
  const [exportType, setExportType] = useState('all')

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Export Data</h2>
        <div className="space-y-4">
          <div>
            <label className="label">Export Type</label>
            <select
              value={exportType}
              onChange={(e) => setExportType(e.target.value)}
              className="input"
            >
              <option value="all">All Data</option>
              <option value="users">Users</option>
              <option value="events">Events</option>
              <option value="contests">Contests</option>
              <option value="categories">Categories</option>
              <option value="scores">Scores</option>
              <option value="logs">Activity Logs</option>
            </select>
          </div>
          <div>
            <label className="label">Format</label>
            <select className="input">
              <option value="csv">CSV</option>
              <option value="excel">Excel</option>
              <option value="json">JSON</option>
            </select>
          </div>
        </div>
        <div className="flex justify-end space-x-3 pt-4">
          <button
            onClick={onClose}
            className="btn btn-outline"
          >
            Cancel
          </button>
          <button
            onClick={() => onExport(exportType)}
            className="btn btn-primary"
          >
            Export
          </button>
        </div>
      </div>
    </div>
  )
}

export default AdminPage
