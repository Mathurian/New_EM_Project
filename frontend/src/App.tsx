import { BrowserRouter as Router, Routes, Route, Navigate } from 'react-router-dom'
import { QueryClient, QueryClientProvider } from 'react-query'
import { AuthProvider } from './contexts/AuthContext'
import { SocketProvider } from './contexts/SocketContext'
import { ThemeProvider } from './contexts/ThemeContext'
import Layout from './components/Layout'
import LoginPage from './pages/LoginPage'
import Dashboard from './pages/Dashboard'
import EventsPage from './pages/EventsPage'
import ContestsPage from './pages/ContestsPage'
import CategoriesPage from './pages/CategoriesPage'
import ScoringPage from './pages/ScoringPage'
import ResultsPage from './pages/ResultsPage'
import UsersPage from './pages/UsersPage'
import AdminPage from './pages/AdminPage'
import SettingsPage from './pages/SettingsPage'
import ProfilePage from './pages/ProfilePage'
import EmceePage from './pages/EmceePage'
import TemplatesPage from './pages/TemplatesPage'
import ReportsPage from './pages/ReportsPage'
import ProtectedRoute from './components/ProtectedRoute'
import ErrorBoundary from './components/ErrorBoundary'
import './index.css'

const queryClient = new QueryClient({
  defaultOptions: {
    queries: {
      retry: 1,
      refetchOnWindowFocus: false,
    },
  },
})

function App() {
  return (
    <ErrorBoundary>
      <QueryClientProvider client={queryClient}>
        <ThemeProvider>
          <Router>
            <AuthProvider>
              <SocketProvider>
                <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
                  <Routes>
                    <Route path="/login" element={<LoginPage />} />
                    <Route
                      path="/*"
                      element={
                        <ProtectedRoute>
                          <Layout>
                            <Routes>
                              <Route path="/" element={<Navigate to="/dashboard" replace />} />
                              <Route path="/dashboard" element={<Dashboard />} />
                              <Route path="/events" element={<EventsPage />} />
                              <Route path="/events/:eventId/contests" element={<ContestsPage />} />
                              <Route path="/contests/:contestId/categories" element={<CategoriesPage />} />
                              <Route path="/scoring" element={<ScoringPage />} />
                              <Route path="/results" element={<ResultsPage />} />
                              <Route path="/users" element={<UsersPage />} />
                              <Route path="/admin" element={<AdminPage />} />
                              <Route path="/settings" element={<SettingsPage />} />
                              <Route path="/profile" element={<ProfilePage />} />
                              <Route path="/emcee" element={<EmceePage />} />
                              <Route path="/templates" element={<TemplatesPage />} />
                              <Route path="/reports" element={<ReportsPage />} />
                            </Routes>
                          </Layout>
                        </ProtectedRoute>
                      }
                    />
                  </Routes>
                </div>
              </SocketProvider>
            </AuthProvider>
          </Router>
        </ThemeProvider>
      </QueryClientProvider>
    </ErrorBoundary>
  )
}

export default App
