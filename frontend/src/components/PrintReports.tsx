import React, { useState } from 'react'
import { useQuery } from 'react-query'
import { resultsAPI, eventsAPI, contestsAPI, usersAPI, api } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  PrinterIcon,
  DocumentTextIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  CalendarIcon,
  TrophyIcon,
  UserGroupIcon,
  StarIcon,
  ChartBarIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  CheckCircleIcon,
  ClockIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface ReportTemplate {
  id: string
  name: string
  type: 'CONTESTANT' | 'JUDGE' | 'CATEGORY' | 'CONTEST' | 'EVENT' | 'CUSTOM'
  description: string
  fields: string[]
  isActive: boolean
  createdAt: string
}

interface ReportData {
  id: string
  name: string
  type: string
  data: any
  generatedAt: string
  generatedBy: string
  parameters: Record<string, any>
}

const PrintReports: React.FC = () => {
  const { user } = useAuth()
  const [activeTab, setActiveTab] = useState<'generate' | 'templates' | 'history'>('generate')
  const [selectedEvent, setSelectedEvent] = useState('')
  const [selectedContest, setSelectedContest] = useState('')
  const [selectedCategory, setSelectedCategory] = useState('')
  const [selectedTemplate, setSelectedTemplate] = useState('')
  const [searchTerm, setSearchTerm] = useState('')
  const [reportType, setReportType] = useState<'SUMMARY' | 'DETAILED' | 'CUSTOM'>('SUMMARY')

  // Fetch data for reports
  const { data: events } = useQuery(
    'events-for-reports',
    () => eventsAPI.getAll().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'JUDGE',
    }
  )

  const { data: contests } = useQuery(
    'contests-for-reports',
    () => contestsAPI.getAll().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'JUDGE',
    }
  )

  const { data: categories } = useQuery(
    'categories-for-reports',
    () => api.get('/categories').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'JUDGE',
    }
  )

  const { data: reportTemplates } = useQuery(
    'report-templates',
    () => api.get('/reports/templates').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: reportHistory } = useQuery(
    'report-history',
    () => api.get('/reports/history').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const generateReport = async (type: string, parameters: any) => {
    try {
      const response = await api.post('/reports/generate', {
        type,
        parameters,
        format: 'PDF',
      })
      
      // Create download link
      const blob = new Blob([response.data], { type: 'application/pdf' })
      const url = window.URL.createObjectURL(blob)
      const link = document.createElement('a')
      link.href = url
      link.download = `${type}_report_${format(new Date(), 'yyyy-MM-dd_HH-mm')}.pdf`
      document.body.appendChild(link)
      link.click()
      document.body.removeChild(link)
      window.URL.revokeObjectURL(url)
    } catch (error) {
      console.error('Error generating report:', error)
    }
  }

  const printReport = async (type: string, parameters: any) => {
    try {
      const response = await api.post('/reports/generate', {
        type,
        parameters,
        format: 'HTML',
      })
      
      // Open print dialog
      const printWindow = window.open('', '_blank')
      if (printWindow) {
        printWindow.document.write(response.data)
        printWindow.document.close()
        printWindow.focus()
        printWindow.print()
        printWindow.close()
      }
    } catch (error) {
      console.error('Error printing report:', error)
    }
  }

  const tabs = [
    { id: 'generate', name: 'Generate Report', icon: PrinterIcon },
    { id: 'templates', name: 'Templates', icon: DocumentTextIcon },
    { id: 'history', name: 'Report History', icon: ClockIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Print Reports</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Generate and print comprehensive reports for events, contests, and results
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
                </button>
              ))}
            </nav>
          </div>
        </div>
      </div>

      {/* Tab Content */}
      <div className="card">
        <div className="card-content">
          {activeTab === 'generate' && (
            <GenerateReportTab
              events={events || []}
              contests={contests || []}
              categories={categories || []}
              templates={reportTemplates || []}
              onGenerate={generateReport}
              onPrint={printReport}
              userRole={user?.role}
            />
          )}

          {activeTab === 'templates' && (
            <TemplatesTab
              templates={reportTemplates || []}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
            />
          )}

          {activeTab === 'history' && (
            <HistoryTab
              history={reportHistory || []}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
            />
          )}
        </div>
      </div>
    </div>
  )
}

// Generate Report Tab Component
interface GenerateReportTabProps {
  events: any[]
  contests: any[]
  categories: any[]
  templates: ReportTemplate[]
  onGenerate: (type: string, parameters: any) => void
  onPrint: (type: string, parameters: any) => void
  userRole?: string
}

const GenerateReportTab: React.FC<GenerateReportTabProps> = ({
  events,
  contests,
  categories,
  templates,
  onGenerate,
  onPrint,
  userRole,
}) => {
  const [selectedEvent, setSelectedEvent] = useState('')
  const [selectedContest, setSelectedContest] = useState('')
  const [selectedCategory, setSelectedCategory] = useState('')
  const [selectedTemplate, setSelectedTemplate] = useState('')
  const [reportType, setReportType] = useState<'SUMMARY' | 'DETAILED' | 'CUSTOM'>('SUMMARY')
  const [includeImages, setIncludeImages] = useState(true)
  const [includeCharts, setIncludeCharts] = useState(true)

  const handleGenerate = () => {
    const parameters = {
      eventId: selectedEvent,
      contestId: selectedContest,
      categoryId: selectedCategory,
      templateId: selectedTemplate,
      type: reportType,
      includeImages,
      includeCharts,
    }
    onGenerate(reportType, parameters)
  }

  const handlePrint = () => {
    const parameters = {
      eventId: selectedEvent,
      contestId: selectedContest,
      categoryId: selectedCategory,
      templateId: selectedTemplate,
      type: reportType,
      includeImages,
      includeCharts,
    }
    onPrint(reportType, parameters)
  }

  const reportTypes = [
    {
      id: 'SUMMARY',
      name: 'Summary Report',
      description: 'Overview of results and standings',
      icon: ChartBarIcon,
      color: 'blue',
    },
    {
      id: 'DETAILED',
      name: 'Detailed Report',
      description: 'Comprehensive results with all scores',
      icon: DocumentTextIcon,
      color: 'green',
    },
    {
      id: 'CUSTOM',
      name: 'Custom Report',
      description: 'Use a custom template',
      icon: StarIcon,
      color: 'purple',
    },
  ]

  return (
    <div className="space-y-6">
      <h3 className="text-lg font-medium text-gray-900 dark:text-white">Generate Report</h3>
      
      {/* Report Type Selection */}
      <div>
        <label className="label">Report Type</label>
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          {reportTypes.map((type) => (
            <div
              key={type.id}
              onClick={() => setReportType(type.id as any)}
              className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                reportType === type.id
                  ? 'border-primary bg-primary/5'
                  : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
              }`}
            >
              <div className="flex items-center space-x-3">
                <div className={`w-8 h-8 bg-${type.color}-500 rounded-md flex items-center justify-center`}>
                  <type.icon className="h-5 w-5 text-white" />
                </div>
                <div>
                  <h4 className="font-medium text-gray-900 dark:text-white">{type.name}</h4>
                  <p className="text-sm text-gray-600 dark:text-gray-400">{type.description}</p>
                </div>
              </div>
            </div>
          ))}
        </div>
      </div>

      {/* Filters */}
      <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div>
          <label className="label">Event</label>
          <select
            value={selectedEvent}
            onChange={(e) => setSelectedEvent(e.target.value)}
            className="input"
          >
            <option value="">All Events</option>
            {events.map((event) => (
              <option key={event.id} value={event.id}>
                {event.name}
              </option>
            ))}
          </select>
        </div>
        <div>
          <label className="label">Contest</label>
          <select
            value={selectedContest}
            onChange={(e) => setSelectedContest(e.target.value)}
            className="input"
            disabled={!selectedEvent}
          >
            <option value="">All Contests</option>
            {contests
              .filter(contest => !selectedEvent || contest.eventId === selectedEvent)
              .map((contest) => (
                <option key={contest.id} value={contest.id}>
                  {contest.name}
                </option>
              ))}
          </select>
        </div>
        <div>
          <label className="label">Category</label>
          <select
            value={selectedCategory}
            onChange={(e) => setSelectedCategory(e.target.value)}
            className="input"
            disabled={!selectedContest}
          >
            <option value="">All Categories</option>
            {categories
              .filter(category => !selectedContest || category.contestId === selectedContest)
              .map((category) => (
                <option key={category.id} value={category.id}>
                  {category.name}
                </option>
              ))}
          </select>
        </div>
      </div>

      {/* Template Selection (for Custom reports) */}
      {reportType === 'CUSTOM' && (
        <div>
          <label className="label">Template</label>
          <select
            value={selectedTemplate}
            onChange={(e) => setSelectedTemplate(e.target.value)}
            className="input"
            required
          >
            <option value="">Select a template</option>
            {templates.map((template) => (
              <option key={template.id} value={template.id}>
                {template.name} - {template.description}
              </option>
            ))}
          </select>
        </div>
      )}

      {/* Options */}
      <div className="space-y-4">
        <h4 className="font-medium text-gray-900 dark:text-white">Report Options</h4>
        <div className="space-y-3">
          <div className="flex items-center space-x-2">
            <input
              type="checkbox"
              id="includeImages"
              checked={includeImages}
              onChange={(e) => setIncludeImages(e.target.checked)}
              className="rounded border-gray-300 text-primary focus:ring-primary"
            />
            <label htmlFor="includeImages" className="label">
              Include contestant images
            </label>
          </div>
          <div className="flex items-center space-x-2">
            <input
              type="checkbox"
              id="includeCharts"
              checked={includeCharts}
              onChange={(e) => setIncludeCharts(e.target.checked)}
              className="rounded border-gray-300 text-primary focus:ring-primary"
            />
            <label htmlFor="includeCharts" className="label">
              Include charts and graphs
            </label>
          </div>
        </div>
      </div>

      {/* Actions */}
      <div className="flex justify-end space-x-3">
        <button
          onClick={handlePrint}
          className="btn btn-outline"
          disabled={!selectedEvent && !selectedContest && !selectedCategory}
        >
          <PrinterIcon className="h-5 w-5 mr-2" />
          Print Report
        </button>
        <button
          onClick={handleGenerate}
          className="btn btn-primary"
          disabled={!selectedEvent && !selectedContest && !selectedCategory}
        >
          <ArrowDownTrayIcon className="h-5 w-5 mr-2" />
          Download PDF
        </button>
      </div>

      {/* Quick Actions */}
      <div className="border-t pt-6">
        <h4 className="font-medium text-gray-900 dark:text-white mb-4">Quick Reports</h4>
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
          <button
            onClick={() => {
              setReportType('SUMMARY')
              setSelectedEvent('')
              setSelectedContest('')
              setSelectedCategory('')
              onGenerate('EVENT_SUMMARY', { includeImages, includeCharts })
            }}
            className="btn btn-outline btn-sm"
          >
            <CalendarIcon className="h-4 w-4 mr-2" />
            Event Summary
          </button>
          <button
            onClick={() => {
              setReportType('DETAILED')
              setSelectedEvent('')
              setSelectedContest('')
              setSelectedCategory('')
              onGenerate('CONTEST_RESULTS', { includeImages, includeCharts })
            }}
            className="btn btn-outline btn-sm"
          >
            <TrophyIcon className="h-4 w-4 mr-2" />
            Contest Results
          </button>
          <button
            onClick={() => {
              setReportType('SUMMARY')
              setSelectedEvent('')
              setSelectedContest('')
              setSelectedCategory('')
              onGenerate('JUDGE_SCORES', { includeImages, includeCharts })
            }}
            className="btn btn-outline btn-sm"
          >
            <UserGroupIcon className="h-4 w-4 mr-2" />
            Judge Scores
          </button>
          <button
            onClick={() => {
              setReportType('DETAILED')
              setSelectedEvent('')
              setSelectedContest('')
              setSelectedCategory('')
              onGenerate('CONTESTANT_PERFORMANCE', { includeImages, includeCharts })
            }}
            className="btn btn-outline btn-sm"
          >
            <StarIcon className="h-4 w-4 mr-2" />
            Contestant Performance
          </button>
        </div>
      </div>
    </div>
  )
}

// Templates Tab Component
interface TemplatesTabProps {
  templates: ReportTemplate[]
  searchTerm: string
  onSearchChange: (term: string) => void
}

const TemplatesTab: React.FC<TemplatesTabProps> = ({
  templates,
  searchTerm,
  onSearchChange,
}) => {
  const filteredTemplates = templates.filter(template =>
    template.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    template.description.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'CONTESTANT': return '👤'
      case 'JUDGE': return '⚖️'
      case 'CATEGORY': return '📂'
      case 'CONTEST': return '🏆'
      case 'EVENT': return '📅'
      case 'CUSTOM': return '📝'
      default: return '📄'
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Report Templates</h3>
        <div className="relative">
          <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search templates..."
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
            className="input pl-10"
          />
        </div>
      </div>

      {filteredTemplates.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No templates found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm ? 'Try adjusting your search criteria' : 'No report templates available'}
          </p>
        </div>
      ) : (
        <div className="grid-responsive">
          {filteredTemplates.map((template) => (
            <div key={template.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{template.name}</h3>
                    <p className="card-description line-clamp-2">{template.description}</p>
                  </div>
                  <div className="flex items-center space-x-2 ml-2">
                    <span className="text-2xl">{getTypeIcon(template.type)}</span>
                    <span className={`badge ${template.isActive ? 'badge-success' : 'badge-secondary'}`}>
                      {template.isActive ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <DocumentTextIcon className="h-4 w-4 mr-2" />
                  <span>{template.type}</span>
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  <div className="font-medium mb-1">Fields:</div>
                  <div className="flex flex-wrap gap-1">
                    {template.fields.map((field) => (
                      <span key={field} className="badge badge-outline badge-sm">
                        {field}
                      </span>
                    ))}
                  </div>
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  Created: {format(new Date(template.createdAt), 'MMM dd, yyyy')}
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <button className="btn btn-outline btn-sm">
                    <EyeIcon className="h-4 w-4 mr-1" />
                    Preview
                  </button>
                  <button className="btn btn-primary btn-sm">
                    <PrinterIcon className="h-4 w-4 mr-1" />
                    Use Template
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

// History Tab Component
interface HistoryTabProps {
  history: ReportData[]
  searchTerm: string
  onSearchChange: (term: string) => void
}

const HistoryTab: React.FC<HistoryTabProps> = ({
  history,
  searchTerm,
  onSearchChange,
}) => {
  const filteredHistory = history.filter(report =>
    report.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    report.type.toLowerCase().includes(searchTerm.toLowerCase())
  )

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'COMPLETED': return <CheckCircleIcon className="h-4 w-4 text-green-500" />
      case 'PROCESSING': return <ClockIcon className="h-4 w-4 text-yellow-500" />
      case 'FAILED': return <ExclamationTriangleIcon className="h-4 w-4 text-red-500" />
      default: return <ClockIcon className="h-4 w-4 text-gray-500" />
    }
  }

  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Report History</h3>
        <div className="relative">
          <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search history..."
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
            className="input pl-10"
          />
        </div>
      </div>

      {filteredHistory.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <ClockIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No reports found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm ? 'Try adjusting your search criteria' : 'Generated reports will appear here'}
          </p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="table">
            <thead>
              <tr>
                <th>Report Name</th>
                <th>Type</th>
                <th>Generated By</th>
                <th>Generated At</th>
                <th>Status</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {filteredHistory.map((report) => (
                <tr key={report.id}>
                  <td className="font-medium text-gray-900 dark:text-white">
                    {report.name}
                  </td>
                  <td className="text-gray-600 dark:text-gray-400">
                    {report.type}
                  </td>
                  <td className="text-gray-600 dark:text-gray-400">
                    {report.generatedBy}
                  </td>
                  <td className="text-gray-600 dark:text-gray-400">
                    {format(new Date(report.generatedAt), 'MMM dd, yyyy HH:mm')}
                  </td>
                  <td>
                    <div className="flex items-center space-x-2">
                      {getStatusIcon(report.data?.status || 'COMPLETED')}
                      <span className="text-sm text-gray-600 dark:text-gray-400">
                        {report.data?.status || 'Completed'}
                      </span>
                    </div>
                  </td>
                  <td>
                    <div className="flex space-x-2">
                      <button className="btn btn-outline btn-sm">
                        <EyeIcon className="h-4 w-4" />
                      </button>
                      <button className="btn btn-outline btn-sm">
                        <ArrowDownTrayIcon className="h-4 w-4" />
                      </button>
                    </div>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      )}
    </div>
  )
}

export default PrintReports
