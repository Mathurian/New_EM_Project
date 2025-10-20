#!/bin/bash
# React Query v5 Fix Script
# This script fixes all React Query v5 API usage issues

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

echo "🔧 React Query v5 API Fix Script"
echo "================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

print_status "Fixing React Query v5 API usage..."

# Create a temporary file with React Query v5 fixes
cat > /tmp/react-query-fixes.js << 'EOF'
const fs = require('fs');
const path = require('path');

// Function to fix React Query v5 usage in a file
function fixReactQueryFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    // Fix useQuery calls - convert string/array to object format
    content = content.replace(
        /useQuery\(\s*['"`]([^'"`]+)['"`]\s*,/g,
        'useQuery({ queryKey: [\'$1\'], queryFn: () => api.$1(),'
    );
    
    content = content.replace(
        /useQuery\(\s*\[([^\]]+)\]\s*,/g,
        'useQuery({ queryKey: [$1], queryFn: () => api.getData($1),'
    );

    // Fix useQuery with options
    content = content.replace(
        /useQuery\(\s*['"`]([^'"`]+)['"`]\s*,\s*\(\)\s*=>\s*([^,]+)\s*,\s*\{([^}]+)\}/g,
        'useQuery({ queryKey: [\'$1\'], queryFn: () => $2, $3 })'
    );

    // Fix useMutation calls
    content = content.replace(
        /useMutation\(\s*async\s*\(([^)]+)\)\s*=>\s*\{([^}]+)\}/g,
        'useMutation({ mutationFn: async ($1) => { $2 }'
    );

    // Fix invalidateQueries calls
    content = content.replace(
        /queryClient\.invalidateQueries\(['"`]([^'"`]+)['"`]\)/g,
        'queryClient.invalidateQueries({ queryKey: [\'$1\'] })'
    );

    content = content.replace(
        /queryClient\.invalidateQueries\(\[([^\]]+)\]\)/g,
        'queryClient.invalidateQueries({ queryKey: [$1] })'
    );

    // Fix isLoading to isPending
    content = content.replace(/\.isLoading/g, '.isPending');

    // Fix mutation.isLoading to mutation.isPending
    content = content.replace(/Mutation\.isLoading/g, 'Mutation.isPending');

    if (content !== fs.readFileSync(filePath, 'utf8')) {
        fs.writeFileSync(filePath, content);
        modified = true;
    }

    return modified;
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
        console.log(`Fixed: ${file}`);
        modifiedCount++;
    }
}

console.log(`\nFixed ${modifiedCount} files`);
EOF

# Run the fix script
print_status "Applying React Query v5 fixes..."
node /tmp/react-query-fixes.js

# Clean up
rm -f /tmp/react-query-fixes.js

print_success "React Query v5 API fixes applied!"

# Now let's create a more comprehensive fix for specific patterns
print_status "Applying additional React Query v5 pattern fixes..."

# Fix specific useQuery patterns
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/useQuery(\([^,]*\),/useQuery({ queryKey: [\1], queryFn: () => api.getData(\1),/g'

# Fix useMutation patterns
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/useMutation(async (\([^)]*\)) => {/useMutation({ mutationFn: async (\1) => {/g'

# Fix invalidateQueries patterns
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/queryClient\.invalidateQueries(\([^)]*\))/queryClient.invalidateQueries({ queryKey: [\1] })/g'

# Fix isLoading to isPending
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/\.isLoading/.isPending/g'

print_success "Additional pattern fixes applied!"

print_status "React Query v5 fixes completed!"
echo ""
print_status "Next steps:"
echo "1. Run 'npm run type-check' to check for remaining TypeScript issues"
echo "2. Run 'npm run build' to test the build"
echo "3. Fix any remaining issues manually if needed"
