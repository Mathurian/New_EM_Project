import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { auditorAPI } from '../services/api'
import {
  CheckCircleIcon,
  ExclamationTriangleIcon,
  DocumentTextIcon,
  ShieldCheckIcon,
  ClockIcon,
  EyeIcon,
  PrinterIcon,
  ChartBarIcon,
} from '@heroicons/react/24/outline'

const AuditorPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'dashboard' | 'scores' | 'certifications' | 'reports'>('dashboard')

  const { data: auditStats, isLoading: statsLoading } = useQuery(
    'auditor-stats',
    () => auditorAPI.getStats().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const { data: pendingAudits, isLoading: auditsLoading } = useQuery(
    'pending-audits',
    () => auditorAPI.getPendingAudits().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const { data: completedAudits, isLoading: completedLoading } = useQuery(
    'completed-audits',
    () => auditorAPI.getCompletedAudits().then(res => res.data),
    {
      refetchInterval: 60000,
    }
  )

  const finalCertificationMutation = useMutation(
    (data: any) => auditorAPI.finalCertification(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('auditor-stats')
        queryClient.invalidateQueries('pending-audits')
        queryClient.invalidateQueries('completed-audits')
      },
    }
  )

  const rejectAuditMutation = useMutation(
    ({ auditId, reason }: { auditId: string; reason: string }) => auditorAPI.rejectAudit(auditId, reason),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('auditor-stats')
        queryClient.invalidateQueries('pending-audits')
        queryClient.invalidateQueries('completed-audits')
      },
    }
  )

  const tabs = [
    { id: 'dashboard', label: 'Dashboard', icon: ChartBarIcon },
    { id: 'scores', label: 'Score Audit', icon: DocumentTextIcon },
    { id: 'certifications', label: 'Certifications', icon: ShieldCheckIcon },
    { id: 'reports', label: 'Reports', icon: PrinterIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Auditor Dashboard</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Review and verify all scores across contests, categories, and subcategories
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
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Pending Audits</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : auditStats?.pendingAudits || 0}
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
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Completed Audits</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {statsLoading ? '--' : auditStats?.completedAudits || 0}
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
                      {statsLoading ? '--' : auditStats?.issuesFound || 0}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Recent Activity */}
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Recent Audit Activity</h3>
            </div>
            <div className="card-content">
              {auditsLoading ? (
                <div className="flex items-center justify-center py-8">
                  <div className="loading-spinner"></div>
                </div>
              ) : pendingAudits && pendingAudits.length > 0 ? (
                <div className="space-y-3">
                  {pendingAudits.slice(0, 5).map((audit: any) => (
                    <div key={audit.id} className="flex items-center justify-between p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                      <div className="flex items-center space-x-3">
                        <DocumentTextIcon className="h-5 w-5 text-gray-400" />
                        <div>
                          <p className="font-medium text-gray-900 dark:text-white">{audit.categoryName}</p>
                          <p className="text-sm text-gray-600 dark:text-gray-400">{audit.contestName}</p>
                        </div>
                      </div>
                      <div className="flex items-center space-x-2">
                        <span className="badge badge-warning">Pending</span>
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
                  <p>No pending audits</p>
                  <p className="text-sm mt-2">All categories have been audited</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {activeTab === 'scores' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Score Audit</h3>
              <p className="card-description">Review and verify all scores across contests and categories</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Score Audit</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain comprehensive score auditing functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'certifications' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Final Certification</h3>
              <p className="card-description">Sign and certify final totals after all Tally Masters have completed their verification</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <ShieldCheckIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Final Certification</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain final certification functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'reports' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Audit Reports</h3>
              <p className="card-description">Generate comprehensive audit reports and summaries</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <PrinterIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Audit Reports</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain audit report generation functionality</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default AuditorPage
