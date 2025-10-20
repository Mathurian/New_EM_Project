import React from 'react'
import { Link, useLocation } from 'react-router-dom'
import { useAuth } from '../hooks/useAuth'
import { 
  HomeIcon,
  CalendarIcon,
  TrophyIcon,
  TagIcon,
  ClipboardDocumentListIcon,
  UsersIcon,
  UserIcon,
  CogIcon,
  ChartBarIcon
} from '@heroicons/react/24/outline'
import clsx from 'clsx'

const Sidebar: React.FC = () => {
  const { user } = useAuth()
  const location = useLocation()

  const navigation = [
    { name: 'Dashboard', href: '/dashboard', icon: HomeIcon, roles: ['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD'] },
    { name: 'Events', href: '/events', icon: CalendarIcon, roles: ['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD'] },
    { name: 'Contests', href: '/contests', icon: TrophyIcon, roles: ['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD'] },
    { name: 'Categories', href: '/categories', icon: TagIcon, roles: ['ORGANIZER', 'JUDGE', 'CONTESTANT', 'EMCEE', 'TALLY_MASTER', 'AUDITOR', 'BOARD'] },
    { name: 'Scoring', href: '/scoring', icon: ClipboardDocumentListIcon, roles: ['JUDGE', 'ORGANIZER', 'BOARD'] },
    { name: 'Users', href: '/users', icon: UsersIcon, roles: ['ORGANIZER', 'BOARD'] },
    { name: 'Admin', href: '/admin', icon: CogIcon, roles: ['ORGANIZER', 'BOARD'] },
  ]

  const filteredNavigation = navigation.filter(item => 
    item.roles.includes(user?.role || '')
  )

  return (
    <div className="hidden md:flex md:w-64 md:flex-col md:fixed md:inset-y-0">
      <div className="flex-1 flex flex-col min-h-0 bg-white dark:bg-gray-800 border-r border-gray-200 dark:border-gray-700">
        <div className="flex-1 flex flex-col pt-5 pb-4 overflow-y-auto">
          <div className="flex items-center flex-shrink-0 px-4">
            <h1 className="text-xl font-bold text-gray-900 dark:text-white">
              Event Manager
            </h1>
          </div>
          <nav className="mt-5 flex-1 px-2 space-y-1">
            {filteredNavigation.map((item) => {
              const isActive = location.pathname === item.href || 
                (item.href !== '/dashboard' && location.pathname.startsWith(item.href))
              
              return (
                <Link
                  key={item.name}
                  to={item.href}
                  className={clsx(
                    'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
                    isActive
                      ? 'bg-blue-100 text-blue-900 dark:bg-blue-900 dark:text-blue-100'
                      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900 dark:text-gray-300 dark:hover:bg-gray-700 dark:hover:text-white'
                  )}
                >
                  <item.icon
                    className={clsx(
                      'mr-3 flex-shrink-0 h-5 w-5',
                      isActive
                        ? 'text-blue-500 dark:text-blue-400'
                        : 'text-gray-400 group-hover:text-gray-500 dark:group-hover:text-gray-300'
                    )}
                    aria-hidden="true"
                  />
                  {item.name}
                </Link>
              )
            })}
          </nav>
        </div>
        
        {/* User Profile Section */}
        <div className="flex-shrink-0 flex border-t border-gray-200 dark:border-gray-700 p-4">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                <span className="text-sm font-medium text-white">
                  {user?.preferredName?.charAt(0) || user?.name?.charAt(0) || 'U'}
                </span>
              </div>
            </div>
            <div className="ml-3">
              <p className="text-sm font-medium text-gray-700 dark:text-gray-200">
                {user?.preferredName || user?.name}
              </p>
              <p className="text-xs text-gray-500 dark:text-gray-400 capitalize">
                {user?.role?.toLowerCase().replace('_', ' ')}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default Sidebar
