# Setup Script Documentation

The `setup.sh` script provides comprehensive automation for installing and configuring the Event Manager application. It can install all prerequisites, configure environment variables, set up the database, and deploy the application.

## 🚀 Overview

The setup script offers multiple execution modes:
- **Interactive Mode**: Prompts for configuration choices
- **Non-Interactive Mode**: Fully automated installation
- **Selective Automation**: Choose which steps to automate

## 📋 Prerequisites

### Automatic Installation (Recommended)
The script can install all prerequisites automatically:
- **Node.js 20 LTS** (via NodeSource repository)
- **PostgreSQL 15** with contrib packages
- **Build tools** (build-essential, python3, git, curl)
- **Additional utilities** (jq, htop, tree, vim, nano)

### Manual Prerequisites (Optional)
If you prefer to install prerequisites manually:
- Node.js 18+
- PostgreSQL 12+
- Git
- Build tools (build-essential on Ubuntu)

## 🎯 Execution Modes

### 1. Interactive Mode (Default)
```bash
./setup.sh
```
**Prompts for:**
- Prerequisites installation
- Database configuration
- Application environment
- Email configuration
- PHP cleanup
- Development server startup

### 2. Non-Interactive Mode (Fully Automated)
```bash
./setup.sh --non-interactive
```
**Automatically:**
- ✅ Installs all prerequisites
- ✅ Sets up database (migrate + seed)
- ✅ Removes PHP files
- ✅ Uses default configuration
- ❌ Does NOT start servers (user choice)

### 3. Selective Automation
```bash
# Install prerequisites automatically, but prompt for other steps
./setup.sh --auto-install-prereqs

# Install prerequisites and setup database automatically
./setup.sh --auto-install-prereqs --auto-setup-db

# Full automation except starting servers
./setup.sh --auto-install-prereqs --auto-setup-db --auto-cleanup-php
```

## 🔧 Command Line Options

### Installation Options
| Option | Description |
|--------|-------------|
| `--auto-install-prereqs` | Automatically install Node.js, PostgreSQL, build tools |
| `--auto-setup-db` | Automatically run database migrations and seed data |
| `--auto-cleanup-php` | Automatically remove old PHP files |
| `--auto-create-installer` | Automatically create minimal installer script |
| `--auto-start-servers` | Automatically start development servers |
| `--non-interactive` | Run in fully automated mode (no prompts) |
| `--skip-env-config` | Skip environment variable configuration |

### Database Configuration
| Option | Default | Description |
|--------|---------|-------------|
| `--db-host=HOST` | localhost | Database server hostname |
| `--db-port=PORT` | 5432 | Database server port |
| `--db-name=NAME` | event_manager | Database name |
| `--db-user=USER` | event_manager | Database username |
| `--db-password=PASS` | password | Database password |

### Application Configuration
| Option | Default | Description |
|--------|---------|-------------|
| `--jwt-secret=SECRET` | auto-generated | JWT signing secret |
| `--session-secret=SECRET` | auto-generated | Session encryption secret |
| `--app-env=ENV` | development | Application environment |
| `--app-url=URL` | http://localhost:3001 | Application base URL |

### Email Configuration
| Option | Default | Description |
|--------|---------|-------------|
| `--smtp-host=HOST` | (empty) | SMTP server hostname |
| `--smtp-port=PORT` | 587 | SMTP server port |
| `--smtp-user=USER` | (empty) | SMTP username |
| `--smtp-pass=PASS` | (empty) | SMTP password |
| `--smtp-from=EMAIL` | noreply@eventmanager.com | From email address |

### General Options
| Option | Description |
|--------|-------------|
| `--help` | Show help information and exit |

## 🚀 Usage Examples

### Development Environment
```bash
# Interactive setup (recommended for development)
./setup.sh

# Quick automated setup
./setup.sh --non-interactive
```

### Production Deployment
```bash
# Production with custom database
./setup.sh \
  --non-interactive \
  --db-host=prod-db.example.com \
  --db-password=secure-password \
  --jwt-secret=production-jwt-secret \
  --session-secret=production-session-secret \
  --app-env=production \
  --app-url=https://eventmanager.example.com

# Production with email configuration
./setup.sh \
  --non-interactive \
  --app-env=production \
  --smtp-host=smtp.gmail.com \
  --smtp-user=admin@eventmanager.com \
  --smtp-pass=app-password \
  --smtp-from=noreply@eventmanager.com
```

### CI/CD Pipeline
```bash
# Fully automated for CI/CD
./setup.sh --non-interactive

# With custom configuration
./setup.sh \
  --non-interactive \
  --db-host=$DB_HOST \
  --db-password=$DB_PASSWORD \
  --jwt-secret=$JWT_SECRET \
  --app-env=production
```

### Docker Environment
```bash
# In Dockerfile
RUN ./setup.sh --non-interactive

# With environment variables
RUN ./setup.sh \
  --non-interactive \
  --db-host=postgres \
  --app-env=production
```

### Server Provisioning
```bash
# Remote server setup
ssh user@server "curl -fsSL https://your-repo.com/setup.sh | bash -s -- --non-interactive"

# With custom configuration
ssh user@server "curl -fsSL https://your-repo.com/setup.sh | bash -s -- --non-interactive --db-password=secret123"
```

## 🔐 Security Features

### Automatic Secret Generation
The script automatically generates secure secrets if not provided:
- **JWT Secret**: 32-byte base64-encoded random string
- **Session Secret**: 32-byte base64-encoded random string
- **Uses OpenSSL** if available, falls back to `/dev/urandom`

### Production-Ready Defaults
- **Strong password hashing** (12 rounds)
- **Rate limiting** enabled (100 requests per 15 minutes)
- **Secure session timeout** (30 minutes)
- **Environment-specific** configurations

## 📁 Generated Files

### Backend `.env` File
```bash
# Environment Configuration
NODE_ENV=development
PORT=3000

# Database Configuration
DATABASE_URL="postgresql://event_manager:password@localhost:5432/event_manager?schema=public"

# JWT Configuration
JWT_SECRET=generated-secret-here
JWT_EXPIRES_IN=24h

# Redis Configuration
REDIS_URL=redis://localhost:6379

# Security Configuration
BCRYPT_ROUNDS=12
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100

# Email Configuration
SMTP_HOST=smtp.example.com
SMTP_PORT=587
SMTP_USER=user@example.com
SMTP_PASS=password
SMTP_FROM=noreply@eventmanager.com

# Session Configuration
SESSION_SECRET=generated-session-secret
SESSION_TIMEOUT=1800000
```

### Frontend `.env` File
```bash
# Environment Configuration for Frontend
VITE_API_URL=http://localhost:3000
VITE_APP_NAME=Event Manager
VITE_APP_VERSION=1.0.0
VITE_APP_URL=http://localhost:3001
```

## 🔧 Environment Variable Priority

1. **Command line arguments** (highest priority)
2. **Interactive prompts** (if not provided via CLI)
3. **Default values** (lowest priority)

## 🛠 What Gets Installed

### Ubuntu 24.04 Packages
```bash
# System packages
build-essential curl wget git python3 python3-pip python3-dev
libpq-dev pkg-config software-properties-common
apt-transport-https ca-certificates gnupg lsb-release

# Node.js 20 LTS
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# PostgreSQL 15
sudo apt install -y postgresql postgresql-contrib

# Additional tools
jq htop tree unzip zip vim nano
```

### macOS Packages (via Homebrew)
```bash
# Install Homebrew (if not present)
/bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"

# Install packages
brew install node postgresql

# Start services
brew services start postgresql
```

## 🗄 Database Setup

### Automatic Database Configuration
The script automatically:
1. **Creates database user** with specified credentials
2. **Creates database** with proper ownership
3. **Grants all privileges** to the database user
4. **Starts and enables** PostgreSQL service
5. **Runs migrations** to create tables
6. **Seeds database** with initial data

### Database Commands
```bash
# Start PostgreSQL (Ubuntu)
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Start PostgreSQL (macOS)
brew services start postgresql

# Connect to database
sudo -u postgres psql  # Ubuntu
psql postgres          # macOS

# Run migrations manually
npm run migrate

# Seed database manually
npm run seed
```

## 🔍 Troubleshooting

### Common Issues

#### Permission Denied
```bash
# Make script executable
chmod +x setup.sh

# Run with proper permissions
./setup.sh
```

#### Node.js Installation Failed
```bash
# Install Node.js manually
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs

# Or use NVM
curl -o- https://raw.githubusercontent.com/nvm-sh/nvm/v0.39.7/install.sh | bash
nvm install --lts
nvm use --lts
```

#### Database Connection Issues
```bash
# Check PostgreSQL status
sudo systemctl status postgresql  # Ubuntu
brew services list | grep postgres  # macOS

# Start PostgreSQL
sudo systemctl start postgresql  # Ubuntu
brew services start postgresql   # macOS

# Check database connection
sudo -u postgres psql -c "SELECT version();"
```

#### Port Conflicts
```bash
# Check port usage
netstat -tulpn | grep :3000
netstat -tulpn | grep :5432

# Use different ports
./setup.sh --db-port=5433
```

### Error Messages

#### "Node.js version 18+ is required"
```bash
# Install Node.js 18+
./setup.sh --auto-install-prereqs

# Or install manually
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt install -y nodejs
```

#### "PostgreSQL is not installed"
```bash
# Install PostgreSQL
./setup.sh --auto-install-prereqs

# Or install manually
sudo apt install -y postgresql postgresql-contrib
```

#### "Database connection failed"
```bash
# Check database configuration
./setup.sh --db-host=localhost --db-password=password

# Or check PostgreSQL status
sudo systemctl status postgresql
```

## 📊 Performance & Resource Usage

### Installation Time
- **Prerequisites**: 2-5 minutes (depending on internet speed)
- **Application setup**: 1-2 minutes
- **Database setup**: 30 seconds
- **Total**: 3-7 minutes

### Resource Requirements
- **RAM**: 2GB+ recommended
- **Disk**: 1GB+ for installation
- **CPU**: Any modern processor
- **Network**: Internet connection for package downloads

## 🚀 Advanced Usage

### Custom Environment Files
```bash
# Create custom environment
cat > custom.env << EOF
DB_HOST=custom-db.example.com
DB_PASSWORD=custom-password
JWT_SECRET=custom-jwt-secret
APP_ENV=production
EOF

# Use custom environment
./setup.sh --non-interactive --env-file=custom.env
```

### Minimal Installer Creation
```bash
# Create minimal installer for distribution
./setup.sh --auto-create-installer

# This creates install.sh that downloads and runs the full setup
```

### Integration with CI/CD
```yaml
# GitHub Actions example
- name: Setup Event Manager
  run: |
    chmod +x setup.sh
    ./setup.sh --non-interactive --app-env=production
```

## 📚 Additional Resources

### Help Information
```bash
# Show help
./setup.sh --help

# Show version
./setup.sh --version
```

### Log Files
The script creates log files for troubleshooting:
- **Setup logs**: `/tmp/setup.log`
- **Application logs**: `logs/event-manager.log`
- **Database logs**: PostgreSQL system logs

### Support
- **Documentation**: This file and main README.md
- **Issues**: GitHub Issues for bug reports
- **Setup Help**: Run `./setup.sh --help` for options

---

For more information, see the main [README.md](README.md) file or run `./setup.sh --help` for command options.
