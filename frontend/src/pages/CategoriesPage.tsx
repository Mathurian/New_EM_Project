import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useParams, Link } from 'react-router-dom'
import { categoriesAPI } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import {
  DocumentTextIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon,
  UsersIcon,
  TrophyIcon,
  MagnifyingGlassIcon,
  ArrowLeftIcon,
  StarIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface Category {
  id: string
  name: string
  description: string
  maxScore: number
  order: number
  contestId: string
  createdAt: string
  updatedAt: string
  _count?: {
    criteria: number
    contestants: number
    judges: number
    scores: number
  }
  criteria?: Criterion[]
}

interface Criterion {
  id: string
  name: string
  description: string
  maxScore: number
  order: number
  categoryId: string
}

const CategoriesPage: React.FC = () => {
  const { contestId } = useParams<{ contestId: string }>()
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [editingCategory, setEditingCategory] = useState<Category | null>(null)
  const [showDeleteModal, setShowDeleteModal] = useState<Category | null>(null)

  const { data: categories, isLoading } = useQuery(
    ['categories', contestId],
    () => categoriesAPI.getByContest(contestId!).then(res => res.data),
    {
      enabled: !!contestId && (user?.role === 'ORGANIZER' || user?.role === 'BOARD'),
    }
  )

  const { data: contest } = useQuery(
    ['contest', contestId],
    () => categoriesAPI.getById(contestId!).then(res => res.data),
    {
      enabled: !!contestId,
    }
  )

  const createMutation = useMutation(
    (categoryData: Partial<Category>) => categoriesAPI.create({ ...categoryData, contestId }),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['categories', contestId])
        setShowCreateModal(false)
      },
    }
  )

  const updateMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<Category> }) =>
      categoriesAPI.update(id, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['categories', contestId])
        setEditingCategory(null)
      },
    }
  )

  const deleteMutation = useMutation(
    (id: string) => categoriesAPI.delete(id),
    {
      onSuccess: () => {
        queryClient.invalidateQueries(['categories', contestId])
        setShowDeleteModal(null)
      },
    }
  )

  const filteredCategories = categories?.filter((category: Category) => {
    const matchesSearch = category.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         category.description.toLowerCase().includes(searchTerm.toLowerCase())
    return matchesSearch
  }) || []

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
          <div className="flex items-center space-x-2 mb-2">
            <Link
              to={`/events/${contest?.eventId}/contests`}
              className="btn btn-ghost btn-sm"
            >
              <ArrowLeftIcon className="h-4 w-4 mr-1" />
              Back to Contests
            </Link>
          </div>
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">
            Categories - {contest?.name || 'Contest'}
          </h1>
          <p className="text-gray-600 dark:text-gray-400">
            Manage scoring categories within this contest
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Create Category
          </button>
        </div>
      </div>

      {/* Filters */}
      <div className="card">
        <div className="card-content">
          <div className="relative">
            <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search categories..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input pl-10"
            />
          </div>
        </div>
      </div>

      {/* Categories Grid */}
      {filteredCategories.length === 0 ? (
        <div className="card">
          <div className="card-content text-center py-12">
            <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              No categories found
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              {searchTerm
                ? 'Try adjusting your search criteria'
                : 'Get started by creating your first category'}
            </p>
            {!searchTerm && (
              <button
                onClick={() => setShowCreateModal(true)}
                className="btn btn-primary"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Create Category
              </button>
            )}
          </div>
        </div>
      ) : (
        <div className="grid-responsive">
          {filteredCategories.map((category: Category) => (
            <div key={category.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{category.name}</h3>
                    <p className="card-description line-clamp-2">{category.description}</p>
                  </div>
                  <div className="flex items-center space-x-2 ml-2">
                    <span className="badge badge-outline">Order: {category.order}</span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <StarIcon className="h-4 w-4 mr-2" />
                  <span>Max Score: {category.maxScore}</span>
                </div>
                <div className="flex items-center justify-between text-sm text-gray-600 dark:text-gray-400">
                  <div className="flex items-center">
                    <DocumentTextIcon className="h-4 w-4 mr-2" />
                    <span>{category._count?.criteria || 0} criteria</span>
                  </div>
                  <div className="flex items-center">
                    <UsersIcon className="h-4 w-4 mr-2" />
                    <span>{category._count?.contestants || 0} contestants</span>
                  </div>
                </div>
                <div className="text-sm text-gray-600 dark:text-gray-400">
                  Judges: {category._count?.judges || 0} | Scores: {category._count?.scores || 0}
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => setEditingCategory(category)}
                      className="btn btn-outline btn-sm"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => setShowDeleteModal(category)}
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

      {/* Create Category Modal */}
      {showCreateModal && (
        <CategoryModal
          category={null}
          onClose={() => setShowCreateModal(false)}
          onSave={(data) => createMutation.mutate(data)}
          isLoading={createMutation.isLoading}
        />
      )}

      {/* Edit Category Modal */}
      {editingCategory && (
        <CategoryModal
          category={editingCategory}
          onClose={() => setEditingCategory(null)}
          onSave={(data) => updateMutation.mutate({ id: editingCategory.id, data })}
          isLoading={updateMutation.isLoading}
        />
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <DeleteModal
          category={showDeleteModal}
          onClose={() => setShowDeleteModal(null)}
          onConfirm={() => deleteMutation.mutate(showDeleteModal.id)}
          isLoading={deleteMutation.isLoading}
        />
      )}
    </div>
  )
}

// Category Modal Component
interface CategoryModalProps {
  category: Category | null
  onClose: () => void
  onSave: (data: Partial<Category>) => void
  isLoading: boolean
}

const CategoryModal: React.FC<CategoryModalProps> = ({ category, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    name: category?.name || '',
    description: category?.description || '',
    maxScore: category?.maxScore || 100,
    order: category?.order || 1,
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
          {category ? 'Edit Category' : 'Create Category'}
        </h2>
        <form onSubmit={handleSubmit} className="space-y-4">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="label">Category Name</label>
              <input
                type="text"
                value={formData.name}
                onChange={(e) => setFormData({ ...formData, name: e.target.value })}
                className="input"
                required
              />
            </div>
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
          <div>
            <label className="label">Maximum Score</label>
            <input
              type="number"
              value={formData.maxScore}
              onChange={(e) => setFormData({ ...formData, maxScore: parseInt(e.target.value) })}
              className="input"
              min="1"
              max="1000"
              required
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
              {isLoading ? 'Saving...' : category ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Delete Confirmation Modal
interface DeleteModalProps {
  category: Category
  onClose: () => void
  onConfirm: () => void
  isLoading: boolean
}

const DeleteModal: React.FC<DeleteModalProps> = ({ category, onClose, onConfirm, isLoading }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Delete Category</h2>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Are you sure you want to delete "{category.name}"? This action cannot be undone.
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

export default CategoriesPage
