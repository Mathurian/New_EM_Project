import { ReactNode } from 'react'
import { Outlet } from 'react-router-dom'

interface AuthLayoutProps {
  children?: ReactNode
}

export const AuthLayout = ({ children }: AuthLayoutProps) => {
  return (
    <div className="min-h-screen bg-background flex items-center justify-center">
      <div className="w-full max-w-md">
        <div className="text-center mb-8">
          <h1 className="text-3xl font-bold text-foreground">Event Manager</h1>
          <p className="text-muted-foreground mt-2">
            Manage your events, contests, and scoring
          </p>
        </div>
        {children || <Outlet />}
      </div>
    </div>
  )
}