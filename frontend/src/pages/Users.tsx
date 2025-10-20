import React from 'react'
import { Link } from 'react-router-dom'
import { useUsers, useContestants, useJudges } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  PlusIcon,
  UsersIcon,
  TrophyIcon,
  UserIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'

const Users: React.FC = () => {
  const { user } = useAuth()
  const [activeTab, setActiveTab] = React.useState<'all' | 'contestants' | 'judges'>('all')
  const [searchTerm, setSearchTerm] = React.useState('')
  
  const { data: usersData, isLoading: usersLoading } = useUsers({
    search: searchTerm || undefined,
    limit: 20
  })
  
  const { data: contestantsData, isLoading: contestantsLoading } = useContestants({
    search: searchTerm || undefined,
    limit: 20
  })
  
  const { data: judgesData, isLoading: judgesLoading } = useJudges({
    search: searchTerm || undefined,
    limit: 20
  })

  const canManageUsers = user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  const getCurrentData = () => {
    switch (activeTab) {
      case 'contestants':
        return { data: contestantsData, isLoading: contestantsLoading }
      case 'judges':
        return { data: judgesData, isLoading: judgesLoading }
      default:
        return { data: usersData, isLoading: usersLoading }
    }
  }

  const { data, isLoading } = getCurrentData()

  const getRoleBadgeColor = (role: string) => {
    switch (role) {
      case 'ORGANIZER':
        return 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200'
      case 'JUDGE':
        return 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200'
      case 'CONTESTANT':
        return 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200'
      case 'EMCEE':
        return 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-200'
      case 'TALLY_MASTER':
        return 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200'
      case 'AUDITOR':
        return 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-200'
      case 'BOARD':
        return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
      default:
        return 'bg-gray-100 text-gray-800 dark:bg-gray-900 dark:text-gray-200'
    }
  }

  const tabs = [
    { id: 'all', name: 'All Users', count: usersData?.users?.length || 0 },
    { id: 'contestants', name: 'Contestants', count: contestantsData?.contestants?.length || 0 },
    { id: 'judges', name: 'Judges', count: judgesData?.judges?.length || 0 },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Users
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage users, contestants, and judges
          </p>
        </div>
        {canManageUsers && (
          <Link
            to="/users/new"
            className="btn btn-primary btn-md"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            New User
          </Link>
        )}
      </div>

      {/* Tabs */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="border-b border-gray-200 dark:border-gray-700">
          <nav className="-mb-px flex space-x-8 px-6">
            {tabs.map((tab) => (
              <button
                key={tab.id}
                onClick={() => setActiveTab(tab.id as any)}
                className={`py-4 px-1 border-b-2 font-medium text-sm ${
                  activeTab === tab.id
                    ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                    : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
                }`}
              >
                {tab.name}
                <span className={`ml-2 py-0.5 px-2 rounded-full text-xs ${
                  activeTab === tab.id
                    ? 'bg-blue-100 text-blue-600 dark:bg-blue-900 dark:text-blue-200'
                    : 'bg-gray-100 text-gray-600 dark:bg-gray-700 dark:text-gray-300'
                }`}>
                  {tab.count}
                </span>
              </button>
            ))}
          </nav>
        </div>

        {/* Search */}
        <div className="p-6 border-b border-gray-200 dark:border-gray-700">
          <input
            type="text"
            placeholder="Search users..."
            value={searchTerm}
            onChange={(e) => setSearchTerm(e.target.value)}
            className="input w-full"
          />
        </div>

        {/* Content */}
        <div className="p-6">
          {isLoading ? (
            <div className="flex justify-center items-center h-64">
              <LoadingSpinner size="lg" />
            </div>
          ) : (
            <div className="space-y-4">
              {activeTab === 'all' && usersData?.users?.map((user: any) => (
                <div key={user.id} className="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="flex items-center space-x-4">
                    <div className="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                      <span className="text-sm font-medium text-white">
                        {user.preferredName?.charAt(0) || user.name?.charAt(0) || 'U'}
                      </span>
                    </div>
                    <div>
                      <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                        {user.preferredName || user.name}
                      </h3>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        {user.email}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-3">
                    <span className={`badge ${getRoleBadgeColor(user.role)}`}>
                      {user.role.toLowerCase().replace('_', ' ')}
                    </span>
                    <div className="flex items-center space-x-2">
                      <Link
                        to={`/users/${user.id}`}
                        className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                      >
                        <EyeIcon className="h-4 w-4" />
                      </Link>
                      {canManageUsers && (
                        <>
                          <Link
                            to={`/users/${user.id}/edit`}
                            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                          >
                            <PencilIcon className="h-4 w-4" />
                          </Link>
                          <button
                            className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                            onClick={() => {
                              // Handle delete
                            }}
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              ))}

              {activeTab === 'contestants' && contestantsData?.contestants?.map((contestant: any) => (
                <div key={contestant.id} className="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="flex items-center space-x-4">
                    <div className="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                      <span className="text-sm font-medium text-white">
                        {contestant.name?.charAt(0) || 'C'}
                      </span>
                    </div>
                    <div>
                      <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                        {contestant.name}
                      </h3>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        {contestant.email}
                        {contestant.contestantNumber && ` • #${contestant.contestantNumber}`}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-3">
                    <span className="badge badge-default">
                      Contestant
                    </span>
                    <div className="flex items-center space-x-2">
                      <Link
                        to={`/contestants/${contestant.id}`}
                        className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                      >
                        <EyeIcon className="h-4 w-4" />
                      </Link>
                      {canManageUsers && (
                        <>
                          <Link
                            to={`/contestants/${contestant.id}/edit`}
                            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                          >
                            <PencilIcon className="h-4 w-4" />
                          </Link>
                          <button
                            className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                            onClick={() => {
                              // Handle delete
                            }}
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              ))}

              {activeTab === 'judges' && judgesData?.judges?.map((judge: any) => (
                <div key={judge.id} className="flex items-center justify-between p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="flex items-center space-x-4">
                    <div className="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center">
                      <span className="text-sm font-medium text-white">
                        {judge.name?.charAt(0) || 'J'}
                      </span>
                    </div>
                    <div>
                      <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                        {judge.name}
                        {judge.isHeadJudge && (
                          <span className="ml-2 text-xs text-green-600 dark:text-green-400">
                            (Head Judge)
                          </span>
                        )}
                      </h3>
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        {judge.email}
                      </p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-3">
                    <span className="badge badge-secondary">
                      Judge
                    </span>
                    <div className="flex items-center space-x-2">
                      <Link
                        to={`/judges/${judge.id}`}
                        className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                      >
                        <EyeIcon className="h-4 w-4" />
                      </Link>
                      {canManageUsers && (
                        <>
                          <Link
                            to={`/judges/${judge.id}/edit`}
                            className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                          >
                            <PencilIcon className="h-4 w-4" />
                          </Link>
                          <button
                            className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                            onClick={() => {
                              // Handle delete
                            }}
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </>
                      )}
                    </div>
                  </div>
                </div>
              ))}

              {(!data || (activeTab === 'all' && !usersData?.users?.length) || 
                (activeTab === 'contestants' && !contestantsData?.contestants?.length) || 
                (activeTab === 'judges' && !judgesData?.judges?.length)) && (
                <div className="text-center py-12">
                  <UsersIcon className="mx-auto h-12 w-12 text-gray-400" />
                  <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                    No users found
                  </h3>
                  <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                    {searchTerm ? 'Try adjusting your search terms.' : 'Get started by creating a new user.'}
                  </p>
                  {canManageUsers && !searchTerm && (
                    <div className="mt-6">
                      <Link
                        to="/users/new"
                        className="btn btn-primary btn-md"
                      >
                        <PlusIcon className="h-5 w-5 mr-2" />
                        New User
                      </Link>
                    </div>
                  )}
                </div>
              )}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default Users
