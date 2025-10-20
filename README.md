# Event Manager - Stable Event Management System

A robust, production-ready event management system built with Express.js and PostgreSQL, designed for reliability and ease of deployment.

## 🚀 Installation Instructions

### Prerequisites
- Ubuntu 24.04 LTS
- Root or sudo access
- Internet connection

### Quick Installation

**🚀 Automated Installation (Recommended):**
```bash
# Download and run the installation script
curl -fsSL https://raw.githubusercontent.com/your-username/New_EM_Project/main/install-stable-ubuntu-24.04.sh | bash
```

**📁 Local Installation:**
```bash
# Make the script executable and run it
chmod +x install-stable-ubuntu-24.04.sh
./install-stable-ubuntu-24.04.sh
```

### Manual Installation Steps

If you prefer to install manually, follow these steps:

#### 1. System Preparation
```bash
# Update system packages
sudo apt update && sudo apt upgrade -y

# Install essential packages
sudo apt install -y curl wget git build-essential python3-dev make g++ pkg-config
```

#### 2. Install Node.js 20.x (LTS)
```bash
# Install Node.js 20.x using NodeSource repository
curl -fsSL https://deb.nodesource.com/setup_20.x | sudo -E bash -
sudo apt-get install -y nodejs

# Verify installation
node --version  # Should be v20.x.x
npm --version   # Should be 10.x.x
```

#### 3. Install Database and Cache
```bash
# Install PostgreSQL
sudo apt install -y postgresql postgresql-contrib postgresql-server-dev-all
sudo systemctl start postgresql
sudo systemctl enable postgresql

# Install Redis
sudo apt install -y redis-server redis-tools libhiredis-dev
sudo systemctl start redis-server
sudo systemctl enable redis-server
```

#### 4. Install Apache
```bash
# Install Apache
sudo apt install -y apache2 apache2-utils libapache2-mod-ssl
sudo systemctl start apache2
sudo systemctl enable apache2

# Enable required modules
sudo a2enmod rewrite ssl headers proxy proxy_http proxy_wstunnel
```

#### 5. Install Image Processing Libraries
```bash
# Required for image processing
sudo apt install -y \
    libvips-dev \
    libcairo2-dev \
    libpango1.0-dev \
    libjpeg-dev \
    libgif-dev \
    librsvg2-dev \
    libpng-dev \
    libwebp-dev \
    libtiff-dev \
    libavif-dev
```

#### 6. Install PM2 Process Manager
```bash
sudo npm install -g pm2
```

#### 7. Database Setup
```bash
# Create database and user
sudo -u postgres psql << EOF
CREATE DATABASE event_manager;
CREATE USER event_manager WITH PASSWORD 'your_secure_password_here';
GRANT ALL PRIVILEGES ON DATABASE event_manager TO event_manager;
ALTER USER event_manager CREATEDB;
\q
EOF
```

#### 8. Application Setup
```bash
# Create application directory
sudo mkdir -p /opt/event-manager
sudo chown $USER:$USER /opt/event-manager

# Clone repository
cd /opt/event-manager
git clone https://github.com/your-username/New_EM_Project.git .

# Install backend dependencies
cd event-manager-api
npm install --omit=dev

# Run database migrations
npm run db:migrate

# Install frontend dependencies and build
cd ../event-manager-frontend
npm install
npm run build
```

#### 9. Configuration
```bash
# Create environment file
cat > /opt/event-manager/.env << EOF
NODE_ENV=production
PORT=3000
HOST=0.0.0.0
APP_URL=http://your-domain.com

DB_HOST=localhost
DB_PORT=5432
DB_NAME=event_manager
DB_USER=event_manager
DB_PASSWORD=your_secure_password_here

REDIS_HOST=localhost
REDIS_PORT=6379
REDIS_PASSWORD=your_redis_password_here

SESSION_SECRET=your-super-secret-session-key-change-in-production
SESSION_MAX_AGE=86400000

BCRYPT_ROUNDS=12
RATE_LIMIT_MAX=100
MAX_FILE_SIZE=5242880

FEATURE_REALTIME_SCORING=true
FEATURE_FILE_UPLOADS=true
FEATURE_AUDIT_LOGGING=true
FEATURE_API_DOCS=true

CORS_ORIGIN=http://your-domain.com
EOF
```

#### 10. Apache Configuration
```bash
# Create Apache virtual host
sudo tee /etc/apache2/sites-available/event-manager.conf > /dev/null << 'EOF'
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /opt/event-manager/event-manager-frontend/dist
    
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:3000/api/
    ProxyPassReverse /api/ http://localhost:3000/api/
    
    ProxyPass /socket.io/ ws://localhost:3000/socket.io/
    ProxyPassReverse /socket.io/ ws://localhost:3000/socket.io/
    
    <Directory /opt/event-manager/event-manager-frontend/dist>
        Options Indexes FollowSymLinks
        AllowOverride All
        Require all granted
        
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    Alias /uploads /opt/event-manager/uploads
    <Directory /opt/event-manager/uploads>
        Options Indexes FollowSymLinks
        AllowOverride None
        Require all granted
    </Directory>
</VirtualHost>
EOF

# Enable site
sudo a2ensite event-manager
sudo systemctl reload apache2
```

#### 11. Start Application
```bash
# Create PM2 ecosystem file
cat > /opt/event-manager/ecosystem.config.js << EOF
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
    }
  }]
}
EOF

# Start application
pm2 start /opt/event-manager/ecosystem.config.js
pm2 save
pm2 startup
```

### Post-Installation Configuration

#### 1. Firewall Setup
```bash
sudo ufw allow OpenSSH
sudo ufw allow 'Apache Full'
sudo ufw --force enable
```

#### 2. SSL Certificate (Optional)
```bash
# Install Certbot
sudo apt install -y certbot python3-certbot-apache

# Get SSL certificate
sudo certbot --apache -d your-domain.com
```

#### 3. Backup Setup
```bash
# Create backup script
cat > /opt/event-manager/backup.sh << 'EOF'
#!/bin/bash
BACKUP_DIR="/opt/event-manager/backups"
DATE=$(date +%Y%m%d_%H%M%S)

mkdir -p $BACKUP_DIR
pg_dump -h localhost -U event_manager event_manager > $BACKUP_DIR/database_$DATE.sql
tar -czf $BACKUP_DIR/application_$DATE.tar.gz /opt/event-manager --exclude=node_modules --exclude=backups
find $BACKUP_DIR -type f -mtime +7 -delete
EOF

chmod +x /opt/event-manager/backup.sh

# Set up automated backups
(crontab -l 2>/dev/null; echo "0 2 * * * /opt/event-manager/backup.sh") | crontab -
```

## 🗑️ Uninstallation

### Quick Uninstall
```bash
# Download and run the uninstall script
curl -fsSL https://raw.githubusercontent.com/your-username/New_EM_Project/main/uninstall-stable-ubuntu-24.04.sh | bash
```

### Local Uninstall
```bash
# Make the script executable and run it
chmod +x uninstall-stable-ubuntu-24.04.sh
./uninstall-stable-ubuntu-24.04.sh
```

**⚠️ Note:** The uninstall script preserves PostgreSQL database and data.

## 📋 System Requirements

### Minimum Requirements
- **OS**: Ubuntu 24.04 LTS
- **RAM**: 2GB (4GB+ recommended)
- **Storage**: 20GB+ disk space
- **CPU**: 2 cores (4+ recommended)

### Software Dependencies
- **Node.js**: 20.x (LTS)
- **PostgreSQL**: 15+
- **Redis**: 7+
- **Apache**: 2.4+
- **PM2**: Latest

### Browser Support
- Chrome 90+
- Firefox 88+
- Safari 14+
- Edge 90+

## 🏗️ Architecture Overview

### Backend (Express.js)
```
src/
├── config/           # Configuration management
├── database/         # Migrations and seeds
├── routes/           # API endpoints
├── services/         # Business logic
├── middleware/       # Authentication and validation
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
- **12 Core Tables**: Users, Events, Contests, Categories, etc.
- **JSONB Support**: Flexible data storage
- **Audit Logging**: Complete activity tracking
- **Soft Deletes**: Data retention and recovery

## 🔐 Authentication System

### Session-Based Authentication
- **No JWT complexity**: Simple session-based authentication
- **Redis sessions**: Scalable session storage
- **Automatic expiration**: Configurable session timeout
- **Secure cookies**: HttpOnly, Secure, SameSite protection

### User Roles & Permissions
1. **Organizer** - Full system access
2. **Judge** - Scoring and evaluation
3. **Contestant** - View results
4. **Emcee** - Event hosting
5. **Tally Master** - Score verification
6. **Auditor** - Final certification
7. **Board** - Executive oversight

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/logout` - User logout
- `GET /api/auth/me` - Get current user
- `PUT /api/auth/profile` - Update profile
- `PUT /api/auth/password` - Change password
- `GET /api/auth/status` - Check authentication status

### Events
- `GET /api/events` - List events
- `POST /api/events` - Create event
- `GET /api/events/:id` - Get event details
- `PUT /api/events/:id` - Update event
- `DELETE /api/events/:id` - Delete event

### Contests
- `GET /api/contests/event/:eventId` - List contests for event
- `POST /api/contests` - Create contest
- `GET /api/contests/:id` - Get contest details
- `PUT /api/contests/:id` - Update contest
- `DELETE /api/contests/:id` - Delete contest

### Scoring
- `POST /api/scoring/submit` - Submit score
- `PUT /api/scoring/:id/certify` - Certify score
- `GET /api/scoring/subcategory/:id` - Get subcategory scores

### Results & Reporting
- `GET /api/results/event/:id` - Event results
- `GET /api/results/event/:id/report/pdf` - PDF report
- `GET /api/results/event/:id/report/excel` - Excel report

## 🛡️ Security Features

### Authentication & Authorization
- Session-based authentication
- Role-based access control
- Permission-based access control
- Password hashing with bcrypt

### Data Protection
- Input validation and sanitization
- SQL injection prevention
- XSS protection
- CSRF protection
- Rate limiting

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
- **Compression**: Gzip compression for responses

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

## 🔧 Troubleshooting

### Common Issues

**Application Won't Start:**
```bash
# Check PM2 logs
pm2 logs event-manager-api

# Restart application
pm2 restart event-manager-api

# Check environment variables
pm2 show event-manager-api
```

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

**Apache 502 Bad Gateway:**
```bash
# Check if API is running
pm2 status

# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

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
- **API Health:** `http://your-domain.com/api/health`
- **Database:** Check PostgreSQL connection
- **Redis:** Check Redis connection
- **Disk Space:** Monitor `/opt/event-manager` and `/var/log`

### Log Management
```bash
# Rotate logs
sudo logrotate -f /etc/logrotate.d/apache2
pm2 flush

# Monitor logs in real-time
pm2 logs --lines 100
sudo tail -f /var/log/apache2/access.log
```

### Backup & Recovery
```bash
# Manual backup
/opt/event-manager/backup.sh

# Restore database
sudo -u postgres psql event_manager < /opt/event-manager/backups/database_YYYYMMDD_HHMMSS.sql

# Restore application
tar -xzf /opt/event-manager/backups/application_YYYYMMDD_HHMMSS.tar.gz -C /
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
