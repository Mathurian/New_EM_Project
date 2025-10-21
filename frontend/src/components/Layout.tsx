import React, { useState } from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../contexts/AuthContext'
import { useTheme } from '../contexts/ThemeContext'
import { useSocket } from '../contexts/SocketContext'
import {
  HomeIcon,
  CalendarIcon,
  TrophyIcon,
  UsersIcon,
  CogIcon,
  UserIcon,
  Bars3Icon,
  XMarkIcon,
  SunIcon,
  MoonIcon,
  ComputerDesktopIcon,
  MicrophoneIcon,
  DocumentTextIcon,
  ChartBarIcon,
  BellIcon,
  ArrowRightOnRectangleIcon,
} from '@heroicons/react/24/outline'

interface LayoutProps {
  children: React.ReactNode
}

const Layout: React.FC<LayoutProps> = ({ children }) => {
  const [sidebarOpen, setSidebarOpen] = useState(false)
  const [profileMenuOpen, setProfileMenuOpen] = useState(false)
  const { user, logout } = useAuth()
  const { theme, setTheme, actualTheme } = useTheme()
  const { isConnected } = useSocket()
  const location = useLocation()

  const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: HomeIcon, roles: ['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD'] },
    { name: 'Events', href: '/events', icon: CalendarIcon, roles: ['ORGANIZER', 'BOARD'] },
    { name: 'Scoring', href: '/scoring', icon: TrophyIcon, roles: ['JUDGE'] },
    { name: 'Results', href: '/results', icon: ChartBarIcon, roles: ['ORGANIZER', 'JUDGE', 'CONTESTANT', 'TALLY_MASTER', 'AUDITOR', 'BOARD'] },
    { name: 'Users', href: '/users', icon: UsersIcon, roles: ['ORGANIZER', 'BOARD'] },
    { name: 'Admin', href: '/admin', icon: CogIcon, roles: ['ORGANIZER', 'BOARD'] },
    { name: 'Emcee', href: '/emcee', icon: MicrophoneIcon, roles: ['EMCEE'] },
    { name: 'Templates', href: '/templates', icon: DocumentTextIcon, roles: ['ORGANIZER', 'BOARD'] },
    { name: 'Reports', href: '/reports', icon: ChartBarIcon, roles: ['ORGANIZER', 'BOARD'] },
  ]

  const filteredNavigation = navigation.filter(item => 
    item.roles.includes(user?.role || '')
  )

  const getRoleColor = (role: string) => {
    const colors = {
      ORGANIZER: 'role-organizer',
      JUDGE: 'role-judge',
      CONTESTANT: 'role-contestant',
      EMCEE: 'role-emcee',
      TALLY_MASTER: 'role-tally-master',
      AUDITOR: 'role-auditor',
      BOARD: 'role-board',
    }
    return colors[role as keyof typeof colors] || 'role-board'
  }

  const getRoleDisplayName = (role: string) => {
    const names = {
      ORGANIZER: 'Organizer',
      JUDGE: 'Judge',
      CONTESTANT: 'Contestant',
      EMCEE: 'Emcee',
      TALLY_MASTER: 'Tally Master',
      AUDITOR: 'Auditor',
      BOARD: 'Board',
    }
    return names[role as keyof typeof names] || role
  }

  return (
    <div className="min-h-screen bg-gray-50 dark:bg-gray-900">
      {/* Mobile sidebar */}
      <div className={`mobile-menu ${sidebarOpen ? 'block' : 'hidden'}`}>
        <div className="mobile-menu-overlay" onClick={() => setSidebarOpen(false)} />
        <div className="mobile-menu-content">
          <div className="flex items-center justify-between p-4 border-b">
            <h2 className="text-lg font-semibold">Event Manager</h2>
            <button
              onClick={() => setSidebarOpen(false)}
              className="btn btn-ghost btn-sm"
            >
              <XMarkIcon className="h-5 w-5" />
            </button>
          </div>
          <nav className="p-4 space-y-2">
            {filteredNavigation.map((item) => {
              const isActive = location.pathname === item.href
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={`sidebar-nav-item ${isActive ? 'sidebar-nav-item-active' : ''}`}
                  onClick={() => setSidebarOpen(false)}
                >
                  <item.icon className="h-5 w-5 mr-3" />
                  {item.name}
                </Link>
              )
            })}
          </nav>
        </div>
      </div>

      {/* Desktop sidebar */}
      <div className="desktop-only sidebar">
        <div className="sidebar-header">
          <h1 className="text-xl font-bold">Event Manager</h1>
        </div>
        <div className="sidebar-content">
          <nav className="sidebar-nav">
            {filteredNavigation.map((item) => {
              const isActive = location.pathname === item.href
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={`sidebar-nav-item ${isActive ? 'sidebar-nav-item-active' : ''}`}
                >
                  <item.icon className="h-5 w-5 mr-3" />
                  {item.name}
                </Link>
              )
            })}
          </nav>
        </div>
        <div className="sidebar-footer">
          <div className="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
            <div className={`w-2 h-2 rounded-full ${isConnected ? 'bg-green-500' : 'bg-red-500'}`} />
            <span>{isConnected ? 'Connected' : 'Disconnected'}</span>
          </div>
        </div>
      </div>

      {/* Main content */}
      <div className="lg:pl-64">
        {/* Top navigation */}
        <div className="sticky top-0 z-40 bg-white dark:bg-gray-800 border-b border-gray-200 dark:border-gray-700">
          <div className="flex items-center justify-between px-4 py-3">
            <div className="flex items-center space-x-4">
              <button
                onClick={() => setSidebarOpen(true)}
                className="mobile-only btn btn-ghost btn-sm"
              >
                <Bars3Icon className="h-5 w-5" />
              </button>
              <h2 className="text-lg font-semibold text-gray-900 dark:text-white">
                {filteredNavigation.find(item => item.href === location.pathname)?.name || 'Dashboard'}
              </h2>
            </div>

            <div className="flex items-center space-x-4">
              {/* Theme toggle */}
              <div className="relative">
                <button
                  onClick={() => {
                    const themes: Theme[] = ['light', 'dark', 'system']
                    const currentIndex = themes.indexOf(theme)
                    const nextIndex = (currentIndex + 1) % themes.length
                    setTheme(themes[nextIndex])
                  }}
                  className="btn btn-ghost btn-sm"
                  title={`Current theme: ${theme}`}
                >
                  {actualTheme === 'dark' ? (
                    <MoonIcon className="h-5 w-5" />
                  ) : (
                    <SunIcon className="h-5 w-5" />
                  )}
                </button>
              </div>

              {/* Notifications */}
              <button className="btn btn-ghost btn-sm relative">
                <BellIcon className="h-5 w-5" />
                <span className="absolute -top-1 -right-1 w-3 h-3 bg-red-500 rounded-full text-xs"></span>
              </button>

              {/* Profile menu */}
              <div className="relative">
                <button
                  onClick={() => setProfileMenuOpen(!profileMenuOpen)}
                  className="flex items-center space-x-2 btn btn-ghost"
                >
                  <div className="w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white text-sm font-medium">
                    {user?.name?.charAt(0).toUpperCase()}
                  </div>
                  <div className="hidden md:block text-left">
                    <div className="text-sm font-medium">{user?.preferredName || user?.name}</div>
                    <div className={`text-xs ${getRoleColor(user?.role || '')}`}>
                      {getRoleDisplayName(user?.role || '')}
                    </div>
                  </div>
                </button>

                {profileMenuOpen && (
                  <div className="dropdown-menu absolute right-0 mt-2 w-48">
                    <Link
                      to="/profile"
                      className="dropdown-menu-item"
                      onClick={() => setProfileMenuOpen(false)}
                    >
                      <UserIcon className="h-4 w-4 mr-2" />
                      Profile
                    </Link>
                    <Link
                      to="/settings"
                      className="dropdown-menu-item"
                      onClick={() => setProfileMenuOpen(false)}
                    >
                      <CogIcon className="h-4 w-4 mr-2" />
                      Settings
                    </Link>
                    <hr className="my-1" />
                    <button
                      onClick={() => {
                        logout()
                        setProfileMenuOpen(false)
                      }}
                      className="dropdown-menu-item w-full text-left"
                    >
                      <ArrowRightOnRectangleIcon className="h-4 w-4 mr-2" />
                      Sign out
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        </div>

        {/* Page content */}
        <main className="p-6">
          {children}
        </main>
      </div>
    </div>
  )
}

export default Layout
