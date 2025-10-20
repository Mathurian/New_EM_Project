import { useQuery, useMutation, useQueryClient } from 'react-query'
import api from '../services/api'
import toast from 'react-hot-toast'

// Types
export interface Event {
  id: string
  name: string
  startDate: string
  endDate: string
  createdAt: string
  updatedAt: string
  contests?: Contest[]
  archivedEvents?: ArchivedEvent[]
  _count?: {
    contests: number
  }
}

export interface Contest {
  id: string
  eventId: string
  name: string
  description?: string
  createdAt: string
  updatedAt: string
  event?: Event
  categories?: Category[]
  contestants?: ContestContestant[]
  judges?: ContestJudge[]
  _count?: {
    contestants: number
    judges: number
    categories: number
  }
}

export interface Category {
  id: string
  contestId: string
  name: string
  description?: string
  scoreCap?: number
  createdAt: string
  updatedAt: string
  contest?: Contest
  contestants?: CategoryContestant[]
  judges?: CategoryJudge[]
  criteria?: Criterion[]
  scores?: Score[]
  comments?: JudgeComment[]
  certifications?: TallyMasterCertification[]
  auditorCertifications?: AuditorCertification[]
  _count?: {
    contestants: number
    judges: number
    criteria: number
    scores: number
  }
}

export interface Contestant {
  id: string
  name: string
  email?: string
  gender?: string
  pronouns?: string
  contestantNumber?: number
  bio?: string
  imagePath?: string
  createdAt: string
  updatedAt: string
  users?: User[]
  _count?: {
    contestContestants: number
    categoryContestants: number
  }
}

export interface Judge {
  id: string
  name: string
  email?: string
  gender?: string
  pronouns?: string
  bio?: string
  imagePath?: string
  isHeadJudge: boolean
  createdAt: string
  updatedAt: string
  users?: User[]
  _count?: {
    contestJudges: number
    categoryJudges: number
  }
}

export interface User {
  id: string
  name: string
  preferredName?: string
  email: string
  role: string
  gender?: string
  pronouns?: string
  judgeId?: string
  contestantId?: string
  sessionVersion: number
  createdAt: string
  updatedAt: string
  judge?: Judge
  contestant?: Contestant
}

export interface Criterion {
  id: string
  categoryId: string
  name: string
  maxScore: number
  createdAt: string
  updatedAt: string
  scores?: Score[]
}

export interface Score {
  id: string
  categoryId: string
  contestantId: string
  judgeId: string
  criterionId: string
  score: number
  createdAt: string
  updatedAt: string
  category?: Category
  contestant?: Contestant
  judge?: Judge
  criterion?: Criterion
}

export interface JudgeComment {
  id: string
  categoryId: string
  contestantId: string
  judgeId: string
  comment?: string
  createdAt: string
  category?: Category
  contestant?: Contestant
  judge?: Judge
}

export interface JudgeCertification {
  id: string
  categoryId: string
  judgeId: string
  signatureName: string
  certifiedAt: string
  category?: Category
  judge?: Judge
}

export interface TallyMasterCertification {
  id: string
  categoryId: string
  signatureName: string
  certifiedAt: string
  category?: Category
}

export interface AuditorCertification {
  id: string
  categoryId: string
  signatureName: string
  certifiedAt: string
  category?: Category
}

export interface ContestContestant {
  contestId: string
  contestantId: string
  contest?: Contest
  contestant?: Contestant
}

export interface ContestJudge {
  contestId: string
  judgeId: string
  contest?: Contest
  judge?: Judge
}

export interface CategoryContestant {
  categoryId: string
  contestantId: string
  category?: Category
  contestant?: Contestant
}

export interface CategoryJudge {
  categoryId: string
  judgeId: string
  category?: Category
  judge?: Judge
}

export interface ArchivedEvent {
  id: string
  eventId: string
  name: string
  description?: string
  startDate?: string
  endDate?: string
  archivedAt: string
  archivedById: string
  event?: Event
}

export interface ActivityLog {
  id: string
  userId?: string
  userName?: string
  userRole?: string
  action: string
  resourceType?: string
  resourceId?: string
  details?: string
  ipAddress?: string
  userAgent?: string
  logLevel: string
  createdAt: string
  user?: User
}

export interface SystemSetting {
  id: string
  settingKey: string
  settingValue: string
  description?: string
  updatedAt: string
  updatedById?: string
  updatedBy?: User
}

// API Hooks

// Events
export const useEvents = (params?: { page?: number; limit?: number; search?: string; archived?: string }) => {
  return useQuery(
    ['events', params],
    () => api.get('/events', { params }).then(res => res.data),
    {
      keepPreviousData: true,
    }
  )
}

export const useEvent = (id: string) => {
  return useQuery(
    ['event', id],
    () => api.get(`/events/${id}`).then(res => res.data.event),
    {
      enabled: !!id,
    }
  )
}

export const useCreateEvent = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (data: { name: string; startDate: string; endDate: string }) =>
      api.post('/events', data).then(res => res.data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['events'])
        toast.success('Event created successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to create event')
      },
    }
  )
}

export const useUpdateEvent = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ id, data }: { id: string; data: { name: string; startDate: string; endDate: string } }) =>
      api.put(`/events/${id}`, data).then(res => res.data),
    {
      onSuccess: (_, { id }) => {
        queryClient.invalidateQueries(['events'])
        queryClient.invalidateQueries(['event', id])
        toast.success('Event updated successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to update event')
      },
    }
  )
}

export const useDeleteEvent = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (id: string) => api.delete(`/events/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['events'])
        toast.success('Event deleted successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to delete event')
      },
    }
  )
}

export const useArchiveEvent = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (id: string) => api.post(`/events/${id}/archive`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['events'])
        toast.success('Event archived successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to archive event')
      },
    }
  )
}

export const useRestoreEvent = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (id: string) => api.post(`/events/${id}/restore`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['events'])
        toast.success('Event restored successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to restore event')
      },
    }
  )
}

// Contests
export const useContests = (eventId: string, params?: { page?: number; limit?: number; search?: string }) => {
  return useQuery(
    ['contests', eventId, params],
    () => api.get(`/contests/event/${eventId}`, { params }).then(res => res.data),
    {
      enabled: !!eventId,
      keepPreviousData: true,
    }
  )
}

export const useContest = (id: string) => {
  return useQuery(
    ['contest', id],
    () => api.get(`/contests/${id}`).then(res => res.data.contest),
    {
      enabled: !!id,
    }
  )
}

export const useCreateContest = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ eventId, data }: { eventId: string; data: { name: string; description?: string } }) =>
      api.post(`/contests/event/${eventId}`, data).then(res => res.data),
    {
      onSuccess: (_, { eventId }) => {
        queryClient.invalidateQueries(['contests', eventId])
        queryClient.invalidateQueries(['event', eventId])
        toast.success('Contest created successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to create contest')
      },
    }
  )
}

export const useUpdateContest = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ id, data }: { id: string; data: { name: string; description?: string } }) =>
      api.put(`/contests/${id}`, data).then(res => res.data),
    {
      onSuccess: (_, { id }) => {
        queryClient.invalidateQueries(['contests'])
        queryClient.invalidateQueries(['contest', id])
        toast.success('Contest updated successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to update contest')
      },
    }
  )
}

export const useDeleteContest = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (id: string) => api.delete(`/contests/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['contests'])
        toast.success('Contest deleted successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to delete contest')
      },
    }
  )
}

// Categories
export const useCategories = (contestId: string, params?: { page?: number; limit?: number; search?: string }) => {
  return useQuery(
    ['categories', contestId, params],
    () => api.get(`/categories/contest/${contestId}`, { params }).then(res => res.data),
    {
      enabled: !!contestId,
      keepPreviousData: true,
    }
  )
}

export const useCategory = (id: string) => {
  return useQuery(
    ['category', id],
    () => api.get(`/categories/${id}`).then(res => res.data.category),
    {
      enabled: !!id,
    }
  )
}

export const useCreateCategory = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ contestId, data }: { contestId: string; data: { name: string; description?: string; scoreCap?: number } }) =>
      api.post(`/categories/contest/${contestId}`, data).then(res => res.data),
    {
      onSuccess: (_, { contestId }) => {
        queryClient.invalidateQueries(['categories', contestId])
        queryClient.invalidateQueries(['contest', contestId])
        toast.success('Category created successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to create category')
      },
    }
  )
}

export const useUpdateCategory = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ id, data }: { id: string; data: { name: string; description?: string; scoreCap?: number } }) =>
      api.put(`/categories/${id}`, data).then(res => res.data),
    {
      onSuccess: (_, { id }) => {
        queryClient.invalidateQueries(['categories'])
        queryClient.invalidateQueries(['category', id])
        toast.success('Category updated successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to update category')
      },
    }
  )
}

export const useDeleteCategory = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (id: string) => api.delete(`/categories/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['categories'])
        toast.success('Category deleted successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to delete category')
      },
    }
  )
}

// Users
export const useUsers = (params?: { page?: number; limit?: number; search?: string; role?: string }) => {
  return useQuery(
    ['users', params],
    () => api.get('/users', { params }).then(res => res.data),
    {
      keepPreviousData: true,
    }
  )
}

export const useContestants = (params?: { page?: number; limit?: number; search?: string }) => {
  return useQuery(
    ['contestants', params],
    () => api.get('/users/contestants', { params }).then(res => res.data),
    {
      keepPreviousData: true,
    }
  )
}

export const useJudges = (params?: { page?: number; limit?: number; search?: string }) => {
  return useQuery(
    ['judges', params],
    () => api.get('/users/judges', { params }).then(res => res.data),
    {
      keepPreviousData: true,
    }
  )
}

export const useCreateUser = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (data: any) => api.post('/users', data).then(res => res.data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['users'])
        queryClient.invalidateQueries(['contestants'])
        queryClient.invalidateQueries(['judges'])
        toast.success('User created successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to create user')
      },
    }
  )
}

export const useUpdateUser = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ id, data }: { id: string; data: any }) =>
      api.put(`/users/${id}`, data).then(res => res.data),
    {
      onSuccess: (_, { id }) => {
        queryClient.invalidateQueries(['users'])
        queryClient.invalidateQueries(['contestants'])
        queryClient.invalidateQueries(['judges'])
        queryClient.invalidateQueries(['user', id])
        toast.success('User updated successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to update user')
      },
    }
  )
}

export const useDeleteUser = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (id: string) => api.delete(`/users/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['users'])
        queryClient.invalidateQueries(['contestants'])
        queryClient.invalidateQueries(['judges'])
        toast.success('User deleted successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to delete user')
      },
    }
  )
}

// Scoring
export const useScores = (categoryId: string, contestantId: string) => {
  return useQuery(
    ['scores', categoryId, contestantId],
    () => api.get(`/scoring/category/${categoryId}/contestant/${contestantId}`).then(res => res.data),
    {
      enabled: !!categoryId && !!contestantId,
    }
  )
}

export const useSubmitScores = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ categoryId, contestantId, data }: { categoryId: string; contestantId: string; data: any }) =>
      api.post(`/scoring/category/${categoryId}/contestant/${contestantId}`, data).then(res => res.data),
    {
      onSuccess: (_, { categoryId, contestantId }) => {
        queryClient.invalidateQueries(['scores', categoryId, contestantId])
        queryClient.invalidateQueries(['category', categoryId])
        toast.success('Scores submitted successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to submit scores')
      },
    }
  )
}

export const useCertifyScores = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ categoryId, data }: { categoryId: string; data: { signatureName: string } }) =>
      api.post(`/scoring/category/${categoryId}/certify`, data).then(res => res.data),
    {
      onSuccess: (_, { categoryId }) => {
        queryClient.invalidateQueries(['scores'])
        queryClient.invalidateQueries(['category', categoryId])
        toast.success('Scores certified successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to certify scores')
      },
    }
  )
}

export const useCertifyTotals = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ categoryId, data }: { categoryId: string; data: { signatureName: string } }) =>
      api.post(`/scoring/category/${categoryId}/certify-totals`, data).then(res => res.data),
    {
      onSuccess: (_, { categoryId }) => {
        queryClient.invalidateQueries(['scores'])
        queryClient.invalidateQueries(['category', categoryId])
        toast.success('Totals certified successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to certify totals')
      },
    }
  )
}

export const useFinalCertification = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    ({ categoryId, data }: { categoryId: string; data: { signatureName: string } }) =>
      api.post(`/scoring/category/${categoryId}/final-certification`, data).then(res => res.data),
    {
      onSuccess: (_, { categoryId }) => {
        queryClient.invalidateQueries(['scores'])
        queryClient.invalidateQueries(['category', categoryId])
        toast.success('Final certification completed successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to complete final certification')
      },
    }
  )
}

export const useCertificationStatus = (categoryId: string) => {
  return useQuery(
    ['certification-status', categoryId],
    () => api.get(`/scoring/category/${categoryId}/certification-status`).then(res => res.data),
    {
      enabled: !!categoryId,
    }
  )
}

// Admin
export const useSystemStats = () => {
  return useQuery(
    ['system-stats'],
    () => api.get('/admin/stats').then(res => res.data.stats),
    {
      refetchInterval: 30000, // Refetch every 30 seconds
    }
  )
}

export const useActivityLogs = (params?: { page?: number; limit?: number; search?: string; logLevel?: string; action?: string; resourceType?: string; startDate?: string; endDate?: string }) => {
  return useQuery(
    ['activity-logs', params],
    () => api.get('/admin/logs', { params }).then(res => res.data),
    {
      keepPreviousData: true,
    }
  )
}

export const useSystemSettings = () => {
  return useQuery(
    ['system-settings'],
    () => api.get('/admin/settings').then(res => res.data.settings)
  )
}

export const useUpdateSystemSettings = () => {
  const queryClient = useQueryClient()
  
  return useMutation(
    (settings: Record<string, string>) =>
      api.put('/admin/settings', { settings }).then(res => res.data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['system-settings'])
        toast.success('System settings updated successfully!')
      },
      onError: (error: any) => {
        toast.error(error.response?.data?.error || 'Failed to update system settings')
      },
    }
  )
}

export const useActiveUsers = () => {
  return useQuery(
    ['active-users'],
    () => api.get('/admin/active-users').then(res => res.data.users),
    {
      refetchInterval: 30000, // Refetch every 30 seconds
    }
  )
}
