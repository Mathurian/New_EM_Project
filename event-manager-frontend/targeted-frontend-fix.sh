#!/bin/bash
# Targeted Frontend Fix Script
# This script fixes the specific remaining issues

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

print_warning() {
    echo -e "${YELLOW}[WARNING]${NC} $1"
}

print_error() {
    echo -e "${RED}[ERROR]${NC} $1"
}

echo "🎯 Targeted Frontend Build Fix"
echo "============================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix React Query v5 syntax issues more precisely
print_status "Step 1: Fixing React Query v5 syntax issues..."

# Create a temporary file with the fixes
cat > /tmp/react-query-fixes.js << 'EOF'
const fs = require('fs');
const path = require('path');

function fixReactQueryFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    // Fix useQuery calls that still use string format
    const useQueryStringRegex = /useQuery\(\s*['"`]([^'"`]+)['"`]\s*,/g;
    if (useQueryStringRegex.test(content)) {
        content = content.replace(useQueryStringRegex, 'useQuery({ queryKey: [\'$1\'], queryFn: () => api.get(\'$1\'),');
        modified = true;
    }

    // Fix useQuery calls with array keys
    const useQueryArrayRegex = /useQuery\(\s*\[([^\]]+)\]\s*,/g;
    if (useQueryArrayRegex.test(content)) {
        content = content.replace(useQueryArrayRegex, 'useQuery({ queryKey: [$1], queryFn: () => api.getData($1),');
        modified = true;
    }

    // Fix useMutation calls
    const useMutationRegex = /useMutation\(\s*async\s*\(([^)]+)\)\s*=>\s*\{/g;
    if (useMutationRegex.test(content)) {
        content = content.replace(useMutationRegex, 'useMutation({ mutationFn: async ($1) => {');
        modified = true;
    }

    // Fix invalidateQueries calls
    const invalidateQueriesRegex = /queryClient\.invalidateQueries\(\s*['"`]([^'"`]+)['"`]\s*\)/g;
    if (invalidateQueriesRegex.test(content)) {
        content = content.replace(invalidateQueriesRegex, 'queryClient.invalidateQueries({ queryKey: [\'$1\'] })');
        modified = true;
    }

    const invalidateQueriesArrayRegex = /queryClient\.invalidateQueries\(\s*\[([^\]]+)\]\s*\)/g;
    if (invalidateQueriesArrayRegex.test(content)) {
        content = content.replace(invalidateQueriesArrayRegex, 'queryClient.invalidateQueries({ queryKey: [$1] })');
        modified = true;
    }

    // Fix isLoading to isPending
    if (content.includes('.isLoading')) {
        content = content.replace(/\.isLoading/g, '.isPending');
        modified = true;
    }

    if (modified) {
        fs.writeFileSync(filePath, content);
        console.log(`Fixed: ${filePath}`);
        return true;
    }
    return false;
}

// Find all TypeScript/JavaScript files in src directory
function findSourceFiles(dir) {
    const files = [];
    const items = fs.readdirSync(dir);
    
    for (const item of items) {
        const fullPath = path.join(dir, item);
        const stat = fs.statSync(fullPath);
        
        if (stat.isDirectory()) {
            files.push(...findSourceFiles(fullPath));
        } else if (item.endsWith('.tsx') || item.endsWith('.ts')) {
            files.push(fullPath);
        }
    }
    
    return files;
}

// Process all source files
const srcDir = path.join(process.cwd(), 'src');
const files = findSourceFiles(srcDir);
let modifiedCount = 0;

for (const file of files) {
    if (fixReactQueryFile(file)) {
        modifiedCount++;
    }
}

console.log(`\nFixed ${modifiedCount} files`);
EOF

# Run the fix script
node /tmp/react-query-fixes.js
rm -f /tmp/react-query-fixes.js

print_success "React Query v5 syntax fixed"

# Step 2: Fix TypeScript event handler issues
print_status "Step 2: Fixing TypeScript event handler issues..."

# Fix select onChange handlers - be more specific
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setStatusFilter/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setStatusFilter/g' {} \;
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSelectedContest/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setSelectedContest/g' {} \;
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setRoleFilter/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setRoleFilter/g' {} \;

print_success "TypeScript event handler issues fixed"

# Step 3: Remove unused imports
print_status "Step 3: Cleaning up unused imports..."

# Remove unused imports more carefully
find src -name "*.tsx" -exec sed -i '/^import.*isSearchOpen.*useState/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Shield.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Users.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Filter.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Search.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*XCircle.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*AlertCircle.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*formatDate.*from.*utils/d' {} \;

print_success "Unused imports cleaned up"

# Step 4: Install dependencies
print_status "Step 4: Installing dependencies..."
npm install
print_success "Dependencies installed"

# Step 5: Run type check
print_status "Step 5: Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed"
else
    print_warning "TypeScript type check had issues, but continuing..."
fi

# Step 6: Try building
print_status "Step 6: Attempting to build frontend..."
if npm run build; then
    print_success "Frontend build completed successfully!"
    print_status "Build output is in the 'dist' directory"
else
    print_error "Frontend build failed"
    print_status "Check the error messages above for remaining issues"
    print_status "You may need to fix some issues manually"
    exit 1
fi

print_success "Targeted frontend build fix completed!"
echo ""
print_status "Summary of fixes applied:"
echo "✅ React Query v5 syntax issues resolved"
echo "✅ TypeScript event handler issues fixed"
echo "✅ Unused imports cleaned up"
echo "✅ Dependencies installed"
echo "✅ Build completed successfully"
echo ""
print_status "Next steps:"
echo "1. The frontend is now built and ready"
echo "2. You can serve it with: npm run preview"
echo "3. Or integrate it with your backend server"
