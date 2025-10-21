import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { eventsAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  CalendarIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon,
  ClockIcon,
  MapPinIcon,
  UsersIcon,
  TrophyIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface Event {
  id: string
  name: string
  description: string
  startDate: string
  endDate: string
  location: string
  maxContestants: number
  status: 'DRAFT' | 'ACTIVE' | 'COMPLETED' | 'ARCHIVED'
  createdAt: string
  updatedAt: string
  _count?: {
    contests: number
    contestants: number
  }
}

const EventsPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [statusFilter, setStatusFilter] = useState<string>('ALL')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [editingEvent, setEditingEvent] = useState<Event | null>(null)
  const [showDeleteModal, setShowDeleteModal] = useState<Event | null>(null)

  const { data: events, isLoading } = useQuery(
    'events',
    () => eventsAPI.getAll().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const createMutation = useMutation(
    (eventData: Partial<Event>) => eventsAPI.create(eventData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('events')
        setShowCreateModal(false)
      },
    }
  )

  const updateMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<Event> }) =>
      eventsAPI.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('events')
        setEditingEvent(null)
      },
    }
  )

  const deleteMutation = useMutation(
    (id: string) => eventsAPI.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('events')
        setShowDeleteModal(null)
      },
    }
  )

  const filteredEvents = events?.filter((event: Event) => {
    const matchesSearch = event.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         event.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         event.location.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesStatus = statusFilter === 'ALL' || event.status === statusFilter
    return matchesSearch && matchesStatus
  }) || []

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'DRAFT': return 'badge-secondary'
      case 'ACTIVE': return 'badge-default'
      case 'COMPLETED': return 'badge-success'
      case 'ARCHIVED': return 'badge-outline'
      default: return 'badge-secondary'
    }
  }

  const getStatusText = (status: string) => {
    switch (status) {
      case 'DRAFT': return 'Draft'
      case 'ACTIVE': return 'Active'
      case 'COMPLETED': return 'Completed'
      case 'ARCHIVED': return 'Archived'
      default: return status
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Events</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Manage contest events and competitions
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Create Event
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
                  placeholder="Search events..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="input pl-10"
                />
              </div>
            </div>
            <div className="sm:w-48">
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="input"
              >
                <option value="ALL">All Status</option>
                <option value="DRAFT">Draft</option>
                <option value="ACTIVE">Active</option>
                <option value="COMPLETED">Completed</option>
                <option value="ARCHIVED">Archived</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Events Grid */}
      {filteredEvents.length === 0 ? (
        <div className="card">
          <div className="card-content text-center py-12">
            <CalendarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              No events found
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              {searchTerm || statusFilter !== 'ALL'
                ? 'Try adjusting your search criteria'
                : 'Get started by creating your first event'}
            </p>
            {!searchTerm && statusFilter === 'ALL' && (
              <button
                onClick={() => setShowCreateModal(true)}
                className="btn btn-primary"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Create Event
              </button>
            )}
          </div>
        </div>
      ) : (
        <div className="grid-responsive">
          {filteredEvents.map((event: Event) => (
            <div key={event.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{event.name}</h3>
                    <p className="card-description line-clamp-2">{event.description}</p>
                  </div>
                  <span className={`badge ${getStatusColor(event.status)} ml-2`}>
                    {getStatusText(event.status)}
                  </span>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <CalendarIcon className="h-4 w-4 mr-2" />
                  <span>
                    {format(new Date(event.startDate), 'MMM dd, yyyy')} -{' '}
                    {format(new Date(event.endDate), 'MMM dd, yyyy')}
                  </span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <MapPinIcon className="h-4 w-4 mr-2" />
                  <span className="truncate">{event.location}</span>
                </div>
                <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                  <div className="flex items-center">
                    <TrophyIcon className="h-4 w-4 mr-2" />
                    <span>{event._count?.contests || 0} contests</span>
                  </div>
                  <div className="flex items-center">
                    <UsersIcon className="h-4 w-4 mr-2" />
                    <span>{event._count?.contestants || 0} contestants</span>
                  </div>
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => setEditingEvent(event)}
                      className="btn btn-outline btn-sm"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => setShowDeleteModal(event)}
                      className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </div>
                  <button className="btn btn-primary btn-sm">
                    <EyeIcon className="h-4 w-4 mr-1" />
                    View
                  </button>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}

      {/* Create Event Modal */}
      {showCreateModal && (
        <EventModal
          event={null}
          onClose={() => setShowCreateModal(false)}
          onSave={(data) => createMutation.mutate(data)}
          isLoading={createMutation.isLoading}
        />
      )}

      {/* Edit Event Modal */}
      {editingEvent && (
        <EventModal
          event={editingEvent}
          onClose={() => setEditingEvent(null)}
          onSave={(data) => updateMutation.mutate({ id: editingEvent.id, data })}
          isLoading={updateMutation.isLoading}
        />
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <DeleteModal
          event={showDeleteModal}
          onClose={() => setShowDeleteModal(null)}
          onConfirm={() => deleteMutation.mutate(showDeleteModal.id)}
          isLoading={deleteMutation.isLoading}
        />
      )}
    </div>
  )
}

// Event Modal Component
interface EventModalProps {
  event: Event | null
  onClose: () => void
  onSave: (data: Partial<Event>) => void
  isLoading: boolean
}

const EventModal: React.FC<EventModalProps> = ({ event, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    name: event?.name || '',
    description: event?.description || '',
    startDate: event?.startDate ? format(new Date(event.startDate), 'yyyy-MM-dd') : '',
    endDate: event?.endDate ? format(new Date(event.endDate), 'yyyy-MM-dd') : '',
    location: event?.location || '',
    maxContestants: event?.maxContestants || 100,
    status: event?.status || 'DRAFT',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSave(formData)
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-2xl">
        <h2 className="text-xl font-semibold mb-4">
          {event ? 'Edit Event' : 'Create Event'}
        </h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Event Name</label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="input"
                required
              />
            </div>
            <div>
              <label className="label">Status</label>
              <select
                value={formData.status}
                onChange={(e) => setFormData({ ...formData, status: e.target.value as any })}
                className="input"
              >
                <option value="DRAFT">Draft</option>
                <option value="ACTIVE">Active</option>
                <option value="COMPLETED">Completed</option>
                <option value="ARCHIVED">Archived</option>
              </select>
            </div>
          </div>
          <div>
            <label className="label">Description</label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              className="input min-h-[100px]"
              rows={3}
            />
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Start Date</label>
              <input
                type="date"
                value={formData.startDate}
                onChange={(e) => setFormData({ ...formData, startDate: e.target.value })}
                className="input"
                required
              />
            </div>
            <div>
              <label className="label">End Date</label>
              <input
                type="date"
                value={formData.endDate}
                onChange={(e) => setFormData({ ...formData, endDate: e.target.value })}
                className="input"
                required
              />
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Location</label>
              <input
                type="text"
                value={formData.location}
                onChange={(e) => setFormData({ ...formData, location: e.target.value })}
                className="input"
                required
              />
            </div>
            <div>
              <label className="label">Max Contestants</label>
              <input
                type="number"
                value={formData.maxContestants}
                onChange={(e) => setFormData({ ...formData, maxContestants: parseInt(e.target.value) })}
                className="input"
                min="1"
                required
              />
            </div>
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
              {isLoading ? 'Saving...' : event ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Delete Confirmation Modal
interface DeleteModalProps {
  event: Event
  onClose: () => void
  onConfirm: () => void
  isLoading: boolean
}

const DeleteModal: React.FC<DeleteModalProps> = ({ event, onClose, onConfirm, isLoading }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Delete Event</h2>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Are you sure you want to delete "{event.name}"? This action cannot be undone.
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

export default EventsPage
