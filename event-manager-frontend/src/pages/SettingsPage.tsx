import { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { api } from '../../lib/api'
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '../../components/ui/Card'
import { Button } from '../../components/ui/Button'
import { Input } from '../../components/ui/Input'
import { Badge } from '../../components/ui/Badge'
import { Settings, Save, RefreshCw, Database, Mail, Shield } from 'lucide-react'
import { LoadingSpinner } from '../../components/ui/LoadingSpinner'
import { toast } from 'react-hot-toast'

export const SettingsPage = () => {
  const [activeTab, setActiveTab] = useState('general')
  const queryClient = useQueryClient()

  const { data: settings, isLoading } = useQuery(
    'settings',
    async () => {
      const response = await api.get('/settings')
      return response.data
    }
  )

  const updateSettingMutation = useMutation(
    async ({ key, value }: { key: string; value: any }) => {
      await api.put(`/settings/${key}`, { value })
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries('settings')
        toast.success('Setting updated successfully')
      },
      onError: () => {
        toast.error('Failed to update setting')
      }
    }
  )

  const tabs = [
    { id: 'general', name: 'General', icon: Settings },
    { id: 'email', name: 'Email', icon: Mail },
    { id: 'security', name: 'Security', icon: Shield },
    { id: 'database', name: 'Database', icon: Database },
  ]

  if (isLoading) {
    return (
      <div className="flex items-center justify-center h-64">
        <LoadingSpinner size="lg" />
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex items-center justify-between">
        <div>
          <h1 className="text-3xl font-bold text-foreground">Settings</h1>
          <p className="text-muted-foreground mt-2">
            Configure application settings and preferences
          </p>
        </div>
        <Button>
          <Save className="h-4 w-4 mr-2" />
          Save Changes
        </Button>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Sidebar */}
        <div className="lg:col-span-1">
          <Card>
            <CardContent className="p-0">
              <nav className="space-y-1">
                {tabs.map((tab) => {
                  const Icon = tab.icon
                  return (
                    <button
                      key={tab.id}
                      onClick={() => setActiveTab(tab.id)}
                      className={`w-full flex items-center px-4 py-3 text-sm font-medium rounded-md transition-colors ${
                        activeTab === tab.id
                          ? 'bg-primary text-primary-foreground'
                          : 'text-muted-foreground hover:bg-accent hover:text-accent-foreground'
                      }`}
                    >
                      <Icon className="h-4 w-4 mr-3" />
                      {tab.name}
                    </button>
                  )
                })}
              </nav>
            </CardContent>
          </Card>
        </div>

        {/* Content */}
        <div className="lg:col-span-3">
          {activeTab === 'general' && (
            <Card>
              <CardHeader>
                <CardTitle>General Settings</CardTitle>
                <CardDescription>
                  Basic application configuration
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="text-sm font-medium mb-2 block">Application Name</label>
                  <Input
                    defaultValue={settings?.app_name || 'Event Manager'}
                    onChange={(e) => updateSettingMutation.mutate({ key: 'app_name', value: e.target.value })}
                  />
                </div>
                <div>
                  <label className="text-sm font-medium mb-2 block">Default Timezone</label>
                  <select className="w-full h-10 px-3 py-2 border border-input bg-background rounded-md text-sm">
                    <option value="UTC">UTC</option>
                    <option value="America/New_York">Eastern Time</option>
                    <option value="America/Chicago">Central Time</option>
                    <option value="America/Denver">Mountain Time</option>
                    <option value="America/Los_Angeles">Pacific Time</option>
                  </select>
                </div>
                <div>
                  <label className="text-sm font-medium mb-2 block">Default Language</label>
                  <select className="w-full h-10 px-3 py-2 border border-input bg-background rounded-md text-sm">
                    <option value="en">English</option>
                    <option value="es">Spanish</option>
                    <option value="fr">French</option>
                  </select>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'email' && (
            <Card>
              <CardHeader>
                <CardTitle>Email Settings</CardTitle>
                <CardDescription>
                  Configure email notifications and SMTP settings
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium mb-2 block">SMTP Host</label>
                    <Input placeholder="smtp.gmail.com" />
                  </div>
                  <div>
                    <label className="text-sm font-medium mb-2 block">SMTP Port</label>
                    <Input placeholder="587" type="number" />
                  </div>
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium mb-2 block">Username</label>
                    <Input placeholder="your-email@gmail.com" />
                  </div>
                  <div>
                    <label className="text-sm font-medium mb-2 block">Password</label>
                    <Input placeholder="••••••••" type="password" />
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium mb-2 block">From Email</label>
                  <Input placeholder="noreply@eventmanager.com" />
                </div>
                <Button variant="outline">
                  <RefreshCw className="h-4 w-4 mr-2" />
                  Test Email Connection
                </Button>
              </CardContent>
            </Card>
          )}

          {activeTab === 'security' && (
            <Card>
              <CardHeader>
                <CardTitle>Security Settings</CardTitle>
                <CardDescription>
                  Configure security and authentication settings
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div>
                  <label className="text-sm font-medium mb-2 block">Session Timeout (minutes)</label>
                  <Input placeholder="30" type="number" />
                </div>
                <div>
                  <label className="text-sm font-medium mb-2 block">Password Requirements</label>
                  <div className="space-y-2">
                    <label className="flex items-center">
                      <input type="checkbox" className="mr-2" defaultChecked />
                      Minimum 8 characters
                    </label>
                    <label className="flex items-center">
                      <input type="checkbox" className="mr-2" defaultChecked />
                      Require uppercase letter
                    </label>
                    <label className="flex items-center">
                      <input type="checkbox" className="mr-2" defaultChecked />
                      Require number
                    </label>
                    <label className="flex items-center">
                      <input type="checkbox" className="mr-2" />
                      Require special character
                    </label>
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium mb-2 block">Rate Limiting</label>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                      <label className="text-xs text-muted-foreground">Requests per minute</label>
                      <Input placeholder="100" type="number" />
                    </div>
                    <div>
                      <label className="text-xs text-muted-foreground">Window (minutes)</label>
                      <Input placeholder="15" type="number" />
                    </div>
                  </div>
                </div>
              </CardContent>
            </Card>
          )}

          {activeTab === 'database' && (
            <Card>
              <CardHeader>
                <CardTitle>Database Settings</CardTitle>
                <CardDescription>
                  Database configuration and maintenance
                </CardDescription>
              </CardHeader>
              <CardContent className="space-y-4">
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="text-sm font-medium mb-2 block">Database Type</label>
                    <select className="w-full h-10 px-3 py-2 border border-input bg-background rounded-md text-sm" disabled>
                      <option value="postgresql">PostgreSQL</option>
                    </select>
                  </div>
                  <div>
                    <label className="text-sm font-medium mb-2 block">Connection Pool Size</label>
                    <Input placeholder="20" type="number" />
                  </div>
                </div>
                <div>
                  <label className="text-sm font-medium mb-2 block">Backup Settings</label>
                  <div className="space-y-2">
                    <label className="flex items-center">
                      <input type="checkbox" className="mr-2" defaultChecked />
                      Enable automatic backups
                    </label>
                    <label className="flex items-center">
                      <input type="checkbox" className="mr-2" />
                      Include file uploads in backup
                    </label>
                  </div>
                </div>
                <div className="flex space-x-2">
                  <Button variant="outline">
                    <Database className="h-4 w-4 mr-2" />
                    Create Backup
                  </Button>
                  <Button variant="outline">
                    <RefreshCw className="h-4 w-4 mr-2" />
                    Test Connection
                  </Button>
                </div>
              </CardContent>
            </Card>
          )}
        </div>
      </div>
    </div>
  )
}