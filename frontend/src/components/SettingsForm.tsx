import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { settingsAPI } from '../services/api'
import {
  CogIcon,
  EnvelopeIcon,
  ServerIcon,
  ShieldCheckIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline'

interface SystemSetting {
  id: string
  key: string
  value: string
  description: string
  category: string
  type: 'string' | 'number' | 'boolean' | 'json'
  isPublic: boolean
  updatedAt: string
  updatedBy: string
}

const SettingsForm: React.FC = () => {
  const queryClient = useQueryClient()
  const [activeCategory, setActiveCategory] = useState<string>('general')
  const [testResults, setTestResults] = useState<Record<string, any>>({})

  const { data: settings, isLoading } = useQuery(
    'settings',
    () => settingsAPI.getAll().then(res => res.data),
    {
      refetchInterval: 60000,
    }
  )

  const updateMutation = useMutation(
    (data: Record<string, any>) => settingsAPI.update(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('settings')
      },
    }
  )

  const testMutation = useMutation(
    (type: 'email' | 'database' | 'backup') => settingsAPI.test(type),
    {
      onSuccess: (data, type) => {
        setTestResults(prev => ({ ...prev, [type]: data }))
      },
    }
  )

  const getSettingsByCategory = (category: string) => {
    return settings?.filter((setting: SystemSetting) => setting.category === category) || []
  }

  const handleSettingChange = (key: string, value: string) => {
    updateMutation.mutate({ [key]: value })
  }

  const handleTest = (type: 'email' | 'database' | 'backup') => {
    testMutation.mutate(type)
  }

  const categories = [
    { id: 'general', label: 'General', icon: CogIcon },
    { id: 'email', label: 'Email', icon: EnvelopeIcon },
    { id: 'database', label: 'Database', icon: ServerIcon },
    { id: 'security', label: 'Security', icon: ShieldCheckIcon },
  ]

  const renderSettingInput = (setting: SystemSetting) => {
    const commonProps = {
      value: setting.value,
      onChange: (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement | HTMLSelectElement>) =>
        handleSettingChange(setting.key, e.target.value),
      className: 'input w-full',
    }

    switch (setting.type) {
      case 'boolean':
        return (
          <select {...commonProps}>
            <option value="true">Enabled</option>
            <option value="false">Disabled</option>
          </select>
        )
      case 'number':
        return <input type="number" {...commonProps} />
      case 'json':
        return (
          <textarea
            {...commonProps}
            rows={4}
            className="input w-full"
            placeholder="Enter JSON configuration..."
          />
        )
      default:
        return <input type="text" {...commonProps} />
    }
  }

  const getTestResult = (type: string) => {
    const result = testResults[type]
    if (!result) return null

    if (result.success) {
      return (
        <div className="flex items-center space-x-2 text-green-600 dark:text-green-400">
          <CheckCircleIcon className="h-4 w-4" />
          <span className="text-sm">Test successful</span>
        </div>
      )
    } else {
      return (
        <div className="flex items-center space-x-2 text-red-600 dark:text-red-400">
          <ExclamationTriangleIcon className="h-4 w-4" />
          <span className="text-sm">Test failed: {result.error}</span>
        </div>
      )
    }
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">System Settings</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Configure system settings and preferences
        </p>
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-4 gap-6">
        {/* Category Sidebar */}
        <div className="lg:col-span-1">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Categories</h3>
            </div>
            <div className="card-content">
              <nav className="space-y-1">
                {categories.map((category) => (
                  <button
                    key={category.id}
                    onClick={() => setActiveCategory(category.id)}
                    className={`w-full flex items-center space-x-3 px-3 py-2 text-sm font-medium rounded-md transition-colors ${
                      activeCategory === category.id
                        ? 'bg-primary text-primary-foreground'
                        : 'text-gray-700 dark:text-gray-300 hover:bg-gray-100 dark:hover:bg-gray-700'
                    }`}
                  >
                    <category.icon className="h-4 w-4" />
                    <span>{category.label}</span>
                  </button>
                ))}
              </nav>
            </div>
          </div>
        </div>

        {/* Settings Content */}
        <div className="lg:col-span-3">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">
                {categories.find(c => c.id === activeCategory)?.label} Settings
              </h3>
            </div>
            <div className="card-content">
              {isLoading ? (
                <div className="flex items-center justify-center py-8">
                  <div className="loading-spinner"></div>
                </div>
              ) : (
                <div className="space-y-6">
                  {getSettingsByCategory(activeCategory).map((setting: SystemSetting) => (
                    <div key={setting.id} className="space-y-2">
                      <div className="flex items-center justify-between">
                        <label className="text-sm font-medium text-gray-700 dark:text-gray-300">
                          {setting.key.replace(/_/g, ' ').replace(/\b\w/g, l => l.toUpperCase())}
                        </label>
                        {!setting.isPublic && (
                          <span className="text-xs text-gray-500 dark:text-gray-400">Private</span>
                        )}
                      </div>
                      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                          {renderSettingInput(setting)}
                        </div>
                        <div className="flex items-center">
                          {activeCategory === 'email' && setting.key.includes('smtp') && (
                            <button
                              onClick={() => handleTest('email')}
                              disabled={testMutation.isLoading}
                              className="btn btn-outline btn-sm"
                            >
                              Test Email
                            </button>
                          )}
                          {activeCategory === 'database' && setting.key.includes('database') && (
                            <button
                              onClick={() => handleTest('database')}
                              disabled={testMutation.isLoading}
                              className="btn btn-outline btn-sm"
                            >
                              Test Connection
                            </button>
                          )}
                        </div>
                      </div>
                      <p className="text-xs text-gray-500 dark:text-gray-400">
                        {setting.description}
                      </p>
                      {getTestResult(activeCategory)}
                    </div>
                  ))}

                  {getSettingsByCategory(activeCategory).length === 0 && (
                    <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                      <CogIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                      <p>No settings found for this category</p>
                    </div>
                  )}
                </div>
              )}
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default SettingsForm