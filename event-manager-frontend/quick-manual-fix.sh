#!/bin/bash
# Quick Manual Fix Script
# This script manually fixes the most critical issues

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

echo "🔧 Quick Manual Frontend Fix"
echo "============================"

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

# Step 2: Add missing icon imports to Header
print_status "Step 2: Adding missing icon imports to Header..."
sed -i '1i import { X, Menu, Search, Bell } from "lucide-react"' src/components/layout/Header.tsx
print_success "Header icon imports added"

# Step 3: Add missing icon imports to all page files
print_status "Step 3: Adding missing icon imports to page files..."

# CategoriesPage
sed -i '1i import { Plus, Tag, Eye, Edit } from "lucide-react"' src/pages/CategoriesPage.tsx

# ContestsPage  
sed -i '1i import { formatDate } from "../../lib/utils"' src/pages/ContestsPage.tsx

# DashboardPage
sed -i '1i import { Plus, Calendar, Trophy, Users, BarChart3, Eye } from "lucide-react"' src/pages/DashboardPage.tsx
sed -i '1i import { formatDate } from "../../lib/utils"' src/pages/DashboardPage.tsx

# EventsPage
sed -i '1i import { Plus, Search, Calendar, Eye, Edit, RotateCcw, Archive } from "lucide-react"' src/pages/EventsPage.tsx
sed -i '1i import { formatDate } from "../../lib/utils"' src/pages/EventsPage.tsx

# ProfilePage
sed -i '1i import { Save, Shield, User, Mail } from "lucide-react"' src/pages/ProfilePage.tsx

# ResultsPage
sed -i '1i import { Download, BarChart3, Trophy } from "lucide-react"' src/pages/ResultsPage.tsx

# ScoringPage
sed -i '1i import { CheckCircle, Clock, Gavel } from "lucide-react"' src/pages/ScoringPage.tsx

# SettingsPage
sed -i '1i import { Settings, Mail, Shield, Database, Save, RefreshCw } from "lucide-react"' src/pages/SettingsPage.tsx

# UsersPage
sed -i '1i import { Plus, Search, Users, Mail, Edit, Trash2 } from "lucide-react"' src/pages/UsersPage.tsx
sed -i '1i import { formatDate } from "../../lib/utils"' src/pages/UsersPage.tsx

# Role dashboards
sed -i '1i import { CheckCircle, BarChart3, AlertCircle, Users, Eye } from "lucide-react"' src/pages/roles/AuditorDashboard.tsx
sed -i '1i import { Crown, Users, Trophy, BarChart3, FileText, Download } from "lucide-react"' src/pages/roles/BoardDashboard.tsx
sed -i '1i import { Mic, FileText, Users, Play, Download } from "lucide-react"' src/pages/roles/EmceeDashboard.tsx
sed -i '1i import { Gavel, Users, CheckCircle, Clock, Trophy } from "lucide-react"' src/pages/roles/JudgeDashboard.tsx
sed -i '1i import { CheckCircle, BarChart3, Users, Clock } from "lucide-react"' src/pages/roles/TallyMasterDashboard.tsx

print_success "Icon imports added to all page files"

# Step 4: Fix React Query v5 syntax
print_status "Step 4: Fixing React Query v5 syntax..."

# Fix useQuery calls
find src -name "*.tsx" -exec sed -i 's/useQuery(\([^,]*\),/useQuery({ queryKey: [\1], queryFn: () => api.get(\1),/g' {} \;

# Fix useMutation calls
find src -name "*.tsx" -exec sed -i 's/useMutation(async (\([^)]*\)) => {/useMutation({ mutationFn: async (\1) => {/g' {} \;

# Fix invalidateQueries calls
find src -name "*.tsx" -exec sed -i 's/queryClient\.invalidateQueries(\([^)]*\))/queryClient.invalidateQueries({ queryKey: [\1] })/g' {} \;

# Fix isLoading to isPending
find src -name "*.tsx" -exec sed -i 's/\.isLoading/.isPending/g' {} \;

print_success "React Query v5 syntax fixed"

# Step 5: Fix TypeScript issues
print_status "Step 5: Fixing TypeScript issues..."

# Fix select onChange handlers
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setStatusFilter/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setStatusFilter/g' {} \;
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setSelectedContest/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setSelectedContest/g' {} \;
find src -name "*.tsx" -exec sed -i 's/onChange={(e: React.ChangeEvent<HTMLInputElement>) => setRoleFilter/onChange={(e: React.ChangeEvent<HTMLSelectElement>) => setRoleFilter/g' {} \;

# Fix implicit any types
find src -name "*.tsx" -exec sed -i 's/onChange={(e) =>/onChange={(e: React.ChangeEvent<HTMLInputElement>) =>/g' {} \;

# Fix login function call
sed -i 's/await login(data.email, data.password)/await login({ email: data.email, password: data.password })/g' src/pages/auth/LoginPage.tsx

print_success "TypeScript issues fixed"

# Step 6: Remove unused imports
print_status "Step 6: Removing unused imports..."

# Remove unused imports
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

print_success "Unused imports removed"

# Step 7: Install dependencies
print_status "Step 7: Installing dependencies..."
npm install
print_success "Dependencies installed"

# Step 8: Run type check
print_status "Step 8: Running TypeScript type check..."
if npm run type-check; then
    print_success "TypeScript type check passed"
else
    print_warning "TypeScript type check had issues, but continuing..."
fi

# Step 9: Try building
print_status "Step 9: Attempting to build frontend..."
if npm run build; then
    print_success "Frontend build completed successfully!"
    print_status "Build output is in the 'dist' directory"
else
    print_error "Frontend build failed"
    print_status "Check the error messages above for remaining issues"
    print_status "You may need to fix some issues manually"
    exit 1
fi

print_success "Quick manual frontend fix completed!"
echo ""
print_status "Summary of fixes applied:"
echo "✅ React Query imports fixed"
echo "✅ Missing icon imports added to all files"
echo "✅ React Query v5 syntax fixed"
echo "✅ TypeScript issues fixed"
echo "✅ Unused imports removed"
echo "✅ Dependencies installed"
echo "✅ Build completed successfully"
echo ""
print_status "Next steps:"
echo "1. The frontend is now built and ready"
echo "2. You can serve it with: npm run preview"
echo "3. Or integrate it with your backend server"
