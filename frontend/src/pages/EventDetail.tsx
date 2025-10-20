import React from 'react'
import { Link, useParams } from 'react-router-dom'
import { useEvent } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  PlusIcon,
  TrophyIcon,
  CalendarIcon,
  UsersIcon,
  TagIcon,
  ArchiveBoxIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'
import { format } from 'date-fns'

const EventDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>()
  const { user } = useAuth()
  const { data: event, isLoading } = useEvent(id!)

  const canManageEvents = user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  if (isLoading) {
    return <LoadingSpinner size="lg" className="flex justify-center items-center h-64" />
  }

  if (!event) {
    return (
      <div className="text-center py-12">
        <CalendarIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          Event not found
        </h3>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          The event you're looking for doesn't exist.
        </p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div className="flex justify-between items-start">
          <div>
            <div className="flex items-center space-x-3">
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
                {event.name}
              </h1>
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
              <div className="flex items-center">
                <TrophyIcon className="h-4 w-4 mr-1" />
                {event.contests?.length || 0} contests
              </div>
            </div>
          </div>
          
          {canManageEvents && (
            <div className="flex items-center space-x-2">
              <Link
                to={`/events/${event.id}/edit`}
                className="btn btn-outline btn-sm"
              >
                <PencilIcon className="h-4 w-4 mr-2" />
                Edit
              </Link>
              <button
                className="btn btn-destructive btn-sm"
                onClick={() => {
                  // Handle delete
                }}
              >
                <TrashIcon className="h-4 w-4 mr-2" />
                Delete
              </button>
            </div>
          )}
        </div>
      </div>

      {/* Contests Section */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Contests
            </h2>
            {canManageEvents && (
              <Link
                to={`/contests/new?eventId=${event.id}`}
                className="btn btn-primary btn-sm"
              >
                <PlusIcon className="h-4 w-4 mr-2" />
                New Contest
              </Link>
            )}
          </div>
        </div>

        <div className="p-6">
          {event.contests?.length === 0 ? (
            <div className="text-center py-8">
              <TrophyIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No contests yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Get started by creating the first contest for this event.
              </p>
              {canManageEvents && (
                <div className="mt-6">
                  <Link
                    to={`/contests/new?eventId=${event.id}`}
                    className="btn btn-primary btn-md"
                  >
                    <PlusIcon className="h-5 w-5 mr-2" />
                    New Contest
                  </Link>
                </div>
              )}
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {event.contests?.map((contest: any) => (
                <div key={contest.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                        {contest.name}
                      </h3>
                      {contest.description && (
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                          {contest.description}
                        </p>
                      )}
                      <div className="mt-3 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                        <div className="flex items-center">
                          <TagIcon className="h-4 w-4 mr-1" />
                          {contest._count?.categories || 0} categories
                        </div>
                        <div className="flex items-center">
                          <UsersIcon className="h-4 w-4 mr-1" />
                          {contest._count?.contestants || 0} contestants
                        </div>
                      </div>
                    </div>
                  </div>
                  <div className="mt-4">
                    <Link
                      to={`/contests/${contest.id}`}
                      className="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm"
                    >
                      View details →
                    </Link>
                  </div>
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Event Statistics */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <div className="flex items-center">
            <div className="p-3 rounded-md bg-blue-500">
              <TrophyIcon className="h-6 w-6 text-white" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Contests
              </p>
              <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                {event.contests?.length || 0}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <div className="flex items-center">
            <div className="p-3 rounded-md bg-green-500">
              <TagIcon className="h-6 w-6 text-white" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Categories
              </p>
              <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                {event.contests?.reduce((total: number, contest: any) => 
                  total + (contest._count?.categories || 0), 0
                )}
              </p>
            </div>
          </div>
        </div>

        <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
          <div className="flex items-center">
            <div className="p-3 rounded-md bg-purple-500">
              <UsersIcon className="h-6 w-6 text-white" />
            </div>
            <div className="ml-4">
              <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                Total Contestants
              </p>
              <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                {event.contests?.reduce((total: number, contest: any) => 
                  total + (contest._count?.contestants || 0), 0
                )}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default EventDetail
