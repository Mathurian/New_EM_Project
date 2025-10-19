# Contest Manager - High-Performance Contest Management System

A modern, scalable contest management system built with Node.js Fastify and React, designed to handle complex scoring systems with real-time updates and comprehensive reporting.

## 🚀 Features

### Core Functionality
- **Multi-Contest Management**: Create and manage multiple contests with categories and subcategories
- **Advanced Scoring System**: Real-time scoring with complex criteria and tabulation
- **User Role Management**: 6 distinct user roles (Organizer, Judge, Emcee, Tally Master, Auditor, Board)
- **Real-Time Updates**: WebSocket-powered live scoring and notifications
- **Comprehensive Reporting**: PDF and Excel export capabilities
- **File Management**: Upload and manage images and documents
- **Audit Logging**: Complete activity tracking and compliance

### Technical Highlights
- **High Performance**: Node.js Fastify with optimized database queries
- **Database Agnostic**: PostgreSQL with Knex.js ORM
- **Simplified Schema**: Reduced from 39 to 10 core tables
- **Modern Frontend**: React 18 with TypeScript and Vite
- **Responsive Design**: Mobile-first approach with Tailwind CSS
- **Real-Time Features**: WebSocket integration for live updates
- **Comprehensive Testing**: Unit, integration, and E2E test suites

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
- **10 Core Tables**: Simplified from original 39 tables
- **JSONB Support**: Flexible data storage
- **Audit Logging**: Complete activity tracking
- **Soft Deletes**: Data retention and recovery

## 🚀 Quick Start

### Prerequisites
- Node.js 18+ 
- PostgreSQL 12+
- Redis (optional, for caching)

### Backend Setup

1. **Clone and Install**
```bash
cd contest-manager-api
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
# Edit .env with your database credentials
```

3. **Database Setup**
```bash
# Create PostgreSQL database
createdb contest_manager

# Run migrations
npm run db:migrate

# Seed initial data (optional)
npm run db:seed
```

4. **Start Development Server**
```bash
npm run dev
```

### Frontend Setup

1. **Install Dependencies**
```bash
cd contest-manager-frontend
npm install
```

2. **Environment Configuration**
```bash
cp .env.example .env
# Edit .env with your API URL
```

3. **Start Development Server**
```bash
npm run dev
```

## 📊 Database Schema

### Core Tables
1. **users** - User accounts and profiles
2. **contests** - Contest information and settings
3. **categories** - Contest categories
4. **subcategories** - Contest subcategories
5. **contestants** - Contestant information
6. **criteria** - Scoring criteria
7. **scores** - Individual scores
8. **subcategory_contestants** - Contestant assignments
9. **subcategory_judges** - Judge assignments
10. **audit_logs** - Activity tracking

### Key Improvements
- **Eliminated 29 redundant tables** (archived tables, separate certification tables)
- **Consolidated certification system** into single table with type enum
- **Added JSONB support** for flexible settings and metadata
- **Implemented comprehensive audit logging**
- **Database-agnostic design** with Knex.js

## 🔧 API Endpoints

### Authentication
- `POST /api/auth/login` - User login
- `POST /api/auth/register` - User registration
- `POST /api/auth/refresh` - Token refresh
- `GET /api/auth/me` - Current user info

### Contests
- `GET /api/contests` - List contests
- `POST /api/contests` - Create contest
- `GET /api/contests/:id` - Get contest details
- `PUT /api/contests/:id` - Update contest
- `DELETE /api/contests/:id` - Delete contest

### Scoring
- `POST /api/scoring/submit` - Submit score
- `PUT /api/scoring/:id` - Update score
- `GET /api/scoring/subcategory/:id` - Get subcategory scores
- `GET /api/scoring/contestant/:id/tabulation` - Get contestant tabulation

### Results & Reporting
- `GET /api/results/contest/:id` - Contest results
- `GET /api/results/contest/:id/report/pdf` - PDF report
- `GET /api/results/contest/:id/report/excel` - Excel report
- `GET /api/results/leaderboard` - Leaderboard

### File Management
- `POST /api/files/upload` - Upload file
- `GET /api/files/:id` - Get file info
- `GET /api/files/:id/download` - Download file
- `GET /api/files/:id/thumbnail` - Get thumbnail

## 🎯 User Roles

### Organizer
- Full system access
- Create and manage contests
- Manage users and assignments
- Access all reports and settings

### Judge
- Score assigned contestants
- View scoring interface
- Access own scoring history

### Emcee
- View contest information
- Access contestant and judge lists
- View basic results

### Tally Master
- View and verify scores
- Access detailed results
- Generate reports

### Auditor
- View audit logs
- Verify data integrity
- Access compliance reports

### Board Member
- View high-level reports
- Access system statistics
- Monitor contest progress

## 🔄 Real-Time Features

### WebSocket Integration
- Live scoring updates
- Real-time notifications
- Connection management
- Room-based messaging

### Supported Events
- `score_submitted` - New score submitted
- `score_updated` - Score modified
- `score_deleted` - Score removed
- `contest_update` - Contest changes
- `user_update` - User profile changes

## 📱 Responsive Design

### Mobile-First Approach
- Touch-friendly interfaces
- Optimized for small screens
- Progressive Web App features
- Offline capability (planned)

### Device Support
- Mobile phones (320px+)
- Tablets (768px+)
- Desktops (1024px+)
- Large screens (1440px+)

## 🧪 Testing

### Backend Testing
```bash
# Unit tests
npm test

# Integration tests
npm run test:integration

# Coverage report
npm run test:coverage
```

### Frontend Testing
```bash
# Unit tests
npm test

# E2E tests
npm run test:e2e

# Type checking
npm run type-check
```

## 🚀 Performance Optimizations

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

## 🔒 Security Features

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

## 🚀 Deployment

### Production Setup
1. **Environment Configuration**
   - Set production environment variables
   - Configure database connections
   - Set up Redis caching
   - Configure email services

2. **Database Migration**
   - Run production migrations
   - Set up database backups
   - Configure monitoring

3. **Application Deployment**
   - Build frontend assets
   - Deploy backend services
   - Configure reverse proxy
   - Set up SSL certificates

### Docker Support
```bash
# Build and run with Docker Compose
docker-compose up -d
```

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

## 📄 License

MIT License - see LICENSE file for details

## 🆘 Support

### Documentation
- API documentation: `/docs`
- User guide: `/guide`
- Developer docs: `/dev-docs`

### Getting Help
- GitHub Issues
- Community Forum
- Email Support

---

**Built with ❤️ for the contest management community**