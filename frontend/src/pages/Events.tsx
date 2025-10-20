import React from 'react'
import { Link } from 'react-router-dom'
import { useEvents } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  PlusIcon,
  CalendarIcon,
  ArchiveBoxIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'
import { format } from 'date-fns'

const Events: React.FC = () => {
  const { user } = useAuth()
  const [searchTerm, setSearchTerm] = React.useState('')
  const [showArchived, setShowArchived] = React.useState(false)
  
  const { data: eventsData, isLoading } = useEvents({
    search: searchTerm || undefined,
    archived: showArchived ? 'true' : 'false',
    limit: 20
  })

  const events = eventsData?.events || []

  const canManageEvents = user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Events
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage your events and contests
          </p>
        </div>
        {canManageEvents && (
          <Link
            to="/events/new"
            className="btn btn-primary btn-md"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            New Event
          </Link>
        )}
      </div>

      {/* Filters */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <input
              type="text"
              placeholder="Search events..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input w-full"
            />
          </div>
          <div className="flex items-center space-x-4">
            <label className="flex items-center">
              <input
                type="checkbox"
                checked={showArchived}
                onChange={(e) => setShowArchived(e.target.checked)}
                className="rounded border-gray-300 text-blue-600 focus:ring-blue-500"
              />
              <span className="ml-2 text-sm text-gray-700 dark:text-gray-300">
                Show archived
              </span>
            </label>
          </div>
        </div>
      </div>

      {/* Events List */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        {isLoading ? (
          <div className="flex justify-center items-center h-64">
            <LoadingSpinner size="lg" />
          </div>
        ) : events.length === 0 ? (
          <div className="text-center py-12">
            <CalendarIcon className="mx-auto h-12 w-12 text-gray-400" />
            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              No events found
            </h3>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              {showArchived 
                ? 'No archived events found.' 
                : 'Get started by creating a new event.'
              }
            </p>
            {canManageEvents && !showArchived && (
              <div className="mt-6">
                <Link
                  to="/events/new"
                  className="btn btn-primary btn-md"
                >
                  <PlusIcon className="h-5 w-5 mr-2" />
                  New Event
                </Link>
              </div>
            )}
          </div>
        ) : (
          <div className="divide-y divide-gray-200 dark:divide-gray-700">
            {events.map((event: any) => (
              <div key={event.id} className="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3">
                      <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                        {event.name}
                      </h3>
                      {event.archivedEvents?.length > 0 && (
                        <span className="badge badge-secondary">
                          <ArchiveBoxIcon className="h-3 w-3 mr-1" />
                          Archived
                        </span>
                      )}
                    </div>
                    <div className="mt-2 flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
                      <div className="flex items-center">
                        <CalendarIcon className="h-4 w-4 mr-1" />
                        {format(new Date(event.startDate), 'MMM dd, yyyy')} - {format(new Date(event.endDate), 'MMM dd, yyyy')}
                      </div>
                      <div>
                        {event._count?.contests || 0} contests
                      </div>
                    </div>
                    <div className="mt-3">
                      <Link
                        to={`/events/${event.id}`}
                        className="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                      >
                        View details →
                      </Link>
                    </div>
                  </div>
                  
                  {canManageEvents && (
                    <div className="flex items-center space-x-2">
                      <Link
                        to={`/events/${event.id}/edit`}
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
      {eventsData?.pagination && eventsData.pagination.pages > 1 && (
        <div className="flex justify-center">
          <nav className="flex space-x-2">
            {Array.from({ length: eventsData.pagination.pages }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                className={`px-3 py-2 text-sm font-medium rounded-md ${
                  page === eventsData.pagination.page
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

export default Events
