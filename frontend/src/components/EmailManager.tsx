import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { emailAPI, usersAPI, eventsAPI, contestsAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  MailIcon,
  PaperAirplaneIcon,
  UserGroupIcon,
  DocumentTextIcon,
  EyeIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  PlusIcon,
  TrashIcon,
  PencilIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  CalendarIcon,
  TrophyIcon,
  XMarkIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface EmailTemplate {
  id: string
  name: string
  subject: string
  content: string
  type: 'WELCOME' | 'RESULTS' | 'REMINDER' | 'ANNOUNCEMENT' | 'CUSTOM'
  isActive: boolean
  createdAt: string
  updatedAt: string
  createdBy: string
}

interface EmailCampaign {
  id: string
  name: string
  subject: string
  content: string
  recipients: string[]
  status: 'DRAFT' | 'SCHEDULED' | 'SENDING' | 'SENT' | 'FAILED'
  scheduledAt?: string
  sentAt?: string
  createdAt: string
  createdBy: string
  stats: {
    total: number
    sent: number
    failed: number
    opened: number
    clicked: number
  }
}

interface EmailLog {
  id: string
  to: string
  subject: string
  status: 'SENT' | 'FAILED' | 'BOUNCED'
  sentAt: string
  errorMessage?: string
  campaignId?: string
}

const EmailManager: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'compose' | 'templates' | 'campaigns' | 'logs'>('compose')
  const [showTemplateModal, setShowTemplateModal] = useState(false)
  const [showCampaignModal, setShowCampaignModal] = useState(false)
  const [editingTemplate, setEditingTemplate] = useState<EmailTemplate | null>(null)
  const [editingCampaign, setEditingCampaign] = useState<EmailCampaign | null>(null)
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('ALL')

  // Email templates
  const { data: templates, isLoading: templatesLoading } = useQuery(
    'email-templates',
    () => api.get('/email/templates').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  // Email campaigns
  const { data: campaigns, isLoading: campaignsLoading } = useQuery(
    'email-campaigns',
    () => api.get('/email/campaigns').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  // Email logs
  const { data: emailLogs, isLoading: logsLoading } = useQuery(
    'email-logs',
    () => api.get('/email/logs').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  // Users for recipient selection
  const { data: users } = useQuery(
    'users-for-email',
    () => usersAPI.getAll().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  // Events for context
  const { data: events } = useQuery(
    'events-for-email',
    () => eventsAPI.getAll().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  // Contests for context
  const { data: contests } = useQuery(
    'contests-for-email',
    () => contestsAPI.getAll().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const sendEmailMutation = useMutation(
    (emailData: any) => emailAPI.sendEmail(emailData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('email-logs')
      },
    }
  )

  const createTemplateMutation = useMutation(
    (templateData: Partial<EmailTemplate>) => api.post('/email/templates', templateData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('email-templates')
        setShowTemplateModal(false)
        setEditingTemplate(null)
      },
    }
  )

  const updateTemplateMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<EmailTemplate> }) =>
      api.put(`/email/templates/${id}`, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('email-templates')
        setShowTemplateModal(false)
        setEditingTemplate(null)
      },
    }
  )

  const deleteTemplateMutation = useMutation(
    (id: string) => api.delete(`/email/templates/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('email-templates')
      },
    }
  )

  const createCampaignMutation = useMutation(
    (campaignData: Partial<EmailCampaign>) => api.post('/email/campaigns', campaignData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('email-campaigns')
        setShowCampaignModal(false)
        setEditingCampaign(null)
      },
    }
  )

  const updateCampaignMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<EmailCampaign> }) =>
      api.put(`/email/campaigns/${id}`, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('email-campaigns')
        setShowCampaignModal(false)
        setEditingCampaign(null)
      },
    }
  )

  const deleteCampaignMutation = useMutation(
    (id: string) => api.delete(`/email/campaigns/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('email-campaigns')
      },
    }
  )

  const filteredTemplates = templates?.filter((template: EmailTemplate) =>
    template.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    template.subject.toLowerCase().includes(searchTerm.toLowerCase())
  ) || []

  const filteredCampaigns = campaigns?.filter((campaign: EmailCampaign) => {
    const matchesSearch = campaign.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         campaign.subject.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesStatus = statusFilter === 'ALL' || campaign.status === statusFilter
    return matchesSearch && matchesStatus
  }) || []

  const filteredLogs = emailLogs?.filter((log: EmailLog) =>
    log.to.toLowerCase().includes(searchTerm.toLowerCase()) ||
    log.subject.toLowerCase().includes(searchTerm.toLowerCase())
  ) || []

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'DRAFT': return 'badge-warning'
      case 'SCHEDULED': return 'badge-info'
      case 'SENDING': return 'badge-primary'
      case 'SENT': return 'badge-success'
      case 'FAILED': return 'badge-destructive'
      case 'SENT': return 'badge-success'
      case 'BOUNCED': return 'badge-destructive'
      default: return 'badge-secondary'
    }
  }

  const getStatusText = (status: string) => {
    switch (status) {
      case 'DRAFT': return 'Draft'
      case 'SCHEDULED': return 'Scheduled'
      case 'SENDING': return 'Sending'
      case 'SENT': return 'Sent'
      case 'FAILED': return 'Failed'
      case 'BOUNCED': return 'Bounced'
      default: return status
    }
  }

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'WELCOME': return '👋'
      case 'RESULTS': return '🏆'
      case 'REMINDER': return '⏰'
      case 'ANNOUNCEMENT': return '📢'
      case 'CUSTOM': return '📝'
      default: return '📧'
    }
  }

  const tabs = [
    { id: 'compose', name: 'Compose', icon: MailIcon },
    { id: 'templates', name: 'Templates', icon: DocumentTextIcon },
    { id: 'campaigns', name: 'Campaigns', icon: PaperAirplaneIcon },
    { id: 'logs', name: 'Email Logs', icon: ClockIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Email Manager</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Send emails, manage templates, and track campaigns
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowTemplateModal(true)}
            className="btn btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            New Template
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
          {activeTab === 'compose' && (
            <ComposeEmailTab
              users={users || []}
              events={events || []}
              contests={contests || []}
              templates={templates || []}
              onSend={(data) => sendEmailMutation.mutate(data)}
              isLoading={sendEmailMutation.isLoading}
            />
          )}

          {activeTab === 'templates' && (
            <TemplatesTab
              templates={filteredTemplates}
              isLoading={templatesLoading}
              onEdit={(template) => {
                setEditingTemplate(template)
                setShowTemplateModal(true)
              }}
              onDelete={(id) => deleteTemplateMutation.mutate(id)}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
            />
          )}

          {activeTab === 'campaigns' && (
            <CampaignsTab
              campaigns={filteredCampaigns}
              isLoading={campaignsLoading}
              onEdit={(campaign) => {
                setEditingCampaign(campaign)
                setShowCampaignModal(true)
              }}
              onDelete={(id) => deleteCampaignMutation.mutate(id)}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
              statusFilter={statusFilter}
              onStatusFilterChange={setStatusFilter}
            />
          )}

          {activeTab === 'logs' && (
            <LogsTab
              logs={filteredLogs}
              isLoading={logsLoading}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
            />
          )}
        </div>
      </div>

      {/* Template Modal */}
      {showTemplateModal && (
        <TemplateModal
          template={editingTemplate}
          onClose={() => {
            setShowTemplateModal(false)
            setEditingTemplate(null)
          }}
          onSave={(data) => {
            if (editingTemplate) {
              updateTemplateMutation.mutate({ id: editingTemplate.id, data })
            } else {
              createTemplateMutation.mutate(data)
            }
          }}
          isLoading={createTemplateMutation.isLoading || updateTemplateMutation.isLoading}
        />
      )}

      {/* Campaign Modal */}
      {showCampaignModal && (
        <CampaignModal
          campaign={editingCampaign}
          users={users || []}
          templates={templates || []}
          onClose={() => {
            setShowCampaignModal(false)
            setEditingCampaign(null)
          }}
          onSave={(data) => {
            if (editingCampaign) {
              updateCampaignMutation.mutate({ id: editingCampaign.id, data })
            } else {
              createCampaignMutation.mutate(data)
            }
          }}
          isLoading={createCampaignMutation.isLoading || updateCampaignMutation.isLoading}
        />
      )}
    </div>
  )
}

// Compose Email Tab Component
interface ComposeEmailTabProps {
  users: any[]
  events: any[]
  contests: any[]
  templates: EmailTemplate[]
  onSend: (data: any) => void
  isLoading: boolean
}

const ComposeEmailTab: React.FC<ComposeEmailTabProps> = ({
  users,
  events,
  contests,
  templates,
  onSend,
  isLoading,
}) => {
  const [formData, setFormData] = useState({
    to: [] as string[],
    cc: [] as string[],
    bcc: [] as string[],
    subject: '',
    content: '',
    templateId: '',
    eventId: '',
    contestId: '',
  })

  const [selectedTemplate, setSelectedTemplate] = useState<EmailTemplate | null>(null)

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSend(formData)
  }

  const handleTemplateSelect = (templateId: string) => {
    const template = templates.find(t => t.id === templateId)
    if (template) {
      setSelectedTemplate(template)
      setFormData({
        ...formData,
        subject: template.subject,
        content: template.content,
        templateId: template.id,
      })
    }
  }

  const addRecipient = (type: 'to' | 'cc' | 'bcc', email: string) => {
    if (email && !formData[type].includes(email)) {
      setFormData({
        ...formData,
        [type]: [...formData[type], email],
      })
    }
  }

  const removeRecipient = (type: 'to' | 'cc' | 'bcc', email: string) => {
    setFormData({
      ...formData,
      [type]: formData[type].filter(e => e !== email),
    })
  }

  return (
    <div className="space-y-6">
      <h3 className="text-lg font-medium text-gray-900 dark:text-white">Compose Email</h3>
      
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Template Selection */}
        <div>
          <label className="label">Email Template (Optional)</label>
          <select
            value={formData.templateId}
            onChange={(e) => handleTemplateSelect(e.target.value)}
            className="input"
          >
            <option value="">Select a template</option>
            {templates.map((template) => (
              <option key={template.id} value={template.id}>
                {template.name} - {template.subject}
              </option>
            ))}
          </select>
        </div>

        {/* Recipients */}
        <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
          <div>
            <label className="label">To</label>
            <RecipientSelector
              recipients={formData.to}
              users={users}
              onAdd={(email) => addRecipient('to', email)}
              onRemove={(email) => removeRecipient('to', email)}
            />
          </div>
          <div>
            <label className="label">CC (Optional)</label>
            <RecipientSelector
              recipients={formData.cc}
              users={users}
              onAdd={(email) => addRecipient('cc', email)}
              onRemove={(email) => removeRecipient('cc', email)}
            />
          </div>
          <div>
            <label className="label">BCC (Optional)</label>
            <RecipientSelector
              recipients={formData.bcc}
              users={users}
              onAdd={(email) => addRecipient('bcc', email)}
              onRemove={(email) => removeRecipient('bcc', email)}
            />
          </div>
        </div>

        {/* Subject */}
        <div>
          <label className="label">Subject</label>
          <input
            type="text"
            value={formData.subject}
            onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
            className="input"
            required
          />
        </div>

        {/* Content */}
        <div>
          <label className="label">Message Content</label>
          <textarea
            value={formData.content}
            onChange={(e) => setFormData({ ...formData, content: e.target.value })}
            className="input min-h-[300px]"
            rows={10}
            required
          />
        </div>

        {/* Context Selection */}
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="label">Event Context (Optional)</label>
            <select
              value={formData.eventId}
              onChange={(e) => setFormData({ ...formData, eventId: e.target.value })}
              className="input"
            >
              <option value="">Select an event</option>
              {events.map((event) => (
                <option key={event.id} value={event.id}>
                  {event.name}
                </option>
              ))}
            </select>
          </div>
          <div>
            <label className="label">Contest Context (Optional)</label>
            <select
              value={formData.contestId}
              onChange={(e) => setFormData({ ...formData, contestId: e.target.value })}
              className="input"
            >
              <option value="">Select a contest</option>
              {contests.map((contest) => (
                <option key={contest.id} value={contest.id}>
                  {contest.name}
                </option>
              ))}
            </select>
          </div>
        </div>

        {/* Actions */}
        <div className="flex justify-end space-x-3">
          <button
            type="button"
            className="btn btn-outline"
          >
            Save as Draft
          </button>
          <button
            type="submit"
            className="btn btn-primary"
            disabled={isLoading || formData.to.length === 0}
          >
            {isLoading ? 'Sending...' : 'Send Email'}
          </button>
        </div>
      </form>
    </div>
  )
}

// Recipient Selector Component
interface RecipientSelectorProps {
  recipients: string[]
  users: any[]
  onAdd: (email: string) => void
  onRemove: (email: string) => void
}

const RecipientSelector: React.FC<RecipientSelectorProps> = ({
  recipients,
  users,
  onAdd,
  onRemove,
}) => {
  const [inputValue, setInputValue] = useState('')

  const handleAdd = () => {
    if (inputValue) {
      onAdd(inputValue)
      setInputValue('')
    }
  }

  const handleKeyPress = (e: React.KeyboardEvent) => {
    if (e.key === 'Enter') {
      e.preventDefault()
      handleAdd()
    }
  }

  return (
    <div className="space-y-2">
      <div className="flex space-x-2">
        <input
          type="email"
          value={inputValue}
          onChange={(e) => setInputValue(e.target.value)}
          onKeyPress={handleKeyPress}
          className="input flex-1"
          placeholder="Enter email address"
        />
        <button
          type="button"
          onClick={handleAdd}
          className="btn btn-outline btn-sm"
        >
          Add
        </button>
      </div>
      <div className="space-y-1">
        {recipients.map((email) => (
          <div key={email} className="flex items-center justify-between bg-gray-50 dark:bg-gray-700 rounded px-2 py-1">
            <span className="text-sm text-gray-900 dark:text-white">{email}</span>
            <button
              type="button"
              onClick={() => onRemove(email)}
              className="text-red-600 hover:text-red-700"
            >
              <XMarkIcon className="h-4 w-4" />
            </button>
          </div>
        ))}
      </div>
    </div>
  )
}

// Templates Tab Component
interface TemplatesTabProps {
  templates: EmailTemplate[]
  isLoading: boolean
  onEdit: (template: EmailTemplate) => void
  onDelete: (id: string) => void
  searchTerm: string
  onSearchChange: (term: string) => void
}

const TemplatesTab: React.FC<TemplatesTabProps> = ({
  templates,
  isLoading,
  onEdit,
  onDelete,
  searchTerm,
  onSearchChange,
}) => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Email Templates</h3>
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

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : templates.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No templates found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm ? 'Try adjusting your search criteria' : 'Create your first email template'}
          </p>
        </div>
      ) : (
        <div className="grid-responsive">
          {templates.map((template) => (
            <div key={template.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{template.name}</h3>
                    <p className="card-description line-clamp-2">{template.subject}</p>
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
                  <MailIcon className="h-4 w-4 mr-2" />
                  <span>{template.type}</span>
                </div>
                <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                  <span>Created: {format(new Date(template.createdAt), 'MMM dd, yyyy')}</span>
                  <span>Updated: {format(new Date(template.updatedAt), 'MMM dd, yyyy')}</span>
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => onEdit(template)}
                      className="btn btn-outline btn-sm"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => onDelete(template.id)}
                      className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </div>
                  <button className="btn btn-primary btn-sm">
                    <EyeIcon className="h-4 w-4 mr-1" />
                    Preview
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

// Campaigns Tab Component
interface CampaignsTabProps {
  campaigns: EmailCampaign[]
  isLoading: boolean
  onEdit: (campaign: EmailCampaign) => void
  onDelete: (id: string) => void
  searchTerm: string
  onSearchChange: (term: string) => void
  statusFilter: string
  onStatusFilterChange: (status: string) => void
}

const CampaignsTab: React.FC<CampaignsTabProps> = ({
  campaigns,
  isLoading,
  onEdit,
  onDelete,
  searchTerm,
  onSearchChange,
  statusFilter,
  onStatusFilterChange,
}) => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Email Campaigns</h3>
        <div className="flex space-x-2">
          <div className="relative">
            <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search campaigns..."
              value={searchTerm}
              onChange={(e) => onSearchChange(e.target.value)}
              className="input pl-10"
            />
          </div>
          <select
            value={statusFilter}
            onChange={(e) => onStatusFilterChange(e.target.value)}
            className="input"
          >
            <option value="ALL">All Status</option>
            <option value="DRAFT">Draft</option>
            <option value="SCHEDULED">Scheduled</option>
            <option value="SENDING">Sending</option>
            <option value="SENT">Sent</option>
            <option value="FAILED">Failed</option>
          </select>
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : campaigns.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <PaperAirplaneIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No campaigns found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm || statusFilter !== 'ALL' ? 'Try adjusting your search criteria' : 'Create your first email campaign'}
          </p>
        </div>
      ) : (
        <div className="grid-responsive">
          {campaigns.map((campaign) => (
            <div key={campaign.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{campaign.name}</h3>
                    <p className="card-description line-clamp-2">{campaign.subject}</p>
                  </div>
                  <div className="flex items-center space-x-2 ml-2">
                    <span className={`badge ${getStatusColor(campaign.status)}`}>
                      {getStatusText(campaign.status)}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <UserGroupIcon className="h-4 w-4 mr-2" />
                  <span>{campaign.recipients.length} recipients</span>
                </div>
                <div className="grid grid-cols-2 gap-4 text-sm">
                  <div>
                    <div className="text-gray-600 dark:text-gray-400">Sent</div>
                    <div className="font-medium text-gray-900 dark:text-white">
                      {campaign.stats.sent} / {campaign.stats.total}
                    </div>
                  </div>
                  <div>
                    <div className="text-gray-600 dark:text-gray-400">Opened</div>
                    <div className="font-medium text-gray-900 dark:text-white">
                      {campaign.stats.opened}
                    </div>
                  </div>
                </div>
                <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                  <span>Created: {format(new Date(campaign.createdAt), 'MMM dd, yyyy')}</span>
                  {campaign.sentAt && (
                    <span>Sent: {format(new Date(campaign.sentAt), 'MMM dd, yyyy')}</span>
                  )}
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => onEdit(campaign)}
                      className="btn btn-outline btn-sm"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => onDelete(campaign.id)}
                      className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </div>
                  <button className="btn btn-primary btn-sm">
                    <EyeIcon className="h-4 w-4 mr-1" />
                    View Stats
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

// Logs Tab Component
interface LogsTabProps {
  logs: EmailLog[]
  isLoading: boolean
  searchTerm: string
  onSearchChange: (term: string) => void
}

const LogsTab: React.FC<LogsTabProps> = ({
  logs,
  isLoading,
  searchTerm,
  onSearchChange,
}) => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Email Logs</h3>
        <div className="relative">
          <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
          <input
            type="text"
            placeholder="Search logs..."
            value={searchTerm}
            onChange={(e) => onSearchChange(e.target.value)}
            className="input pl-10"
          />
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : logs.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <ClockIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No email logs found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm ? 'Try adjusting your search criteria' : 'Email logs will appear here once emails are sent'}
          </p>
        </div>
      ) : (
        <div className="overflow-x-auto">
          <table className="table">
            <thead>
              <tr>
                <th>To</th>
                <th>Subject</th>
                <th>Status</th>
                <th>Sent At</th>
                <th>Error</th>
                <th>Actions</th>
              </tr>
            </thead>
            <tbody>
              {logs.map((log) => (
                <tr key={log.id}>
                  <td className="font-medium text-gray-900 dark:text-white">
                    {log.to}
                  </td>
                  <td className="text-gray-600 dark:text-gray-400">
                    {log.subject}
                  </td>
                  <td>
                    <span className={`badge ${getStatusColor(log.status)}`}>
                      {getStatusText(log.status)}
                    </span>
                  </td>
                  <td className="text-gray-600 dark:text-gray-400">
                    {format(new Date(log.sentAt), 'MMM dd, yyyy HH:mm')}
                  </td>
                  <td className="text-gray-600 dark:text-gray-400">
                    {log.errorMessage || '--'}
                  </td>
                  <td>
                    <button className="btn btn-outline btn-sm">
                      <EyeIcon className="h-4 w-4" />
                    </button>
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

// Template Modal Component
interface TemplateModalProps {
  template: EmailTemplate | null
  onClose: () => void
  onSave: (data: Partial<EmailTemplate>) => void
  isLoading: boolean
}

const TemplateModal: React.FC<TemplateModalProps> = ({ template, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    name: template?.name || '',
    subject: template?.subject || '',
    content: template?.content || '',
    type: template?.type || 'CUSTOM',
    isActive: template?.isActive ?? true,
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSave(formData)
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-4xl">
        <h2 className="text-xl font-semibold mb-4">
          {template ? 'Edit Template' : 'Create Template'}
        </h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Template Name</label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="input"
                required
              />
            </div>
            <div>
              <label className="label">Type</label>
              <select
                value={formData.type}
                onChange={(e) => setFormData({ ...formData, type: e.target.value as any })}
                className="input"
                required
              >
                <option value="WELCOME">Welcome</option>
                <option value="RESULTS">Results</option>
                <option value="REMINDER">Reminder</option>
                <option value="ANNOUNCEMENT">Announcement</option>
                <option value="CUSTOM">Custom</option>
              </select>
            </div>
          </div>
          <div>
            <label className="label">Subject</label>
            <input
              type="text"
              value={formData.subject}
              onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
              className="input"
              required
            />
          </div>
          <div>
            <label className="label">Content</label>
            <textarea
              value={formData.content}
              onChange={(e) => setFormData({ ...formData, content: e.target.value })}
              className="input min-h-[300px]"
              rows={10}
              required
            />
          </div>
          <div className="flex items-center space-x-2">
            <input
              type="checkbox"
              id="isActive"
              checked={formData.isActive}
              onChange={(e) => setFormData({ ...formData, isActive: e.target.checked })}
              className="rounded border-gray-300 text-primary focus:ring-primary"
            />
            <label htmlFor="isActive" className="label">
              Active template
            </label>
          </div>
          <div className="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="btn btn-outline"
              disabled={isLoading}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={isLoading}
            >
              {isLoading ? 'Saving...' : template ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Campaign Modal Component
interface CampaignModalProps {
  campaign: EmailCampaign | null
  users: any[]
  templates: EmailTemplate[]
  onClose: () => void
  onSave: (data: Partial<EmailCampaign>) => void
  isLoading: boolean
}

const CampaignModal: React.FC<CampaignModalProps> = ({
  campaign,
  users,
  templates,
  onClose,
  onSave,
  isLoading,
}) => {
  const [formData, setFormData] = useState({
    name: campaign?.name || '',
    subject: campaign?.subject || '',
    content: campaign?.content || '',
    recipients: campaign?.recipients || [],
    scheduledAt: campaign?.scheduledAt || '',
    templateId: '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSave(formData)
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-4xl">
        <h2 className="text-xl font-semibold mb-4">
          {campaign ? 'Edit Campaign' : 'Create Campaign'}
        </h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Campaign Name</label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="input"
                required
              />
            </div>
            <div>
              <label className="label">Template (Optional)</label>
              <select
                value={formData.templateId}
                onChange={(e) => {
                  const template = templates.find(t => t.id === e.target.value)
                  if (template) {
                    setFormData({
                      ...formData,
                      subject: template.subject,
                      content: template.content,
                      templateId: template.id,
                    })
                  }
                }}
                className="input"
              >
                <option value="">Select a template</option>
                {templates.map((template) => (
                  <option key={template.id} value={template.id}>
                    {template.name}
                  </option>
                ))}
              </select>
            </div>
          </div>
          <div>
            <label className="label">Subject</label>
            <input
              type="text"
              value={formData.subject}
              onChange={(e) => setFormData({ ...formData, subject: e.target.value })}
              className="input"
              required
            />
          </div>
          <div>
            <label className="label">Content</label>
            <textarea
              value={formData.content}
              onChange={(e) => setFormData({ ...formData, content: e.target.value })}
              className="input min-h-[300px]"
              rows={10}
              required
            />
          </div>
          <div>
            <label className="label">Recipients</label>
            <RecipientSelector
              recipients={formData.recipients}
              users={users}
              onAdd={(email) => {
                if (!formData.recipients.includes(email)) {
                  setFormData({
                    ...formData,
                    recipients: [...formData.recipients, email],
                  })
                }
              }}
              onRemove={(email) => {
                setFormData({
                  ...formData,
                  recipients: formData.recipients.filter(e => e !== email),
                })
              }}
            />
          </div>
          <div>
            <label className="label">Schedule (Optional)</label>
            <input
              type="datetime-local"
              value={formData.scheduledAt}
              onChange={(e) => setFormData({ ...formData, scheduledAt: e.target.value })}
              className="input"
            />
          </div>
          <div className="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              onClick={onClose}
              className="btn btn-outline"
              disabled={isLoading}
            >
              Cancel
            </button>
            <button
              type="submit"
              className="btn btn-primary"
              disabled={isLoading}
            >
              {isLoading ? 'Saving...' : campaign ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default EmailManager
