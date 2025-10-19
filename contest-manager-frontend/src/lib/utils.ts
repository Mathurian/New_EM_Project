import { type ClassValue, clsx } from 'clsx'
import { twMerge } from 'tailwind-merge'

export function cn(...inputs: ClassValue[]) {
  return twMerge(clsx(inputs))
}

export function formatDate(date: string | Date) {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
  }).format(new Date(date))
}

export function formatDateTime(date: string | Date) {
  return new Intl.DateTimeFormat('en-US', {
    year: 'numeric',
    month: 'short',
    day: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date))
}

export function formatTime(date: string | Date) {
  return new Intl.DateTimeFormat('en-US', {
    hour: '2-digit',
    minute: '2-digit',
  }).format(new Date(date))
}

export function formatFileSize(bytes: number) {
  if (bytes === 0) return '0 Bytes'
  
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

export function formatScore(score: number, maxScore: number) {
  const percentage = maxScore > 0 ? (score / maxScore * 100).toFixed(1) : '0.0'
  return `${score}/${maxScore} (${percentage}%)`
}

export function getInitials(firstName: string, lastName: string) {
  return `${firstName.charAt(0)}${lastName.charAt(0)}`.toUpperCase()
}

export function getRoleDisplayName(role: string) {
  const roleNames = {
    organizer: 'Organizer',
    emcee: 'Emcee',
    judge: 'Judge',
    tally_master: 'Tally Master',
    auditor: 'Auditor',
    board: 'Board Member'
  }
  
  return roleNames[role as keyof typeof roleNames] || role
}

export function getRoleColor(role: string) {
  const colors = {
    organizer: 'bg-purple-100 text-purple-800',
    emcee: 'bg-blue-100 text-blue-800',
    judge: 'bg-green-100 text-green-800',
    tally_master: 'bg-orange-100 text-orange-800',
    auditor: 'bg-red-100 text-red-800',
    board: 'bg-gray-100 text-gray-800'
  }
  
  return colors[role as keyof typeof colors] || 'bg-gray-100 text-gray-800'
}

export function debounce<T extends (...args: any[]) => any>(
  func: T,
  wait: number
): (...args: Parameters<T>) => void {
  let timeout: NodeJS.Timeout | null = null
  
  return (...args: Parameters<T>) => {
    if (timeout) clearTimeout(timeout)
    timeout = setTimeout(() => func(...args), wait)
  }
}

export function throttle<T extends (...args: any[]) => any>(
  func: T,
  limit: number
): (...args: Parameters<T>) => void {
  let inThrottle: boolean
  
  return (...args: Parameters<T>) => {
    if (!inThrottle) {
      func(...args)
      inThrottle = true
      setTimeout(() => inThrottle = false, limit)
    }
  }
}