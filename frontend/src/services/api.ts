import axios from 'axios'

const API_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000'

export const api = axios.create({
  baseURL: `${API_URL}/api`,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('token')
    if (token) {
      config.headers.Authorization = `Bearer ${token}`
    }
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor
api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('token')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// API endpoints
export const authAPI = {
  login: (email: string, password: string) => api.post('/auth/login', { email, password }),
  getProfile: () => api.get('/auth/profile'),
  updateProfile: (data: any) => api.put('/auth/profile', data),
  changePassword: (currentPassword: string, newPassword: string) => 
    api.put('/auth/change-password', { currentPassword, newPassword }),
}

export const eventsAPI = {
  getAll: () => api.get('/events'),
  getById: (id: string) => api.get(`/events/${id}`),
  create: (data: any) => api.post('/events', data),
  update: (id: string, data: any) => api.put(`/events/${id}`, data),
  delete: (id: string) => api.delete(`/events/${id}`),
}

export const contestsAPI = {
  getAll: () => api.get('/contests'),
  getByEvent: (eventId: string) => api.get(`/contests/event/${eventId}`),
  getById: (id: string) => api.get(`/contests/${id}`),
  create: (data: any) => api.post('/contests', data),
  update: (id: string, data: any) => api.put(`/contests/${id}`, data),
  delete: (id: string) => api.delete(`/contests/${id}`),
}

export const categoriesAPI = {
  getAll: () => api.get('/categories'),
  getByContest: (contestId: string) => api.get(`/categories/contest/${contestId}`),
  getById: (id: string) => api.get(`/categories/${id}`),
  create: (data: any) => api.post('/categories', data),
  update: (id: string, data: any) => api.put(`/categories/${id}`, data),
  delete: (id: string) => api.delete(`/categories/${id}`),
}

export const scoringAPI = {
  getScores: (categoryId: string, contestantId: string) => 
    api.get(`/scoring/category/${categoryId}/contestant/${contestantId}`),
  submitScore: (data: any) => api.post('/scoring/submit', data),
  updateScore: (id: string, data: any) => api.put(`/scoring/${id}`, data),
  deleteScore: (id: string) => api.delete(`/scoring/${id}`),
  getCategories: () => api.get('/scoring/categories'),
  getCriteria: (categoryId: string) => api.get(`/scoring/criteria/${categoryId}`),
  certifyScores: (categoryId: string, data: any) => 
    api.post(`/scoring/category/${categoryId}/certify`, data),
  certifyTotals: (categoryId: string, data: any) => 
    api.post(`/scoring/category/${categoryId}/certify-totals`, data),
  finalCertification: (categoryId: string, data: any) => 
    api.post(`/scoring/category/${categoryId}/final-certification`, data),
}

export const resultsAPI = {
  getCategoryResults: (categoryId: string) => api.get(`/results/category/${categoryId}`),
  getCategories: () => api.get('/results/categories'),
  getContestantResults: (contestantId: string) => api.get(`/results/contestant/${contestantId}`),
}

export const usersAPI = {
  getAll: (params?: any) => api.get('/users', { params }),
  getById: (id: string) => api.get(`/users/${id}`),
  create: (data: any) => api.post('/users', data),
  update: (id: string, data: any) => api.put(`/users/${id}`, data),
  delete: (id: string) => api.delete(`/users/${id}`),
}

export const adminAPI = {
  getStats: () => api.get('/admin/stats'),
  getLogs: (params?: any) => api.get('/admin/logs', { params }),
  getActiveUsers: () => api.get('/admin/active-users'),
  getSettings: () => api.get('/admin/settings'),
  updateSettings: (data: any) => api.put('/admin/settings', data),
  getUsers: () => api.get('/admin/users'),
  getEvents: () => api.get('/admin/events'),
  getContests: () => api.get('/admin/contests'),
  getCategories: () => api.get('/admin/categories'),
  getScores: () => api.get('/admin/scores'),
  getActivityLogs: () => api.get('/admin/activity-logs'),
  getAuditLogs: (params?: any) => api.get('/admin/audit-logs', { params }),
  exportAuditLogs: (params?: any) => api.post('/admin/export-audit-logs', params),
  testConnection: (type: string) => api.post(`/admin/test/${type}`),
}

export const uploadAPI = {
  uploadFile: (file: File, type: string) => {
    const formData = new FormData()
    formData.append('file', file)
    formData.append('type', type)
    return api.post('/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },
  uploadFileData: (fileData: FormData, type: string = 'OTHER') => {
    fileData.append('type', type)
    return api.post('/upload', fileData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },
  getFiles: () => api.get('/upload/files'),
  deleteFile: (fileId: string) => api.delete(`/upload/files/${fileId}`),
}

export const emailAPI = {
  sendEmail: (data: any) => api.post('/email/send', data),
}

// Additional API modules
export const archiveAPI = {
  getAll: () => api.get('/archive'),
  getActiveEvents: () => api.get('/archive/active-events'),
  archive: (eventId: string, reason: string) => api.post(`/archive/events/${eventId}`, { reason }),
  restore: (eventId: string) => api.post(`/archive/events/${eventId}/restore`),
  delete: (eventId: string) => api.delete(`/archive/events/${eventId}`),
  archiveEvent: (eventId: string) => api.post(`/archive/events/${eventId}`),
  restoreEvent: (eventId: string) => api.post(`/archive/events/${eventId}/restore`),
}

export const backupAPI = {
  getAll: () => api.get('/backup'),
  create: (data: any) => api.post('/backup', data),
  restore: (backupIdOrFile: string | File) => {
    if (typeof backupIdOrFile === 'string') {
      return api.post(`/backup/${backupIdOrFile}/restore`)
    } else {
      const formData = new FormData()
      formData.append('file', backupIdOrFile)
      return api.post('/backup/restore-from-file', formData, {
        headers: {
          'Content-Type': 'multipart/form-data',
        },
      })
    }
  },
  download: async (backupId: string) => {
    const response = await api.get(`/backup/${backupId}/download`, { responseType: 'blob' })
    return response.data
  },
  delete: (id: string) => api.delete(`/backup/${id}`),
}

export const settingsAPI = {
  getAll: () => api.get('/settings'),
  update: (data: any) => api.put('/settings', data),
  test: (type: string) => api.post(`/settings/test/${type}`),
}

export const assignmentsAPI = {
  getAll: () => api.get('/assignments'),
  create: (data: any) => api.post('/assignments', data),
  update: (id: string, data: any) => api.put(`/assignments/${id}`, data),
  delete: (id: string) => api.delete(`/assignments/${id}`),
  getJudges: () => api.get('/assignments/judges'),
  getCategories: () => api.get('/assignments/categories'),
}

export const auditorAPI = {
  getStats: () => api.get('/auditor/stats'),
  getAuditLogs: (params?: any) => api.get('/auditor/logs', { params }),
  exportAuditLogs: (params?: any) => api.post('/auditor/export', params),
  getPendingAudits: () => api.get('/auditor/pending'),
  getCompletedAudits: () => api.get('/auditor/completed'),
  finalCertification: (data: any) => api.post('/auditor/final-certification', data),
  rejectAudit: (auditId: string, reason: string) => api.post(`/auditor/reject/${auditId}`, { reason }),
}

export const boardAPI = {
  getStats: () => api.get('/board/stats'),
  getCertifications: () => api.get('/board/certifications'),
  approveCertification: (id: string) => api.post(`/board/certifications/${id}/approve`),
  rejectCertification: (id: string, reason: string) => api.post(`/board/certifications/${id}/reject`, { reason }),
  getCertificationStatus: () => api.get('/board/certification-status'),
  getEmceeScripts: () => api.get('/board/emcee-scripts'),
}

export const tallyMasterAPI = {
  getStats: () => api.get('/tally-master/stats'),
  getCertifications: () => api.get('/tally-master/certifications'),
  certifyScores: (categoryId: string) => api.post(`/tally-master/certify/${categoryId}`),
  getCertificationQueue: () => api.get('/tally-master/certification-queue'),
  getPendingCertifications: () => api.get('/tally-master/pending-certifications'),
  certifyTotals: (data: any) => api.post('/tally-master/certify-totals', data),
}

export default api
