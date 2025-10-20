# Event Manager - Stable Ubuntu 24.04 Installation

A comprehensive event management system built with Express.js, React, TypeScript, and PostgreSQL, designed for Ubuntu 24.04 with Apache web server.

## 🚀 Quick Start

### Prerequisites

- Ubuntu 24.04 LTS
- Root or sudo access
- Internet connection

### One-Command Installation

```bash
# Download and run the installation script
curl -fsSL https://raw.githubusercontent.com/your-repo/event-manager/main/install-stable-ubuntu-24.04.sh | bash
```

Or manually:

```bash
# Clone the repository
git clone https://github.com/your-repo/event-manager.git
cd event-manager

# Make installation script executable
chmod +x install-stable-ubuntu-24.04.sh

# Run installation
sudo ./install-stable-ubuntu-24.04.sh
```

### Running the Application

After installation, start the application:

```bash
# Terminal 1 - Start Backend API
cd /opt/event-manager/event-manager-api
npm start

# Terminal 2 - Start Frontend (Development)
cd /opt/event-manager/event-manager-frontend
npm run dev

# For Production with Apache - Build frontend first
cd /opt/event-manager/event-manager-frontend
npm run build
```

### Apache Configuration (Production)

For production deployment with Apache:

```bash
# Enable required Apache modules
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod proxy_wstunnel
sudo a2enmod rewrite
sudo a2enmod ssl

# Create Apache virtual host configuration
sudo tee /etc/apache2/sites-available/event-manager.conf > /dev/null << 'EOF'
<VirtualHost *:80>
    ServerName localhost
    ServerAlias *
    DocumentRoot /opt/event-manager/event-manager-frontend/dist
    
    # Proxy API requests to backend
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:3000/api/
    ProxyPassReverse /api/ http://localhost:3000/api/
    
    # Proxy WebSocket connections
    ProxyPass /socket.io/ ws://localhost:3000/socket.io/
    ProxyPassReverse /socket.io/ ws://localhost:3000/socket.io/
    
    # Serve static frontend files
    <Directory /opt/event-manager/event-manager-frontend/dist>
        Options -Indexes +FollowSymLinks
        AllowOverride All
        Require all granted
        
        # Handle client-side routing
        RewriteEngine On
        RewriteBase /
        RewriteRule ^index\.html$ - [L]
        RewriteCond %{REQUEST_FILENAME} !-f
        RewriteCond %{REQUEST_FILENAME} !-d
        RewriteRule . /index.html [L]
    </Directory>
    
    # Security headers
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
    Header always set X-XSS-Protection "1; mode=block"
    
    ErrorLog ${APACHE_LOG_DIR}/event-manager_error.log
    CustomLog ${APACHE_LOG_DIR}/event-manager_access.log combined
</VirtualHost>
EOF

# Disable default Apache site
sudo a2dissite 000-default

# Enable the event-manager site
sudo a2ensite event-manager.conf

# Restart Apache
sudo systemctl restart apache2
```

### Troubleshooting Apache Configuration

If you still see the default Apache page:

```bash
# Check which sites are enabled
sudo a2ensite --list

# Verify the configuration syntax
sudo apache2ctl configtest

# Check Apache error logs
sudo tail -f /var/log/apache2/error.log

# Check if the frontend build exists
ls -la /opt/event-manager/event-manager-frontend/dist/

# Force disable default site and enable our site
sudo a2dissite 000-default.conf
sudo a2ensite event-manager.conf
sudo systemctl reload apache2

# Alternative: Replace default site configuration
sudo cp /etc/apache2/sites-available/event-manager.conf /etc/apache2/sites-available/000-default.conf
sudo systemctl restart apache2
```

### Access the Application

- **Frontend**: http://localhost:3000 (development) or http://localhost (production)
- **API**: http://localhost:3000/api
- **Health Check**: http://localhost:3000/api/health
- **Apache Production**: http://localhost (port 80) or https://localhost (port 443)

### Default Credentials

- **Admin User**: admin@example.com / admin123
- **Database**: event_manager / (password from installation)

### Reset Admin User Data

If you need to reset the admin user data before logging in:

#### Method 1: Database Reset (Recommended)
```bash
# Connect to PostgreSQL
sudo -u postgres psql event_manager

# Reset admin user password
UPDATE users SET password = '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi' WHERE email = 'admin@example.com';

# Reset user profile data
UPDATE users SET 
    first_name = 'Admin',
    last_name = 'User',
    role = 'admin',
    is_active = true,
    updated_at = CURRENT_TIMESTAMP
WHERE email = 'admin@example.com';

# Exit PostgreSQL
\q
```

#### Method 2: Full Database Reset
```bash
# Stop the application
sudo systemctl stop event-manager-api

# Reset database completely
cd /opt/event-manager/event-manager-api
npm run db:migrate:rollback
npm run db:migrate
npm run db:seed

# Restart the application
sudo systemctl start event-manager-api
```

#### Method 3: Manual User Creation
```bash
# Connect to PostgreSQL
sudo -u postgres psql event_manager

# Delete existing admin user
DELETE FROM users WHERE email = 'admin@example.com';

# Create new admin user
INSERT INTO users (id, email, password, first_name, last_name, role, is_active, created_at, updated_at) 
VALUES (
    gen_random_uuid(),
    'admin@example.com',
    '$2a$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi',
    'Admin',
    'User',
    'admin',
    true,
    CURRENT_TIMESTAMP,
    CURRENT_TIMESTAMP
);

# Exit PostgreSQL
\q
```

#### Method 4: Using Reset Script
```bash
# Run the database reset script
cd /opt/event-manager/event-manager-api
./reset-database.sh
```

**Note**: The password hash above corresponds to `admin123`. To use a different password, generate a new bcrypt hash.

### Quick Start Checklist

1. ✅ **Ensure database is running**: `sudo systemctl status postgresql`
2. ✅ **Ensure Redis is running**: `sudo systemctl status redis-server`
3. ✅ **Ensure Apache is running**: `sudo systemctl status apache2`
4. ✅ **Start backend**: `cd event-manager-api && npm start`
5. ✅ **Start frontend**: `cd event-manager-frontend && npm run dev`
6. ✅ **Access application**: http://localhost:3000 (dev) or http://localhost (production)

## 📋 Installation Process

The installation script will automatically:

1. **Update system packages**
2. **Install Node.js 20.x LTS** (via NodeSource PPA)
3. **Install system dependencies**:
   - Build tools (build-essential, python3, make, g++)
   - Image processing libraries (libpng-dev, libjpeg-dev, libwebp-dev)
   - PostgreSQL client libraries
   - Redis server
   - Apache web server with SSL support
4. **Configure PostgreSQL** database
5. **Install Redis** for session storage
6. **Configure Apache** with proxy modules
7. **Install application dependencies**
8. **Run database migrations and seeds**
9. **Set up SSL certificates** (optional)
10. **Configure firewall** (UFW)

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

### Backend (Express.js + PostgreSQL)
- **Framework**: Express.js with session-based authentication
- **Database**: PostgreSQL with Knex.js ORM
- **Validation**: Joi schema validation
- **Caching**: Redis for session storage
- **File Uploads**: Multer for handling file uploads
- **Security**: Helmet, CORS, rate limiting

### Frontend (React + TypeScript)
- **Framework**: React 18 with TypeScript
- **Build Tool**: Vite
- **State Management**: Zustand
- **Data Fetching**: TanStack React Query v5
- **UI Components**: Custom components with Tailwind CSS
- **Icons**: Lucide React
- **Routing**: React Router v6

### Web Server (Apache)
- **Proxy Configuration**: Reverse proxy to Node.js backend
- **WebSocket Support**: Proxy for real-time features
- **SSL/TLS**: Automatic certificate management with Certbot
- **Static Files**: Serves React build files

## 🔧 Configuration

### Environment Variables

The application uses environment variables for configuration. Key variables:

```bash
# Database
DB_HOST=localhost
DB_PORT=5432
DB_NAME=event_manager
DB_USER=event_manager
DB_PASSWORD=your_secure_password

# Redis
REDIS_HOST=localhost
REDIS_PORT=6379

# Session
SESSION_SECRET=your_session_secret_key

# API
API_PORT=3000
API_URL=http://localhost:3000/api

# Frontend
VITE_API_URL=http://localhost:3000/api
```

### Database Setup

The installation script automatically:
- Creates PostgreSQL database
- Runs migrations
- Seeds initial data
- Sets up user accounts

### Apache Configuration

Apache is configured with:
- Reverse proxy to backend API
- WebSocket proxy for real-time features
- Static file serving for frontend
- SSL/TLS termination

## 🚀 Usage

### Starting the Application

```bash
# Start backend API
cd /opt/event-manager/event-manager-api
npm start

# Start frontend (development)
cd /opt/event-manager/event-manager-frontend
npm run dev

# Build frontend for production
npm run build
```

### Accessing the Application

- **Frontend**: http://localhost (or your domain)
- **API**: http://localhost:3000/api
- **Admin Panel**: http://localhost/admin

### Default Credentials

- **Admin User**: admin@example.com / admin123
- **Database**: event_manager / (password from installation)

## 🛠️ Development

### Backend Development

```bash
cd event-manager-api

# Install dependencies
npm install

# Run in development mode
npm run dev

# Run database migrations
npm run db:migrate

# Seed database
npm run db:seed

# Run tests
npm test
```

### Frontend Development

```bash
cd event-manager-frontend

# Install dependencies
npm install

# Run development server
npm run dev

# Type checking
npm run type-check

# Build for production
npm run build

# Preview production build
npm run preview
```

## 📁 Project Structure

```
event-manager/
├── event-manager-api/           # Backend API
│   ├── src/
│   │   ├── config/             # Configuration
│   │   ├── database/           # Database setup
│   │   ├── routes/             # API routes
│   │   ├── services/           # Business logic
│   │   └── utils/              # Utilities
│   ├── scripts/                # Database scripts
│   └── package.json
├── event-manager-frontend/      # Frontend application
│   ├── src/
│   │   ├── components/         # React components
│   │   ├── pages/              # Page components
│   │   ├── stores/             # State management
│   │   ├── lib/                # Utilities
│   │   └── hooks/              # Custom hooks
│   └── package.json
├── install-stable-ubuntu-24.04.sh
├── uninstall-stable-ubuntu-24.04.sh
└── README.md
```

## 🔒 Security Features

- **Session-based Authentication**: Secure session management
- **Input Validation**: Joi schema validation
- **SQL Injection Protection**: Knex.js ORM
- **XSS Protection**: Helmet security headers
- **CORS Configuration**: Controlled cross-origin requests
- **Rate Limiting**: API request throttling
- **SSL/TLS**: Encrypted communication
- **Firewall**: UFW configuration

## 🐛 Troubleshooting

### Common Issues

1. **Port Conflicts**: Ensure ports 80, 443, 3000, and 5432 are available
2. **Permission Issues**: Run installation script with sudo
3. **Database Connection**: Check PostgreSQL service status
4. **Build Errors**: Ensure Node.js 20.x is installed

### Logs and Debugging

```bash
# Check application logs
sudo journalctl -u event-manager-api
sudo journalctl -u event-manager-frontend

# Check Apache logs
sudo tail -f /var/log/apache2/error.log
sudo tail -f /var/log/apache2/access.log

# Check PostgreSQL logs
sudo tail -f /var/log/postgresql/postgresql-*.log
```

### Reset and Reinstall

```bash
# Uninstall application
sudo ./uninstall-stable-ubuntu-24.04.sh

# Clean reinstall
sudo ./install-stable-ubuntu-24.04.sh
```

## 🔄 Updates and Maintenance

### Updating Dependencies

```bash
# Backend
cd event-manager-api
npm update

# Frontend
cd event-manager-frontend
npm update
```

### Database Migrations

```bash
cd event-manager-api
npm run db:migrate
```

### Backup and Restore

```bash
# Backup database
pg_dump event_manager > backup.sql

# Restore database
psql event_manager < backup.sql
```

## 🌐 Production Deployment

### Domain Configuration

1. **Update DNS**: Point domain to server IP
2. **SSL Certificate**: Run Certbot for automatic SSL
3. **Environment Variables**: Set production values
4. **Database**: Use production PostgreSQL instance
5. **Monitoring**: Set up application monitoring

### Scaling Considerations

- **Load Balancing**: Use multiple Apache instances
- **Database**: Consider PostgreSQL clustering
- **Caching**: Redis cluster for high availability
- **CDN**: Use CDN for static assets

## 📝 License

This project is licensed under the MIT License - see the LICENSE file for details.

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## 📞 Support

For support and questions:
- **Issues**: GitHub Issues
- **Documentation**: Project Wiki
- **Email**: support@example.com

---

**Event Manager** - A modern, scalable event management solution for Ubuntu 24.04
