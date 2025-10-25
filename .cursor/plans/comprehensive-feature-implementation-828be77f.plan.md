<!-- 828be77f-f8ff-4f43-8713-47ff0bed9541 2691f4d1-19de-404a-ac7d-36902cc242b6 -->
# Event Manager Enhancement Implementation Plan

## Phase 1: Critical API Endpoint Fixes (Priority: CRITICAL)

### 1.1 Settings API Endpoints

**Issue**: Settings endpoints returning 500 errors

- `/api/settings/logging-levels` - Not implemented
- `/api/settings/backup` - Not implemented  
- `/api/settings/security` - Not implemented
- `/api/settings/email` - Not implemented

**Implementation**:

- Create `settingsController.js` with methods: `getLoggingLevels`, `updateLoggingLevel`, `getSecuritySettings`, `updateSecuritySettings`, `getBackupSettings`, `updateBackupSettings`, `getEmailSettings`, `updateEmailSettings`
- Add routes in `settingsRoutes.js` for each endpoint
- Update Prisma schema to include `SystemSettings` model with fields: `id`, `key`, `value`, `category`, `description`, `updatedAt`, `updatedBy`
- Implement validation middleware for settings updates

### 1.2 Backup API Endpoint

**Issue**: `/api/backup` returning 500 error

**Implementation**:

- Create `backupController.js` with methods: `createBackup`, `listBackups`, `downloadBackup`, `restoreBackup`, `deleteBackup`
- Add Prisma models: `BackupLog` (id, type, filePath, fileSize, status, createdBy, createdAt, errorMessage)
- Implement file system operations for backup creation/restoration
- Add scheduled backup functionality using node-cron

### 1.3 Reports API Endpoints

**Issue**: Reports endpoints returning 404/500 errors

- `/api/reports` - Returns 404
- `/api/reports/templates` - Returns 500

**Implementation**:

- Create `reportsController.js` with methods: `getReports`, `getReportTemplates`, `createTemplate`, `generateReport`, `deleteReport`
- Add Prisma models: `ReportTemplate` (id, name, type, template, parameters), `ReportInstance` (id, templateId, generatedBy, generatedAt, data)
- Implement report generation logic with PDF/Excel export capabilities
- Add routes in `reportsRoutes.js`

### 1.4 Events API Endpoint

**Issue**: `/api/events` POST returning 400 error

**Implementation**:

- Fix validation in `eventsController.js` - remove `maxContestants` as required field
- Update event creation schema to make `maxContestants` optional
- Add proper error messages for validation failures

### 1.5 Password Policy API

**Issue**: `/api/settings/password-policy` returning 401 error

**Implementation**:

- Create `getPasswordPolicy` and `updatePasswordPolicy` methods in `settingsController.js`
- Add route: `GET /api/settings/password-policy` (no auth required for reading)
- Store password policy in SystemSettings table

## Phase 2: Admin Dashboard Data Population

### 2.1 Dashboard Overview Statistics

**Issue**: No data displayed for Users/Events/Contests/Database/Certifications

**Implementation**:

- Update `adminController.js` `getStats` method to query:
  - Total users count from `User` model
  - Total events count from `Event` model
  - Total contests count from `Contest` model
  - Database size from PostgreSQL system tables
  - Certifications count from audit logs
- Return structured JSON with all statistics

### 2.2 Activity Logs

**Issue**: Activity logs not populated

**Implementation**:

- Create `AuditLog` Prisma model (id, userId, action, entityType, entityId, details, ipAddress, userAgent, createdAt)
- Implement logging middleware to capture all API actions
- Add `getActivityLogs` method in `adminController.js` with pagination and filtering
- Log all CRUD operations automatically

### 2.3 Emcee Scripts Upload/Manage

**Issue**: Missing upload/manage button functionality

**Implementation**:

- Create `EmceeScript` Prisma model (id, filename, filePath, isActive, uploadedBy, createdAt)
- Add file upload endpoint: `POST /api/emcee/scripts` with multer middleware
- Add management endpoints: `GET /api/emcee/scripts`, `DELETE /api/emcee/scripts/:id`, `PUT /api/emcee/scripts/:id/toggle`
- Update frontend `EmceeScripts.tsx` component to include upload button and file management UI

## Phase 3: Templates and Categories Enhancement

### 3.1 Manual Category Types

**Issue**: Templates don't allow manually added category types

**Implementation**:

- Add `CategoryType` Prisma model (id, name, description, isSystem, createdBy, createdAt)
- Create endpoints: `GET /api/category-types`, `POST /api/category-types`, `DELETE /api/category-types/:id`
- Update `CategoryTemplates.tsx` to include dropdown with system + custom types
- Add "Add Custom Type" button in templates UI
- Fix category creation validation to accept custom types

## Phase 4: User Management Enhancements

### 4.1 Last Login Tracking

**Issue**: User management page shows no last login data

**Implementation**:

- Add `lastLogin` field to `User` Prisma model (DateTime)
- Update `authController.js` login method to set `lastLogin` timestamp
- Update `usersController.js` to include `lastLogin` in user list response

### 4.2 User Profile Enhancements

**Issue**: Missing bio/image upload, unnecessary experience field

**Implementation**:

- Add fields to `User` model: `bio` (Text), `imagePath` (String)
- Remove `experience` field from user forms
- Add file upload endpoint: `POST /api/users/:id/image` with image validation
- Update `UsersPage.tsx` to include file upload inputs for bio and image
- Implement image storage in `/uploads/users/` directory

### 4.3 User Information Configuration

**Issue**: Admin cannot configure visible/required fields

**Implementation**:

- Create `UserFieldConfiguration` model (id, fieldName, isVisible, isRequired, order)
- Add endpoints: `GET /api/settings/user-fields`, `PUT /api/settings/user-fields`
- Create admin UI for field configuration
- Update user forms to dynamically render based on configuration

## Phase 5: UI/UX Improvements

### 5.1 Modal Consistency

**Issue**: Archive/user modals don't match event modal styling

**Implementation**:

- Create shared modal component `Modal.tsx` with consistent styling
- Update `ArchiveManager.tsx`, `UsersPage.tsx` to use shared modal
- Apply Tailwind classes for consistent appearance

### 5.2 Home Button Navigation

**Issue**: No home button on non-home pages

**Implementation**:

- Update `Layout.tsx` to include home button in header for all authenticated pages
- Add navigation logic to return to role-specific dashboard

### 5.3 Print Reports Functionality

**Issue**: Print Reports modal has no functionality

**Implementation**:

- Create `PrintReportsModal.tsx` component with:
  - Report type selection (Event, Contest, Category, Results)
  - Date range picker
  - Format selection (PDF, Excel, CSV)
  - Print/Download buttons
- Implement `printReport` method in `reportsController.js`
- Add PDF generation using puppeteer or pdfkit

## Phase 6: Role-Based Access Control (RBAC) Restructuring

### 6.1 Admin Role Elevation

**Issue**: Organizer and Admin roles need restructuring

**Implementation**:

- Update `Role` enum in Prisma schema:
  - ADMIN: Full system access (100%)
  - ORGANIZER: Event management with full user access
  - BOARD: Review and oversight with full reporting
  - JUDGE, CONTESTANT, EMCEE, TALLY_MASTER, AUDITOR: Existing roles
- Add `isHeadJudge` boolean field to Judge model for approval workflows
- Create permission matrix in `permissions.js`:
  ```javascript
  ADMIN: ['*'], // All permissions
  ORGANIZER: ['events:*', 'contests:*', 'categories:*', 'users:*', 'reports:*'],
  BOARD: ['events:read', 'contests:read', 'results:*', 'reports:*', 'approvals:*'],
  JUDGE: ['scores:write', 'scores:read', 'results:read', 'commentary:write'],
  TALLY_MASTER: ['results:read', 'certifications:*', 'approvals:*'],
  AUDITOR: ['results:read', 'certifications:*', 'approvals:*', 'audit:*']
  ```

- Update `requireRole` middleware to check permission matrix
- Migrate existing Organizer users to Admin role

### 6.2 Results Granularity by Role

**Issue**: Results and Reporting needs role-specific permissions

**Implementation**:

- Update `resultsController.js` to filter data based on user role:
  - ADMIN/ORGANIZER: All results
  - BOARD: All results with certification status indicators (pending/completed)
  - JUDGE: All results for assigned categories/contests with certification status
  - TALLY_MASTER: All results with certification workflow access
  - AUDITOR: All results with audit trail access
  - CONTESTANT: Own results only
- Add certification status field to results response:
  - `certificationStatus`: 'pending', 'auditor_certified', 'tally_certified', 'final_certified'
  - `certifiedBy`: Array of user IDs who have certified
  - `certifiedAt`: Timestamp of final certification
- Add role-based UI rendering in `ResultsPage.tsx` showing certification badges

### 6.3 Judge Commentary System

**Issue**: Judges need ability to add commentary per criterion per contestant

**Implementation**:

- Add `ScoreComment` Prisma model:
  - `id`: String (UUID)
  - `scoreId`: String (foreign key to Score)
  - `criterionId`: String (foreign key to Criterion)
  - `contestantId`: String (foreign key to Contestant)
  - `judgeId`: String (foreign key to Judge)
  - `comment`: Text
  - `isPrivate`: Boolean (visible only to admin/organizer/board)
  - `createdAt`: DateTime
  - `updatedAt`: DateTime
- Add endpoints:
  - `POST /api/scoring/comments` - Create comment
  - `GET /api/scoring/comments/:scoreId` - Get comments for a score
  - `PUT /api/scoring/comments/:id` - Update comment
  - `DELETE /api/scoring/comments/:id` - Delete comment
- Update `ScoringPage.tsx` to include comment textarea for each criterion
- Display comments in results view based on role permissions

## Phase 7: Advanced Features

### 7.1 Database Browser UI

**Issue**: Database UI is missing

**Implementation**:

- Create `DatabaseBrowser.tsx` component with:
  - Table list sidebar
  - Query builder interface
  - Results grid with pagination
  - Export functionality
- Add endpoints: `GET /api/admin/database/tables`, `POST /api/admin/database/query`
- Implement read-only query execution with SQL injection protection
- Restrict to ADMIN role only

### 7.2 Bulk User Operations

**Issue**: Bulk upload/removal missing

**Implementation**:

- Create `POST /api/users/bulk-upload` endpoint accepting CSV
- Implement CSV parsing with validation
- Create `POST /api/users/bulk-delete` endpoint accepting array of user IDs
- Add UI in `UsersPage.tsx`:
  - CSV upload button with template download
  - Bulk select checkboxes
  - Bulk delete button with confirmation

### 7.3 Point Deduction Approvals

**Issue**: Point deductions need multi-approval workflow

**Implementation**:

- Create `DeductionRequest` model:
  - `id`: String (UUID)
  - `contestantId`: String
  - `categoryId`: String
  - `amount`: Float
  - `reason`: Text
  - `requestedBy`: String
  - `status`: Enum ('pending', 'approved', 'rejected')
  - `createdAt`: DateTime
- Create `DeductionApproval` model:
  - `id`: String (UUID)
  - `requestId`: String (foreign key)
  - `approvedBy`: String (user ID)
  - `role`: String (user role at time of approval)
  - `isHeadJudge`: Boolean
  - `approvedAt`: DateTime
- Implement approval workflow requiring ALL of the following:
  - At least 1 JUDGE with HEAD_JUDGE flag approval
  - At least 1 TALLY_MASTER approval
  - At least 1 AUDITOR approval
  - At least 1 BOARD or ORGANIZER or ADMIN approval
- Add endpoints:
  - `POST /api/deductions/request` - Create deduction request
  - `GET /api/deductions/pending` - Get pending deductions (role-filtered)
  - `POST /api/deductions/:id/approve` - Approve deduction
  - `POST /api/deductions/:id/reject` - Reject deduction
  - `GET /api/deductions/:id/approvals` - Get approval status
- Create approval UI component showing:
  - Required approvals checklist
  - Current approval status by role type
  - Approval history with timestamps
  - Approve/Reject buttons (visible only if user's role hasn't approved yet)

### 7.4 Session/JWT Configuration

**Issue**: Need configurable timeout

**Implementation**:

- Add to SystemSettings: `jwt_expiration` (default: '24h'), `session_timeout` (default: '1h')
- Update JWT generation in `authController.js` to use configured expiration
- Add session timeout middleware to check last activity
- Create admin UI for timeout configuration

### 7.5 Theme Customization

**Issue**: Cannot modify system theme colors/logos

**Implementation**:

- Create `ThemeSettings` model (id, primaryColor, secondaryColor, logoPath, faviconPath)
- Add endpoints: `GET /api/settings/theme`, `PUT /api/settings/theme`, `POST /api/settings/theme/logo`
- Create admin UI for theme customization with color pickers
- Implement CSS variable injection for dynamic theming
- Store uploaded logos in `/uploads/theme/` directory

## Implementation Priority Order

1. **Phase 1** (Week 1-2): Critical API fixes - Settings, Backup, Reports, Events
2. **Phase 2** (Week 2-3): Dashboard data population
3. **Phase 6** (Week 3-4): RBAC restructuring (critical for security)
4. **Phase 3** (Week 4): Templates enhancement
5. **Phase 4** (Week 5): User management enhancements
6. **Phase 5** (Week 5-6): UI/UX improvements
7. **Phase 7** (Week 6-8): Advanced features

## Technical Requirements

### Database Migrations

- Create migration files for all new Prisma models
- Run `npx prisma migrate dev` after schema updates
- Seed initial data for SystemSettings, CategoryTypes

### Testing Strategy

- Unit tests for all new controller methods
- Integration tests for API endpoints
- E2E tests for critical user workflows
- Manual testing checklist for UI components

### Documentation

- API documentation for all new endpoints
- User guide for new features
- Admin configuration guide
- Developer setup instructions

## Estimated Timeline

- Total: 8 weeks
- Critical fixes: 2 weeks
- Core features: 4 weeks  
- Advanced features: 2 weeks