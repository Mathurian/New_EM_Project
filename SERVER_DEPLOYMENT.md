# Server Deployment Guide

This guide provides comprehensive instructions for deploying the Event Manager application on a remote Linux server with proper permissions and security configurations.

## 🚀 **Quick Server Deployment**

### **Automated Server Setup**
```bash
# Full automated server deployment
./setup.sh --non-interactive --auto-setup-permissions

# With custom web server user
./setup.sh --non-interactive --auto-setup-permissions --web-server-user=www-data

# With custom database configuration
./setup.sh --non-interactive --auto-setup-permissions \
  --db-host=your-db-server.com \
  --db-password=secure-password \
  --app-env=production
```

### **Interactive Server Setup**
```bash
# Interactive setup with permissions
./setup.sh --auto-setup-permissions

# Specify web server user
./setup.sh --web-server-user=www-data
```

## 🔧 **Permission Management**

### **Automatic Web Server Detection**

The setup script automatically detects your web server and configures permissions accordingly:

#### **Supported Web Servers:**
- **Nginx** → `www-data:www-data`
- **Apache2** → `www-data:www-data` 
- **Apache (httpd)** → `apache:apache`
- **Default** → `www-data:www-data`

#### **Detection Process:**
```bash
# The script checks for running processes:
pgrep -f nginx    # Nginx detection
pgrep -f apache2  # Apache2 detection  
pgrep -f httpd    # Apache httpd detection
```

### **Manual Permission Configuration**

If automatic detection fails, you can specify the web server user manually:

```bash
# For Nginx/Apache2 (Ubuntu/Debian)
./setup.sh --web-server-user=www-data

# For Apache (CentOS/RHEL)
./setup.sh --web-server-user=apache

# For custom web server user
./setup.sh --web-server-user=your-web-user
```

## 📁 **File Permissions Structure**

### **Directory Permissions (755)**
```
drwxr-xr-x  www-data www-data  /path/to/event-manager/
drwxr-xr-x  www-data www-data  src/
drwxr-xr-x  www-data www-data  frontend/
drwxr-xr-x  www-data www-data  uploads/
drwxr-xr-x  www-data www-data  logs/
drwxr-xr-x  www-data www-data  node_modules/
```

### **File Permissions (644)**
```
-rw-r--r--  www-data www-data  package.json
-rw-r--r--  www-data www-data  .env
-rw-r--r--  www-data www-data  src/server.js
-rw-r--r--  www-data www-data  frontend/package.json
```

### **Executable Files (755)**
```
-rwxr-xr-x  www-data www-data  setup.sh
-rwxr-xr-x  www-data www-data  install.sh
```

### **Sensitive Files (600)**
```
-rw-------  www-data www-data  .env
-rw-------  www-data www-data  frontend/.env
```

## 🔒 **Security Configuration**

### **Environment File Security**
```bash
# Environment files are secured with 600 permissions
chmod 600 .env frontend/.env

# Only the web server user can read/write
# Other users have no access
```

### **Upload Directory Security**
```bash
# Upload directories are writable but not executable
chmod 755 uploads logs

# Prevents execution of uploaded scripts
# Web server can write files but cannot execute them
```

### **Node Modules Security**
```bash
# All node_modules files are secured
find node_modules -type d -exec chmod 755 {} \;
find node_modules -type f -exec chmod 644 {} \;

# Prevents unauthorized script execution
# Maintains functionality while ensuring security
```

## 🌐 **Web Server Configuration**

### **Nginx Configuration**

#### **Backend Proxy (Node.js)**
```nginx
# /etc/nginx/sites-available/event-manager
server {
    listen 80;
    server_name your-domain.com;
    
    # Backend API
    location /api/ {
        proxy_pass http://localhost:3000;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection 'upgrade';
        proxy_set_header Host $host;
        proxy_set_header X-Real-IP $remote_addr;
        proxy_set_header X-Forwarded-For $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
        proxy_cache_bypass $http_upgrade;
    }
    
    # Frontend static files
    location / {
        root /path/to/event-manager/frontend/dist;
        try_files $uri $uri/ /index.html;
        
        # Security headers
        add_header X-Frame-Options "SAMEORIGIN" always;
        add_header X-Content-Type-Options "nosniff" always;
        add_header X-XSS-Protection "1; mode=block" always;
    }
    
    # Upload directory (no execution)
    location /uploads/ {
        root /path/to/event-manager;
        add_header X-Content-Type-Options "nosniff" always;
        location ~* \.(php|pl|py|jsp|asp|sh|cgi)$ {
            deny all;
        }
    }
}
```

#### **Enable Site**
```bash
# Enable the site
sudo ln -s /etc/nginx/sites-available/event-manager /etc/nginx/sites-enabled/

# Test configuration
sudo nginx -t

# Reload Nginx
sudo systemctl reload nginx
```

### **Apache Configuration**

#### **Virtual Host**
```apache
# /etc/apache2/sites-available/event-manager.conf
<VirtualHost *:80>
    ServerName your-domain.com
    DocumentRoot /path/to/event-manager/frontend/dist
    
    # Backend API proxy
    ProxyPreserveHost On
    ProxyPass /api/ http://localhost:3000/api/
    ProxyPassReverse /api/ http://localhost:3000/api/
    
    # Frontend static files
    <Directory /path/to/event-manager/frontend/dist>
        Options -Indexes
        AllowOverride All
        Require all granted
    </Directory>
    
    # Upload directory security
    <Directory /path/to/event-manager/uploads>
        Options -Indexes -ExecCGI
        AllowOverride None
        Require all granted
        
        # Prevent script execution
        <FilesMatch "\.(php|pl|py|jsp|asp|sh|cgi)$">
            Require all denied
        </FilesMatch>
    </Directory>
    
    # Security headers
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</VirtualHost>
```

#### **Enable Modules and Site**
```bash
# Enable required modules
sudo a2enmod proxy
sudo a2enmod proxy_http
sudo a2enmod headers
sudo a2enmod rewrite

# Enable the site
sudo a2ensite event-manager

# Reload Apache
sudo systemctl reload apache2
```

## 🐳 **Docker Server Deployment**

### **Production Docker Compose**
```yaml
# docker-compose.prod.yml
version: '3.8'

services:
  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: event_manager
      POSTGRES_USER: event_manager
      POSTGRES_PASSWORD: ${DB_PASSWORD}
    volumes:
      - postgres_data:/var/lib/postgresql/data
    restart: unless-stopped
    networks:
      - event-manager-network

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    restart: unless-stopped
    networks:
      - event-manager-network

  backend:
    build:
      context: .
      dockerfile: Dockerfile.backend
    environment:
      NODE_ENV: production
      DATABASE_URL: postgresql://event_manager:${DB_PASSWORD}@postgres:5432/event_manager
      REDIS_URL: redis://redis:6379
      JWT_SECRET: ${JWT_SECRET}
    volumes:
      - ./uploads:/app/uploads
      - ./logs:/app/logs
    restart: unless-stopped
    networks:
      - event-manager-network
    depends_on:
      - postgres
      - redis

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    restart: unless-stopped
    networks:
      - event-manager-network
    depends_on:
      - backend

volumes:
  postgres_data:
  redis_data:

networks:
  event-manager-network:
    driver: bridge
```

### **Deploy with Docker**
```bash
# Create production environment file
cat > .env.prod << EOF
DB_PASSWORD=your-secure-password
JWT_SECRET=your-jwt-secret
EOF

# Deploy with Docker Compose
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Check status
docker-compose ps
```

## 🔧 **Process Management**

### **PM2 Process Manager**

#### **Install PM2**
```bash
# Install PM2 globally
sudo npm install -g pm2

# Or install as web server user
sudo -u www-data npm install -g pm2
```

#### **PM2 Configuration**
```javascript
// ecosystem.config.js
module.exports = {
  apps: [{
    name: 'event-manager-backend',
    script: 'src/server.js',
    cwd: '/path/to/event-manager',
    user: 'www-data',
    instances: 'max',
    exec_mode: 'cluster',
    env: {
      NODE_ENV: 'production',
      PORT: 3000
    },
    error_file: './logs/err.log',
    out_file: './logs/out.log',
    log_file: './logs/combined.log',
    time: true
  }]
}
```

#### **PM2 Commands**
```bash
# Start application
pm2 start ecosystem.config.js

# Monitor
pm2 monit

# Restart
pm2 restart event-manager-backend

# Stop
pm2 stop event-manager-backend

# Save PM2 configuration
pm2 save

# Setup PM2 startup
pm2 startup
```

### **Systemd Service**

#### **Backend Service**
```ini
# /etc/systemd/system/event-manager-backend.service
[Unit]
Description=Event Manager Backend
After=network.target postgresql.service

[Service]
Type=simple
User=www-data
Group=www-data
WorkingDirectory=/path/to/event-manager
ExecStart=/usr/bin/node src/server.js
Restart=always
RestartSec=10
Environment=NODE_ENV=production
Environment=PORT=3000

[Install]
WantedBy=multi-user.target
```

#### **Enable and Start Service**
```bash
# Reload systemd
sudo systemctl daemon-reload

# Enable service
sudo systemctl enable event-manager-backend

# Start service
sudo systemctl start event-manager-backend

# Check status
sudo systemctl status event-manager-backend
```

## 🔍 **Troubleshooting Server Issues**

### **Permission Issues**
```bash
# Check file ownership
ls -la /path/to/event-manager

# Fix ownership
sudo chown -R www-data:www-data /path/to/event-manager

# Fix permissions
sudo find /path/to/event-manager -type d -exec chmod 755 {} \;
sudo find /path/to/event-manager -type f -exec chmod 644 {} \;

# Secure sensitive files
sudo chmod 600 /path/to/event-manager/.env
```

### **Web Server Issues**
```bash
# Check web server status
sudo systemctl status nginx
sudo systemctl status apache2

# Check web server logs
sudo tail -f /var/log/nginx/error.log
sudo tail -f /var/log/apache2/error.log

# Test configuration
sudo nginx -t
sudo apache2ctl configtest
```

### **Application Issues**
```bash
# Check application logs
tail -f /path/to/event-manager/logs/event-manager.log

# Check PM2 logs
pm2 logs event-manager-backend

# Check systemd logs
sudo journalctl -u event-manager-backend -f
```

### **Database Issues**
```bash
# Check PostgreSQL status
sudo systemctl status postgresql

# Check database connection
psql -h localhost -p 5432 -U event_manager -d event_manager

# Check database logs
sudo tail -f /var/log/postgresql/postgresql-*.log
```

## 📊 **Monitoring and Maintenance**

### **Log Rotation**
```bash
# /etc/logrotate.d/event-manager
/path/to/event-manager/logs/*.log {
    daily
    missingok
    rotate 30
    compress
    delaycompress
    notifempty
    create 644 www-data www-data
    postrotate
        systemctl reload event-manager-backend
    endscript
}
```

### **Backup Script**
```bash
#!/bin/bash
# backup-event-manager.sh

BACKUP_DIR="/backups/event-manager"
APP_DIR="/path/to/event-manager"
DATE=$(date +%Y%m%d_%H%M%S)

# Create backup directory
mkdir -p $BACKUP_DIR

# Backup application files
tar -czf $BACKUP_DIR/app_$DATE.tar.gz -C $APP_DIR .

# Backup database
pg_dump -h localhost -U event_manager event_manager > $BACKUP_DIR/db_$DATE.sql

# Clean old backups (keep 30 days)
find $BACKUP_DIR -name "*.tar.gz" -mtime +30 -delete
find $BACKUP_DIR -name "*.sql" -mtime +30 -delete

echo "Backup completed: $DATE"
```

### **Health Check Script**
```bash
#!/bin/bash
# health-check.sh

# Check if backend is responding
if curl -f http://localhost:3000/health > /dev/null 2>&1; then
    echo "Backend: OK"
else
    echo "Backend: FAILED"
    # Restart service
    systemctl restart event-manager-backend
fi

# Check if frontend is accessible
if curl -f http://localhost:3001 > /dev/null 2>&1; then
    echo "Frontend: OK"
else
    echo "Frontend: FAILED"
fi

# Check database connection
if psql -h localhost -U event_manager -d event_manager -c '\q' > /dev/null 2>&1; then
    echo "Database: OK"
else
    echo "Database: FAILED"
fi
```

## ✅ **Deployment Checklist**

### **Pre-Deployment**
- [ ] Server meets system requirements (Node.js 18+, PostgreSQL 12+)
- [ ] Web server (Nginx/Apache) is installed and configured
- [ ] Firewall rules allow ports 80, 443, 3000, 5432
- [ ] SSL certificate is configured (for production)
- [ ] Domain name points to server IP

### **Deployment**
- [ ] Clone repository to server
- [ ] Run setup script with server options
- [ ] Configure web server virtual host
- [ ] Set up process manager (PM2/systemd)
- [ ] Configure log rotation
- [ ] Set up monitoring and alerts

### **Post-Deployment**
- [ ] Test all application functionality
- [ ] Verify file permissions and ownership
- [ ] Check application logs for errors
- [ ] Test backup and restore procedures
- [ ] Monitor system resources
- [ ] Set up automated backups

## 🆘 **Support**

For server deployment issues:
- Check [TROUBLESHOOTING.md](TROUBLESHOOTING.md) for common issues
- Review web server logs for configuration errors
- Verify file permissions and ownership
- Check application logs for runtime errors

For more information:
- [README.md](README.md) - Main documentation
- [DOCKER.md](DOCKER.md) - Docker deployment guide
- [SETUP.md](SETUP.md) - Setup script documentation
