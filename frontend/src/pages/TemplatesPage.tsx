import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { useAuth } from '../contexts/AuthContext'
import { api } from '../services/api'
import {
  DocumentDuplicateIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon,
  MagnifyingGlassIcon,
  DocumentTextIcon,
  ClockIcon,
  CalendarIcon,
  UserIcon,
  ClipboardDocumentListIcon,
  CogIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface CategoryTemplate {
  id: string
  name: string
  description: string
  categories: CategoryTemplateItem[]
  isActive: boolean
  createdAt: string
  updatedAt: string
  createdBy: string
  usageCount: number
}

interface CategoryTemplateItem {
  id: string
  name: string
  description: string
  maxScore: number
  criteria: CriteriaTemplate[]
  order: number
}

interface CriteriaTemplate {
  id: string
  name: string
  description: string
  maxScore: number
  weight: number
  order: number
}

const TemplatesPage: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [searchTerm, setSearchTerm] = useState('')
  const [showCreateModal, setShowCreateModal] = useState(false)
  const [editingTemplate, setEditingTemplate] = useState<CategoryTemplate | null>(null)
  const [showDeleteModal, setShowDeleteModal] = useState<CategoryTemplate | null>(null)
  const [showPreviewModal, setShowPreviewModal] = useState<CategoryTemplate | null>(null)

  const { data: templates, isLoading } = useQuery(
    'category-templates',
    () => api.get('/templates/categories').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD',
    }
  )

  const createMutation = useMutation(
    (templateData: Partial<CategoryTemplate>) => api.post('/templates/categories', templateData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
        setShowCreateModal(false)
      },
    }
  )

  const updateMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<CategoryTemplate> }) =>
      api.put(`/templates/categories/${id}`, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
        setEditingTemplate(null)
      },
    }
  )

  const deleteMutation = useMutation(
    (id: string) => api.delete(`/templates/categories/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
        setShowDeleteModal(null)
      },
    }
  )

  const duplicateMutation = useMutation(
    (id: string) => api.post(`/templates/categories/${id}/duplicate`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
      },
    }
  )

  const filteredTemplates = templates?.filter((template: CategoryTemplate) =>
    template.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
    template.description.toLowerCase().includes(searchTerm.toLowerCase())
  ) || []

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
          <h1 className="text-2xl font-bold text-gray-900 dark:text-white">Category Templates</h1>
          <p className="text-gray-600 dark:text-gray-400">
            Create and manage reusable category templates for contests
          </p>
        </div>
        <div className="mt-4 sm:mt-0">
          <button
            onClick={() => setShowCreateModal(true)}
            className="btn btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Create Template
          </button>
        </div>
      </div>

      {/* Search */}
      <div className="card">
        <div className="card-content">
          <div className="relative">
            <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search templates..."
              value={searchTerm}
              onChange={(e) => setSearchTerm(e.target.value)}
              className="input pl-10"
            />
          </div>
        </div>
      </div>

      {/* Templates Grid */}
      {filteredTemplates.length === 0 ? (
        <div className="card">
          <div className="card-content text-center py-12">
            <ClipboardDocumentListIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
              No templates found
            </h3>
            <p className="text-gray-600 dark:text-gray-400 mb-4">
              {searchTerm
                ? 'Try adjusting your search criteria'
                : 'Get started by creating your first category template'}
            </p>
            {!searchTerm && (
              <button
                onClick={() => setShowCreateModal(true)}
                className="btn btn-primary"
              >
                <PlusIcon className="h-5 w-5 mr-2" />
                Create Template
              </button>
            )}
          </div>
        </div>
      ) : (
        <div className="grid-responsive">
          {filteredTemplates.map((template: CategoryTemplate) => (
            <div key={template.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex-1 min-w-0">
                    <h3 className="card-title text-lg truncate">{template.name}</h3>
                    <p className="card-description line-clamp-2">{template.description}</p>
                  </div>
                  <div className="flex items-center space-x-2 ml-2">
                    <span className="text-2xl">📋</span>
                    <span className={`status-indicator ${template.isActive ? 'status-online' : 'status-offline'}`}>
                      {template.isActive ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <DocumentTextIcon className="h-4 w-4 mr-2" />
                  <span>{template.categories.length} categories</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <ClipboardDocumentListIcon className="h-4 w-4 mr-2" />
                  <span>{template.categories.reduce((acc, cat) => acc + cat.criteria.length, 0)} criteria</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <UserIcon className="h-4 w-4 mr-2" />
                  <span>Used {template.usageCount} times</span>
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
                      onClick={() => setEditingTemplate(template)}
                      className="btn btn-outline btn-sm"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => duplicateMutation.mutate(template.id)}
                      className="btn btn-outline btn-sm"
                      disabled={duplicateMutation.isLoading}
                    >
                      <DocumentDuplicateIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => setShowDeleteModal(template)}
                      className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  </div>
                  <button
                    onClick={() => setShowPreviewModal(template)}
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

      {/* Create Template Modal */}
      {showCreateModal && (
        <TemplateModal
          template={null}
          onClose={() => setShowCreateModal(false)}
          onSave={(data) => createMutation.mutate(data)}
          isLoading={createMutation.isLoading}
        />
      )}

      {/* Edit Template Modal */}
      {editingTemplate && (
        <TemplateModal
          template={editingTemplate}
          onClose={() => setEditingTemplate(null)}
          onSave={(data) => updateMutation.mutate({ id: editingTemplate.id, data })}
          isLoading={updateMutation.isLoading}
        />
      )}

      {/* Delete Confirmation Modal */}
      {showDeleteModal && (
        <DeleteModal
          template={showDeleteModal}
          onClose={() => setShowDeleteModal(null)}
          onConfirm={() => deleteMutation.mutate(showDeleteModal.id)}
          isLoading={deleteMutation.isLoading}
        />
      )}

      {/* Preview Modal */}
      {showPreviewModal && (
        <PreviewModal
          template={showPreviewModal}
          onClose={() => setShowPreviewModal(null)}
        />
      )}
    </div>
  )
}

// Template Modal Component
interface TemplateModalProps {
  template: CategoryTemplate | null
  onClose: () => void
  onSave: (data: Partial<CategoryTemplate>) => void
  isLoading: boolean
}

const TemplateModal: React.FC<TemplateModalProps> = ({ template, onClose, onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    name: template?.name || '',
    description: template?.description || '',
    isActive: template?.isActive ?? true,
    categories: template?.categories || [],
  })

  const [editingCategory, setEditingCategory] = useState<CategoryTemplateItem | null>(null)
  const [editingCriteria, setEditingCriteria] = useState<CriteriaTemplate | null>(null)

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSave(formData)
  }

  const addCategory = () => {
    const newCategory: CategoryTemplateItem = {
      id: `temp-${Date.now()}`,
      name: '',
      description: '',
      maxScore: 100,
      criteria: [],
      order: formData.categories.length + 1,
    }
    setFormData({
      ...formData,
      categories: [...formData.categories, newCategory],
    })
  }

  const updateCategory = (categoryId: string, updates: Partial<CategoryTemplateItem>) => {
    setFormData({
      ...formData,
      categories: formData.categories.map(cat =>
        cat.id === categoryId ? { ...cat, ...updates } : cat
      ),
    })
  }

  const deleteCategory = (categoryId: string) => {
    setFormData({
      ...formData,
      categories: formData.categories.filter(cat => cat.id !== categoryId),
    })
  }

  const addCriteria = (categoryId: string) => {
    const category = formData.categories.find(cat => cat.id === categoryId)
    if (category) {
      const newCriteria: CriteriaTemplate = {
        id: `temp-${Date.now()}`,
        name: '',
        description: '',
        maxScore: 10,
        weight: 1,
        order: category.criteria.length + 1,
      }
      updateCategory(categoryId, {
        criteria: [...category.criteria, newCriteria],
      })
    }
  }

  const updateCriteria = (categoryId: string, criteriaId: string, updates: Partial<CriteriaTemplate>) => {
    const category = formData.categories.find(cat => cat.id === categoryId)
    if (category) {
      updateCategory(categoryId, {
        criteria: category.criteria.map(crit =>
          crit.id === criteriaId ? { ...crit, ...updates } : crit
        ),
      })
    }
  }

  const deleteCriteria = (categoryId: string, criteriaId: string) => {
    const category = formData.categories.find(cat => cat.id === categoryId)
    if (category) {
      updateCategory(categoryId, {
        criteria: category.criteria.filter(crit => crit.id !== criteriaId),
      })
    }
  }

  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-6xl">
        <h2 className="text-xl font-semibold mb-4">
          {template ? 'Edit Template' : 'Create Template'}
        </h2>
        <form onSubmit={handleSubmit} className="space-y-6">
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
          </div>
          <div>
            <label className="label">Description</label>
            <textarea
              value={formData.description}
              onChange={(e) => setFormData({ ...formData, description: e.target.value })}
              className="input"
              rows={3}
              required
            />
          </div>

          {/* Categories Section */}
          <div>
            <div className="flex items-center justify-between mb-4">
              <h3 className="text-lg font-medium text-gray-900 dark:text-white">Categories</h3>
              <button
                type="button"
                onClick={addCategory}
                className="btn btn-outline btn-sm"
              >
                <PlusIcon className="h-4 w-4 mr-1" />
                Add Category
              </button>
            </div>
            <div className="space-y-4">
              {formData.categories.map((category, index) => (
                <div key={category.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                  <div className="flex items-center justify-between mb-3">
                    <h4 className="font-medium text-gray-900 dark:text-white">
                      Category {index + 1}
                    </h4>
                    <div className="flex space-x-2">
                      <button
                        type="button"
                        onClick={() => setEditingCategory(category)}
                        className="btn btn-outline btn-sm"
                      >
                        <PencilIcon className="h-4 w-4" />
                      </button>
                      <button
                        type="button"
                        onClick={() => deleteCategory(category.id)}
                        className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                      >
                        <TrashIcon className="h-4 w-4" />
                      </button>
                    </div>
                  </div>
                  <div className="grid grid-cols-1 md:grid-cols-2 gap-3 mb-3">
                    <div>
                      <label className="label text-sm">Name</label>
                      <input
                        type="text"
                        value={category.name}
                        onChange={(e) => updateCategory(category.id, { name: e.target.value })}
                        className="input"
                        placeholder="Category name"
                      />
                    </div>
                    <div>
                      <label className="label text-sm">Max Score</label>
                      <input
                        type="number"
                        value={category.maxScore}
                        onChange={(e) => updateCategory(category.id, { maxScore: parseInt(e.target.value) })}
                        className="input"
                        min="1"
                      />
                    </div>
                  </div>
                  <div className="mb-3">
                    <label className="label text-sm">Description</label>
                    <textarea
                      value={category.description}
                      onChange={(e) => updateCategory(category.id, { description: e.target.value })}
                      className="input"
                      rows={2}
                      placeholder="Category description"
                    />
                  </div>
                  
                  {/* Criteria Section */}
                  <div>
                    <div className="flex items-center justify-between mb-2">
                      <h5 className="text-sm font-medium text-gray-900 dark:text-white">Criteria</h5>
                      <button
                        type="button"
                        onClick={() => addCriteria(category.id)}
                        className="btn btn-outline btn-sm"
                      >
                        <PlusIcon className="h-4 w-4 mr-1" />
                        Add Criteria
                      </button>
                    </div>
                    <div className="space-y-2">
                      {category.criteria.map((criteria, critIndex) => (
                        <div key={criteria.id} className="flex items-center space-x-2 p-2 bg-gray-50 dark:bg-gray-700 rounded">
                          <div className="flex-1 grid grid-cols-1 md:grid-cols-3 gap-2">
                            <input
                              type="text"
                              value={criteria.name}
                              onChange={(e) => updateCriteria(category.id, criteria.id, { name: e.target.value })}
                              className="input"
                              placeholder="Criteria name"
                            />
                            <input
                              type="number"
                              value={criteria.maxScore}
                              onChange={(e) => updateCriteria(category.id, criteria.id, { maxScore: parseInt(e.target.value) })}
                              className="input"
                              min="1"
                              placeholder="Max score"
                            />
                            <input
                              type="number"
                              value={criteria.weight}
                              onChange={(e) => updateCriteria(category.id, criteria.id, { weight: parseFloat(e.target.value) })}
                              className="input"
                              min="0.1"
                              step="0.1"
                              placeholder="Weight"
                            />
                          </div>
                          <button
                            type="button"
                            onClick={() => deleteCriteria(category.id, criteria.id)}
                            className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                          >
                            <TrashIcon className="h-4 w-4" />
                          </button>
                        </div>
                      ))}
                    </div>
                  </div>
                </div>
              ))}
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
              {isLoading ? 'Saving...' : template ? 'Update' : 'Create'}
            </button>
          </div>
        </form>
      </div>
    </div>
  )
}

// Delete Confirmation Modal
interface DeleteModalProps {
  template: CategoryTemplate
  onClose: () => void
  onConfirm: () => void
  isLoading: boolean
}

const DeleteModal: React.FC<DeleteModalProps> = ({ template, onClose, onConfirm, isLoading }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content">
        <h2 className="text-xl font-semibold mb-4">Delete Template</h2>
        <p className="text-gray-600 dark:text-gray-400 mb-6">
          Are you sure you want to delete "{template.name}"? This action cannot be undone.
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
  template: CategoryTemplate
  onClose: () => void
}

const PreviewModal: React.FC<PreviewModalProps> = ({ template, onClose }) => {
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-4xl">
        <div className="flex items-center justify-between mb-4">
          <h2 className="text-xl font-semibold">Template Preview</h2>
          <button
            onClick={onClose}
            className="btn btn-ghost btn-sm"
          >
            <XMarkIcon className="h-5 w-5" />
          </button>
        </div>
        <div className="space-y-6">
          <div>
            <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">{template.name}</h3>
            <p className="text-gray-600 dark:text-gray-400">{template.description}</p>
          </div>
          
          <div className="space-y-4">
            {template.categories.map((category, index) => (
              <div key={category.id} className="border border-gray-200 dark:border-gray-700 rounded-lg p-4">
                <div className="flex items-center justify-between mb-3">
                  <h4 className="font-medium text-gray-900 dark:text-white">
                    {category.name}
                  </h4>
                  <span className="text-sm text-gray-600 dark:text-gray-400">
                    Max Score: {category.maxScore}
                  </span>
                </div>
                <p className="text-sm text-gray-600 dark:text-gray-400 mb-3">
                  {category.description}
                </p>
                <div className="space-y-2">
                  <h5 className="text-sm font-medium text-gray-900 dark:text-white">Criteria:</h5>
                  {category.criteria.map((criteria) => (
                    <div key={criteria.id} className="flex items-center justify-between p-2 bg-gray-50 dark:bg-gray-700 rounded">
                      <span className="text-sm text-gray-900 dark:text-white">{criteria.name}</span>
                      <div className="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400">
                        <span>Max: {criteria.maxScore}</span>
                        <span>Weight: {criteria.weight}</span>
                      </div>
                    </div>
                  ))}
                </div>
              </div>
            ))}
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

export default TemplatesPage
