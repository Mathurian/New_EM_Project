import React from 'react'
import { Link, useParams } from 'react-router-dom'
import { useCategory } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  PlusIcon,
  TagIcon,
  UsersIcon,
  TrophyIcon,
  PencilIcon,
  TrashIcon,
  UserPlusIcon,
  UserMinusIcon,
  ClipboardDocumentListIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'

const CategoryDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>()
  const { user } = useAuth()
  const { data: category, isLoading } = useCategory(id!)

  const canManageCategories = user?.role === 'ORGANIZER' || user?.role === 'BOARD'
  const canScore = user?.role === 'JUDGE' || user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  if (isLoading) {
    return <LoadingSpinner size="lg" className="flex justify-center items-center h-64" />
  }

  if (!category) {
    return (
      <div className="text-center py-12">
        <TagIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          Category not found
        </h3>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          The category you're looking for doesn't exist.
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
                {category.name}
              </h1>
              {category.contest && (
                <span className="badge badge-outline">
                  {category.contest.name}
                </span>
              )}
            </div>
            {category.description && (
              <p className="mt-2 text-gray-600 dark:text-gray-400">
                {category.description}
              </p>
            )}
            <div className="mt-3 flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
              <div className="flex items-center">
                <UsersIcon className="h-4 w-4 mr-1" />
                {category._count?.contestants || 0} contestants
              </div>
              <div className="flex items-center">
                <TrophyIcon className="h-4 w-4 mr-1" />
                {category._count?.judges || 0} judges
              </div>
              <div className="flex items-center">
                <TagIcon className="h-4 w-4 mr-1" />
                {category._count?.criteria || 0} criteria
              </div>
              {category.scoreCap && (
                <div className="flex items-center">
                  Score Cap: {category.scoreCap}
                </div>
              )}
            </div>
          </div>
          
          {canManageCategories && (
            <div className="flex items-center space-x-2">
              <Link
                to={`/categories/${category.id}/edit`}
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

      {/* Criteria Section */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <div className="flex justify-between items-center">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Scoring Criteria
            </h2>
            {canManageCategories && (
              <button className="btn btn-primary btn-sm">
                <PlusIcon className="h-4 w-4 mr-2" />
                Add Criterion
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {category.criteria?.length === 0 ? (
            <div className="text-center py-8">
              <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No criteria yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add scoring criteria to this category.
              </p>
            </div>
          ) : (
            <div className="space-y-3">
              {category.criteria?.map((criterion: any) => (
                <div key={criterion.id} className="flex items-center justify-between p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="flex-1">
                    <h3 className="text-sm font-medium text-gray-900 dark:text-white">
                      {criterion.name}
                    </h3>
                    <p className="text-sm text-gray-500 dark:text-gray-400">
                      Max Score: {criterion.maxScore}
                    </p>
                  </div>
                  {canManageCategories && (
                    <div className="flex items-center space-x-2">
                      <button className="p-1 text-gray-400 hover:text-gray-600 dark:hover:text-gray-300">
                        <PencilIcon className="h-4 w-4" />
                      </button>
                      <button className="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                        <TrashIcon className="h-4 w-4" />
                      </button>
                    </div>
                  )}
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
            {canManageCategories && (
              <button className="btn btn-outline btn-sm">
                <UserPlusIcon className="h-4 w-4 mr-2" />
                Add Contestant
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {category.contestants?.length === 0 ? (
            <div className="text-center py-8">
              <UsersIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No contestants yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add contestants to this category to get started.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {category.contestants?.map((contestant: any) => (
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
                  <div className="flex items-center space-x-2">
                    {canScore && (
                      <Link
                        to={`/scoring/category/${category.id}/contestant/${contestant.contestantId}`}
                        className="btn btn-primary btn-sm"
                      >
                        Score
                      </Link>
                    )}
                    {canManageCategories && (
                      <button className="p-1 text-gray-400 hover:text-red-600 dark:hover:text-red-400">
                        <UserMinusIcon className="h-4 w-4" />
                      </button>
                    )}
                  </div>
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
            {canManageCategories && (
              <button className="btn btn-outline btn-sm">
                <UserPlusIcon className="h-4 w-4 mr-2" />
                Add Judge
              </button>
            )}
          </div>
        </div>

        <div className="p-6">
          {category.judges?.length === 0 ? (
            <div className="text-center py-8">
              <TrophyIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No judges yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Add judges to this category to get started.
              </p>
            </div>
          ) : (
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {category.judges?.map((judge: any) => (
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
                  {canManageCategories && (
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

      {/* Scoring Summary */}
      {canScore && (
        <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
          <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
            <h2 className="text-lg font-medium text-gray-900 dark:text-white">
              Scoring Summary
            </h2>
          </div>
          <div className="p-6">
            <div className="text-center py-8">
              <ClipboardDocumentListIcon className="mx-auto h-12 w-12 text-gray-400" />
              <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
                No scores yet
              </h3>
              <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
                Scores will appear here once judges start scoring contestants.
              </p>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default CategoryDetail
