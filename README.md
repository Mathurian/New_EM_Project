# Event Manager Contest System

A modern, full-stack contest management system built with Node.js, React, and PostgreSQL. Features comprehensive role-based access control, real-time updates, and a complete Docker containerization setup.

## 🚀 Features

- **Event Management**: Create and manage events with multiple contests
- **Contest Organization**: Organize contests into categories with scoring criteria
- **User Management**: Role-based access control with 7 different user types
- **Scoring System**: Comprehensive scoring with certification workflows
- **Real-time Updates**: WebSocket integration for live updates
- **Admin Dashboard**: System monitoring and management tools
- **Responsive Design**: Modern UI with dark mode support
- **Docker Ready**: Complete containerization with Docker Compose
- **Zero-Prerequisites Setup**: Automated installation script

## 🛠 Technology Stack

### Backend
- **Node.js 18+** with Express.js
- **PostgreSQL 15** with Prisma ORM
- **Socket.IO** for real-time communication
- **JWT** for authentication
- **Winston** for logging
- **Redis** for caching (optional)

### Frontend
- **React 18** with TypeScript
- **Vite** for build tooling
- **Tailwind CSS** for styling
- **React Query** for data fetching
- **React Hook Form** for form handling
- **Socket.IO Client** for real-time updates

### Infrastructure
- **Docker** & **Docker Compose** for containerization
- **Nginx** for frontend serving
- **PostgreSQL** with persistent volumes
- **Redis** for caching and sessions

## 📦 Installation Options

### Option 1: Automated Setup (Recommended)

The setup script can install **all prerequisites automatically** and configure the entire application:

```bash
# Make script executable
chmod +x setup.sh

# Interactive setup (prompts for configuration)
./setup.sh

# Fully automated setup (no prompts)
./setup.sh --non-interactive

# Custom configuration
./setup.sh --db-host=db.example.com --db-password=secret123 --app-env=production
```

#### Setup Script Features:
- **Zero Prerequisites**: Installs Node.js, PostgreSQL, build tools automatically
- **Environment Configuration**: Prompts for or accepts all configuration variables
- **Database Setup**: Creates database, user, and runs migrations
- **Security**: Auto-generates secure JWT and session secrets
- **Multiple Modes**: Interactive, non-interactive, or selective automation

#### Command Line Options:
```bash
# Installation Options
--auto-install-prereqs    # Automatically install all prerequisites
--auto-setup-db           # Automatically setup database (migrate + seed)
--auto-cleanup-php        # Automatically remove old PHP files
--auto-create-installer   # Automatically create minimal installer
--auto-start-servers      # Automatically start development servers
--non-interactive         # Run in non-interactive mode (auto-install everything)
--skip-env-config         # Skip environment variable configuration

# Database Configuration
--db-host=HOST           # Database host (default: localhost)
--db-port=PORT           # Database port (default: 5432)
--db-name=NAME           # Database name (default: event_manager)
--db-user=USER           # Database user (default: event_manager)
--db-password=PASS       # Database password (default: password)

# Application Configuration
--jwt-secret=SECRET       # JWT secret key (required for production)
--session-secret=SECRET  # Session secret key (required for production)
--app-env=ENV            # Application environment (development/production)
--app-url=URL            # Application URL (default: http://localhost:3001)

# Email Configuration
--smtp-host=HOST         # SMTP server host
--smtp-port=PORT         # SMTP server port (default: 587)
--smtp-user=USER         # SMTP username
--smtp-pass=PASS         # SMTP password
--smtp-from=EMAIL        # From email address
```

### Option 2: Docker Deployment (Production Ready)

#### Quick Start with Docker:
```bash
# Start entire application stack
docker-compose up -d

# View logs
docker-compose logs -f

# Stop services
docker-compose down
```

#### Docker Services:
- **PostgreSQL**: Database with persistent storage
- **Redis**: Caching and session storage
- **Backend**: Node.js API server
- **Frontend**: React app served by Nginx

#### Docker Commands:
```bash
# Build and start all services
docker-compose up --build

# Start specific services
docker-compose up postgres redis

# Scale backend service
docker-compose up --scale backend=3

# View service status
docker-compose ps

# Execute commands in containers
docker-compose exec backend npm run migrate
docker-compose exec postgres psql -U event_manager -d event_manager
```

### Option 3: Manual Installation

#### Prerequisites:
- Node.js 18+
- PostgreSQL 12+
- Redis (optional)
- Git
- Build tools (build-essential on Ubuntu)

#### Manual Setup Steps:
```bash
# 1. Clone repository
git clone <repository-url>
cd event-manager

# 2. Install backend dependencies
npm install

# 3. Configure environment
cp env.example .env
# Edit .env with your configuration

# 4. Setup database
npm run migrate
npm run seed

# 5. Start backend
npm run dev

# 6. Setup frontend (in another terminal)
cd frontend
npm install
npm run dev
```

## 🐳 Docker Architecture

### Container Overview:
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

### Docker Files:

#### `docker-compose.yml`
- **Orchestrates** all services
- **Manages** dependencies and startup order
- **Handles** networking between containers
- **Configures** persistent volumes
- **Sets up** health checks

#### `Dockerfile.backend`
- **Base**: Node.js 18 Alpine
- **Optimized** for production
- **Includes** Prisma client generation
- **Health checks** for monitoring
- **Lightweight** (~120MB)

#### `frontend/Dockerfile`
- **Multi-stage build**: Build React app, serve with Nginx
- **Optimized bundle**: Minified, compressed assets
- **Production-ready**: Nginx with security headers
- **Lightweight** (~15MB)

#### `init.sql`
- **Database initialization** script
- **Creates** database and user automatically
- **Sets** proper permissions
- **Idempotent** (safe to run multiple times)

### Docker Benefits:
- **Environment Consistency**: Same environment across dev/staging/production
- **Simplified Deployment**: One command to start entire application
- **Scalability**: Easy horizontal scaling
- **Portability**: Runs anywhere Docker is supported
- **Resource Efficiency**: Shared OS kernel, optimized resource usage

## 🔧 Environment Configuration

### Environment Variables:

#### Database Configuration:
```bash
DATABASE_URL="postgresql://username:password@host:port/database"
DB_HOST=localhost
DB_PORT=5432
DB_NAME=event_manager
DB_USER=event_manager
DB_PASSWORD=password
```

#### Application Configuration:
```bash
NODE_ENV=development
PORT=3000
APP_URL=http://localhost:3001
JWT_SECRET=your-super-secret-jwt-key
SESSION_SECRET=your-super-secret-session-key
```

#### Email Configuration (Optional):
```bash
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USER=your-email@gmail.com
SMTP_PASS=your-password
SMTP_FROM=noreply@eventmanager.com
```

#### Security Configuration:
```bash
BCRYPT_ROUNDS=12
RATE_LIMIT_WINDOW_MS=900000
RATE_LIMIT_MAX_REQUESTS=100
```

## 🚀 Quick Start Examples

### Development Environment:
```bash
# Interactive setup
./setup.sh

# Quick automated setup
./setup.sh --non-interactive
```

### Production Deployment:
```bash
# Docker deployment
docker-compose up -d

# Server deployment with proper permissions
./setup.sh --non-interactive --auto-setup-permissions

# Custom production setup
./setup.sh \
  --non-interactive \
  --auto-setup-permissions \
  --db-host=prod-db.example.com \
  --db-password=secure-password \
  --jwt-secret=production-jwt-secret \
  --app-env=production \
  --app-url=https://eventmanager.example.com
```

### CI/CD Pipeline:
```bash
# Automated deployment
./setup.sh --non-interactive

# Docker in CI/CD
docker-compose -f docker-compose.prod.yml up -d
```

## 📁 Project Structure

```
event-manager/
├── src/                    # Backend source code
│   ├── controllers/        # Route controllers
│   ├── middleware/         # Express middleware
│   ├── routes/            # API routes
│   ├── socket/            # Socket.IO handlers
│   ├── utils/             # Utility functions
│   └── database/          # Database scripts
├── frontend/              # React frontend
│   ├── src/
│   │   ├── components/    # React components
│   │   ├── pages/         # Page components
│   │   ├── hooks/         # Custom hooks
│   │   ├── contexts/      # React contexts
│   │   └── services/      # API services
│   ├── Dockerfile         # Frontend container
│   └── nginx.conf         # Nginx configuration
├── prisma/                # Database schema
├── docker-compose.yml     # Docker orchestration
├── Dockerfile.backend     # Backend container
├── init.sql              # Database initialization
├── setup.sh              # Automated setup script
└── package.json           # Backend dependencies
```

## 👥 User Roles

1. **Organizer**: Full system access, can manage all events and users
2. **Judge**: Can score contestants in assigned categories
3. **Contestant**: Can view their scores and contest information
4. **Emcee**: Can access emcee scripts and announcements
5. **Tally Master**: Can certify totals after judges complete scoring
6. **Auditor**: Can perform final certification of results
7. **Board**: Administrative access with oversight capabilities

## 🔌 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `GET /api/auth/profile` - Get user profile
- `PUT /api/auth/profile` - Update profile
- `PUT /api/auth/change-password` - Change password

### Events
- `GET /api/events` - List events
- `POST /api/events` - Create event
- `GET /api/events/:id` - Get event details
- `PUT /api/events/:id` - Update event
- `DELETE /api/events/:id` - Delete event

### Contests
- `GET /api/contests/event/:eventId` - List contests for event
- `POST /api/contests/event/:eventId` - Create contest
- `GET /api/contests/:id` - Get contest details
- `PUT /api/contests/:id` - Update contest

### Categories
- `GET /api/categories/contest/:contestId` - List categories for contest
- `POST /api/categories/contest/:contestId` - Create category
- `GET /api/categories/:id` - Get category details
- `PUT /api/categories/:id` - Update category

### Scoring
- `POST /api/scoring/category/:categoryId/contestant/:contestantId` - Submit scores
- `GET /api/scoring/category/:categoryId/contestant/:contestantId` - Get scores
- `POST /api/scoring/category/:categoryId/certify` - Certify scores (judges)
- `POST /api/scoring/category/:categoryId/certify-totals` - Certify totals (tally masters)
- `POST /api/scoring/category/:categoryId/final-certification` - Final certification (auditors)

### Users
- `GET /api/users` - List users
- `POST /api/users` - Create user
- `GET /api/users/:id` - Get user details
- `PUT /api/users/:id` - Update user
- `DELETE /api/users/:id` - Delete user

### Admin
- `GET /api/admin/stats` - System statistics
- `GET /api/admin/logs` - Activity logs
- `GET /api/admin/active-users` - Active users
- `GET /api/admin/settings` - System settings
- `PUT /api/admin/settings` - Update settings

## 🛠 Development

### Backend Development
```bash
# Run in development mode with hot reload
npm run dev

# Run database migrations
npm run migrate

# Seed database with sample data
npm run seed

# Run tests
npm test
```

### Frontend Development
```bash
cd frontend

# Run in development mode
npm run dev

# Build for production
npm run build

# Preview production build
npm run preview

# Type checking
npm run type-check
```

### Docker Development
```bash
# Start development environment
docker-compose -f docker-compose.dev.yml up

# Rebuild containers
docker-compose up --build

# View logs
docker-compose logs -f backend
```

## 🗄 Database Schema

The application uses PostgreSQL with Prisma ORM. Key entities include:

- **Events**: Top-level containers for contests
- **Contests**: Categories within events (formerly "Categories" in PHP)
- **Categories**: Subcategories within contests (formerly "Subcategories" in PHP)
- **Users**: Authentication and user management
- **Contestants**: Contest participants
- **Judges**: Scoring personnel
- **Scores**: Individual scores for criteria
- **Certifications**: Judge, tally master, and auditor certifications

## 🔒 Security Features

- **JWT-based authentication** with configurable secrets
- **Role-based access control** (RBAC)
- **Input validation** and sanitization
- **CSRF protection**
- **Rate limiting** with configurable limits
- **Secure session management**
- **Password hashing** with bcrypt (configurable rounds)
- **Environment-specific** security configurations

## ⚡ Real-time Features

- **Live user activity** monitoring
- **Real-time score updates** via WebSocket
- **Certification status** updates
- **System notifications**
- **Active user tracking**
- **Connection status** indicators

## 🚀 Deployment

### Docker Deployment (Recommended)
```bash
# Production deployment
docker-compose -f docker-compose.prod.yml up -d

# Scale services
docker-compose up --scale backend=3

# Update deployment
docker-compose pull && docker-compose up -d
```

### Manual Deployment
```bash
# Backend deployment
1. Set up PostgreSQL database
2. Configure environment variables
3. Run database migrations
4. Start the Node.js application

# Frontend deployment
1. Build the React application: npm run build
2. Serve the built files with a web server
3. Configure API endpoint URLs
```

### Cloud Deployment
- **AWS**: ECS, EKS, or EC2 with Docker
- **Azure**: Container Instances or AKS
- **Google Cloud**: Cloud Run or GKE
- **DigitalOcean**: App Platform or Droplets
- **Heroku**: Container deployment

## 🔧 Troubleshooting

### Common Issues:

#### Setup Script Issues:
```bash
# Permission denied
chmod +x setup.sh

# Node.js version issues
./setup.sh --auto-install-prereqs

# Database connection issues
./setup.sh --db-host=localhost --db-password=password
```

#### Docker Issues:
```bash
# Container won't start
docker-compose logs backend

# Database connection issues
docker-compose exec backend npm run migrate

# Port conflicts
docker-compose down && docker-compose up
```

#### Environment Issues:
```bash
# Check environment variables
cat .env

# Regenerate secrets
./setup.sh --jwt-secret=new-secret --session-secret=new-session-secret
```

## 📊 Performance & Monitoring

### Resource Usage:
- **Frontend**: ~15MB image, ~10MB RAM
- **Backend**: ~120MB image, ~50MB RAM
- **PostgreSQL**: ~200MB image, ~100MB RAM
- **Redis**: ~10MB image, ~5MB RAM
- **Total**: ~345MB images, ~165MB RAM

### Monitoring:
- **Health checks** built into containers
- **Log aggregation** with Winston
- **Real-time monitoring** via WebSocket
- **Database performance** tracking
- **User activity** logging

## 📚 Additional Documentation

### Comprehensive Guides
- **[DOCKER.md](DOCKER.md)** - Complete Docker deployment guide with architecture, configuration, and troubleshooting
- **[SETUP.md](SETUP.md)** - Detailed setup script documentation with all command options and examples
- **[SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md)** - Remote Linux server deployment with proper permissions and security
- **[TROUBLESHOOTING.md](TROUBLESHOOTING.md)** - Common issues and solutions for installation and setup
- **[README.md](README.md)** - This main documentation file

### Quick Reference

#### Docker Commands
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

#### Setup Script Commands
```bash
# Interactive setup
./setup.sh

# Automated setup
./setup.sh --non-interactive

# Custom configuration
./setup.sh --db-host=prod.example.com --app-env=production

# Help and options
./setup.sh --help
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

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

### Development Setup:
```bash
# Clone and setup
git clone <your-fork>
cd event-manager
./setup.sh --non-interactive

# Make changes and test
npm run test
docker-compose up --build
```

## 📄 License

MIT License - see LICENSE file for details.

## 🆘 Support

- **Documentation**: 
  - [README.md](README.md) - Main documentation
  - [DOCKER.md](DOCKER.md) - Docker deployment guide
  - [SERVER_DEPLOYMENT.md](SERVER_DEPLOYMENT.md) - Remote Linux server deployment
  - [SETUP.md](SETUP.md) - Setup script documentation
  - [TROUBLESHOOTING.md](TROUBLESHOOTING.md) - Common issues and solutions
- **Issues**: GitHub Issues for bug reports and feature requests
- **Setup Help**: Run `./setup.sh --help` for command options
- **Docker Help**: Run `docker-compose --help` for Docker commands

---

**Default Login Credentials:**
- Email: `admin@eventmanager.com`
- Password: `admin123`

**Application URLs:**
- Frontend: http://localhost:3001
- Backend API: http://localhost:3000
- Database: localhost:5432
- Redis: localhost:6379