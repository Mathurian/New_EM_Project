import React, { useState } from 'react'
import { useSocket } from '../contexts/SocketContext'
import { useAuth } from '../contexts/AuthContext'
import {
  UsersIcon,
  EyeIcon,
  XMarkIcon,
  UserIcon,
  ClockIcon,
  CheckCircleIcon,
} from '@heroicons/react/24/outline'
import { format, formatDistanceToNow } from 'date-fns'

const ActiveUsers: React.FC = () => {
  const { activeUsers } = useSocket()
  const { user } = useAuth()
  const [isOpen, setIsOpen] = useState(false)

  const onlineUsers = activeUsers.filter(u => u.isOnline)
  const offlineUsers = activeUsers.filter(u => !u.isOnline)

  const getRoleColor = (role: string) => {
    switch (role) {
      case 'ORGANIZER':
        return 'bg-purple-500'
      case 'BOARD':
        return 'bg-indigo-500'
      case 'JUDGE':
        return 'bg-green-500'
      case 'CONTESTANT':
        return 'bg-yellow-500'
      case 'EMCEE':
        return 'bg-pink-500'
      case 'TALLY_MASTER':
        return 'bg-blue-500'
      case 'AUDITOR':
        return 'bg-red-500'
      default:
        return 'bg-gray-500'
    }
  }

  const getRoleBadge = (role: string) => {
    switch (role) {
      case 'ORGANIZER':
        return 'badge-purple'
      case 'BOARD':
        return 'badge-indigo'
      case 'JUDGE':
        return 'badge-green'
      case 'CONTESTANT':
        return 'badge-yellow'
      case 'EMCEE':
        return 'badge-pink'
      case 'TALLY_MASTER':
        return 'badge-blue'
      case 'AUDITOR':
        return 'badge-red'
      default:
        return 'badge-gray'
    }
  }

  return (
    <div className="relative">
      {/* Active Users Button */}
      <button
        onClick={() => setIsOpen(!isOpen)}
        className="relative p-2 text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white focus:outline-none focus:ring-2 focus:ring-primary rounded-md"
      >
        <UsersIcon className="h-6 w-6" />
        {onlineUsers.length > 0 && (
          <span className="absolute -top-1 -right-1 h-5 w-5 bg-green-500 text-white text-xs rounded-full flex items-center justify-center">
            {onlineUsers.length}
          </span>
        )}
      </button>

      {/* Active Users Dropdown */}
      {isOpen && (
        <>
          <div
            className="fixed inset-0 z-10"
            onClick={() => setIsOpen(false)}
          />
          <div className="absolute right-0 mt-2 w-80 bg-white dark:bg-gray-800 rounded-lg shadow-lg border border-gray-200 dark:border-gray-700 z-20">
            {/* Header */}
            <div className="px-4 py-3 border-b border-gray-200 dark:border-gray-700">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                  Active Users
                </h3>
                <button
                  onClick={() => setIsOpen(false)}
                  className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                >
                  <XMarkIcon className="h-5 w-5" />
                </button>
              </div>
              <div className="mt-2 flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                <div className="flex items-center space-x-1">
                  <CheckCircleIcon className="h-4 w-4 text-green-500" />
                  <span>{onlineUsers.length} online</span>
                </div>
                <div className="flex items-center space-x-1">
                  <ClockIcon className="h-4 w-4 text-gray-500" />
                  <span>{offlineUsers.length} offline</span>
                </div>
              </div>
            </div>

            {/* Users List */}
            <div className="max-h-96 overflow-y-auto">
              {/* Online Users */}
              {onlineUsers.length > 0 && (
                <div className="px-4 py-2">
                  <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                    Online ({onlineUsers.length})
                  </h4>
                  <div className="space-y-2">
                    {onlineUsers.map((activeUser) => (
                      <div
                        key={activeUser.id}
                        className="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                      >
                        <div className="relative">
                          <div className={`w-8 h-8 ${getRoleColor(activeUser.role)} rounded-full flex items-center justify-center`}>
                            <UserIcon className="h-5 w-5 text-white" />
                          </div>
                          <div className="absolute -bottom-1 -right-1 w-3 h-3 bg-green-500 border-2 border-white dark:border-gray-800 rounded-full"></div>
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {activeUser.name}
                            {activeUser.id === user?.id && ' (You)'}
                          </p>
                          <div className="flex items-center space-x-2">
                            <span className={`badge ${getRoleBadge(activeUser.role)} badge-sm`}>
                              {activeUser.role.replace('_', ' ')}
                            </span>
                            <span className="text-xs text-gray-500 dark:text-gray-400">
                              Active now
                            </span>
                          </div>
                        </div>
                        <button className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                          <EyeIcon className="h-4 w-4" />
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {/* Offline Users */}
              {offlineUsers.length > 0 && (
                <div className="px-4 py-2 border-t border-gray-200 dark:border-gray-700">
                  <h4 className="text-sm font-medium text-gray-900 dark:text-white mb-2">
                    Recently Active ({offlineUsers.length})
                  </h4>
                  <div className="space-y-2">
                    {offlineUsers.slice(0, 10).map((activeUser) => (
                      <div
                        key={activeUser.id}
                        className="flex items-center space-x-3 p-2 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700"
                      >
                        <div className="relative">
                          <div className={`w-8 h-8 ${getRoleColor(activeUser.role)} rounded-full flex items-center justify-center opacity-60`}>
                            <UserIcon className="h-5 w-5 text-white" />
                          </div>
                          <div className="absolute -bottom-1 -right-1 w-3 h-3 bg-gray-400 border-2 border-white dark:border-gray-800 rounded-full"></div>
                        </div>
                        <div className="flex-1 min-w-0">
                          <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                            {activeUser.name}
                            {activeUser.id === user?.id && ' (You)'}
                          </p>
                          <div className="flex items-center space-x-2">
                            <span className={`badge ${getRoleBadge(activeUser.role)} badge-sm opacity-60`}>
                              {activeUser.role.replace('_', ' ')}
                            </span>
                            <span className="text-xs text-gray-500 dark:text-gray-400">
                              {formatDistanceToNow(new Date(activeUser.lastSeen), { addSuffix: true })}
                            </span>
                          </div>
                        </div>
                        <button className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                          <EyeIcon className="h-4 w-4" />
                        </button>
                      </div>
                    ))}
                  </div>
                </div>
              )}

              {activeUsers.length === 0 && (
                <div className="px-4 py-8 text-center text-gray-500 dark:text-gray-400">
                  <UsersIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No active users</p>
                </div>
              )}
            </div>

            {/* Footer */}
            <div className="px-4 py-3 border-t border-gray-200 dark:border-gray-700">
              <div className="text-xs text-gray-500 dark:text-gray-400 text-center">
                Real-time updates enabled
              </div>
            </div>
          </div>
        </>
      )}
    </div>
  )
}

export default ActiveUsers
