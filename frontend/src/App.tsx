import React from 'react'
import { Routes, Route, Navigate } from 'react-router-dom'
import { useAuth } from './hooks/useAuth'
import Layout from './components/Layout'
import Login from './pages/Login'
import Dashboard from './pages/Dashboard'
import Events from './pages/Events'
import EventDetail from './pages/EventDetail'
import Contests from './pages/Contests'
import ContestDetail from './pages/ContestDetail'
import Categories from './pages/Categories'
import CategoryDetail from './pages/CategoryDetail'
import Scoring from './pages/Scoring'
import Users from './pages/Users'
import Profile from './pages/Profile'
import Admin from './pages/Admin'
import NotFound from './pages/NotFound'
import LoadingSpinner from './components/LoadingSpinner'

// Protected Route Component
const ProtectedRoute: React.FC<{ children: React.ReactNode; requiredRoles?: string[] }> = ({ 
  children, 
  requiredRoles = [] 
}) => {
  const { user, isLoading } = useAuth()

  if (isLoading) {
    return <LoadingSpinner />
  }

  if (!user) {
    return <Navigate to="/login" replace />
  }

  if (requiredRoles.length > 0 && !requiredRoles.includes(user.role)) {
    return <Navigate to="/dashboard" replace />
  }

  return <>{children}</>
}

// Public Route Component (redirects to dashboard if already logged in)
const PublicRoute: React.FC<{ children: React.ReactNode }> = ({ children }) => {
  const { user, isLoading } = useAuth()

  if (isLoading) {
    return <LoadingSpinner />
  }

  if (user) {
    return <Navigate to="/dashboard" replace />
  }

  return <>{children}</>
}

const App: React.FC = () => {
  return (
    <Routes>
      {/* Public Routes */}
      <Route 
        path="/login" 
        element={
          <PublicRoute>
            <Login />
          </PublicRoute>
        } 
      />

      {/* Protected Routes */}
      <Route 
        path="/" 
        element={
          <ProtectedRoute>
            <Layout />
          </ProtectedRoute>
        }
      >
        <Route index element={<Navigate to="/dashboard" replace />} />
        <Route path="dashboard" element={<Dashboard />} />
        
        {/* Events */}
        <Route path="events" element={<Events />} />
        <Route path="events/:id" element={<EventDetail />} />
        
        {/* Contests */}
        <Route path="contests" element={<Contests />} />
        <Route path="contests/:id" element={<ContestDetail />} />
        
        {/* Categories */}
        <Route path="categories" element={<Categories />} />
        <Route path="categories/:id" element={<CategoryDetail />} />
        
        {/* Scoring */}
        <Route 
          path="scoring" 
          element={
            <ProtectedRoute requiredRoles={['JUDGE', 'ORGANIZER', 'BOARD']}>
              <Scoring />
            </ProtectedRoute>
          } 
        />
        
        {/* Users */}
        <Route 
          path="users" 
          element={
            <ProtectedRoute requiredRoles={['ORGANIZER', 'BOARD']}>
              <Users />
            </ProtectedRoute>
          } 
        />
        
        {/* Profile */}
        <Route path="profile" element={<Profile />} />
        
        {/* Admin */}
        <Route 
          path="admin" 
          element={
            <ProtectedRoute requiredRoles={['ORGANIZER', 'BOARD']}>
              <Admin />
            </ProtectedRoute>
          } 
        />
      </Route>

      {/* 404 */}
      <Route path="*" element={<NotFound />} />
    </Routes>
  )
}

export default App
