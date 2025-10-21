#!/bin/bash

# Event Manager Complete Setup Script
# One-command installation for fully functional web application
# Includes: Node.js, PostgreSQL, Nginx, SSL certificates, systemd service

set -e

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Application configuration
APP_NAME="event-manager"
APP_DIR="/var/www/$APP_NAME"
NODE_VERSION="20.19.5"
DB_NAME="event_manager"
DB_USER="event_manager"
DB_PASSWORD="password"
JWT_SECRET=""
SESSION_SECRET=""
APP_ENV="production"
APP_URL=""
DOMAIN=""
EMAIL=""
WEB_SERVER_USER="www-data"

# Installation flags
AUTO_INSTALL_PREREQS=false
AUTO_SETUP_DB=false
AUTO_CLEANUP_PHP=false
AUTO_CREATE_INSTALLER=false
AUTO_START_SERVERS=false
NON_INTERACTIVE=false
SKIP_ENV_CONFIG=false
SKIP_WEB_SERVER_PERMISSIONS=false
AUTO_SETUP_PERMISSIONS=false
INSTALL_NGINX=true
INSTALL_SSL=true
USE_PM2=false

# Print functions
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

# Check if running as root
check_root() {
    if [[ $EUID -eq 0 ]]; then
        print_error "This script should not be run as root. Please run as a regular user with sudo privileges."
        exit 1
    fi
    
    # Check if user has sudo privileges
    if ! sudo -n true 2>/dev/null; then
        print_error "This script requires sudo privileges. Please ensure your user can run sudo commands."
        exit 1
    fi
}

# Detect operating system
detect_os() {
    if [[ "$OSTYPE" == "linux-gnu"* ]]; then
        if command -v lsb_release &> /dev/null; then
            OS_DISTRO=$(lsb_release -si)
            OS_VERSION=$(lsb_release -sr)
            if [[ "$OS_DISTRO" == "Ubuntu" ]]; then
                OS="ubuntu"
                OS_VERSION_NUM=$(echo $OS_VERSION | cut -d'.' -f1)
                print_status "Detected Ubuntu $OS_VERSION"
            else
                OS="linux"
                print_status "Detected Linux distribution: $OS_DISTRO"
            fi
        else
            OS="linux"
            print_status "Detected Linux (distribution unknown)"
        fi
    elif [[ "$OSTYPE" == "darwin"* ]]; then
        OS="macos"
        print_status "Detected macOS"
    else
        OS="unknown"
        print_warning "Unknown operating system: $OSTYPE"
    fi
}

# Parse command line arguments
parse_args() {
    while [[ $# -gt 0 ]]; do
        case $1 in
            --auto-install-prereqs)
                AUTO_INSTALL_PREREQS=true
                shift
                ;;
            --auto-setup-db)
                AUTO_SETUP_DB=true
                shift
                ;;
            --auto-cleanup-php)
                AUTO_CLEANUP_PHP=true
                shift
                ;;
            --auto-create-installer)
                AUTO_CREATE_INSTALLER=true
                shift
                ;;
            --auto-start-servers)
                AUTO_START_SERVERS=true
                shift
                ;;
            --non-interactive)
                NON_INTERACTIVE=true
                AUTO_INSTALL_PREREQS=true
                AUTO_SETUP_DB=true
                AUTO_CLEANUP_PHP=true
                AUTO_SETUP_PERMISSIONS=true
                shift
                ;;
            --skip-env-config)
                SKIP_ENV_CONFIG=true
                shift
                ;;
            --skip-web-server-permissions)
                SKIP_WEB_SERVER_PERMISSIONS=true
                shift
                ;;
            --auto-setup-permissions)
                AUTO_SETUP_PERMISSIONS=true
                shift
                ;;
            --db-host=*)
                DB_HOST="${1#*=}"
                shift
                ;;
            --db-port=*)
                DB_PORT="${1#*=}"
                shift
                ;;
            --db-name=*)
                DB_NAME="${1#*=}"
                shift
                ;;
            --db-user=*)
                DB_USER="${1#*=}"
                shift
                ;;
            --db-password=*)
                DB_PASSWORD="${1#*=}"
                shift
                ;;
            --jwt-secret=*)
                JWT_SECRET="${1#*=}"
                shift
                ;;
            --session-secret=*)
                SESSION_SECRET="${1#*=}"
                shift
                ;;
            --app-env=*)
                APP_ENV="${1#*=}"
                shift
                ;;
            --app-url=*)
                APP_URL="${1#*=}"
                shift
                ;;
            --domain=*)
                DOMAIN="${1#*=}"
                shift
                ;;
            --email=*)
                EMAIL="${1#*=}"
                shift
                ;;
            --web-server-user=*)
                WEB_SERVER_USER="${1#*=}"
                shift
                ;;
            --help)
                show_help
                exit 0
                ;;
            *)
                print_error "Unknown option: $1"
                show_help
                exit 1
                ;;
        esac
    done
}

# Show help information
show_help() {
    echo "Event Manager Complete Setup Script"
    echo "===================================="
    echo ""
    echo "Usage: $0 [OPTIONS]"
    echo ""
    echo "Installation Options:"
    echo "  --auto-install-prereqs    Automatically install Node.js, PostgreSQL, Nginx, build tools"
    echo "  --auto-setup-db           Automatically setup database (migrate + seed)"
    echo "  --auto-cleanup-php        Automatically remove old PHP files"
    echo "  --auto-create-installer   Automatically create minimal installer script"
    echo "  --auto-start-servers      Automatically start development servers"
    echo "  --non-interactive         Run in fully automated mode (no prompts)"
    echo "  --skip-env-config         Skip environment variable configuration"
    echo "  --skip-web-server-permissions  Skip web server permission setup"
    echo "  --auto-setup-permissions  Automatically setup web server permissions"
    echo ""
    echo "Database Configuration:"
    echo "  --db-host=HOST           Database server hostname (default: localhost)"
    echo "  --db-port=PORT           Database server port (default: 5432)"
    echo "  --db-name=NAME           Database name (default: event_manager)"
    echo "  --db-user=USER           Database username (default: event_manager)"
    echo "  --db-password=PASS       Database password (default: password)"
    echo ""
    echo "Application Configuration:"
    echo "  --jwt-secret=SECRET       JWT signing secret (auto-generated if not provided)"
    echo "  --session-secret=SECRET   Session encryption secret (auto-generated if not provided)"
    echo "  --app-env=ENV            Application environment (development/production)"
    echo "  --app-url=URL            Application base URL"
    echo ""
    echo "Web Server Configuration:"
    echo "  --domain=DOMAIN          Domain name for SSL certificate"
    echo "  --email=EMAIL            Email for SSL certificate registration"
    echo "  --web-server-user=USER   Web server user (default: www-data)"
    echo ""
    echo "Examples:"
    echo "  $0                                    # Interactive setup"
    echo "  $0 --non-interactive                  # Fully automated setup"
    echo "  $0 --domain=example.com --email=admin@example.com"
    echo ""
}

# Generate secure secrets
generate_secrets() {
    if [[ -z "$JWT_SECRET" ]]; then
        if command -v openssl &> /dev/null; then
            JWT_SECRET=$(openssl rand -base64 32)
        else
            JWT_SECRET=$(head -c 32 /dev/urandom | base64)
        fi
    fi
    
    if [[ -z "$SESSION_SECRET" ]]; then
        if command -v openssl &> /dev/null; then
            SESSION_SECRET=$(openssl rand -base64 32)
        else
            SESSION_SECRET=$(head -c 32 /dev/urandom | base64)
        fi
    fi
}

# Install prerequisites
install_prerequisites() {
    if [[ "$AUTO_INSTALL_PREREQS" == "false" && "$NON_INTERACTIVE" == "false" ]]; then
        echo ""
        read -p "Install all prerequisites (Node.js, PostgreSQL, Nginx, build tools)? [y/N]: " -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Skipping prerequisite installation"
            return
        fi
    fi
    
    print_status "Installing prerequisites..."
    
    if [[ "$OS" == "ubuntu" ]]; then
        # Update package list
        sudo apt update
        
        # Install essential packages
        sudo apt install -y curl wget git build-essential python3 python3-pip python3-dev \
            libpq-dev pkg-config software-properties-common apt-transport-https \
            ca-certificates gnupg lsb-release jq htop tree unzip zip vim nano
        
        # Install Node.js 20 LTS
        print_status "Installing Node.js $NODE_VERSION..."
        curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
        sudo apt install -y nodejs
        
        # Update npm to latest version
        sudo npm install -g npm@latest
        
        # Install PostgreSQL
        print_status "Installing PostgreSQL..."
        sudo apt install -y postgresql postgresql-contrib
        
        # Install Nginx
        if [[ "$INSTALL_NGINX" == "true" ]]; then
            print_status "Installing Nginx..."
            sudo apt install -y nginx
        fi
        
        # Install Certbot for SSL
        if [[ "$INSTALL_SSL" == "true" ]]; then
            print_status "Installing Certbot for SSL certificates..."
            sudo apt install -y certbot python3-certbot-nginx
        fi
        
        # Start and enable services
        sudo systemctl start postgresql
        sudo systemctl enable postgresql
        
        if [[ "$INSTALL_NGINX" == "true" ]]; then
            sudo systemctl start nginx
            sudo systemctl enable nginx
        fi
        
    elif [[ "$OS" == "macos" ]]; then
        # Check if Homebrew is installed
        if ! command -v brew &> /dev/null; then
            print_status "Installing Homebrew..."
            /bin/bash -c "$(curl -fsSL https://raw.githubusercontent.com/Homebrew/install/HEAD/install.sh)"
        fi
        
        # Install packages
        brew install node postgresql
        
        # Start PostgreSQL
        brew services start postgresql
        
    else
        print_error "Unsupported operating system: $OS"
        exit 1
    fi
    
    # Check Node.js version
    NODE_VERSION_INSTALLED=$(node -v | cut -d'v' -f2)
    NODE_VERSION_NUM=$(echo $NODE_VERSION_INSTALLED | cut -d'.' -f1)
    
    if [[ $NODE_VERSION_NUM -lt 18 ]]; then
        print_error "Node.js version 18+ is required. Current version: $NODE_VERSION_INSTALLED"
        exit 1
    fi
    
    print_success "Prerequisites installed successfully"
    print_status "Node.js: $(node -v)"
    print_status "npm: $(npm -v)"
    print_status "PostgreSQL: $(psql --version)"
}

# Create application directory and set permissions
setup_application_directory() {
    print_status "Setting up application directory..."
    
    # Create application directory
    sudo mkdir -p "$APP_DIR"
    
    # Copy current directory contents to application directory
    sudo cp -r . "$APP_DIR/"
    
    # Set ownership to current user for initial setup
    sudo chown -R "$(whoami):$(whoami)" "$APP_DIR"
    
    print_success "Application directory created at $APP_DIR"
}

# Create essential backend files
create_backend_files() {
    print_status "Creating essential backend files..."
    
    # Create directories
    mkdir -p "$APP_DIR/src/database" "$APP_DIR/src/controllers" "$APP_DIR/src/middleware" \
             "$APP_DIR/src/routes" "$APP_DIR/src/socket" "$APP_DIR/src/utils" "$APP_DIR/prisma"
    
    # Create Prisma schema (overwrite if exists to ensure correct relations)
    print_status "Creating Prisma schema..."
        cat > "$APP_DIR/prisma/schema.prisma" << 'EOF'
// This is your Prisma schema file,
// learn more about it in the docs: https://pris.ly/d/prisma-schema

generator client {
  provider = "prisma-client-js"
}

datasource db {
  provider = "postgresql"
  url      = env("DATABASE_URL")
}

model Event {
  id          String   @id @default(cuid())
  name        String
  description String?
  startDate   DateTime
  endDate     DateTime
  createdAt   DateTime @default(now())
  updatedAt   DateTime @updatedAt

  contests         Contest[]
  archivedEvents   ArchivedEvent[]

  @@map("events")
}

model Contest {
  id          String   @id @default(cuid())
  eventId     String
  name        String
  description String?
  createdAt   DateTime @default(now())
  updatedAt   DateTime @updatedAt

  event      Event      @relation(fields: [eventId], references: [id], onDelete: Cascade)
  categories Category[]
  contestants ContestContestant[]
  judges     ContestJudge[]

  @@map("contests")
}

model Category {
  id          String   @id @default(cuid())
  contestId   String
  name        String
  description String?
  scoreCap    Int?
  createdAt   DateTime @default(now())
  updatedAt   DateTime @updatedAt

  contest      Contest      @relation(fields: [contestId], references: [id], onDelete: Cascade)
  contestants  CategoryContestant[]
  judges       CategoryJudge[]
  criteria     Criterion[]
  scores       Score[]
  comments     JudgeComment[]
  certifications TallyMasterCertification[]
  auditorCertifications AuditorCertification[]
  judgeCertifications JudgeCertification[] @relation("CategoryJudgeCertifications")

  @@map("categories")
}

model Contestant {
  id               String   @id @default(cuid())
  name             String
  email            String?  @unique
  gender           String?
  pronouns         String?
  contestantNumber Int?
  bio              String?
  imagePath        String?
  createdAt        DateTime @default(now())
  updatedAt        DateTime @updatedAt

  users              User[]
  contestContestants ContestContestant[]
  categoryContestants CategoryContestant[]
  scores             Score[]
  comments           JudgeComment[]

  @@map("contestants")
}

model Judge {
  id         String   @id @default(cuid())
  name       String
  email      String?  @unique
  gender     String?
  pronouns   String?
  bio        String?
  imagePath  String?
  isHeadJudge Boolean @default(false)
  createdAt  DateTime @default(now())
  updatedAt  DateTime @updatedAt

  users            User[]
  contestJudges    ContestJudge[]
  categoryJudges   CategoryJudge[]
  scores           Score[]
  comments         JudgeComment[]
  certifications   JudgeCertification[] @relation("JudgeCertifications")

  @@map("judges")
}

model Criterion {
  id         String   @id @default(cuid())
  categoryId String
  name       String
  maxScore   Int
  createdAt  DateTime @default(now())
  updatedAt  DateTime @updatedAt

  category Category @relation(fields: [categoryId], references: [id], onDelete: Cascade)
  scores   Score[]

  @@map("criteria")
}

model Score {
  id           String   @id @default(cuid())
  categoryId   String
  contestantId String
  judgeId      String
  criterionId  String
  score        Int
  createdAt    DateTime @default(now())
  updatedAt    DateTime @updatedAt

  category   Category   @relation(fields: [categoryId], references: [id], onDelete: Cascade)
  contestant Contestant @relation(fields: [contestantId], references: [id], onDelete: Cascade)
  judge      Judge      @relation(fields: [judgeId], references: [id], onDelete: Cascade)
  criterion  Criterion  @relation(fields: [criterionId], references: [id], onDelete: Cascade)

  @@unique([categoryId, contestantId, judgeId, criterionId])
  @@map("scores")
}

model User {
  id             String   @id @default(cuid())
  name           String
  preferredName  String?
  email          String   @unique
  password       String
  role           UserRole
  gender         String?
  pronouns       String?
  judgeId        String?
  contestantId   String?
  sessionVersion Int      @default(1)
  createdAt      DateTime @default(now())
  updatedAt      DateTime @updatedAt

  judge      Judge?      @relation(fields: [judgeId], references: [id])
  contestant Contestant? @relation(fields: [contestantId], references: [id])
  logs       ActivityLog[]
  updatedSettings SystemSetting[] @relation("UserUpdatedSettings")

  @@map("users")
}

model JudgeComment {
  id           String   @id @default(cuid())
  categoryId   String
  contestantId String
  judgeId      String
  comment      String?
  createdAt    DateTime @default(now())

  category   Category   @relation(fields: [categoryId], references: [id], onDelete: Cascade)
  contestant Contestant @relation(fields: [contestantId], references: [id], onDelete: Cascade)
  judge      Judge      @relation(fields: [judgeId], references: [id], onDelete: Cascade)

  @@unique([categoryId, contestantId, judgeId])
  @@map("judge_comments")
}

model JudgeCertification {
  id            String   @id @default(cuid())
  categoryId    String
  judgeId       String
  signatureName String
  certifiedAt   DateTime @default(now())

  category Category @relation("CategoryJudgeCertifications", fields: [categoryId], references: [id], onDelete: Cascade)
  judge    Judge    @relation("JudgeCertifications", fields: [judgeId], references: [id], onDelete: Cascade)

  @@unique([categoryId, judgeId])
  @@map("judge_certifications")
}

model TallyMasterCertification {
  id            String   @id @default(cuid())
  categoryId    String
  signatureName String
  certifiedAt   DateTime @default(now())

  category Category @relation(fields: [categoryId], references: [id], onDelete: Cascade)

  @@unique([categoryId])
  @@map("tally_master_certifications")
}

model AuditorCertification {
  id            String   @id @default(cuid())
  categoryId    String
  signatureName String
  certifiedAt   DateTime @default(now())

  category Category @relation(fields: [categoryId], references: [id], onDelete: Cascade)

  @@unique([categoryId])
  @@map("auditor_certifications")
}

model OverallDeduction {
  id           String   @id @default(cuid())
  categoryId   String
  contestantId String
  deduction    Float
  reason       String?
  createdAt    DateTime @default(now())
  updatedAt    DateTime @updatedAt

  @@unique([categoryId, contestantId])
  @@map("overall_deductions")
}

model ActivityLog {
  id           String   @id @default(cuid())
  userId       String?
  userName     String?
  userRole     String?
  action       String
  resourceType String?
  resourceId   String?
  details      String?
  ipAddress    String?
  userAgent    String?
  logLevel     LogLevel @default(INFO)
  createdAt    DateTime @default(now())

  user User? @relation(fields: [userId], references: [id])

  @@map("activity_logs")
}

model SystemSetting {
  id          String   @id @default(cuid())
  settingKey  String   @unique
  settingValue String
  description String?
  updatedAt   DateTime @updatedAt
  updatedById String?

  updatedBy User? @relation("UserUpdatedSettings", fields: [updatedById], references: [id])

  @@map("system_settings")
}

model EmceeScript {
  id          String   @id @default(cuid())
  eventId     String?
  contestId   String?
  categoryId  String?
  title       String
  content     String
  order       Int?
  createdAt   DateTime @default(now())
  updatedAt   DateTime @updatedAt

  @@map("emcee_scripts")
}

model ArchivedEvent {
  id          String   @id @default(cuid())
  eventId     String
  name        String
  description String?
  startDate   DateTime?
  endDate     DateTime?
  archivedAt  DateTime @default(now())
  archivedById String

  event Event @relation(fields: [eventId], references: [id])

  @@map("archived_events")
}

model CategoryTemplate {
  id          String   @id @default(cuid())
  name        String
  description String?
  createdAt   DateTime @default(now())
  updatedAt   DateTime @updatedAt

  criteria TemplateCriterion[]

  @@map("category_templates")
}

model TemplateCriterion {
  id         String   @id @default(cuid())
  templateId String
  name       String
  maxScore   Int
  createdAt  DateTime @default(now())
  updatedAt  DateTime @updatedAt

  template CategoryTemplate @relation(fields: [templateId], references: [id], onDelete: Cascade)

  @@map("template_criteria")
}

model JudgeScoreRemovalRequest {
  id           String        @id @default(cuid())
  categoryId   String
  contestantId String
  judgeId      String
  reason       String
  status       RequestStatus @default(PENDING)
  requestedAt  DateTime      @default(now())
  reviewedAt   DateTime?
  reviewedById String?

  @@map("judge_score_removal_requests")
}

model ContestContestant {
  contestId    String
  contestantId String

  contest    Contest    @relation(fields: [contestId], references: [id], onDelete: Cascade)
  contestant Contestant @relation(fields: [contestantId], references: [id], onDelete: Cascade)

  @@id([contestId, contestantId])
  @@map("contest_contestants")
}

model ContestJudge {
  contestId String
  judgeId   String

  contest Contest @relation(fields: [contestId], references: [id], onDelete: Cascade)
  judge   Judge   @relation(fields: [judgeId], references: [id], onDelete: Cascade)

  @@id([contestId, judgeId])
  @@map("contest_judges")
}

model CategoryContestant {
  categoryId   String
  contestantId String

  category   Category   @relation(fields: [categoryId], references: [id], onDelete: Cascade)
  contestant Contestant @relation(fields: [contestantId], references: [id], onDelete: Cascade)

  @@id([categoryId, contestantId])
  @@map("category_contestants")
}

model CategoryJudge {
  categoryId String
  judgeId    String

  category Category @relation(fields: [categoryId], references: [id], onDelete: Cascade)
  judge    Judge    @relation(fields: [judgeId], references: [id], onDelete: Cascade)

  @@id([categoryId, judgeId])
  @@map("category_judges")
}

enum UserRole {
  ORGANIZER
  JUDGE
  CONTESTANT
  EMCEE
  TALLY_MASTER
  AUDITOR
  BOARD
}

enum LogLevel {
  ERROR
  WARN
  INFO
  DEBUG
}

    enum RequestStatus {
      PENDING
      APPROVED
      REJECTED
    }
EOF
    
    # Create migration script (overwrite if exists to ensure correct operations)
    print_status "Creating database migration script..."
        cat > "$APP_DIR/src/database/migrate.js" << 'EOF'
const migrate = async () => {
  try {
    console.log('🔄 Running database migrations...')
    
    // Generate Prisma client
    const { execSync } = require('child_process')
    console.log('📦 Generating Prisma client...')
    
    // Set environment to suppress npm warnings
    const env = { ...process.env }
    
    // Try different approaches for Prisma generation
    try {
      execSync('npx prisma generate', { stdio: 'inherit', env })
    } catch (error) {
      console.log('⚠️  npx prisma generate failed, trying alternative...')
      // Try using the full path to prisma
      const path = require('path')
      const prismaPath = path.join(process.cwd(), 'node_modules', '.bin', 'prisma')
      execSync(`node ${prismaPath} generate`, { stdio: 'inherit', env })
    }
    
    // Push schema to database
    console.log('🗄️ Pushing schema to database...')
    try {
      execSync('npx prisma db push', { stdio: 'inherit', env })
    } catch (error) {
      console.log('⚠️  npx prisma db push failed, trying alternative...')
      const path = require('path')
      const prismaPath = path.join(process.cwd(), 'node_modules', '.bin', 'prisma')
      execSync(`node ${prismaPath} db push`, { stdio: 'inherit', env })
    }
    
    console.log('✅ Database migrations completed successfully!')
  } catch (error) {
    console.error('❌ Migration failed:', error)
    console.error('Error details:', error.message)
    process.exit(1)
  }
}

const seed = async () => {
  try {
    // Import Prisma client after generation
    const { PrismaClient } = require('@prisma/client')
    const bcrypt = require('bcryptjs')
    
    const prisma = new PrismaClient()
    
    console.log('🌱 Seeding database with initial data...')
    
    // Create default admin user
    const hashedPassword = await bcrypt.hash('admin123', 12)
    
    const adminUser = await prisma.user.upsert({
      where: { email: 'admin@eventmanager.com' },
      update: {},
      create: {
        name: 'System Administrator',
        preferredName: 'Admin',
        email: 'admin@eventmanager.com',
        password: hashedPassword,
        role: 'ORGANIZER',
        gender: 'Other',
        pronouns: 'they/them'
      }
    })
    
    console.log('✅ Admin user created:', adminUser.email)
    
    // Create sample event
    const sampleEvent = await prisma.event.create({
      data: {
        name: 'Sample Event 2024',
        description: 'A sample event for testing the system',
        startDate: new Date('2024-01-01'),
        endDate: new Date('2024-01-02')
      }
    })
    
    console.log('✅ Sample event created:', sampleEvent.name)
    
    // Create sample contest
    const sampleContest = await prisma.contest.create({
      data: {
        eventId: sampleEvent.id,
        name: 'Sample Contest',
        description: 'A sample contest for testing'
      }
    })
    
    console.log('✅ Sample contest created:', sampleContest.name)
    
    // Create sample category
    const sampleCategory = await prisma.category.create({
      data: {
        contestId: sampleContest.id,
        name: 'Sample Category',
        description: 'A sample category for testing',
        scoreCap: 100
      }
    })
    
    console.log('✅ Sample category created:', sampleCategory.name)
    
    // Create sample contestants (use upsert to avoid duplicates)
    const contestants = await Promise.all([
      prisma.contestant.upsert({
        where: { email: 'john@example.com' },
        update: {},
        create: {
          name: 'John Doe',
          email: 'john@example.com',
          gender: 'Male',
          pronouns: 'he/him',
          contestantNumber: 1,
          bio: 'Sample contestant 1'
        }
      }),
      prisma.contestant.upsert({
        where: { email: 'jane@example.com' },
        update: {},
        create: {
          name: 'Jane Smith',
          email: 'jane@example.com',
          gender: 'Female',
          pronouns: 'she/her',
          contestantNumber: 2,
          bio: 'Sample contestant 2'
        }
      })
    ])
    
    console.log('✅ Sample contestants created:', contestants.length)
    
    // Create sample judges (use upsert to avoid duplicates)
    const judges = await Promise.all([
      prisma.judge.upsert({
        where: { email: 'judge1@example.com' },
        update: {},
        create: {
          name: 'Judge Johnson',
          email: 'judge1@example.com',
          gender: 'Male',
          pronouns: 'he/him',
          isHeadJudge: true,
          bio: 'Head judge'
        }
      }),
      prisma.judge.upsert({
        where: { email: 'judge2@example.com' },
        update: {},
        create: {
          name: 'Judge Williams',
          email: 'judge2@example.com',
          gender: 'Female',
          pronouns: 'she/her',
          isHeadJudge: false,
          bio: 'Assistant judge'
        }
      })
    ])
    
    console.log('✅ Sample judges created:', judges.length)
    
    // Create sample criteria
    const criteria = await Promise.all([
      prisma.criterion.create({
        data: {
          categoryId: sampleCategory.id,
          name: 'Technical Skill',
          maxScore: 40
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: sampleCategory.id,
          name: 'Presentation',
          maxScore: 30
        }
      }),
      prisma.criterion.create({
        data: {
          categoryId: sampleCategory.id,
          name: 'Creativity',
          maxScore: 30
        }
      })
    ])
    
    console.log('✅ Sample criteria created:', criteria.length)
    
    // Add contestants to category
    await Promise.all([
      prisma.categoryContestant.create({
        data: {
          categoryId: sampleCategory.id,
          contestantId: contestants[0].id
        }
      }),
      prisma.categoryContestant.create({
        data: {
          categoryId: sampleCategory.id,
          contestantId: contestants[1].id
        }
      })
    ])
    
    // Add judges to category
    await Promise.all([
      prisma.categoryJudge.create({
        data: {
          categoryId: sampleCategory.id,
          judgeId: judges[0].id
        }
      }),
      prisma.categoryJudge.create({
        data: {
          categoryId: sampleCategory.id,
          judgeId: judges[1].id
        }
      })
    ])
    
    // Create system settings (use upsert to avoid duplicates)
    const settings = await Promise.all([
      prisma.systemSetting.upsert({
        where: { settingKey: 'app_name' },
        update: {},
        create: {
          settingKey: 'app_name',
          settingValue: 'Event Manager',
          description: 'Application name'
        }
      }),
      prisma.systemSetting.upsert({
        where: { settingKey: 'app_version' },
        update: {},
        create: {
          settingKey: 'app_version',
          settingValue: '1.0.0',
          description: 'Application version'
        }
      }),
      prisma.systemSetting.upsert({
        where: { settingKey: 'max_file_size' },
        update: {},
        create: {
          settingKey: 'max_file_size',
          settingValue: '10485760',
          description: 'Maximum file upload size in bytes'
        }
      })
    ])
    
    console.log('✅ System settings created:', settings.length)
    
    console.log('🎉 Database seeding completed successfully!')
    console.log('')
    console.log('📋 Default login credentials:')
    console.log('   Email: admin@eventmanager.com')
    console.log('   Password: admin123')
    
    await prisma.$disconnect()
    
  } catch (error) {
    console.error('❌ Seeding failed:', error)
    process.exit(1)
  }
}

const main = async () => {
  try {
    await migrate()
    await seed()
  } catch (error) {
    console.error('❌ Setup failed:', error)
    process.exit(1)
  }
}

if (require.main === module) {
  main()
}

module.exports = { migrate, seed }
EOF
    
    # Create seed script (overwrite if exists to ensure correct operations)
    print_status "Creating database seed script..."
        cat > "$APP_DIR/src/database/seed.js" << 'EOF'
const { seed } = require('./migrate')

const main = async () => {
  try {
    await seed()
  } catch (error) {
    console.error('❌ Seeding failed:', error)
    process.exit(1)
  }
}

if (require.main === module) {
  main()
}

module.exports = { seed }
EOF
    
    # Create basic server.js if it doesn't exist
    if [[ ! -f "$APP_DIR/src/server.js" ]]; then
        print_status "Creating basic server.js..."
        cat > "$APP_DIR/src/server.js" << 'EOF'
const express = require('express')
const cors = require('cors')
const helmet = require('helmet')
const compression = require('compression')
const morgan = require('morgan')
const rateLimit = require('express-rate-limit')
const { PrismaClient } = require('@prisma/client')

const app = express()
const prisma = new PrismaClient()

// Security middleware
app.use(helmet())
app.use(compression())

// Rate limiting
const limiter = rateLimit({
  windowMs: parseInt(process.env.RATE_LIMIT_WINDOW_MS) || 15 * 60 * 1000, // 15 minutes
  max: parseInt(process.env.RATE_LIMIT_MAX_REQUESTS) || 100, // limit each IP to 100 requests per windowMs
  message: 'Too many requests from this IP, please try again later.'
})
app.use(limiter)

// CORS configuration
app.use(cors({
  origin: process.env.FRONTEND_URL || 'http://localhost:3001',
  credentials: true
}))

// Body parsing middleware
app.use(express.json({ limit: '10mb' }))
app.use(express.urlencoded({ extended: true, limit: '10mb' }))

// Logging middleware
if (process.env.NODE_ENV !== 'test') {
  app.use(morgan('combined'))
}

// Health check endpoint
app.get('/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    timestamp: new Date().toISOString(),
    uptime: process.uptime(),
    environment: process.env.NODE_ENV || 'development'
  })
})

// Basic API routes
app.get('/api/health', (req, res) => {
  res.json({ 
    status: 'OK', 
    message: 'Event Manager API is running',
    timestamp: new Date().toISOString()
  })
})

// Error handling middleware
app.use((err, req, res, next) => {
  console.error('Error:', err)
  res.status(500).json({ 
    error: 'Internal server error',
    message: process.env.NODE_ENV === 'development' ? err.message : 'Something went wrong'
  })
})

// 404 handler
app.use('*', (req, res) => {
  res.status(404).json({ error: 'Route not found' })
})

const PORT = process.env.PORT || 3000

const server = app.listen(PORT, () => {
  console.log(`🚀 Event Manager API server running on port ${PORT}`)
  console.log(`📊 Environment: ${process.env.NODE_ENV || 'development'}`)
  console.log(`🔗 Health check: http://localhost:${PORT}/health`)
})

// Graceful shutdown
process.on('SIGTERM', async () => {
  console.log('SIGTERM received, shutting down gracefully')
  server.close(async () => {
    await prisma.$disconnect()
    process.exit(0)
  })
})

process.on('SIGINT', async () => {
  console.log('SIGINT received, shutting down gracefully')
  server.close(async () => {
    await prisma.$disconnect()
    process.exit(0)
  })
})

module.exports = app
EOF
    fi
    
    print_success "Essential backend files created!"
}

# Setup environment variables
setup_environment() {
    if [[ "$SKIP_ENV_CONFIG" == "true" ]]; then
        return
    fi
    
    print_status "Setting up environment variables..."
    
    # Generate secrets if not provided
    generate_secrets
    
    # Set default values if not provided
    if [[ -z "$APP_URL" ]]; then
        if [[ -n "$DOMAIN" ]]; then
            APP_URL="https://$DOMAIN"
        else
            APP_URL="http://localhost:3001"
        fi
    fi
    
    # Create backend .env file
    cat > "$APP_DIR/.env" << EOF
# Environment Configuration
NODE_ENV=$APP_ENV
PORT=3000

# Database Configuration
DATABASE_URL="postgresql://$DB_USER:$DB_PASSWORD@localhost:5432/$DB_NAME?schema=public"

# JWT Configuration
JWT_SECRET=$JWT_SECRET
JWT_EXPIRES_IN=24h

# Redis Configuration
REDIS_URL=redis://localhost:6379

# File Upload Configuration
UPLOAD_DIR=uploads
MAX_FILE_SIZE=10485760

# Security Configuration
BCRYPT_ROUNDS=12
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100

# Logging Configuration
LOG_LEVEL=info
LOG_FILE=logs/event-manager.log

# Email Configuration (optional)
SMTP_HOST=localhost
SMTP_PORT=587
SMTP_USER=
SMTP_PASS=
SMTP_FROM=noreply@eventmanager.com

# Session Configuration
SESSION_SECRET=$SESSION_SECRET
SESSION_TIMEOUT=1800000

# Application Configuration
APP_URL=$APP_URL
FRONTEND_URL=$APP_URL
EOF
    
    # Create frontend .env file
    cat > "$APP_DIR/frontend/.env" << EOF
# Environment Configuration for Frontend
VITE_API_URL=http://localhost:3000
VITE_APP_NAME=Event Manager
VITE_APP_VERSION=1.0.0
VITE_APP_URL=$APP_URL
EOF
    
    # Secure environment files
    chmod 600 "$APP_DIR/.env" "$APP_DIR/frontend/.env"
    
    print_success "Environment variables configured"
}

# Setup database
setup_database() {
    if [[ "$AUTO_SETUP_DB" == "false" && "$NON_INTERACTIVE" == "false" ]]; then
        echo ""
        read -p "Setup database (create user, database, migrate, seed)? [y/N]: " -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Skipping database setup"
            return
        fi
    fi
    
    print_status "Setting up database..."
    
    # Create essential backend files first
    create_backend_files
    
    # Create database user and database
    sudo -u postgres psql << EOF
-- Create user if it doesn't exist
DO \$\$
BEGIN
    IF NOT EXISTS (SELECT FROM pg_catalog.pg_roles WHERE rolname = '$DB_USER') THEN
        CREATE ROLE $DB_USER WITH LOGIN PASSWORD '$DB_PASSWORD';
    END IF;
END
\$\$;

-- Create database if it doesn't exist
SELECT 'CREATE DATABASE $DB_NAME'
WHERE NOT EXISTS (SELECT FROM pg_database WHERE datname = '$DB_NAME')\gexec

-- Grant privileges
GRANT ALL PRIVILEGES ON DATABASE $DB_NAME TO $DB_USER;
GRANT ALL PRIVILEGES ON SCHEMA public TO $DB_USER;
GRANT ALL PRIVILEGES ON ALL TABLES IN SCHEMA public TO $DB_USER;
GRANT ALL PRIVILEGES ON ALL SEQUENCES IN SCHEMA public TO $DB_USER;

-- Set default privileges for future objects
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON TABLES TO $DB_USER;
ALTER DEFAULT PRIVILEGES IN SCHEMA public GRANT ALL ON SEQUENCES TO $DB_USER;
EOF
    
    # Create .npmrc to handle permission issues
    cat > "$APP_DIR/.npmrc" << EOF
legacy-peer-deps=true
audit-level=moderate
fund=false
EOF
    
    # Also create global .npmrc to suppress warnings
    cat > ~/.npmrc << EOF
legacy-peer-deps=true
audit-level=moderate
fund=false
EOF
    
    # Install dependencies
    print_status "Installing Node.js dependencies..."
    cd "$APP_DIR"
    npm install --no-fund --no-audit
    
    # Make Node.js binaries executable before running migrations
    if [[ -d "$APP_DIR/node_modules/.bin" ]]; then
        chmod +x "$APP_DIR/node_modules/.bin"/*
        print_status "Fixed Node.js binary permissions for migration"
    fi
    
    # Clean up any existing Prisma client files
    print_status "Cleaning up existing Prisma client files..."
    rm -rf "$APP_DIR/node_modules/.prisma" 2>/dev/null || true
    rm -rf "$APP_DIR/node_modules/@prisma/client" 2>/dev/null || true
    
    # Make Prisma engine binaries executable
    print_status "Setting Prisma engine binary permissions..."
    if [[ -d "$APP_DIR/node_modules/@prisma/engines" ]]; then
        chmod +x "$APP_DIR/node_modules/@prisma/engines"/*
        print_status "Fixed Prisma engine binary permissions"
    fi
    
    # Validate Prisma schema first
    print_status "Validating Prisma schema..."
    if ! npx prisma validate; then
        print_error "Prisma schema validation failed. Please check the schema file."
        exit 1
    fi
    
    # Run migrations
    print_status "Running database migrations..."
    npm run migrate
    
    print_success "Database setup complete"
}

# Setup web server permissions
setup_permissions() {
    if [[ "$SKIP_WEB_SERVER_PERMISSIONS" == "true" ]]; then
        return
    fi
    
    if [[ "$AUTO_SETUP_PERMISSIONS" == "false" && "$NON_INTERACTIVE" == "false" ]]; then
        echo ""
        read -p "Setup web server permissions? [y/N]: " -n 1 -r
        echo ""
        if [[ ! $REPLY =~ ^[Yy]$ ]]; then
            print_status "Skipping web server permission setup"
            return
        fi
    fi
    
    print_status "Setting up web server permissions..."
    
    # Set ownership to web server user
    sudo chown -R "$WEB_SERVER_USER:$WEB_SERVER_USER" "$APP_DIR"
    
    # Set directory permissions (755)
    sudo find "$APP_DIR" -type d -exec chmod 755 {} \;
    
    # Set file permissions (644)
    sudo find "$APP_DIR" -type f -exec chmod 644 {} \;
    
    # Make Node.js binaries executable (fixes Prisma permission issues)
    if [[ -d "$APP_DIR/node_modules/.bin" ]]; then
        sudo chmod +x "$APP_DIR/node_modules/.bin"/*
        print_status "Fixed Node.js binary permissions"
    fi
    
    # Make Prisma engine binaries executable
    if [[ -d "$APP_DIR/node_modules/@prisma/engines" ]]; then
        sudo chmod +x "$APP_DIR/node_modules/@prisma/engines"/*
        print_status "Fixed Prisma engine binary permissions"
    fi
    
    # Make frontend binaries executable
    if [[ -d "$APP_DIR/frontend/node_modules/.bin" ]]; then
        sudo chmod +x "$APP_DIR/frontend/node_modules/.bin"/*
        print_status "Fixed frontend binary permissions"
    fi
    
    # Secure sensitive files (600)
    sudo chmod 600 "$APP_DIR/.env" "$APP_DIR/frontend/.env"
    
    # Make scripts executable
    sudo chmod 755 "$APP_DIR/setup.sh"
    if [[ -f "$APP_DIR/install.sh" ]]; then
        sudo chmod 755 "$APP_DIR/install.sh"
    fi
    
    # Create uploads and logs directories with proper permissions
    sudo mkdir -p "$APP_DIR/uploads" "$APP_DIR/logs"
    sudo chown -R "$WEB_SERVER_USER:$WEB_SERVER_USER" "$APP_DIR/uploads" "$APP_DIR/logs"
    sudo chmod 755 "$APP_DIR/uploads" "$APP_DIR/logs"
    
    print_success "Web server permissions configured"
}

# Check for PM2 and prompt user
check_pm2() {
    if command -v pm2 &> /dev/null; then
        if [[ "$NON_INTERACTIVE" == "false" ]]; then
            echo ""
            read -p "PM2 is installed. Use PM2 for process management? [y/N]: " -n 1 -r
            echo ""
            if [[ $REPLY =~ ^[Yy]$ ]]; then
                USE_PM2=true
            fi
        fi
    fi
}

# Setup systemd service
setup_systemd_service() {
    if [[ "$USE_PM2" == "true" ]]; then
        print_status "Setting up PM2 process management..."
        
        # Create PM2 ecosystem file
        cat > "$APP_DIR/ecosystem.config.js" << EOF
module.exports = {
  apps: [{
    name: '$APP_NAME',
    script: 'src/server.js',
    cwd: '$APP_DIR',
    user: '$WEB_SERVER_USER',
    instances: 'max',
    exec_mode: 'cluster',
    env: {
      NODE_ENV: '$APP_ENV',
      PORT: 3000
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_file: './logs/combined.log',
    time: true
  }]
}
EOF
        
        # Start with PM2
        sudo -u "$WEB_SERVER_USER" pm2 start "$APP_DIR/ecosystem.config.js"
        sudo -u "$WEB_SERVER_USER" pm2 save
        sudo -u "$WEB_SERVER_USER" pm2 startup
        
    else
        print_status "Setting up systemd service..."
        
        # Create systemd service file
        sudo tee /etc/systemd/system/$APP_NAME.service > /dev/null << EOF
[Unit]
Description=$APP_NAME Node.js Application
After=network.target postgresql.service

[Service]
Type=simple
User=$WEB_SERVER_USER
Group=$WEB_SERVER_USER
WorkingDirectory=$APP_DIR
ExecStart=/usr/bin/node src/server.js
Restart=always
RestartSec=10
Environment=NODE_ENV=$APP_ENV
Environment=PORT=3000

[Install]
WantedBy=multi-user.target
EOF
        
        # Reload systemd and start service
        sudo systemctl daemon-reload
        sudo systemctl enable $APP_NAME
        sudo systemctl start $APP_NAME
        
        print_success "Systemd service configured and started"
    fi
}

# Configure Nginx
configure_nginx() {
    if [[ "$INSTALL_NGINX" == "false" ]]; then
        return
    fi
    
    print_status "Configuring Nginx..."
    
    # Create Nginx configuration
    sudo tee /etc/nginx/sites-available/$APP_NAME > /dev/null << EOF
server {
    listen 80;
    server_name ${DOMAIN:-localhost};
    
    # Backend API
    location /api/ {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade \$http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host \$host;
        proxy_set_header X-Real-IP \$remote_addr;
        proxy_set_header X-Forwarded-For \$proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto \$scheme;
        proxy_cache_bypass \$http_upgrade;
    }
    
    # Frontend static files
    location / {
        root $APP_DIR/frontend/dist;
        try_files \$uri \$uri/ /index.html;
        
        # Security headers
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
    }
    
    # Upload directory (no execution)
    location /uploads/ {
        root $APP_DIR;
        add_header X-Content-Type-Options "nosniff" always;
        location ~* \.(php|pl|py|jsp|asp|sh|cgi)$ {
            deny all;
        }
    }
}
EOF
    
    # Enable site
    sudo ln -sf /etc/nginx/sites-available/$APP_NAME /etc/nginx/sites-enabled/
    
    # Remove default site if it exists
    sudo rm -f /etc/nginx/sites-enabled/default
    
    # Test configuration
    sudo nginx -t
    
    # Reload Nginx
    sudo systemctl reload nginx
    
    print_success "Nginx configured"
}

# Setup SSL certificate
setup_ssl() {
    if [[ "$INSTALL_SSL" == "false" || -z "$DOMAIN" || -z "$EMAIL" ]]; then
        return
    fi
    
    print_status "Setting up SSL certificate..."
    
    # Obtain SSL certificate
    sudo certbot --nginx -d "$DOMAIN" --non-interactive --agree-tos -m "$EMAIL"
    
    # Setup automatic renewal
    echo "0 12 * * * root certbot renew --quiet" | sudo tee -a /etc/crontab
    
    print_success "SSL certificate configured"
}

# Build frontend
build_frontend() {
    print_status "Building frontend..."
    
    cd "$APP_DIR/frontend"
    
    # Create TypeScript configuration if it doesn't exist
    if [[ ! -f "$APP_DIR/frontend/tsconfig.json" ]]; then
        print_status "Creating TypeScript configuration..."
        cat > "$APP_DIR/frontend/tsconfig.json" << 'EOF'
{
  "compilerOptions": {
    "target": "ES2020",
    "useDefineForClassFields": true,
    "lib": ["ES2020", "DOM", "DOM.Iterable"],
    "module": "ESNext",
    "skipLibCheck": true,
    "moduleResolution": "bundler",
    "allowImportingTsExtensions": true,
    "resolveJsonModule": true,
    "isolatedModules": true,
    "noEmit": true,
    "jsx": "react-jsx",
    "strict": true,
    "noUnusedLocals": true,
    "noUnusedParameters": true,
    "noFallthroughCasesInSwitch": true
  },
  "include": ["src"],
  "references": [{ "path": "./tsconfig.node.json" }]
}
EOF
    fi
    
    # Create TypeScript node configuration if it doesn't exist
    if [[ ! -f "$APP_DIR/frontend/tsconfig.node.json" ]]; then
        print_status "Creating TypeScript node configuration..."
        cat > "$APP_DIR/frontend/tsconfig.node.json" << 'EOF'
{
  "compilerOptions": {
    "composite": true,
    "skipLibCheck": true,
    "module": "ESNext",
    "moduleResolution": "bundler",
    "allowSyntheticDefaultImports": true
  },
  "include": ["vite.config.ts"]
}
EOF
    fi
    
    # Create Vite configuration if it doesn't exist
    if [[ ! -f "$APP_DIR/frontend/vite.config.ts" ]]; then
        print_status "Creating Vite configuration..."
        cat > "$APP_DIR/frontend/vite.config.ts" << 'EOF'
import { defineConfig } from 'vite'
import react from '@vitejs/plugin-react'

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  server: {
    port: 3001,
    host: true
  },
  build: {
    outDir: 'dist',
    sourcemap: false
  }
})
EOF
    fi
    
    # Create basic React app structure (force overwrite to ensure correct content)
    print_status "Creating basic React app structure..."
    mkdir -p "$APP_DIR/frontend/src"
    
    cat > "$APP_DIR/frontend/src/main.tsx" << 'EOF'
import React from 'react'
import ReactDOM from 'react-dom/client'
import App from './App'
import './index.css'

ReactDOM.createRoot(document.getElementById('root')!).render(
  <React.StrictMode>
    <App />
  </React.StrictMode>,
)
EOF
    
    cat > "$APP_DIR/frontend/src/App.tsx" << 'EOF'
function App() {
  return (
    <div className="min-h-screen bg-gray-100 flex items-center justify-center">
      <div className="bg-white p-8 rounded-lg shadow-md text-center">
        <h1 className="text-3xl font-bold text-gray-800 mb-4">
          Event Manager
        </h1>
        <p className="text-gray-600 mb-6">
          Contest Management System
        </p>
        <div className="text-sm text-gray-500">
          <p>Backend API: Running on port 3000</p>
          <p>Frontend: Running on port 3001</p>
        </div>
      </div>
    </div>
  )
}

export default App
EOF
    
    cat > "$APP_DIR/frontend/src/index.css" << 'EOF'
@tailwind base;
@tailwind components;
@tailwind utilities;

body {
  margin: 0;
  font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Roboto', 'Oxygen',
    'Ubuntu', 'Cantarell', 'Fira Sans', 'Droid Sans', 'Helvetica Neue',
    sans-serif;
  -webkit-font-smoothing: antialiased;
  -moz-osx-font-smoothing: grayscale;
}

code {
  font-family: source-code-pro, Menlo, Monaco, Consolas, 'Courier New',
    monospace;
}
EOF
    
    cat > "$APP_DIR/frontend/index.html" << 'EOF'
<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <link rel="icon" type="image/svg+xml" href="/vite.svg" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Event Manager</title>
  </head>
  <body>
    <div id="root"></div>
    <script type="module" src="/src/main.tsx"></script>
  </body>
</html>
EOF
    
    npm install --no-fund --no-audit
    
    # Fix frontend binary permissions
    if [[ -d "$APP_DIR/frontend/node_modules/.bin" ]]; then
        chmod +x "$APP_DIR/frontend/node_modules/.bin"/*
        print_status "Fixed frontend binary permissions"
    fi
    
    # Fix esbuild binary permissions specifically
    if [[ -d "$APP_DIR/frontend/node_modules/@esbuild" ]]; then
        find "$APP_DIR/frontend/node_modules/@esbuild" -name "esbuild" -type f -exec chmod +x {} \;
        print_status "Fixed esbuild binary permissions"
    fi
    
    # Fix all binary files in node_modules
    find "$APP_DIR/frontend/node_modules" -name "*.bin" -type f -exec chmod +x {} \; 2>/dev/null || true
    find "$APP_DIR/frontend/node_modules" -name "esbuild" -type f -exec chmod +x {} \; 2>/dev/null || true
    print_status "Fixed all frontend binary permissions"
    
    npm run build
    
    print_success "Frontend built successfully"
}

# Main installation function
main() {
    echo "🚀 Event Manager Complete Setup Script"
    echo "======================================"
    echo ""
    
    # Parse command line arguments
    parse_args "$@"
    
    # Check prerequisites
    check_root
    detect_os
    
    # Install prerequisites
    install_prerequisites
    
    # Setup application directory
    setup_application_directory
    
    # Setup environment variables
    setup_environment
    
    # Setup database
    setup_database
    
    # Build frontend
    build_frontend
    
    # Setup web server permissions
    setup_permissions
    
    # Check for PM2
    check_pm2
    
    # Setup process management
    setup_systemd_service
    
    # Configure Nginx
    configure_nginx
    
    # Setup SSL certificate
    setup_ssl
    
    echo ""
    echo "🎉 Installation Complete!"
    echo "========================"
    echo ""
    echo "📋 Application Details:"
    echo "   Application Directory: $APP_DIR"
    echo "   Database: $DB_NAME"
    echo "   Database User: $DB_USER"
    echo "   Web Server User: $WEB_SERVER_USER"
    echo "   Process Manager: $([ "$USE_PM2" == "true" ] && echo "PM2" || echo "systemd")"
    echo ""
    echo "🌐 Access Information:"
    if [[ -n "$DOMAIN" ]]; then
        echo "   URL: https://$DOMAIN"
    else
        echo "   URL: http://localhost"
    fi
    echo "   Backend API: http://localhost:3000"
    echo ""
    echo "🔐 Default Login Credentials:"
    echo "   Email: admin@eventmanager.com"
    echo "   Password: admin123"
    echo ""
    echo "📚 Management Commands:"
    if [[ "$USE_PM2" == "true" ]]; then
        echo "   PM2 Status: sudo -u $WEB_SERVER_USER pm2 status"
        echo "   PM2 Logs: sudo -u $WEB_SERVER_USER pm2 logs"
        echo "   PM2 Restart: sudo -u $WEB_SERVER_USER pm2 restart $APP_NAME"
    else
        echo "   Service Status: sudo systemctl status $APP_NAME"
        echo "   Service Logs: sudo journalctl -u $APP_NAME -f"
        echo "   Service Restart: sudo systemctl restart $APP_NAME"
    fi
    echo "   Nginx Status: sudo systemctl status nginx"
    echo "   Nginx Reload: sudo systemctl reload nginx"
    echo ""
}

# Run main function
main "$@"