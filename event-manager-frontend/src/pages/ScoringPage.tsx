import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import { Search, Gavel, CheckCircle, XCircle, Clock } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { toast } from 'react-hot-toast'
import { useAuthStore } from '../../stores/authStore'

export const ScoringPage = () => {
  const { user } = useAuthStore()
  const [searchTerm, setSearchTerm] = useState('')
  const [selectedSubcategory, setSelectedSubcategory] = useState('')
  const queryClient = useQueryClient()

  // Get judge's assigned subcategories
  const { data: subcategories, isLoading: subcategoriesLoading } = useQuery(
    'judge-subcategories',
    async () => {
      const response = await api.get('/scoring/assignments')
      return response.data
    },
    {
      enabled: user?.role === 'judge'
    }
  )

  // Get contestants for selected subcategory
  const { data: contestants, isLoading: contestantsLoading } = useQuery(
    ['subcategory-contestants', selectedSubcategory],
    async () => {
      if (!selectedSubcategory) return { data: [] }
      const response = await api.get(`/scoring/subcategory/${selectedSubcategory}`)
      return response.data
    },
    {
      enabled: !!selectedSubcategory
    }
  )

  // Submit score mutation
  const submitScoreMutation = useMutation(
    async (scoreData: any) => {
      await api.post('/scoring/submit', scoreData)
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['subcategory-contestants', selectedSubcategory])
        toast.success('Score submitted successfully')
      },
      onError: () => {
        toast.error('Failed to submit score')
      }
    }
  )

  // Sign scores mutation
  const signScoresMutation = useMutation(
    async (subcategoryId: string) => {
      await api.post('/scoring/sign', { subcategory_id: subcategoryId })
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['subcategory-contestants', selectedSubcategory])
        toast.success('Scores signed successfully')
      },
      onError: () => {
        toast.error('Failed to sign scores')
      }
    }
  )

  const handleScoreSubmit = (criterionId: string, contestantId: string, score: number) => {
    submitScoreMutation.mutate({
      criterion_id: criterionId,
      contestant_id: contestantId,
      score: score
    })
  }

  const handleSignScores = () => {
    if (selectedSubcategory) {
      signScoresMutation.mutate(selectedSubcategory)
    }
  }

  if (subcategoriesLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Scoring</h1>
          <p className="text-muted-foreground mt-2">
            Score contestants and manage your assignments
          </p>
        </div>
      </div>

      {/* Subcategory Selection */}
      <Card>
        <CardHeader>
          <CardTitle>Select Subcategory</CardTitle>
          <CardDescription>
            Choose a subcategory to score contestants
          </CardDescription>
        </CardHeader>
        <CardContent>
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            {subcategories?.map((subcategory: any) => (
              <Button
                key={subcategory.id}
                variant={selectedSubcategory === subcategory.id ? 'default' : 'outline'}
                onClick={() => setSelectedSubcategory(subcategory.id)}
                className="h-auto p-4 flex flex-col items-start"
              >
                <div className="font-medium">{subcategory.name}</div>
                <div className="text-sm text-muted-foreground mt-1">
                  {subcategory.category_name} - {subcategory.contest_name}
                </div>
                <div className="flex items-center mt-2">
                  <Badge variant="outline" className="text-xs">
                    {subcategory.contestants_count || 0} contestants
                  </Badge>
                </div>
              </Button>
            ))}
          </div>
        </CardContent>
      </Card>

      {/* Scoring Interface */}
      {selectedSubcategory && (
        <Card>
          <CardHeader>
            <div className="flex items-center justify-between">
              <div>
                <CardTitle>Score Contestants</CardTitle>
                <CardDescription>
                  Score each contestant for the selected subcategory
                </CardDescription>
              </div>
              <Button
                onClick={handleSignScores}
                loading={signScoresMutation.isLoading}
                className="flex items-center"
              >
                <CheckCircle className="h-4 w-4 mr-2" />
                Sign All Scores
              </Button>
            </div>
          </CardHeader>
          <CardContent>
            {contestantsLoading ? (
              <div className="flex items-center justify-center h-32">
                <LoadingSpinner />
              </div>
            ) : (
              <div className="space-y-6">
                {contestants?.map((contestant: any) => (
                  <div key={contestant.id} className="border rounded-lg p-4">
                    <div className="flex items-center justify-between mb-4">
                      <div>
                        <h3 className="font-medium">{contestant.name}</h3>
                        <p className="text-sm text-muted-foreground">
                          Contestant #{contestant.contestant_number}
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        {contestant.is_signed ? (
                          <Badge variant="default" className="flex items-center">
                            <CheckCircle className="h-3 w-3 mr-1" />
                            Signed
                          </Badge>
                        ) : (
                          <Badge variant="outline" className="flex items-center">
                            <Clock className="h-3 w-3 mr-1" />
                            Pending
                          </Badge>
                        )}
                      </div>
                    </div>
                    
                    {/* Criteria Scoring */}
                    <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                      {contestant.criteria?.map((criterion: any) => (
                        <div key={criterion.id} className="space-y-2">
                          <label className="text-sm font-medium">
                            {criterion.name}
                          </label>
                          <div className="flex items-center space-x-2">
                            <Input
                              type="number"
                              min="0"
                              max={criterion.max_score}
                              step="0.1"
                              placeholder="0"
                              defaultValue={criterion.current_score || ''}
                              onChange={(e) => {
                                const score = parseFloat(e.target.value)
                                if (!isNaN(score)) {
                                  handleScoreSubmit(criterion.id, contestant.id, score)
                                }
                              }}
                              className="flex-1"
                            />
                            <span className="text-sm text-muted-foreground">
                              / {criterion.max_score}
                            </span>
                          </div>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            )}
          </CardContent>
        </Card>
      )}

      {/* No subcategories assigned */}
      {!subcategoriesLoading && (!subcategories || subcategories.length === 0) && (
        <Card>
          <CardContent className="text-center py-12">
            <Gavel className="h-12 w-12 text-muted-foreground mx-auto mb-4" />
            <h3 className="text-lg font-medium text-foreground mb-2">No assignments found</h3>
            <p className="text-muted-foreground">
              You haven't been assigned to any subcategories yet.
            </p>
          </CardContent>
        </Card>
      )}
    </div>
  )
}