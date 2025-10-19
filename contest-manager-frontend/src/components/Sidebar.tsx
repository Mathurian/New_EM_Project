import { NavLink } from 'react-router-dom'
import { 
  LayoutDashboard, 
  Trophy, 
  Target, 
  BarChart3, 
  Users, 
  Settings,
  User,
  Calendar,
  FileText
} from 'lucide-react'
import { useAuthStore } from '../stores/authStore'
import { cn } from '../lib/utils'

const navigation = [
  { name: 'Dashboard', href: '/dashboard', icon: LayoutDashboard, roles: ['organizer', 'emcee', 'judge', 'tally_master', 'auditor', 'board'] },
  { name: 'Events', href: '/events', icon: Trophy, roles: ['organizer', 'emcee', 'judge', 'tally_master', 'auditor', 'board'] },
  { name: 'Scoring', href: '/scoring', icon: Target, roles: ['judge', 'organizer'] },
  { name: 'Results', href: '/results', icon: BarChart3, roles: ['organizer', 'tally_master', 'auditor', 'board'] },
  { name: 'Users', href: '/users', icon: Users, roles: ['organizer', 'board'] },
  { name: 'Reports', href: '/reports', icon: FileText, roles: ['organizer', 'board', 'tally_master'] },
  { name: 'Settings', href: '/settings', icon: Settings, roles: ['organizer'] },
]

export function Sidebar() {
  const { user } = useAuthStore()

  const filteredNavigation = navigation.filter(item => 
    item.roles.includes(user?.role || '')
  )

  return (
    <div className="w-64 bg-white border-r border-gray-200 min-h-screen">
      <div className="p-6">
        <div className="flex items-center space-x-2">
          <div className="h-8 w-8 bg-blue-600 rounded-lg flex items-center justify-center">
            <Trophy className="h-5 w-5 text-white" />
          </div>
          <span className="text-xl font-bold text-gray-900">Event Manager</span>
        </div>
      </div>

      <nav className="mt-8 px-4">
        <ul className="space-y-2">
          {filteredNavigation.map((item) => (
            <li key={item.name}>
              <NavLink
                to={item.href}
                className={({ isActive }) =>
                  cn(
                    'flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
                    isActive
                      ? 'bg-blue-50 text-blue-700 border-r-2 border-blue-700'
                      : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
                  )
                }
              >
                <item.icon className="h-5 w-5" />
                <span>{item.name}</span>
              </NavLink>
            </li>
          ))}
        </ul>
      </nav>

      {/* User Profile Link */}
      <div className="absolute bottom-4 left-4 right-4">
        <NavLink
          to="/profile"
          className={({ isActive }) =>
            cn(
              'flex items-center space-x-3 px-3 py-2 rounded-lg text-sm font-medium transition-colors',
              isActive
                ? 'bg-blue-50 text-blue-700'
                : 'text-gray-600 hover:bg-gray-50 hover:text-gray-900'
            )
          }
        >
          <User className="h-5 w-5" />
          <span>Profile</span>
        </NavLink>
      </div>
    </div>
  )
}