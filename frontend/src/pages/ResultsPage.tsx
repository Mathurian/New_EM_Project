import React, { useState } from 'react'
import { useQuery } from 'react-query'
import { resultsAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  ChartBarIcon,
  TrophyIcon,
  UserIcon,
  StarIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  PrinterIcon,
  DocumentArrowDownIcon,
  EyeIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface Result {
  id: string
  contestantId: string
  categoryId: string
  totalScore: number
  averageScore: number
  rank: number
  isCertified: boolean
  certifiedAt?: string
  certifiedBy?: string
  contestant: {
    id: string
    name: string
    email: string
    contestantNumber?: string
  }
  category: {
    id: string
    name: string
    maxScore: number
  }
  scores: Score[]
}

interface Score {
  id: string
  score: number
  comment?: string
  createdAt: string
  judge: {
    id: string
    name: string
  }
  criterion: {
    id: string
    name: string
    maxScore: number
  }
}

interface Category {
  id: string
  name: string
  description: string
  maxScore: number
  status: 'DRAFT' | 'ACTIVE' | 'COMPLETED' | 'ARCHIVED'
  _count?: {
    contestants: number
    scores: number
  }
}

const ResultsPage: React.FC = () => {
  const { user } = useAuth()
  const [selectedCategory, setSelectedCategory] = useState<string>('')
  const [searchTerm, setSearchTerm] = useState('')
  const [viewMode, setViewMode] = useState<'summary' | 'detailed'>('summary')
  const [showPrintModal, setShowPrintModal] = useState(false)

  const { data: categories, isLoading: categoriesLoading } = useQuery(
    'categories',
    () => resultsAPI.getCategories().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'CONTESTANT',
    }
  )

  const { data: results, isLoading: resultsLoading } = useQuery(
    ['results', selectedCategory],
    () => resultsAPI.getCategoryResults(selectedCategory).then(res => res.data),
    {
      enabled: !!selectedCategory,
    }
  )

  const { data: contestantResults } = useQuery(
    'contestant-results',
    () => resultsAPI.getContestantResults(user?.id || '').then(res => res.data),
    {
      enabled: user?.role === 'CONTESTANT' && !!user?.id,
    }
  )

  const filteredCategories = categories?.filter((category: Category) => {
    const matchesSearch = category.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         category.description.toLowerCase().includes(searchTerm.toLowerCase())
    return matchesSearch
  }) || []

  const filteredResults = results?.filter((result: Result) => {
    const matchesSearch = result.contestant.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         result.contestant.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         (result.contestant.contestantNumber && 
                          result.contestant.contestantNumber.toLowerCase().includes(searchTerm.toLowerCase()))
    return matchesSearch
  }) || []

  const getRankIcon = (rank: number) => {
    switch (rank) {
      case 1: return '🥇'
      case 2: return '🥈'
      case 3: return '🥉'
      default: return `#${rank}`
    }
  }

  const getRankColor = (rank: number) => {
    switch (rank) {
      case 1: return 'text-yellow-600 dark:text-yellow-400'
      case 2: return 'text-gray-600 dark:text-gray-400'
      case 3: return 'text-orange-600 dark:text-orange-400'
      default: return 'text-gray-500 dark:text-gray-400'
    }
  }

  const getCertificationStatus = (isCertified: boolean, certifiedAt?: string) => {
    if (isCertified) {
      return (
        <div className="flex items-center text-green-600 dark:text-green-400">
          <CheckCircleIcon className="h-4 w-4 mr-1" />
          <span className="text-sm">Certified</span>
        </div>
      )
    } else {
      return (
        <div className="flex items-center text-yellow-600 dark:text-yellow-400">
          <ClockIcon className="h-4 w-4 mr-1" />
          <span className="text-sm">Pending</span>
        </div>
      )
    }
  }

  const handlePrint = () => {
    setShowPrintModal(true)
    setTimeout(() => {
      window.print()
      setShowPrintModal(false)
    }, 100)
  }

  if (categoriesLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="loading-spinner"></div>
      </div>
    )
  }

  // Contestant view - show only their results
  if (user?.role === 'CONTESTANT') {
    return (
      <div className="space-y-6">
        {/* Header */}
        <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
          <div>
            <h1 className="text-2xl font-bold text-gray-900 dark:text-white">My Results</h1>
            <p className="text-gray-600 dark:text-gray-400">
              View your contest results and rankings
            </p>
          </div>
          <div className="mt-4 sm:mt-0">
            <button
              onClick={handlePrint}
              className="btn btn-outline"
            >
              <PrinterIcon className="h-5 w-5 mr-2" />
              Print Results
            </button>
          </div>
        </div>

        {/* Contestant Results */}
        {contestantResults && contestantResults.length > 0 ? (
          <div className="grid-responsive">
            {contestantResults.map((result: Result) => (
              <div key={result.id} className="card">
                <div className="card-header">
                  <div className="flex items-start justify-between">
                    <div className="flex-1 min-w-0">
                      <h3 className="card-title text-lg">{result.category.name}</h3>
                      <p className="card-description">
                        Contestant #{result.contestant.contestantNumber || 'N/A'}
                      </p>
                    </div>
                    <div className="text-right">
                      <div className={`text-2xl font-bold ${getRankColor(result.rank)}`}>
                        {getRankIcon(result.rank)}
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">
                        Rank {result.rank}
                      </div>
                    </div>
                  </div>
                </div>
                <div className="card-content space-y-3">
                  <div className="grid grid-cols-2 gap-4">
                    <div className="text-center">
                      <div className="text-2xl font-bold text-primary">
                        {result.totalScore}
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">Total Score</div>
                    </div>
                    <div className="text-center">
                      <div className="text-2xl font-bold text-primary">
                        {result.averageScore.toFixed(1)}
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">Average Score</div>
                    </div>
                  </div>
                  <div className="flex items-center justify-between">
                    <span className="text-sm text-gray-600 dark:text-gray-400">Certification Status</span>
                    {getCertificationStatus(result.isCertified, result.certifiedAt)}
                  </div>
                </div>
                <div className="card-footer">
                  <button className="btn btn-primary w-full btn-sm">
                    <EyeIcon className="h-4 w-4 mr-1" />
                    View Details
                  </button>
                </div>
              </div>
            ))}
          </div>
        ) : (
          <div className="card">
            <div className="card-content text-center py-12">
              <TrophyIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                No results yet
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                Your results will appear here once judges complete scoring.
              </p>
            </div>
          </div>
        )}
      </div>
    )
  }

  // Organizer/Board/Judge view - show all results
  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Results</h1>
          <p className="text-gray-600 dark:text-gray-400">
            View contest results and rankings
          </p>
        </div>
        <div className="mt-4 sm:mt-0 flex space-x-2">
          <button
            onClick={() => setViewMode(viewMode === 'summary' ? 'detailed' : 'summary')}
            className="btn btn-outline"
          >
            <EyeIcon className="h-5 w-5 mr-2" />
            {viewMode === 'summary' ? 'Detailed View' : 'Summary View'}
          </button>
          <button
            onClick={handlePrint}
            className="btn btn-outline"
          >
            <PrinterIcon className="h-5 w-5 mr-2" />
            Print Results
          </button>
        </div>
      </div>

      {/* Category Selection */}
      <div className="card">
        <div className="card-header">
          <h3 className="card-title">Select Category</h3>
        </div>
        <div className="card-content">
          <div className="space-y-4">
            <div className="relative">
              <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search categories..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input pl-10"
              />
            </div>
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {filteredCategories.map((category: Category) => (
                <div
                  key={category.id}
                  className={`card cursor-pointer transition-colors ${
                    selectedCategory === category.id
                      ? 'ring-2 ring-primary bg-primary/5'
                      : 'hover:bg-gray-50 dark:hover:bg-gray-700'
                  }`}
                  onClick={() => setSelectedCategory(category.id)}
                >
                  <div className="card-content">
                    <div className="flex items-start justify-between mb-2">
                      <h4 className="font-medium text-gray-900 dark:text-white">
                        {category.name}
                      </h4>
                      <span className="badge badge-outline">
                        {category._count?.contestants || 0} contestants
                      </span>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                      {category.description}
                    </p>
                    <div className="text-sm text-gray-600 dark:text-gray-400">
                      Max Score: {category.maxScore}
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Results Display */}
      {selectedCategory && (
        <div className="card">
          <div className="card-header">
            <h3 className="card-title">
              Results - {categories?.find((c: Category) => c.id === selectedCategory)?.name}
            </h3>
          </div>
          <div className="card-content">
            {resultsLoading ? (
              <div className="flex items-center justify-center py-8">
                <div className="loading-spinner"></div>
              </div>
            ) : filteredResults && filteredResults.length > 0 ? (
              <div className="space-y-4">
                {viewMode === 'summary' ? (
                  // Summary View
                  <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    {filteredResults.map((result: Result) => (
                      <div key={result.id} className="card">
                        <div className="card-content">
                          <div className="flex items-center justify-between mb-3">
                            <div className="flex items-center space-x-3">
                              <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-medium">
                                {result.contestant.contestantNumber || result.contestant.name.charAt(0)}
                              </div>
                              <div>
                                <h4 className="font-medium text-gray-900 dark:text-white">
                                  {result.contestant.name}
                                </h4>
                                <p className="text-sm text-gray-600 dark:text-gray-400">
                                  #{result.contestant.contestantNumber || 'N/A'}
                                </p>
                              </div>
                            </div>
                            <div className="text-right">
                              <div className={`text-2xl font-bold ${getRankColor(result.rank)}`}>
                                {getRankIcon(result.rank)}
                              </div>
                            </div>
                          </div>
                          <div className="grid grid-cols-2 gap-4 mb-3">
                            <div className="text-center">
                              <div className="text-lg font-bold text-primary">
                                {result.totalScore}
                              </div>
                              <div className="text-xs text-gray-600 dark:text-gray-400">Total</div>
                            </div>
                            <div className="text-center">
                              <div className="text-lg font-bold text-primary">
                                {result.averageScore.toFixed(1)}
                              </div>
                              <div className="text-xs text-gray-600 dark:text-gray-400">Average</div>
                            </div>
                          </div>
                          <div className="flex items-center justify-between">
                            <span className="text-sm text-gray-600 dark:text-gray-400">Status</span>
                            {getCertificationStatus(result.isCertified, result.certifiedAt)}
                          </div>
                        </div>
                      </div>
                    ))}
                  </div>
                ) : (
                  // Detailed View
                  <div className="space-y-3">
                    {filteredResults.map((result: Result) => (
                      <div key={result.id} className="p-4 bg-white dark:bg-gray-800 rounded-lg border">
                        <div className="flex items-center justify-between mb-4">
                          <div className="flex items-center space-x-4">
                            <div className="w-12 h-12 bg-primary rounded-full flex items-center justify-center text-white font-medium text-lg">
                              {result.contestant.contestantNumber || result.contestant.name.charAt(0)}
                            </div>
                            <div>
                              <h4 className="text-lg font-medium text-gray-900 dark:text-white">
                                {result.contestant.name}
                              </h4>
                              <p className="text-sm text-gray-600 dark:text-gray-400">
                                Contestant #{result.contestant.contestantNumber || 'N/A'}
                              </p>
                            </div>
                          </div>
                          <div className="text-right">
                            <div className={`text-3xl font-bold ${getRankColor(result.rank)}`}>
                              {getRankIcon(result.rank)}
                            </div>
                            <div className="text-sm text-gray-600 dark:text-gray-400">
                              Rank {result.rank}
                            </div>
                          </div>
                        </div>
                        <div className="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
                          <div className="text-center">
                            <div className="text-2xl font-bold text-primary">
                              {result.totalScore}
                            </div>
                            <div className="text-sm text-gray-600 dark:text-gray-400">Total Score</div>
                          </div>
                          <div className="text-center">
                            <div className="text-2xl font-bold text-primary">
                              {result.averageScore.toFixed(1)}
                            </div>
                            <div className="text-sm text-gray-600 dark:text-gray-400">Average Score</div>
                          </div>
                          <div className="text-center">
                            <div className="text-2xl font-bold text-primary">
                              {result.scores.length}
                            </div>
                            <div className="text-sm text-gray-600 dark:text-gray-400">Criteria Scored</div>
                          </div>
                        </div>
                        <div className="flex items-center justify-between">
                          <span className="text-sm text-gray-600 dark:text-gray-400">Certification Status</span>
                          {getCertificationStatus(result.isCertified, result.certifiedAt)}
                        </div>
                      </div>
                    ))}
                  </div>
                )}
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <ChartBarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                  No results found
                </h3>
                <p className="text-gray-600 dark:text-gray-400">
                  {searchTerm
                    ? 'Try adjusting your search criteria'
                    : 'Results will appear here once scoring is completed'}
                </p>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Print Modal */}
      {showPrintModal && (
        <div className="modal">
          <div className="modal-overlay" onClick={() => setShowPrintModal(false)} />
          <div className="modal-content">
            <h2 className="text-xl font-semibold mb-4">Print Results</h2>
            <p className="text-gray-600 dark:text-gray-400 mb-6">
              Results will be printed. Make sure your printer is ready.
            </p>
            <div className="flex justify-end space-x-3">
              <button
                onClick={() => setShowPrintModal(false)}
                className="btn btn-outline"
              >
                Cancel
              </button>
              <button
                onClick={handlePrint}
                className="btn btn-primary"
              >
                Print
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default ResultsPage
