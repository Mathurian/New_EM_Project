import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { api } from '../services/api'
import {
  MicrophoneIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  DocumentTextIcon,
  ClockIcon,
  CalendarIcon,
  UserIcon,
  SpeakerWaveIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface EmceeScript {
  id: string
  title: string
  content: string
  type: 'WELCOME' | 'INTRO' | 'ANNOUNCEMENT' | 'AWARD' | 'CLOSING' | 'CUSTOM'
  eventId?: string
  contestId?: string
  categoryId?: string
  order: number
  isActive: boolean
  createdAt: string
  updatedAt: string
  createdBy: string
  event?: {
    id: string
    name: string
  }
  contest?: {
    id: string
    name: string
  }
  category?: {
    id: string
    name: string
  }
}

const EmceePage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [typeFilter, setTypeFilter] = useState<string>('ALL')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [editingScript, setEditingScript] = useState<EmceeScript | null>(null)
  const [showDeleteModal, setShowDeleteModal] = useState<EmceeScript | null>(null)
  const [showPreviewModal, setShowPreviewModal] = useState<EmceeScript | null>(null)

  const { data: scripts, isLoading } = useQuery(
    'emcee-scripts',
    () => api.get('/emcee/scripts').then(res => res.data),
    {
      enabled: user?.role === 'EMCEE' || user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const createMutation = useMutation(
    (scriptData: Partial<EmceeScript>) => api.post('/emcee/scripts', scriptData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('emcee-scripts')
        setShowCreateModal(false)
      },
    }
  )

  const updateMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<EmceeScript> }) =>
      api.put(`/emcee/scripts/${id}`, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('emcee-scripts')
        setEditingScript(null)
      },
    }
  )

  const deleteMutation = useMutation(
    (id: string) => api.delete(`/emcee/scripts/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('emcee-scripts')
        setShowDeleteModal(null)
      },
    }
  )

  const filteredScripts = scripts?.filter((script: EmceeScript) => {
    const matchesSearch = script.title.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         script.content.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesType = typeFilter === 'ALL' || script.type === typeFilter
    return matchesSearch && matchesType
  }) || []

  const getTypeColor = (type: string) => {
    switch (type) {
      case 'WELCOME': return 'badge-default'
      case 'INTRO': return 'badge-secondary'
      case 'ANNOUNCEMENT': return 'badge-success'
      case 'AWARD': return 'badge-warning'
      case 'CLOSING': return 'badge-destructive'
      case 'CUSTOM': return 'badge-outline'
      default: return 'badge-secondary'
    }
  }

  const getTypeText = (type: string) => {
    switch (type) {
      case 'WELCOME': return 'Welcome'
      case 'INTRO': return 'Introduction'
      case 'ANNOUNCEMENT': return 'Announcement'
      case 'AWARD': return 'Award'
      case 'CLOSING': return 'Closing'
      case 'CUSTOM': return 'Custom'
      default: return type
    }
  }

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'WELCOME': return '👋'
      case 'INTRO': return '🎤'
      case 'ANNOUNCEMENT': return '📢'
      case 'AWARD': return '🏆'
      case 'CLOSING': return '👋'
      case 'CUSTOM': return '📝'
      default: return '📝'
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Emcee Scripts</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Manage your emcee scripts and announcements
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Create Script
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="card">
        <div className="card-content">
          <div className="flex flex-col sm:flex-row gap-4">
            <div className="flex-1">
              <div className="relative">
                <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
                <input
                  type="text"
                  placeholder="Search scripts..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="input pl-10"
                />
              </div>
            </div>
            <div className="sm:w-48">
              <select
                value={typeFilter}
                onChange={(e) => setTypeFilter(e.target.value)}
                className="input"
              >
                <option value="ALL">All Types</option>
                <option value="WELCOME">Welcome</option>
                <option value="INTRO">Introduction</option>
                <option value="ANNOUNCEMENT">Announcement</option>
                <option value="AWARD">Award</option>
                <option value="CLOSING">Closing</option>
                <option value="CUSTOM">Custom</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Scripts Grid */}
      {filteredScripts.length === 0 ? (
        <div className="card">
          <div className="card-content text-center py-12">
            <MicrophoneIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              No scripts found
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              {searchTerm || typeFilter !== 'ALL'
                ? 'Try adjusting your search criteria'
                : 'Get started by creating your first emcee script'}
            </p>
            {!searchTerm && typeFilter === 'ALL' && (
              <button
                onClick={() => setShowCreateModal(true)}
                className="btn btn-primary"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Create Script
              </button>
            )}
          </div>
        </div>
      ) : (
        <div className="grid-responsive">
          {filteredScripts.map((script: EmceeScript) => (
            <div key={script.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{script.title}</h3>
                    <p className="card-description line-clamp-2">{script.content}</p>
                  </div>
                  <div className="flex items-center space-x-2 ml-2">
                    <span className="text-2xl">{getTypeIcon(script.type)}</span>
                    <span className={`badge ${getTypeColor(script.type)}`}>
                      {getTypeText(script.type)}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <ClockIcon className="h-4 w-4 mr-2" />
                  <span>Order: {script.order}</span>
                </div>
                {script.event && (
                  <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <CalendarIcon className="h-4 w-4 mr-2" />
                    <span>Event: {script.event.name}</span>
                  </div>
                )}
                {script.contest && (
                  <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <TrophyIcon className="h-4 w-4 mr-2" />
                    <span>Contest: {script.contest.name}</span>
                  </div>
                )}
                {script.category && (
                  <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                    <DocumentTextIcon className="h-4 w-4 mr-2" />
                    <span>Category: {script.category.name}</span>
                  </div>
                )}
                <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                  <span>Created: {format(new Date(script.createdAt), 'MMM dd, yyyy')}</span>
                  <span className={`status-indicator ${script.isActive ? 'status-online' : 'status-offline'}`}>
                    {script.isActive ? 'Active' : 'Inactive'}
                  </span>
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => setEditingScript(script)}
                      className="btn btn-outline btn-sm"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => setShowDeleteModal(script)}
                      className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </div>
                  <button
                    onClick={() => setShowPreviewModal(script)}
                    className="btn btn-primary btn-sm"
                  >
                    <EyeIcon className="h-4 w-4 mr-1" />
                    Preview
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Create Script Modal */}
      {showCreateModal && (
        <ScriptModal
          script={null}
          onClose={() => setShowCreateModal(false)}
          onSave={(data) => createMutation.mutate(data)}
          isLoading={createMutation.isLoading}
        />
      )}

      {/* Edit Script Modal */}
      {editingScript && (
        <ScriptModal
          script={editingScript}
          onClose={() => setEditingScript(null)}
          onSave={(data) => updateMutation.mutate({ id: editingScript.id, data })}
          isLoading={updateMutation.isLoading}
        />
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <DeleteModal
          script={showDeleteModal}
          onClose={() => setShowDeleteModal(null)}
          onConfirm={() => deleteMutation.mutate(showDeleteModal.id)}
          isLoading={deleteMutation.isLoading}
        />
      )}

      {/* Preview Modal */}
      {showPreviewModal && (
        <PreviewModal
          script={showPreviewModal}
          onClose={() => setShowPreviewModal(null)}
        />
      )}
    </div>
  )
}

// Script Modal Component
interface ScriptModalProps {
  script: EmceeScript | null
  onClose: () => void
  onSave: (data: Partial<EmceeScript>) => void
  isLoading: boolean
}

const ScriptModal: React.FC<ScriptModalProps> = ({ script, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    title: script?.title || '',
    content: script?.content || '',
    type: script?.type || 'CUSTOM',
    order: script?.order || 1,
    isActive: script?.isActive ?? true,
    eventId: script?.eventId || '',
    contestId: script?.contestId || '',
    categoryId: script?.categoryId || '',
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
          {script ? 'Edit Script' : 'Create Script'}
        </h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Title</label>
              <input
                type="text"
                value={formData.title}
                onChange={(e) => setFormData({ ...formData, title: e.target.value })}
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
                <option value="INTRO">Introduction</option>
                <option value="ANNOUNCEMENT">Announcement</option>
                <option value="AWARD">Award</option>
                <option value="CLOSING">Closing</option>
                <option value="CUSTOM">Custom</option>
              </select>
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label className="label">Order</label>
              <input
                type="number"
                value={formData.order}
                onChange={(e) => setFormData({ ...formData, order: parseInt(e.target.value) })}
                className="input"
                min="1"
                required
              />
            </div>
            <div>
              <label className="label">Event (Optional)</label>
              <select
                value={formData.eventId}
                onChange={(e) => setFormData({ ...formData, eventId: e.target.value })}
                className="input"
              >
                <option value="">Select Event</option>
                {/* Event options would be populated from API */}
              </select>
            </div>
            <div>
              <label className="label">Contest (Optional)</label>
              <select
                value={formData.contestId}
                onChange={(e) => setFormData({ ...formData, contestId: e.target.value })}
                className="input"
              >
                <option value="">Select Contest</option>
                {/* Contest options would be populated from API */}
              </select>
            </div>
          </div>
          <div>
            <label className="label">Script Content</label>
            <textarea
              value={formData.content}
              onChange={(e) => setFormData({ ...formData, content: e.target.value })}
              className="input min-h-[300px]"
              rows={10}
              placeholder="Enter your emcee script content here..."
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
              Active script
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
              {isLoading ? 'Saving...' : script ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Delete Confirmation Modal
interface DeleteModalProps {
  script: EmceeScript
  onClose: () => void
  onConfirm: () => void
  isLoading: boolean
}

const DeleteModal: React.FC<DeleteModalProps> = ({ script, onClose, onConfirm, isLoading }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Delete Script</h2>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Are you sure you want to delete "{script.title}"? This action cannot be undone.
        </p>
        <div className="flex justify-end space-x-3">
          <button
            onClick={onClose}
            className="btn btn-outline"
            disabled={isLoading}
          >
            Cancel
          </button>
          <button
            onClick={onConfirm}
            className="btn btn-destructive"
            disabled={isLoading}
          >
            {isLoading ? 'Deleting...' : 'Delete'}
          </button>
        </div>
      </div>
    </div>
  )
}

// Preview Modal Component
interface PreviewModalProps {
  script: EmceeScript
  onClose: () => void
}

const PreviewModal: React.FC<PreviewModalProps> = ({ script, onClose }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-4xl">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold">Script Preview</h2>
          <button
            onClick={onClose}
            className="btn btn-ghost btn-sm"
          >
            <XMarkIcon className="h-5 w-5" />
          </button>
        </div>
        <div className="space-y-4">
          <div className="flex items-center space-x-4">
            <div className="text-3xl">{getTypeIcon(script.type)}</div>
            <div>
              <h3 className="text-lg font-medium text-gray-900 dark:text-white">{script.title}</h3>
              <span className={`badge ${getTypeColor(script.type)}`}>
                {getTypeText(script.type)}
              </span>
            </div>
          </div>
          <div className="bg-gray-50 dark:bg-gray-700 rounded-lg p-6">
            <div className="prose prose-sm max-w-none">
              <pre className="whitespace-pre-wrap text-gray-900 dark:text-white font-sans">
                {script.content}
              </pre>
            </div>
          </div>
          <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
            <span>Order: {script.order}</span>
            <span>Created: {format(new Date(script.createdAt), 'MMM dd, yyyy')}</span>
          </div>
        </div>
        <div className="flex justify-end pt-4">
          <button
            onClick={onClose}
            className="btn btn-primary"
          >
            Close
          </button>
        </div>
      </div>
    </div>
  )
}

export default EmceePage
