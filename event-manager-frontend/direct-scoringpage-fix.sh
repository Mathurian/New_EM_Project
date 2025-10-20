#!/bin/bash
# Direct Fix for ScoringPage.tsx Line 17 Syntax Error
# This script directly fixes the malformed destructuring assignment

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

echo "🔧 Direct Fix for ScoringPage.tsx Line 17"
echo "========================================="

# Check if we're in the right directory
if [ ! -f "src/pages/ScoringPage.tsx" ]; then
    print_error "ScoringPage.tsx not found. Please run from event-manager-frontend directory."
    exit 1
fi

print_status "Current problematic line 17:"
sed -n '17p' src/pages/ScoringPage.tsx

print_status "Fixing the malformed destructuring assignment..."

# The exact fix: replace the malformed destructuring with the correct one
sed -i 's/const { data: isPending: assignmentsLoading }/const { data: assignments, isPending: assignmentsLoading }/g' src/pages/ScoringPage.tsx

print_success "Fixed malformed destructuring assignment"

print_status "Fixed line 17:"
sed -n '17p' src/pages/ScoringPage.tsx

# Verify the fix by running type check
print_status "Running TypeScript type check to verify fix..."
if npm run type-check; then
    print_success "✅ TypeScript type check passed!"
else
    print_warning "⚠️  TypeScript type check still has issues"
    print_status "Remaining errors:"
    npm run type-check 2>&1 | head -10
fi

# Try building
print_status "🏗️  Attempting to build..."
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
    print_error "❌ Build failed"
    print_status "Remaining build errors:"
    npm run build 2>&1 | head -10
fi
