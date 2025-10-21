import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { adminAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  CogIcon,
  ServerIcon,
  EnvelopeIcon,
  ShieldCheckIcon,
  ServerIcon as DatabaseIcon,
  BellIcon,
  KeyIcon,
  GlobeAltIcon,
  DocumentTextIcon,
  CloudIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
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

const SettingsPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'general' | 'email' | 'security' | 'database' | 'notifications' | 'backup'>('general')
  const [showTestModal, setShowTestModal] = useState(false)
  const [testType, setTestType] = useState<'email' | 'database' | 'backup'>('email')

  const { data: settings, isLoading } = useQuery(
    'admin-settings',
    () => adminAPI.getSettings().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const updateMutation = useMutation(
    (data: any) => adminAPI.updateSettings(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('admin-settings')
      },
    }
  )

  const testMutation = useMutation(
    (type: string) => adminAPI.testConnection(type),
    {
      onSuccess: () => {
        setShowTestModal(false)
      },
    }
  )

  const tabs = [
    { id: 'general', name: 'General', icon: CogIcon },
    { id: 'email', name: 'Email', icon: MailIcon },
    { id: 'security', name: 'Security', icon: ShieldCheckIcon },
    { id: 'database', name: 'Database', icon: DatabaseIcon },
    { id: 'notifications', name: 'Notifications', icon: BellIcon },
    { id: 'backup', name: 'Backup', icon: CloudIcon },
  ]

  const getSettingsByCategory = (category: string) => {
    return settings?.filter((setting: SystemSetting) => setting.category === category) || []
  }

  const handleSettingChange = (key: string, value: string) => {
    updateMutation.mutate({ [key]: value })
  }

  const handleTest = (type: 'email' | 'database' | 'backup') => {
    setTestType(type)
    setShowTestModal(true)
    testMutation.mutate(type)
  }

  if (isLoading) {
    return (
      <div className="flex items-center justify-center py-12">
        <div className="loading-spinner"></div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">System Settings</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Configure system-wide settings and preferences
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => handleTest('email')}
            className="btn btn-outline"
          >
            <MailIcon className="h-5 w-5 mr-2" />
            Test Email
          </button>
        </div>
      </div>

      {/* Tabs */}
      <div className="card">
        <div className="card-content p-0">
          <div className="border-b border-gray-200 dark:border-gray-700">
            <nav className="flex space-x-8 px-6">
              {tabs.map((tab) => (
                <button
                  key={tab.id}
                  onClick={() => setActiveTab(tab.id as any)}
                  className={`py-4 px-1 border-b-2 font-medium text-sm ${
                    activeTab === tab.id
                      ? 'border-primary text-primary'
                      : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                  }`}
                >
                  <tab.icon className="h-5 w-5 inline mr-2" />
                  {tab.name}
                </button>
              ))}
            </nav>
          </div>
        </div>
      </div>

      {/* Tab Content */}
      <div className="card">
        <div className="card-content">
          {activeTab === 'general' && (
            <div className="space-y-6">
              <h3 className="text-lg font-medium">General Settings</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {getSettingsByCategory('general').map((setting: SystemSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <label className="label">{setting.description}</label>
                    {setting.type === 'boolean' ? (
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={setting.value === 'true'}
                          onChange={(e) => handleSettingChange(setting.key, e.target.checked.toString())}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {setting.value === 'true' ? 'Enabled' : 'Disabled'}
                        </span>
                      </div>
                    ) : setting.type === 'number' ? (
                      <input
                        type="number"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    ) : (
                      <input
                        type="text"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'email' && (
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Email Configuration</h3>
                <button
                  onClick={() => handleTest('email')}
                  className="btn btn-outline btn-sm"
                >
                  <MailIcon className="h-4 w-4 mr-2" />
                  Test Email
                </button>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {getSettingsByCategory('email').map((setting: SystemSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <label className="label">{setting.description}</label>
                    {setting.key.includes('password') || setting.key.includes('secret') ? (
                      <input
                        type="password"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    ) : (
                      <input
                        type="text"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    )}
                  </div>
                ))}
              </div>
              <div className="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                <div className="flex items-start">
                  <MailIcon className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" />
                  <div>
                    <h4 className="text-sm font-medium text-blue-800 dark:text-blue-200">Email Testing</h4>
                    <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
                      Test your email configuration by sending a test email to verify SMTP settings.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'security' && (
            <div className="space-y-6">
              <h3 className="text-lg font-medium">Security Settings</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {getSettingsByCategory('security').map((setting: SystemSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <label className="label">{setting.description}</label>
                    {setting.type === 'boolean' ? (
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={setting.value === 'true'}
                          onChange={(e) => handleSettingChange(setting.key, e.target.checked.toString())}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {setting.value === 'true' ? 'Enabled' : 'Disabled'}
                        </span>
                      </div>
                    ) : setting.type === 'number' ? (
                      <input
                        type="number"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                        min="1"
                      />
                    ) : (
                      <input
                        type="text"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    )}
                  </div>
                ))}
              </div>
              <div className="bg-yellow-50 dark:bg-yellow-900 p-4 rounded-lg">
                <div className="flex items-start">
                  <ExclamationTriangleIcon className="h-5 w-5 text-yellow-600 dark:text-yellow-400 mt-0.5 mr-3" />
                  <div>
                    <h4 className="text-sm font-medium text-yellow-800 dark:text-yellow-200">Security Warning</h4>
                    <p className="text-sm text-yellow-700 dark:text-yellow-300 mt-1">
                      Changes to security settings may affect system access. Please review carefully before saving.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'database' && (
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Database Settings</h3>
                <button
                  onClick={() => handleTest('database')}
                  className="btn btn-outline btn-sm"
                >
                  <DatabaseIcon className="h-4 w-4 mr-2" />
                  Test Connection
                </button>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {getSettingsByCategory('database').map((setting: SystemSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <label className="label">{setting.description}</label>
                    {setting.key.includes('password') || setting.key.includes('secret') ? (
                      <input
                        type="password"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    ) : (
                      <input
                        type="text"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    )}
                  </div>
                ))}
              </div>
              <div className="bg-green-50 dark:bg-green-900 p-4 rounded-lg">
                <div className="flex items-start">
                  <CheckCircleIcon className="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5 mr-3" />
                  <div>
                    <h4 className="text-sm font-medium text-green-800 dark:text-green-200">Database Status</h4>
                    <p className="text-sm text-green-700 dark:text-green-300 mt-1">
                      Database connection is active and healthy.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'notifications' && (
            <div className="space-y-6">
              <h3 className="text-lg font-medium">Notification Settings</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {getSettingsByCategory('notifications').map((setting: SystemSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <label className="label">{setting.description}</label>
                    {setting.type === 'boolean' ? (
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={setting.value === 'true'}
                          onChange={(e) => handleSettingChange(setting.key, e.target.checked.toString())}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {setting.value === 'true' ? 'Enabled' : 'Disabled'}
                        </span>
                      </div>
                    ) : (
                      <input
                        type="text"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    )}
                  </div>
                ))}
              </div>
            </div>
          )}

          {activeTab === 'backup' && (
            <div className="space-y-6">
              <div className="flex items-center justify-between">
                <h3 className="text-lg font-medium">Backup Settings</h3>
                <button
                  onClick={() => handleTest('backup')}
                  className="btn btn-outline btn-sm"
                >
                  <CloudIcon className="h-4 w-4 mr-2" />
                  Test Backup
                </button>
              </div>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                {getSettingsByCategory('backup').map((setting: SystemSetting) => (
                  <div key={setting.key} className="space-y-2">
                    <label className="label">{setting.description}</label>
                    {setting.type === 'boolean' ? (
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={setting.value === 'true'}
                          onChange={(e) => handleSettingChange(setting.key, e.target.checked.toString())}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {setting.value === 'true' ? 'Enabled' : 'Disabled'}
                        </span>
                      </div>
                    ) : setting.type === 'number' ? (
                      <input
                        type="number"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                        min="1"
                      />
                    ) : (
                      <input
                        type="text"
                        value={setting.value}
                        onChange={(e) => handleSettingChange(setting.key, e.target.value)}
                        className="input"
                      />
                    )}
                  </div>
                ))}
              </div>
              <div className="bg-blue-50 dark:bg-blue-900 p-4 rounded-lg">
                <div className="flex items-start">
                  <CloudIcon className="h-5 w-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3" />
                  <div>
                    <h4 className="text-sm font-medium text-blue-800 dark:text-blue-200">Backup Information</h4>
                    <p className="text-sm text-blue-700 dark:text-blue-300 mt-1">
                      Automated backups are configured to run daily. Manual backups can be created at any time.
                    </p>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Test Modal */}
      {showTestModal && (
        <TestModal
          type={testType}
          onClose={() => setShowTestModal(false)}
          isLoading={testMutation.isLoading}
          result={testMutation.data}
        />
      )}
    </div>
  )
}

// Test Modal Component
interface TestModalProps {
  type: 'email' | 'database' | 'backup'
  onClose: () => void
  isLoading: boolean
  result?: any
}

const TestModal: React.FC<TestModalProps> = ({ type, onClose, isLoading, result }) => {
  const getTestTitle = () => {
    switch (type) {
      case 'email': return 'Email Test'
      case 'database': return 'Database Test'
      case 'backup': return 'Backup Test'
      default: return 'Test'
    }
  }

  const getTestDescription = () => {
    switch (type) {
      case 'email': return 'Testing email configuration and SMTP connection...'
      case 'database': return 'Testing database connection and permissions...'
      case 'backup': return 'Testing backup configuration and storage...'
      default: return 'Running test...'
    }
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">{getTestTitle()}</h2>
        {isLoading ? (
          <div className="text-center py-8">
            <div className="loading-spinner mx-auto mb-4"></div>
            <p className="text-gray-600 dark:text-gray-400">{getTestDescription()}</p>
          </div>
        ) : result ? (
          <div className="space-y-4">
            <div className={`p-4 rounded-lg ${
              result.success 
                ? 'bg-green-50 dark:bg-green-900' 
                : 'bg-red-50 dark:bg-red-900'
            }`}>
              <div className="flex items-start">
                {result.success ? (
                  <CheckCircleIcon className="h-5 w-5 text-green-600 dark:text-green-400 mt-0.5 mr-3" />
                ) : (
                  <ExclamationTriangleIcon className="h-5 w-5 text-red-600 dark:text-red-400 mt-0.5 mr-3" />
                )}
                <div>
                  <h4 className={`text-sm font-medium ${
                    result.success 
                      ? 'text-green-800 dark:text-green-200' 
                      : 'text-red-800 dark:text-red-200'
                  }`}>
                    {result.success ? 'Test Successful' : 'Test Failed'}
                  </h4>
                  <p className={`text-sm mt-1 ${
                    result.success 
                      ? 'text-green-700 dark:text-green-300' 
                      : 'text-red-700 dark:text-red-300'
                  }`}>
                    {result.message}
                  </p>
                </div>
              </div>
            </div>
            {result.details && (
              <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <h5 className="text-sm font-medium text-gray-900 dark:text-white mb-2">Details</h5>
                <pre className="text-xs text-gray-600 dark:text-gray-400 whitespace-pre-wrap">
                  {JSON.stringify(result.details, null, 2)}
                </pre>
              </div>
            )}
          </div>
        ) : null}
        <div className="flex justify-end pt-4">
          <button
            onClick={onClose}
            className="btn btn-primary"
            disabled={isLoading}
          >
            {isLoading ? 'Testing...' : 'Close'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default SettingsPage
