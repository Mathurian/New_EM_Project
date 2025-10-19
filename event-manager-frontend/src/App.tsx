import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuthStore } from './stores/authStore'
import { useEffect } from 'react'
import { useWebSocket } from './hooks/useWebSocket'

// Layout components
import { Layout } from './components/layout/Layout'
import { AuthLayout } from './components/layout/AuthLayout'

// Page components
import { LoginPage } from './pages/auth/LoginPage'
import { DashboardPage } from './pages/DashboardPage'
import { EventsPage } from './pages/EventsPage'
import { ContestsPage } from './pages/ContestsPage'
import { CategoriesPage } from './pages/CategoriesPage'
import { ScoringPage } from './pages/ScoringPage'
import { ResultsPage } from './pages/ResultsPage'
import { UsersPage } from './pages/UsersPage'
import { SettingsPage } from './pages/SettingsPage'
import { ProfilePage } from './pages/ProfilePage'

// Role-specific pages
import { JudgeDashboard } from './pages/roles/JudgeDashboard'
import { TallyMasterDashboard } from './pages/roles/TallyMasterDashboard'
import { EmceeDashboard } from './pages/roles/EmceeDashboard'
import { AuditorDashboard } from './pages/roles/AuditorDashboard'
import { BoardDashboard } from './pages/roles/BoardDashboard'

// Loading component
import { LoadingSpinner } from './components/ui/LoadingSpinner'

function App() {
  const { user, isLoading, checkAuth } = useAuthStore()
  const { connect, disconnect } = useWebSocket()

  useEffect(() => {
    checkAuth()
  }, [checkAuth])

  useEffect(() => {
    if (user) {
      connect()
    } else {
      disconnect()
    }
    
    return () => disconnect()
  }, [user, connect, disconnect])

  if (isLoading) {
    return (
      <div className="min-h-screen flex items-center justify-center">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  if (!user) {
    return (
      <AuthLayout>
        <Routes>
          <Route path="/login" element={<LoginPage />} />
          <Route path="*" element={<Navigate to="/login" replace />} />
        </Routes>
      </AuthLayout>
    )
  }

  return (
    <Layout>
      <Routes>
        {/* Main Dashboard */}
        <Route path="/" element={<DashboardPage />} />
        
        {/* Event Management */}
        <Route path="/events" element={<EventsPage />} />
        <Route path="/events/:eventId/contests" element={<ContestsPage />} />
        <Route path="/contests/:contestId/categories" element={<CategoriesPage />} />
        
        {/* Scoring & Results */}
        <Route path="/scoring" element={<ScoringPage />} />
        <Route path="/results" element={<ResultsPage />} />
        
        {/* User Management */}
        <Route path="/users" element={<UsersPage />} />
        <Route path="/profile" element={<ProfilePage />} />
        
        {/* Settings */}
        <Route path="/settings" element={<SettingsPage />} />
        
        {/* Role-specific dashboards */}
        <Route path="/judge" element={<JudgeDashboard />} />
        <Route path="/tally-master" element={<TallyMasterDashboard />} />
        <Route path="/emcee" element={<EmceeDashboard />} />
        <Route path="/auditor" element={<AuditorDashboard />} />
        <Route path="/board" element={<BoardDashboard />} />
        
        {/* Catch all route */}
        <Route path="*" element={<Navigate to="/" replace />} />
      </Routes>
    </Layout>
  )
}

export default App