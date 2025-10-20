# Troubleshooting Guide

This guide addresses common issues encountered during Event Manager installation and setup.

## 🔧 **NPM Dependency Issues**

### **Problem**: Deprecated Package Warnings
```
npm warn deprecated inflight@1.0.6: This module is not supported, and leaks memory
npm warn deprecated glob@7.2.3: Glob versions prior to v9 are no longer supported
npm warn deprecated rimraf@3.0.2: Rimraf versions prior to v4 are no longer supported
npm warn deprecated @humanwhocodes/object-schema@2.0.3: Use @eslint/object-schema instead
npm warn deprecated @humanwhocodes/config-array@0.13.0: Use @eslint/config-array instead
npm warn deprecated eslint@8.57.1: This version is no longer supported
```

### **Solution**: Updated Package Configuration

The package.json files have been updated with:

#### **Backend (`package.json`)**
```json
{
  "engines": {
    "node": ">=18.0.0",
    "npm": ">=9.0.0"
  },
  "overrides": {
    "glob": "^10.3.10",
    "rimraf": "^5.0.5",
    "inflight": "npm:lru-cache@^10.0.0"
  }
}
```

#### **Frontend (`frontend/package.json`)**
```json
{
  "devDependencies": {
    "eslint": "^9.0.0",
    "@eslint/js": "^9.0.0"
  },
  "overrides": {
    "glob": "^10.3.10",
    "rimraf": "^5.0.5",
    "inflight": "npm:lru-cache@^10.0.0",
    "@humanwhocodes/object-schema": "npm:@eslint/object-schema@^0.1.0",
    "@humanwhocodes/config-array": "npm:@eslint/config-array@^0.18.0"
  }
}
```

### **Installation Commands**
```bash
# Clean install with updated dependencies
rm -rf node_modules package-lock.json
npm install

# For frontend
cd frontend
rm -rf node_modules package-lock.json
npm install
cd ..
```

## 🗄 **Database Connection Issues**

### **Problem**: Database Connection Failed
```
[ERROR] Cannot connect to database. Please check your credentials in .env
```

### **Root Cause**
The setup script was parsing DATABASE_URL from the .env file instead of using the variables set earlier in the script execution.

### **Solution**: Fixed Variable Usage

The setup script now properly uses the global variables set during argument parsing:

```bash
# Before (incorrect)
DB_USER=$(echo $DATABASE_URL | sed -n 's/.*:\/\/\([^:]*\):.*/\1/p')
DB_PASS=$(echo $DATABASE_URL | sed -n 's/.*:\/\/[^:]*:\([^@]*\)@.*/\1/p')

# After (correct)
# Use the global variables set earlier in the script
DB_PASS=$DB_PASSWORD
```

### **Enhanced Error Reporting**
The script now provides detailed troubleshooting information:

```bash
# Test database connection with better error reporting
if PGPASSWORD="$DB_PASS" psql -h "$DB_HOST" -p "$DB_PORT" -U "$DB_USER" -d "$DB_NAME" -c '\q' 2>/dev/null; then
    print_success "Database connection successful"
else
    print_error "Cannot connect to database. Please check your credentials and ensure PostgreSQL is running."
    print_status "Troubleshooting steps:"
    print_status "  1. Check if PostgreSQL is running: sudo systemctl status postgresql"
    print_status "  2. Verify database exists: sudo -u postgres psql -c '\\l'"
    print_status "  3. Check user permissions: sudo -u postgres psql -c '\\du'"
    print_status "  4. Test connection manually: psql -h $DB_HOST -p $DB_PORT -U $DB_USER -d $DB_NAME"
    exit 1
fi
```

## 📦 **NPM Version Issues**

### **Problem**: Using Outdated NPM
The setup script now ensures the latest NPM version is used.

### **Solution**: Automatic NPM Updates

#### **Ubuntu Installation**
```bash
# Install Node.js 20 LTS via NodeSource
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Update npm to latest version
sudo npm install -g npm@latest
```

#### **macOS Installation**
```bash
# Install Node.js via Homebrew
brew install node

# Update npm to latest version
npm install -g npm@latest
```

#### **Version Checking**
```bash
# Check npm version and update if needed
NPM_VERSION=$(npm -v | cut -d'.' -f1)
if [ "$NPM_VERSION" -lt 9 ]; then
    print_warning "npm version is older than 9. Current version: $(npm -v)"
    print_status "Updating npm to latest version..."
    npm install -g npm@latest
    print_success "npm updated to $(npm -v)"
else
    print_success "npm $(npm -v) is installed"
fi
```

## 🚀 **Complete Fix Installation**

### **Step 1: Update Dependencies**
```bash
# Backend
rm -rf node_modules package-lock.json
npm install

# Frontend
cd frontend
rm -rf node_modules package-lock.json
npm install
cd ..
```

### **Step 2: Update Setup Script**
```bash
# The setup script has been updated with:
# - Fixed database variable usage
# - Enhanced error reporting
# - Automatic npm updates
# - Better troubleshooting information
```

### **Step 3: Run Setup**
```bash
# Interactive setup
./setup.sh

# Or automated setup
./setup.sh --non-interactive
```

## 🔍 **Manual Troubleshooting**

### **Database Connection Issues**
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Start PostgreSQL if not running
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Check if database exists
sudo -u postgres psql -c '\l'

# Check user permissions
sudo -u postgres psql -c '\du'

# Test connection manually
psql -h localhost -p 5432 -U event_manager -d event_manager
```

### **NPM Issues**
```bash
# Check npm version
npm -v

# Update npm globally
npm install -g npm@latest

# Clear npm cache
npm cache clean --force

# Use legacy peer deps if needed
npm install --legacy-peer-deps
```

### **Node.js Issues**
```bash
# Check Node.js version
node -v

# Update Node.js via NVM (recommended)
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
source ~/.bashrc
nvm install --lts
nvm use --lts
```

## 📊 **Performance Improvements**

### **Memory Leak Fix**
- **Replaced `inflight`** with `lru-cache` to prevent memory leaks
- **Updated `glob`** to version 10+ for better performance
- **Updated `rimraf`** to version 5+ for improved file operations

### **Security Improvements**
- **Updated ESLint** to version 9+ with modern configuration
- **Replaced deprecated packages** with maintained alternatives
- **Added package overrides** to force correct versions

### **Dependency Resolution**
- **Added `overrides`** section to package.json files
- **Specified minimum npm version** (9.0.0+)
- **Updated all major dependencies** to latest stable versions

## ✅ **Verification Steps**

### **Check Installation**
```bash
# Verify Node.js and npm versions
node -v  # Should be 18+
npm -v   # Should be 9+

# Check PostgreSQL
psql --version

# Test database connection
psql -h localhost -p 5432 -U event_manager -d event_manager -c 'SELECT version();'
```

### **Run Application**
```bash
# Start backend
npm run dev

# In another terminal, start frontend
cd frontend
npm run dev
```

### **Access Application**
- **Frontend**: http://localhost:3001
- **Backend API**: http://localhost:3000
- **Default Login**: admin@eventmanager.com / admin123

## 🆘 **Additional Support**

If you continue to experience issues:

1. **Check Logs**: Review application logs for specific error messages
2. **Verify Prerequisites**: Ensure all system requirements are met
3. **Clean Installation**: Remove all node_modules and package-lock.json files
4. **Update System**: Ensure your operating system is up to date
5. **Check Permissions**: Verify file and directory permissions

For more help, see:
- [README.md](README.md) - Main documentation
- [DOCKER.md](DOCKER.md) - Docker deployment guide
- [SETUP.md](SETUP.md) - Setup script documentation
