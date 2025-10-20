import React from 'react'
import { Outlet } from 'react-router-dom'
import Sidebar from './Sidebar'
import Header from './Header'
import { useAuth } from '../hooks/useAuth'
import { SocketProvider } from '../contexts/SocketContext'

const Layout: React.FC = () => {
  const { user } = useAuth()

  return (
    <SocketProvider>
      <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
        <div className="flex">
          {/* Sidebar */}
          <Sidebar />
          
          {/* Main Content */}
          <div className="flex-1 flex flex-col">
            {/* Header */}
            <Header />
            
            {/* Page Content */}
            <main className="flex-1 p-6">
              <Outlet />
            </main>
          </div>
        </div>
      </div>
    </SocketProvider>
  )
}

export default Layout
