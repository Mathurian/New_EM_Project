#!/bin/bash
# Targeted Fix for ScoringPage.tsx Syntax Error
# This script specifically fixes the malformed destructuring assignment

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

echo "🔧 Targeted Fix for ScoringPage.tsx"
echo "=================================="

# Check if we're in the right directory
if [ ! -f "src/pages/ScoringPage.tsx" ]; then
    print_error "ScoringPage.tsx not found. Please run from event-manager-frontend directory."
    exit 1
fi

print_status "Investigating ScoringPage.tsx syntax error..."

# First, let's see what's actually on line 17
print_status "Current content around line 17:"
sed -n '15,20p' src/pages/ScoringPage.tsx

print_status "Searching for malformed destructuring patterns..."

# Look for the specific error pattern
if grep -q "data: isPending: assignmentsLoading" src/pages/ScoringPage.tsx; then
    print_status "Found malformed destructuring - fixing..."
    sed -i 's/data: isPending: assignmentsLoading/data: assignments, isPending: assignmentsLoading/g' src/pages/ScoringPage.tsx
    print_success "Fixed malformed destructuring"
elif grep -q "data: isPending:" src/pages/ScoringPage.tsx; then
    print_status "Found other malformed destructuring pattern - fixing..."
    sed -i 's/data: isPending:/data: assignments, isPending:/g' src/pages/ScoringPage.tsx
    print_success "Fixed malformed destructuring"
else
    print_status "No malformed destructuring found. Checking for other syntax issues..."
    
    # Look for any useQuery calls that might have syntax issues
    if grep -q "const { data: isPending:" src/pages/ScoringPage.tsx; then
        print_status "Found problematic useQuery destructuring - fixing..."
        sed -i 's/const { data: isPending:/const { data: assignments, isPending:/g' src/pages/ScoringPage.tsx
        print_success "Fixed useQuery destructuring"
    fi
fi

# Let's also check if there are any other syntax issues
print_status "Checking for other potential syntax issues..."

# Fix any remaining issues with assignments variable
if grep -q "assignmentsLoading" src/pages/ScoringPage.tsx && ! grep -q "data: assignments" src/pages/ScoringPage.tsx; then
    print_status "Found assignmentsLoading without proper destructuring - fixing..."
    # This is a more complex fix - we need to find the line and fix it properly
    sed -i 's/const { data: isPending: assignmentsLoading }/const { data: assignments, isPending: assignmentsLoading }/g' src/pages/ScoringPage.tsx
    print_success "Fixed assignmentsLoading destructuring"
fi

print_status "Updated content around line 17:"
sed -n '15,20p' src/pages/ScoringPage.tsx

# Run type check to verify the fix
print_status "Running TypeScript type check..."
if npm run type-check; then
    print_success "✅ TypeScript type check passed!"
else
    print_warning "⚠️  TypeScript type check still has issues"
    print_status "Let's see what errors remain:"
    npm run type-check 2>&1 | head -20
fi

# Try building
print_status "🏗️  Attempting to build..."
if npm run build; then
    print_success "🎉 Build completed successfully!"
    print_status "📁 Build output is in the 'dist' directory"
else
    print_error "❌ Build failed"
    print_status "Let's see the specific error:"
    npm run build 2>&1 | head -10
fi
