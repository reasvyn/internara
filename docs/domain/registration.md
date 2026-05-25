# Registration Domain

## Purpose

Registration manages the student enrollment journey into internship programs. It is the gateway domain — a student cannot access any internship activity (attendance, assignments, logbook, evaluations) without an active registration. This domain owns the application wizard (student's multi-step enrollment form), the guest account application flow (for users without accounts), the document upload per program requirements, and the approval workflow (admin verification and placement).

## Boundary

**In scope:** Student internship registration (program selection, placement choice, review and submit), guest account application (personal data, school info, internship selection), document upload per program requirement, admin verification and placement assignment, registration status lifecycle (pending, active).

**Out of scope:** Placement slot assignment (Placement domain handles company slots — Registration consumes placement status), user identity and base profile management (User domain owns User and Profile models), day-to-day task and assignment management (Assignment domain), attendance recording (Attendance domain), logbook entries (Logbook domain), certificate issuance (Certificate domain), mentor assignment (Mentor domain), evaluation collection (Evaluation domain), guidance document acknowledgements (Guidance domain), program requirement definitions (Internship domain owns what documents are required).

## Key Concepts

**Registration Flow.** The core flow has two entry points:

1. **Authenticated student** browses open programs (`RegistrationCenter`), completes the multi-step wizard (`RegistrationWizard`) selecting a program, placement (or proposing their own company), and submits. This creates a `Registration` with `pending` status. An admin then processes the registration (`RegistrationVerification`) by assigning a placement and mentors, transitioning it to `active`.

2. **Guest user (no account)** fills out the `AccountApplicationForm` with personal and school information plus internship preferences. An admin reviews (`ApplicationReview`), approves (which auto-creates a User + Mentee + Registration in `active` status), or rejects with a reason.

**Registration Status.** Registrations use a simple 2-status system managed by Spatie's `laravel-model-statuses`:

| Status | Meaning |
|--------|---------|
| `pending` | Student has submitted; awaiting admin review and placement assignment. No internship activity possible. |
| `active` | Admin has verified and assigned a placement. Student can now access internship features (attendance, logbook, etc.). |

There is no multi-state machine, no DRAFT/SUBMITTED/UNDER_REVIEW/APPROVED/COMPLETED/WITHDRAWN state progression. Registration is either pending review or active.

**Document Requirements.** Each internship program defines what documents students must upload. These are managed via `InternshipDocumentRequirement` in the Internship domain. The `RegistrationDocumentUpload` component lets students upload files per requirement. Documents are stored via Spatie Media Library and have their own status (`RegistrationDocumentStatus`): `PENDING`, `VERIFIED`, `REJECTED`.

**Account Applications.** Guest users without an account can apply via `AccountApplicationForm`. The application captures personal details, school information, and internship preferences. Admin reviews are handled by `ApplicationReview` (in the Admin domain). Approved applications auto-create a User account with the `student` role, a Mentee profile, and an active Registration.

## Requirements

### User Stories & Rules

**Student Registration**
- **Student:** As a student, I want to browse open internship programs so that I can choose one to register for
- **Student:** As a student, I want to complete a multi-step registration wizard so that I can enroll in an internship program
- **Student:** As a student, I want to upload required documents so that my registration is complete
- **Student:** As a student, I want to know my registration status so that I understand what is happening
- A student can have at most one registration in a non-terminal state (`pending` or `active`) at any time

**Guest Application**
- **Guest:** As a guest without an account, I want to submit an application so that I can get an account and register for an internship
- **Guest:** As a guest, I want to choose between available placements or propose my own company
- Duplicate email submissions are blocked (checked against both `account_applications` and `users` tables)

**Admin Verification**
- **Admin:** As an admin, I want to review pending registrations so that I can verify and assign placements
- **Admin:** As an admin, I want to assign mentors during verification so that students have guidance from the start
- **Admin:** As an admin, I want to approve or reject account applications so that only eligible students get access

### Process Flow

```
Student path:   RegistrationCenter → RegistrationWizard → [pending] → RegistrationVerification → [active]
Guest path:     AccountApplicationForm → [pending] → ApplicationReview → [approved → active] or [rejected]
                                                                                ↓
                                                                        User + Mentee auto-created
```

### Key Operations

| Action | Description |
|--------|-------------|
| `ApplyAccountAction` | Guest submits an account application with personal/school info and internship preferences |
| `ApproveAccountApplicationAction` | Admin approves a pending application, auto-creates User + Mentee + Registration as active |
| `RejectAccountApplicationAction` | Admin rejects a pending application with a reason |
| `RegisterInternshipAction` | Authenticated student registers for an internship program (creates pending Registration) |
| `VerifyRegistrationAction` | Admin verifies a pending registration, assigns placement and mentors (transitions to active) |

### Livewire Components

| Component | Access | Description |
|-----------|--------|-------------|
| `RegistrationCenter` | Authenticated users | Lists internship programs currently accepting registrations (status PUBLISHED, within registration window) |
| `RegistrationWizard` | Authenticated students | Multi-step wizard (program selection, placement choice, review and submit) |
| `RegistrationDocumentUpload` | Authenticated students | Upload required documents per program requirements |
| `RegistrationVerification` | Admin | Review pending registrations, assign placements and mentors |
| `AccountApplicationForm` | Guest users | Submit an account and internship application without logging in |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Registration` (`registrations`), `AccountApplication`, `RegistrationDocument` |
| **Entity** | `RegistrationState` (status checks, date calculations, approval gating) |
| **Enums** | `RegistrationDocumentStatus` — `PENDING`, `VERIFIED`, `REJECTED` (implements `LabelEnum`, `StatusEnum`); `AccountApplicationStatus` — `PENDING`, `APPROVED`, `REJECTED` (implements `LabelEnum`, `StatusEnum`) |
| **Livewire** | `RegistrationCenter`, `RegistrationWizard`, `RegistrationDocumentUpload`, `RegistrationVerification`, `AccountApplicationForm` |
| **Form Objects** | `AccountApplicationFormData` (AccountApplicationForm), `RegistrationWizardForm` (RegistrationWizard) |

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Program definitions determine document requirements and registration eligibility |
| Placement | Confirmed placement is required for active status |
| User | Student identity gates registration submission |
| Document | Storage and validation of uploaded application documents via media library |
| Core | BaseAction, BaseModel, SmartLogger |

## Routes

| URL | Name | Component | Middleware |
|-----|------|-----------|------------|
| `/apply` | `apply` | AccountApplicationForm | guest |
| `/registration` | `registration.center` | RegistrationCenter | auth |
| `/register` | `registration.wizard` | RegistrationWizard | auth |
| `/registration/documents` | `registration.documents` | RegistrationDocumentUpload | auth |
| `/admin/internships/registrations/pending` | `admin.internships.registrations.pending` | RegistrationVerification | auth, role:super_admin\|admin |
| `/admin/applications` | `admin.applications` | ApplicationReview | auth, role:super_admin\|admin |
