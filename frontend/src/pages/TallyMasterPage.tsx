import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { tallyMasterAPI } from '../services/api'
import {
  CheckCircleIcon,
  ClockIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
  EyeIcon,
  ChartBarIcon,
  ClipboardDocumentListIcon,
} from '@heroicons/react/24/outline'

const TallyMasterPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'dashboard' | 'certifications' | 'score-review' | 'reports'>('dashboard')

  const { data: tallyStats, isLoading: statsLoading } = useQuery(
    'tally-stats',
    () => tallyMasterAPI.getStats().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const { data: certificationQueue, isLoading: queueLoading } = useQuery(
    'certification-queue',
    () => tallyMasterAPI.getCertificationQueue().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const { data: pendingCertifications, isLoading: pendingLoading } = useQuery(
    'pending-certifications',
    () => tallyMasterAPI.getPendingCertifications().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const certifyTotalsMutation = useMutation(
    (data: any) => tallyMasterAPI.certifyTotals(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('tally-stats')
        queryClient.invalidateQueries('certification-queue')
        queryClient.invalidateQueries('pending-certifications')
      },
    }
  )

  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: ChartBarIcon },
    { id: 'certifications', label: 'Certifications', icon: ShieldCheckIcon },
    { id: 'score-review', label: 'Score Review', icon: ClipboardDocumentListIcon },
    { id: 'reports', label: 'Reports', icon: DocumentTextIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Tally Master Dashboard</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Review and certify contest scores after judges complete scoring
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
          <div className="grid grid-cols-1 md:grid-cols-3 gap-6">
            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ClockIcon className="h-8 w-8 text-yellow-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Review</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : tallyStats?.pendingReview || 0}
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
                      {statsLoading ? '--' : tallyStats?.certified || 0}
                    </p>
                  </div>
                </div>
              </div>
            </div>

            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ExclamationTriangleIcon className="h-8 w-8 text-red-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Issues Found</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : tallyStats?.issuesFound || 0}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Certification Queue */}
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Certification Queue</h3>
              <p className="card-description">Categories ready for tally master certification</p>
            </div>
            <div className="card-content">
              {queueLoading ? (
                <div className="flex items-center justify-center py-8">
                  <div className="loading-spinner"></div>
                </div>
              ) : certificationQueue && certificationQueue.length > 0 ? (
                <div className="space-y-3">
                  {certificationQueue.map((item: any) => (
                    <div key={item.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                      <div className="flex items-center space-x-3">
                        <div className="w-10 h-10 bg-blue-500 rounded-lg flex items-center justify-center">
                          <span className="text-white text-sm font-medium">
                            {item.categoryName?.charAt(0).toUpperCase()}
                          </span>
                        </div>
                        <div>
                          <p className="font-medium text-gray-900 dark:text-white">{item.categoryName}</p>
                          <p className="text-sm text-gray-600 dark:text-gray-400">{item.contestName}</p>
                        </div>
                      </div>
                      <div className="flex items-center space-x-2">
                        <span className={`badge ${
                          item.status === 'PENDING' ? 'badge-warning' : 'badge-success'
                        }`}>
                          {item.status}
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
                  <CheckCircleIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No pending certifications</p>
                  <p className="text-sm mt-2">Categories will appear here when judges complete scoring</p>
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
              <h3 className="card-title">Certification Management</h3>
              <p className="card-description">Review and certify totals after all judges have completed scoring</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <ShieldCheckIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Certification Management</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain certification management functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'score-review' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Score Review</h3>
              <p className="card-description">Review individual scores and verify calculations</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <ClipboardDocumentListIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Score Review</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain detailed score review functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'reports' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Tally Reports</h3>
              <p className="card-description">Generate reports for tally master activities and certifications</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Tally Reports</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain tally master report generation functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default TallyMasterPage
