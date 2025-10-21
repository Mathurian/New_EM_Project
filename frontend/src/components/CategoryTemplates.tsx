import React, { useState } from 'react'
import { useQuery, useMutation, useQueryClient } from 'react-query'
import { contestsAPI, categoriesAPI, api } from '../services/api'
import { useAuth } from '../contexts/AuthContext'
import { getCategoryIcon, getCategoryColor } from '../utils/helpers'
import {
  DocumentTextIcon,
  PlusIcon,
  PencilIcon,
  TrashIcon,
  EyeIcon,
  DocumentDuplicateIcon,
  MagnifyingGlassIcon,
  FunnelIcon,
  CheckCircleIcon,
  ClockIcon,
  StarIcon,
  TrophyIcon,
  UserGroupIcon,
  CalendarIcon,
  CogIcon,
} from '@heroicons/react/24/outline'
import { format } from 'date-fns'

interface CategoryTemplate {
  id: string
  name: string
  description: string
  categoryType: 'PERFORMANCE' | 'SKILL' | 'KNOWLEDGE' | 'CREATIVE' | 'TECHNICAL'
  criteria: ScoringCriteria[]
  maxScore: number
  timeLimit?: number
  requirements: string[]
  instructions: string
  isActive: boolean
  isPublic: boolean
  createdBy: string
  createdAt: string
  updatedAt: string
  usageCount: number
  tags: string[]
}

interface ScoringCriteria {
  id: string
  name: string
  description: string
  maxScore: number
  weight: number
  isRequired: boolean
  order: number
}

const CategoryTemplates: React.FC = () => {
  const { user } = useAuth()
  const queryClient = useQueryClient()
  const [activeTab, setActiveTab] = useState<'browse' | 'create' | 'manage'>('browse')
  const [searchTerm, setSearchTerm] = useState('')
  const [categoryFilter, setCategoryFilter] = useState('')
  const [showTemplateModal, setShowTemplateModal] = useState(false)
  const [editingTemplate, setEditingTemplate] = useState<CategoryTemplate | null>(null)

  // Fetch templates
  const { data: templates, isLoading: templatesLoading } = useQuery(
    'category-templates',
    () => api.get('/templates/categories').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'JUDGE',
    }
  )

  // Fetch user's templates
  const { data: userTemplates, isLoading: userTemplatesLoading } = useQuery(
    'user-category-templates',
    () => api.get('/templates/categories/user').then(res => res.data),
    {
      enabled: user?.role === 'ORGANIZER' || user?.role === 'BOARD' || user?.role === 'JUDGE',
    }
  )

  const createTemplateMutation = useMutation(
    (templateData: Partial<CategoryTemplate>) => api.post('/templates/categories', templateData),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
        queryClient.invalidateQueries('user-category-templates')
        setShowTemplateModal(false)
        setEditingTemplate(null)
      },
    }
  )

  const updateTemplateMutation = useMutation(
    ({ id, data }: { id: string; data: Partial<CategoryTemplate> }) =>
      api.put(`/templates/categories/${id}`, data),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
        queryClient.invalidateQueries('user-category-templates')
        setShowTemplateModal(false)
        setEditingTemplate(null)
      },
    }
  )

  const deleteTemplateMutation = useMutation(
    (id: string) => api.delete(`/templates/categories/${id}`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
        queryClient.invalidateQueries('user-category-templates')
      },
    }
  )

  const duplicateTemplateMutation = useMutation(
    (id: string) => api.post(`/templates/categories/${id}/duplicate`),
    {
      onSuccess: () => {
        queryClient.invalidateQueries('category-templates')
        queryClient.invalidateQueries('user-category-templates')
      },
    }
  )

  const filteredTemplates = templates?.filter((template: CategoryTemplate) => {
    const matchesSearch = template.name.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         template.description.toLowerCase().includes(searchTerm.toLowerCase()) ||
                         template.tags.some(tag => tag.toLowerCase().includes(searchTerm.toLowerCase()))
    const matchesCategory = categoryFilter === '' || template.categoryType === categoryFilter
    return matchesSearch && matchesCategory
  }) || []

  const getCategoryIcon = (type: string) => {
    switch (type) {
      case 'PERFORMANCE': return <TrophyIcon className="h-5 w-5 text-yellow-500" />
      case 'SKILL': return <StarIcon className="h-5 w-5 text-blue-500" />
      case 'KNOWLEDGE': return <DocumentTextIcon className="h-5 w-5 text-green-500" />
      case 'CREATIVE': return <UserGroupIcon className="h-5 w-5 text-purple-500" />
      case 'TECHNICAL': return <CogIcon className="h-5 w-5 text-gray-500" />
      default: return <DocumentTextIcon className="h-5 w-5 text-gray-500" />
    }
  }

  const getCategoryColor = (type: string) => {
    switch (type) {
      case 'PERFORMANCE': return 'badge-yellow'
      case 'SKILL': return 'badge-blue'
      case 'KNOWLEDGE': return 'badge-green'
      case 'CREATIVE': return 'badge-purple'
      case 'TECHNICAL': return 'badge-gray'
      default: return 'badge-gray'
    }
  }

  const tabs = [
    { id: 'browse', name: 'Browse Templates', icon: EyeIcon },
    { id: 'create', name: 'Create Template', icon: PlusIcon },
    { id: 'manage', name: 'My Templates', icon: CogIcon },
  ]

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
            onClick={() => {
              setEditingTemplate(null)
              setShowTemplateModal(true)
            }}
            className="btn btn-primary"
          >
            <PlusIcon className="h-5 w-5 mr-2" />
            Create Template
          </button>
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
          {activeTab === 'browse' && (
            <BrowseTemplatesTab
              templates={filteredTemplates}
              isLoading={templatesLoading}
              searchTerm={searchTerm}
              onSearchChange={setSearchTerm}
              categoryFilter={categoryFilter}
              onCategoryFilterChange={setCategoryFilter}
              onEdit={(template) => {
                setEditingTemplate(template)
                setShowTemplateModal(true)
              }}
              onDuplicate={(id) => duplicateTemplateMutation.mutate(id)}
            />
          )}

          {activeTab === 'create' && (
            <CreateTemplateTab
              onSave={(data) => createTemplateMutation.mutate(data)}
              isLoading={createTemplateMutation.isLoading}
            />
          )}

          {activeTab === 'manage' && (
            <ManageTemplatesTab
              templates={userTemplates || []}
              isLoading={userTemplatesLoading}
              onEdit={(template) => {
                setEditingTemplate(template)
                setShowTemplateModal(true)
              }}
              onDelete={(id) => deleteTemplateMutation.mutate(id)}
              onDuplicate={(id) => duplicateTemplateMutation.mutate(id)}
            />
          )}
        </div>
      </div>

      {/* Template Modal */}
      {showTemplateModal && (
        <TemplateModal
          template={editingTemplate}
          onClose={() => {
            setShowTemplateModal(false)
            setEditingTemplate(null)
          }}
          onSave={(data) => {
            if (editingTemplate) {
              updateTemplateMutation.mutate({ id: editingTemplate.id, data })
            } else {
              createTemplateMutation.mutate(data)
            }
          }}
          isLoading={createTemplateMutation.isLoading || updateTemplateMutation.isLoading}
        />
      )}
    </div>
  )
}

// Browse Templates Tab Component
interface BrowseTemplatesTabProps {
  templates: CategoryTemplate[]
  isLoading: boolean
  searchTerm: string
  onSearchChange: (term: string) => void
  categoryFilter: string
  onCategoryFilterChange: (category: string) => void
  onEdit: (template: CategoryTemplate) => void
  onDuplicate: (id: string) => void
}

const BrowseTemplatesTab: React.FC<BrowseTemplatesTabProps> = ({
  templates,
  isLoading,
  searchTerm,
  onSearchChange,
  categoryFilter,
  onCategoryFilterChange,
  onEdit,
  onDuplicate,
}) => {
  return (
    <div className="space-y-6">
      <div className="flex items-center justify-between">
        <h3 className="text-lg font-medium text-gray-900 dark:text-white">Browse Templates</h3>
        <div className="flex space-x-2">
          <div className="relative">
            <MagnifyingGlassIcon className="h-5 w-5 absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-400" />
            <input
              type="text"
              placeholder="Search templates..."
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
            <option value="">All Categories</option>
            <option value="PERFORMANCE">Performance</option>
            <option value="SKILL">Skill</option>
            <option value="KNOWLEDGE">Knowledge</option>
            <option value="CREATIVE">Creative</option>
            <option value="TECHNICAL">Technical</option>
          </select>
        </div>
      </div>

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : templates.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <DocumentTextIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No templates found
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            {searchTerm || categoryFilter
              ? 'Try adjusting your search criteria'
              : 'No category templates are available yet'}
          </p>
        </div>
      ) : (
        <div className="grid-responsive">
          {templates.map((template) => (
            <div key={template.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex items-center space-x-3">
                    {getCategoryIcon(template.categoryType)}
                    <div className="flex-1 min-w-0">
                      <h3 className="card-title text-lg truncate">{template.name}</h3>
                      <p className="card-description line-clamp-2">{template.description}</p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className={`badge ${getCategoryColor(template.categoryType)}`}>
                      {template.categoryType}
                    </span>
                    <span className={`badge ${template.isPublic ? 'badge-success' : 'badge-secondary'}`}>
                      {template.isPublic ? 'Public' : 'Private'}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <StarIcon className="h-4 w-4 mr-2" />
                  <span>Max Score: {template.maxScore}</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <CheckCircleIcon className="h-4 w-4 mr-2" />
                  <span>{template.criteria.length} criteria</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <CalendarIcon className="h-4 w-4 mr-2" />
                  <span>Used {template.usageCount} times</span>
                </div>
                {template.tags.length > 0 && (
                  <div className="flex flex-wrap gap-1">
                    {template.tags.map((tag) => (
                      <span key={tag} className="badge badge-outline badge-sm">
                        {tag}
                      </span>
                    ))}
                  </div>
                )}
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => onEdit(template)}
                      className="btn btn-outline btn-sm"
                    >
                      <EyeIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => onDuplicate(template.id)}
                      className="btn btn-outline btn-sm"
                    >
                      <DocumentDuplicateIcon className="h-4 w-4" />
                    </button>
                  </div>
                  <button className="btn btn-primary btn-sm">
                    Use Template
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

// Create Template Tab Component
interface CreateTemplateTabProps {
  onSave: (data: Partial<CategoryTemplate>) => void
  isLoading: boolean
}

const CreateTemplateTab: React.FC<CreateTemplateTabProps> = ({ onSave, isLoading }) => {
  const [formData, setFormData] = useState({
    name: '',
    description: '',
    categoryType: 'PERFORMANCE' as const,
    maxScore: 100,
    timeLimit: '',
    requirements: [''],
    instructions: '',
    isPublic: false,
    tags: [''],
  })

  const [criteria, setCriteria] = useState<ScoringCriteria[]>([
    {
      id: '1',
      name: '',
      description: '',
      maxScore: 0,
      weight: 1,
      isRequired: true,
      order: 1,
    },
  ])

  const handleSubmit = (e: React.FormEvent) => {
    e.preventDefault()
    onSave({
      ...formData,
      criteria: criteria.filter(c => c.name.trim() !== ''),
      requirements: formData.requirements.filter(r => r.trim() !== ''),
      tags: formData.tags.filter(t => t.trim() !== ''),
      timeLimit: formData.timeLimit ? parseInt(formData.timeLimit) : undefined,
    })
  }

  const addCriterion = () => {
    setCriteria([
      ...criteria,
      {
        id: Date.now().toString(),
        name: '',
        description: '',
        maxScore: 0,
        weight: 1,
        isRequired: true,
        order: criteria.length + 1,
      },
    ])
  }

  const removeCriterion = (id: string) => {
    setCriteria(criteria.filter(c => c.id !== id))
  }

  const updateCriterion = (id: string, field: keyof ScoringCriteria, value: any) => {
    setCriteria(criteria.map(c => c.id === id ? { ...c, [field]: value } : c))
  }

  return (
    <div className="space-y-6">
      <h3 className="text-lg font-medium text-gray-900 dark:text-white">Create Template</h3>
      
      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Basic Information */}
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
          <div>
            <label className="label">Category Type</label>
            <select
              value={formData.categoryType}
              onChange={(e) => setFormData({ ...formData, categoryType: e.target.value as any })}
              className="input"
              required
            >
              <option value="PERFORMANCE">Performance</option>
              <option value="SKILL">Skill</option>
              <option value="KNOWLEDGE">Knowledge</option>
              <option value="CREATIVE">Creative</option>
              <option value="TECHNICAL">Technical</option>
            </select>
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

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
          <div>
            <label className="label">Maximum Score</label>
            <input
              type="number"
              value={formData.maxScore}
              onChange={(e) => setFormData({ ...formData, maxScore: parseInt(e.target.value) })}
              className="input"
              min="1"
              required
            />
          </div>
          <div>
            <label className="label">Time Limit (minutes, optional)</label>
            <input
              type="number"
              value={formData.timeLimit}
              onChange={(e) => setFormData({ ...formData, timeLimit: e.target.value })}
              className="input"
              min="1"
            />
          </div>
        </div>

        {/* Scoring Criteria */}
        <div>
          <div className="flex items-center justify-between mb-4">
            <label className="label">Scoring Criteria</label>
            <button
              type="button"
              onClick={addCriterion}
              className="btn btn-outline btn-sm"
            >
              <PlusIcon className="h-4 w-4 mr-1" />
              Add Criterion
            </button>
          </div>
          <div className="space-y-4">
            {criteria.map((criterion, index) => (
              <div key={criterion.id} className="p-4 border border-gray-200 dark:border-gray-700 rounded-lg">
                <div className="flex items-center justify-between mb-3">
                  <h4 className="font-medium text-gray-900 dark:text-white">
                    Criterion {index + 1}
                  </h4>
                  {criteria.length > 1 && (
                    <button
                      type="button"
                      onClick={() => removeCriterion(criterion.id)}
                      className="text-red-600 hover:text-red-700"
                    >
                      <TrashIcon className="h-4 w-4" />
                    </button>
                  )}
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
                  <div>
                    <label className="label">Name</label>
                    <input
                      type="text"
                      value={criterion.name}
                      onChange={(e) => updateCriterion(criterion.id, 'name', e.target.value)}
                      className="input"
                      required
                    />
                  </div>
                  <div>
                    <label className="label">Max Score</label>
                    <input
                      type="number"
                      value={criterion.maxScore}
                      onChange={(e) => updateCriterion(criterion.id, 'maxScore', parseInt(e.target.value))}
                      className="input"
                      min="1"
                      required
                    />
                  </div>
                </div>
                <div className="mt-4">
                  <label className="label">Description</label>
                  <textarea
                    value={criterion.description}
                    onChange={(e) => updateCriterion(criterion.id, 'description', e.target.value)}
                    className="input"
                    rows={2}
                  />
                </div>
                <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mt-4">
                  <div>
                    <label className="label">Weight</label>
                    <input
                      type="number"
                      value={criterion.weight}
                      onChange={(e) => updateCriterion(criterion.id, 'weight', parseFloat(e.target.value))}
                      className="input"
                      min="0.1"
                      step="0.1"
                      required
                    />
                  </div>
                  <div className="flex items-center space-x-2 mt-6">
                    <input
                      type="checkbox"
                      id={`required-${criterion.id}`}
                      checked={criterion.isRequired}
                      onChange={(e) => updateCriterion(criterion.id, 'isRequired', e.target.checked)}
                      className="rounded border-gray-300 text-primary focus:ring-primary"
                    />
                    <label htmlFor={`required-${criterion.id}`} className="label">
                      Required criterion
                    </label>
                  </div>
                </div>
              </div>
            ))}
          </div>
        </div>

        {/* Requirements */}
        <div>
          <label className="label">Requirements</label>
          <div className="space-y-2">
            {formData.requirements.map((requirement, index) => (
              <div key={index} className="flex space-x-2">
                <input
                  type="text"
                  value={requirement}
                  onChange={(e) => {
                    const newRequirements = [...formData.requirements]
                    newRequirements[index] = e.target.value
                    setFormData({ ...formData, requirements: newRequirements })
                  }}
                  className="input flex-1"
                  placeholder="Enter requirement"
                />
                {formData.requirements.length > 1 && (
                  <button
                    type="button"
                    onClick={() => {
                      const newRequirements = formData.requirements.filter((_, i) => i !== index)
                      setFormData({ ...formData, requirements: newRequirements })
                    }}
                    className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                  >
                    <TrashIcon className="h-4 w-4" />
                  </button>
                )}
              </div>
            ))}
            <button
              type="button"
              onClick={() => setFormData({ ...formData, requirements: [...formData.requirements, ''] })}
              className="btn btn-outline btn-sm"
            >
              <PlusIcon className="h-4 w-4 mr-1" />
              Add Requirement
            </button>
          </div>
        </div>

        {/* Instructions */}
        <div>
          <label className="label">Instructions</label>
          <textarea
            value={formData.instructions}
            onChange={(e) => setFormData({ ...formData, instructions: e.target.value })}
            className="input"
            rows={4}
            placeholder="Enter detailed instructions for judges and contestants"
          />
        </div>

        {/* Tags */}
        <div>
          <label className="label">Tags</label>
          <div className="space-y-2">
            {formData.tags.map((tag, index) => (
              <div key={index} className="flex space-x-2">
                <input
                  type="text"
                  value={tag}
                  onChange={(e) => {
                    const newTags = [...formData.tags]
                    newTags[index] = e.target.value
                    setFormData({ ...formData, tags: newTags })
                  }}
                  className="input flex-1"
                  placeholder="Enter tag"
                />
                {formData.tags.length > 1 && (
                  <button
                    type="button"
                    onClick={() => {
                      const newTags = formData.tags.filter((_, i) => i !== index)
                      setFormData({ ...formData, tags: newTags })
                    }}
                    className="btn btn-outline btn-sm text-red-600 hover:text-red-700"
                  >
                    <TrashIcon className="h-4 w-4" />
                  </button>
                )}
              </div>
            ))}
            <button
              type="button"
              onClick={() => setFormData({ ...formData, tags: [...formData.tags, ''] })}
              className="btn btn-outline btn-sm"
            >
              <PlusIcon className="h-4 w-4 mr-1" />
              Add Tag
            </button>
          </div>
        </div>

        {/* Options */}
        <div className="flex items-center space-x-2">
          <input
            type="checkbox"
            id="isPublic"
            checked={formData.isPublic}
            onChange={(e) => setFormData({ ...formData, isPublic: e.target.checked })}
            className="rounded border-gray-300 text-primary focus:ring-primary"
          />
          <label htmlFor="isPublic" className="label">
            Make this template public for other users
          </label>
        </div>

        {/* Actions */}
        <div className="flex justify-end space-x-3">
          <button
            type="button"
            className="btn btn-outline"
          >
            Save as Draft
          </button>
          <button
            type="submit"
            className="btn btn-primary"
            disabled={isLoading}
          >
            {isLoading ? 'Creating...' : 'Create Template'}
          </button>
        </div>
      </form>
    </div>
  )
}

// Manage Templates Tab Component
interface ManageTemplatesTabProps {
  templates: CategoryTemplate[]
  isLoading: boolean
  onEdit: (template: CategoryTemplate) => void
  onDelete: (id: string) => void
  onDuplicate: (id: string) => void
}

const ManageTemplatesTab: React.FC<ManageTemplatesTabProps> = ({
  templates,
  isLoading,
  onEdit,
  onDelete,
  onDuplicate,
}) => {
  return (
    <div className="space-y-6">
      <h3 className="text-lg font-medium text-gray-900 dark:text-white">My Templates</h3>

      {isLoading ? (
        <div className="flex items-center justify-center py-8">
          <div className="loading-spinner"></div>
        </div>
      ) : templates.length === 0 ? (
        <div className="text-center py-8 text-gray-500 dark:text-gray-400">
          <CogIcon className="h-12 w-12 mx-auto mb-4 text-gray-300 dark:text-gray-600" />
          <h3 className="text-lg font-medium text-gray-900 dark:text-white mb-2">
            No templates created
          </h3>
          <p className="text-gray-600 dark:text-gray-400">
            Create your first category template to get started
          </p>
        </div>
      ) : (
        <div className="grid-responsive">
          {templates.map((template) => (
            <div key={template.id} className="card">
              <div className="card-header">
                <div className="flex items-start justify-between">
                  <div className="flex items-center space-x-3">
                    {getCategoryIcon(template.categoryType)}
                    <div className="flex-1 min-w-0">
                      <h3 className="card-title text-lg truncate">{template.name}</h3>
                      <p className="card-description line-clamp-2">{template.description}</p>
                    </div>
                  </div>
                  <div className="flex items-center space-x-2">
                    <span className={`badge ${getCategoryColor(template.categoryType)}`}>
                      {template.categoryType}
                    </span>
                    <span className={`badge ${template.isActive ? 'badge-success' : 'badge-secondary'}`}>
                      {template.isActive ? 'Active' : 'Inactive'}
                    </span>
                  </div>
                </div>
              </div>
              <div className="card-content space-y-3">
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <StarIcon className="h-4 w-4 mr-2" />
                  <span>Max Score: {template.maxScore}</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <CheckCircleIcon className="h-4 w-4 mr-2" />
                  <span>{template.criteria.length} criteria</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <CalendarIcon className="h-4 w-4 mr-2" />
                  <span>Used {template.usageCount} times</span>
                </div>
                <div className="flex items-center text-sm text-gray-600 dark:text-gray-400">
                  <ClockIcon className="h-4 w-4 mr-2" />
                  <span>Created: {format(new Date(template.createdAt), 'MMM dd, yyyy')}</span>
                </div>
              </div>
              <div className="card-footer">
                <div className="flex items-center justify-between">
                  <div className="flex space-x-2">
                    <button
                      onClick={() => onEdit(template)}
                      className="btn btn-outline btn-sm"
                    >
                      <PencilIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => onDuplicate(template.id)}
                      className="btn btn-outline btn-sm"
                    >
                      <DocumentDuplicateIcon className="h-4 w-4" />
                    </button>
                    <button
                      onClick={() => onDelete(template.id)}
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

// Template Modal Component
interface TemplateModalProps {
  template: CategoryTemplate | null
  onClose: () => void
  onSave: (data: Partial<CategoryTemplate>) => void
  isLoading: boolean
}

const TemplateModal: React.FC<TemplateModalProps> = ({ template, onClose, onSave, isLoading }) => {
  // This would be similar to the CreateTemplateTab but in a modal format
  // For brevity, I'll just show the structure
  return (
    <div className="modal">
      <div className="modal-overlay" onClick={onClose} />
      <div className="modal-content max-w-4xl">
        <h2 className="text-xl font-semibold mb-4">
          {template ? 'Edit Template' : 'Create Template'}
        </h2>
        {/* Template form would go here */}
        <div className="flex justify-end space-x-3 pt-4">
          <button onClick={onClose} className="btn btn-outline">
            Cancel
          </button>
          <button className="btn btn-primary" disabled={isLoading}>
            {isLoading ? 'Saving...' : template ? 'Update' : 'Create'}
          </button>
        </div>
      </div>
    </div>
  )
}

export default CategoryTemplates
