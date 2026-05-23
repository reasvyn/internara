# Registration Domain

## Purpose

Registration manages the entire journey from "I want to intern" to "I am actively participating 
in an internship." It is the gateway domain — a student cannot access any internship activity 
(attendance, assignments, logbook, evaluations, briefings) without an active registration. This 
domain owns the application wizard (the multi-step form that collects student information and 
documents), the document upload requirements (defining what must be submitted per program), the 
approval workflow (admin review, approval, rejection, or revision requests), the registration 
status lifecycle from draft through active participation to completion or withdrawal, and the 
holistic completion check that evaluates readiness for certification.

## Boundary

**In scope:** Multi-step application wizard (personal data, program selection, document uploads, 
placement preferences, review and submit), application review and approval workflow (approve, 
reject with reason, request revisions with specific change requests), required document upload 
management (types defined per program, validation rules, status tracking), registration number 
generation (unique, system-wide), registration status lifecycle (draft, submitted, under_review, 
approved, active, completed, withdrawn), cohort tracking and registration grouping, holistic 
completion check (querying all domains to verify all program requirements are met), registration 
amendment workflow (changing details after submission), registration history and full audit trail.

**Out of scope:** Placement slot assignment (Placement domain handles matching students to 
company slots — Registration consumes placement status), user identity and base profile 
management (User domain owns User and Profile models — Registration pre-fills from them), 
day-to-day task and assignment management (Assignment domain), attendance recording (Attendance 
domain), logbook entries (Logbook domain), certificate issuance (Certificate domain — 
Registration provides completion status, Certificate issues), mentor assignment (Mentor domain), 
evaluation collection (Evaluation domain), incident management (Incident domain), guidance 
document acknowledgements (Guidance domain).

## Key Concepts

**Application Wizard.** A multi-step, guided form that students complete to apply for an 
internship. The wizard is designed for progressive completion — students save at each step and 
can return later. Typical steps: (1) PERSONAL DATA — pre-filled from User Profile, editable 
with validation; (2) PROGRAM SELECTION — choose from available OPEN programs, filtered by 
eligibility (department, prerequisites, capacity); (3) DOCUMENT UPLOADS — required and optional 
documents per program requirements, with per-document validation (format, size, content checks); 
(4) PLACEMENT PREFERENCES — indicate preferred companies, industries, or locations (fed to the 
Placement domain's matching algorithm); (5) ADDITIONAL INFORMATION — motivation statement, 
special requirements, accessibility needs, notes; (6) REVIEW AND SUBMIT — summary of all 
entered data, checklist of completeness, final submission action. The wizard validates each step 
before allowing progression — incomplete required fields block advancement. The wizard can be 
exited at any step; the application is saved as DRAFT and can be resumed later.

**Registration Status Lifecycle.** Registrations move through a carefully defined state machine 
with explicit transition rules and preconditions. DRAFT: the wizard has been started but not yet 
submitted — student can edit freely, no admin visibility. SUBMITTED: the student has completed 
the wizard and submitted the application — student can no longer edit, admin sees it in the 
review queue. UNDER_REVIEW: an admin has opened the application for review — the application is 
locked against further student edits, admin is actively examining it. APPROVED: all documents and 
information have been verified and accepted — registration moves to placement phase. From 
APPROVED, the registration transitions to ACTIVE once placement is confirmed by the Placement 
domain. ACTIVE: the student is participating in the internship — this is the operational state 
that gates all other domain activities. COMPLETED: all program requirements have been met 
(verified by the completion check) — the student is eligible for certification. WITHDRAWN: the 
student has left the program early (voluntary or administratively) — no further activity 
possible. Each transition has specific preconditions: SUBMITTED → APPROVED requires document 
validation; APPROVED → ACTIVE requires placement confirmation; ACTIVE → COMPLETED requires 
the holistic completion check to pass; ACTIVE → WITHDRAWN requires a reason and optional 
supporting documentation.

**Document Requirements.** Each internship program defines what documents students must upload 
during registration. Common document types include: internship proposal, company acceptance 
letter, CV/resume, academic transcript, identity document copy, insurance proof, and 
recommendation letters. Each document type can have: accepted file formats (PDF, DOCX, JPG, PNG), 
maximum file size, whether it is REQUIRED or OPTIONAL, validation rules (e.g., "must contain the 
word 'internship'"), and an optional description or instructions for the student. The wizard 
enforces these requirements: required documents must be uploaded before submission; optional 
documents are suggested but not enforced. Documents are stored via the Document domain's media 
library. Once submitted, documents are immutable — revisions require uploading a new version, 
which creates a separate attachment record. Previous versions are preserved for audit.

**Approval Workflow.** Submitted applications enter a review queue visible to admins. Each 
application shows: student name, program, submission date, document completeness status, 
placement preferences, and any flags (missing documents, unusual patterns, duplicate 
applications). The reviewer has three actions. APPROVE: documents and information are 
satisfactory — the registration moves to APPROVED status, triggering the placement process. 
REJECT: the application is denied — requires a reason category (INCOMPLETE_DOCUMENTS, 
INELIGIBLE, FRAUDULENT_INFORMATION, CAPACITY_EXCEEDED, OTHER) and a detailed explanation visible 
to the student. Rejected applications can be configured to allow resubmission (student corrects 
issues and re-submits) or be terminal (no resubmission). REQUEST_REVISION: the application is 
sent back to the student with specific change requests — the student must address each request 
and resubmit. The workflow supports batch operations: selecting multiple applications and 
approving, rejecting, or requesting revisions in bulk for programs with large applicant pools.

**Holistic Completion Check.** When a registration is being evaluated for completion, the system 
performs a cross-domain readiness check. It queries: Attendance domain (minimum attendance 
percentage met?), Assignment domain (all required assignments submitted with passing grades?), 
Assessment domain (all required evaluations and assessments completed?), Guidance domain (all 
required documents acknowledged?), Logbook domain (minimum entry count and consistency met?), 
Schedule domain (mandatory briefings attended?), and Internship domain (all program-level 
requirements satisfied?). Each check returns pass/fail with details. The check is read-only — 
it queries data from other domains but never modifies it. The results are presented as a 
checklist showing which requirements are met and which are not, with specific details for each 
failure. The Registration domain orchestrates this check but does not override the data — if 
Attendance says the student missed too many days, the registration cannot be completed until that 
is resolved through the Attendance domain.

**Registration Number.** Each submitted registration receives a unique, system-wide registration 
number. The format is configurable per institution (e.g., INST-YYYY-NNNNNN). Numbers are 
generated atomically upon first submission and are immutable thereafter. Registration numbers are 
used in all official correspondence, on certificates, and in external verification.

## Requirements

### User Stories

| Role | Story |
|------|-------|
| Student | As a student, I want to create a registration application so that I can enroll in an internship program |
| Student | As a student, I want to upload the required documents during registration so that my application is complete |
| Student | As a student, I want to track my registration status so that I know what is happening with my application |
| Student | As a student, I want to revise my application when asked so that I can correct issues and proceed |
| Admin | As an admin, I want to review submitted applications so that I can approve or reject them |
| Admin | As an admin, I want to verify uploaded documents so that all program requirements are met |
| Admin | As an admin, I want to request revisions with specific change requests so that students know exactly what to fix |
| Admin | As an admin, I want to batch-process applications so that I can handle large applicant pools efficiently |
| System | As the system, I want to enforce the state machine so that no invalid transitions occur |
| System | As the system, I want to perform a holistic completion check across all domains so that certification eligibility is accurate |

### Process Flow

```
DRAFT ──→ SUBMITTED ──→ UNDER_REVIEW ──→ APPROVED ──→ ACTIVE ──→ COMPLETED
  ↑                        │                 │            │
  └── resubmit             │                 │            │
                    ┌──────┘                 │            │
                    ↓                       ↓            ↓
                REJECTED              WITHDRAWN     WITHDRAWN
```

- **DRAFT**: Wizard started, not yet submitted — student edits freely
- **SUBMITTED**: Student completed wizard and submitted — locked for editing
- **UNDER_REVIEW**: Admin examining the application — locked for student
- **APPROVED**: Documents verified and accepted — moves to placement phase
- **ACTIVE**: Placement confirmed — operational state, gates all other domain activities
- **COMPLETED**: All requirements met — eligible for certification
- **WITHDRAWN**: Student left the program early — no further activity
- **REJECTED**: Application denied — may or may not allow resubmission

### Key Operations

| Action | Description |
|--------|-------------|
| `RegisterInternshipAction` | Initiates a new registration for a student in a program |
| `VerifyRegistrationAction` | Verifies a registration, transitioning it toward active status |
| `ApplyAccountAction` | Handles account application submission |
| `ApproveAccountApplicationAction` | Approves a pending account application |
| `RejectAccountApplicationAction` | Rejects a pending account application with a reason |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Registration` (`registrations`), `AccountApplication`, `RegistrationDocument` |
| **Entity** | `RegistrationState` (status checks, date calculations, approval gating) |
| **Enums** | `RegistrationDocumentStatus` — `PENDING`, `VERIFIED`, `REJECTED` |
| **Livewire** | `RegistrationWizard`, `AccountApplicationForm`, `RegistrationCenter`, `RegistrationDocumentUpload`, `RegistrationVerification` |

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Program definitions determine document requirements, eligibility rules, enrollment 
capacity, and completion criteria |
| Placement | Confirmed placement is a prerequisite for ACTIVE status — Registration consumes 
placement confirmation |
| User | Student identity and Profile pre-fill the application wizard; authentication gates 
submission |
| Document | Storage and validation of uploaded application documents via media library |
| Core | BaseAction, BaseModel, SmartLogger, BaseRecordManager |

## Important Rules

- A student can have at most one registration in a non-terminal state (DRAFT, SUBMITTED, 
UNDER_REVIEW, APPROVED, ACTIVE) at any time — no concurrent active registrations.
- Registration transitions must strictly follow the defined state machine — skipping states is 
not permitted.
- Registration numbers are globally unique and generated atomically upon first submission — no 
duplicates, no gaps.
- ACTIVE status requires both APPROVED registration AND confirmed placement from the Placement 
domain — neither alone is sufficient.
- Withdrawn registrations can be reinstated only within a configurable grace period after 
withdrawal (default 14 days).
- Application documents are immutable after submission — revisions create new versions; 
previous versions remain in the audit trail.
- The holistic completion check is read-only — it queries but never modifies data in other 
domains.
- Rejected applications include categorized reasons and detailed explanations, both visible to 
the student.
- Registration amendments (changes after submission) are limited to specific editable fields and 
are logged as amendments with the original value preserved.
