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
  getByEvent: (eventId: string) => api.get(`/contests/event/${eventId}`),
  getById: (id: string) => api.get(`/contests/${id}`),
  create: (eventId: string, data: any) => api.post(`/contests/event/${eventId}`, data),
  update: (id: string, data: any) => api.put(`/contests/${id}`, data),
}

export const categoriesAPI = {
  getByContest: (contestId: string) => api.get(`/categories/contest/${contestId}`),
  getById: (id: string) => api.get(`/categories/${id}`),
  create: (contestId: string, data: any) => api.post(`/categories/contest/${contestId}`, data),
  update: (id: string, data: any) => api.put(`/categories/${id}`, data),
}

export const scoringAPI = {
  getScores: (categoryId: string, contestantId: string) => 
    api.get(`/scoring/category/${categoryId}/contestant/${contestantId}`),
  submitScore: (categoryId: string, contestantId: string, data: any) => 
    api.post(`/scoring/category/${categoryId}/contestant/${contestantId}`, data),
  certifyScores: (categoryId: string, data: any) => 
    api.post(`/scoring/category/${categoryId}/certify`, data),
  certifyTotals: (categoryId: string, data: any) => 
    api.post(`/scoring/category/${categoryId}/certify-totals`, data),
  finalCertification: (categoryId: string, data: any) => 
    api.post(`/scoring/category/${categoryId}/final-certification`, data),
}

export const resultsAPI = {
  getCategoryResults: (categoryId: string) => api.get(`/results/category/${categoryId}`),
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
}

export const uploadAPI = {
  uploadFile: (file: File) => {
    const formData = new FormData()
    formData.append('file', file)
    return api.post('/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data',
      },
    })
  },
}

export const emailAPI = {
  sendEmail: (data: any) => api.post('/email/send', data),
}

export default api
