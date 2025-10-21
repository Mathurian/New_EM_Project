import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { scoringAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  StarIcon,
  CheckCircleIcon,
  XCircleIcon,
  ClockIcon,
  UserIcon,
  TrophyIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  DocumentCheckIcon,
  ExclamationTriangleIcon,
  PencilIcon,
  TrashIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface Score {
  id: string
  score: number
  comment?: string
  createdAt: string
  updatedAt: string
  judge: {
    id: string
    name: string
    email: string
  }
  contestant: {
    id: string
    name: string
    email: string
  }
  criterion: {
    id: string
    name: string
    maxScore: number
  }
  category: {
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
  order: number
  status: 'DRAFT' | 'ACTIVE' | 'COMPLETED' | 'ARCHIVED'
  _count?: {
    criteria: number
    contestants: number
    judges: number
    scores: number
  }
  criteria?: Criterion[]
  contestants?: Contestant[]
  judges?: Judge[]
}

interface Criterion {
  id: string
  name: string
  description: string
  maxScore: number
  order: number
}

interface Contestant {
  id: string
  name: string
  email: string
  contestantNumber?: string
}

interface Judge {
  id: string
  name: string
  email: string
}

const ScoringPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [selectedCategory, setSelectedCategory] = useState<string>('')
  const [selectedContestant, setSelectedContestant] = useState<string>('')
  const [searchTerm, setSearchTerm] = useState('')
  const [showScoreModal, setShowScoreModal] = useState(false)
  const [editingScore, setEditingScore] = useState<Score | null>(null)

  const { data: categories, isLoading: categoriesLoading } = useQuery(
    'categories',
    () => scoringAPI.getCategories().then(res => res.data),
    {
      enabled: user?.role === 'JUDGE',
    }
  )

  const { data: scores, isLoading: scoresLoading } = useQuery(
    ['scores', selectedCategory, selectedContestant],
    () => scoringAPI.getScores(selectedCategory, selectedContestant).then(res => res.data),
    {
      enabled: !!selectedCategory && !!selectedContestant,
    }
  )

  const submitScoreMutation = useMutation(
    (scoreData: any) => scoringAPI.submitScore(scoreData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['scores', selectedCategory, selectedContestant])
        setShowScoreModal(false)
        setEditingScore(null)
      },
    }
  )

  const updateScoreMutation = useMutation(
    ({ id, data }: { id: string; data: any }) => scoringAPI.updateScore(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['scores', selectedCategory, selectedContestant])
        setShowScoreModal(false)
        setEditingScore(null)
      },
    }
  )

  const deleteScoreMutation = useMutation(
    (id: string) => scoringAPI.deleteScore(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['scores', selectedCategory, selectedContestant])
      },
    }
  )

  const filteredCategories = categories?.filter((category: Category) => {
    const matchesSearch = category.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         category.description.toLowerCase().includes(searchTerm.toLowerCase())
    return matchesSearch
  }) || []

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'DRAFT': return 'badge-secondary'
      case 'ACTIVE': return 'badge-default'
      case 'COMPLETED': return 'badge-success'
      case 'ARCHIVED': return 'badge-outline'
      default: return 'badge-secondary'
    }
  }

  const getStatusText = (status: string) => {
    switch (status) {
      case 'DRAFT': return 'Draft'
      case 'ACTIVE': return 'Active'
      case 'COMPLETED': return 'Completed'
      case 'ARCHIVED': return 'Archived'
      default: return status
    }
  }

  const calculateTotalScore = (scores: Score[]) => {
    return scores.reduce((total, score) => total + score.score, 0)
  }

  const calculateAverageScore = (scores: Score[]) => {
    if (scores.length === 0) return 0
    return calculateTotalScore(scores) / scores.length
  }

  if (categoriesLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="loading-spinner"></div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Scoring</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Score contestants in your assigned categories
          </p>
        </div>
        {selectedCategory && selectedContestant && (
          <div className="mt-4 sm:mt-0">
            <button
              onClick={() => setShowScoreModal(true)}
              className="btn btn-primary"
            >
              <StarIcon className="h-5 w-5 mr-2" />
              Add Score
            </button>
          </div>
        )}
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
                  onClick={() => {
                    setSelectedCategory(category.id)
                    setSelectedContestant('')
                  }}
                >
                  <div className="card-content">
                    <div className="flex items-start justify-between mb-2">
                      <h4 className="font-medium text-gray-900 dark:text-white">
                        {category.name}
                      </h4>
                      <span className={`badge ${getStatusColor(category.status)}`}>
                        {getStatusText(category.status)}
                      </span>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                      {category.description}
                    </p>
                    <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                      <span>Max Score: {category.maxScore}</span>
                      <span>{category._count?.contestants || 0} contestants</span>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      </div>

      {/* Contestant Selection */}
      {selectedCategory && (
        <div className="card">
          <div className="card-header">
            <h3 className="card-title">Select Contestant</h3>
          </div>
          <div className="card-content">
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
              {categories
                ?.find((c: Category) => c.id === selectedCategory)
                ?.contestants?.map((contestant: Contestant) => (
                  <div
                    key={contestant.id}
                    className={`card cursor-pointer transition-colors ${
                      selectedContestant === contestant.id
                        ? 'ring-2 ring-primary bg-primary/5'
                        : 'hover:bg-gray-50 dark:hover:bg-gray-700'
                    }`}
                    onClick={() => setSelectedContestant(contestant.id)}
                  >
                    <div className="card-content">
                      <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-medium">
                          {contestant.contestantNumber || contestant.name.charAt(0)}
                        </div>
                        <div>
                          <h4 className="font-medium text-gray-900 dark:text-white">
                            {contestant.name}
                          </h4>
                          <p className="text-sm text-gray-600 dark:text-gray-400">
                            {contestant.email}
                          </p>
                        </div>
                      </div>
                    </div>
                  </div>
                ))}
            </div>
          </div>
        </div>
      )}

      {/* Scoring Interface */}
      {selectedCategory && selectedContestant && (
        <div className="card">
          <div className="card-header">
            <h3 className="card-title">Scoring Interface</h3>
          </div>
          <div className="card-content">
            {scoresLoading ? (
              <div className="flex items-center justify-center py-8">
                <div className="loading-spinner"></div>
              </div>
            ) : scores && scores.length > 0 ? (
              <div className="space-y-4">
                {/* Score Summary */}
                <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-4">
                  <div className="grid grid-cols-1 md:grid-cols-3 gap-4 text-center">
                    <div>
                      <div className="text-2xl font-bold text-primary">
                        {calculateTotalScore(scores)}
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">Total Score</div>
                    </div>
                    <div>
                      <div className="text-2xl font-bold text-primary">
                        {calculateAverageScore(scores).toFixed(1)}
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">Average Score</div>
                    </div>
                    <div>
                      <div className="text-2xl font-bold text-primary">
                        {scores.length}
                      </div>
                      <div className="text-sm text-gray-600 dark:text-gray-400">Criteria Scored</div>
                    </div>
                  </div>
                </div>

                {/* Scores List */}
                <div className="space-y-3">
                  {scores.map((score: Score) => (
                    <div key={score.id} className="flex items-center justify-between p-4 bg-white dark:bg-gray-800 rounded-lg border">
                      <div className="flex-1">
                        <div className="flex items-center justify-between mb-2">
                          <h4 className="font-medium text-gray-900 dark:text-white">
                            {score.criterion.name}
                          </h4>
                          <span className="text-sm text-gray-600 dark:text-gray-400">
                            Max: {score.criterion.maxScore}
                          </span>
                        </div>
                        {score.comment && (
                          <p className="text-sm text-gray-600 dark:text-gray-400 mb-2">
                            {score.comment}
                          </p>
                        )}
                        <div className="flex items-center space-x-4 text-sm text-gray-600 dark:text-gray-400">
                          <span>Judge: {score.judge.name}</span>
                          <span>Scored: {format(new Date(score.createdAt), 'MMM dd, yyyy HH:mm')}</span>
                        </div>
                      </div>
                      <div className="flex items-center space-x-3">
                        <div className="text-right">
                          <div className="text-2xl font-bold text-primary">
                            {score.score}
                          </div>
                          <div className="text-sm text-gray-600 dark:text-gray-400">
                            / {score.criterion.maxScore}
                          </div>
                        </div>
                        <div className="flex space-x-2">
                          <button
                            onClick={() => {
                              setEditingScore(score)
                              setShowScoreModal(true)
                            }}
                            className="btn btn-outline btn-sm"
                          >
                            <PencilIcon className="h-4 w-4" />
                          </button>
                          <button
                            onClick={() => deleteScoreMutation.mutate(score.id)}
                            className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ) : (
              <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                <StarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                  No scores yet
                </h3>
                <p className="mb-4">Start scoring this contestant by adding scores for each criterion.</p>
                <button
                  onClick={() => setShowScoreModal(true)}
                  className="btn btn-primary"
                >
                  <StarIcon className="h-5 w-5 mr-2" />
                  Add First Score
                </button>
              </div>
            )}
          </div>
        </div>
      )}

      {/* Score Modal */}
      {showScoreModal && (
        <ScoreModal
          score={editingScore}
          categoryId={selectedCategory}
          contestantId={selectedContestant}
          onClose={() => {
            setShowScoreModal(false)
            setEditingScore(null)
          }}
          onSave={(data) => {
            if (editingScore) {
              updateScoreMutation.mutate({ id: editingScore.id, data })
            } else {
              submitScoreMutation.mutate(data)
            }
          }}
          isLoading={submitScoreMutation.isLoading || updateScoreMutation.isLoading}
        />
      )}
    </div>
  )
}

// Score Modal Component
interface ScoreModalProps {
  score: Score | null
  categoryId: string
  contestantId: string
  onClose: () => void
  onSave: (data: any) => void
  isLoading: boolean
}

const ScoreModal: React.FC<ScoreModalProps> = ({ score, categoryId, contestantId, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    criterionId: score?.criterion.id || '',
    score: score?.score || 0,
    comment: score?.comment || '',
  })

  const { data: criteria } = useQuery(
    ['criteria', categoryId],
    () => scoringAPI.getCriteria(categoryId).then(res => res.data),
    {
      enabled: !!categoryId,
    }
  )

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSave({
      ...formData,
      categoryId,
      contestantId,
    })
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">
          {score ? 'Edit Score' : 'Add Score'}
        </h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="label">Criterion</label>
            <select
              value={formData.criterionId}
              onChange={(e) => setFormData({ ...formData, criterionId: e.target.value })}
              className="input"
              required
            >
              <option value="">Select criterion</option>
              {criteria?.map((criterion: any) => (
                <option key={criterion.id} value={criterion.id}>
                  {criterion.name} (Max: {criterion.maxScore})
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="label">Score</label>
            <input
              type="number"
              value={formData.score}
              onChange={(e) => setFormData({ ...formData, score: parseInt(e.target.value) })}
              className="input"
              min="0"
              max={criteria?.find((c: any) => c.id === formData.criterionId)?.maxScore || 100}
              required
            />
          </div>
          <div>
            <label className="label">Comment (Optional)</label>
            <textarea
              value={formData.comment}
              onChange={(e) => setFormData({ ...formData, comment: e.target.value })}
              className="input min-h-[100px]"
              rows={3}
              placeholder="Add any comments about this score..."
            />
          </div>
          <div className="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="btn btn-outline"
              disabled={isLoading}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={isLoading}
            >
              {isLoading ? 'Saving...' : score ? 'Update' : 'Save'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default ScoringPage
