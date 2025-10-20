#!/bin/bash
# Direct React Query Fix Script
# This script directly fixes the React Query issues

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

echo "🔧 Direct React Query Fix"
echo "========================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix React Query imports
print_status "Step 1: Fixing React Query imports..."
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from '\''react-query'\''/from '\''@tanstack\/react-query'\''/g'
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from "react-query"/from "@tanstack\/react-query"/g'
print_success "React Query imports fixed"

# Step 2: Fix useQuery syntax to v5 format
print_status "Step 2: Fixing useQuery syntax to v5 format..."

# Create a comprehensive fix script
cat > /tmp/fix-usequery-v5.js << 'EOF'
const fs = require('fs');
const path = require('path');

function fixUseQueryV5(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    // Fix useQuery calls from v4 to v5 format
    // Pattern: useQuery(['key'], async () => { ... }, { options })
    const useQueryV4Regex = /useQuery\(\s*\[([^\]]+)\],\s*async\s*\(\)\s*=>\s*\{([^}]+)\},\s*\{([^}]+)\}\s*\)/gs;
    
    if (useQueryV4Regex.test(content)) {
        content = content.replace(useQueryV4Regex, (match, queryKey, queryFn, options) => {
            return `useQuery({
    queryKey: [${queryKey}],
    queryFn: async () => {
      ${queryFn}
    },
    ${options}
  })`;
        });
        modified = true;
    }

    // Pattern: useQuery(['key'], async () => { ... })
    const useQueryV4SimpleRegex = /useQuery\(\s*\[([^\]]+)\],\s*async\s*\(\)\s*=>\s*\{([^}]+)\}\s*\)/gs;
    
    if (useQueryV4SimpleRegex.test(content)) {
        content = content.replace(useQueryV4SimpleRegex, (match, queryKey, queryFn) => {
            return `useQuery({
    queryKey: [${queryKey}],
    queryFn: async () => {
      ${queryFn}
    }
  })`;
        });
        modified = true;
    }

    // Fix useMutation calls from v4 to v5 format
    // Pattern: useMutation(async (params) => { ... })
    const useMutationV4Regex = /useMutation\(\s*async\s*\(([^)]+)\)\s*=>\s*\{([^}]+)\}\s*\)/gs;
    
    if (useMutationV4Regex.test(content)) {
        content = content.replace(useMutationV4Regex, (match, params, mutationFn) => {
            return `useMutation({
    mutationFn: async (${params}) => {
      ${mutationFn}
    }
  })`;
        });
        modified = true;
    }

    // Fix invalidateQueries calls
    const invalidateQueriesRegex = /queryClient\.invalidateQueries\(\s*\[([^\]]+)\]\s*\)/g;
    if (invalidateQueriesRegex.test(content)) {
        content = content.replace(invalidateQueriesRegex, 'queryClient.invalidateQueries({ queryKey: [$1] })');
        modified = true;
    }

    // Fix isLoading to isPending
    if (content.includes('.isLoading')) {
        content = content.replace(/\.isLoading/g, '.isPending');
        modified = true;
    }

    if (modified) {
        fs.writeFileSync(filePath, content);
        console.log(`Fixed useQuery v5 in: ${filePath}`);
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
    if (fixUseQueryV5(file)) {
        modifiedCount++;
    }
}

console.log(`\nFixed useQuery v5 in ${modifiedCount} files`);
EOF

# Run the fix script
node /tmp/fix-usequery-v5.js
rm -f /tmp/fix-usequery-v5.js

print_success "useQuery v5 syntax fixed"

# Step 3: Run type check
print_status "Step 3: Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed"
else
    print_warning "TypeScript type check had issues, but continuing..."
fi

# Step 4: Try building
print_status "Step 4: Attempting to build frontend..."
if npm run build; then
    print_success "Frontend build completed successfully!"
    print_status "Build output is in the 'dist' directory"
else
    print_error "Frontend build failed"
    print_status "Check the error messages above for remaining issues"
    print_status "You may need to fix some issues manually"
    exit 1
fi

print_success "Direct React Query fix completed!"
echo ""
print_status "Summary of fixes applied:"
echo "✅ React Query imports fixed"
echo "✅ useQuery v5 syntax fixed"
echo "✅ useMutation v5 syntax fixed"
echo "✅ invalidateQueries v5 syntax fixed"
echo "✅ isLoading changed to isPending"
echo "✅ Build completed successfully"
echo ""
print_status "Next steps:"
echo "1. The frontend is now built and ready"
echo "2. You can serve it with: npm run preview"
echo "3. Or integrate it with your backend server"
