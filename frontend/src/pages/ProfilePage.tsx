import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { api } from '../services/api'
import {
  UserIcon,
  PencilIcon,
  KeyIcon,
  BellIcon,
  ShieldCheckIcon,
  CameraIcon,
  CheckCircleIcon,
  ExclamationTriangleIcon,
  InformationCircleIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface UserProfile {
  id: string
  name: string
  preferredName?: string
  email: string
  role: string
  isActive: boolean
  createdAt: string
  updatedAt: string
  lastLoginAt?: string
  avatar?: string
  bio?: string
  phone?: string
  address?: string
  timezone?: string
  language?: string
  notifications?: {
    email: boolean
    push: boolean
    sms: boolean
  }
  privacy?: {
    showEmail: boolean
    showPhone: boolean
    showAddress: boolean
  }
}

const ProfilePage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'profile' | 'security' | 'notifications' | 'privacy'>('profile')
  const [showEditModal, setShowEditModal] = useState(false)
  const [showPasswordModal, setShowPasswordModal] = useState(false)
  const [showAvatarModal, setShowAvatarModal] = useState(false)

  const { data: profile, isLoading } = useQuery(
    'user-profile',
    () => api.get('/auth/profile').then(res => res.data),
    {
      enabled: !!user,
    }
  )

  const updateProfileMutation = useMutation(
    (data: Partial<UserProfile>) => api.put('/auth/profile', data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('user-profile')
        setShowEditModal(false)
      },
    }
  )

  const changePasswordMutation = useMutation(
    (data: { currentPassword: string; newPassword: string; confirmPassword: string }) =>
      api.put('/auth/change-password', data),
    {
      onSuccess: () => {
        setShowPasswordModal(false)
      },
    }
  )

  const uploadAvatarMutation = useMutation(
    (file: File) => {
      const formData = new FormData()
      formData.append('avatar', file)
      return api.post('/auth/upload-avatar', formData, {
        headers: { 'Content-Type': 'multipart/form-data' },
      })
    },
    {
      onSuccess: () => {
        queryClient.invalidateQueries('user-profile')
        setShowAvatarModal(false)
      },
    }
  )

  const tabs = [
    { id: 'profile', name: 'Profile', icon: UserIcon },
    { id: 'security', name: 'Security', icon: ShieldCheckIcon },
    { id: 'notifications', name: 'Notifications', icon: BellIcon },
    { id: 'privacy', name: 'Privacy', icon: InformationCircleIcon },
  ]

  const getRoleColor = (role: string) => {
    switch (role) {
      case 'ORGANIZER': return 'role-organizer'
      case 'BOARD': return 'role-board'
      case 'JUDGE': return 'role-judge'
      case 'CONTESTANT': return 'role-contestant'
      case 'EMCEE': return 'role-emcee'
      case 'TALLY_MASTER': return 'role-tally-master'
      case 'AUDITOR': return 'role-auditor'
      default: return 'role-board'
    }
  }

  const getRoleDisplayName = (role: string) => {
    switch (role) {
      case 'ORGANIZER': return 'Organizer'
      case 'BOARD': return 'Board'
      case 'JUDGE': return 'Judge'
      case 'CONTESTANT': return 'Contestant'
      case 'EMCEE': return 'Emcee'
      case 'TALLY_MASTER': return 'Tally Master'
      case 'AUDITOR': return 'Auditor'
      default: return role
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">My Profile</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Manage your personal information and preferences
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowEditModal(true)}
            className="btn btn-primary"
          >
            <PencilIcon className="h-5 w-5 mr-2" />
            Edit Profile
          </button>
        </div>
      </div>

      {/* Profile Header */}
      <div className="card">
        <div className="card-content">
          <div className="flex items-center space-x-6">
            <div className="relative">
              <div className="w-24 h-24 bg-primary rounded-full flex items-center justify-center text-white text-2xl font-bold">
                {profile?.avatar ? (
                  <img
                    src={profile.avatar}
                    alt={profile.name}
                    className="w-24 h-24 rounded-full object-cover"
                  />
                ) : (
                  profile?.name?.charAt(0).toUpperCase()
                )}
              </div>
              <button
                onClick={() => setShowAvatarModal(true)}
                className="absolute bottom-0 right-0 w-8 h-8 bg-primary rounded-full flex items-center justify-center text-white hover:bg-primary-dark transition-colors"
              >
                <CameraIcon className="h-4 w-4" />
              </button>
            </div>
            <div className="flex-1">
              <h2 className="text-2xl font-bold text-gray-900 dark:text-white">
                {profile?.preferredName || profile?.name}
              </h2>
              <p className="text-gray-600 dark:text-gray-400">{profile?.email}</p>
              <div className="flex items-center space-x-4 mt-2">
                <span className={`role-badge ${getRoleColor(profile?.role || '')}`}>
                  {getRoleDisplayName(profile?.role || '')}
                </span>
                <span className={`status-indicator ${profile?.isActive ? 'status-online' : 'status-offline'}`}>
                  {profile?.isActive ? 'Active' : 'Inactive'}
                </span>
              </div>
            </div>
          </div>
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
          {activeTab === 'profile' && (
            <div className="space-y-6">
              <h3 className="text-lg font-medium">Personal Information</h3>
              <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                  <label className="label">Full Name</label>
                  <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    {profile?.name || 'Not set'}
                  </div>
                </div>
                <div>
                  <label className="label">Preferred Name</label>
                  <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    {profile?.preferredName || 'Not set'}
                  </div>
                </div>
                <div>
                  <label className="label">Email</label>
                  <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    {profile?.email}
                  </div>
                </div>
                <div>
                  <label className="label">Phone</label>
                  <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    {profile?.phone || 'Not set'}
                  </div>
                </div>
                <div className="md:col-span-2">
                  <label className="label">Bio</label>
                  <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg min-h-[100px]">
                    {profile?.bio || 'No bio available'}
                  </div>
                </div>
                <div>
                  <label className="label">Member Since</label>
                  <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    {profile?.createdAt ? format(new Date(profile.createdAt), 'MMM dd, yyyy') : 'Unknown'}
                  </div>
                </div>
                <div>
                  <label className="label">Last Login</label>
                  <div className="p-3 bg-gray-50 dark:bg-gray-700 rounded-lg">
                    {profile?.lastLoginAt ? format(new Date(profile.lastLoginAt), 'MMM dd, yyyy HH:mm') : 'Never'}
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'security' && (
            <div className="space-y-6">
              <h3 className="text-lg font-medium">Security Settings</h3>
              <div className="space-y-4">
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Password</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Last changed: {profile?.updatedAt ? format(new Date(profile.updatedAt), 'MMM dd, yyyy') : 'Unknown'}
                        </p>
                      </div>
                      <button
                        onClick={() => setShowPasswordModal(true)}
                        className="btn btn-outline btn-sm"
                      >
                        <KeyIcon className="h-4 w-4 mr-2" />
                        Change Password
                      </button>
                    </div>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Two-Factor Authentication</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Add an extra layer of security to your account
                        </p>
                      </div>
                      <button className="btn btn-outline btn-sm">
                        <ShieldCheckIcon className="h-4 w-4 mr-2" />
                        Enable 2FA
                      </button>
                    </div>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Login Sessions</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Manage your active login sessions
                        </p>
                      </div>
                      <button className="btn btn-outline btn-sm">
                        <UserIcon className="h-4 w-4 mr-2" />
                        View Sessions
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'notifications' && (
            <div className="space-y-6">
              <h3 className="text-lg font-medium">Notification Preferences</h3>
              <div className="space-y-4">
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Email Notifications</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Receive notifications via email
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={profile?.notifications?.email ?? true}
                          onChange={(e) => {
                            // Handle notification toggle
                          }}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {profile?.notifications?.email ? 'Enabled' : 'Disabled'}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Push Notifications</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Receive push notifications in your browser
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={profile?.notifications?.push ?? true}
                          onChange={(e) => {
                            // Handle notification toggle
                          }}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {profile?.notifications?.push ? 'Enabled' : 'Disabled'}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">SMS Notifications</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Receive notifications via SMS
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={profile?.notifications?.sms ?? false}
                          onChange={(e) => {
                            // Handle notification toggle
                          }}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {profile?.notifications?.sms ? 'Enabled' : 'Disabled'}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}

          {activeTab === 'privacy' && (
            <div className="space-y-6">
              <h3 className="text-lg font-medium">Privacy Settings</h3>
              <div className="space-y-4">
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Show Email</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Allow other users to see your email address
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={profile?.privacy?.showEmail ?? false}
                          onChange={(e) => {
                            // Handle privacy toggle
                          }}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {profile?.privacy?.showEmail ? 'Visible' : 'Hidden'}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Show Phone</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Allow other users to see your phone number
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={profile?.privacy?.showPhone ?? false}
                          onChange={(e) => {
                            // Handle privacy toggle
                          }}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {profile?.privacy?.showPhone ? 'Visible' : 'Hidden'}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
                <div className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between">
                      <div>
                        <h4 className="font-medium text-gray-900 dark:text-white">Show Address</h4>
                        <p className="text-sm text-gray-600 dark:text-gray-400">
                          Allow other users to see your address
                        </p>
                      </div>
                      <div className="flex items-center space-x-2">
                        <input
                          type="checkbox"
                          checked={profile?.privacy?.showAddress ?? false}
                          onChange={(e) => {
                            // Handle privacy toggle
                          }}
                          className="rounded border-gray-300 text-primary focus:ring-primary"
                        />
                        <span className="text-sm text-gray-600 dark:text-gray-400">
                          {profile?.privacy?.showAddress ? 'Visible' : 'Hidden'}
                        </span>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          )}
        </div>
      </div>

      {/* Edit Profile Modal */}
      {showEditModal && (
        <EditProfileModal
          profile={profile}
          onClose={() => setShowEditModal(false)}
          onSave={(data) => updateProfileMutation.mutate(data)}
          isLoading={updateProfileMutation.isLoading}
        />
      )}

      {/* Change Password Modal */}
      {showPasswordModal && (
        <ChangePasswordModal
          onClose={() => setShowPasswordModal(false)}
          onSave={(data) => changePasswordMutation.mutate(data)}
          isLoading={changePasswordMutation.isLoading}
        />
      )}

      {/* Avatar Upload Modal */}
      {showAvatarModal && (
        <AvatarUploadModal
          onClose={() => setShowAvatarModal(false)}
          onUpload={(file) => uploadAvatarMutation.mutate(file)}
          isLoading={uploadAvatarMutation.isLoading}
        />
      )}
    </div>
  )
}

// Edit Profile Modal Component
interface EditProfileModalProps {
  profile: UserProfile | undefined
  onClose: () => void
  onSave: (data: Partial<UserProfile>) => void
  isLoading: boolean
}

const EditProfileModal: React.FC<EditProfileModalProps> = ({ profile, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    name: profile?.name || '',
    preferredName: profile?.preferredName || '',
    phone: profile?.phone || '',
    bio: profile?.bio || '',
    address: profile?.address || '',
    timezone: profile?.timezone || 'UTC',
    language: profile?.language || 'en',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSave(formData)
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-2xl">
        <h2 className="text-xl font-semibold mb-4">Edit Profile</h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Full Name</label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="input"
                required
              />
            </div>
            <div>
              <label className="label">Preferred Name</label>
              <input
                type="text"
                value={formData.preferredName}
                onChange={(e) => setFormData({ ...formData, preferredName: e.target.value })}
                className="input"
              />
            </div>
          </div>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Phone</label>
              <input
                type="tel"
                value={formData.phone}
                onChange={(e) => setFormData({ ...formData, phone: e.target.value })}
                className="input"
              />
            </div>
            <div>
              <label className="label">Timezone</label>
              <select
                value={formData.timezone}
                onChange={(e) => setFormData({ ...formData, timezone: e.target.value })}
                className="input"
              >
                <option value="UTC">UTC</option>
                <option value="America/New_York">Eastern Time</option>
                <option value="America/Chicago">Central Time</option>
                <option value="America/Denver">Mountain Time</option>
                <option value="America/Los_Angeles">Pacific Time</option>
              </select>
            </div>
          </div>
          <div>
            <label className="label">Bio</label>
            <textarea
              value={formData.bio}
              onChange={(e) => setFormData({ ...formData, bio: e.target.value })}
              className="input min-h-[100px]"
              rows={3}
              placeholder="Tell us about yourself..."
            />
          </div>
          <div>
            <label className="label">Address</label>
            <textarea
              value={formData.address}
              onChange={(e) => setFormData({ ...formData, address: e.target.value })}
              className="input min-h-[80px]"
              rows={2}
              placeholder="Your address..."
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
              {isLoading ? 'Saving...' : 'Save Changes'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Change Password Modal Component
interface ChangePasswordModalProps {
  onClose: () => void
  onSave: (data: { currentPassword: string; newPassword: string; confirmPassword: string }) => void
  isLoading: boolean
}

const ChangePasswordModal: React.FC<ChangePasswordModalProps> = ({ onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    currentPassword: '',
    newPassword: '',
    confirmPassword: '',
  })

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (formData.newPassword === formData.confirmPassword) {
      onSave(formData)
    }
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Change Password</h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="label">Current Password</label>
            <input
              type="password"
              value={formData.currentPassword}
              onChange={(e) => setFormData({ ...formData, currentPassword: e.target.value })}
              className="input"
              required
            />
          </div>
          <div>
            <label className="label">New Password</label>
            <input
              type="password"
              value={formData.newPassword}
              onChange={(e) => setFormData({ ...formData, newPassword: e.target.value })}
              className="input"
              required
              minLength={8}
            />
          </div>
          <div>
            <label className="label">Confirm New Password</label>
            <input
              type="password"
              value={formData.confirmPassword}
              onChange={(e) => setFormData({ ...formData, confirmPassword: e.target.value })}
              className="input"
              required
              minLength={8}
            />
          </div>
          {formData.newPassword && formData.confirmPassword && formData.newPassword !== formData.confirmPassword && (
            <div className="text-sm text-red-600 dark:text-red-400">
              Passwords do not match
            </div>
          )}
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
              disabled={isLoading || formData.newPassword !== formData.confirmPassword}
            >
              {isLoading ? 'Changing...' : 'Change Password'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Avatar Upload Modal Component
interface AvatarUploadModalProps {
  onClose: () => void
  onUpload: (file: File) => void
  isLoading: boolean
}

const AvatarUploadModal: React.FC<AvatarUploadModalProps> = ({ onClose, onUpload, isLoading }) => {
  const [selectedFile, setSelectedFile] = useState<File | null>(null)
  const [preview, setPreview] = useState<string | null>(null)

  const handleFileSelect = (e: React.ChangeEvent<HTMLInputElement>) => {
    const file = e.target.files?.[0]
    if (file) {
      setSelectedFile(file)
      const reader = new FileReader()
      reader.onload = (e) => {
        setPreview(e.target?.result as string)
      }
      reader.readAsDataURL(file)
    }
  }

  const handleUpload = () => {
    if (selectedFile) {
      onUpload(selectedFile)
    }
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Upload Avatar</h2>
        <div className="space-y-4">
          <div className="text-center">
            <div className="w-32 h-32 mx-auto bg-gray-100 dark:bg-gray-700 rounded-full flex items-center justify-center overflow-hidden">
              {preview ? (
                <img src={preview} alt="Preview" className="w-32 h-32 object-cover" />
              ) : (
                <CameraIcon className="h-12 w-12 text-gray-400" />
              )}
            </div>
          </div>
          <div>
            <label className="label">Select Image</label>
            <input
              type="file"
              accept="image/*"
              onChange={handleFileSelect}
              className="input"
            />
          </div>
          <div className="text-sm text-gray-600 dark:text-gray-400">
            <p>• Maximum file size: 5MB</p>
            <p>• Supported formats: JPG, PNG, GIF</p>
            <p>• Recommended size: 400x400 pixels</p>
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
              onClick={handleUpload}
              className="btn btn-primary"
              disabled={isLoading || !selectedFile}
            >
              {isLoading ? 'Uploading...' : 'Upload'}
            </button>
          </div>
        </div>
      </div>
    </div>
  )
}

export default ProfilePage
