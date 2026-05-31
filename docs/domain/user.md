# User Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 40 files in [reference](user-reference.md) exist

## Purpose

User is the identity persistence layer. While Auth handles authentication and authorization,
User owns the static data: the User model, Profile model, dashboard routing, username generation,
notification center, and avatar management.

---

## Design Principles

### 1. User is the Universal Identity

Every person in the system — student, teacher, supervisor, admin — is a User first. The User
model extends `Authenticatable` (not `BaseModel`) because Laravel's authentication system
requires it. UUID consistency is maintained via manual `HasUuids` trait.

### 2. Profile is On-Demand

Users can exist without a Profile, but with limited functionality. Profile is created on first
edit, not at user creation. This keeps signup lightweight and allows progressive data collection.

### 3. Role-Based Dashboard Routing

After login, users are redirected based on role priority:
SUPER_ADMIN > ADMIN > TEACHER = SUPERVISOR > STUDENT.

### 4. Notification Center is Universal

Every authenticated user has access to notifications regardless of role.

---

## Domain Boundary

The User domain owns the identity persistence layer — everything that makes a person known to the system beyond authentication. It manages user identities with UUID primary keys and extended profiles containing personal data (phone, address, gender, blood type, emergency contact, national identifier, and school or department affiliations). It handles avatar uploads via the media library, generating WebP thumbnails for display. It provides the notification center — a full-page interface with search, filter by read status, sorting, and bulk mark-read or delete operations — plus a navbar bell indicator showing unread counts.

User does not own authentication, roles, permissions, account lifecycle, or password management — those belong to Auth. It does not own school profiles, academic years, or department data (School), program definitions (Internship), or any operational workflow domains. The User domain receives a verified identity from Auth and provides the persistent record that operational domains reference.

The domain depends on the Auth domain for role definitions and authentication status, on the School domain for department and school affiliations in the extended profile, and on Core for base model infrastructure. It does not control or manage data in those referenced domains — it only stores foreign key references to them.

---

## Key Features

- Edit personal profile data including name, email, phone number, address, and bio through self-service forms.
- Upload a single avatar image that is automatically converted to a WebP thumbnail.
- Route authenticated users to their correct dashboard based on role priority after login.
- Display system-wide statistics, readiness checklists, and quick links on the administrator dashboard.
- Show supervised students, pending journal entries, and active companies on the teacher dashboard.
- Present active participants, pending evaluations, and verified journals on the supervisor dashboard.
- Provide registration status, journal progress, and quick actions on the student dashboard.
- View, search, filter, sort, and bulk-manage all notifications in a dedicated notification center with an unread-count badge.
- Generate unique usernames automatically with collision avoidance when new users are created.
- Upload and crop a profile photo with a live preview before saving.
- View unread notification counts as a badge on the navigation bar bell icon.
- Switch the display language from any page via a language selector in the user menu.
- Mark all notifications as read in a single bulk action from the notification center.
