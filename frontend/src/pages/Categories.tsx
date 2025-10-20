import React from 'react'
import { Link } from 'react-router-dom'
import { useCategories } from '../hooks/useApi'
import { useAuth } from '../hooks/useAuth'
import { 
  PlusIcon,
  TagIcon,
  UsersIcon,
  TrophyIcon,
  PencilIcon,
  TrashIcon
} from '@heroicons/react/24/outline'
import LoadingSpinner from '../components/LoadingSpinner'

const Categories: React.FC = () => {
  const { user } = useAuth()
  const [searchTerm, setSearchTerm] = React.useState('')
  const [selectedContestId, setSelectedContestId] = React.useState('')
  
  const { data: categoriesData, isLoading } = useCategories(selectedContestId, {
    search: searchTerm || undefined,
    limit: 20
  })

  const categories = categoriesData?.categories || []

  const canManageCategories = user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex justify-between items-center">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Categories
          </h1>
          <p className="text-gray-600 dark:text-gray-400 mt-1">
            Manage categories and scoring criteria
          </p>
        </div>
        {canManageCategories && (
          <Link
            to="/categories/new"
            className="btn btn-primary btn-md"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            New Category
          </Link>
        )}
      </div>

      {/* Filters */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div className="flex flex-col sm:flex-row gap-4">
          <div className="flex-1">
            <input
              type="text"
              placeholder="Search categories..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input w-full"
            />
          </div>
          <div className="flex-1">
            <select
              value={selectedContestId}
              onChange={(e) => setSelectedContestId(e.target.value)}
              className="input w-full"
            >
              <option value="">All Contests</option>
              {/* This would be populated with contests */}
            </select>
          </div>
        </div>
      </div>

      {/* Categories List */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        {isLoading ? (
          <div className="flex justify-center items-center h-64">
            <LoadingSpinner size="lg" />
          </div>
        ) : categories.length === 0 ? (
          <div className="text-center py-12">
            <TagIcon className="mx-auto h-12 w-12 text-gray-400" />
            <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
              No categories found
            </h3>
            <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
              Get started by creating a new category.
            </p>
            {canManageCategories && (
              <div className="mt-6">
                <Link
                  to="/categories/new"
                  className="btn btn-primary btn-md"
                >
                  <PlusIcon className="h-5 w-5 mr-2" />
                  New Category
                </Link>
              </div>
            )}
          </div>
        ) : (
          <div className="divide-y divide-gray-200 dark:divide-gray-700">
            {categories.map((category: any) => (
              <div key={category.id} className="p-6 hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors">
                <div className="flex items-center justify-between">
                  <div className="flex-1">
                    <div className="flex items-center space-x-3">
                      <h3 className="text-lg font-medium text-gray-900 dark:text-white">
                        {category.name}
                      </h3>
                      {category.contest && (
                        <span className="badge badge-outline">
                          {category.contest.name}
                        </span>
                      )}
                    </div>
                    {category.description && (
                      <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                        {category.description}
                      </p>
                    )}
                    <div className="mt-2 flex items-center space-x-6 text-sm text-gray-500 dark:text-gray-400">
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
                    <div className="mt-3">
                      <Link
                        to={`/categories/${category.id}`}
                        className="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium"
                      >
                        View details →
                      </Link>
                    </div>
                  </div>
                  
                  {canManageCategories && (
                    <div className="flex items-center space-x-2">
                      <Link
                        to={`/categories/${category.id}/edit`}
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
      {categoriesData?.pagination && categoriesData.pagination.pages > 1 && (
        <div className="flex justify-center">
          <nav className="flex space-x-2">
            {Array.from({ length: categoriesData.pagination.pages }, (_, i) => i + 1).map((page) => (
              <button
                key={page}
                className={`px-3 py-2 text-sm font-medium rounded-md ${
                  page === categoriesData.pagination.page
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

export default Categories
