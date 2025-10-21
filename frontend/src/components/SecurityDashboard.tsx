import React, { useState, useEffect } from 'react'
import { useAuth } from '../contexts/AuthContext'
import { ShieldCheckIcon, ExclamationTriangleIcon, ClockIcon, CheckCircleIcon } from '@heroicons/react/24/outline'

interface SecurityStatus {
  csrfProtection: boolean
  rateLimiting: boolean
  sessionSecurity: boolean
  inputValidation: boolean
  sqlInjectionProtection: boolean
  xssProtection: boolean
  lastSecurityCheck: string
  vulnerabilities: number
  recommendations: string[]
}

const SecurityDashboard: React.FC = () => {
  const { user } = useAuth()
  const [securityStatus, setSecurityStatus] = useState<SecurityStatus | null>(null)
  const [isLoading, setIsLoading] = useState(true)

  useEffect(() => {
    // Simulate security status check
    const checkSecurity = async () => {
      setIsLoading(true)
      // In a real implementation, this would call the backend API
      setTimeout(() => {
        setSecurityStatus({
          csrfProtection: true,
          rateLimiting: true,
          sessionSecurity: true,
          inputValidation: true,
          sqlInjectionProtection: true,
          xssProtection: true,
          lastSecurityCheck: new Date().toISOString(),
          vulnerabilities: 0,
          recommendations: [
            'Enable HTTPS in production',
            'Regular security updates',
            'Monitor failed login attempts',
            'Implement password complexity requirements',
          ],
        })
        setIsLoading(false)
      }, 1000)
    }

    checkSecurity()
  }, [])

  const getSecurityIcon = (status: boolean) => {
    return status ? (
      <CheckCircleIcon className="h-5 w-5 text-green-500" />
    ) : (
      <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />
    )
  }

  const getSecurityColor = (status: boolean) => {
    return status ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'
  }

  if (!user || (user.role !== 'ORGANIZER' && user.role !== 'BOARD')) {
    return (
      <div className="card">
        <div className="card-content text-center py-12">
          <ShieldCheckIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            Access Restricted
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            You don't have permission to view security settings.
          </p>
        </div>
      </div>
    )
  }

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Security Dashboard</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Monitor security status and configure protection settings
        </p>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : securityStatus ? (
        <>
          {/* Security Overview */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ShieldCheckIcon className="h-8 w-8 text-green-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Security Status</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {securityStatus.vulnerabilities === 0 ? 'Secure' : 'Issues Found'}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ExclamationTriangleIcon className="h-8 w-8 text-yellow-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Vulnerabilities</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {securityStatus.vulnerabilities}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ClockIcon className="h-8 w-8 text-blue-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Last Check</p>
                    <p className="text-sm font-semibold text-gray-900 dark:text-white">
                      {new Date(securityStatus.lastSecurityCheck).toLocaleDateString()}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Security Features */}
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Security Features</h3>
              <p className="card-description">Current security protection status</p>
            </div>
            <div className="card-content">
              <div className="space-y-4">
                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div className="flex items-center space-x-3">
                    {getSecurityIcon(securityStatus.csrfProtection)}
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">CSRF Protection</p>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Prevents cross-site request forgery attacks
                      </p>
                    </div>
                  </div>
                  <span className={`text-sm font-medium ${getSecurityColor(securityStatus.csrfProtection)}`}>
                    {securityStatus.csrfProtection ? 'Enabled' : 'Disabled'}
                  </span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div className="flex items-center space-x-3">
                    {getSecurityIcon(securityStatus.rateLimiting)}
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">Rate Limiting</p>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Prevents brute force and DDoS attacks
                      </p>
                    </div>
                  </div>
                  <span className={`text-sm font-medium ${getSecurityColor(securityStatus.rateLimiting)}`}>
                    {securityStatus.rateLimiting ? 'Enabled' : 'Disabled'}
                  </span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div className="flex items-center space-x-3">
                    {getSecurityIcon(securityStatus.sessionSecurity)}
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">Session Security</p>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Secure session management and timeout
                      </p>
                    </div>
                  </div>
                  <span className={`text-sm font-medium ${getSecurityColor(securityStatus.sessionSecurity)}`}>
                    {securityStatus.sessionSecurity ? 'Enabled' : 'Disabled'}
                  </span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div className="flex items-center space-x-3">
                    {getSecurityIcon(securityStatus.inputValidation)}
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">Input Validation</p>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Validates and sanitizes all user inputs
                      </p>
                    </div>
                  </div>
                  <span className={`text-sm font-medium ${getSecurityColor(securityStatus.inputValidation)}`}>
                    {securityStatus.inputValidation ? 'Enabled' : 'Disabled'}
                  </span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div className="flex items-center space-x-3">
                    {getSecurityIcon(securityStatus.sqlInjectionProtection)}
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">SQL Injection Protection</p>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Prevents SQL injection attacks
                      </p>
                    </div>
                  </div>
                  <span className={`text-sm font-medium ${getSecurityColor(securityStatus.sqlInjectionProtection)}`}>
                    {securityStatus.sqlInjectionProtection ? 'Enabled' : 'Disabled'}
                  </span>
                </div>

                <div className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <div className="flex items-center space-x-3">
                    {getSecurityIcon(securityStatus.xssProtection)}
                    <div>
                      <p className="font-medium text-gray-900 dark:text-white">XSS Protection</p>
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Prevents cross-site scripting attacks
                      </p>
                    </div>
                  </div>
                  <span className={`text-sm font-medium ${getSecurityColor(securityStatus.xssProtection)}`}>
                    {securityStatus.xssProtection ? 'Enabled' : 'Disabled'}
                  </span>
                </div>
              </div>
            </div>
          </div>

          {/* Security Recommendations */}
          {securityStatus.recommendations.length > 0 && (
            <div className="card">
              <div className="card-header">
                <h3 className="card-title">Security Recommendations</h3>
                <p className="card-description">Improve your security posture</p>
              </div>
              <div className="card-content">
                <div className="space-y-3">
                  {securityStatus.recommendations.map((recommendation, index) => (
                    <div key={index} className="flex items-start space-x-3 p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
                      <ExclamationTriangleIcon className="h-5 w-5 text-yellow-500 mt-0.5" />
                      <p className="text-sm text-gray-700 dark:text-gray-300">{recommendation}</p>
                    </div>
                  ))}
                </div>
              </div>
            </div>
          )}
        </>
      ) : (
        <div className="card">
          <div className="card-content text-center py-8">
            <ExclamationTriangleIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <p className="text-gray-600 dark:text-gray-400">Unable to load security status</p>
          </div>
        </div>
      )}
    </div>
  )
}

export default SecurityDashboard
