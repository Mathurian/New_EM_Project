# Docker Deployment Guide

This guide provides comprehensive instructions for deploying the Event Manager application using Docker containers.

## 🐳 Docker Architecture

### Service Overview
The Event Manager application consists of 4 containerized services:

```
┌─────────────────┐    ┌─────────────────┐    ┌─────────────────┐
│   Frontend      │    │   Backend API   │    │   PostgreSQL    │
│   (Nginx)       │◄──►│   (Node.js)     │◄──►│   Database      │
│   Port: 80      │    │   Port: 3000    │    │   Port: 5432    │
└─────────────────┘    └─────────────────┘    └─────────────────┘
                                │
                                ▼
                       ┌─────────────────┐
                       │   Redis Cache    │
                       │   Port: 6379     │
                       └─────────────────┘
```

### Container Details

| Service | Image | Port | Purpose | Resources |
|---------|-------|------|---------|-----------|
| **Frontend** | nginx:alpine | 80 | Serves React app | ~15MB, ~10MB RAM |
| **Backend** | node:18-alpine | 3000 | API server | ~120MB, ~50MB RAM |
| **PostgreSQL** | postgres:15 | 5432 | Database | ~200MB, ~100MB RAM |
| **Redis** | redis:7-alpine | 6379 | Cache/Sessions | ~10MB, ~5MB RAM |

## 🚀 Quick Start

### Prerequisites
- Docker Engine 20.10+
- Docker Compose 2.0+
- 2GB+ available RAM
- 1GB+ available disk space

### Basic Deployment
```bash
# Clone repository
git clone <repository-url>
cd event-manager

# Start all services
docker-compose up -d

# View logs
docker-compose logs -f

# Check status
docker-compose ps
```

### Access Application
- **Frontend**: http://localhost:3001
- **Backend API**: http://localhost:3000
- **Default Login**: admin@eventmanager.com / admin123

## 📋 Docker Files Explained

### `docker-compose.yml`
**Purpose**: Orchestrates all services and manages the application stack

**Key Features**:
- **Service Dependencies**: Backend waits for database and Redis
- **Health Checks**: Monitors service health
- **Volume Management**: Persistent data storage
- **Network Configuration**: Internal service communication
- **Environment Variables**: Service configuration

**Configuration**:
```yaml
version: '3.8'

services:
  postgres:
    image: postgres:15
    environment:
      POSTGRES_DB: event_manager
      POSTGRES_USER: event_manager
      POSTGRES_PASSWORD: password
    volumes:
      - postgres_data:/var/lib/postgresql/data
      - ./init.sql:/docker-entrypoint-initdb.d/init.sql
    healthcheck:
      test: ["CMD-SHELL", "pg_isready -U event_manager -d event_manager"]
      interval: 10s
      timeout: 5s
      retries: 5

  redis:
    image: redis:7-alpine
    volumes:
      - redis_data:/data
    healthcheck:
      test: ["CMD", "redis-cli", "ping"]
      interval: 10s
      timeout: 5s
      retries: 5

  backend:
    build:
      context: .
      dockerfile: Dockerfile.backend
    environment:
      NODE_ENV: production
      DATABASE_URL: postgresql://event_manager:password@postgres:5432/event_manager
      REDIS_URL: redis://redis:6379
    depends_on:
      postgres:
        condition: service_healthy
      redis:
        condition: service_healthy
    volumes:
      - ./uploads:/app/uploads
      - ./logs:/app/logs

  frontend:
    build:
      context: ./frontend
      dockerfile: Dockerfile
    depends_on:
      - backend
```

### `Dockerfile.backend`
**Purpose**: Creates production-ready Node.js backend container

**Build Process**:
1. **Base Image**: node:18-alpine (lightweight Linux)
2. **Dependencies**: Install production dependencies only
3. **Prisma**: Generate database client
4. **Directories**: Create uploads and logs directories
5. **Health Check**: Monitor application health
6. **Start Command**: Run the application

**Key Features**:
- **Multi-stage optimization**: Minimal final image size
- **Security**: Non-root user execution
- **Health monitoring**: Built-in health checks
- **Production ready**: Optimized for production use

### `frontend/Dockerfile`
**Purpose**: Creates production-ready React frontend container

**Build Process**:
1. **Build Stage**: Install dependencies and build React app
2. **Production Stage**: Serve built files with Nginx
3. **Optimization**: Minified, compressed assets
4. **Security**: Nginx security headers

**Key Features**:
- **Multi-stage build**: Separate build and runtime environments
- **Nginx optimization**: Efficient static file serving
- **Security headers**: XSS protection, content type validation
- **Gzip compression**: Reduced bandwidth usage

### `init.sql`
**Purpose**: Database initialization script

**Functions**:
- **Database Creation**: Creates database if it doesn't exist
- **User Management**: Creates user with proper permissions
- **Privileges**: Grants necessary database permissions
- **Idempotent**: Safe to run multiple times

## 🔧 Configuration

### Environment Variables

#### Backend Environment
```bash
# Application
NODE_ENV=production
PORT=3000

# Database
DATABASE_URL=postgresql://event_manager:password@postgres:5432/event_manager

# Redis
REDIS_URL=redis://redis:6379

# Security
JWT_SECRET=your-super-secret-jwt-key
SESSION_SECRET=your-super-secret-session-key

# Email (Optional)
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-password
SMTP_FROM=noreply@eventmanager.com
```

#### Frontend Environment
```bash
# API Configuration
VITE_API_URL=http://localhost:3000
VITE_APP_NAME=Event Manager
VITE_APP_VERSION=1.0.0
VITE_APP_URL=http://localhost:3001
```

### Custom Configuration

#### Custom Database Credentials
```bash
# Create custom environment file
cat > .env << EOF
POSTGRES_DB=my_event_manager
POSTGRES_USER=my_user
POSTGRES_PASSWORD=my_secure_password
DATABASE_URL=postgresql://my_user:my_secure_password@postgres:5432/my_event_manager
EOF

# Use custom environment
docker-compose --env-file .env up -d
```

#### Custom Ports
```yaml
# docker-compose.override.yml
version: '3.8'
services:
  frontend:
    ports:
      - "8080:80"  # Frontend on port 8080
  backend:
    ports:
      - "8081:3000"  # Backend on port 8081
```

## 🚀 Deployment Scenarios

### Development Environment
```bash
# Start with hot reload
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up

# Rebuild on changes
docker-compose up --build

# View logs
docker-compose logs -f backend frontend
```

### Staging Environment
```bash
# Staging configuration
docker-compose -f docker-compose.yml -f docker-compose.staging.yml up -d

# Scale services
docker-compose up --scale backend=2 --scale frontend=2
```

### Production Environment
```bash
# Production deployment
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# High availability
docker-compose up --scale backend=3 --scale frontend=2
```

## 📊 Monitoring & Maintenance

### Health Checks
```bash
# Check service health
docker-compose ps

# View health check logs
docker-compose logs backend | grep health

# Manual health check
curl http://localhost:3000/health
```

### Log Management
```bash
# View all logs
docker-compose logs

# View specific service logs
docker-compose logs backend
docker-compose logs frontend
docker-compose logs postgres

# Follow logs in real-time
docker-compose logs -f backend

# View last 100 lines
docker-compose logs --tail=100 backend
```

### Database Management
```bash
# Connect to database
docker-compose exec postgres psql -U event_manager -d event_manager

# Run migrations
docker-compose exec backend npm run migrate

# Seed database
docker-compose exec backend npm run seed

# Backup database
docker-compose exec postgres pg_dump -U event_manager event_manager > backup.sql

# Restore database
docker-compose exec -T postgres psql -U event_manager -d event_manager < backup.sql
```

### Container Management
```bash
# Restart services
docker-compose restart

# Restart specific service
docker-compose restart backend

# Stop services
docker-compose down

# Stop and remove volumes
docker-compose down -v

# Update services
docker-compose pull
docker-compose up -d
```

## 🔧 Troubleshooting

### Common Issues

#### Container Won't Start
```bash
# Check logs
docker-compose logs backend

# Check resource usage
docker stats

# Restart services
docker-compose restart
```

#### Database Connection Issues
```bash
# Check database status
docker-compose exec postgres pg_isready -U event_manager

# Check database logs
docker-compose logs postgres

# Reset database
docker-compose down -v
docker-compose up -d
```

#### Port Conflicts
```bash
# Check port usage
netstat -tulpn | grep :3000

# Use different ports
docker-compose -f docker-compose.yml -f docker-compose.override.yml up
```

#### Memory Issues
```bash
# Check memory usage
docker stats

# Increase memory limits
# In docker-compose.yml:
services:
  backend:
    deploy:
      resources:
        limits:
          memory: 512M
```

### Performance Optimization

#### Resource Limits
```yaml
# docker-compose.prod.yml
version: '3.8'
services:
  backend:
    deploy:
      resources:
        limits:
          memory: 512M
          cpus: '0.5'
        reservations:
          memory: 256M
          cpus: '0.25'
```

#### Scaling
```bash
# Scale backend service
docker-compose up --scale backend=3

# Scale with load balancer
docker-compose -f docker-compose.yml -f docker-compose.scale.yml up
```

## 🔒 Security Considerations

### Container Security
- **Non-root users**: Containers run as non-root
- **Read-only filesystems**: Where possible
- **Security scanning**: Regular vulnerability scans
- **Image updates**: Keep base images updated

### Network Security
- **Internal networks**: Services communicate internally
- **Port exposure**: Only necessary ports exposed
- **Firewall rules**: Restrict external access
- **SSL/TLS**: Use HTTPS in production

### Data Security
- **Volume encryption**: Encrypt persistent volumes
- **Backup encryption**: Encrypt database backups
- **Secret management**: Use Docker secrets for sensitive data
- **Access control**: Limit database access

## 📈 Scaling & High Availability

### Horizontal Scaling
```bash
# Scale backend service
docker-compose up --scale backend=3

# Scale with load balancer
docker-compose -f docker-compose.yml -f docker-compose.scale.yml up
```

### Load Balancer Configuration
```yaml
# docker-compose.scale.yml
version: '3.8'
services:
  nginx:
    image: nginx:alpine
    ports:
      - "80:80"
    volumes:
      - ./nginx-lb.conf:/etc/nginx/nginx.conf
    depends_on:
      - backend
      - frontend
```

### High Availability Setup
```bash
# Multi-node deployment
docker-compose -f docker-compose.yml -f docker-compose.ha.yml up

# Database clustering
docker-compose -f docker-compose.yml -f docker-compose.cluster.yml up
```

## 🚀 CI/CD Integration

### GitHub Actions
```yaml
# .github/workflows/docker.yml
name: Docker Build and Deploy
on:
  push:
    branches: [main]
jobs:
  deploy:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v2
      - name: Build and deploy
        run: |
          docker-compose -f docker-compose.prod.yml up -d
```

### GitLab CI
```yaml
# .gitlab-ci.yml
deploy:
  stage: deploy
  script:
    - docker-compose -f docker-compose.prod.yml up -d
  only:
    - main
```

## 📚 Additional Resources

### Docker Commands Reference
```bash
# Basic commands
docker-compose up -d          # Start services
docker-compose down           # Stop services
docker-compose ps             # List services
docker-compose logs           # View logs
docker-compose exec <service> <command>  # Execute command

# Management commands
docker-compose pull           # Pull latest images
docker-compose build          # Build images
docker-compose restart        # Restart services
docker-compose scale <service>=<count>  # Scale service
```

### Useful Scripts
```bash
# Start development environment
#!/bin/bash
docker-compose -f docker-compose.yml -f docker-compose.dev.yml up

# Deploy to production
#!/bin/bash
docker-compose -f docker-compose.yml -f docker-compose.prod.yml up -d

# Backup database
#!/bin/bash
docker-compose exec postgres pg_dump -U event_manager event_manager > backup_$(date +%Y%m%d_%H%M%S).sql
```

---

For more information, see the main [README.md](README.md) file or run `docker-compose --help` for Docker Compose commands.
