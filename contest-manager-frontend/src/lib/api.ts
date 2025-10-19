import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000/api'

export const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor to add auth token
api.interceptors.request.use(
  (config) => {
    const token = localStorage.getItem('auth-storage')
    if (token) {
      try {
        const parsed = JSON.parse(token)
        if (parsed.state?.accessToken) {
          config.headers.Authorization = `Bearer ${parsed.state.accessToken}`
        }
      } catch (error) {
        // Invalid token in localStorage
        localStorage.removeItem('auth-storage')
      }
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
      // Token expired or invalid
      localStorage.removeItem('auth-storage')
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// API endpoints
export const endpoints = {
  // Auth
  auth: {
    login: '/auth/login',
    register: '/auth/register',
    logout: '/auth/logout',
    me: '/auth/me',
    refresh: '/auth/refresh',
  },
  
  // Contests
  contests: {
    list: '/contests',
    create: '/contests',
    get: (id: string) => `/contests/${id}`,
    update: (id: string) => `/contests/${id}`,
    delete: (id: string) => `/contests/${id}`,
    archive: (id: string) => `/contests/${id}/archive`,
    reactivate: (id: string) => `/contests/${id}/reactivate`,
    stats: (id: string) => `/contests/${id}/stats`,
  },
  
  // Scoring
  scoring: {
    submit: '/scoring/submit',
    update: (id: string) => `/scoring/${id}`,
    delete: (id: string) => `/scoring/${id}`,
    subcategory: (id: string) => `/scoring/subcategory/${id}`,
    contestantTabulation: (id: string) => `/scoring/contestant/${id}/tabulation`,
    judgeTabulation: (id: string) => `/scoring/judge/${id}/tabulation`,
    subcategoryResults: (id: string) => `/scoring/subcategory/${id}/results`,
  },
  
  // Users
  users: {
    list: '/users',
    create: '/users',
    get: (id: string) => `/users/${id}`,
    update: (id: string) => `/users/${id}`,
    delete: (id: string) => `/users/${id}`,
    changePassword: (id: string) => `/users/${id}/change-password`,
    assignSubcategory: (id: string) => `/users/${id}/assign-subcategory`,
    certify: (id: string) => `/users/${id}/certify`,
    stats: '/users/stats/overview',
  },
  
  // Results
  results: {
    contest: (id: string) => `/results/contest/${id}`,
    subcategory: (id: string) => `/results/subcategory/${id}`,
    contestant: (id: string) => `/results/contestant/${id}`,
    judge: (id: string) => `/results/judge/${id}`,
    leaderboard: '/results/leaderboard',
    scoringStats: '/results/stats/scoring',
    export: '/results/export',
    pdfReport: (id: string) => `/results/contest/${id}/report/pdf`,
    excelReport: (id: string) => `/results/contest/${id}/report/excel`,
  },
  
  // Files
  files: {
    upload: '/files/upload',
    uploadMultiple: '/files/upload-multiple',
    get: (id: string) => `/files/${id}`,
    download: (id: string) => `/files/${id}/download`,
    thumbnail: (id: string) => `/files/${id}/thumbnail`,
    byEntity: (entityType: string, entityId: string) => `/files/entity/${entityType}/${entityId}`,
    update: (id: string) => `/files/${id}`,
    delete: (id: string) => `/files/${id}`,
    stats: '/files/stats/overview',
  },
  
  // Settings
  settings: {
    list: '/settings',
    get: (key: string) => `/settings/${key}`,
    update: (key: string) => `/settings/${key}`,
    create: '/settings',
    delete: (key: string) => `/settings/${key}`,
    bulkUpdate: '/settings/bulk',
    resetDefaults: '/settings/reset-defaults',
    export: '/settings/export/backup',
    import: '/settings/import/backup',
  },
  
  // WebSocket
  websocket: {
    broadcast: '/ws/broadcast/scoring',
    connections: '/ws/connections',
    send: (userId: string) => `/ws/send/${userId}`,
  },
}

export default api