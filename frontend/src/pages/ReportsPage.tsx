import React, { useState } from 'react'
import { useQuery } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { api } from '../services/api'
import {
  DocumentArrowDownIcon,
  PrinterIcon,
  MagnifyingGlassIcon,
  CalendarIcon,
  ChartBarIcon,
  DocumentTextIcon,
  UserGroupIcon,
  TrophyIcon,
  ClipboardDocumentListIcon,
  EyeIcon,
  FunnelIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline'
import { format, subDays, subMonths } from 'date-fns'

interface Report {
  id: string
  name: string
  type: 'CONTEST_SUMMARY' | 'CONTESTANT_RESULTS' | 'JUDGE_SCORES' | 'CATEGORY_BREAKDOWN' | 'FINAL_RANKINGS' | 'CUSTOM'
  description: string
  parameters: ReportParameters
  isActive: boolean
  createdAt: string
  updatedAt: string
  createdBy: string
  lastGenerated?: string
  generationCount: number
}

interface ReportParameters {
  eventId?: string
  contestId?: string
  categoryId?: string
  contestantId?: string
  judgeId?: string
  dateFrom?: string
  dateTo?: string
  includeDetails?: boolean
  includeComments?: boolean
  format?: 'PDF' | 'EXCEL' | 'CSV'
}

interface ReportData {
  summary: {
    totalContestants: number
    totalJudges: number
    totalCategories: number
    averageScore: number
    highestScore: number
    lowestScore: number
  }
  rankings: Array<{
    rank: number
    contestantId: string
    contestantName: string
    totalScore: number
    averageScore: number
    categoryScores: Array<{
      categoryId: string
      categoryName: string
      score: number
    }>
  }>
  categories: Array<{
    id: string
    name: string
    maxScore: number
    averageScore: number
    contestantCount: number
    criteria: Array<{
      id: string
      name: string
      maxScore: number
      averageScore: number
    }>
  }>
  judges: Array<{
    id: string
    name: string
    categoriesAssigned: number
    scoresSubmitted: number
    averageScore: number
  }>
}

const ReportsPage: React.FC = () => {
  const { user } = useAuth()
  const [searchTerm, setSearchTerm] = useState('')
  const [typeFilter, setTypeFilter] = useState<string>('ALL')
  const [dateFilter, setDateFilter] = useState<string>('ALL')
  const [selectedReport, setSelectedReport] = useState<Report | null>(null)
  const [reportData, setReportData] = useState<ReportData | null>(null)
  const [isGenerating, setIsGenerating] = useState(false)
  const [showPreview, setShowPreview] = useState(false)

  const { data: reports, isLoading } = useQuery(
    'reports',
    () => api.get('/reports').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'TALLY_MASTER' || user?.role === 'AUDITOR',
    }
  )

  const { data: events } = useQuery(
    'events-for-reports',
    () => api.get('/events').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'TALLY_MASTER' || user?.role === 'AUDITOR',
    }
  )

  const { data: contests } = useQuery(
    'contests-for-reports',
    () => api.get('/contests').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'TALLY_MASTER' || user?.role === 'AUDITOR',
    }
  )

  const filteredReports = reports?.filter((report: Report) => {
    const matchesSearch = report.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         report.description.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesType = typeFilter === 'ALL' || report.type === typeFilter
    const matchesDate = dateFilter === 'ALL' || 
                       (dateFilter === 'RECENT' && report.lastGenerated && 
                        new Date(report.lastGenerated) > subDays(new Date(), 7)) ||
                       (dateFilter === 'THIS_MONTH' && report.lastGenerated && 
                        new Date(report.lastGenerated) > subMonths(new Date(), 1))
    return matchesSearch && matchesType && matchesDate
  }) || []

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'CONTEST_SUMMARY': return 'badge-default'
      case 'CONTESTANT_RESULTS': return 'badge-secondary'
      case 'JUDGE_SCORES': return 'badge-success'
      case 'CATEGORY_BREAKDOWN': return 'badge-warning'
      case 'FINAL_RANKINGS': return 'badge-destructive'
      case 'CUSTOM': return 'badge-outline'
      default: return 'badge-secondary'
    }
  }

  const getTypeText = (type: string) => {
    switch (type) {
      case 'CONTEST_SUMMARY': return 'Contest Summary'
      case 'CONTESTANT_RESULTS': return 'Contestant Results'
      case 'JUDGE_SCORES': return 'Judge Scores'
      case 'CATEGORY_BREAKDOWN': return 'Category Breakdown'
      case 'FINAL_RANKINGS': return 'Final Rankings'
      case 'CUSTOM': return 'Custom Report'
      default: return type
    }
  }

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'CONTEST_SUMMARY': return '📊'
      case 'CONTESTANT_RESULTS': return '👤'
      case 'JUDGE_SCORES': return '⚖️'
      case 'CATEGORY_BREAKDOWN': return '📋'
      case 'FINAL_RANKINGS': return '🏆'
      case 'CUSTOM': return '📄'
      default: return '📄'
    }
  }

  const generateReport = async (report: Report) => {
    setIsGenerating(true)
    try {
      const response = await api.post(`/reports/${report.id}/generate`, {
        parameters: report.parameters,
      })
      setReportData(response.data)
      setShowPreview(true)
    } catch (error) {
      console.error('Error generating report:', error)
    } finally {
      setIsGenerating(false)
    }
  }

  const downloadReport = async (report: Report, format: 'PDF' | 'EXCEL' | 'CSV') => {
    try {
      const response = await api.post(`/reports/${report.id}/download`, {
        parameters: report.parameters,
        format,
      }, {
        responseType: 'blob',
      })
      
      const url = window.URL.createObjectURL(new Blob([response.data]))
      const link = document.createElement('a')
      link.href = url
      link.setAttribute('download', `${report.name}.${format.toLowerCase()}`)
      document.body.appendChild(link)
      link.click()
      link.remove()
      window.URL.revokeObjectURL(url)
    } catch (error) {
      console.error('Error downloading report:', error)
    }
  }

  const printReport = async (report: Report) => {
    try {
      const response = await api.post(`/reports/${report.id}/print`, {
        parameters: report.parameters,
      })
      
      const printWindow = window.open('', '_blank')
      if (printWindow) {
        printWindow.document.write(response.data)
        printWindow.document.close()
        printWindow.print()
      }
    } catch (error) {
      console.error('Error printing report:', error)
    }
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Reports & Analytics</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Generate and view contest reports and analytics
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setSelectedReport(null)}
            className="btn btn-primary"
          >
            <ChartBarIcon className="h-5 w-5 mr-2" />
            Quick Report
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="card">
        <div className="card-content">
          <div className="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div className="md:col-span-2">
              <div className="relative">
                <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search reports..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="input pl-10"
                />
              </div>
            </div>
            <div>
              <select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="input"
              >
                <option value="ALL">All Types</option>
                <option value="CONTEST_SUMMARY">Contest Summary</option>
                <option value="CONTESTANT_RESULTS">Contestant Results</option>
                <option value="JUDGE_SCORES">Judge Scores</option>
                <option value="CATEGORY_BREAKDOWN">Category Breakdown</option>
                <option value="FINAL_RANKINGS">Final Rankings</option>
                <option value="CUSTOM">Custom Report</option>
              </select>
            </div>
            <div>
              <select
                value={dateFilter}
                onChange={(e) => setDateFilter(e.target.value)}
                className="input"
              >
                <option value="ALL">All Time</option>
                <option value="RECENT">Last 7 Days</option>
                <option value="THIS_MONTH">This Month</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Reports Grid */}
      {filteredReports.length === 0 ? (
        <div className="card">
          <div className="card-content text-center py-12">
            <ChartBarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              No reports found
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              {searchTerm || typeFilter !== 'ALL' || dateFilter !== 'ALL'
                ? 'Try adjusting your search criteria'
                : 'No reports have been created yet'}
            </p>
          </div>
        </div>
      ) : (
        <div className="grid-responsive">
          {filteredReports.map((report: Report) => (
            <div key={report.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{report.name}</h3>
                    <p className="card-description line-clamp-2">{report.description}</p>
                  </div>
                  <div className="flex items-center space-x-2 ml-2">
                    <span className="text-2xl">{getTypeIcon(report.type)}</span>
                    <span className={`badge ${getTypeColor(report.type)}`}>
                      {getTypeText(report.type)}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <ClipboardDocumentListIcon className="h-4 w-4 mr-2" />
                  <span>Generated {report.generationCount} times</span>
                </div>
                {report.lastGenerated && (
                  <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <CalendarIcon className="h-4 w-4 mr-2" />
                    <span>Last: {format(new Date(report.lastGenerated), 'MMM dd, yyyy')}</span>
                  </div>
                )}
                <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                  <span>Created: {format(new Date(report.createdAt), 'MMM dd, yyyy')}</span>
                  <span className={`status-indicator ${report.isActive ? 'status-online' : 'status-offline'}`}>
                    {report.isActive ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => generateReport(report)}
                      className="btn btn-outline btn-sm"
                      disabled={isGenerating}
                    >
                      <EyeIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => downloadReport(report, 'PDF')}
                      className="btn btn-outline btn-sm"
                    >
                      <DocumentArrowDownIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => printReport(report)}
                      className="btn btn-outline btn-sm"
                    >
                      <PrinterIcon className="h-4 w-4" />
                    </button>
                  </div>
                  <button
                    onClick={() => setSelectedReport(report)}
                    className="btn btn-primary btn-sm"
                  >
                    <FunnelIcon className="h-4 w-4 mr-1" />
                    Configure
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Quick Report Modal */}
      {selectedReport && (
        <QuickReportModal
          report={selectedReport}
          events={events || []}
          contests={contests || []}
          onClose={() => setSelectedReport(null)}
          onGenerate={generateReport}
          isLoading={isGenerating}
        />
      )}

      {/* Report Preview Modal */}
      {showPreview && reportData && (
        <ReportPreviewModal
          reportData={reportData}
          onClose={() => setShowPreview(false)}
          onDownload={(format) => downloadReport(selectedReport!, format)}
          onPrint={() => printReport(selectedReport!)}
        />
      )}
    </div>
  )
}

// Quick Report Modal Component
interface QuickReportModalProps {
  report: Report
  events: any[]
  contests: any[]
  onClose: () => void
  onGenerate: (report: Report) => void
  isLoading: boolean
}

const QuickReportModal: React.FC<QuickReportModalProps> = ({ 
  report, 
  events, 
  contests, 
  onClose, 
  onGenerate, 
  isLoading 
}) => {
  const [parameters, setParameters] = useState<ReportParameters>(report.parameters)

  const handleGenerate = () => {
    const updatedReport = { ...report, parameters }
    onGenerate(updatedReport)
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-2xl">
        <h2 className="text-xl font-semibold mb-4">Configure Report</h2>
        <div className="space-y-4">
          <div>
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">{report.name}</h3>
            <p className="text-gray-600 dark:text-gray-400">{report.description}</p>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Event</label>
              <select
                value={parameters.eventId || ''}
                onChange={(e) => setParameters({ ...parameters, eventId: e.target.value })}
                className="input"
              >
                <option value="">All Events</option>
                {events.map((event) => (
                  <option key={event.id} value={event.id}>{event.name}</option>
                ))}
              </select>
            </div>
            <div>
              <label className="label">Contest</label>
              <select
                value={parameters.contestId || ''}
                onChange={(e) => setParameters({ ...parameters, contestId: e.target.value })}
                className="input"
              >
                <option value="">All Contests</option>
                {contests.map((contest) => (
                  <option key={contest.id} value={contest.id}>{contest.name}</option>
                ))}
              </select>
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Date From</label>
              <input
                type="date"
                value={parameters.dateFrom || ''}
                onChange={(e) => setParameters({ ...parameters, dateFrom: e.target.value })}
                className="input"
              />
            </div>
            <div>
              <label className="label">Date To</label>
              <input
                type="date"
                value={parameters.dateTo || ''}
                onChange={(e) => setParameters({ ...parameters, dateTo: e.target.value })}
                className="input"
              />
            </div>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Format</label>
              <select
                value={parameters.format || 'PDF'}
                onChange={(e) => setParameters({ ...parameters, format: e.target.value as any })}
                className="input"
              >
                <option value="PDF">PDF</option>
                <option value="EXCEL">Excel</option>
                <option value="CSV">CSV</option>
              </select>
            </div>
            <div className="flex items-center space-x-4">
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  checked={parameters.includeDetails || false}
                  onChange={(e) => setParameters({ ...parameters, includeDetails: e.target.checked })}
                  className="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <span className="text-sm text-gray-700 dark:text-gray-300">Include Details</span>
              </label>
              <label className="flex items-center space-x-2">
                <input
                  type="checkbox"
                  checked={parameters.includeComments || false}
                  onChange={(e) => setParameters({ ...parameters, includeComments: e.target.checked })}
                  className="rounded border-gray-300 text-primary focus:ring-primary"
                />
                <span className="text-sm text-gray-700 dark:text-gray-300">Include Comments</span>
              </label>
            </div>
          </div>
        </div>

        <div className="flex justify-end space-x-3 pt-4">
          <button
            onClick={onClose}
            className="btn btn-outline"
            disabled={isLoading}
          >
            Cancel
          </button>
          <button
            onClick={handleGenerate}
            className="btn btn-primary"
            disabled={isLoading}
          >
            {isLoading ? 'Generating...' : 'Generate Report'}
          </button>
        </div>
      </div>
    </div>
  )
}

// Report Preview Modal Component
interface ReportPreviewModalProps {
  reportData: ReportData
  onClose: () => void
  onDownload: (format: 'PDF' | 'EXCEL' | 'CSV') => void
  onPrint: () => void
}

const ReportPreviewModal: React.FC<ReportPreviewModalProps> = ({ 
  reportData, 
  onClose, 
  onDownload, 
  onPrint 
}) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-6xl">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold">Report Preview</h2>
          <div className="flex space-x-2">
            <button
              onClick={() => onDownload('PDF')}
              className="btn btn-outline btn-sm"
            >
              <DocumentArrowDownIcon className="h-4 w-4 mr-1" />
              PDF
            </button>
            <button
              onClick={() => onDownload('EXCEL')}
              className="btn btn-outline btn-sm"
            >
              <DocumentArrowDownIcon className="h-4 w-4 mr-1" />
              Excel
            </button>
            <button
              onClick={() => onDownload('CSV')}
              className="btn btn-outline btn-sm"
            >
              <DocumentArrowDownIcon className="h-4 w-4 mr-1" />
              CSV
            </button>
            <button
              onClick={onPrint}
              className="btn btn-outline btn-sm"
            >
              <PrinterIcon className="h-4 w-4 mr-1" />
              Print
            </button>
            <button
              onClick={onClose}
              className="btn btn-ghost btn-sm"
            >
              <XMarkIcon className="h-5 w-5" />
            </button>
          </div>
        </div>

        <div className="space-y-6">
          {/* Summary Stats */}
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <UserGroupIcon className="h-8 w-8 text-blue-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Total Contestants</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {reportData.summary.totalContestants}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <TrophyIcon className="h-8 w-8 text-yellow-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Average Score</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {reportData.summary.averageScore.toFixed(1)}
                    </p>
                  </div>
                </div>
              </div>
            </div>
            <div className="card">
              <div className="card-content">
                <div className="flex items-center">
                  <ChartBarIcon className="h-8 w-8 text-green-500" />
                  <div className="ml-3">
                    <p className="text-sm font-medium text-gray-600 dark:text-gray-400">Categories</p>
                    <p className="text-2xl font-semibold text-gray-900 dark:text-white">
                      {reportData.summary.totalCategories}
                    </p>
                  </div>
                </div>
              </div>
            </div>
          </div>

          {/* Rankings Table */}
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Final Rankings</h3>
            </div>
            <div className="card-content">
              <div className="overflow-x-auto">
                <table className="table">
                  <thead>
                    <tr>
                      <th>Rank</th>
                      <th>Contestant</th>
                      <th>Total Score</th>
                      <th>Average Score</th>
                      <th>Categories</th>
                    </tr>
                  </thead>
                  <tbody>
                    {reportData.rankings.map((ranking) => (
                      <tr key={ranking.contestantId}>
                        <td>
                          <span className="badge badge-primary">#{ranking.rank}</span>
                        </td>
                        <td className="font-medium">{ranking.contestantName}</td>
                        <td>{ranking.totalScore.toFixed(1)}</td>
                        <td>{ranking.averageScore.toFixed(1)}</td>
                        <td>{ranking.categoryScores.length}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          </div>

          {/* Categories Breakdown */}
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Category Breakdown</h3>
            </div>
            <div className="card-content">
              <div className="space-y-4">
                {reportData.categories.map((category) => (
                  <div key={category.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                    <div className="flex items-center justify-between mb-3">
                      <h4 className="font-medium text-gray-900 dark:text-white">{category.name}</h4>
                      <div className="text-sm text-gray-600 dark:text-gray-400">
                        Avg: {category.averageScore.toFixed(1)} / {category.maxScore}
                      </div>
                    </div>
                    <div className="grid grid-cols-1 md:grid-cols-2 gap-2">
                      {category.criteria.map((criteria) => (
                        <div key={criteria.id} className="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                          <span className="text-sm text-gray-900 dark:text-white">{criteria.name}</span>
                          <span className="text-sm text-gray-600 dark:text-gray-400">
                            {criteria.averageScore.toFixed(1)} / {criteria.maxScore}
                          </span>
                        </div>
                      ))}
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ReportsPage
