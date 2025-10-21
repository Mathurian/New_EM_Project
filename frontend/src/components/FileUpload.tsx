import React, { useState, useRef, useCallback } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { uploadAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  CloudArrowUpIcon,
  DocumentIcon,
  PhotoIcon,
  TrashIcon,
  EyeIcon,
  ArrowDownTrayIcon,
  ExclamationTriangleIcon,
  CheckCircleIcon,
  ClockIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  UserIcon,
  CalendarIcon,
  TrophyIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface UploadedFile {
  id: string
  filename: string
  originalName: string
  mimeType: string
  size: number
  path: string
  uploadedBy: string
  uploadedAt: string
  category: 'CONTESTANT_IMAGE' | 'JUDGE_IMAGE' | 'DOCUMENT' | 'TEMPLATE' | 'OTHER'
  contestantId?: string
  judgeId?: string
  contestId?: string
  categoryId?: string
  isPublic: boolean
  metadata: Record<string, any>
}

interface UploadProgress {
  file: File
  progress: number
  status: 'uploading' | 'completed' | 'error'
  error?: string
}

const FileUpload: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const fileInputRef = useRef<HTMLInputElement>(null)
  const [activeTab, setActiveTab] = useState<'upload' | 'manage' | 'browse'>('upload')
  const [dragActive, setDragActive] = useState(false)
  const [uploads, setUploads] = useState<UploadProgress[]>([])
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState<string>('ALL')
  const [userFilter, setUserFilter] = useState<string>('ALL')

  // Fetch uploaded files
  const { data: files, isLoading: filesLoading } = useQuery(
    'uploaded-files',
    () => uploadAPI.getFiles().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'JUDGE',
    }
  )

  // Fetch users for filtering
  const { data: users } = useQuery(
    'users-for-upload',
    () => api.get('/users').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const uploadMutation = useMutation(
    (fileData: FormData) => uploadAPI.uploadFile(fileData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('uploaded-files')
      },
    }
  )

  const deleteFileMutation = useMutation(
    (fileId: string) => uploadAPI.deleteFile(fileId),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('uploaded-files')
      },
    }
  )

  const handleDrag = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    if (e.type === 'dragenter' || e.type === 'dragover') {
      setDragActive(true)
    } else if (e.type === 'dragleave') {
      setDragActive(false)
    }
  }, [])

  const handleDrop = useCallback((e: React.DragEvent) => {
    e.preventDefault()
    e.stopPropagation()
    setDragActive(false)
    
    if (e.dataTransfer.files && e.dataTransfer.files[0]) {
      handleFiles(Array.from(e.dataTransfer.files))
    }
  }, [])

  const handleFileInput = (e: React.ChangeEvent<HTMLInputElement>) => {
    if (e.target.files) {
      handleFiles(Array.from(e.target.files))
    }
  }

  const handleFiles = async (files: File[]) => {
    const newUploads: UploadProgress[] = files.map(file => ({
      file,
      progress: 0,
      status: 'uploading',
    }))
    
    setUploads(prev => [...prev, ...newUploads])

    for (let i = 0; i < files.length; i++) {
      const file = files[i]
      const formData = new FormData()
      formData.append('file', file)
      formData.append('category', 'OTHER')
      formData.append('isPublic', 'false')

      try {
        await uploadMutation.mutateAsync(formData)
        setUploads(prev => 
          prev.map(upload => 
            upload.file === file 
              ? { ...upload, progress: 100, status: 'completed' }
              : upload
          )
        )
      } catch (error) {
        setUploads(prev => 
          prev.map(upload => 
            upload.file === file 
              ? { 
                  ...upload, 
                  progress: 0, 
                  status: 'error',
                  error: error instanceof Error ? error.message : 'Upload failed'
                }
              : upload
          )
        )
      }
    }
  }

  const filteredFiles = files?.filter((file: UploadedFile) => {
    const matchesSearch = file.originalName.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         file.filename.toLowerCase().includes(searchTerm.toLowerCase())
    const matchesCategory = categoryFilter === 'ALL' || file.category === categoryFilter
    const matchesUser = userFilter === 'ALL' || file.uploadedBy === userFilter
    return matchesSearch && matchesCategory && matchesUser
  }) || []

  const getFileIcon = (mimeType: string) => {
    if (mimeType.startsWith('image/')) {
      return <PhotoIcon className="h-8 w-8 text-blue-500" />
    }
    return <DocumentIcon className="h-8 w-8 text-gray-500" />
  }

  const getCategoryIcon = (category: string) => {
    switch (category) {
      case 'CONTESTANT_IMAGE': return <UserIcon className="h-4 w-4" />
      case 'JUDGE_IMAGE': return <UserIcon className="h-4 w-4" />
      case 'DOCUMENT': return <DocumentIcon className="h-4 w-4" />
      case 'TEMPLATE': return <TrophyIcon className="h-4 w-4" />
      default: return <DocumentIcon className="h-4 w-4" />
    }
  }

  const formatFileSize = (bytes: number) => {
    if (bytes === 0) return '0 Bytes'
    const k = 1024
    const sizes = ['Bytes', 'KB', 'MB', 'GB']
    const i = Math.floor(Math.log(bytes) / Math.log(k))
    return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
  }

  const tabs = [
    { id: 'upload', name: 'Upload Files', icon: CloudArrowUpIcon },
    { id: 'manage', name: 'Manage Files', icon: DocumentIcon },
    { id: 'browse', name: 'Browse Files', icon: EyeIcon },
  ]

  return (
    <div className="space-y-6">
      {/* Header */}
      <div className="flex flex-col sm:flex-row sm:items-center sm:justify-between">
        <div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">File Upload</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Upload and manage files for contestants, judges, and documents
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
          {activeTab === 'upload' && (
            <UploadTab
              dragActive={dragActive}
              onDrag={handleDrag}
              onDrop={handleDrop}
              onFileInput={handleFileInput}
              uploads={uploads}
              fileInputRef={fileInputRef}
            />
          )}

          {activeTab === 'manage' && (
            <ManageTab
              files={filteredFiles}
              isLoading={filesLoading}
              onDelete={(id) => deleteFileMutation.mutate(id)}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
              categoryFilter={categoryFilter}
              onCategoryFilterChange={setCategoryFilter}
              userFilter={userFilter}
              onUserFilterChange={setUserFilter}
              users={users || []}
            />
          )}

          {activeTab === 'browse' && (
            <BrowseTab
              files={filteredFiles}
              isLoading={filesLoading}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
              categoryFilter={categoryFilter}
              onCategoryFilterChange={setCategoryFilter}
            />
          )}
        </div>
      </div>
    </div>
  )
}

// Upload Tab Component
interface UploadTabProps {
  dragActive: boolean
  onDrag: (e: React.DragEvent) => void
  onDrop: (e: React.DragEvent) => void
  onFileInput: (e: React.ChangeEvent<HTMLInputElement>) => void
  uploads: UploadProgress[]
  fileInputRef: React.RefObject<HTMLInputElement>
}

const UploadTab: React.FC<UploadTabProps> = ({
  dragActive,
  onDrag,
  onDrop,
  onFileInput,
  uploads,
  fileInputRef,
}) => {
  const [uploadCategory, setUploadCategory] = useState('OTHER')
  const [isPublic, setIsPublic] = useState(false)
  const [contestantId, setContestantId] = useState('')
  const [judgeId, setJudgeId] = useState('')
  const [contestId, setContestId] = useState('')
  const [categoryId, setCategoryId] = useState('')

  return (
    <div className="space-y-6">
      <h3 className="text-lg font-medium text-gray-900 dark:text-white">Upload Files</h3>
      
      {/* Upload Area */}
      <div
        className={`border-2 border-dashed rounded-lg p-8 text-center transition-colors ${
          dragActive
            ? 'border-primary bg-primary/5'
            : 'border-gray-300 dark:border-gray-600 hover:border-gray-400 dark:hover:border-gray-500'
        }`}
        onDragEnter={onDrag}
        onDragLeave={onDrag}
        onDragOver={onDrag}
        onDrop={onDrop}
      >
        <CloudArrowUpIcon className="h-12 w-12 mx-auto text-gray-400 mb-4" />
        <h4 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
          Drop files here or click to browse
        </h4>
        <p className="text-gray-600 dark:text-gray-400 mb-4">
          Support for images, documents, and other file types
        </p>
        <button
          onClick={() => fileInputRef.current?.click()}
          className="btn btn-primary"
        >
          Choose Files
        </button>
        <input
          ref={fileInputRef}
          type="file"
          multiple
          onChange={onFileInput}
          className="hidden"
          accept="image/*,.pdf,.doc,.docx,.txt,.csv,.xlsx,.xls"
        />
      </div>

      {/* Upload Options */}
      <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
        <div>
          <label className="label">Category</label>
          <select
            value={uploadCategory}
            onChange={(e) => setUploadCategory(e.target.value)}
            className="input"
          >
            <option value="OTHER">Other</option>
            <option value="CONTESTANT_IMAGE">Contestant Image</option>
            <option value="JUDGE_IMAGE">Judge Image</option>
            <option value="DOCUMENT">Document</option>
            <option value="TEMPLATE">Template</option>
          </select>
        </div>
        <div>
          <label className="label">Visibility</label>
          <select
            value={isPublic ? 'public' : 'private'}
            onChange={(e) => setIsPublic(e.target.value === 'public')}
            className="input"
          >
            <option value="private">Private</option>
            <option value="public">Public</option>
          </select>
        </div>
      </div>

      {/* Context Selection */}
      {uploadCategory === 'CONTESTANT_IMAGE' && (
        <div>
          <label className="label">Contestant</label>
          <select
            value={contestantId}
            onChange={(e) => setContestantId(e.target.value)}
            className="input"
          >
            <option value="">Select contestant</option>
            {/* Contestants would be loaded here */}
          </select>
        </div>
      )}

      {uploadCategory === 'JUDGE_IMAGE' && (
        <div>
          <label className="label">Judge</label>
          <select
            value={judgeId}
            onChange={(e) => setJudgeId(e.target.value)}
            className="input"
          >
            <option value="">Select judge</option>
            {/* Judges would be loaded here */}
          </select>
        </div>
      )}

      {/* Upload Progress */}
      {uploads.length > 0 && (
        <div className="space-y-4">
          <h4 className="font-medium text-gray-900 dark:text-white">Upload Progress</h4>
          <div className="space-y-3">
            {uploads.map((upload, index) => (
              <div key={index} className="flex items-center space-x-4 p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                <div className="flex-shrink-0">
                  {upload.status === 'completed' ? (
                    <CheckCircleIcon className="h-6 w-6 text-green-500" />
                  ) : upload.status === 'error' ? (
                    <ExclamationTriangleIcon className="h-6 w-6 text-red-500" />
                  ) : (
                    <ClockIcon className="h-6 w-6 text-yellow-500" />
                  )}
                </div>
                <div className="flex-1 min-w-0">
                  <p className="text-sm font-medium text-gray-900 dark:text-white truncate">
                    {upload.file.name}
                  </p>
                  <p className="text-xs text-gray-600 dark:text-gray-400">
                    {(upload.file.size / 1024 / 1024).toFixed(2)} MB
                  </p>
                </div>
                <div className="flex-shrink-0">
                  {upload.status === 'uploading' && (
                    <div className="w-16 bg-gray-200 dark:bg-gray-600 rounded-full h-2">
                      <div
                        className="bg-primary h-2 rounded-full transition-all duration-300"
                        style={{ width: `${upload.progress}%` }}
                      />
                    </div>
                  )}
                  {upload.status === 'completed' && (
                    <span className="text-sm text-green-600 dark:text-green-400">Completed</span>
                  )}
                  {upload.status === 'error' && (
                    <span className="text-sm text-red-600 dark:text-red-400">
                      {upload.error || 'Failed'}
                    </span>
                  )}
                </div>
              </div>
            ))}
          </div>
        </div>
      )}
    </div>
  )
}

// Manage Tab Component
interface ManageTabProps {
  files: UploadedFile[]
  isLoading: boolean
  onDelete: (id: string) => void
  searchTerm: string
  onSearchChange: (term: string) => void
  categoryFilter: string
  onCategoryFilterChange: (category: string) => void
  userFilter: string
  onUserFilterChange: (user: string) => void
  users: any[]
}

const ManageTab: React.FC<ManageTabProps> = ({
  files,
  isLoading,
  onDelete,
  searchTerm,
  onSearchChange,
  categoryFilter,
  onCategoryFilterChange,
  userFilter,
  onUserFilterChange,
  users,
}) => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Manage Files</h3>
        <div className="flex space-x-2">
          <div className="relative">
            <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search files..."
              value={searchTerm}
              onChange={(e) => onSearchChange(e.target.value)}
              className="input pl-10"
            />
          </div>
          <select
            value={categoryFilter}
            onChange={(e) => onCategoryFilterChange(e.target.value)}
            className="input"
          >
            <option value="ALL">All Categories</option>
            <option value="CONTESTANT_IMAGE">Contestant Images</option>
            <option value="JUDGE_IMAGE">Judge Images</option>
            <option value="DOCUMENT">Documents</option>
            <option value="TEMPLATE">Templates</option>
            <option value="OTHER">Other</option>
          </select>
          <select
            value={userFilter}
            onChange={(e) => onUserFilterChange(e.target.value)}
            className="input"
          >
            <option value="ALL">All Users</option>
            {users.map((user) => (
              <option key={user.id} value={user.id}>
                {user.name}
              </option>
            ))}
          </select>
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : files.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <DocumentIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No files found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm || categoryFilter !== 'ALL' || userFilter !== 'ALL' 
              ? 'Try adjusting your search criteria' 
              : 'No files have been uploaded yet'}
          </p>
        </div>
      ) : (
        <div className="grid-responsive">
          {files.map((file) => (
            <div key={file.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex items-center space-x-3">
                    {getFileIcon(file.mimeType)}
                    <div className="flex-1 min-w-0">
                      <h3 className="card-title text-lg truncate">{file.originalName}</h3>
                      <p className="card-description">{formatFileSize(file.size)}</p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className={`badge ${file.isPublic ? 'badge-success' : 'badge-secondary'}`}>
                      {file.isPublic ? 'Public' : 'Private'}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  {getCategoryIcon(file.category)}
                  <span className="ml-2">{file.category.replace('_', ' ')}</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <UserIcon className="h-4 w-4" />
                  <span className="ml-2">{file.uploadedBy}</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <CalendarIcon className="h-4 w-4" />
                  <span className="ml-2">{format(new Date(file.uploadedAt), 'MMM dd, yyyy')}</span>
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button className="btn btn-outline btn-sm">
                      <EyeIcon className="h-4 w-4" />
                    </button>
                    <button className="btn btn-outline btn-sm">
                      <ArrowDownTrayIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => onDelete(file.id)}
                      className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </div>
                </div>
              </div>
            </div>
          ))}
        </div>
      )}
    </div>
  )
}

// Browse Tab Component
interface BrowseTabProps {
  files: UploadedFile[]
  isLoading: boolean
  searchTerm: string
  onSearchChange: (term: string) => void
  categoryFilter: string
  onCategoryFilterChange: (category: string) => void
}

const BrowseTab: React.FC<BrowseTabProps> = ({
  files,
  isLoading,
  searchTerm,
  onSearchChange,
  categoryFilter,
  onCategoryFilterChange,
}) => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Browse Files</h3>
        <div className="flex space-x-2">
          <div className="relative">
            <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search files..."
              value={searchTerm}
              onChange={(e) => onSearchChange(e.target.value)}
              className="input pl-10"
            />
          </div>
          <select
            value={categoryFilter}
            onChange={(e) => onCategoryFilterChange(e.target.value)}
            className="input"
          >
            <option value="ALL">All Categories</option>
            <option value="CONTESTANT_IMAGE">Contestant Images</option>
            <option value="JUDGE_IMAGE">Judge Images</option>
            <option value="DOCUMENT">Documents</option>
            <option value="TEMPLATE">Templates</option>
            <option value="OTHER">Other</option>
          </select>
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : files.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <EyeIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No files found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm || categoryFilter !== 'ALL' 
              ? 'Try adjusting your search criteria' 
              : 'No files are available for browsing'}
          </p>
        </div>
      ) : (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
          {files.map((file) => (
            <div key={file.id} className="card">
              <div className="card-content">
                <div className="flex items-center justify-center mb-4">
                  {getFileIcon(file.mimeType)}
                </div>
                <h3 className="card-title text-sm truncate mb-2">{file.originalName}</h3>
                <p className="text-xs text-gray-600 dark:text-gray-400 mb-2">
                  {formatFileSize(file.size)}
                </p>
                <div className="flex items-center text-xs text-gray-600 dark:text-gray-400 mb-3">
                  {getCategoryIcon(file.category)}
                  <span className="ml-1">{file.category.replace('_', ' ')}</span>
                </div>
                <div className="flex items-center justify-between">
                  <button className="btn btn-outline btn-sm">
                    <EyeIcon className="h-4 w-4" />
                  </button>
                  <button className="btn btn-outline btn-sm">
                    <ArrowDownTrayIcon className="h-4 w-4" />
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

export default FileUpload
