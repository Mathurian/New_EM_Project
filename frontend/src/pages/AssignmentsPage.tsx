import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { assignmentsAPI } from '../services/api'
import {
  PlusIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon,
  CheckCircleIcon,
  ClockIcon,
  UserGroupIcon,
  TrophyIcon,
} from '@heroicons/react/24/outline'

const AssignmentsPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [selectedAssignment, setSelectedAssignment] = useState<any>(null)
  const [isEditModalOpen, setIsEditModalOpen] = useState(false)
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false)

  const { data: assignments, isLoading } = useQuery(
    'assignments',
    () => assignmentsAPI.getAll().then(res => res.data),
    {
      refetchInterval: 30000,
    }
  )

  const { data: judges } = useQuery(
    'judges',
    () => assignmentsAPI.getJudges().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const { data: categories } = useQuery(
    'categories',
    () => assignmentsAPI.getCategories().then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const createAssignmentMutation = useMutation(
    (data: any) => assignmentsAPI.create(data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('assignments')
        setIsCreateModalOpen(false)
      },
    }
  )

  const updateAssignmentMutation = useMutation(
    ({ id, data }: { id: string; data: any }) => assignmentsAPI.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('assignments')
        setIsEditModalOpen(false)
        setSelectedAssignment(null)
      },
    }
  )

  const deleteAssignmentMutation = useMutation(
    (id: string) => assignmentsAPI.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('assignments')
      },
    }
  )

  const getStatusBadge = (status: string) => {
    switch (status) {
      case 'ACTIVE':
        return <span className="badge badge-success">Active</span>
      case 'PENDING':
        return <span className="badge badge-warning">Pending</span>
      case 'COMPLETED':
        return <span className="badge badge-info">Completed</span>
      default:
        return <span className="badge badge-secondary">{status}</span>
    }
  }

  const getRoleSpecificContent = () => {
    switch (user?.role) {
      case 'ORGANIZER':
      case 'BOARD':
        return (
          <div className="space-y-6">
            {/* Header */}
            <div className="flex justify-between items-center">
              <div>
                <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Judge Assignments</h1>
                <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                  Manage judge assignments to categories
                </p>
              </div>
              <button
                onClick={() => setIsCreateModalOpen(true)}
                className="btn btn-primary"
              >
                <PlusIcon className="h-4 w-4 mr-2" />
                New Assignment
              </button>
            </div>

            {/* Assignments Table */}
            <div className="card">
              <div className="card-content">
                {isLoading ? (
                  <div className="flex items-center justify-center py-8">
                    <div className="loading-spinner"></div>
                  </div>
                ) : assignments && assignments.length > 0 ? (
                  <div className="overflow-x-auto">
                    <table className="table">
                      <thead>
                        <tr>
                          <th>Judge</th>
                          <th>Category</th>
                          <th>Contest</th>
                          <th>Status</th>
                          <th>Assigned Date</th>
                          <th>Actions</th>
                        </tr>
                      </thead>
                      <tbody>
                        {assignments.map((assignment: any) => (
                          <tr key={assignment.id}>
                            <td>
                              <div className="flex items-center space-x-3">
                                <div className="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                                  <span className="text-white text-xs font-medium">
                                    {assignment.judge?.name?.charAt(0).toUpperCase()}
                                  </span>
                                </div>
                                <div>
                                  <div className="font-medium text-gray-900 dark:text-white">
                                    {assignment.judge?.name}
                                  </div>
                                  <div className="text-sm text-gray-500 dark:text-gray-400">
                                    {assignment.judge?.email}
                                  </div>
                                </div>
                              </div>
                            </td>
                            <td>
                              <div className="font-medium text-gray-900 dark:text-white">
                                {assignment.category?.name}
                              </div>
                              <div className="text-sm text-gray-500 dark:text-gray-400">
                                {assignment.category?.description}
                              </div>
                            </td>
                            <td>
                              <div className="font-medium text-gray-900 dark:text-white">
                                {assignment.contest?.name}
                              </div>
                              <div className="text-sm text-gray-500 dark:text-gray-400">
                                {assignment.event?.name}
                              </div>
                            </td>
                            <td>{getStatusBadge(assignment.status)}</td>
                            <td>
                              {new Date(assignment.assignedAt).toLocaleDateString()}
                            </td>
                            <td>
                              <div className="flex items-center space-x-2">
                                <button
                                  onClick={() => {
                                    setSelectedAssignment(assignment)
                                    setIsEditModalOpen(true)
                                  }}
                                  className="btn btn-ghost btn-sm"
                                >
                                  <PencilIcon className="h-4 w-4" />
                                </button>
                                <button
                                  onClick={() => deleteAssignmentMutation.mutate(assignment.id)}
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
                    <UserGroupIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
                    <p>No assignments found</p>
                    <button
                      onClick={() => setIsCreateModalOpen(true)}
                      className="btn btn-primary btn-sm mt-2"
                    >
                      <PlusIcon className="h-4 w-4 mr-1" />
                      Create First Assignment
                    </button>
                  </div>
                )}
              </div>
            </div>
          </div>
        )

      case 'JUDGE':
        return (
          <div className="space-y-6">
            {/* Header */}
            <div>
              <h1 className="text-2xl font-bold text-gray-900 dark:text-white">My Assignments</h1>
              <p className="mt-1 text-sm text-gray-600 dark:text-gray-400">
                View your assigned categories and scoring tasks
              </p>
            </div>

            {/* Judge Assignments */}
            <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
              {assignments?.filter((a: any) => a.judge?.id === user?.id).map((assignment: any) => (
                <div key={assignment.id} className="card">
                  <div className="card-content">
                    <div className="flex items-center justify-between mb-4">
                      <div className="w-10 h-10 bg-green-500 rounded-lg flex items-center justify-center">
                        <TrophyIcon className="h-6 w-6 text-white" />
                      </div>
                      {getStatusBadge(assignment.status)}
                    </div>
                    <h3 className="font-semibold text-gray-900 dark:text-white mb-2">
                      {assignment.category?.name}
                    </h3>
                    <p className="text-sm text-gray-600 dark:text-gray-400 mb-4">
                      {assignment.category?.description}
                    </p>
                    <div className="space-y-2 text-sm">
                      <div className="flex justify-between">
                        <span className="text-gray-500 dark:text-gray-400">Contest:</span>
                        <span className="text-gray-900 dark:text-white">{assignment.contest?.name}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-500 dark:text-gray-400">Event:</span>
                        <span className="text-gray-900 dark:text-white">{assignment.event?.name}</span>
                      </div>
                      <div className="flex justify-between">
                        <span className="text-gray-500 dark:text-gray-400">Assigned:</span>
                        <span className="text-gray-900 dark:text-white">
                          {new Date(assignment.assignedAt).toLocaleDateString()}
                        </span>
                      </div>
                    </div>
                    <div className="mt-4 pt-4 border-t border-gray-200 dark:border-gray-700">
                      <button className="btn btn-primary w-full">
                        <EyeIcon className="h-4 w-4 mr-2" />
                        View Details
                      </button>
                    </div>
                  </div>
                </div>
              ))}
            </div>
          </div>
        )

      default:
        return (
          <div className="card">
            <div className="card-content text-center py-12">
              <UserGroupIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
              <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
                Access Restricted
              </h3>
              <p className="text-gray-600 dark:text-gray-400">
                You don't have permission to view assignments.
              </p>
            </div>
          </div>
        )
    }
  }

  return (
    <div className="space-y-6">
      {getRoleSpecificContent()}

      {/* Create Assignment Modal */}
      {isCreateModalOpen && (
        <div className="modal modal-open">
          <div className="modal-box">
            <h3 className="font-bold text-lg mb-4">Create New Assignment</h3>
            <form
              onSubmit={(e) => {
                e.preventDefault()
                const formData = new FormData(e.target as HTMLFormElement)
                createAssignmentMutation.mutate({
                  judgeId: formData.get('judgeId'),
                  categoryId: formData.get('categoryId'),
                  status: 'PENDING',
                })
              }}
            >
              <div className="form-group">
                <label className="form-label">Judge</label>
                <select name="judgeId" className="form-input" required>
                  <option value="">Select a judge</option>
                  {judges?.map((judge: any) => (
                    <option key={judge.id} value={judge.id}>
                      {judge.name} ({judge.email})
                    </option>
                  ))}
                </select>
              </div>
              <div className="form-group">
                <label className="form-label">Category</label>
                <select name="categoryId" className="form-input" required>
                  <option value="">Select a category</option>
                  {categories?.map((category: any) => (
                    <option key={category.id} value={category.id}>
                      {category.name} - {category.contest?.name}
                    </option>
                  ))}
                </select>
              </div>
              <div className="modal-action">
                <button
                  type="button"
                  onClick={() => setIsCreateModalOpen(false)}
                  className="btn btn-outline"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={createAssignmentMutation.isLoading}
                  className="btn btn-primary"
                >
                  {createAssignmentMutation.isLoading ? 'Creating...' : 'Create Assignment'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}

      {/* Edit Assignment Modal */}
      {isEditModalOpen && selectedAssignment && (
        <div className="modal modal-open">
          <div className="modal-box">
            <h3 className="font-bold text-lg mb-4">Edit Assignment</h3>
            <form
              onSubmit={(e) => {
                e.preventDefault()
                const formData = new FormData(e.target as HTMLFormElement)
                updateAssignmentMutation.mutate({
                  id: selectedAssignment.id,
                  data: {
                    status: formData.get('status'),
                  },
                })
              }}
            >
              <div className="form-group">
                <label className="form-label">Status</label>
                <select name="status" className="form-input" defaultValue={selectedAssignment.status}>
                  <option value="PENDING">Pending</option>
                  <option value="ACTIVE">Active</option>
                  <option value="COMPLETED">Completed</option>
                </select>
              </div>
              <div className="modal-action">
                <button
                  type="button"
                  onClick={() => {
                    setIsEditModalOpen(false)
                    setSelectedAssignment(null)
                  }}
                  className="btn btn-outline"
                >
                  Cancel
                </button>
                <button
                  type="submit"
                  disabled={updateAssignmentMutation.isLoading}
                  className="btn btn-primary"
                >
                  {updateAssignmentMutation.isLoading ? 'Updating...' : 'Update Assignment'}
                </button>
              </div>
            </form>
          </div>
        </div>
      )}
    </div>
  )
}

export default AssignmentsPage
