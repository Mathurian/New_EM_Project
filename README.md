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

### User Roles
- **Organizer**: Full system access, create and manage events
- **Judge**: Score contestants, view assigned subcategories
- **Contestant**: View results and personal information
- **Emcee**: Access contest information and scripts
- **Tally Master**: Verify scores and generate reports
- **Auditor**: View audit logs and verify data integrity
- **Board**: Access high-level reports and statistics

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

## 📄 License

MIT License - see LICENSE file for details

## 🆘 Support

- Documentation: `/docs` endpoint
- Issues: GitHub Issues
- Discussions: GitHub Discussions
- Email: support@eventmanager.com

---

**Built with ❤️ for the event management community**