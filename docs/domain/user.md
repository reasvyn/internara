# User Domain

## Purpose

User is the identity persistence layer. While Auth handles the dynamic aspects — authentication 
(verifying who you are), authorization (what you can do), and account lifecycle (states and 
transitions) — User owns the static data: the User model and the Profile model that together 
represent every person in the system. Students, teachers, supervisors, and admins are all User 
records first. The Profile extends this with personal details: contact information, demographics, 
emergency contacts, and institutional associations (school, department). This domain also handles 
dashboard routing (directing users to the correct landing page based on their role), username 
generation (creating unique system identifiers), and avatar management.

## Boundary

**In scope:** User model (name, email, username, password hash, account metadata — timestamps 
for email verification, account lock, setup required flag), Profile model (phone, address, 
gender, blood type, place and date of birth, emergency contact details — name, phone, address 
— national identifier, registration number, school and department foreign keys), username 
generation (system-wide unique usernames computed from user attributes with collision avoidance), 
role-based dashboard routing (DashboardController inspects the user's highest-priority role and 
redirects to the correct dashboard), profile editing (self-service updates to personal data with 
appropriate validation), dashboard data aggregation (the student dashboard pulls together 
assignment, attendance, logbook, and evaluation data from multiple domains for a 
unified overview), avatar management (single image per user with automated thumbnail conversion).

**Out of scope:** Authentication and login flows (Auth domain handles login form, credential 
verification, session management, and rate limiting), account status state machine (Auth domain's 
AccountStatus enum and lifecycle transitions — User records have a status but Auth manages its 
transitions), role and permission definitions (Auth domain owns the Role enum, permission 
definitions, and the Spatie permission package integration), password policies, password reset, 
and account recovery (Auth domain handles all password and recovery flows), user CRUD by 
administrators (Admin domain creates, manages, and deactivates user accounts — User is the 
target, not the manager), school and department model definitions (School domain owns School and 
Department models — User Profile references them via foreign keys but does not define them).

## Key Concepts

**User Model.** The central identity record representing every person in the system. The User 
model uniquely extends Laravel's Authenticatable class rather than Core's BaseModel — this is 
necessary because authentication features (password hashing via the 'hashed' cast, remember_token 
support for "remember me" sessions, email verification via MustVerifyEmail contract) are provided 
by the Authenticatable base. Despite not extending BaseModel, the User model uses the same UUID 
primary key convention via the HasUuids trait, ensuring foreign key consistency across all 
relationships. The model carries core identity fields: name (display name used throughout the 
UI), email (login identity, must be unique), username (alternative system-wide unique 
identifier), password (hashed, never exposed), and metadata timestamps (email_verified_at for 
verification status, locked_at for account lock state, setup_required boolean for first-time 
flow). Relationships radiate outward from User to virtually every other domain: profile() to 
Profile (one-to-one), mentees() to Registration (many, through the mentee lens), mentors() to 
Mentor assignments (many, through the mentor lens), registrations() through mentee (many, 
providing internship context), and teams() for mentoring team membership. The User model is the 
entity that all other domains reference for authorization, display, and audit attribution.

**Profile Model.** Extends Core's BaseModel and stores extended personal information not needed 
for authentication. The profile is created ON DEMAND — a User can exist without a Profile, but 
will have limited functionality (cannot complete registration, cannot be fully onboarded). Fields 
include: contact details (phone number, physical address for correspondence), demographics 
(gender as a typed enum Gender, blood type as a typed enum BloodType, place of birth as free 
text, date of birth as a date), emergency contact information (name, phone number, address — 
critical for student safety), and institutional data (national identifier for government 
reporting, student registration number for academic records, school_id and department_id foreign 
keys linking to the School domain). The Profile is the bridge between the User domain and the 
School domain — through school and department references, users can be scoped to institutional 
units for reporting, filtering, and access control. Profile editing is self-service through a 
dedicated Livewire component, with appropriate validation on sensitive fields.

**Role-Based Dashboard Routing.** After a user authenticates, the DashboardController (an HTTP 
controller in the User domain) determines where to send them based on their highest-priority 
role. The routing logic inspects the user's roles via Auth's Spatie integration and redirects 
accordingly: STUDENT role → mentee dashboard (Mentee domain), TEACHER or SUPERVISOR role → 
mentor dashboard (Mentor domain), ADMIN or SUPER_ADMIN role → admin dashboard (Admin domain). 
The redirect is a simple HTTP 302 to the appropriate named route. If a user has multiple roles 
(e.g., a teacher who is also an admin), the highest-priority role determines the dashboard. The 
controller is a thin routing layer — the actual dashboard content and functionality lives in 
the respective target domain.

**Username Generation.** The UserIdentifierGenerator support class creates system-wide unique 
usernames from user attributes. The generation algorithm uses a random approach with prefix 'u' 
followed by alphanumeric characters. When a generated username collides with an existing 
one, the algorithm retries up to 100 attempts. The SystemUsername validation rule enforces the 
format: lowercase alphanumeric, starts with a letter, between 3 and 30 characters. Once 
assigned, a username is permanently associated with the user — even if the username is later 
changed, the old value is never reassigned to another user. 
Usernames serve as an alternative login identifier to email.

**Avatar Management.** Users can upload a personal avatar — a single image associated with 
their account and managed through spatie/laravel-medialibrary. The avatar collection supports one 
image at a time (uploading a new one replaces the old). A required thumbnail conversion produces 
a standardized 200x200 pixel WebP image for use in navigation bars, comment displays, team lists, 
and notification mentions. The avatar is entirely optional — a default avatar or initials-based 
placeholder is shown when no avatar is uploaded.

**Student Dashboard Data Aggregation.** The User domain owns the student dashboard (a Livewire
component) that aggregates data from multiple domains to provide a comprehensive overview. It
pulls from: Assignment domain (pending and upcoming task deadlines, recent grades), Attendance
domain (today's clock-in status, attendance percentage), Logbook domain (recent entries, pending
acknowledgements, submission gaps), Evaluation domain (received evaluations, pending
evaluations), Schedule domain (upcoming events and deadlines), Guidance domain (pending document
acknowledgements), and Announcements (Admin domain). The dashboard is a read-only aggregation
layer — all source data lives in its respective domain. The aggregation is performed in
real-time on each dashboard load, optimized through eager-loading and caching strategies to keep
load times under one second.

**Notification Center.** The User domain owns universal notification delivery — every
authenticated user, regardless of role, can access their notifications. The `NotificationCenter`
Livewire component lists all notifications for the current user with search, status filtering
(unread/read), sorting, and bulk actions. The `NotificationBell` component displays the unread
count in the navigation bar and links to the notification center. The `CustomDatabaseChannel`
(Core domain) stores notifications in the custom `notifications` table with a flexible schema
(type, title, message, data JSON, deep-link URL). All notification classes implement `ShouldQueue`
for asynchronous delivery. Real-time updates are pushed via Laravel Echo (Reverb WebSocket) when
configured. See `docs/notification.md` for the full channel strategy.

## Requirements

### User Stories & Rules

- **User:** As a user, I want to edit my profile so that my personal information is up to date
- **User:** As a user, I want to upload an avatar so that my account has a personal photo
- **Student:** As a student, I want to log in and be directed to my dashboard so that I can quickly access my tools
- **Teacher/Supervisor:** As a teacher or supervisor, I want to log in and be directed to my mentor dashboard so that I can manage my mentees
- **Admin:** As an admin, I want to log in and be directed to the admin dashboard so that I can manage the system
- **System:** As the system, I want to generate unique usernames so that every user has a system-wide identifier
- User extends Authenticatable, NOT BaseModel — this is the only exception to the system-wide 
BaseModel model convention, required because Authenticatable provides password hashing, remember 
tokens, and email verification.
- Each user can have at most one Profile record — enforced by the one-to-one relationship; the 
profile is created on first edit (on-demand), not automatically at user creation.
- Both email and username must be unique system-wide — they are both valid login identifiers 
and must be globally unique.
- Dashboard routing is role-based with a priority order: SUPER_ADMIN > ADMIN > TEACHER = 
SUPERVISOR > STUDENT.
- Gender and blood type are backed by typed enums (Gender, BloodType) that implement Core's 
LabelEnum contract — they are optional fields.
- The avatar is a single-file media collection with exactly one required conversion (thumb, 
200x200 WebP) and is entirely optional.
- Username changes are logged and the old username is permanently retired — never reassigned to 
another user.
- Username validation is enforced by the `SystemUsername` rule: lowercase alphanumeric, starts with a letter, 3-30 characters.
- Profile creation is demand-driven (first profile edit) — a user can operate without a profile 
but with limited functionality.
- The Profile's school_id and department_id foreign keys are optional — a user can exist 
without institutional association.
- All profile data changes are logged via SmartLogger for audit trail and GDPR compliance.
- All Livewire components return `: View` for type safety — `ProfileEditor`, `RecentActivityList`, 
and `UserDashboard` were updated to match the existing convention.

### Key Operations

| Action | Description |
|--------|-------------|
| `UpdateProfileAction` | Updates the user's profile with personal data |
| `GetStudentDashboardDataAction` | Aggregates student dashboard data from multiple domains |
| `SendNotificationAction` | Sends an in-app notification to a user (implements `SendsNotifications` contract) |
| `MarkAsReadAction` | Marks a single notification as read |
| `MarkAllAsReadAction` | Marks all unread notifications as read |
| `MarkBatchAsReadAction` | Marks selected notifications as read |
| `DeleteNotificationAction` | Deletes a single notification |
| `GetNotificationsAction` | Retrieves notifications for a user with filtering |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `User` (extends `Authenticatable`, UUID via `HasUuids`), `Profile` (extends `BaseModel`, on-demand creation), `Notification` (custom in-app notifications table) |
| **Enums** | `BloodType` — `A`, `B`, `AB`, `O`; `Gender` — `MALE`, `FEMALE` |
| **Livewire** | `UserDashboard`, `ProfileEditor`, `RecentActivityList`, `NotificationCenter`, `NotificationBell`, `ActivityFeedManager` |
| **Support** | `UserIdentifierGenerator` (unique username generation with collision avoidance) |
| **Notifications** | `TestMailNotification` (email configuration testing) |
| **Rules** | `SystemUsername` (username format validation) |
| **Controllers** | `DashboardController` (role-based dashboard routing) |

## Dependencies

| Dependency | Reason |
|---|---|---|
| Core | BaseModel (Profile, Notification extend it), BaseController (DashboardController extends it), base entity for User entity extraction, SmartLogger for profile change audit, HandlesActionErrors for profile editing, SendsNotifications contract bound to SendNotificationAction |
| School | Profile references School and Department models via foreign keys (belongsTo relationships) — these are optional associations |
| Auth | Role enum consumed by DashboardController for role-to-dashboard routing; Apprentice entity for account state checks in dashboards. Notifiable trait on User model for notification delivery |


