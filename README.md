# Event Manager - High-Performance Event Management System

A modern, scalable event management system built with Node.js Fastify and React, designed to handle complex scoring systems with real-time updates and comprehensive reporting.

## 🚀 Features

### Core Functionality
- **Multi-Event Management**: Create and manage multiple events with contests and categories
- **Hierarchical Structure**: Event(s) > Contest(s) > Category(ies) organization
- **Real-time Scoring**: Live scoring updates with WebSocket support
- **User Management**: Role-based access control with 7 distinct user roles
- **File Management**: Upload and manage documents and images
- **Archive System**: Archive and reactivate events with full data preservation
- **Audit Logging**: Complete activity tracking and compliance reporting
- **Responsive Design**: Optimized for all device types (mobile, tablet, desktop)

## 👥 User Roles & Permissions

The Event Manager system implements a comprehensive role-based access control (RBAC) system with 7 distinct user roles, each with specific permissions and responsibilities.

### **1. Organizer** 🎯
**Highest level of access - System Administrator**

**Permissions:**
- `events:create`, `events:read`, `events:update`, `events:delete`, `events:archive`
- `contests:create`, `contests:read`, `contests:update`, `contests:delete`, `contests:archive`
- `categories:create`, `categories:read`, `categories:update`, `categories:delete`
- `users:create`, `users:read`, `users:update`, `users:delete`
- `scoring:read`, `results:read`, `settings:read`, `settings:update`

**Responsibilities:**
- Create and manage all events and contests
- Manage user accounts and roles
- Configure system settings
- Access all reports and analytics
- Manage templates and categories
- Handle system backups and maintenance
- Full database access and administration

**API Access:** All endpoints with `requireRole(['organizer'])`

---

### **2. Judge** ⚖️
**Scoring and evaluation specialist**

**Permissions:**
- `events:read`, `contests:read`, `categories:read`
- `scoring:create`, `scoring:read`, `scoring:update`
- `results:read`
- **NEW:** `scoring:certify`, `scoring:update_comments`

**Responsibilities:**
- Score contestants in assigned subcategories
- **Certify scores** per contestant per subcategory
- **Update comments** on certified scores (cannot edit scores after certification)
- View contestant information and criteria
- Submit and modify scores (before certification)
- View results for their assigned categories
- Access scoring interface and tools

**API Access:** 
- `/api/scoring/*` - All scoring endpoints
- `/api/scoring/certify/:subcategoryId` - Certify scores
- `/api/scoring/:id/comments` - Update comments on certified scores
- `/api/results/*` - Read-only results access
- `/api/events/*`, `/api/contests/*`, `/api/categories/*` - Read-only access

**Restrictions:**
- Cannot create or modify events/contests
- Cannot access user management
- Cannot view system settings
- **Cannot edit scores after certification** (comments only)
- Limited to assigned subcategories only

---

### **3. Contestant** 🏆
**Event participant with limited access**

**Permissions:**
- `events:read`, `contests:read`, `categories:read`
- `results:read`

**Responsibilities:**
- View their own contest information
- Access personal results and scores
- View event schedules and details
- Update personal profile information

**API Access:**
- `/api/results/*` - Personal results only
- `/api/events/*`, `/api/contests/*`, `/api/categories/*` - Read-only access
- `/api/auth/profile` - Profile management

**Restrictions:**
- Cannot access scoring functions
- Cannot view other contestants' information
- Cannot access administrative functions
- Limited to their own data only

---

### **4. Emcee** 🎤
**Event host and presenter**

**Permissions:**
- `events:read`, `contests:read`, `categories:read`
- `results:read`

**Responsibilities:**
- Access contest information and schedules
- View contestant information for presentations
- Access emcee scripts and materials
- View real-time results and updates
- Manage presentation materials

**API Access:**
- `/api/emcee/*` - All emcee-specific endpoints
- `/api/results/*` - Read-only results access
- `/api/events/*`, `/api/contests/*`, `/api/categories/*` - Read-only access

**Restrictions:**
- Cannot modify scores or data
- Cannot access administrative functions
- Cannot view sensitive user information
- Limited to presentation-related data

---

### **5. Tally Master** 📊
**Score verification and reporting specialist**

**Permissions:**
- `events:read`, `contests:read`, `categories:read`
- `scoring:read`, `results:read`, `results:update`
- **NEW:** `scoring:verify`, `scoring:verify_all_judges`
- **NEW:** `discrepancy:create`, `discrepancy:approve`
- **NEW:** `final_results:view`, `final_results:print`

**Responsibilities:**
- **Verify all judges have certified** their respective scores
- **Verify and validate all scores** after judge certification
- Generate official reports and certificates
- Manage final score calculations
- **Create discrepancies** for score modifications
- **Approve discrepancies** (multi-signature required)
- Handle score disputes and corrections
- Ensure scoring accuracy and integrity

**API Access:**
- `/api/tally-master/*` - All tally master endpoints
- `/api/scoring/verify/:subcategoryId` - Verify all judge certifications
- `/api/scoring/:id/discrepancy` - Create score discrepancies
- `/api/scoring/:id/discrepancy/approve` - Approve discrepancies
- `/api/scoring/final-results/:subcategoryId` - View final results
- `/api/scoring/*` - Read-only scoring access
- `/api/results/*` - Full results access (read/update)
- `/api/events/*`, `/api/contests/*`, `/api/categories/*` - Read-only access

**Restrictions:**
- Cannot create or modify events/contests
- Cannot access user management
- Cannot modify system settings
- **Cannot verify until all judges have certified**
- Limited to scoring and results functions

---

### **6. Auditor** 🔍
**Final certification specialist - COMPLETELY RESTRUCTURED**

**Permissions:**
- `events:read`, `contests:read`, `categories:read`
- `scoring:read`, `results:read`
- **NEW:** `scoring:audit_certify`, `scoring:verify_all_tally`
- **NEW:** `discrepancy:approve`
- **NEW:** `final_results:view`, `final_results:print`

**Responsibilities:**
- **Double-check Tally Master role** - Final certification authority
- **Certify scores ONLY after** Judges AND Tally Master have certified
- **Same visibility as Tally Master** - Can view all verification stages
- **Approve discrepancies** (multi-signature required)
- **View final results** after complete certification
- Ensure final scoring accuracy and compliance

**API Access:**
- `/api/scoring/audit-certify/:subcategoryId` - Final certification
- `/api/scoring/:id/discrepancy/approve` - Approve discrepancies
- `/api/scoring/final-results/:subcategoryId` - View final results
- `/api/scoring/certification-status/:subcategoryId` - Check certification status
- `/api/results/*` - Read-only results access
- `/api/scoring/*` - Read-only scoring access
- `/api/events/*`, `/api/contests/*`, `/api/categories/*` - Read-only access

**Restrictions:**
- **Cannot certify until Tally Master has verified**
- Cannot create or modify events/contests
- Cannot access user management
- Cannot modify system settings
- **Cannot modify scores** (approval only)

---

### **7. Board** 📋
**Executive and oversight role**

**Permissions:**
- `events:read`, `contests:read`, `categories:read`
- `results:read`, `reports:read`
- **NEW:** `discrepancy:approve`, `discrepancy:final_approval`
- **NEW:** `final_results:view`, `final_results:print`

**Responsibilities:**
- **Final approval authority** for score discrepancies (equal to Organizer)
- **View final results** after complete certification
- Access high-level reports and analytics
- View system performance metrics
- Generate executive summaries
- Monitor overall system health
- Make strategic decisions based on data

**API Access:**
- `/api/board/*` - All board endpoints
- `/api/scoring/:id/discrepancy/approve` - Approve discrepancies
- `/api/scoring/final-results/:subcategoryId` - View final results
- `/api/results/*` - Read-only results access
- `/api/print/*` - Print and report generation
- `/api/events/*`, `/api/contests/*`, `/api/categories/*` - Read-only access

**Restrictions:**
- Cannot modify data or settings
- Cannot access user management
- Cannot access administrative functions
- **Cannot view uncertified results** (final results only)
- Limited to reporting and analytics

---

## 🔐 Permission Matrix

| Permission | Organizer | Judge | Contestant | Emcee | Tally Master | Auditor | Board |
|------------|-----------|-------|------------|-------|--------------|---------|-------|
| **Events** | CRUD+A | R | R | R | R | R | R |
| **Contests** | CRUD+A | R | R | R | R | R | R |
| **Categories** | CRUD | R | R | R | R | R | R |
| **Users** | CRUD | - | - | - | - | - | - |
| **Scoring** | CRUD | CRU | - | - | R | R | - |
| **Results** | CRUD | R | R | R | RU | R | R |
| **Settings** | RU | - | - | - | - | - | - |
| **Templates** | CRUD | - | - | - | - | - | - |
| **Backups** | CRUD | - | - | - | - | - | - |
| **Audit Logs** | R | - | - | - | - | - | - |
| **Reports** | R | - | - | - | R | R | R |
| **CERTIFICATION WORKFLOW** | | | | | | | |
| **Score Certification** | ✓ | ✓ | - | - | - | - | - |
| **Score Verification** | ✓ | - | - | - | ✓ | - | - |
| **Audit Certification** | ✓ | - | - | - | - | ✓ | - |
| **Discrepancy Creation** | ✓ | - | - | - | ✓ | ✓ | ✓ |
| **Discrepancy Approval** | ✓ | - | - | - | ✓ | ✓ | ✓ |
| **Final Results** | ✓ | - | - | ✓ | ✓ | ✓ | ✓ |

**Legend:** C=Create, R=Read, U=Update, D=Delete, A=Archive, ✓=Full Access

---

## 🛡️ Security Features

### Authentication
- JWT-based authentication with configurable expiration
- Password hashing using bcrypt with configurable rounds
- Session management with automatic timeout
- Multi-factor authentication support (planned)

### Authorization
- Role-based access control (RBAC)
- Permission-based access control (PBAC)
- Route-level security middleware
- API endpoint protection

### Data Protection
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- File upload security

### Audit & Compliance
- Complete activity logging
- User action tracking
- Data change auditing
- Compliance reporting
- Security event monitoring

## 🔄 Certification Workflow

### **4-Stage Certification Process:**

#### **Stage 1: Judge Certification** ⚖️
- Judges score contestants in assigned subcategories
- **Must certify scores** per contestant per subcategory
- **Cannot edit scores** after certification (comments only)
- Status: `draft` → `judge_certified`

#### **Stage 2: Tally Master Verification** 📊
- Tally Master **verifies all judges have certified** their scores
- **Cannot verify** until all judges have certified
- Ensures complete judge certification before proceeding
- Status: `judge_certified` → `tally_verified`

#### **Stage 3: Auditor Certification** 🔍
- Auditor **double-checks Tally Master verification**
- **Cannot certify** until Tally Master has verified
- Final certification authority
- Status: `tally_verified` → `auditor_certified`

#### **Stage 4: Final Results** 🏆
- **Final results available** only after all certifications complete
- **Multi-signature discrepancy resolution** required for changes
- **Board/Organizer approval** required for score modifications

### **Discrepancy Resolution Process:**
1. **Tally Master** creates discrepancy with reason and proposed score
2. **Tally Master** approves their own discrepancy
3. **Auditor** approves the discrepancy
4. **Board OR Organizer** provides final approval
5. **Score is updated** only after all three signatures

### **Final Results Access:**
- **Available to:** Organizer, Tally Master, Auditor, Board, Emcee
- **Requires:** Complete certification by all user types
- **Cannot be viewed** until all stages are complete

### Technical Highlights
- **High Performance**: Built with Fastify for maximum speed
- **Database Agnostic**: PostgreSQL with Knex.js query builder
- **Real-time Updates**: WebSocket integration for live scoring
- **Modern Frontend**: React 18 with TypeScript and Tailwind CSS
- **Comprehensive API**: RESTful API with Swagger documentation
- **Security First**: JWT authentication, CSRF protection, input validation
- **Scalable Architecture**: Microservice-ready design with Redis caching

## 🏗️ Architecture

### Backend (Node.js + Fastify)
```
src/
├── config/           # Configuration management
├── database/         # Migrations and seeds
├── routes/           # API endpoints
├── services/         # Business logic
├── utils/            # Utilities and helpers
└── server.js         # Main server file
```

### Frontend (React + TypeScript)
```
src/
├── components/       # Reusable UI components
├── pages/           # Application pages
├── stores/          # State management (Zustand)
├── lib/             # Utilities and API client
└── main.tsx         # Application entry point
```

### Database Schema
- **12 Core Tables**: Simplified from original 39 tables
- **JSONB Support**: Flexible data storage
- **Audit Logging**: Complete activity tracking
- **Soft Deletes**: Data retention and recovery

## 🚀 Quick Start

### Prerequisites
- Node.js 18+ 
- PostgreSQL 12+
- Redis 6+
- npm or yarn

### Backend Setup

1. **Clone and install dependencies**
```bash
cd event-manager-api
npm install
```

2. **Configure environment**
```bash
cp .env.example .env
# Edit .env with your database and Redis credentials
```

3. **Set up database**
```bash
# Create PostgreSQL database
createdb event_manager

# Run migrations
npm run db:migrate

# Seed initial data (optional)
npm run db:seed
```

4. **Start development server**
```bash
npm run dev
```

The API will be available at `http://localhost:3000`
API Documentation: `http://localhost:3000/docs`

### Frontend Setup

1. **Install dependencies**
```bash
cd event-manager-frontend
npm install
```

2. **Configure environment**
```bash
cp .env.example .env
# Edit .env with your API URL
```

3. **Start development server**
```bash
npm run dev
```

The frontend will be available at `http://localhost:5173`

## 🐧 Ubuntu Production Deployment

### System Requirements
- Ubuntu 20.04 LTS or later
- Minimum 2GB RAM (4GB+ recommended)
- 20GB+ disk space
- Root or sudo access

### 1. System Preparation

```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git build-essential software-properties-common

# Install Node.js 18.x
curl -fsSL https://deb.nodesource.com/setup_18.x | sudo -E bash -
sudo apt install -y nodejs

# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib

# Install Redis
sudo apt install -y redis-server

# Install Nginx
sudo apt install -y nginx

# Install PM2 for process management
sudo npm install -g pm2

# Install SSL certificate tool
sudo apt install -y certbot python3-certbot-nginx
```

### 2. Database Setup

```bash
# Switch to postgres user
sudo -u postgres psql

# Create database and user
CREATE DATABASE event_manager;
CREATE USER event_manager WITH PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;
ALTER USER event_manager CREATEDB;
\q

# Configure PostgreSQL
sudo nano /etc/postgresql/*/main/postgresql.conf
# Uncomment and set: listen_addresses = 'localhost'

sudo nano /etc/postgresql/*/main/pg_hba.conf
# Add: local   event_manager   event_manager   md5

# Restart PostgreSQL
sudo systemctl restart postgresql
sudo systemctl enable postgresql
```

### 3. Redis Configuration

```bash
# Configure Redis
sudo nano /etc/redis/redis.conf
# Set: requirepass your_redis_password_here

# Restart Redis
sudo systemctl restart redis-server
sudo systemctl enable redis-server
```

### 4. Application Deployment

```bash
# Create application directory
sudo mkdir -p /opt/event-manager
sudo chown $USER:$USER /opt/event-manager
cd /opt/event-manager

# Clone repository
git clone https://github.com/your-username/New_EM_Project.git .
cd event-manager-api

# Install dependencies
npm install --production

# Create environment file
cp .env.example .env
nano .env
```

**Environment Configuration (.env):**
```bash
# Application
NODE_ENV=production
PORT=3000
HOST=0.0.0.0
APP_URL=https://yourdomain.com
DEBUG=false

# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=event_manager
DB_USER=event_manager
DB_PASSWORD=your_secure_password_here
DB_SSL=false

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password_here

# JWT (Generate strong secrets)
JWT_SECRET=your-super-secret-jwt-key-change-in-production
JWT_EXPIRES_IN=24h
JWT_ISSUER=event-manager
JWT_AUDIENCE=event-manager-users

# Security
BCRYPT_ROUNDS=12
RATE_LIMIT_MAX=100
MAX_FILE_SIZE=5242880

# Email (Configure with your SMTP)
EMAIL_HOST=your-smtp-host
EMAIL_PORT=587
EMAIL_USER=your-email@domain.com
EMAIL_PASS=your-email-password
EMAIL_FROM=noreply@yourdomain.com

# Features
FEATURE_REALTIME_SCORING=true
FEATURE_EMAIL_NOTIFICATIONS=true
FEATURE_FILE_UPLOADS=true
FEATURE_AUDIT_LOGGING=true
FEATURE_API_DOCS=true

# CORS
CORS_ORIGIN=https://yourdomain.com
```

```bash
# Run database migrations
npm run db:migrate

# Create PM2 ecosystem file
cat > ecosystem.config.js << EOF
module.exports = {
  apps: [{
    name: 'event-manager-api',
    script: 'src/server.js',
    cwd: '/opt/event-manager/event-manager-api',
    instances: 'max',
    exec_mode: 'cluster',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: '/var/log/pm2/event-manager-api-error.log',
    out_file: '/var/log/pm2/event-manager-api-out.log',
    log_file: '/var/log/pm2/event-manager-api.log',
    time: true
  }]
}
EOF

# Start application with PM2
pm2 start ecosystem.config.js
pm2 save
pm2 startup
```

### 5. Frontend Deployment

```bash
# Build frontend
cd /opt/event-manager/event-manager-frontend
npm install
npm run build

# Create frontend environment
cat > .env << EOF
VITE_API_URL=https://yourdomain.com/api
VITE_WS_URL=wss://yourdomain.com
VITE_APP_NAME=Event Manager
VITE_FEATURE_REALTIME_SCORING=true
VITE_FEATURE_EMAIL_NOTIFICATIONS=true
VITE_FEATURE_FILE_UPLOADS=true
VITE_FEATURE_AUDIT_LOGGING=true
EOF

# Build for production
npm run build
```

### 6. Nginx Configuration

```bash
# Create Nginx configuration
sudo nano /etc/nginx/sites-available/event-manager

# Add the following configuration:
server {
    listen 80;
    server_name yourdomain.com www.yourdomain.com;
    
    # Redirect HTTP to HTTPS
    return 301 https://$server_name$request_uri;
}

server {
    listen 443 ssl http2;
    server_name yourdomain.com www.yourdomain.com;
    
    # SSL Configuration (will be set up by Certbot)
    ssl_certificate /etc/letsencrypt/live/yourdomain.com/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/yourdomain.com/privkey.pem;
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-XSS-Protection "1; mode=block" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header Referrer-Policy "no-referrer-when-downgrade" always;
    add_header Content-Security-Policy "default-src 'self' http: https: data: blob: 'unsafe-inline'" always;
    
    # API Proxy
    location /api/ {
        proxy_pass http://localhost:3000/api/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
    
    # WebSocket Proxy
    location /ws/ {
        proxy_pass http://localhost:3000/ws/;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
    
    # Static files
    location / {
        root /opt/event-manager/event-manager-frontend/dist;
        try_files $uri $uri/ /index.html;
        
        # Cache static assets
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg)$ {
            expires 1y;
            add_header Cache-Control "public, immutable";
        }
    }
    
    # File uploads
    location /uploads/ {
        alias /opt/event-manager/uploads/;
        expires 1y;
        add_header Cache-Control "public";
    }
    
    # Security
    location ~ /\. {
        deny all;
    }
}

# Enable site
sudo ln -s /etc/nginx/sites-available/event-manager /etc/nginx/sites-enabled/
sudo nginx -t
sudo systemctl reload nginx
```

### 7. SSL Certificate Setup

```bash
# Install SSL certificate
sudo certbot --nginx -d yourdomain.com -d www.yourdomain.com

# Test auto-renewal
sudo certbot renew --dry-run
```

### 8. Firewall Configuration

```bash
# Configure UFW firewall
sudo ufw allow OpenSSH
sudo ufw allow 'Nginx Full'
sudo ufw --force enable
```

### 9. Monitoring and Logs

```bash
# View application logs
pm2 logs event-manager-api

# View Nginx logs
sudo tail -f /var/log/nginx/access.log
sudo tail -f /var/log/nginx/error.log

# Monitor system resources
htop
```

### 10. Backup Setup

```bash
# Create backup script
sudo nano /opt/event-manager/backup.sh

#!/bin/bash
BACKUP_DIR="/opt/backups/event-manager"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Database backup
pg_dump -h localhost -U event_manager event_manager > $BACKUP_DIR/database_$DATE.sql

# Application backup
tar -czf $BACKUP_DIR/application_$DATE.tar.gz /opt/event-manager --exclude=node_modules

# Keep only last 7 days of backups
find $BACKUP_DIR -type f -mtime +7 -delete

# Make executable
sudo chmod +x /opt/event-manager/backup.sh

# Add to crontab for daily backups
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/event-manager/backup.sh") | crontab -
```

### 11. Health Checks

```bash
# Check application health
curl https://yourdomain.com/api/health

# Check PM2 status
pm2 status

# Check services
sudo systemctl status postgresql
sudo systemctl status redis-server
sudo systemctl status nginx
```

### 12. Updates and Maintenance

```bash
# Update application
cd /opt/event-manager
git pull origin main
cd event-manager-api
npm install --production
pm2 reload event-manager-api

# Update frontend
cd ../event-manager-frontend
npm install
npm run build
sudo systemctl reload nginx
```

## 📊 Database Schema

### Core Tables
1. **users** - User accounts and profiles
2. **events** - Event information and settings
3. **contests** - Contest information and settings
4. **categories** - Event categories
5. **subcategories** - Event subcategories
6. **contestants** - Contestant information
7. **criteria** - Scoring criteria
8. **scores** - Individual scores
9. **subcategory_contestants** - Contestant assignments
10. **subcategory_judges** - Judge assignments
11. **audit_logs** - Activity tracking
12. **system_settings** - Application settings

### Key Improvements
- **Eliminated 27 redundant tables** (archived tables, separate certification tables)
- **Consolidated certification system** into single table with type enum
- **Added JSONB support** for flexible settings and metadata
- **Implemented comprehensive audit logging**
- **Database-agnostic design** with Knex.js

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `GET /api/auth/me` - Get current user
- `PUT /api/auth/profile` - Update profile
- `PUT /api/auth/password` - Change password

### Events
- `GET /api/events` - List events
- `POST /api/events` - Create event
- `GET /api/events/:id` - Get event details
- `PUT /api/events/:id` - Update event
- `DELETE /api/events/:id` - Delete event
- `POST /api/events/:id/archive` - Archive event
- `POST /api/events/:id/reactivate` - Reactivate event

### Contests
- `GET /api/contests/event/:eventId` - List contests for event
- `POST /api/contests` - Create contest
- `GET /api/contests/:id` - Get contest details
- `PUT /api/contests/:id` - Update contest
- `DELETE /api/contests/:id` - Delete contest

### Scoring
- `POST /api/scoring/submit` - Submit score
- `PUT /api/scoring/:id/sign` - Sign score
- `GET /api/scoring/subcategory/:id` - Get subcategory scores

### Results & Reporting
- `GET /api/results/event/:id` - Event results
- `GET /api/results/event/:id/report/pdf` - PDF report
- `GET /api/results/event/:id/report/excel` - Excel report
- `GET /api/results/leaderboard` - Leaderboard

## 🔐 Security Features

### Authentication & Authorization
- JWT-based authentication
- Role-based access control
- Session management
- Password hashing with bcrypt

### Data Protection
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection

### File Security
- File type validation
- Size limits
- Secure file storage
- Virus scanning (planned)

## 📱 Responsive Design

### Mobile-First Approach
- Optimized for touch interfaces
- Collapsible navigation
- Swipe gestures
- Offline support (planned)

### Cross-Device Compatibility
- iOS Safari
- Android Chrome
- Desktop browsers
- Tablet interfaces

## ⚡ Performance Optimizations

### Backend
- **Connection Pooling**: Optimized database connections
- **Query Optimization**: Indexed queries and efficient joins
- **Caching**: Redis-based caching for frequently accessed data
- **Rate Limiting**: API protection and resource management

### Frontend
- **Code Splitting**: Lazy loading of components
- **Image Optimization**: WebP support and lazy loading
- **Bundle Optimization**: Tree shaking and minification
- **Caching**: Service worker and browser caching

## 🔄 Real-time Features

### WebSocket Integration
- Live scoring updates
- Real-time notifications
- Collaborative editing
- Connection management

### Supported Events
- `score_submitted` - New score submitted
- `score_updated` - Score modified
- `score_deleted` - Score removed
- `event_update` - Event changes
- `user_update` - User profile changes

## 📈 Monitoring & Analytics

### Built-in Monitoring
- Health check endpoints
- Performance metrics
- Error tracking
- Usage analytics

### Logging
- Structured logging with Winston
- Request/response logging
- Error tracking and debugging
- Audit trail maintenance

## 🧪 Testing

### Backend Testing
- Unit tests with Jest
- Integration tests
- API endpoint testing
- Database testing

### Frontend Testing
- Component testing
- E2E testing
- Accessibility testing
- Performance testing

## 🚀 Deployment

### Production Setup
- Environment configuration
- Database migrations
- SSL/TLS setup
- CDN configuration
- Monitoring setup

### Docker Support
- Multi-stage builds
- Container orchestration
- Environment management
- Health checks

## 🤝 Contributing

### Development Setup
1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests
5. Submit a pull request

### Code Standards
- ESLint configuration
- Prettier formatting
- TypeScript strict mode
- Conventional commits

## 🔧 Troubleshooting

### Common Issues

**Database Connection Failed:**
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Check database exists
sudo -u postgres psql -c "\l" | grep event_manager

# Reset database user permissions
sudo -u postgres psql -c "ALTER USER event_manager CREATEDB;"
```

**Redis Connection Failed:**
```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping
```

**Application Won't Start:**
```bash
# Check PM2 logs
pm2 logs event-manager-api

# Restart application
pm2 restart event-manager-api

# Check environment variables
pm2 show event-manager-api
```

**Nginx 502 Bad Gateway:**
```bash
# Check if API is running
pm2 status

# Check Nginx error logs
sudo tail -f /var/log/nginx/error.log

# Test API directly
curl http://localhost:3000/api/health
```

### Performance Optimization

**Database Optimization:**
```bash
# Analyze database performance
sudo -u postgres psql event_manager -c "SELECT * FROM pg_stat_activity;"

# Check slow queries
sudo -u postgres psql event_manager -c "SELECT query, mean_time FROM pg_stat_statements ORDER BY mean_time DESC LIMIT 10;"
```

**Memory Optimization:**
```bash
# Monitor memory usage
htop
pm2 monit

# Adjust PM2 instances
pm2 scale event-manager-api 2
```

## 📊 Monitoring & Maintenance

### Health Checks
- **API Health:** `https://yourdomain.com/api/health`
- **Database:** Check PostgreSQL connection
- **Redis:** Check Redis connection
- **Disk Space:** Monitor `/opt/event-manager` and `/var/log`

### Log Management
```bash
# Rotate logs
sudo logrotate -f /etc/logrotate.d/nginx
pm2 flush

# Monitor logs in real-time
pm2 logs --lines 100
sudo tail -f /var/log/nginx/access.log
```

### Backup & Recovery
```bash
# Manual backup
/opt/event-manager/backup.sh

# Restore database
sudo -u postgres psql event_manager < /opt/backups/event-manager/database_YYYYMMDD_HHMMSS.sql

# Restore application
tar -xzf /opt/backups/event-manager/application_YYYYMMDD_HHMMSS.tar.gz -C /
```

## 📄 License

MIT License - see LICENSE file for details

## 🆘 Support

- Documentation: `/docs` endpoint
- Issues: GitHub Issues
- Discussions: GitHub Discussions
- Email: support@eventmanager.com

---

**Built with ❤️ for the event management community**