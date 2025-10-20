import axios from 'axios'

const API_BASE_URL = import.meta.env.VITE_API_URL || 'http://localhost:3000/api'

// Create axios instance
const apiClient = axios.create({
  baseURL: API_BASE_URL,
  timeout: 10000,
  headers: {
    'Content-Type': 'application/json',
  },
})

// Request interceptor to add auth token
apiClient.interceptors.request.use(
  (config) => {
    // Add session-based auth if needed
    return config
  },
  (error) => {
    return Promise.reject(error)
  }
)

// Response interceptor for error handling
apiClient.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Handle unauthorized access
      window.location.href = '/login'
    }
    return Promise.reject(error)
  }
)

// API functions
export const api = {
  // Auth
  login: (credentials: { email: string; password: string }) =>
    apiClient.post('/auth/login', credentials),
  
  register: (userData: any) =>
    apiClient.post('/auth/register', userData),
  
  logout: () =>
    apiClient.post('/auth/logout'),
  
  getProfile: () =>
    apiClient.get('/auth/profile'),
  
  updateProfile: (data: any) =>
    apiClient.put('/auth/profile', data),
  
  changePassword: (data: any) =>
    apiClient.put('/auth/password', data),

  // Events
  getEvents: (params?: any) =>
    apiClient.get('/events', { params }),
  
  getEvent: (id: string) =>
    apiClient.get(`/events/${id}`),
  
  createEvent: (data: any) =>
    apiClient.post('/events', data),
  
  updateEvent: (id: string, data: any) =>
    apiClient.put(`/events/${id}`, data),
  
  deleteEvent: (id: string) =>
    apiClient.delete(`/events/${id}`),
  
  archiveEvent: (id: string) =>
    apiClient.post(`/events/${id}/archive`),
  
  reactivateEvent: (id: string) =>
    apiClient.post(`/events/${id}/reactivate`),

  // Contests
  getContests: (eventId: string, params?: any) =>
    apiClient.get(`/events/${eventId}/contests`, { params }),
  
  getContest: (eventId: string, contestId: string) =>
    apiClient.get(`/events/${eventId}/contests/${contestId}`),
  
  createContest: (eventId: string, data: any) =>
    apiClient.post(`/events/${eventId}/contests`, data),
  
  updateContest: (eventId: string, contestId: string, data: any) =>
    apiClient.put(`/events/${eventId}/contests/${contestId}`, data),
  
  deleteContest: (eventId: string, contestId: string) =>
    apiClient.delete(`/events/${eventId}/contests/${contestId}`),

  // Categories
  getCategories: (contestId: string) =>
    apiClient.get(`/contests/${contestId}/categories`),
  
  getCategory: (contestId: string, categoryId: string) =>
    apiClient.get(`/contests/${contestId}/categories/${categoryId}`),
  
  createCategory: (contestId: string, data: any) =>
    apiClient.post(`/contests/${contestId}/categories`, data),
  
  updateCategory: (contestId: string, categoryId: string, data: any) =>
    apiClient.put(`/contests/${contestId}/categories/${categoryId}`, data),
  
  deleteCategory: (contestId: string, categoryId: string) =>
    apiClient.delete(`/contests/${contestId}/categories/${categoryId}`),

  // Users
  getUsers: (params?: any) =>
    apiClient.get('/users', { params }),
  
  getUser: (id: string) =>
    apiClient.get(`/users/${id}`),
  
  createUser: (data: any) =>
    apiClient.post('/users', data),
  
  updateUser: (id: string, data: any) =>
    apiClient.put(`/users/${id}`, data),
  
  deleteUser: (id: string) =>
    apiClient.delete(`/users/${id}`),

  // Settings
  getSettings: () =>
    apiClient.get('/settings'),
  
  updateSetting: (key: string, value: any) =>
    apiClient.put('/settings', { key, value }),

  // Dashboard
  getDashboardStats: () =>
    apiClient.get('/dashboard/stats'),
  
  getDashboardData: (role: string) =>
    apiClient.get(`/dashboard/${role}`),

  // Results
  getResults: (eventId?: string, contestId?: string) =>
    apiClient.get('/results', { params: { eventId, contestId } }),

  // Scoring
  getSubcategories: (categoryId: string) =>
    apiClient.get(`/categories/${categoryId}/subcategories`),
  
  getContestants: (subcategoryId: string) =>
    apiClient.get(`/subcategories/${subcategoryId}/contestants`),
  
  submitScore: (data: any) =>
    apiClient.post('/scores', data),
  
  signScores: (subcategoryId: string) =>
    apiClient.post(`/subcategories/${subcategoryId}/sign-scores`),
}

export default apiClient