import React from 'react'
import { Link } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { 
  ClipboardDocumentListIcon,
  TrophyIcon,
  TagIcon,
  UsersIcon,
  CheckCircleIcon
} from '@heroicons/react/24/outline'

const Scoring: React.FC = () => {
  const { user } = useAuth()

  const canScore = user?.role === 'JUDGE' || user?.role === 'ORGANIZER' || user?.role === 'BOARD'

  if (!canScore) {
    return (
      <div className="text-center py-12">
        <TrophyIcon className="mx-auto h-12 w-12 text-gray-400" />
        <h3 className="mt-2 text-sm font-medium text-gray-900 dark:text-white">
          Access Denied
        </h3>
        <p className="mt-1 text-sm text-gray-500 dark:text-gray-400">
          You don't have permission to access the scoring section.
        </p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        <div className="flex items-center space-x-3">
          <div className="p-3 rounded-md bg-blue-500">
            <ClipboardDocumentListIcon className="h-6 w-6 text-white" />
          </div>
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
              Scoring Dashboard
            </h1>
            <p className="text-gray-600 dark:text-gray-400 mt-1">
              Manage scores and certifications for assigned categories
            </p>
          </div>
        </div>
      </div>

      {/* Judge-specific content */}
      {user?.role === 'JUDGE' && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
          {/* Assigned Categories */}
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center">
              <div className="p-3 rounded-md bg-green-500">
                <TagIcon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                  Assigned Categories
                </p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  3
                </p>
              </div>
            </div>
            <div className="mt-4">
              <Link
                to="/scoring/categories"
                className="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm"
              >
                View all →
              </Link>
            </div>
          </div>

          {/* Pending Scores */}
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center">
              <div className="p-3 rounded-md bg-orange-500">
                <ClipboardDocumentListIcon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                  Pending Scores
                </p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  12
                </p>
              </div>
            </div>
            <div className="mt-4">
              <Link
                to="/scoring/pending"
                className="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm"
              >
                Score now →
              </Link>
            </div>
          </div>

          {/* Completed Scores */}
          <div className="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
            <div className="flex items-center">
              <div className="p-3 rounded-md bg-purple-500">
                <CheckCircleIcon className="h-6 w-6 text-white" />
              </div>
              <div className="ml-4">
                <p className="text-sm font-medium text-gray-600 dark:text-gray-400">
                  Completed Scores
                </p>
                <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                  8
                </p>
              </div>
            </div>
            <div className="mt-4">
              <Link
                to="/scoring/completed"
                className="text-blue-600 hover:text-blue-500 dark:text-blue-400 dark:hover:text-blue-300 font-medium text-sm"
              >
                View all →
              </Link>
            </div>
          </div>
        </div>
      )}

      {/* Recent Scoring Activity */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-medium text-gray-900 dark:text-white">
            Recent Scoring Activity
          </h2>
        </div>
        <div className="p-6">
          <div className="space-y-4">
            {/* Sample scoring activity */}
            <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
              <div className="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                <span className="text-sm font-medium text-white">J</span>
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm text-gray-900 dark:text-white">
                  <span className="font-medium">John Doe</span> scored <span className="font-medium">Contestant #1</span> in <span className="font-medium">Performance Category</span>
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  2 hours ago
                </p>
              </div>
              <div className="text-sm text-gray-500 dark:text-gray-400">
                85/100
              </div>
            </div>

            <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
              <div className="h-8 w-8 rounded-full bg-green-500 flex items-center justify-center">
                <span className="text-sm font-medium text-white">S</span>
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm text-gray-900 dark:text-white">
                  <span className="font-medium">Sarah Smith</span> certified scores for <span className="font-medium">Technique Category</span>
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  4 hours ago
                </p>
              </div>
              <div className="text-sm text-green-600 dark:text-green-400">
                Certified
              </div>
            </div>

            <div className="flex items-center space-x-3 p-3 border border-gray-200 dark:border-gray-700 rounded-lg">
              <div className="h-8 w-8 rounded-full bg-purple-500 flex items-center justify-center">
                <span className="text-sm font-medium text-white">M</span>
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm text-gray-900 dark:text-white">
                  <span className="font-medium">Mike Johnson</span> scored <span className="font-medium">Contestant #3</span> in <span className="font-medium">Presentation Category</span>
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  6 hours ago
                </p>
              </div>
              <div className="text-sm text-gray-500 dark:text-gray-400">
                92/100
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* Quick Actions */}
      <div className="bg-white dark:bg-gray-800 shadow rounded-lg">
        <div className="px-6 py-4 border-b border-gray-200 dark:border-gray-700">
          <h2 className="text-lg font-medium text-gray-900 dark:text-white">
            Quick Actions
          </h2>
        </div>
        <div className="p-6">
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <Link
              to="/scoring/categories"
              className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <TagIcon className="h-8 w-8 text-blue-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  My Categories
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  View assigned categories
                </p>
              </div>
            </Link>

            <Link
              to="/scoring/pending"
              className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <ClipboardDocumentListIcon className="h-8 w-8 text-orange-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Pending Scores
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Score contestants
                </p>
              </div>
            </Link>

            <Link
              to="/scoring/certifications"
              className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <CheckCircleIcon className="h-8 w-8 text-green-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Certifications
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  Manage certifications
                </p>
              </div>
            </Link>

            <Link
              to="/scoring/reports"
              className="flex items-center p-4 border border-gray-200 dark:border-gray-700 rounded-lg hover:bg-gray-50 dark:hover:bg-gray-700 transition-colors"
            >
              <TrophyIcon className="h-8 w-8 text-purple-500 mr-3" />
              <div>
                <p className="text-sm font-medium text-gray-900 dark:text-white">
                  Score Reports
                </p>
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  View score summaries
                </p>
              </div>
            </Link>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Scoring
