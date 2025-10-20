# Event Manager - Stable Event Management System

A comprehensive, production-ready event management system built with Node.js, Express.js, React, and PostgreSQL. Designed for Ubuntu 24.04 with session-based authentication and real-time capabilities.

## Table of Contents

- [Installation](#installation)
- [Quick Start Guide](#quick-start-guide)
- [Manual Installation](#manual-installation)
- [Configuration](#configuration)
- [Usage](#usage)
- [Uninstallation](#uninstallation)
- [Project Structure](#project-structure)
- [Security Features](#security-features)
- [Development](#development)
- [Troubleshooting](#troubleshooting)
- [Deployment](#deployment)

## Installation

### Prerequisites

- Ubuntu 24.04 LTS (recommended)
- Internet connection
- User account with sudo privileges
- At least 2GB RAM and 10GB disk space

### Quick Start Guide

#### One-Command Installation

```bash
# Download and run the installation script
curl -fsSL https://raw.githubusercontent.com/your-repo/event-manager/main/install-event-manager-ubuntu-24.04.sh | bash
```

#### Manual Installation

1. **Clone the repository:**
   ```bash
   git clone https://github.com/your-repo/event-manager.git
   cd event-manager
   ```

2. **Run the installation script:**
   ```bash
   chmod +x install-event-manager-ubuntu-24.04.sh
   ./install-event-manager-ubuntu-24.04.sh
   ```

3. **Access the application:**
   - Open your browser and navigate to `http://localhost`
   - Login with the default credentials:
     - Email: `admin@eventmanager.com`
     - Password: `admin123`

### What Gets Installed

The installation script automatically installs and configures:

- **System Dependencies:**
  - Node.js 20.x LTS
  - PostgreSQL 15+
  - Redis 7+
  - Apache 2.4
  - Build tools and libraries

- **Application Components:**
  - Event Manager API (Express.js backend)
  - Event Manager Frontend (React SPA)
  - Database migrations and seed data
  - PM2 process management
  - Apache virtual host configuration

- **Security Features:**
  - Firewall configuration (UFW)
  - Rate limiting
  - Session-based authentication
  - Security headers
  - Input validation

## Configuration

### Environment Variables

The application uses environment variables for configuration. The main configuration file is located at `/opt/event-manager/.env`:

```bash
# Application Configuration
NODE_ENV=production
PORT=3000
HOST=0.0.0.0
APP_NAME=Event Manager
APP_VERSION=2.0.0

# Database Configuration
DB_HOST=localhost
DB_PORT=5432
DB_NAME=event_manager
DB_USER=event_manager
DB_PASSWORD=your_secure_password

# Redis Configuration
REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password
REDIS_DB=0

# Session Configuration
SESSION_SECRET=your_session_secret
SESSION_MAX_AGE=86400000

# Security Configuration
BCRYPT_ROUNDS=12
RATE_LIMIT_MAX=100
RATE_LIMIT_WINDOW_MS=900000

# Features
FEATURE_REALTIME_SCORING=true
FEATURE_EMAIL_NOTIFICATIONS=false
FEATURE_FILE_UPLOADS=true
FEATURE_AUDIT_LOGGING=true
FEATURE_BACKUP_AUTOMATION=true
FEATURE_API_DOCS=true
```

### Apache Configuration

The Apache virtual host is configured at `/etc/apache2/sites-available/event-manager.conf`:

- **Document Root:** `/opt/event-manager/event-manager-frontend/dist`
- **API Proxy:** `/api/` → `http://localhost:3000/api/`
- **WebSocket Proxy:** `/socket.io/` → `ws://localhost:3000/socket.io/`
- **Security Headers:** XSS protection, content type options, frame options
- **CORS Headers:** Configured for API access

### Database Configuration

PostgreSQL is configured with:
- **Database:** `event_manager`
- **User:** `event_manager`
- **Password:** Generated during installation
- **Connection Pooling:** Configured for optimal performance

### Redis Configuration

Redis is configured with:
- **Password Protection:** Enabled
- **Memory Management:** 256MB limit with LRU eviction
- **Persistence:** RDB and AOF enabled
- **Session Storage:** Used for session management

## Usage

### Accessing the Application

- **Frontend:** http://localhost
- **API:** http://localhost/api
- **API Documentation:** http://localhost/docs
- **Health Check:** http://localhost/api/health

### Default Credentials

- **Email:** admin@eventmanager.com
- **Password:** admin123

**Important:** Change the default password immediately after first login.

### User Roles

The system supports multiple user roles with different permissions:

- **Organizer:** Full system access, can manage all events and users
- **Judge:** Can score contestants and view results
- **Contestant:** Can view events and results
- **Emcee:** Can view events and announce results
- **Tally Master:** Can verify scores and manage discrepancies
- **Auditor:** Can audit scoring and certify results
- **Board:** Can approve final results and manage disputes

### Service Management

```bash
# Start the application
sudo systemctl start event-manager

# Stop the application
sudo systemctl stop event-manager

# Restart the application
sudo systemctl restart event-manager

# Check status
sudo systemctl status event-manager

# View logs
sudo journalctl -u event-manager -f
```

### PM2 Management

```bash
# Switch to the service user
sudo su - eventmanager

# PM2 commands
pm2 status
pm2 restart event-manager-api
pm2 logs event-manager-api
pm2 monit
```

## Uninstallation

To completely remove Event Manager from your system:

```bash
# Run the uninstallation script
./uninstall-event-manager-ubuntu-24.04.sh
```

The uninstallation script will:
- Stop all services
- Remove application files
- Remove database and data (with confirmation)
- Remove Redis data (with confirmation)
- Remove Apache configuration
- Remove systemd services
- Clean up firewall rules
- Remove application user

You can choose to preserve database and Redis data during uninstallation.

## Project Structure

```
event-manager/
├── event-manager-api/           # Backend API
│   ├── src/
│   │   ├── config/             # Configuration files
│   │   ├── database/           # Database migrations and seeds
│   │   ├── routes/             # API route handlers
│   │   ├── services/           # Business logic services
│   │   ├── utils/              # Utility functions
│   │   └── server.js           # Main server file
│   ├── scripts/                # Database scripts
│   └── package.json            # Backend dependencies
├── event-manager-frontend/     # Frontend React app
│   ├── src/
│   │   ├── components/         # React components
│   │   ├── pages/              # Page components
│   │   ├── stores/             # State management
│   │   ├── lib/                # Utility libraries
│   │   └── hooks/              # Custom React hooks
│   └── package.json            # Frontend dependencies
├── install-event-manager-ubuntu-24.04.sh
├── uninstall-event-manager-ubuntu-24.04.sh
└── README.md
```

## Security Features

### Authentication & Authorization
- **Session-based authentication** (no JWT complexity)
- **Role-based access control** with granular permissions
- **Password hashing** using bcrypt with configurable rounds
- **Session management** with Redis storage
- **Automatic session timeout**

### Input Validation
- **Joi validation** for all API endpoints
- **SQL injection protection** via parameterized queries
- **XSS protection** with proper input sanitization
- **File upload validation** with type and size restrictions

### Security Headers
- **Helmet.js** for security headers
- **CORS configuration** with credentials support
- **Rate limiting** to prevent abuse
- **Content Security Policy** (configurable)

### Infrastructure Security
- **Firewall configuration** (UFW)
- **Service isolation** with dedicated user
- **Secure file permissions**
- **Log monitoring** and audit trails

## Development

### Prerequisites for Development

- Node.js 18+ and npm
- PostgreSQL 12+
- Redis 6+
- Git

### Development Setup

1. **Clone and install dependencies:**
   ```bash
   git clone https://github.com/your-repo/event-manager.git
   cd event-manager
   
   # Backend
   cd event-manager-api
   npm install
   
   # Frontend
   cd ../event-manager-frontend
   npm install
   ```

2. **Setup environment:**
   ```bash
   # Copy environment template
   cp event-manager-api/.env.example event-manager-api/.env
   
   # Configure database and Redis settings
   nano event-manager-api/.env
   ```

3. **Setup database:**
   ```bash
   cd event-manager-api
   npm run db:migrate
   npm run db:seed
   ```

4. **Start development servers:**
   ```bash
   # Backend (Terminal 1)
   cd event-manager-api
   npm run dev
   
   # Frontend (Terminal 2)
   cd event-manager-frontend
   npm run dev
   ```

### API Development

The API follows RESTful conventions:

- **Authentication:** `/api/auth/*`
- **Events:** `/api/events/*`
- **Contests:** `/api/contests/*`
- **Categories:** `/api/categories/*`
- **Scoring:** `/api/scoring/*`
- **Users:** `/api/users/*`
- **Results:** `/api/results/*`
- **Settings:** `/api/settings/*`

### Database Schema

Key tables:
- **users:** User accounts and profiles
- **events:** Event information
- **contests:** Contest details
- **categories:** Contest categories
- **scores:** Scoring data
- **results:** Calculated results
- **audit_logs:** System audit trail

## Troubleshooting

### Common Issues

#### Service Won't Start
```bash
# Check service status
sudo systemctl status event-manager

# Check logs
sudo journalctl -u event-manager -f

# Check PM2 logs
sudo su - eventmanager
pm2 logs event-manager-api
```

#### Database Connection Issues
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Test database connection
sudo -u postgres psql -c "SELECT 1;"

# Check database exists
sudo -u postgres psql -l | grep event_manager
```

#### Redis Connection Issues
```bash
# Check Redis status
sudo systemctl status redis-server

# Test Redis connection
redis-cli ping

# Check Redis logs
sudo tail -f /var/log/redis/redis-server.log
```

#### Frontend Not Loading
```bash
# Check Apache status
sudo systemctl status apache2

# Check Apache configuration
sudo apache2ctl configtest

# Check Apache logs
sudo tail -f /var/log/apache2/error.log
```

### Log Locations

- **Application Logs:** `/opt/event-manager/logs/`
- **System Logs:** `sudo journalctl -u event-manager`
- **Apache Logs:** `/var/log/apache2/`
- **PostgreSQL Logs:** `/var/log/postgresql/`
- **Redis Logs:** `/var/log/redis/redis-server.log`

### Performance Optimization

#### Database Optimization
- **Connection Pooling:** Configured in Knex.js
- **Indexes:** Added on frequently queried columns
- **Query Optimization:** Using proper joins and filters

#### Redis Optimization
- **Memory Management:** LRU eviction policy
- **Persistence:** Balanced RDB and AOF
- **Connection Pooling:** Reused connections

#### Frontend Optimization
- **Code Splitting:** React lazy loading
- **Asset Optimization:** Vite build optimization
- **Caching:** Browser and CDN caching headers

## Deployment

### Production Deployment

1. **SSL Configuration:**
   ```bash
   # Install Certbot
   sudo apt install certbot python3-certbot-apache
   
   # Get SSL certificate
   sudo certbot --apache -d yourdomain.com
   ```

2. **Firewall Configuration:**
   ```bash
   # Allow HTTPS
   sudo ufw allow 443/tcp
   
   # Remove HTTP if using HTTPS only
   sudo ufw delete allow 80/tcp
   ```

3. **Backup Configuration:**
   ```bash
   # Create backup script
   sudo tee /opt/event-manager/backup.sh > /dev/null << 'EOF'
   #!/bin/bash
   pg_dump event_manager > /opt/backups/event_manager_$(date +%Y%m%d_%H%M%S).sql
   redis-cli --rdb /opt/backups/redis_$(date +%Y%m%d_%H%M%S).rdb
   EOF
   
   sudo chmod +x /opt/event-manager/backup.sh
   
   # Schedule backups
   sudo crontab -e
   # Add: 0 2 * * * /opt/event-manager/backup.sh
   ```

### Monitoring

#### Health Checks
- **API Health:** `GET /api/health`
- **Database Health:** Included in API health check
- **Redis Health:** Included in API health check

#### Monitoring Tools
- **PM2 Monitoring:** `pm2 monit`
- **System Monitoring:** `htop`, `iotop`
- **Log Monitoring:** `journalctl`, `tail -f`

### Scaling

#### Horizontal Scaling
- **Load Balancer:** Use nginx or Apache as load balancer
- **Multiple API Instances:** Run multiple PM2 instances
- **Database Clustering:** PostgreSQL streaming replication
- **Redis Clustering:** Redis Cluster or Sentinel

#### Vertical Scaling
- **Increase Memory:** For Redis and Node.js
- **Increase CPU:** For processing-intensive operations
- **SSD Storage:** For database performance

## Support

### Getting Help

1. **Check the logs** for error messages
2. **Review this documentation** for common solutions
3. **Check the GitHub issues** for known problems
4. **Create a new issue** with detailed information

### Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### License

This project is licensed under the MIT License - see the LICENSE file for details.

---

**Event Manager** - Professional event management made simple.