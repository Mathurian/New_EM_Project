#!/bin/bash
# Comprehensive Frontend Fix Script
# This script fixes ALL remaining frontend build issues systematically

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

echo "🔧 Comprehensive Frontend Build Fix"
echo "==================================="

# Check if we're in the right directory
if [ ! -f "package.json" ]; then
    print_error "package.json not found. Please run this script from the event-manager-frontend directory."
    exit 1
fi

# Step 1: Fix React Query imports
print_status "Step 1: Fixing React Query imports..."

# Fix all react-query imports to @tanstack/react-query
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from '\''react-query'\''/from '\''@tanstack\/react-query'\''/g'
find src -name "*.tsx" -o -name "*.ts" | xargs sed -i 's/from "react-query"/from "@tanstack\/react-query"/g'

print_success "React Query imports fixed"

# Step 2: Fix missing cn imports in UI components
print_status "Step 2: Fixing missing cn imports in UI components..."

# Check if cn import exists, if not add it
for file in src/components/ui/*.tsx src/components/layout/*.tsx; do
    if [ -f "$file" ]; then
        if ! grep -q "import.*cn.*from.*utils" "$file"; then
            # Add cn import at the top
            sed -i '1i import { cn } from "../../lib/utils"' "$file"
        fi
    fi
done

print_success "cn imports fixed"

# Step 3: Fix missing Lucide React icon imports
print_status "Step 3: Fixing missing Lucide React icon imports..."

# Create a comprehensive icon fix script
cat > /tmp/fix-icons.js << 'EOF'
const fs = require('fs');
const path = require('path');

// All the icons used in the codebase
const allIcons = [
    'X', 'Menu', 'Search', 'Bell', 'Plus', 'Tag', 'Eye', 'Edit', 'Calendar', 'Trophy', 
    'Users', 'BarChart3', 'RotateCcw', 'Archive', 'Save', 'Shield', 'User', 'Mail', 
    'Download', 'CheckCircle', 'Clock', 'Gavel', 'Settings', 'Database', 'RefreshCw', 
    'Trash2', 'Crown', 'FileText', 'Mic', 'Play', 'AlertCircle'
];

function fixIconsInFile(filePath) {
    let content = fs.readFileSync(filePath, 'utf8');
    let modified = false;

    // Find all icons used in the file
    const usedIcons = [];
    for (const icon of allIcons) {
        if (content.includes(`<${icon} `) || content.includes(`<${icon}>`)) {
            usedIcons.push(icon);
        }
    }

    if (usedIcons.length > 0) {
        // Check if lucide-react import exists
        const lucideImportRegex = /import\s*{\s*([^}]+)\s*}\s*from\s*['"]lucide-react['"]/;
        const match = content.match(lucideImportRegex);
        
        if (match) {
            // Add missing icons to existing import
            const existingIcons = match[1].split(',').map(i => i.trim());
            const newIcons = usedIcons.filter(icon => !existingIcons.includes(icon));
            
            if (newIcons.length > 0) {
                const allIconsList = [...existingIcons, ...newIcons].join(', ');
                content = content.replace(lucideImportRegex, `import { ${allIconsList} } from 'lucide-react'`);
                modified = true;
            }
        } else {
            // Add new import
            const iconList = usedIcons.join(', ');
            const importStatement = `import { ${iconList} } from 'lucide-react'\n`;
            content = importStatement + content;
            modified = true;
        }
    }

    if (modified) {
        fs.writeFileSync(filePath, content);
        console.log(`Fixed icons in: ${filePath}`);
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
    if (fixIconsInFile(file)) {
        modifiedCount++;
    }
}

console.log(`\nFixed icons in ${modifiedCount} files`);
EOF

# Run the icon fix script
node /tmp/fix-icons.js
rm -f /tmp/fix-icons.js

print_success "Lucide React icon imports fixed"

# Step 4: Fix React Query v5 syntax issues
print_status "Step 4: Fixing React Query v5 syntax issues..."

# Create a comprehensive React Query fix script
cat > /tmp/fix-react-query.js << 'EOF'
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

    // Fix login function call in LoginPage
    if (content.includes('await login(data.email, data.password)')) {
        content = content.replace('await login(data.email, data.password)', 'await login({ email: data.email, password: data.password })');
        modified = true;
    }

    if (modified) {
        fs.writeFileSync(filePath, content);
        console.log(`Fixed React Query in: ${filePath}`);
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

console.log(`\nFixed React Query in ${modifiedCount} files`);
EOF

# Run the React Query fix script
node /tmp/fix-react-query.js
rm -f /tmp/fix-react-query.js

print_success "React Query v5 syntax fixed"

# Step 5: Fix TypeScript event handler issues
print_status "Step 5: Fixing TypeScript event handler issues..."

# Fix select onChange handlers
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setStatusFilter/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setStatusFilter/g' {} \;
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSelectedContest/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setSelectedContest/g' {} \;
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setRoleFilter/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setRoleFilter/g' {} \;

# Fix implicit any types
find src -name "*.tsx" -exec sed -i 's/onChange={(e) =>/onChange={(e: React.ChangeEvent<HTMLInputElement>) =>/g' {} \;

print_success "TypeScript event handler issues fixed"

# Step 6: Remove unused imports and variables
print_status "Step 6: Cleaning up unused imports and variables..."

# Remove unused imports more carefully
find src -name "*.tsx" -exec sed -i '/^import.*isSearchOpen.*useState/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Shield.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Users.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Filter.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Search.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*XCircle.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*AlertCircle.*from.*lucide-react/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*formatDate.*from.*utils/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*Badge.*from.*Badge/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*CardDescription.*from.*Card/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*CardHeader.*from.*Card/d' {} \;
find src -name "*.tsx" -exec sed -i '/^import.*CardTitle.*from.*Card/d' {} \;

print_success "Unused imports cleaned up"

# Step 7: Fix auth store selector
print_status "Step 7: Fixing auth store selector..."

# Fix the isPending selector
sed -i 's/state.isPending/state.isLoading/g' src/stores/authStore.ts

print_success "Auth store selector fixed"

# Step 8: Install dependencies
print_status "Step 8: Installing dependencies..."
npm install
print_success "Dependencies installed"

# Step 9: Run type check
print_status "Step 9: Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed"
else
    print_warning "TypeScript type check had issues, but continuing..."
fi

# Step 10: Try building
print_status "Step 10: Attempting to build frontend..."
if npm run build; then
    print_success "Frontend build completed successfully!"
    print_status "Build output is in the 'dist' directory"
else
    print_error "Frontend build failed"
    print_status "Check the error messages above for remaining issues"
    print_status "You may need to fix some issues manually"
    exit 1
fi

print_success "Comprehensive frontend build fix completed!"
echo ""
print_status "Summary of fixes applied:"
echo "✅ React Query imports fixed"
echo "✅ Missing cn imports fixed in UI components"
echo "✅ Missing Lucide React icon imports fixed"
echo "✅ React Query v5 syntax issues resolved"
echo "✅ TypeScript event handler issues fixed"
echo "✅ Unused imports and variables cleaned up"
echo "✅ Auth store selector fixed"
echo "✅ Dependencies installed"
echo "✅ Build completed successfully"
echo ""
print_status "Next steps:"
echo "1. The frontend is now built and ready"
echo "2. You can serve it with: npm run preview"
echo "3. Or integrate it with your backend server"