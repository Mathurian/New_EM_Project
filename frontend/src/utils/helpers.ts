// Utility functions for the application

export const getSeverityColor = (severity: string) => {
  switch (severity.toLowerCase()) {
    case 'error':
      return 'badge-destructive'
    case 'warning':
      return 'badge-warning'
    case 'info':
      return 'badge-info'
    case 'success':
      return 'badge-success'
    default:
      return 'badge-secondary'
  }
}

export const getStatusColor = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending':
      return 'badge-warning'
    case 'in_progress':
      return 'badge-info'
    case 'certified':
    case 'approved':
    case 'completed':
      return 'badge-success'
    case 'rejected':
    case 'failed':
      return 'badge-destructive'
    case 'active':
      return 'badge-success'
    case 'inactive':
      return 'badge-secondary'
    default:
      return 'badge-secondary'
  }
}

export const getStepIcon = (stepStatus: string) => {
  switch (stepStatus.toLowerCase()) {
    case 'pending':
      return '⏳'
    case 'in_progress':
      return '🔄'
    case 'completed':
      return '✅'
    case 'failed':
      return '❌'
    default:
      return '📋'
  }
}

export const getCategoryIcon = (type: string) => {
  switch (type.toLowerCase()) {
    case 'performance':
      return '🎭'
    case 'talent':
      return '⭐'
    case 'interview':
      return '💬'
    case 'presentation':
      return '📊'
    case 'creative':
      return '🎨'
    default:
      return '📋'
  }
}

export const getCategoryColor = (type: string) => {
  switch (type.toLowerCase()) {
    case 'performance':
      return 'badge-purple'
    case 'talent':
      return 'badge-yellow'
    case 'interview':
      return 'badge-blue'
    case 'presentation':
      return 'badge-green'
    case 'creative':
      return 'badge-pink'
    default:
      return 'badge-secondary'
  }
}

export const getTypeIcon = (type: string) => {
  switch (type.toLowerCase()) {
    case 'announcement':
      return '📢'
    case 'introduction':
      return '👋'
    case 'transition':
      return '🔄'
    case 'closing':
      return '👋'
    case 'award':
      return '🏆'
    case 'break':
      return '☕'
    default:
      return '📝'
  }
}

export const getTypeColor = (type: string) => {
  switch (type.toLowerCase()) {
    case 'announcement':
      return 'badge-blue'
    case 'introduction':
      return 'badge-green'
    case 'transition':
      return 'badge-yellow'
    case 'closing':
      return 'badge-purple'
    case 'award':
      return 'badge-gold'
    case 'break':
      return 'badge-gray'
    default:
      return 'badge-secondary'
  }
}

export const getFileIcon = (mimeType: string) => {
  if (mimeType.startsWith('image/')) return '🖼️'
  if (mimeType.startsWith('video/')) return '🎥'
  if (mimeType.startsWith('audio/')) return '🎵'
  if (mimeType.includes('pdf')) return '📄'
  if (mimeType.includes('word')) return '📝'
  if (mimeType.includes('excel') || mimeType.includes('spreadsheet')) return '📊'
  if (mimeType.includes('powerpoint') || mimeType.includes('presentation')) return '📈'
  if (mimeType.includes('zip') || mimeType.includes('rar')) return '📦'
  return '📁'
}

export const formatFileSize = (bytes: number) => {
  if (bytes === 0) return '0 Bytes'
  const k = 1024
  const sizes = ['Bytes', 'KB', 'MB', 'GB', 'TB']
  const i = Math.floor(Math.log(bytes) / Math.log(k))
  return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i]
}

export const getStatusText = (status: string) => {
  switch (status.toLowerCase()) {
    case 'pending':
      return 'Pending'
    case 'in_progress':
      return 'In Progress'
    case 'sent':
      return 'Sent'
    case 'delivered':
      return 'Delivered'
    case 'failed':
      return 'Failed'
    case 'draft':
      return 'Draft'
    case 'scheduled':
      return 'Scheduled'
    default:
      return status.charAt(0).toUpperCase() + status.slice(1)
  }
}
