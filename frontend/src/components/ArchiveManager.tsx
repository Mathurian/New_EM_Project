import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { archiveAPI } from '../services/api'
import {
  ArchiveBoxIcon,
  ArrowDownTrayIcon,
  TrashIcon,
  EyeIcon,
  CalendarIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline'

interface ArchivedEvent {
  id: string
  name: string
  description: string
  startDate: string
  endDate: string
  location: string
  archivedAt: string
  archivedBy: string
  reason: string
  originalEventId: string
  contests: number
  contestants: number
  totalScores: number
}

const ArchiveManager: React.FC = () => {
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'archive' | 'restore' | 'history'>('archive')
  const [selectedEvent, setSelectedEvent] = useState<any>(null)
  const [archiveReason, setArchiveReason] = useState('')

  const { data: archivedEvents, isLoading: archiveLoading } = useQuery(
    'archived-events',
    () => archiveAPI.getAll().then(res => res.data),
    {
      refetchInterval: 60000,
    }
  )

  const { data: activeEvents, isLoading: eventsLoading } = useQuery(
    'active-events',
    () => archiveAPI.getActiveEvents().then(res => res.data),
    {
      refetchInterval: 60000,
    }
  )

  const archiveEventMutation = useMutation(
    ({ eventId, reason }: { eventId: string; reason: string }) => archiveAPI.archive(eventId, reason),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('archived-events')
        queryClient.invalidateQueries('active-events')
        setSelectedEvent(null)
        setArchiveReason('')
      },
    }
  )

  const restoreEventMutation = useMutation(
    (eventId: string) => archiveAPI.restore(eventId),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('archived-events')
        queryClient.invalidateQueries('active-events')
      },
    }
  )

  const deleteArchiveMutation = useMutation(
    (eventId: string) => archiveAPI.delete(eventId),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('archived-events')
      },
    }
  )

  const handleArchive = () => {
    if (selectedEvent && archiveReason) {
      archiveEventMutation.mutate({
        eventId: selectedEvent.id,
        reason: archiveReason,
      })
    }
  }

  const tabs = [
    { id: 'archive', label: 'Archive Events', icon: ArchiveBoxIcon },
    { id: 'restore', label: 'Restore Events', icon: ArrowDownTrayIcon },
    { id: 'history', label: 'Archive History', icon: CalendarIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Archive Manager</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Archive completed events and manage historical data
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
      {activeTab === 'archive' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Archive Events</h3>
              <p className="card-description">Select events to archive for long-term storage</p>
            </div>
            <div className="card-content">
              {eventsLoading ? (
                <div className="flex items-center justify-center py-8">
                  <div className="loading-spinner"></div>
                </div>
              ) : activeEvents && activeEvents.length > 0 ? (
                <div className="space-y-4">
                  {activeEvents.map((event: any) => (
                    <div
                      key={event.id}
                      className={`p-4 border rounded-lg cursor-pointer transition-colors ${
                        selectedEvent?.id === event.id
                          ? 'border-primary bg-primary/5'
                          : 'border-gray-200 dark:border-gray-700 hover:border-gray-300 dark:hover:border-gray-600'
                      }`}
                      onClick={() => setSelectedEvent(event)}
                    >
                      <div className="flex items-center justify-between">
                        <div>
                          <h4 className="font-medium text-gray-900 dark:text-white">{event.name}</h4>
                          <p className="text-sm text-gray-600 dark:text-gray-400">{event.description}</p>
                          <div className="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>{new Date(event.startDate).toLocaleDateString()} - {new Date(event.endDate).toLocaleDateString()}</span>
                            <span>{event.location}</span>
                            <span>{event._count?.contests || 0} contests</span>
                            <span>{event._count?.contestants || 0} contestants</span>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <span className={`badge ${
                            event.status === 'COMPLETED' ? 'badge-success' : 'badge-warning'
                          }`}>
                            {event.status}
                          </span>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <ArchiveBoxIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No active events found</p>
                  <p className="text-sm mt-2">All events have been archived</p>
                </div>
              )}

              {selectedEvent && (
                <div className="mt-6 p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                  <h4 className="font-medium text-gray-900 dark:text-white mb-3">
                    Archive "{selectedEvent.name}"
                  </h4>
                  <div className="space-y-3">
                    <div>
                      <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                        Archive Reason
                      </label>
                      <textarea
                        value={archiveReason}
                        onChange={(e) => setArchiveReason(e.target.value)}
                        placeholder="Enter reason for archiving this event..."
                        className="input w-full"
                        rows={3}
                      />
                    </div>
                    <div className="flex space-x-3">
                      <button
                        onClick={handleArchive}
                        disabled={!archiveReason || archiveEventMutation.isLoading}
                        className="btn btn-primary"
                      >
                        {archiveEventMutation.isLoading ? (
                          <>
                            <div className="loading-spinner mr-2"></div>
                            Archiving...
                          </>
                        ) : (
                          <>
                            <ArchiveBoxIcon className="h-4 w-4 mr-2" />
                            Archive Event
                          </>
                        )}
                      </button>
                      <button
                        onClick={() => {
                          setSelectedEvent(null)
                          setArchiveReason('')
                        }}
                        className="btn btn-outline"
                      >
                        Cancel
                      </button>
                    </div>
                  </div>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {activeTab === 'restore' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Restore Archived Events</h3>
              <p className="card-description">Restore archived events back to active status</p>
            </div>
            <div className="card-content">
              {archiveLoading ? (
                <div className="flex items-center justify-center py-8">
                  <div className="loading-spinner"></div>
                </div>
              ) : archivedEvents && archivedEvents.length > 0 ? (
                <div className="space-y-4">
                  {archivedEvents.map((event: ArchivedEvent) => (
                    <div key={event.id} className="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                      <div className="flex items-center justify-between">
                        <div>
                          <h4 className="font-medium text-gray-900 dark:text-white">{event.name}</h4>
                          <p className="text-sm text-gray-600 dark:text-gray-400">{event.description}</p>
                          <div className="flex items-center space-x-4 mt-2 text-xs text-gray-500 dark:text-gray-400">
                            <span>Archived: {new Date(event.archivedAt).toLocaleDateString()}</span>
                            <span>By: {event.archivedBy}</span>
                            <span>Reason: {event.reason}</span>
                          </div>
                        </div>
                        <div className="flex items-center space-x-2">
                          <button
                            onClick={() => restoreEventMutation.mutate(event.id)}
                            disabled={restoreEventMutation.isLoading}
                            className="btn btn-primary btn-sm"
                          >
                            <ArrowDownTrayIcon className="h-4 w-4 mr-1" />
                            Restore
                          </button>
                          <button
                            onClick={() => deleteArchiveMutation.mutate(event.id)}
                            disabled={deleteArchiveMutation.isLoading}
                            className="btn btn-ghost btn-sm text-red-600 hover:text-red-700"
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </div>
                      </div>
                    </div>
                  ))}
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <CalendarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No archived events found</p>
                  <p className="text-sm mt-2">Archived events will appear here</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}

      {activeTab === 'history' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Archive History</h3>
              <p className="card-description">View detailed archive history and statistics</p>
            </div>
            <div className="card-content">
              <div className="text-center py-12">
                <CalendarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">Archive History</h3>
                <p className="text-gray-600 dark:text-gray-400">This page will contain detailed archive history and statistics</p>
              </div>
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default ArchiveManager
