import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000/api'

export const api = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor
api.interceptors.request.use(
  (config) => {
    // Add auth token if available
    const token = localStorage.getItem('auth-storage')
    if (token) {
      try {
        const authData = JSON.parse(token)
        if (authData.state?.token) {
          config.headers.Authorization = `Bearer ${authData.state.token}`
        }
      } catch (error) {
        // Invalid token, ignore
      }
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
      // Token expired or invalid, clear auth state
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
    logout: '/auth/logout',
    me: '/auth/me',
    profile: '/auth/profile',
  },
  
  // Events
  events: {
    list: '/events',
    create: '/events',
    get: (id: string) => `/events/${id}`,
    update: (id: string) => `/events/${id}`,
    delete: (id: string) => `/events/${id}`,
    archive: (id: string) => `/events/${id}/archive`,
    reactivate: (id: string) => `/events/${id}/reactivate`,
  },
  
  // Contests
  contests: {
    list: '/contests',
    create: '/contests',
    get: (id: string) => `/contests/${id}`,
    update: (id: string) => `/contests/${id}`,
    delete: (id: string) => `/contests/${id}`,
    byEvent: (eventId: string) => `/events/${eventId}/contests`,
  },
  
  // Categories
  categories: {
    list: '/categories',
    create: '/categories',
    get: (id: string) => `/categories/${id}`,
    update: (id: string) => `/categories/${id}`,
    delete: (id: string) => `/categories/${id}`,
    byContest: (contestId: string) => `/contests/${contestId}/categories`,
  },
  
  // Scoring
  scoring: {
    submit: '/scoring/submit',
    sign: '/scoring/sign',
    unsign: '/scoring/unsign',
    get: '/scoring',
    bySubcategory: (subcategoryId: string) => `/scoring/subcategory/${subcategoryId}`,
    byContestant: (contestantId: string) => `/scoring/contestant/${contestantId}`,
    byJudge: (judgeId: string) => `/scoring/judge/${judgeId}`,
  },
  
  // Results
  results: {
    event: (eventId: string) => `/results/event/${eventId}`,
    contest: (contestId: string) => `/results/contest/${contestId}`,
    subcategory: (subcategoryId: string) => `/results/subcategory/${subcategoryId}`,
  },
  
  // Users
  users: {
    list: '/users',
    create: '/users',
    get: (id: string) => `/users/${id}`,
    update: (id: string) => `/users/${id}`,
    delete: (id: string) => `/users/${id}`,
    byRole: (role: string) => `/users/role/${role}`,
  },
  
  // Files
  files: {
    upload: '/files/upload',
    get: (id: string) => `/files/${id}`,
    delete: (id: string) => `/files/${id}`,
    byEntity: (entityType: string, entityId: string) => `/files/${entityType}/${entityId}`,
  },
  
  // Settings
  settings: {
    list: '/settings',
    get: (key: string) => `/settings/${key}`,
    update: (key: string) => `/settings/${key}`,
    create: '/settings',
    delete: (key: string) => `/settings/${key}`,
  },
  
  // WebSocket
  websocket: {
    scoring: '/ws/scoring',
    event: (eventId: string) => `/ws/event/${eventId}`,
  }
}

export default api