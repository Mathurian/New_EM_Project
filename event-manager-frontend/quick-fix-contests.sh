#!/bin/bash
# Quick Fix for ContestsPage.tsx
# This script fixes the remaining ContestsPage.tsx import issue

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

print_status() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

print_success() {
    echo -e "${GREEN}[SUCCESS]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "🔧 Quick Fix for ContestsPage.tsx"
echo "================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Fix ContestsPage.tsx
print_status "Fixing ContestsPage.tsx..."
cat > src/pages/ContestsPage.tsx << 'EOF'
import React, { useState } from 'react'
import { useQuery } from '@tanstack/react-query'
import { api, formatDate } from '../utils'
import { Card, CardContent, CardDescription, CardHeader, CardTitle, Button, Badge, LoadingSpinner } from '../components'
import { Plus, Tag, Eye, Edit } from 'lucide-react'

export const ContestsPage = () => {
  const [searchTerm, setSearchTerm] = useState('')

  const { data: contests, isLoading, error } = useQuery({
    queryKey: ['contests'],
    queryFn: () => api.getContests('1').then(res => res.data),
  })

  if (isLoading) return <LoadingSpinner size="large" />
  if (error) return <div>Error loading contests</div>

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold">Contests</h1>
        <Button>
          <Plus className="h-4 w-4 mr-2" />
          Add Contest
        </Button>
      </div>

      <div className="grid gap-6">
        {contests?.map((contest: any) => (
          <Card key={contest.id}>
            <CardHeader>
              <div className="flex justify-between items-start">
                <div>
                  <CardTitle>{contest.name}</CardTitle>
                  <CardDescription>{contest.description}</CardDescription>
                </div>
                <Badge variant="secondary">{contest.status}</Badge>
              </div>
            </CardHeader>
            <CardContent>
              <div className="space-y-2">
                <span>Start: {formatDate(contest.start_date)}</span>
                <span>End: {formatDate(contest.end_date)}</span>
              </div>
            </CardContent>
          </Card>
        ))}
      </div>
    </div>
  )
}
EOF

print_success "ContestsPage.tsx fixed"

# Run type check
print_status "Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed!"
else
    print_warning "TypeScript type check still has issues, but continuing..."
fi

# Try building
print_status "Attempting to build..."
if npm run build; then
    print_success "🎉 Build completed successfully!"
    print_status "📁 Build output is in the 'dist' directory"
    echo ""
    print_status "🎯 Frontend build is now working!"
    print_status "You can now:"
    print_status "  - Serve the frontend with: npm run preview"
    print_status "  - Integrate with your backend server"
    print_status "  - Deploy the application"
else
    print_error "Build failed"
    print_status "Remaining build errors:"
    npm run build 2>&1 | head -20
fi
