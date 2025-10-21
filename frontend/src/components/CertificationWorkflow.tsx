import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { scoringAPI, contestsAPI, categoriesAPI, api } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import { getStatusColor, getStepIcon } from '../utils/helpers'
import {
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
  XCircleIcon,
  EyeIcon,
  PencilIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  UserIcon,
  TrophyIcon,
  DocumentTextIcon,
  ArrowRightIcon,
  ArrowDownIcon,
  ShieldCheckIcon,
  ClipboardDocumentCheckIcon,
  ExclamationCircleIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface CertificationStep {
  id: string
  name: string
  role: 'JUDGE' | 'TALLY_MASTER' | 'AUDITOR' | 'BOARD'
  status: 'PENDING' | 'IN_PROGRESS' | 'COMPLETED' | 'REJECTED'
  completedBy?: string
  completedAt?: string
  comments?: string
  order: number
}

interface Certification {
  id: string
  categoryId: string
  categoryName: string
  contestId: string
  contestName: string
  eventId: string
  eventName: string
  status: 'PENDING' | 'IN_PROGRESS' | 'CERTIFIED' | 'REJECTED'
  currentStep: number
  steps: CertificationStep[]
  totalScores: number
  averageScore: number
  createdAt: string
  updatedAt: string
  certifiedAt?: string
  certifiedBy?: string
  rejectionReason?: string
}

const CertificationWorkflow: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'queue' | 'in-progress' | 'certified' | 'rejected'>('queue')
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState('')
  const [selectedCertification, setSelectedCertification] = useState<Certification | null>(null)
  const [showDetails, setShowDetails] = useState(false)
  const [showApprovalModal, setShowApprovalModal] = useState(false)

  // Fetch certifications based on user role
  const { data: certifications, isLoading: certificationsLoading } = useQuery(
    ['certifications', activeTab, searchTerm, statusFilter],
    () => {
      const endpoint = user?.role === 'JUDGE' ? '/certifications/judge' :
                      user?.role === 'TALLY_MASTER' ? '/certifications/tally-master' :
                      user?.role === 'AUDITOR' ? '/certifications/auditor' :
                      user?.role === 'BOARD' ? '/certifications/board' :
                      '/certifications'
      
      return api.get(endpoint, {
        params: {
          status: activeTab === 'queue' ? 'PENDING' : 
                 activeTab === 'in-progress' ? 'IN_PROGRESS' :
                 activeTab === 'certified' ? 'CERTIFIED' :
                 activeTab === 'rejected' ? 'REJECTED' : undefined,
          search: searchTerm,
        }
      }).then(res => res.data)
    },
    {
      enabled: user?.role === 'JUDGE' || user?.role === 'TALLY_MASTER' || 
              user?.role === 'AUDITOR' || user?.role === 'BOARD' || 
              user?.role === 'ORGANIZER',
      refetchInterval: 30000, // Refresh every 30 seconds
    }
  )

  const approveCertificationMutation = useMutation(
    ({ id, comments }: { id: string; comments?: string }) =>
      api.post(`/certifications/${id}/approve`, { comments }),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('certifications')
        setShowApprovalModal(false)
        setSelectedCertification(null)
      },
    }
  )

  const rejectCertificationMutation = useMutation(
    ({ id, reason }: { id: string; reason: string }) =>
      api.post(`/certifications/${id}/reject`, { reason }),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('certifications')
        setShowApprovalModal(false)
        setSelectedCertification(null)
      },
    }
  )

  const filteredCertifications = certifications || []

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'PENDING': return 'badge-warning'
      case 'IN_PROGRESS': return 'badge-primary'
      case 'CERTIFIED': return 'badge-success'
      case 'REJECTED': return 'badge-destructive'
      default: return 'badge-secondary'
    }
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'PENDING': return <ClockIcon className="h-4 w-4 text-yellow-500" />
      case 'IN_PROGRESS': return <ExclamationTriangleIcon className="h-4 w-4 text-blue-500" />
      case 'CERTIFIED': return <CheckCircleIcon className="h-4 w-4 text-green-500" />
      case 'REJECTED': return <XCircleIcon className="h-4 w-4 text-red-500" />
      default: return <ClockIcon className="h-4 w-4 text-gray-500" />
    }
  }

  const getStepIcon = (stepStatus: string) => {
    switch (stepStatus) {
      case 'COMPLETED': return <CheckCircleIcon className="h-4 w-4 text-green-500" />
      case 'IN_PROGRESS': return <ClockIcon className="h-4 w-4 text-blue-500" />
      case 'REJECTED': return <XCircleIcon className="h-4 w-4 text-red-500" />
      default: return <ClockIcon className="h-4 w-4 text-gray-400" />
    }
  }

  const canApprove = (certification: Certification) => {
    if (!user) return false
    
    const currentStep = certification.steps.find(s => s.status === 'IN_PROGRESS')
    if (!currentStep) return false
    
    return currentStep.role === user.role
  }

  const tabs = [
    { id: 'queue', name: 'Pending Queue', icon: ClockIcon, count: filteredCertifications.filter((c: any) => c.status === 'PENDING').length },
    { id: 'in-progress', name: 'In Progress', icon: ExclamationTriangleIcon, count: filteredCertifications.filter((c: any) => c.status === 'IN_PROGRESS').length },
    { id: 'certified', name: 'Certified', icon: CheckCircleIcon, count: filteredCertifications.filter((c: any) => c.status === 'CERTIFIED').length },
    { id: 'rejected', name: 'Rejected', icon: XCircleIcon, count: filteredCertifications.filter((c: any) => c.status === 'REJECTED').length },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Certification Workflow</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Multi-level certification system: Judge → Tally Master → Auditor → Board
          </p>
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
                  {tab.count > 0 && (
                    <span className="ml-2 bg-gray-100 dark:bg-gray-700 text-gray-600 dark:text-gray-400 rounded-full px-2 py-1 text-xs">
                      {tab.count}
                    </span>
                  )}
                </button>
              ))}
            </nav>
          </div>
        </div>
      </div>

      {/* Filters */}
      <div className="card">
        <div className="card-content">
          <div className="flex space-x-4">
            <div className="relative flex-1">
              <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
              <input
                type="text"
                placeholder="Search certifications..."
                value={searchTerm}
                onChange={(e) => setSearchTerm(e.target.value)}
                className="input pl-10"
              />
            </div>
            <select
              value={statusFilter}
              onChange={(e) => setStatusFilter(e.target.value)}
              className="input"
            >
              <option value="">All Statuses</option>
              <option value="PENDING">Pending</option>
              <option value="IN_PROGRESS">In Progress</option>
              <option value="CERTIFIED">Certified</option>
              <option value="REJECTED">Rejected</option>
            </select>
          </div>
        </div>
      </div>

      {/* Certifications List */}
      <div className="card">
        <div className="card-content p-0">
          {certificationsLoading ? (
            <div className="flex items-center justify-center py-8">
              <div className="loading-spinner"></div>
            </div>
          ) : filteredCertifications.length === 0 ? (
            <div className="text-center py-8 text-gray-500 dark:text-gray-400">
              <ShieldCheckIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                No certifications found
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                {searchTerm || statusFilter
                  ? 'Try adjusting your search criteria'
                  : `No ${activeTab.replace('-', ' ')} certifications available`}
              </p>
            </div>
          ) : (
            <div className="overflow-x-auto">
              <table className="table">
                <thead>
                  <tr>
                    <th>Category</th>
                    <th>Contest</th>
                    <th>Status</th>
                    <th>Current Step</th>
                    <th>Score</th>
                    <th>Created</th>
                    <th>Actions</th>
                  </tr>
                </thead>
                <tbody>
                  {filteredCertifications.map((certification: any) => (
                    <tr key={certification.id}>
                      <td>
                        <div className="flex items-center space-x-2">
                          <TrophyIcon className="h-4 w-4 text-gray-400" />
                          <div>
                            <div className="font-medium text-gray-900 dark:text-white">
                              {certification.categoryName}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="text-gray-600 dark:text-gray-400">
                        {certification.contestName}
                      </td>
                      <td>
                        <div className="flex items-center space-x-2">
                          {getStatusIcon(certification.status)}
                          <span className={`badge ${getStatusColor(certification.status)}`}>
                            {certification.status.replace('_', ' ')}
                          </span>
                        </div>
                      </td>
                      <td>
                        <div className="flex items-center space-x-2">
                          <span className="text-sm text-gray-600 dark:text-gray-400">
                            {certification.steps.find((s: any) => s.status === 'IN_PROGRESS')?.name || 'Completed'}
                          </span>
                        </div>
                      </td>
                      <td>
                        <div className="text-sm">
                          <div className="font-medium text-gray-900 dark:text-white">
                            {certification.averageScore.toFixed(1)}
                          </div>
                          <div className="text-gray-600 dark:text-gray-400">
                            {certification.totalScores} scores
                          </div>
                        </div>
                      </td>
                      <td className="text-gray-600 dark:text-gray-400">
                        {format(new Date(certification.createdAt), 'MMM dd, yyyy')}
                      </td>
                      <td>
                        <div className="flex space-x-2">
                          <button
                            onClick={() => {
                              setSelectedCertification(certification)
                              setShowDetails(true)
                            }}
                            className="btn btn-outline btn-sm"
                          >
                            <EyeIcon className="h-4 w-4" />
                          </button>
                          {canApprove(certification) && (
                            <button
                              onClick={() => {
                                setSelectedCertification(certification)
                                setShowApprovalModal(true)
                              }}
                              className="btn btn-primary btn-sm"
                            >
                              <CheckCircleIcon className="h-4 w-4" />
                            </button>
                          )}
                        </div>
                      </td>
                    </tr>
                  ))}
                </tbody>
              </table>
            </div>
          )}
        </div>
      </div>

      {/* Details Modal */}
      {showDetails && selectedCertification && (
        <CertificationDetailsModal
          certification={selectedCertification}
          onClose={() => {
            setShowDetails(false)
            setSelectedCertification(null)
          }}
          onApprove={() => {
            setShowDetails(false)
            setShowApprovalModal(true)
          }}
          onReject={() => {
            setShowDetails(false)
            setShowApprovalModal(true)
          }}
          canApprove={canApprove(selectedCertification)}
        />
      )}

      {/* Approval Modal */}
      {showApprovalModal && selectedCertification && (
        <ApprovalModal
          certification={selectedCertification}
          onClose={() => {
            setShowApprovalModal(false)
            setSelectedCertification(null)
          }}
          onApprove={(comments) => {
            approveCertificationMutation.mutate({
              id: selectedCertification.id,
              comments,
            })
          }}
          onReject={(reason) => {
            rejectCertificationMutation.mutate({
              id: selectedCertification.id,
              reason,
            })
          }}
          isLoading={approveCertificationMutation.isLoading || rejectCertificationMutation.isLoading}
        />
      )}
    </div>
  )
}

// Certification Details Modal Component
interface CertificationDetailsModalProps {
  certification: Certification
  onClose: () => void
  onApprove: () => void
  onReject: () => void
  canApprove: boolean
}

const CertificationDetailsModal: React.FC<CertificationDetailsModalProps> = ({
  certification,
  onClose,
  onApprove,
  onReject,
  canApprove,
}) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-4xl">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
            Certification Details
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XCircleIcon className="h-6 w-6" />
          </button>
        </div>

        <div className="space-y-6">
          {/* Basic Information */}
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Category</label>
              <p className="text-gray-900 dark:text-white">{certification.categoryName}</p>
            </div>
            <div>
              <label className="label">Contest</label>
              <p className="text-gray-900 dark:text-white">{certification.contestName}</p>
            </div>
            <div>
              <label className="label">Event</label>
              <p className="text-gray-900 dark:text-white">{certification.eventName}</p>
            </div>
            <div>
              <label className="label">Status</label>
              <span className={`badge ${getStatusColor(certification.status)}`}>
                {certification.status.replace('_', ' ')}
              </span>
            </div>
            <div>
              <label className="label">Average Score</label>
              <p className="text-gray-900 dark:text-white">{certification.averageScore.toFixed(1)}</p>
            </div>
            <div>
              <label className="label">Total Scores</label>
              <p className="text-gray-900 dark:text-white">{certification.totalScores}</p>
            </div>
          </div>

          {/* Workflow Steps */}
          <div>
            <label className="label">Certification Workflow</label>
            <div className="space-y-4">
              {certification.steps.map((step, index) => (
                <div key={step.id} className="flex items-center space-x-4 p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                  <div className="flex-shrink-0">
                    {getStepIcon(step.status)}
                  </div>
                  <div className="flex-1">
                    <div className="flex items-center justify-between">
                      <h4 className="font-medium text-gray-900 dark:text-white">{step.name}</h4>
                      <span className={`badge ${getStatusColor(step.status)}`}>
                        {step.status.replace('_', ' ')}
                      </span>
                    </div>
                    <p className="text-sm text-gray-600 dark:text-gray-400">
                      Role: {step.role.replace('_', ' ')}
                    </p>
                    {step.completedBy && (
                      <p className="text-sm text-gray-600 dark:text-gray-400">
                        Completed by: {step.completedBy} on {format(new Date(step.completedAt!), 'MMM dd, yyyy HH:mm')}
                      </p>
                    )}
                    {step.comments && (
                      <p className="text-sm text-gray-600 dark:text-gray-400 mt-1">
                        Comments: {step.comments}
                      </p>
                    )}
                  </div>
                  {index < certification.steps.length - 1 && (
                    <ArrowDownIcon className="h-5 w-5 text-gray-400" />
                  )}
                </div>
              ))}
            </div>
          </div>

          {/* Actions */}
          {canApprove && (
            <div className="flex justify-end space-x-3 pt-4">
              <button onClick={onClose} className="btn btn-outline">
                Close
              </button>
              <button onClick={onReject} className="btn btn-outline text-red-600 hover:text-red-700">
                Reject
              </button>
              <button onClick={onApprove} className="btn btn-primary">
                Approve
              </button>
            </div>
          )}
        </div>
      </div>
    </div>
  )
}

// Approval Modal Component
interface ApprovalModalProps {
  certification: Certification
  onClose: () => void
  onApprove: (comments: string) => void
  onReject: (reason: string) => void
  isLoading: boolean
}

const ApprovalModal: React.FC<ApprovalModalProps> = ({
  certification,
  onClose,
  onApprove,
  onReject,
  isLoading,
}) => {
  const [action, setAction] = useState<'approve' | 'reject' | null>(null)
  const [comments, setComments] = useState('')
  const [reason, setReason] = useState('')

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (action === 'approve') {
      onApprove(comments)
    } else if (action === 'reject') {
      onReject(reason)
    }
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-2xl">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold text-gray-900 dark:text-white">
            {action === 'approve' ? 'Approve Certification' : 
             action === 'reject' ? 'Reject Certification' : 
             'Certification Action'}
          </h2>
          <button
            onClick={onClose}
            className="text-gray-400 hover:text-gray-600 dark:hover:text-gray-300"
          >
            <XCircleIcon className="h-6 w-6" />
          </button>
        </div>

        {!action ? (
          <div className="space-y-4">
            <p className="text-gray-600 dark:text-gray-400">
              What action would you like to take for this certification?
            </p>
            <div className="flex space-x-4">
              <button
                onClick={() => setAction('approve')}
                className="btn btn-primary flex-1"
              >
                <CheckCircleIcon className="h-5 w-5 mr-2" />
                Approve
              </button>
              <button
                onClick={() => setAction('reject')}
                className="btn btn-outline text-red-600 hover:text-red-700 flex-1"
              >
                <XCircleIcon className="h-5 w-5 mr-2" />
                Reject
              </button>
            </div>
          </div>
        ) : (
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="label">
                {action === 'approve' ? 'Comments (Optional)' : 'Rejection Reason'}
              </label>
              <textarea
                value={action === 'approve' ? comments : reason}
                onChange={(e) => action === 'approve' ? setComments(e.target.value) : setReason(e.target.value)}
                className="input"
                rows={4}
                required={action === 'reject'}
                placeholder={action === 'approve' ? 'Add any comments about this certification...' : 'Explain why this certification is being rejected...'}
              />
            </div>
            <div className="flex justify-end space-x-3">
              <button
                type="button"
                onClick={() => setAction(null)}
                className="btn btn-outline"
                disabled={isLoading}
              >
                Back
              </button>
              <button
                type="submit"
                className={`btn ${action === 'approve' ? 'btn-primary' : 'btn-outline text-red-600 hover:text-red-700'}`}
                disabled={isLoading}
              >
                {isLoading ? 'Processing...' : action === 'approve' ? 'Approve' : 'Reject'}
              </button>
            </div>
          </form>
        )}
      </div>
    </div>
  )
}

export default CertificationWorkflow
