import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { backupAPI } from '../services/api'
import {
  ArrowDownTrayIcon,
  ArrowUpTrayIcon,
  TrashIcon,
  ClockIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  DocumentIcon,
  CalendarIcon,
} from '@heroicons/react/24/outline'

interface Backup {
  id: string
  filename: string
  type: 'FULL' | 'SCHEMA' | 'DATA'
  size: number
  createdAt: string
  createdBy: string
  status: 'COMPLETED' | 'FAILED' | 'IN_PROGRESS'
  description?: string
}

const BackupManager: React.FC = () => {
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'backup' | 'restore' | 'history'>('backup')
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [isUploading, setIsUploading] = useState(false)

  const { data: backups, isLoading: backupsLoading } = useQuery(
    'backups',
    () => backupAPI.getAll().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const createBackupMutation = useMutation(
    (type: 'FULL' | 'SCHEMA' | 'DATA') => backupAPI.create(type),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('backups')
      },
    }
  )

  const deleteBackupMutation = useMutation(
    (id: string) => backupAPI.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('backups')
      },
    }
  )

  const downloadBackupMutation = useMutation(
    (id: string) => backupAPI.download(id),
    {
      onSuccess: (data, id) => {
        const backup = backups?.find((b: Backup) => b.id === id)
        if (backup) {
          const url = window.URL.createObjectURL(new Blob([data]))
          const link = document.createElement('a')
          link.href = url
          link.setAttribute('download', backup.filename)
          document.body.appendChild(link)
          link.click()
          link.remove()
          window.URL.revokeObjectURL(url)
        }
      },
    }
  )

  const restoreBackupMutation = useMutation(
    (file: File) => backupAPI.restore(file),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('backups')
        setSelectedFile(null)
        setIsUploading(false)
      },
    }
  )

  const handleFileSelect = (event: React.ChangeEvent<HTMLInputElement>) => {
    const file = event.target.files?.[0]
    if (file) {
      setSelectedFile(file)
    }
  }

  const handleRestore = () => {
    if (selectedFile) {
      setIsUploading(true)
      restoreBackupMutation.mutate(selectedFile)
    }
  }

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  const getStatusIcon = (status: string) => {
    switch (status) {
      case 'COMPLETED':
        return <CheckCircleIcon className="h-5 w-5 text-green-500" />
      case 'FAILED':
        return <ExclamationTriangleIcon className="h-5 w-5 text-red-500" />
      case 'IN_PROGRESS':
        return <ClockIcon className="h-5 w-5 text-yellow-500" />
      default:
        return <ClockIcon className="h-5 w-5 text-gray-500" />
    }
  }

  const getStatusColor = (status: string) => {
    switch (status) {
      case 'COMPLETED':
        return 'badge-success'
      case 'FAILED':
        return 'badge-destructive'
      case 'IN_PROGRESS':
        return 'badge-warning'
      default:
        return 'badge-secondary'
    }
  }

  const getTypeIcon = (type: string) => {
    switch (type) {
      case 'FULL':
        return <DocumentIcon className="h-5 w-5 text-blue-500" />
      case 'SCHEMA':
        return <DocumentIcon className="h-5 w-5 text-green-500" />
      case 'DATA':
        return <DocumentIcon className="h-5 w-5 text-purple-500" />
      default:
        return <DocumentIcon className="h-5 w-5 text-gray-500" />
    }
  }

  const tabs = [
    { id: 'backup', label: 'Create Backup', icon: ArrowDownTrayIcon },
    { id: 'restore', label: 'Restore Backup', icon: ArrowUpTrayIcon },
    { id: 'history', label: 'Backup History', icon: CalendarIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div>
        <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Backup Manager</h1>
        <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
          Create, restore, and manage database backups
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
      {activeTab === 'backup' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Create New Backup</h3>
              <p className="card-description">Create a backup of your database</p>
            </div>
            <div className="card-content">
              <div className="grid grid-cols-1 md:grid-cols-3 gap-4">
                <button
                  onClick={() => createBackupMutation.mutate('FULL')}
                  disabled={createBackupMutation.isLoading}
                  className="btn btn-outline h-24 flex flex-col items-center justify-center space-y-2"
                >
                  <DocumentIcon className="h-8 w-8 text-blue-500" />
                  <span className="font-medium">Full Backup</span>
                  <span className="text-xs text-gray-500">Database + Files</span>
                </button>

                <button
                  onClick={() => createBackupMutation.mutate('SCHEMA')}
                  disabled={createBackupMutation.isLoading}
                  className="btn btn-outline h-24 flex flex-col items-center justify-center space-y-2"
                >
                  <DocumentIcon className="h-8 w-8 text-green-500" />
                  <span className="font-medium">Schema Backup</span>
                  <span className="text-xs text-gray-500">Structure Only</span>
                </button>

                <button
                  onClick={() => createBackupMutation.mutate('DATA')}
                  disabled={createBackupMutation.isLoading}
                  className="btn btn-outline h-24 flex flex-col items-center justify-center space-y-2"
                >
                  <DocumentIcon className="h-8 w-8 text-purple-500" />
                  <span className="font-medium">Data Backup</span>
                  <span className="text-xs text-gray-500">Data Only</span>
                </button>
              </div>

              {createBackupMutation.isLoading && (
                <div className="mt-4 flex items-center justify-center">
                  <div className="loading-spinner"></div>
                  <span className="ml-2 text-gray-600 dark:text-gray-400">Creating backup...</span>
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
              <h3 className="card-title">Restore from Backup</h3>
              <p className="card-description">Upload and restore a backup file</p>
            </div>
            <div className="card-content">
              <div className="space-y-4">
                <div>
                  <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                    Select Backup File
                  </label>
                  <input
                    type="file"
                    accept=".sql,.db,.backup"
                    onChange={handleFileSelect}
                    className="input w-full"
                  />
                </div>

                {selectedFile && (
                  <div className="p-4 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    <div className="flex items-center space-x-3">
                      <DocumentIcon className="h-8 w-8 text-blue-500" />
                      <div>
                        <p className="font-medium text-gray-900 dark:text-white">{selectedFile.name}</p>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          {formatFileSize(selectedFile.size)}
                        </p>
                      </div>
                    </div>
                  </div>
                )}

                <div className="flex space-x-3">
                  <button
                    onClick={handleRestore}
                    disabled={!selectedFile || isUploading}
                    className="btn btn-primary"
                  >
                    {isUploading ? (
                      <>
                        <div className="loading-spinner mr-2"></div>
                        Restoring...
                      </>
                    ) : (
                      <>
                        <ArrowUpTrayIcon className="h-4 w-4 mr-2" />
                        Restore Backup
                      </>
                    )}
                  </button>
                  <button
                    onClick={() => setSelectedFile(null)}
                    disabled={!selectedFile}
                    className="btn btn-outline"
                  >
                    Clear
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      )}

      {activeTab === 'history' && (
        <div className="space-y-6">
          <div className="card">
            <div className="card-header">
              <h3 className="card-title">Backup History</h3>
              <p className="card-description">View and manage existing backups</p>
            </div>
            <div className="card-content">
              {backupsLoading ? (
                <div className="flex items-center justify-center py-8">
                  <div className="loading-spinner"></div>
                </div>
              ) : backups && backups.length > 0 ? (
                <div className="overflow-x-auto">
                  <table className="table">
                    <thead>
                      <tr>
                        <th>Type</th>
                        <th>Filename</th>
                        <th>Size</th>
                        <th>Created</th>
                        <th>Status</th>
                        <th>Actions</th>
                      </tr>
                    </thead>
                    <tbody>
                      {backups.map((backup: Backup) => (
                        <tr key={backup.id}>
                          <td>
                            <div className="flex items-center space-x-2">
                              {getTypeIcon(backup.type)}
                              <span className="font-medium">{backup.type}</span>
                            </div>
                          </td>
                          <td>
                            <div className="font-medium text-gray-900 dark:text-white">
                              {backup.filename}
                            </div>
                            {backup.description && (
                              <div className="text-sm text-gray-500 dark:text-gray-400">
                                {backup.description}
                              </div>
                            )}
                          </td>
                          <td>{formatFileSize(backup.size)}</td>
                          <td>
                            <div className="text-sm">
                              {new Date(backup.createdAt).toLocaleDateString()}
                            </div>
                            <div className="text-xs text-gray-500 dark:text-gray-400">
                              {new Date(backup.createdAt).toLocaleTimeString()}
                            </div>
                          </td>
                          <td>
                            <div className="flex items-center space-x-2">
                              {getStatusIcon(backup.status)}
                              <span className={`badge ${getStatusColor(backup.status)}`}>
                                {backup.status}
                              </span>
                            </div>
                          </td>
                          <td>
                            <div className="flex items-center space-x-2">
                              <button
                                onClick={() => downloadBackupMutation.mutate(backup.id)}
                                disabled={downloadBackupMutation.isLoading}
                                className="btn btn-ghost btn-sm"
                              >
                                <ArrowDownTrayIcon className="h-4 w-4" />
                              </button>
                              <button
                                onClick={() => deleteBackupMutation.mutate(backup.id)}
                                disabled={deleteBackupMutation.isLoading}
                                className="btn btn-ghost btn-sm text-red-600 hover:text-red-700"
                              >
                                <TrashIcon className="h-4 w-4" />
                              </button>
                            </div>
                          </td>
                        </tr>
                      ))}
                    </tbody>
                  </table>
                </div>
              ) : (
                <div className="text-center py-8 text-gray-500 dark:text-gray-400">
                  <CalendarIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                  <p>No backups found</p>
                  <p className="text-sm mt-2">Create your first backup to get started</p>
                </div>
              )}
            </div>
          </div>
        </div>
      )}
    </div>
  )
}

export default BackupManager