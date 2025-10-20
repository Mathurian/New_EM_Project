import React from 'react'
import { Link } from 'react-router-dom'
import { useContests } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  PlusIcon,
  TrophyIcon,
  TagIcon,
  UsersIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'

const Contests: React.FC = () => {
  const { user } = useAuth()
  const [searchTerm, setSearchTerm] = React.useState('')
  const [selectedEventId, setSelectedEventId] = React.useState('')
  
  // This would need to be updated to get events for the dropdown
  const { data: contestsData, isLoading } = useContests(selectedEventId, {
    search: searchTerm || undefined,
    limit: 20
  })

  const contests = contestsData?.contests || []

  const canManageContests = user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Contests
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage contests and their categories
          </p>
        </div>
        {canManageContests && (
          <Link
            to="/contests/new"
            className="btn btn-primary btn-md"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            New Contest
          </Link>
        )}
      </div>

      {/* Filters */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <input
              type="text"
              placeholder="Search contests..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input w-full"
            />
          </div>
          <div className="flex-1">
            <select
              value={selectedEventId}
              onChange={(e) => setSelectedEventId(e.target.value)}
              className="input w-full"
            >
              <option value="">All Events</option>
              {/* This would be populated with events */}
            </select>
          </div>
        </div>
      </div>

      {/* Contests List */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        {isLoading ? (
          <div className="flex justify-center items-center h-64">
            <LoadingSpinner size="lg" />
          </div>
        ) : contests.length === 0 ? (
          <div className="text-center py-12">
            <TrophyIcon className="mx-auto h-12 w-12 text-gray-400" />
            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              No contests found
            </h3>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Get started by creating a new contest.
            </p>
            {canManageContests && (
              <div className="mt-6">
                <Link
                  to="/contests/new"
                  className="btn btn-primary btn-md"
                >
                  <PlusIcon className="h-5 w-5 mr-2" />
                  New Contest
                </Link>
              </div>
            )}
          </div>
        ) : (
          <div className="divide-y divide-gray-200 dark:divide-gray-700">
            {contests.map((contest: any) => (
              <div key={contest.id} className="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3">
                      <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                        {contest.name}
                      </h3>
                      {contest.event && (
                        <span className="badge badge-outline">
                          {contest.event.name}
                        </span>
                      )}
                    </div>
                    {contest.description && (
                      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {contest.description}
                      </p>
                    )}
                    <div className="mt-2 flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                      <div className="flex items-center">
                        <TagIcon className="h-4 w-4 mr-1" />
                        {contest._count?.categories || 0} categories
                      </div>
                      <div className="flex items-center">
                        <UsersIcon className="h-4 w-4 mr-1" />
                        {contest._count?.contestants || 0} contestants
                      </div>
                      <div className="flex items-center">
                        <TrophyIcon className="h-4 w-4 mr-1" />
                        {contest._count?.judges || 0} judges
                      </div>
                    </div>
                    <div className="mt-3">
                      <Link
                        to={`/contests/${contest.id}`}
                        className="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                      >
                        View details →
                      </Link>
                    </div>
                  </div>
                  
                  {canManageContests && (
                    <div className="flex items-center space-x-2">
                      <Link
                        to={`/contests/${contest.id}/edit`}
                        className="p-2 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
                      >
                        <PencilIcon className="h-5 w-5" />
                      </Link>
                      <button
                        className="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400"
                        onClick={() => {
                          // Handle delete
                        }}
                      >
                        <TrashIcon className="h-5 w-5" />
                      </button>
                    </div>
                  )}
                </div>
              </div>
            ))}
          </div>
        )}
      </div>

      {/* Pagination */}
      {contestsData?.pagination && contestsData.pagination.pages > 1 && (
        <div className="flex justify-center">
          <nav className="flex space-x-2">
            {Array.from({ length: contestsData.pagination.pages }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                className={`px-3 py-2 text-sm font-medium rounded-md ${
                  page === contestsData.pagination.page
                    ? 'bg-blue-600 text-white'
                    : 'bg-white dark:bg-gray-800 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700'
                }`}
                onClick={() => {
                  // Handle page change
                }}
              >
                {page}
              </button>
            ))}
          </nav>
        </div>
      )}
    </div>
  )
}

export default Contests
