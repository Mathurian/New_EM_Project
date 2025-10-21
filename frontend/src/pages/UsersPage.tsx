import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { usersAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  UsersIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  UserPlusIcon,
  KeyIcon,
  ShieldCheckIcon,
  ExclamationTriangleIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface User {
  id: string
  name: string
  preferredName?: string
  email: string
  role: string
  isActive: boolean
  createdAt: string
  updatedAt: string
  lastLoginAt?: string
  judge?: {
    id: string
    certifications: any[]
  }
  contestant?: {
    id: string
    contestantNumber?: string
  }
}

const UsersPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [roleFilter, setRoleFilter] = useState<string>('ALL')
  const [statusFilter, setStatusFilter] = useState<string>('ALL')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [editingUser, setEditingUser] = useState<User | null>(null)
  const [showDeleteModal, setShowDeleteModal] = useState<User | null>(null)
  const [showPasswordModal, setShowPasswordModal] = useState<User | null>(null)

  const { data: users, isLoading } = useQuery(
    'users',
    () => usersAPI.getAll().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const createMutation = useMutation(
    (userData: Partial<User>) => usersAPI.create(userData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('users')
        setShowCreateModal(false)
      },
    }
  )

  const updateMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<User> }) =>
      usersAPI.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('users')
        setEditingUser(null)
      },
    }
  )

  const deleteMutation = useMutation(
    (id: string) => usersAPI.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('users')
        setShowDeleteModal(null)
      },
    }
  )

  const filteredUsers = users?.filter((user: User) => {
    const matchesSearch = user.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         user.email.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         (user.preferredName && user.preferredName.toLowerCase().includes(searchTerm.toLowerCase()))
    const matchesRole = roleFilter === 'ALL' || user.role === roleFilter
    const matchesStatus = statusFilter === 'ALL' || 
                         (statusFilter === 'ACTIVE' && user.isActive) ||
                         (statusFilter === 'INACTIVE' && !user.isActive)
    return matchesSearch && matchesRole && matchesStatus
  }) || []

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

  const getStatusColor = (isActive: boolean) => {
    return isActive ? 'status-online' : 'status-offline'
  }

  const getStatusText = (isActive: boolean) => {
    return isActive ? 'Active' : 'Inactive'
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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Users</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Manage system users and their roles
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Add User
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
                  placeholder="Search users..."
                  value={searchTerm}
                  onChange={(e) => setSearchTerm(e.target.value)}
                  className="input pl-10"
                />
              </div>
            </div>
            <div>
              <select
                value={roleFilter}
                onChange={(e) => setRoleFilter(e.target.value)}
                className="input"
              >
                <option value="ALL">All Roles</option>
                <option value="ORGANIZER">Organizer</option>
                <option value="BOARD">Board</option>
                <option value="JUDGE">Judge</option>
                <option value="CONTESTANT">Contestant</option>
                <option value="EMCEE">Emcee</option>
                <option value="TALLY_MASTER">Tally Master</option>
                <option value="AUDITOR">Auditor</option>
              </select>
            </div>
            <div>
              <select
                value={statusFilter}
                onChange={(e) => setStatusFilter(e.target.value)}
                className="input"
              >
                <option value="ALL">All Status</option>
                <option value="ACTIVE">Active</option>
                <option value="INACTIVE">Inactive</option>
              </select>
            </div>
          </div>
        </div>
      </div>

      {/* Users Table */}
      {filteredUsers.length === 0 ? (
        <div className="card">
          <div className="card-content text-center py-12">
            <UsersIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              No users found
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              {searchTerm || roleFilter !== 'ALL' || statusFilter !== 'ALL'
                ? 'Try adjusting your search criteria'
                : 'Get started by adding your first user'}
            </p>
            {!searchTerm && roleFilter === 'ALL' && statusFilter === 'ALL' && (
              <button
                onClick={() => setShowCreateModal(true)}
                className="btn btn-primary"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Add User
              </button>
            )}
          </div>
        </div>
      ) : (
        <div className="card">
          <div className="card-content p-0">
            <div className="overflow-x-auto">
              <table className="table">
                <thead className="table-header">
                  <tr>
                    <th className="table-head">User</th>
                    <th className="table-head">Role</th>
                    <th className="table-head">Status</th>
                    <th className="table-head">Last Login</th>
                    <th className="table-head">Created</th>
                    <th className="table-head">Actions</th>
                  </tr>
                </thead>
                <tbody className="table-body">
                  {filteredUsers.map((user: User) => (
                    <tr key={user.id} className="table-row">
                      <td className="table-cell">
                        <div className="flex items-center space-x-3">
                          <div className="w-10 h-10 bg-primary rounded-full flex items-center justify-center text-white font-medium">
                            {user.name.charAt(0).toUpperCase()}
                          </div>
                          <div>
                            <div className="font-medium text-gray-900 dark:text-white">
                              {user.preferredName || user.name}
                            </div>
                            <div className="text-sm text-gray-600 dark:text-gray-400">
                              {user.email}
                            </div>
                          </div>
                        </div>
                      </td>
                      <td className="table-cell">
                        <span className={`role-badge ${getRoleColor(user.role)}`}>
                          {getRoleDisplayName(user.role)}
                        </span>
                      </td>
                      <td className="table-cell">
                        <span className={`status-indicator ${getStatusColor(user.isActive)}`}>
                          {getStatusText(user.isActive)}
                        </span>
                      </td>
                      <td className="table-cell">
                        <div className="text-sm text-gray-600 dark:text-gray-400">
                          {user.lastLoginAt
                            ? format(new Date(user.lastLoginAt), 'MMM dd, yyyy HH:mm')
                            : 'Never'}
                        </div>
                      </td>
                      <td className="table-cell">
                        <div className="text-sm text-gray-600 dark:text-gray-400">
                          {format(new Date(user.createdAt), 'MMM dd, yyyy')}
                        </div>
                      </td>
                      <td className="table-cell">
                        <div className="flex items-center space-x-2">
                          <button
                            onClick={() => setEditingUser(user)}
                            className="btn btn-outline btn-sm"
                            title="Edit user"
                          >
                            <PencilIcon className="h-4 w-4" />
                          </button>
                          <button
                            onClick={() => setShowPasswordModal(user)}
                            className="btn btn-outline btn-sm"
                            title="Reset password"
                          >
                            <KeyIcon className="h-4 w-4" />
                          </button>
                          <button
                            onClick={() => setShowDeleteModal(user)}
                            className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                            title="Delete user"
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
          </div>
        </div>
      )}

      {/* Create User Modal */}
      {showCreateModal && (
        <UserModal
          user={null}
          onClose={() => setShowCreateModal(false)}
          onSave={(data) => createMutation.mutate(data)}
          isLoading={createMutation.isLoading}
        />
      )}

      {/* Edit User Modal */}
      {editingUser && (
        <UserModal
          user={editingUser}
          onClose={() => setEditingUser(null)}
          onSave={(data) => updateMutation.mutate({ id: editingUser.id, data })}
          isLoading={updateMutation.isLoading}
        />
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <DeleteModal
          user={showDeleteModal}
          onClose={() => setShowDeleteModal(null)}
          onConfirm={() => deleteMutation.mutate(showDeleteModal.id)}
          isLoading={deleteMutation.isLoading}
        />
      )}

      {/* Password Reset Modal */}
      {showPasswordModal && (
        <PasswordModal
          user={showPasswordModal}
          onClose={() => setShowPasswordModal(null)}
          onReset={(data) => {
            // Handle password reset
            setShowPasswordModal(null)
          }}
          isLoading={false}
        />
      )}
    </div>
  )
}

// User Modal Component
interface UserModalProps {
  user: User | null
  onClose: () => void
  onSave: (data: Partial<User>) => void
  isLoading: boolean
}

const UserModal: React.FC<UserModalProps> = ({ user, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    name: user?.name || '',
    preferredName: user?.preferredName || '',
    email: user?.email || '',
    role: user?.role || 'CONTESTANT',
    isActive: user?.isActive ?? true,
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
          {user ? 'Edit User' : 'Create User'}
        </h2>
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
              <label className="label">Preferred Name (Optional)</label>
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
              <label className="label">Email</label>
              <input
                type="email"
                value={formData.email}
                onChange={(e) => setFormData({ ...formData, email: e.target.value })}
                className="input"
                required
              />
            </div>
            <div>
              <label className="label">Role</label>
              <select
                value={formData.role}
                onChange={(e) => setFormData({ ...formData, role: e.target.value })}
                className="input"
                required
              >
                <option value="CONTESTANT">Contestant</option>
                <option value="JUDGE">Judge</option>
                <option value="EMCEE">Emcee</option>
                <option value="TALLY_MASTER">Tally Master</option>
                <option value="AUDITOR">Auditor</option>
                <option value="BOARD">Board</option>
                <option value="ORGANIZER">Organizer</option>
              </select>
            </div>
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
              Active user
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
              {isLoading ? 'Saving...' : user ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Delete Confirmation Modal
interface DeleteModalProps {
  user: User
  onClose: () => void
  onConfirm: () => void
  isLoading: boolean
}

const DeleteModal: React.FC<DeleteModalProps> = ({ user, onClose, onConfirm, isLoading }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Delete User</h2>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Are you sure you want to delete "{user.preferredName || user.name}"? This action cannot be undone.
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

// Password Reset Modal
interface PasswordModalProps {
  user: User
  onClose: () => void
  onReset: (data: any) => void
  isLoading: boolean
}

const PasswordModal: React.FC<PasswordModalProps> = ({ user, onClose, onReset, isLoading }) => {
  const [newPassword, setNewPassword] = useState('')
  const [confirmPassword, setConfirmPassword] = useState('')

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    if (newPassword === confirmPassword) {
      onReset({ userId: user.id, newPassword })
    }
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Reset Password</h2>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Reset password for "{user.preferredName || user.name}"
        </p>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div>
            <label className="label">New Password</label>
            <input
              type="password"
              value={newPassword}
              onChange={(e) => setNewPassword(e.target.value)}
              className="input"
              required
              minLength={8}
            />
          </div>
          <div>
            <label className="label">Confirm Password</label>
            <input
              type="password"
              value={confirmPassword}
              onChange={(e) => setConfirmPassword(e.target.value)}
              className="input"
              required
              minLength={8}
            />
          </div>
          {newPassword && confirmPassword && newPassword !== confirmPassword && (
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
              disabled={isLoading || newPassword !== confirmPassword}
            >
              {isLoading ? 'Resetting...' : 'Reset Password'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

export default UsersPage
