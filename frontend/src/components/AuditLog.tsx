import React, { useState } from 'react'
import { useQuery } from 'react-query'
import { adminAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  ClockIcon,
  UserIcon,
  DocumentTextIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  CalendarIcon,
  ArrowDownTrayIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  InformationCircleIcon,
  XCircleIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  TrophyIcon,
  StarIcon,
  CogIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface AuditLogEntry {
  id: string
  userId: string
  userName: string
  userRole: string
  action: string
  entityType: 'USER' | 'EVENT' | 'CONTEST' | 'CATEGORY' | 'SCORE' | 'CERTIFICATION' | 'SYSTEM'
  entityId: string
  entityName: string
  oldValues?: Record<string, any>
  newValues?: Record<string, any>
  ipAddress: string
  userAgent: string
  timestamp: string
  severity: 'LOW' | 'MEDIUM' | 'HIGH' | 'CRITICAL'
  description: string
}

const AuditLog: React.FC = () => {
  const { user } = useAuth()
  const [searchTerm, setSearchTerm] = useState('')
  const [userFilter, setUserFilter] = useState('')
  const [actionFilter, setActionFilter] = useState('')
  const [entityFilter, setEntityFilter] = useState('')
  const [severityFilter, setSeverityFilter] = useState('')
  const [dateRange, setDateRange] = useState('')
  const [selectedEntry, setSelectedEntry] = useState<AuditLogEntry | null>(null)
  const [showDetails, setShowDetails] = useState(false)

  // Fetch audit logs
  const { data: auditLogs, isLoading: logsLoading } = useQuery(
    ['audit-logs', searchTerm, userFilter, actionFilter, entityFilter, severityFilter, dateRange],
    () => adminAPI.getAuditLogs({
      search: searchTerm,
      userId: userFilter,
      action: actionFilter,
      entityType: entityFilter,
      severity: severityFilter,
      dateRange: dateRange,
    }).then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'AUDITOR',
      refetchInterval: 30000, // Refresh every 30 seconds
    }
  )

  // Fetch users for filtering
  const { data: users } = useQuery(
    'users-for-audit',
    () => api.get('/users').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'AUDITOR',
    }
  )

  const filteredLogs = auditLogs || []

  const getSeverityColor = (severity: string) => {
    switch (severity) {
      case 'LOW': return 'badge-gray'
      case 'MEDIUM': return 'badge-yellow'
      case 'HIGH': return 'badge-orange'
      case 'CRITICAL': return 'badge-red'
      default: return 'badge-gray'
    }
  }

  const getSeverityIcon = (severity: string) => {
    switch (severity) {
      case 'LOW': return <InformationCircleIcon className="h-4 w-4 text-gray-500" />
      case 'MEDIUM': return <ExclamationTriangleIcon className="h-4 w-4 text-yellow-500" />
      case 'HIGH': return <ExclamationTriangleIcon className="h-4 w-4 text-orange-500" />
      case 'CRITICAL': return <XCircleIcon className="h-4 w-4 text-red-500" />
      default: return <InformationCircleIcon className="h-4 w-4 text-gray-500" />
    }
  }

  const getActionIcon = (action: string) => {
    switch (action.toLowerCase()) {
      case 'create': return <PlusIcon className="h-4 w-4 text-green-500" />
      case 'update': return <PencilIcon className="h-4 w-4 text-blue-500" />
      case 'delete': return <TrashIcon className="h-4 w-4 text-red-500" />
      case 'login': return <CheckCircleIcon className="h-4 w-4 text-green-500" />
      case 'logout': return <XCircleIcon className="h-4 w-4 text-gray-500" />
      default: return <DocumentTextIcon className="h-4 w-4 text-gray-500" />
    }
  }

  const getEntityIcon = (entityType: string) => {
    switch (entityType) {
      case 'USER': return <UserIcon className="h-4 w-4" />
      case 'EVENT': return <CalendarIcon className="h-4 w-4" />
      case 'CONTEST': return <TrophyIcon className="h-4 w-4" />
      case 'CATEGORY': return <DocumentTextIcon className="h-4 w-4" />
      case 'SCORE': return <StarIcon className="h-4 w-4" />
      case 'CERTIFICATION': return <CheckCircleIcon className="h-4 w-4" />
      case 'SYSTEM': return <CogIcon className="h-4 w-4" />
      default: return <DocumentTextIcon className="h-4 w-4" />
    }
  }

  const exportLogs = async () => {
    try {
      const response = await adminAPI.exportAuditLogs({
        search: searchTerm,
        userId: userFilter,
        action: actionFilter,
        entityType: entityFilter,
        severity: severityFilter,
        dateRange: dateRange,
      })
      
      const blob = new Blob([response.data], { type: 'text/csv' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `audit_logs_${format(new Date(), 'yyyy-MM-dd_HH-mm')}.csv`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (error) {
      console.error('Error exporting audit logs:', error)
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Audit Log</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Comprehensive activity logging and audit trail system
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={exportLogs}
            className="btn btn-outline"
          >
            <ArrowDownTrayIcon className="h-5 w-5 mr-2" />
            Export Logs
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="card">
        <div className="card-content">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-6 gap-4">
            <div>
              <label className="label">Search</label>
              <div className="relative">
                <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search logs..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="input pl-10"
                />
              </div>
            </div>
            <div>
              <label className="label">User</label>
              <select
                value={userFilter}
                onChange={(e) => setUserFilter(e.target.value)}
                className="input"
              >
                <option value="">All Users</option>
                {users?.map((user) => (
                  <option key={user.id} value={user.id}>
                    {user.name}
                  </option>
                ))}
              </select>
            </div>
            <div>
              <label className="label">Action</label>
              <select
                value={actionFilter}
                onChange={(e) => setActionFilter(e.target.value)}
                className="input"
              >
                <option value="">All Actions</option>
                <option value="CREATE">Create</option>
                <option value="UPDATE">Update</option>
                <option value="DELETE">Delete</option>
                <option value="LOGIN">Login</option>
                <option value="LOGOUT">Logout</option>
                <option value="EXPORT">Export</option>
                <option value="IMPORT">Import</option>
              </select>
            </div>
            <div>
              <label className="label">Entity Type</label>
              <select
                value={entityFilter}
                onChange={(e) => setEntityFilter(e.target.value)}
                className="input"
              >
                <option value="">All Entities</option>
                <option value="USER">User</option>
                <option value="EVENT">Event</option>
                <option value="CONTEST">Contest</option>
                <option value="CATEGORY">Category</option>
                <option value="SCORE">Score</option>
                <option value="CERTIFICATION">Certification</option>
                <option value="SYSTEM">System</option>
              </select>
            </div>
            <div>
              <label className="label">Severity</label>
              <select
                value={severityFilter}
                onChange={(e) => setSeverityFilter(e.target.value)}
                className="input"
              >
                <option value="">All Severities</option>
                <option value="LOW">Low</option>
                <option value="MEDIUM">Medium</option>
                <option value="HIGH">High</option>
                <option value="CRITICAL">Critical</option>
              </select>
            </div>
            <div>
              <label className="label">Date Range</label>
              <select
                value={dateRange}
                onChange={(e) => setDateRange(e.target.value)}
                className="input"
              >
                <option value="">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="quarter">This Quarter</option>
                <option value="year">This Year</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Audit Logs Table */}
      <div className="card">
        <div className="card-content p-0">
          {logsLoading ? (
            <div className="flex items-center justify-center py-8">
              <div className="loading-spinner"></div>
            </div>
          ) : filteredLogs.length === 0 ? (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
              <ClockIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                No audit logs found
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                {searchTerm || userFilter || actionFilter || entityFilter || severityFilter || dateRange
                  ? 'Try adjusting your search criteria'
                  : 'No audit logs have been recorded yet'}
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>Timestamp</th>
                    <th>User</th>
                    <th>Action</th>
                    <th>Entity</th>
                    <th>Severity</th>
                    <th>IP Address</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredLogs.map((log) => (
                    <tr key={log.id}>
                      <td className="text-gray-600 dark:text-gray-400">
                        <div className="flex items-center space-x-2">
                          <ClockIcon className="h-4 w-4" />
                          <span>{format(new Date(log.timestamp), 'MMM dd, yyyy HH:mm:ss')}</span>
                        </div>
                      </td>
                      <td>
                        <div className="flex items-center space-x-2">
                          <UserIcon className="h-4 w-4 text-gray-400" />
                          <div>
                            <div className="font-medium text-gray-900 dark:text-white">
                              {log.userName}
                            </div>
                            <div className="text-sm text-gray-600 dark:text-gray-400">
                              {log.userRole}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div className="flex items-center space-x-2">
                          {getActionIcon(log.action)}
                          <span className="font-medium text-gray-900 dark:text-white">
                            {log.action}
                          </span>
                        </div>
                      </td>
                      <td>
                        <div className="flex items-center space-x-2">
                          {getEntityIcon(log.entityType)}
                          <div>
                            <div className="font-medium text-gray-900 dark:text-white">
                              {log.entityName}
                            </div>
                            <div className="text-sm text-gray-600 dark:text-gray-400">
                              {log.entityType}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td>
                        <div className="flex items-center space-x-2">
                          {getSeverityIcon(log.severity)}
                          <span className={`badge ${getSeverityColor(log.severity)}`}>
                            {log.severity}
                          </span>
                        </div>
                      </td>
                      <td className="text-gray-600 dark:text-gray-400">
                        {log.ipAddress}
                      </td>
                      <td>
                        <button
                          onClick={() => {
                            setSelectedEntry(log)
                            setShowDetails(true)
                          }}
                          className="btn btn-outline btn-sm"
                        >
                          <EyeIcon className="h-4 w-4" />
                        </button>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Details Modal */}
      {showDetails && selectedEntry && (
        <AuditLogDetailsModal
          entry={selectedEntry}
          onClose={() => {
            setShowDetails(false)
            setSelectedEntry(null)
          }}
        />
      )}
    </div>
  )
}

// Audit Log Details Modal Component
interface AuditLogDetailsModalProps {
  entry: AuditLogEntry
  onClose: () => void
}

const AuditLogDetailsModal: React.FC<AuditLogDetailsModalProps> = ({ entry, onClose }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-4xl">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
            Audit Log Details
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XMarkIcon className="h-6 w-6" />
          </button>
        </div>

        <div className="space-y-6">
          {/* Basic Information */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Timestamp</label>
              <p className="text-gray-900 dark:text-white">
                {format(new Date(entry.timestamp), 'MMMM dd, yyyy HH:mm:ss')}
              </p>
            </div>
            <div>
              <label className="label">Severity</label>
              <span className={`badge ${getSeverityColor(entry.severity)}`}>
                {entry.severity}
              </span>
            </div>
            <div>
              <label className="label">User</label>
              <p className="text-gray-900 dark:text-white">
                {entry.userName} ({entry.userRole})
              </p>
            </div>
            <div>
              <label className="label">Action</label>
              <p className="text-gray-900 dark:text-white">{entry.action}</p>
            </div>
            <div>
              <label className="label">Entity</label>
              <p className="text-gray-900 dark:text-white">
                {entry.entityName} ({entry.entityType})
              </p>
            </div>
            <div>
              <label className="label">IP Address</label>
              <p className="text-gray-900 dark:text-white">{entry.ipAddress}</p>
            </div>
          </div>

          {/* Description */}
          <div>
            <label className="label">Description</label>
            <p className="text-gray-900 dark:text-white">{entry.description}</p>
          </div>

          {/* User Agent */}
          <div>
            <label className="label">User Agent</label>
            <p className="text-gray-900 dark:text-white text-sm break-all">
              {entry.userAgent}
            </p>
          </div>

          {/* Changes */}
          {(entry.oldValues || entry.newValues) && (
            <div>
              <label className="label">Changes</label>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                {entry.oldValues && (
                  <div>
                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">Old Values</h4>
                    <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                      <pre className="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">
                        {JSON.stringify(entry.oldValues, null, 2)}
                      </pre>
                    </div>
                  </div>
                )}
                {entry.newValues && (
                  <div>
                    <h4 className="font-medium text-gray-900 dark:text-white mb-2">New Values</h4>
                    <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                      <pre className="text-sm text-gray-900 dark:text-white whitespace-pre-wrap">
                        {JSON.stringify(entry.newValues, null, 2)}
                      </pre>
                    </div>
                  </div>
                )}
              </div>
            </div>
          )}
        </div>

        <div className="flex justify-end space-x-3 pt-6">
          <button onClick={onClose} className="btn btn-outline">
            Close
          </button>
        </div>
      </div>
    </div>
  )
}

export default AuditLog
