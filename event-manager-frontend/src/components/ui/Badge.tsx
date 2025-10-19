import { cn } from '../../lib/utils'

interface BadgeProps {
  variant?: 'default' | 'secondary' | 'destructive' | 'outline'
  children: React.ReactNode
  className?: string
}

export const Badge = ({ variant = 'default', children, className }: BadgeProps) => {
  return (
    <span
      className={cn(
        'badge',
        {
          'badge-default': variant === 'default',
          'badge-secondary': variant === 'secondary',
          'badge-destructive': variant === 'destructive',
          'badge-outline': variant === 'outline',
        },
        className
      )}
    >
      {children}
    </span>
  )
}