import { NavLink, useLocation } from 'react-router-dom'
import { 
  Home, 
  Calendar, 
  Trophy, 
  Users, 
  Settings, 
  BarChart3,
  User,
  Gavel,
  Mic,
  Shield,
  Eye,
  Crown
} from 'lucide-react'
import { useAuthStore } from '../../stores/authStore'
import { cn } from '../../lib/utils'

const navigation = [
  { name: 'Dashboard', href: '/', icon: Home },
  { name: 'Events', href: '/events', icon: Calendar },
  { name: 'Scoring', href: '/scoring', icon: BarChart3 },
  { name: 'Results', href: '/results', icon: Trophy },
  { name: 'Users', href: '/users', icon: Users },
  { name: 'Settings', href: '/settings', icon: Settings },
]

const roleNavigation = {
  judge: [
    { name: 'Judge Dashboard', href: '/judge', icon: Gavel },
  ],
  emcee: [
    { name: 'Emcee Dashboard', href: '/emcee', icon: Mic },
  ],
  tally_master: [
    { name: 'Tally Master', href: '/tally-master', icon: BarChart3 },
  ],
  auditor: [
    { name: 'Auditor', href: '/auditor', icon: Eye },
  ],
  board: [
    { name: 'Board', href: '/board', icon: Crown },
  ],
}

export const Sidebar = () => {
  const { user } = useAuthStore()
  const location = useLocation()

  if (!user) return null

  const userRoleNavigation = roleNavigation[user.role as keyof typeof roleNavigation] || []

  return (
    <div className="hidden md:flex md:w-64 md:flex-col">
      <div className="flex flex-col flex-grow pt-5 bg-card border-r overflow-y-auto">
        <div className="flex items-center flex-shrink-0 px-4">
          <h2 className="text-lg font-semibold text-foreground">Event Manager</h2>
        </div>
        
        <div className="mt-5 flex-grow flex flex-col">
          <nav className="flex-1 px-2 space-y-1">
            {/* Main Navigation */}
            {navigation.map((item) => {
              const isActive = location.pathname === item.href
              return (
                <NavLink
                  key={item.name}
                  to={item.href}
                  className={cn(
                    'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
                    isActive
                      ? 'bg-primary text-primary-foreground'
                      : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                  )}
                >
                  <item.icon
                    className={cn(
                      'mr-3 flex-shrink-0 h-5 w-5',
                      isActive ? 'text-primary-foreground' : 'text-muted-foreground group-hover:text-accent-foreground'
                    )}
                  />
                  {item.name}
                </NavLink>
              )
            })}
            
            {/* Role-specific Navigation */}
            {userRoleNavigation.length > 0 && (
              <>
                <div className="border-t border-border my-4"></div>
                <div className="px-2 text-xs font-semibold text-muted-foreground uppercase tracking-wider">
                  {user.role.replace('_', ' ')} Tools
                </div>
                {userRoleNavigation.map((item) => {
                  const isActive = location.pathname === item.href
                  return (
                    <NavLink
                      key={item.name}
                      to={item.href}
                      className={cn(
                        'group flex items-center px-2 py-2 text-sm font-medium rounded-md transition-colors',
                        isActive
                          ? 'bg-primary text-primary-foreground'
                          : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                      )}
                    >
                      <item.icon
                        className={cn(
                          'mr-3 flex-shrink-0 h-5 w-5',
                          isActive ? 'text-primary-foreground' : 'text-muted-foreground group-hover:text-accent-foreground'
                        )}
                      />
                      {item.name}
                    </NavLink>
                  )
                })}
              </>
            )}
          </nav>
        </div>
        
        {/* User Profile */}
        <div className="flex-shrink-0 flex border-t border-border p-4">
          <div className="flex items-center">
            <div className="flex-shrink-0">
              <div className="h-8 w-8 rounded-full bg-primary flex items-center justify-center">
                <User className="h-4 w-4 text-primary-foreground" />
              </div>
            </div>
            <div className="ml-3">
              <p className="text-sm font-medium text-foreground">
                {user.first_name} {user.last_name}
              </p>
              <p className="text-xs text-muted-foreground capitalize">
                {user.role.replace('_', ' ')}
              </p>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}