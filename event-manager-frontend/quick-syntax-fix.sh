#!/bin/bash
# Quick Fix for ScoringPage.tsx Syntax Error

echo "🔧 Quick Fix for ScoringPage.tsx"
echo "================================"

# Check if we're in the right directory
if [ ! -f "src/pages/ScoringPage.tsx" ]; then
    echo "❌ ScoringPage.tsx not found. Please run from event-manager-frontend directory."
    exit 1
fi

echo "📝 Fixing syntax error in ScoringPage.tsx..."

# Find and fix the broken destructuring assignment
# The error is likely: "const { data: isPending: assignmentsLoading } = useQuery({"
# Should be: "const { data: assignments, isPending: assignmentsLoading } = useQuery({"

# Use sed to fix the broken destructuring
sed -i 's/data: isPending: assignmentsLoading/data: assignments, isPending: assignmentsLoading/g' src/pages/ScoringPage.tsx

echo "✅ Syntax error fixed"

# Run type check to verify
echo "🔍 Running TypeScript type check..."
if npm run type-check; then
    echo "✅ TypeScript type check passed!"
else
    echo "⚠️  TypeScript type check still has issues"
fi

# Try building
echo "🏗️  Attempting to build..."
if npm run build; then
    echo "🎉 Build completed successfully!"
    echo "📁 Build output is in the 'dist' directory"
else
    echo "❌ Build failed - check error messages above"
fi
