#!/bin/bash

# Complete API Service Fix for Remote Server
# This script will replace the incomplete API service with the corrected version

echo "🔧 Fixing TypeScript errors on remote server..."
echo "📋 This will replace the incomplete API service with the corrected version"

# Navigate to the frontend directory
cd /var/www/event-manager/frontend || {
    echo "❌ Frontend directory not found. Please run setup.sh first."
    exit 1
}

# Backup the current API service file
echo "📦 Backing up current API service file..."
cp src/services/api.ts src/services/api.ts.backup

# Create the corrected API service file
echo "🔨 Creating corrected API service file..."
cat > src/services/api.ts << 'EOF'
import axios from 'axios'

const api = axios.create({
  baseURL: import.meta.env.VITE_API_URL || '/api',
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor to add auth token
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

// Response interceptor to handle errors
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

export const eventsAPI = {
  getAll: () => api.get('/events'),
  getById: (id: string) => api.get(`/events/${id}`),
  create: (data: any) => api.post('/events', data),
  update: (id: string, data: any) => api.put(`/events/${id}`, data),
  delete: (id: string) => api.delete(`/events/${id}`),
}

export const contestsAPI = {
  getAll: async (): Promise<{ data: any[] }> => {
    // Get all events first, then get contests for each event
    const events = await api.get('/events')
    const allContests: any[] = []
    for (const event of events.data) {
      const contests = await api.get(`/contests/event/${event.id}`)
      allContests.push(...contests.data)
    }
    return { data: allContests }
  },
  getByEvent: (eventId: string) => api.get(`/contests/event/${eventId}`),
  getById: (id: string) => api.get(`/contests/${id}`),
  create: (eventIdOrData: string | any, data?: any) => {
    if (typeof eventIdOrData === 'string') {
      // Called with (eventId, data)
      return api.post(`/contests/event/${eventIdOrData}`, data)
    } else {
      // Called with (data) - extract eventId from data
      const { eventId, ...contestData } = eventIdOrData
      return api.post(`/contests/event/${eventId}`, contestData)
    }
  },
  update: (id: string, data: any) => api.put(`/contests/${id}`, data),
  delete: (id: string) => api.delete(`/contests/${id}`),
}

export const categoriesAPI = {
  getAll: () => api.get('/categories'),
  getByContest: (contestId: string) => api.get(`/categories/contest/${contestId}`),
  getById: (id: string) => api.get(`/categories/${id}`),
  create: (contestIdOrData: string | any, data?: any) => {
    if (typeof contestIdOrData === 'string') {
      // Called with (contestId, data)
      return api.post(`/categories/contest/${contestIdOrData}`, data)
    } else {
      // Called with (data) - extract contestId from data
      const { contestId, ...categoryData } = contestIdOrData
      return api.post(`/categories/contest/${contestId}`, categoryData)
    }
  },
  update: (id: string, data: any) => api.put(`/categories/${id}`, data),
  delete: (id: string) => api.delete(`/categories/${id}`),
}

export const scoringAPI = {
  getScores: (categoryId: string, contestantId: string) => api.get(`/scoring/category/${categoryId}/contestant/${contestantId}`),
  submitScore: (categoryIdOrData: string | any, contestantIdOrData?: string, data?: any) => {
    if (typeof categoryIdOrData === 'string' && typeof contestantIdOrData === 'string') {
      // Called with (categoryId, contestantId, data)
      return api.post(`/scoring/category/${categoryIdOrData}/contestant/${contestantIdOrData}`, data)
    } else {
      // Called with (scoreData) - extract categoryId and contestantId from data
      const { categoryId, contestantId, ...scoreData } = categoryIdOrData
      return api.post(`/scoring/category/${categoryId}/contestant/${contestantId}`, scoreData)
    }
  },
  updateScore: (scoreId: string, data: any) => api.put(`/scoring/${scoreId}`, data),
  deleteScore: (scoreId: string) => api.delete(`/scoring/${scoreId}`),
  certifyScores: (categoryId: string) => api.post(`/scoring/category/${categoryId}/certify`),
  certifyTotals: (categoryId: string) => api.post(`/scoring/category/${categoryId}/certify-totals`),
  finalCertification: (categoryId: string) => api.post(`/scoring/category/${categoryId}/final-certification`),
  getCategories: () => api.get('/scoring/categories'),
  getCriteria: (categoryId: string) => api.get(`/scoring/category/${categoryId}/criteria`),
}

export const resultsAPI = {
  getAll: () => api.get('/results'),
  getCategories: () => api.get('/results/categories'),
  getContestantResults: (contestantId: string) => api.get(`/results/contestant/${contestantId}`),
  getCategoryResults: (categoryId: string) => api.get(`/results/category/${categoryId}`),
  getContestResults: (contestId: string) => api.get(`/results/contest/${contestId}`),
  getEventResults: (eventId: string) => api.get(`/results/event/${eventId}`),
}

export const usersAPI = {
  getAll: () => api.get('/users'),
  getById: (id: string) => api.get(`/users/${id}`),
  create: (data: any) => api.post('/users', data),
  update: (id: string, data: any) => api.put(`/users/${id}`, data),
  delete: (id: string) => api.delete(`/users/${id}`),
  resetPassword: (id: string, data: any) => api.post(`/users/${id}/reset-password`, data),
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
  getActivityLogs: () => api.get('/admin/logs'),
  getAuditLogs: (params?: any) => api.get('/admin/audit-logs', { params }),
  exportAuditLogs: (params?: any) => api.post('/admin/export-audit-logs', params),
  testConnection: (type: string) => api.post(`/admin/test/${type}`),
}

export const uploadAPI = {
  uploadFile: (file: File, type: string = 'OTHER') => {
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
  deleteFile: (fileId: string) => api.delete(`/upload/${fileId}`),
  getFiles: (params?: any) => api.get('/upload/files', { params }),
}

export const archiveAPI = {
  getAll: () => api.get('/archive'),
  getActiveEvents: () => api.get('/archive/events/active'),
  archive: (typeOrEventId: string, idOrReason?: string, reason?: string) => {
    if (reason !== undefined) {
      // Called with (type, id, reason)
      return api.post(`/archive/${typeOrEventId}/${idOrReason}`, { reason })
    } else {
      // Called with (eventId, reason) - treat as event archive
      return api.post(`/archive/event/${typeOrEventId}`, { reason: idOrReason })
    }
  },
  restore: (typeOrEventId: string, id?: string) => {
    if (id !== undefined) {
      // Called with (type, id)
      return api.post(`/archive/${typeOrEventId}/${id}/restore`)
    } else {
      // Called with (eventId) - treat as event restore
      return api.post(`/archive/event/${typeOrEventId}/restore`)
    }
  },
  delete: (typeOrEventId: string, id?: string) => {
    if (id !== undefined) {
      // Called with (type, id)
      return api.delete(`/archive/${typeOrEventId}/${id}`)
    } else {
      // Called with (eventId) - treat as event delete
      return api.delete(`/archive/event/${typeOrEventId}`)
    }
  },
  archiveEvent: (eventId: string, reason: string) => api.post(`/archive/event/${eventId}`, { reason }),
  restoreEvent: (eventId: string) => api.post(`/archive/event/${eventId}/restore`),
  getArchivedEvents: () => api.get('/archive/events'),
}

export const backupAPI = {
  getAll: () => api.get('/backup'),
  create: (type: 'FULL' | 'SCHEMA' | 'DATA') => api.post('/backup', { type }),
  list: () => api.get('/backup'),
  download: async (backupId: string) => {
    const response = await api.get(`/backup/${backupId}/download`, { responseType: 'blob' })
    return response.data
  },
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
  restoreFromFile: (file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    return api.post('/backup/restore-from-file', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },
  delete: (backupId: string) => api.delete(`/backup/${backupId}`),
}

export const settingsAPI = {
  getAll: () => api.get('/settings'),
  getSettings: () => api.get('/settings'),
  update: (data: Record<string, any>) => api.put('/settings', data),
  updateSettings: (data: any) => api.put('/settings', data),
  test: (type: 'email' | 'database' | 'backup') => api.post(`/settings/test/${type}`),
}

export const assignmentsAPI = {
  getAll: () => api.get('/assignments'),
  getJudges: () => api.get('/assignments/judges'),
  getCategories: () => api.get('/assignments/categories'),
  create: (data: any) => api.post('/assignments', data),
  update: (id: string, data: any) => api.put(`/assignments/${id}`, data),
  delete: (id: string) => api.delete(`/assignments/${id}`),
  assignJudge: (judgeId: string, categoryId: string) => api.post('/assignments/judge', { judgeId, categoryId }),
  removeAssignment: (assignmentId: string) => api.delete(`/assignments/${assignmentId}`),
}

export const auditorAPI = {
  getStats: () => api.get('/auditor/stats'),
  getPendingAudits: () => api.get('/auditor/pending'),
  getCompletedAudits: () => api.get('/auditor/completed'),
  finalCertification: (categoryIdOrData: string | any, data?: any) => {
    if (typeof categoryIdOrData === 'string') {
      // Called with (categoryId, data)
      return api.post(`/auditor/category/${categoryIdOrData}/final-certification`, data)
    } else {
      // Called with (data) - extract categoryId from data
      const { categoryId, ...certificationData } = categoryIdOrData
      return api.post(`/auditor/category/${categoryId}/final-certification`, certificationData)
    }
  },
  rejectAudit: (categoryId: string, reason: string) => api.post(`/auditor/category/${categoryId}/reject`, { reason }),
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
  getCertificationQueue: () => api.get('/tally-master/queue'),
  getPendingCertifications: () => api.get('/tally-master/pending'),
  certifyTotals: (categoryIdOrData: string | any, data?: any) => {
    if (typeof categoryIdOrData === 'string') {
      // Called with (categoryId, data)
      return api.post(`/tally-master/category/${categoryIdOrData}/certify-totals`, data)
    } else {
      // Called with (data) - extract categoryId from data
      const { categoryId, ...totalsData } = categoryIdOrData
      return api.post(`/tally-master/category/${categoryId}/certify-totals`, totalsData)
    }
  },
}

export const emailAPI = {
  getAll: () => api.get('/email'),
  getTemplates: () => api.get('/email/templates'),
  getCampaigns: () => api.get('/email/campaigns'),
  getLogs: () => api.get('/email/logs'),
  sendEmail: (data: any) => api.post('/email/send', data),
  createTemplate: (data: any) => api.post('/email/templates', data),
  updateTemplate: (id: string, data: any) => api.put(`/email/templates/${id}`, data),
  deleteTemplate: (id: string) => api.delete(`/email/templates/${id}`),
}

// Export the api instance for direct use
export { api }
export default api
EOF

echo "✅ Corrected API service file created!"

# Test the build
echo "🔨 Testing TypeScript compilation..."
if npm run build; then
    echo "🎉 SUCCESS! All TypeScript errors resolved!"
    echo "✅ Frontend build completed successfully"
    
    # Restart the frontend service
    echo "🔄 Restarting frontend service..."
    sudo systemctl restart event-manager-frontend || echo "⚠️  Could not restart frontend service (may not be using systemd)"
    
    echo ""
    echo "🚀 DEPLOYMENT COMPLETE!"
    echo "📋 Summary:"
    echo "   ✅ API service file corrected"
    echo "   ✅ All TypeScript errors resolved"
    echo "   ✅ Frontend build successful"
    echo "   ✅ Service restarted"
    echo ""
    echo "🌐 Your application should now be working correctly!"
else
    echo "❌ Build failed. Check the errors above."
    echo "🔄 Restoring backup..."
    cp src/services/api.ts.backup src/services/api.ts
    echo "📦 Backup restored. Please check the errors and try again."
    exit 1
fi
