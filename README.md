# Event Manager Contest System

A modern, full-stack contest management system built with Node.js, React, and PostgreSQL.

## Features

- **Event Management**: Create and manage events with multiple contests
- **Contest Organization**: Organize contests into categories with scoring criteria
- **User Management**: Role-based access control with 7 different user types
- **Scoring System**: Comprehensive scoring with certification workflows
- **Real-time Updates**: WebSocket integration for live updates
- **Admin Dashboard**: System monitoring and management tools
- **Responsive Design**: Modern UI with dark mode support

## Technology Stack

### Backend
- **Node.js** with Express.js
- **PostgreSQL** with Prisma ORM
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

## Quick Start

### Prerequisites
- Node.js 18+ 
- PostgreSQL 12+
- Redis (optional)

### Installation

1. **Clone and setup backend:**
```bash
cd /Users/Mat/New_EM_Project
npm install
```

2. **Setup database:**
```bash
# Copy environment file
cp env.example .env

# Edit .env with your database credentials
# DATABASE_URL="postgresql://username:password@localhost:5432/event_manager"

# Run migrations and seed data
npm run migrate
npm run seed
```

3. **Start backend:**
```bash
npm run dev
```

4. **Setup frontend:**
```bash
cd frontend
npm install
```

5. **Start frontend:**
```bash
npm run dev
```

6. **Access the application:**
- Frontend: http://localhost:3001
- Backend API: http://localhost:3000
- Default login: admin@eventmanager.com / admin123

## Project Structure

```
/Users/Mat/New_EM_Project/
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
├── prisma/                # Database schema
└── package.json           # Backend dependencies
```

## User Roles

1. **Organizer**: Full system access, can manage all events and users
2. **Judge**: Can score contestants in assigned categories
3. **Contestant**: Can view their scores and contest information
4. **Emcee**: Can access emcee scripts and announcements
5. **Tally Master**: Can certify totals after judges complete scoring
6. **Auditor**: Can perform final certification of results
7. **Board**: Administrative access with oversight capabilities

## API Endpoints

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

## Development

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

## Database Schema

The application uses PostgreSQL with Prisma ORM. Key entities include:

- **Events**: Top-level containers for contests
- **Contests**: Categories within events (formerly "Categories" in PHP)
- **Categories**: Subcategories within contests (formerly "Subcategories" in PHP)
- **Users**: Authentication and user management
- **Contestants**: Contest participants
- **Judges**: Scoring personnel
- **Scores**: Individual scores for criteria
- **Certifications**: Judge, tally master, and auditor certifications

## Security Features

- JWT-based authentication
- Role-based access control (RBAC)
- Input validation and sanitization
- CSRF protection
- Rate limiting
- Secure session management
- Password hashing with bcrypt

## Real-time Features

- Live user activity monitoring
- Real-time score updates
- Certification status updates
- System notifications
- Active user tracking

## Deployment

### Backend Deployment
1. Set up PostgreSQL database
2. Configure environment variables
3. Run database migrations
4. Start the Node.js application

### Frontend Deployment
1. Build the React application: `npm run build`
2. Serve the built files with a web server
3. Configure API endpoint URLs

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

MIT License - see LICENSE file for details.