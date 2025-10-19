# Event Manager Frontend

A modern, responsive React/TypeScript frontend for the Event Manager application.

## Features

- **Modern UI/UX**: Built with React 18, TypeScript, and Tailwind CSS
- **Responsive Design**: Optimized for desktop, tablet, and mobile devices
- **Real-time Updates**: WebSocket integration for live scoring updates
- **Role-based Access**: Different dashboards for each user role
- **State Management**: Zustand for efficient state management
- **Data Fetching**: React Query for server state management
- **Form Handling**: React Hook Form with validation
- **Notifications**: Toast notifications for user feedback

## Technology Stack

- **React 18** - UI library
- **TypeScript** - Type safety
- **Vite** - Build tool and dev server
- **Tailwind CSS** - Styling
- **React Router** - Client-side routing
- **Zustand** - State management
- **React Query** - Server state management
- **React Hook Form** - Form handling
- **Axios** - HTTP client
- **Socket.IO** - WebSocket client
- **Lucide React** - Icons

## Getting Started

### Prerequisites

- Node.js 18+ 
- npm or yarn
- Event Manager API running on port 3000

### Installation

1. Install dependencies:
```bash
npm install
```

2. Copy environment variables:
```bash
cp .env.example .env
```

3. Update environment variables in `.env`:
```env
VITE_API_URL=http://localhost:3000/api
VITE_WS_URL=ws://localhost:3000
```

4. Start development server:
```bash
npm run dev
```

The application will be available at `http://localhost:3001`.

## Available Scripts

- `npm run dev` - Start development server
- `npm run build` - Build for production
- `npm run preview` - Preview production build
- `npm run lint` - Run ESLint
- `npm run type-check` - Run TypeScript type checking

## Project Structure

```
src/
├── components/          # Reusable UI components
│   ├── layout/         # Layout components (Header, Sidebar, etc.)
│   └── ui/             # Basic UI components (Button, Input, etc.)
├── pages/              # Page components
│   ├── auth/           # Authentication pages
│   └── roles/          # Role-specific dashboards
├── stores/             # Zustand stores
├── hooks/              # Custom React hooks
├── lib/                # Utility functions and API client
├── styles/             # Global styles and CSS variables
└── main.tsx           # Application entry point
```

## User Roles

The application supports different user roles with specific dashboards:

- **Organizer**: Full system access and management
- **Judge**: Scoring interface and assignment management
- **Contestant**: View results and personal information
- **Emcee**: Script management and contestant information
- **Tally Master**: Score review and certification
- **Auditor**: Final score verification and certification
- **Board**: System overview and administrative controls

## Features by Role

### Organizer
- Event, contest, and category management
- User management and role assignment
- System settings and configuration
- Reports and analytics

### Judge
- Assigned subcategory scoring
- Real-time score submission
- Score signing and certification
- Assignment overview

### Emcee
- Script management and access
- Contestant information and bios
- Event flow management
- Real-time updates

### Tally Master
- Score review and verification
- Certification management
- Progress tracking
- Quality assurance

### Auditor
- Final score verification
- Tally master status monitoring
- Final certification process
- Audit trail review

### Board
- System overview and statistics
- Administrative controls
- Report generation
- System maintenance

## API Integration

The frontend communicates with the Event Manager API through:

- **REST API**: For CRUD operations and data fetching
- **WebSocket**: For real-time updates and live scoring
- **File Upload**: For document and image uploads
- **Authentication**: JWT-based authentication

## Responsive Design

The application is fully responsive and optimized for:

- **Desktop**: Full-featured interface with sidebar navigation
- **Tablet**: Adapted layout with collapsible sidebar
- **Mobile**: Touch-optimized interface with bottom navigation

## Development

### Code Style

- ESLint for code linting
- Prettier for code formatting
- TypeScript for type safety
- Tailwind CSS for styling

### State Management

- **Zustand**: Global state (authentication, user data)
- **React Query**: Server state (API data, caching)
- **React Hook Form**: Form state and validation

### Performance

- Code splitting with React.lazy
- Image optimization
- Bundle size optimization
- Caching strategies

## Deployment

### Production Build

```bash
npm run build
```

The built files will be in the `dist` directory.

### Environment Variables

Required environment variables for production:

```env
VITE_API_URL=https://your-api-domain.com/api
VITE_WS_URL=wss://your-api-domain.com
VITE_APP_NAME=Event Manager
```

### Docker

```dockerfile
FROM node:18-alpine as builder
WORKDIR /app
COPY package*.json ./
RUN npm ci
COPY . .
RUN npm run build

FROM nginx:alpine
COPY --from=builder /app/dist /usr/share/nginx/html
COPY nginx.conf /etc/nginx/nginx.conf
EXPOSE 80
CMD ["nginx", "-g", "daemon off;"]
```

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Add tests if applicable
5. Submit a pull request

## License

MIT License - see LICENSE file for details.