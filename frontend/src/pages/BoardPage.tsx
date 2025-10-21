import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { boardAPI } from '../services/api'
import {
  ShieldCheckIcon,
  DocumentTextIcon,
  PrinterIcon,
  ChartBarIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  CogIcon,
} from '@heroicons/react/24/outline'

const BoardPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'dashboard' | 'certifications' | 'scripts' | 'reports' | 'scores'>('dashboard')

  const { data: boardStats, isLoading: statsLoading } = useQuery(
    'board-stats',
    () => boardAPI.getStats().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const { data: certificationStatus, isLoading: certLoading } = useQuery(
    'certification-status',
    () => boardAPI.getCertificationStatus().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const { data: emceeScripts, isLoading: scriptsLoading } = useQuery(
    'emcee-scripts',
    () => boardAPI.getEmceeScripts().then(res => res.data),
    {
      refetchInterval: 60000,
    }
  )

  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: ChartBarIcon },
    { id: 'certifications', label: 'Certifications', icon: ShieldCheckIcon },
    { id: 'scripts', label: 'Emcee Scripts', icon: DocumentTextIcon },
    { id: 'reports', label: 'Print Reports', icon: PrinterIcon },
    { id: 'scores', label: 'Score Management', icon: CogIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Board Dashboard</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Administrative oversight and final certification management
        </p>
      </div>

      {/* Tabs */}
      <div className="border-b border-gray-200 dark:border-gray-700">
        <nav className="-mb-px flex space-x-8">
          {tabs.map((tab) => (
            <button
              key={tab.id}
              onClick={() => setActiveTab(tab.id as any)}
              className={`flex items-center space-x-2 py-2 px-1 border-b-2 font-medium text-sm ${
                activeTab === tab.id
                  ? 'border-primary text-primary'
                  : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300 dark:text-gray-400 dark:hover:text-gray-300'
              }`}
            >
              <tab.icon className="h-4 w-4" />
              <span>{tab.label}</span>
            </button>
          ))}
        </nav>
      </div>

      {/* Tab Content */}
      {activeTab === 'dashboard' && (
        <div className="space-y-6">
          {/* Stats Cards */}
          <div className="grid grid-cols-1 md:grid-cols-4 gap-6">
            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ChartBarIcon className="h-8 w-8 text-blue-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Contests</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : boardStats?.contests || 0}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ShieldCheckIcon className="h-8 w-8 text-green-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Categories</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : boardStats?.categories || 0}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <CheckCircleIcon className="h-8 w-8 text-green-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Certified</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : boardStats?.certified || 0}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ClockIcon className="h-8 w-8 text-yellow-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Pending</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : boardStats?.pending || 0}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Certification Summary */}
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Certification Summary</h3>
              <p className="card-description">Monitor the certification progress across all levels</p>
            </div>
            <div className="card-content">
              {certLoading ? (
                <div className="flex items-center justify-center py-8">
                  <div className="loading-spinner"></div>
                </div>
              ) : certificationStatus && certificationStatus.length > 0 ? (
                <div className="space-y-3">
                  {certificationStatus.map((status: any) => (
                    <div key={status.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                      <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                          <span className="text-white text-sm font-medium">
                            {status.categoryName?.charAt(0).toUpperCase()}
                          </span>
                        </div>
                        <div>
                          <p className="font-medium text-gray-900 dark:text-white">{status.categoryName}</p>
                          <p className="text-sm text-gray-600 dark:text-gray-400">{status.contestName}</p>
                        </div>
                      </div>
                      <div className="flex items-center space-x-2">
                        <span className={`badge ${
                          status.status === 'CERTIFIED' ? 'badge-success' : 
                          status.status === 'PENDING' ? 'badge-warning' : 'badge-secondary'
                        }`}>
                          {status.status}
                        </span>
                        <button className="btn btn-primary btn-sm">
                          <EyeIcon className="h-4 w-4" />
                        </button>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <ShieldCheckIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No certification data available</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {activeTab === 'certifications' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Certification Status</h3>
              <p className="card-description">Monitor the certification progress across all levels: Judges, Tally Masters, and Auditors</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <ShieldCheckIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Certification Status</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain detailed certification status monitoring</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'scripts' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Emcee Scripts</h3>
              <p className="card-description">Manage contest scripts and announcements for emcees</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Emcee Scripts</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain emcee script management functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'reports' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Print Reports</h3>
              <p className="card-description">Generate and print result reports for contests and categories</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <PrinterIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Print Reports</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain print report generation functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'scores' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Score Management</h3>
              <p className="card-description">Remove judge scores with proper authorization and co-signatures</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <CogIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Score Management</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain score management functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default BoardPage
