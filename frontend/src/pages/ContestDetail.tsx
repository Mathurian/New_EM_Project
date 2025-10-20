import React from 'react'
import { Link, useParams } from 'react-router-dom'
import { useContest } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  PlusIcon,
  TagIcon,
  UsersIcon,
  TrophyIcon,
  PencilIcon,
  TrashIcon,
  UserPlusIcon,
  UserMinusIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'

const ContestDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>()
  const { user } = useAuth()
  const { data: contest, isLoading } = useContest(id!)

  const canManageContests = user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  if (isLoading) {
    return <LoadingSpinner size="lg" className="flex justify-center items-center h-64" />
  }

  if (!contest) {
    return (
      <div className="text-center py-12">
        <TrophyIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          Contest not found
        </h3>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          The contest you're looking for doesn't exist.
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
                {contest.name}
              </h1>
              {contest.event && (
                <span className="badge badge-outline">
                  {contest.event.name}
                </span>
              )}
            </div>
            {contest.description && (
              <p className="mt-2 text-gray-600 dark:text-gray-400">
                {contest.description}
              </p>
            )}
            <div className="mt-3 flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
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
          </div>
          
          {canManageContests && (
            <div className="flex items-center space-x-2">
              <Link
                to={`/contests/${contest.id}/edit`}
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

      {/* Categories Section */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Categories
            </h2>
            {canManageContests && (
              <Link
                to={`/categories/new?contestId=${contest.id}`}
                className="btn btn-primary btn-sm"
              >
                <PlusIcon className="h-4 w-4 mr-2" />
                New Category
              </Link>
            )}
          </div>
        </div>

        <div className="p-6">
          {contest.categories?.length === 0 ? (
            <div className="text-center py-8">
              <TagIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No categories yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Get started by creating the first category for this contest.
              </p>
              {canManageContests && (
                <div className="mt-6">
                  <Link
                    to={`/categories/new?contestId=${contest.id}`}
                    className="btn btn-primary btn-md"
                  >
                    <PlusIcon className="h-5 w-5 mr-2" />
                    New Category
                  </Link>
                </div>
              )}
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {contest.categories?.map((category: any) => (
                <div key={category.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4 hover:shadow-md transition-shadow">
                  <div className="flex items-start justify-between">
                    <div className="flex-1">
                      <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                        {category.name}
                      </h3>
                      {category.description && (
                        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                          {category.description}
                        </p>
                      )}
                      <div className="mt-3 flex items-center space-x-4 text-sm text-gray-500 dark:text-gray-400">
                        <div className="flex items-center">
                          <UsersIcon className="h-4 w-4 mr-1" />
                          {category._count?.contestants || 0} contestants
                        </div>
                        <div className="flex items-center">
                          <TrophyIcon className="h-4 w-4 mr-1" />
                          {category._count?.judges || 0} judges
                        </div>
                      </div>
                      {category.scoreCap && (
                        <div className="mt-2 text-sm text-gray-500 dark:text-gray-400">
                          Score Cap: {category.scoreCap}
                        </div>
                      )}
                    </div>
                  </div>
                  <div className="mt-4">
                    <Link
                      to={`/categories/${category.id}`}
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

      {/* Contestants Section */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Contestants
            </h2>
            {canManageContests && (
              <button className="btn btn-outline btn-sm">
                <UserPlusIcon className="h-4 w-4 mr-2" />
                Add Contestant
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {contest.contestants?.length === 0 ? (
            <div className="text-center py-8">
              <UsersIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No contestants yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add contestants to this contest to get started.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {contest.contestants?.map((contestant: any) => (
                <div key={contestant.contestantId} className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="h-10 w-10 rounded-full bg-blue-500 flex items-center justify-center">
                    <span className="text-sm font-medium text-white">
                      {contestant.contestant.name.charAt(0)}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {contestant.contestant.name}
                    </p>
                    {contestant.contestant.contestantNumber && (
                      <p className="text-sm text-gray-500 dark:text-gray-400">
                        #{contestant.contestant.contestantNumber}
                      </p>
                    )}
                  </div>
                  {canManageContests && (
                    <button className="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                      <UserMinusIcon className="h-4 w-4" />
                    </button>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>

      {/* Judges Section */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Judges
            </h2>
            {canManageContests && (
              <button className="btn btn-outline btn-sm">
                <UserPlusIcon className="h-4 w-4 mr-2" />
                Add Judge
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {contest.judges?.length === 0 ? (
            <div className="text-center py-8">
              <TrophyIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No judges yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add judges to this contest to get started.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {contest.judges?.map((judge: any) => (
                <div key={judge.judgeId} className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="h-10 w-10 rounded-full bg-green-500 flex items-center justify-center">
                    <span className="text-sm font-medium text-white">
                      {judge.judge.name.charAt(0)}
                    </span>
                  </div>
                  <div className="flex-1 min-w-0">
                    <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                      {judge.judge.name}
                    </p>
                    {judge.judge.isHeadJudge && (
                      <p className="text-sm text-green-600 dark:text-green-400">
                        Head Judge
                      </p>
                    )}
                  </div>
                  {canManageContests && (
                    <button className="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                      <UserMinusIcon className="h-4 w-4" />
                    </button>
                  )}
                </div>
              ))}
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

export default ContestDetail
