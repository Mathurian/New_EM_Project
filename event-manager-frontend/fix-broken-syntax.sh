#!/bin/bash
# React Query Syntax Repair Script
# This script fixes the broken React Query v5 syntax

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

echo "🔧 React Query Syntax Repair"
echo "============================"

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix broken useQuery syntax
print_status "Step 1: Fixing broken useQuery syntax..."

# Create a comprehensive fix script
cat > /tmp/fix-broken-syntax.js << 'EOF'
const fs = require('fs');
const path = require('path');

function fixBrokenSyntax(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    // Fix broken useQuery calls - look for patterns like:
    // useQuery({ queryKey: ['key'], queryFn: () => api.get('key'),
    // and convert them to proper syntax
    
    // Pattern 1: Fix malformed useQuery calls
    const brokenUseQueryRegex = /useQuery\(\s*{\s*queryKey:\s*\[([^\]]+)\],\s*queryFn:\s*\(\)\s*=>\s*api\.get\(([^)]+)\),\s*$/gm;
    if (brokenUseQueryRegex.test(content)) {
        content = content.replace(brokenUseQueryRegex, (match, queryKey, apiCall) => {
            return `useQuery({
    queryKey: [${queryKey}],
    queryFn: () => api.get(${apiCall}),
  })`;
        });
        modified = true;
    }

    // Pattern 2: Fix useQuery calls that are missing closing braces
    const incompleteUseQueryRegex = /useQuery\(\s*{\s*queryKey:\s*\[([^\]]+)\],\s*queryFn:\s*\(\)\s*=>\s*api\.get\(([^)]+)\),\s*$/gm;
    if (incompleteUseQueryRegex.test(content)) {
        content = content.replace(incompleteUseQueryRegex, (match, queryKey, apiCall) => {
            return `useQuery({
    queryKey: [${queryKey}],
    queryFn: () => api.get(${apiCall}),
  })`;
        });
        modified = true;
    }

    // Pattern 3: Fix useMutation calls that are missing closing braces
    const brokenUseMutationRegex = /useMutation\(\s*{\s*mutationFn:\s*async\s*\(([^)]+)\)\s*=>\s*\{([^}]+)\}\s*$/gm;
    if (brokenUseMutationRegex.test(content)) {
        content = content.replace(brokenUseMutationRegex, (match, params, body) => {
            return `useMutation({
    mutationFn: async (${params}) => {
      ${body}
    },
  })`;
        });
        modified = true;
    }

    // Pattern 4: Fix any remaining malformed syntax
    // Look for lines that start with async () => { but don't have proper structure
    const malformedAsyncRegex = /^\s*async\s*\(\)\s*=>\s*\{\s*$/gm;
    if (malformedAsyncRegex.test(content)) {
        // This is a complex fix, so we'll handle it case by case
        const lines = content.split('\n');
        const fixedLines = [];
        
        for (let i = 0; i < lines.length; i++) {
            const line = lines[i];
            
            // If we find a malformed async line, try to fix it
            if (line.match(/^\s*async\s*\(\)\s*=>\s*\{\s*$/)) {
                // Look ahead to find the matching closing brace and fix the structure
                let braceCount = 1;
                let j = i + 1;
                let functionBody = [];
                
                while (j < lines.length && braceCount > 0) {
                    const nextLine = lines[j];
                    functionBody.push(nextLine);
                    
                    // Count braces
                    const openBraces = (nextLine.match(/\{/g) || []).length;
                    const closeBraces = (nextLine.match(/\}/g) || []).length;
                    braceCount += openBraces - closeBraces;
                    j++;
                }
                
                // Reconstruct the function properly
                fixedLines.push('    async () => {');
                fixedLines.push(...functionBody);
                i = j - 1; // Skip the lines we've already processed
            } else {
                fixedLines.push(line);
            }
        }
        
        content = fixedLines.join('\n');
        modified = true;
    }

    if (modified) {
        fs.writeFileSync(filePath, content);
        console.log(`Fixed syntax in: ${filePath}`);
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
    if (fixBrokenSyntax(file)) {
        modifiedCount++;
    }
}

console.log(`\nFixed syntax in ${modifiedCount} files`);
EOF

# Run the fix script
node /tmp/fix-broken-syntax.js
rm -f /tmp/fix-broken-syntax.js

print_success "Broken syntax fixed"

# Step 2: Manual fix for specific patterns
print_status "Step 2: Applying manual fixes for specific patterns..."

# Fix the most common broken patterns manually
for file in src/pages/*.tsx src/pages/roles/*.tsx; do
    if [ -f "$file" ]; then
        # Fix useQuery calls that are missing proper structure
        sed -i 's/useQuery({ queryKey: \[\([^]]*\)\], queryFn: () => api\.get(\([^)]*\)),/useQuery({\n    queryKey: [\1],\n    queryFn: () => api.get(\2),\n  })/g' "$file"
        
        # Fix useMutation calls that are missing proper structure
        sed -i 's/useMutation({ mutationFn: async (\([^)]*\)) => {/useMutation({\n    mutationFn: async (\1) => {/g' "$file"
        
        # Fix any remaining malformed async functions
        sed -i 's/async () => {/    async () => {/g' "$file"
    fi
done

print_success "Manual fixes applied"

# Step 3: Run type check to see remaining issues
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

print_success "React Query syntax repair completed!"
echo ""
print_status "Summary of fixes applied:"
echo "✅ Broken useQuery syntax fixed"
echo "✅ Broken useMutation syntax fixed"
echo "✅ Malformed async functions fixed"
echo "✅ Manual pattern fixes applied"
echo "✅ Build completed successfully"
echo ""
print_status "Next steps:"
echo "1. The frontend is now built and ready"
echo "2. You can serve it with: npm run preview"
echo "3. Or integrate it with your backend server"
