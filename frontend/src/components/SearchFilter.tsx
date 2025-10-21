import React, { useState } from 'react'
import { MagnifyingGlassIcon, FunnelIcon, XMarkIcon } from '@heroicons/react/24/outline'

interface FilterOption {
  value: string
  label: string
}

interface SearchFilterProps {
  searchTerm: string
  onSearchChange: (term: string) => void
  filters?: {
    [key: string]: {
      label: string
      options: FilterOption[]
      value: string
      onChange: (value: string) => void
    }
  }
  placeholder?: string
  className?: string
}

const SearchFilter: React.FC<SearchFilterProps> = ({
  searchTerm,
  onSearchChange,
  filters = {},
  placeholder = 'Search...',
  className = '',
}) => {
  const [showFilters, setShowFilters] = useState(false)

  const clearAllFilters = () => {
    onSearchChange('')
    Object.values(filters).forEach(filter => {
      filter.onChange('')
    })
  }

  const hasActiveFilters = searchTerm || Object.values(filters).some(filter => filter.value)

  return (
    <div className={`space-y-4 ${className}`}>
      {/* Search Bar */}
      <div className="relative">
        <div className="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
          <MagnifyingGlassIcon className="h-5 w-5 text-gray-400" />
        </div>
        <input
          type="text"
          value={searchTerm}
          onChange={(e) => onSearchChange(e.target.value)}
          placeholder={placeholder}
          className="input pl-10 pr-10 w-full"
        />
        {searchTerm && (
          <button
            onClick={() => onSearchChange('')}
            className="absolute inset-y-0 right-0 pr-3 flex items-center"
          >
            <XMarkIcon className="h-5 w-5 text-gray-400 hover:text-gray-600" />
          </button>
        )}
      </div>

      {/* Filter Toggle */}
      {Object.keys(filters).length > 0 && (
        <div className="flex items-center justify-between">
          <button
            onClick={() => setShowFilters(!showFilters)}
            className="flex items-center space-x-2 text-sm text-gray-600 dark:text-gray-400 hover:text-gray-900 dark:hover:text-white"
          >
            <FunnelIcon className="h-4 w-4" />
            <span>Filters</span>
            {hasActiveFilters && (
              <span className="bg-primary text-primary-foreground text-xs px-2 py-1 rounded-full">
                {Object.values(filters).filter(f => f.value).length + (searchTerm ? 1 : 0)}
              </span>
            )}
          </button>

          {hasActiveFilters && (
            <button
              onClick={clearAllFilters}
              className="text-sm text-gray-500 hover:text-gray-700 dark:text-gray-400 dark:hover:text-gray-200"
            >
              Clear all
            </button>
          )}
        </div>
      )}

      {/* Filter Options */}
      {showFilters && Object.keys(filters).length > 0 && (
        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-4 bg-gray-50 dark:bg-gray-800 rounded-lg">
          {Object.entries(filters).map(([key, filter]) => (
            <div key={key}>
              <label className="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                {filter.label}
              </label>
              <select
                value={filter.value}
                onChange={(e) => filter.onChange(e.target.value)}
                className="input w-full"
              >
                <option value="">All {filter.label}</option>
                {filter.options.map((option) => (
                  <option key={option.value} value={option.value}>
                    {option.label}
                  </option>
                ))}
              </select>
            </div>
          ))}
        </div>
      )}

      {/* Active Filters Display */}
      {hasActiveFilters && (
        <div className="flex flex-wrap gap-2">
          {searchTerm && (
            <span className="inline-flex items-center px-3 py-1 rounded-full text-sm bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200">
              Search: "{searchTerm}"
              <button
                onClick={() => onSearchChange('')}
                className="ml-2 hover:text-blue-600"
              >
                <XMarkIcon className="h-3 w-3" />
              </button>
            </span>
          )}
          {Object.entries(filters).map(([key, filter]) => {
            if (!filter.value) return null
            const selectedOption = filter.options.find(opt => opt.value === filter.value)
            return (
              <span
                key={key}
                className="inline-flex items-center px-3 py-1 rounded-full text-sm bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-200"
              >
                {filter.label}: {selectedOption?.label || filter.value}
                <button
                  onClick={() => filter.onChange('')}
                  className="ml-2 hover:text-gray-600"
                >
                  <XMarkIcon className="h-3 w-3" />
                </button>
              </span>
            )
          })}
        </div>
      )}
    </div>
  )
}

export default SearchFilter