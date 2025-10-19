import { useState, useEffect } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { api } from '../lib/api'
import { useAuthStore } from '../stores/authStore'
import { 
  Target, 
  Users, 
  Star, 
  Save, 
  CheckCircle,
  AlertCircle,
  Clock,
  Trophy
} from 'lucide-react'
import { formatScore } from '../lib/utils'
import { LoadingSpinner } from '../components/ui/LoadingSpinner'
import toast from 'react-hot-toast'

interface Contestant {
  id: string
  name: string
  contestant_number?: number
  image_url?: string
}

interface Criterion {
  id: string
  name: string
  description?: string
  max_score: number
  order_index: number
}

interface Subcategory {
  id: string
  name: string
  score_cap?: number
  criteria: Criterion[]
  contestants: Contestant[]
}

interface Score {
  id?: string
  score: number
  comments?: string
  criterion_id: string
  contestant_id: string
  judge_id: string
}

export function ScoringPage() {
  const { user } = useAuthStore()
  const [selectedContest, setSelectedContest] = useState<string>('')
  const [selectedSubcategory, setSelectedSubcategory] = useState<string>('')
  const [selectedContestant, setSelectedContestant] = useState<string>('')
  const [scores, setScores] = useState<Record<string, Score>>({})
  const [isSubmitting, setIsSubmitting] = useState(false)
  const queryClient = useQueryClient()

  // Fetch contests for the judge
  const { data: contests } = useQuery(
    'judge-contests',
    async () => {
      const response = await api.get('/contests?status=active')
      return response.data.data
    }
  )

  // Fetch subcategories for selected contest
  const { data: subcategories } = useQuery(
    ['subcategories', selectedContest],
    async () => {
      if (!selectedContest) return []
      const response = await api.get(`/contests/${selectedContest}`)
      return response.data.categories?.flatMap((cat: any) => cat.subcategories) || []
    },
    { enabled: !!selectedContest }
  )

  // Fetch contestant details for selected subcategory
  const { data: subcategoryData } = useQuery(
    ['subcategory-details', selectedSubcategory],
    async () => {
      if (!selectedSubcategory) return null
      const response = await api.get(`/scoring/subcategory/${selectedSubcategory}`)
      return response.data
    },
    { enabled: !!selectedSubcategory }
  )

  // Fetch existing scores for the selected contestant
  const { data: existingScores } = useQuery(
    ['existing-scores', selectedContestant, selectedSubcategory],
    async () => {
      if (!selectedContestant || !selectedSubcategory) return []
      const response = await api.get(`/scoring/subcategory/${selectedSubcategory}?group_by=contestant`)
      const contestantScores = response.data.find((group: any) => group.contestant_id === selectedContestant)
      return contestantScores?.scores || []
    },
    { enabled: !!selectedContestant && !!selectedSubcategory }
  )

  // Initialize scores when existing scores are loaded
  useEffect(() => {
    if (existingScores && selectedContestant) {
      const initialScores: Record<string, Score> = {}
      existingScores.forEach((score: any) => {
        initialScores[score.criterion_id] = {
          id: score.id,
          score: score.score,
          comments: score.comments || '',
          criterion_id: score.criterion_id,
          contestant_id: selectedContestant,
          judge_id: user?.id || ''
        }
      })
      setScores(initialScores)
    }
  }, [existingScores, selectedContestant, user?.id])

  const submitScoreMutation = useMutation(
    async (scoreData: Score) => {
      if (scoreData.id) {
        // Update existing score
        await api.put(`/scoring/${scoreData.id}`, {
          score: scoreData.score,
          comments: scoreData.comments
        })
      } else {
        // Create new score
        await api.post('/scoring/submit', {
          subcategory_id: selectedSubcategory,
          contestant_id: scoreData.contestant_id,
          criterion_id: scoreData.criterion_id,
          score: scoreData.score,
          comments: scoreData.comments
        })
      }
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['existing-scores', selectedContestant, selectedSubcategory])
        toast.success('Score saved successfully')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to save score')
      }
    }
  )

  const handleScoreChange = (criterionId: string, value: number) => {
    setScores(prev => ({
      ...prev,
      [criterionId]: {
        ...prev[criterionId],
        score: value,
        criterion_id: criterionId,
        contestant_id: selectedContestant,
        judge_id: user?.id || ''
      }
    }))
  }

  const handleCommentsChange = (criterionId: string, comments: string) => {
    setScores(prev => ({
      ...prev,
      [criterionId]: {
        ...prev[criterionId],
        comments,
        criterion_id: criterionId,
        contestant_id: selectedContestant,
        judge_id: user?.id || ''
      }
    }))
  }

  const handleSaveScore = async (criterionId: string) => {
    const score = scores[criterionId]
    if (!score || score.score < 0) return

    setIsSubmitting(true)
    try {
      await submitScoreMutation.mutateAsync(score)
    } finally {
      setIsSubmitting(false)
    }
  }

  const calculateTotalScore = () => {
    return Object.values(scores).reduce((total, score) => total + (score.score || 0), 0)
  }

  const calculateMaxScore = () => {
    return subcategoryData?.criteria?.reduce((total: number, criterion: Criterion) => total + criterion.max_score, 0) || 0
  }

  const getContestant = () => {
    return subcategoryData?.contestants?.find((c: Contestant) => c.id === selectedContestant)
  }

  const getCriterion = (criterionId: string) => {
    return subcategoryData?.criteria?.find((c: Criterion) => c.id === criterionId)
  }

  if (!user || user.role !== 'judge') {
    return (
      <div className="bg-white rounded-lg shadow p-12 text-center">
        <AlertCircle className="h-12 w-12 text-red-500 mx-auto mb-4" />
        <h3 className="text-lg font-medium text-gray-900 mb-2">Access Denied</h3>
        <p className="text-gray-500">Only judges can access the scoring interface.</p>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900">Scoring Interface</h1>
          <p className="text-gray-600">Score contestants for your assigned subcategories</p>
        </div>
        <div className="flex items-center space-x-2 text-sm text-gray-500">
          <Clock className="h-4 w-4" />
          <span>Auto-save enabled</span>
        </div>
      </div>

      {/* Contest and Subcategory Selection */}
      <div className="bg-white rounded-lg shadow p-6">
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Select Contest
            </label>
            <select
              value={selectedContest}
              onChange={(e) => {
                setSelectedContest(e.target.value)
                setSelectedSubcategory('')
                setSelectedContestant('')
                setScores({})
              }}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
            >
              <option value="">Choose a contest...</option>
              {contests?.map((contest: any) => (
                <option key={contest.id} value={contest.id}>
                  {contest.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Select Subcategory
            </label>
            <select
              value={selectedSubcategory}
              onChange={(e) => {
                setSelectedSubcategory(e.target.value)
                setSelectedContestant('')
                setScores({})
              }}
              disabled={!selectedContest}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
            >
              <option value="">Choose a subcategory...</option>
              {subcategories?.map((sub: any) => (
                <option key={sub.id} value={sub.id}>
                  {sub.name}
                </option>
              ))}
            </select>
          </div>

          <div>
            <label className="block text-sm font-medium text-gray-700 mb-2">
              Select Contestant
            </label>
            <select
              value={selectedContestant}
              onChange={(e) => {
                setSelectedContestant(e.target.value)
                setScores({})
              }}
              disabled={!selectedSubcategory}
              className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent disabled:bg-gray-100"
            >
              <option value="">Choose a contestant...</option>
              {subcategoryData?.contestants?.map((contestant: Contestant) => (
                <option key={contestant.id} value={contestant.id}>
                  {contestant.contestant_number ? `#${contestant.contestant_number} - ` : ''}{contestant.name}
                </option>
              ))}
            </select>
          </div>
        </div>
      </div>

      {/* Scoring Interface */}
      {selectedContestant && subcategoryData && (
        <div className="bg-white rounded-lg shadow">
          {/* Contestant Header */}
          <div className="p-6 border-b border-gray-200">
            <div className="flex items-center justify-between">
              <div className="flex items-center space-x-4">
                <div className="h-16 w-16 bg-blue-100 rounded-full flex items-center justify-center">
                  <Users className="h-8 w-8 text-blue-600" />
                </div>
                <div>
                  <h2 className="text-xl font-semibold text-gray-900">
                    {getContestant()?.name}
                  </h2>
                  {getContestant()?.contestant_number && (
                    <p className="text-sm text-gray-500">
                      Contestant #{getContestant()?.contestant_number}
                    </p>
                  )}
                </div>
              </div>
              <div className="text-right">
                <div className="text-2xl font-bold text-gray-900">
                  {formatScore(calculateTotalScore(), calculateMaxScore())}
                </div>
                <div className="text-sm text-gray-500">Total Score</div>
              </div>
            </div>
          </div>

          {/* Scoring Form */}
          <div className="p-6">
            <div className="space-y-6">
              {subcategoryData.criteria?.map((criterion: Criterion) => (
                <div key={criterion.id} className="border border-gray-200 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-3">
                    <div>
                      <h3 className="font-medium text-gray-900">{criterion.name}</h3>
                      {criterion.description && (
                        <p className="text-sm text-gray-600">{criterion.description}</p>
                      )}
                    </div>
                    <div className="text-sm text-gray-500">
                      Max: {criterion.max_score} points
                    </div>
                  </div>

                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Score
                      </label>
                      <div className="flex items-center space-x-2">
                        <input
                          type="number"
                          min="0"
                          max={criterion.max_score}
                          step="0.1"
                          value={scores[criterion.id]?.score || ''}
                          onChange={(e) => handleScoreChange(criterion.id, parseFloat(e.target.value) || 0)}
                          className="flex-1 px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                          placeholder="0"
                        />
                        <span className="text-sm text-gray-500">
                          / {criterion.max_score}
                        </span>
                      </div>
                    </div>

                    <div>
                      <label className="block text-sm font-medium text-gray-700 mb-2">
                        Comments (Optional)
                      </label>
                      <textarea
                        value={scores[criterion.id]?.comments || ''}
                        onChange={(e) => handleCommentsChange(criterion.id, e.target.value)}
                        rows={2}
                        className="w-full px-3 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                        placeholder="Add comments about this score..."
                      />
                    </div>
                  </div>

                  <div className="mt-3 flex items-center justify-between">
                    <div className="flex items-center space-x-2">
                      {scores[criterion.id]?.id && (
                        <CheckCircle className="h-4 w-4 text-green-500" />
                      )}
                      <span className="text-sm text-gray-500">
                        {scores[criterion.id]?.id ? 'Saved' : 'Not saved'}
                      </span>
                    </div>
                    <button
                      onClick={() => handleSaveScore(criterion.id)}
                      disabled={isSubmitting || !scores[criterion.id]?.score}
                      className="bg-blue-600 text-white px-4 py-2 rounded-lg hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                    >
                      <Save className="h-4 w-4" />
                      <span>Save Score</span>
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>
        </div>
      )}

      {/* Empty State */}
      {!selectedContestant && (
        <div className="bg-white rounded-lg shadow p-12 text-center">
          <Target className="h-12 w-12 text-gray-300 mx-auto mb-4" />
          <h3 className="text-lg font-medium text-gray-900 mb-2">Ready to Score</h3>
          <p className="text-gray-500">
            Select a contest, subcategory, and contestant to begin scoring.
          </p>
        </div>
      )}
    </div>
  )
}