# Assignment — Coursework Management, Submission Lifecycle & Grading

> **Last updated:** 2026-07-22 **Changes:** feat — initial spec covering assignment CRUD, publish workflow,
> student submission with draft/revision, file uploads, teacher/supervisor grading, and notification system

## Description

Complete specification of the Internara Assignment module: assignment creation and management
(publish/draft lifecycle), student submission with draft-to-submitted workflow, file upload via
Spatie MediaLibrary, teacher/supervisor grading with score and feedback, revision request loop,
role-based authorization with Cross-Role Proxy, and notification dispatch on key state changes.

---

## 1. Problem Statements

### PS-1 — Structured Coursework Distribution to Interns

Teachers and supervisors need to assign coursework (projects, reports, essays) to students during
their PKL period. Without a structured assignment system, tasks are communicated via chat or
email, making tracking and deadline enforcement impossible.

### PS-2 — Student Submission With Draft Workflow

Students need to submit their work progressively — starting with drafts, finalizing when ready,
and resubmitting after feedback. A single-submission model would force students to submit
incomplete work or lose their previous submission on revision.

### PS-3 — Teacher/Supervisor Grading With Feedback

Educators need to grade submissions with numeric scores (0–100) and written feedback. Without
structured grading, feedback is scattered across messages and students have no centralized view
of their performance.

### PS-4 — Revision Request Loop

When a submission doesn't meet expectations, the evaluator should return it with feedback for
revision rather than giving a low score. This supports iterative learning. Without a revision
workflow, students would need to create entirely new submissions.

### PS-5 — Notification on State Changes

Students need to know when new assignments are published, and when their submissions are graded
or returned for revision. Without push notifications, students must manually check for updates.

### PS-6 — Deadline Enforcement With Overdue Detection

Assignments have due dates. Submissions after the deadline should be flagged or blocked. Without
automated enforcement, teachers must manually check dates and students may unknowingly submit late.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full CRUD for assignments (project, report, essay types) scoped to internships |
| G2  | Manage assignment lifecycle: DRAFT → PUBLISHED → CLOSED |
| G3  | Support student submission with draft/submitted/revision_required workflow |
| G4  | Allow file uploads via Spatie MediaLibrary (pdf, doc, docx, zip, ppt, pptx) |
| G5  | Enable teacher/supervisor grading with numeric score (0–100) and written feedback |
| G6  | Support revision request loop: SUBMITTED → REVISION_REQUIRED → SUBMITTED |
| G7  | Dispatch notifications on assignment publish, grading, and revision request |
| G8  | Enforce deadline-based submission blocking |
| G9  | Enforce unique submission per student per assignment |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Assignment version history or audit trail |
| NG2  | Peer review or collaborative grading |
| NG3  | Rubric-based grading for assignments (uses Assessment module) |
| NG4  | Auto-grading or plagiarism detection |
| NG5  | Assignment templates or cloning |

---

## 3. User Stories / Use Cases

### UC-1 — Teacher Creates and Publishes an Assignment

**Actor:** Teacher
**Preconditions:** Teacher is authenticated; internship exists
**Flow:**
1. Teacher navigates to `admin/assignments`
2. `AssignmentManager` shows existing assignments
3. Teacher clicks "New Assignment", fills form (type, title, description, due_date, is_mandatory)
4. `CreateAssignmentAction` creates assignment with `DRAFT` status
5. Teacher clicks "Publish"
6. `PublishAssignmentAction` transitions DRAFT → PUBLISHED
7. Event `AssignmentPublished` dispatched; `NotifyOnAssignmentPublished` listener sends notification to creator
8. `PublishAssignmentAction` also sends `AssignmentNotification` to all enrolled students
**Postconditions:** Assignment is PUBLISHED; enrolled students notified

### UC-2 — Student Submits Work

**Actor:** Student
**Preconditions:** Published assignment exists; student has active registration; assignment not overdue
**Flow:**
1. Student navigates to `/student/assignments`
2. `SubmitAssignment` shows published assignments for student's internship
3. Student selects an assignment, enters content (min 20 chars), optionally uploads file
4. `submit()` validates, creates `SubmitAssignmentData(content)`, calls `SubmitAssignmentAction`
5. Action guards: assignment is PUBLISHED, not overdue, student has active registration, no existing non-revision submission
6. Creates `Submission` with status `SUBMITTED`, `submitted_at` set
**Postconditions:** Submission exists with SUBMITTED status

### UC-3 — Teacher Grades a Submission

**Actor:** Teacher
**Preconditions:** SUBMITTED or REVISION_REQUIRED submission exists; teacher is authorized
**Flow:**
1. Teacher navigates to `/supervision/submissions/grading`
2. `SubmissionGrading` lists pending submissions
3. Teacher selects submission, enters score (0–100) and optional feedback
4. `grade()` calls `GradeSubmissionAction::execute(submission, score, feedback)`
5. Action validates score range, updates submission with score, feedback, `GRADED` status
**Postconditions:** Submission graded; student notified via `SubmissionFeedbackNotification`

### UC-4 — Teacher Requests Revision

**Actor:** Teacher
**Preconditions:** SUBMITTED submission exists
**Flow:**
1. Teacher views submission in `SubmissionGrading`
2. Enters feedback (min 10 chars), clicks "Request Revision"
3. `RequestSubmissionRevisionAction::execute(submission, feedback)` transitions to REVISION_REQUIRED
4. `SubmissionRevisionRequested` event dispatched
5. Student notified via `SubmissionFeedbackNotification`
**Postconditions:** Submission returned to student for revision

### UC-5 — Student Resubmits After Revision

**Actor:** Student
**Preconditions:** Submission is in REVISION_REQUIRED status
**Flow:**
1. Student views assignment in `SubmitAssignment`
2. Sees existing submission with revision feedback
3. Updates content, clicks "Resubmit"
4. `SubmitAssignmentAction` detects REVISION_REQUIRED submission, updates content, transitions to SUBMITTED
**Postconditions:** Submission back in SUBMITTED status with updated content

---

## 4. Functional Requirements

### Assignment Management

| ID   | Requirement |
| ---- | ----------- |
| FR-AM1 | `AssignmentManager` must be accessible at route `admin/assignments` with `auth` and `role:super_admin\|admin` middleware |
| FR-AM2 | `CreateAssignmentAction` must accept `assignmentType`, `internshipId`, `title`, `description`, `isMandatory`, `dueDate` and return `Assignment` |
| FR-AM3 | `Assignment` model must use `#[Fillable]` with `internship_id`, `document_id`, `assignment_type`, `title`, `description`, `is_mandatory`, `due_date`, `status`, `created_by` |
| FR-AM4 | `assignment_type` must support: `project`, `report`, `essay` (default: `project`) |
| FR-AM5 | New assignments must default to `DRAFT` status |
| FR-AM6 | `UpdateAssignmentAction` must filter null values for partial updates |
| FR-AM7 | `DeleteAssignmentAction` must cascade-delete submissions via DB constraint |
| FR-AM8 | `AssignmentPolicy::delete()` must require admin role AND no existing submissions |
| FR-AM9 | `AssignmentManager` must support search (title, type, internship name) and filters (status, type, is_mandatory) |

### Assignment Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-AL1 | `AssignmentStatus` enum must define: `DRAFT`, `PUBLISHED`, `CLOSED` |
| FR-AL2 | Valid transitions: DRAFT → [PUBLISHED, CLOSED]; PUBLISHED → [CLOSED]; CLOSED → [] |
| FR-AL3 | `PublishAssignmentAction` must guard that status is DRAFT (throw `RejectedException` otherwise) |
| FR-AL4 | `PublishAssignmentAction` must dispatch `AssignmentPublished` event |
| FR-AL5 | `PublishAssignmentAction` must send `AssignmentNotification` to all students registered for the internship |
| FR-AL6 | `NotifyOnAssignmentPublished` listener must notify the assignment creator |

### Student Submission

| ID   | Requirement |
| ---- | ----------- |
| FR-SS1 | `SubmitAssignment` must be accessible at route `/student/assignments` with `auth` and `role:student` middleware |
| FR-SS2 | `SubmitAssignmentAction` must guard: assignment is PUBLISHED (throw `RejectedException` otherwise) |
| FR-SS3 | `SubmitAssignmentAction` must guard: assignment is not overdue (use `AssignmentRules::isOverdue()`) |
| FR-SS4 | `SubmitAssignmentAction` must guard: student has active/placed registration |
| FR-SS5 | `SubmitAssignmentAction` must guard: no existing submission in SUBMITTED or GRADED status |
| FR-SS6 | If existing submission is REVISION_REQUIRED: update content, transition to SUBMITTED, clear feedback |
| FR-SS7 | `Submission` model must enforce unique constraint on `(assignment_id, registration_id)` |
| FR-SS8 | `SubmitAssignmentData` DTO must contain `content` (string); file upload handled separately via MediaLibrary |
| FR-SS9 | File uploads must accept: pdf, doc, docx, zip, ppt, pptx; max 10MB |
| FR-SS10 | Content must be minimum 20 characters |

### Grading

| ID   | Requirement |
| ---- | ----------- |
| FR-GD1 | `SubmissionGrading` must be accessible at routes for admin, teacher, and supervisor roles |
| FR-GD2 | `GradeSubmissionAction` must validate score range: 0–100 (throw `RejectedException` otherwise) |
| FR-GD3 | `GradeSubmissionAction` must set `score`, `feedback`, `status=GRADED`, `graded_by`, `graded_at` |
| FR-GD4 | `GradeSubmissionAction` must log `submission_graded` |
| FR-GD5 | `SubmissionGrading` must filter submissions by: status (SUBMITTED, REVISION_REQUIRED), search (student name), assignment, status filter |

### Revision Request

| ID   | Requirement |
| ---- | ----------- |
| FR-RV1 | `RequestSubmissionRevisionAction` must guard status is SUBMITTED (throw `RejectedException` otherwise) |
| FR-RV2 | `RequestSubmissionRevisionAction` must set status to REVISION_REQUIRED and store feedback |
| FR-RV3 | `RequestSubmissionRevisionAction` must dispatch `SubmissionRevisionRequested` event |
| FR-RV4 | Feedback must be minimum 10 characters for revision request |

### Verification

| ID   | Requirement |
| ---- | ----------- |
| FR-VF1 | `VerifySubmissionAction` must set status to `verified`, `verified_by`, `verified_at` |
| FR-VF2 | Verification must be available to admin, teacher, and supervisor (via mentor proxy) |

### Notifications

| ID   | Requirement |
| ---- | ----------- |
| FR-NF1 | `AssignmentNotification` must notify students on assignment publish (channels: mail, broadcast, database) |
| FR-NF2 | `SubmissionFeedbackNotification` must notify students on grading or revision request |
| FR-NF3 | Notifications must implement `ShouldQueue` for async delivery |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-S1 | All mutations must be authorized via `AssignmentPolicy` or `SubmissionPolicy` |
| NFR-S2 | `SubmissionPolicy` must enforce student-only create, owner-only update (when SUBMITTED), admin-only delete |
| NFR-S3 | Deadline enforcement must be checked at Action layer, not just UI |
| NFR-S4 | Cross-Role Proxy must be respected for supervisor verification via `HasMentorProxy` trait |
| NFR-P1 | Student assignment list must load in < 500ms |
| NFR-P2 | Submission grading list with JOINs must load in < 1s |
| NFR-P3 | File upload processing must complete in < 5s for 10MB files |
| NFR-R1 | Submission creation must be wrapped in a database transaction |
| NFR-R2 | Unique constraint on `(assignment_id, registration_id)` must prevent duplicate submissions at DB level |
| NFR-U1 | File upload must show progress indicator during upload |
| NFR-U2 | Revision feedback must be prominently displayed on student submission view |
| NFR-U3 | Assignment due dates must display in the user's local timezone |
| NFR-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-L1 | All user-facing strings must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### Assignment Model

```
App\Assignment\Models\Assignment
  Table: assignments (UUID PK)
  Fillable: internship_id, document_id, assignment_type, title, description, is_mandatory, due_date, status, created_by
  Casts: due_date → datetime, is_mandatory → boolean, status → AssignmentStatus
  Relations: internship() BelongsTo Internship, submissions() HasMany Submission,
             creator() BelongsTo User, document() BelongsTo Document
  Bridge: asAssignmentRules() → AssignmentRules
  Factory: AssignmentFactory
```

### Submission Model

```
App\Assignment\Submission\Models\Submission
  Table: submissions (UUID PK)
  Implements: HasMedia (Spatie MediaLibrary)
  Fillable: assignment_id, registration_id, student_id, content, metadata, status, submitted_at,
            score, feedback, graded_by, graded_at, verified_by, verified_at
  Casts: metadata → array, submitted_at → datetime, graded_at → datetime, status → SubmissionStatus
  Relations: assignment() BelongsTo Assignment, registration() BelongsTo Registration,
             student() BelongsTo User, grader() BelongsTo User
  Bridge: asSubmissionState() → SubmissionState
  Media: file (single)
  Unique: (assignment_id, registration_id)
  Factory: SubmissionFactory
```

### AssignmentRules Entity

```
App\Assignment\Entities\AssignmentRules extends BaseEntity (final readonly)
  Constructor: (bool $isMandatory, ?Carbon $dueDate)
  Factory: fromModel(Model)
  Methods: isMandatory(): bool, isOverdue(Carbon $now): bool
```

### SubmissionState Entity

```
App\Assignment\Submission\Entities\SubmissionState extends BaseEntity (final readonly)
  Constructor: (SubmissionStatus $status)
  Factory: fromModel(Model)
  Methods: canBeEdited(): bool, isVerified(): bool
```

### Enums

```
App\Assignment\Enums\AssignmentStatus: string
  Implements: LabelEnum, StatusEnum
  Cases: DRAFT='draft', PUBLISHED='published', CLOSED='closed'
  Transitions: DRAFT→[PUBLISHED, CLOSED], PUBLISHED→[CLOSED], CLOSED→[]

App\Assignment\Submission\Enums\SubmissionStatus: string
  Implements: LabelEnum, StatusEnum
  Cases: DRAFT='draft', SUBMITTED='submitted', VERIFIED='verified', GRADED='graded', REVISION_REQUIRED='revision_required'
  Transitions: DRAFT→[SUBMITTED], SUBMITTED→[VERIFIED, GRADED, REVISION_REQUIRED],
               REVISION_REQUIRED→[SUBMITTED], VERIFIED→[], GRADED→[]
```

### DTO

```
App\Assignment\Submission\Data\SubmitAssignmentData extends BaseData
  Properties: content: string
```

### Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `CreateAssignmentAction` | `BaseCommandAction` | `assignmentType, internshipId, title, ?description, isMandatory, ?dueDate` | `Assignment` |
| `UpdateAssignmentAction` | `BaseCommandAction` | `Assignment, ?assignmentType, ?title, ?description, ?isMandatory, ?dueDate` | `Assignment` |
| `DeleteAssignmentAction` | `BaseCommandAction` | `Assignment` | `void` |
| `PublishAssignmentAction` | `BaseCommandAction` | `Assignment` | `Assignment` |
| `SubmitAssignmentAction` | `BaseCommandAction` | `User $student, Assignment, SubmitAssignmentData` | `Submission` |
| `GradeSubmissionAction` | `BaseCommandAction` | `Submission, int $score, ?feedback` | `Submission` |
| `VerifySubmissionAction` | `BaseCommandAction` | `Submission` | `Submission` |
| `RequestSubmissionRevisionAction` | `BaseCommandAction` | `Submission, string $feedback` | `Submission` |

### Events

| Event | Dispatched By |
| ----- | ------------- |
| `AssignmentPublished` | `PublishAssignmentAction` |
| `SubmissionRevisionRequested` | `RequestSubmissionRevisionAction` |

### Listeners

| Listener | Event | Queued |
| -------- | ----- | ------ |
| `NotifyOnAssignmentPublished` | `AssignmentPublished` | Yes |

### Notifications

| Notification | Trigger | Channels |
| ------------ | ------- | -------- |
| `AssignmentNotification` | Assignment published (to students) | mail, broadcast, database |
| `SubmissionFeedbackNotification` | Grading or revision request (to student) | mail, broadcast, database |

### Policies

| Policy | Abilities |
| ------ | --------- |
| `AssignmentPolicy` | viewAny: all roles, view: all roles, create: admin/teacher, update: admin/teacher, publish: admin/teacher, delete: admin+no submissions |
| `SubmissionPolicy` | viewAny: admin/teacher/supervisor, view: admin/owner/mentorProxy, create: student, update: owner+SUBMITTED, verify: admin/mentorProxy, delete: admin |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /student/assignments` | `SubmitAssignment` | `student.assignments` | `auth`, `role:student` |
| `GET /admin/assignments` | `AssignmentManager` | `sysadmin.assignments` | `auth`, `role:super_admin\|admin` |
| `GET /admin/submissions/grading` | `SubmissionGrading` | `sysadmin.submissions.grading` | `auth`, `role:super_admin\|admin` |
| `GET /supervision/submissions/grading` | `SubmissionGrading` | `supervision.submissions.grading` | `auth`, `role:teacher\|supervisor` |
| `GET /teacher/submissions/grading` | `SubmissionGrading` | `teacher.submissions.grading` | `auth`, `role:teacher` |

### Database Schema

```
assignments:
  id: uuid (PK)
  internship_id: foreignUuid → internships.id (cascadeOnDelete)
  document_id: foreignUuid → documents.id (nullOnDelete, nullable, indexed)
  assignment_type: string (default 'project')
  title: string
  description: text (nullable)
  is_mandatory: boolean (default false)
  due_date: dateTime (nullable)
  status: string(20) (default 'draft')
  created_by: foreignUuid → users.id (nullOnDelete, nullable)
  timestamps
  Indexes: (internship_id, status), document_id

submissions:
  id: uuid (PK)
  assignment_id: foreignUuid → assignments.id (cascadeOnDelete)
  registration_id: foreignUuid → registrations.id (cascadeOnDelete)
  student_id: foreignUuid → users.id (cascadeOnDelete)
  content: text (nullable)
  metadata: json (nullable)
  submitted_at: timestamp (nullable)
  status: string(20) (default 'draft')
  score: float (nullable)
  feedback: text (nullable)
  graded_by: foreignUuid → users.id (set null, nullable)
  graded_at: timestamp (nullable)
  verified_by: foreignUuid → users.id (set null, nullable)
  verified_at: timestamp (nullable)
  timestamps
  Unique: (assignment_id, registration_id)
  Indexes: (student_id, status), (assignment_id, status), (registration_id, status), status
```

---

## 7. Design Decisions

### DD-1 — Separate Assignment Status and Submission Status

**Decision:** `Assignment` and `Submission` each have independent status enums and state machines.
**Rationale:** An assignment can be PUBLISHED while individual submissions are at different stages (SUBMITTED, GRADED, REVISION_REQUIRED). Coupling the statuses would force all-or-nothing transitions that don't reflect the one-to-many relationship.
**Trade-off:** Two separate state machines to maintain and reason about. Rejected alternative: single status field on both (creates ambiguity when assignment is CLOSED but some submissions are still being graded).

### DD-2 — File Upload via MediaLibrary, Not Through DTO

**Decision:** File uploads are handled in the Livewire component via `WithFileUploads` and stored via Spatie MediaLibrary, bypassing the `SubmitAssignmentData` DTO.
**Rationale:** The DTO carries business data (content) to the Action. File uploads are infrastructure concerns — they need Livewire's file upload handling, temporary storage, and MediaLibrary's collection management. Mixing file handling into the DTO would violate C6 (DTO must not import Model/Entity).
**Trade-off:** File upload logic lives in the Presentation layer rather than the Action layer. Rejected alternative: pass UploadedFile through DTO (violates C6, adds framework dependency to DTO).

### DD-3 — Revision Loop Instead of Resubmit-From-Scratch

**Decision:** When a submission is returned for revision, the student updates the existing record rather than creating a new one.
**Rationale:** Preserves the submission history (content changes are tracked in the DB via timestamps). The unique constraint on `(assignment_id, registration_id)` naturally enforces one submission per student per assignment. The REVISION_REQUIRED → SUBMITTED transition reuses the same record.
**Trade-off:** Previous content versions are overwritten. Rejected alternative: create new submission records (breaks unique constraint, complicates grading history).

### DD-4 — Deadline Enforcement at Action Layer

**Decision:** `SubmitAssignmentAction` checks `AssignmentRules::isOverdue()` before accepting a submission.
**Rationale:** Deadline enforcement must be authoritative at the business logic layer, not just hidden behind a disabled submit button in the UI. A determined user (or API call) could bypass UI restrictions. The Action layer is the single source of truth.
**Trade-off:** Students cannot submit late work at all (no grace period). Rejected alternative: allow late submission with a flag (adds complexity, may not align with school policy).

### DD-5 — Dual Notification on Publish

**Decision:** `PublishAssignmentAction` sends notifications to students inline AND dispatches `AssignmentPublished` event for the creator notification listener.
**Rationale:** Student notifications are immediate and critical — they must be sent before the Action returns. Creator notification is a side effect that can be queued. Separating them ensures students are notified even if the event listener fails.
**Trade-off:** Two notification paths to maintain. Rejected alternative: all notifications via events (delayed student notification degrades UX).

---

## 8. Success Metrics

### 8.1 Data Integrity

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Duplicate submissions per student per assignment | 0 | Unique constraint on `(assignment_id, registration_id)` |
| Late submissions accepted | 0 | `SubmitAssignmentAction` blocks overdue assignments |
| Invalid status transitions | 0 | `AssignmentStatus` and `SubmissionStatus` enforce valid transitions |
| Orphaned submissions after assignment delete | 0 | Cascade delete via DB constraint |

### 8.2 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Student assignment list | < 500ms | Published assignments for student's internship |
| Submission grading list | < 1s | Filtered, paginated, JOINed query |
| File upload (10MB) | < 5s | Livewire upload + MediaLibrary storage |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Notification delivery | < 30s after event | Queued notification processing |
| Revision feedback visibility | Prominent on student view | Feedback displayed at top of submission |
| Due date display | Timezone-aware | Shows in user's local timezone |

### 8.4 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations via Actions |
| No service locator (C2) | 0 violations | Constructor injection throughout |
| Entity purity (C5) | 0 violations | AssignmentRules/SubmissionState import no Actions |
| DTO purity (C6) | 0 violations | SubmitAssignmentData imports no Models |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |

---

## Quick References

- `app/Assignment/Models/Assignment.php` — Assignment model with status and relationships
- `app/Assignment/Submission/Models/Submission.php` — Submission model with MediaLibrary
- `app/Assignment/Entities/AssignmentRules.php` — Entity with mandatory/overdue checks
- `app/Assignment/Submission/Entities/SubmissionState.php` — Entity with edit/verify checks
- `app/Assignment/Enums/AssignmentStatus.php` — DRAFT/PUBLISHED/CLOSED enum
- `app/Assignment/Submission/Enums/SubmissionStatus.php` — 5-case submission status enum
- `app/Assignment/Submission/Data/SubmitAssignmentData.php` — Submission DTO
- `app/Assignment/Actions/CreateAssignmentAction.php` — Create command
- `app/Assignment/Actions/UpdateAssignmentAction.php` — Update command
- `app/Assignment/Actions/DeleteAssignmentAction.php` — Delete command
- `app/Assignment/Actions/PublishAssignmentAction.php` — Publish with notifications
- `app/Assignment/Submission/Actions/SubmitAssignmentAction.php` — Submit with guards
- `app/Assignment/Submission/Actions/GradeSubmissionAction.php` — Grade with score validation
- `app/Assignment/Submission/Actions/VerifySubmissionAction.php` — Verify receipt
- `app/Assignment/Submission/Actions/RequestSubmissionRevisionAction.php` — Return for revision
- `app/Assignment/Livewire/AssignmentManager.php` — Admin CRUD UI
- `app/Assignment/Submission/Livewire/SubmitAssignment.php` — Student submission UI
- `app/Assignment/Submission/Livewire/SubmissionGrading.php` — Grading UI
- `app/Assignment/Policies/AssignmentPolicy.php` — Authorization
- `app/Assignment/Submission/Policies/SubmissionPolicy.php` — Submission authorization
- `app/Assignment/Events/AssignmentPublished.php` — Publish event
- `app/Assignment/Submission/Events/SubmissionRevisionRequested.php` — Revision event
- `app/Assignment/Listeners/NotifyOnAssignmentPublished.php` — Creator notification
- `app/Assignment/Notifications/AssignmentNotification.php` — Student publish notification
- `app/Assignment/Submission/Notifications/SubmissionFeedbackNotification.php` — Grading/revision notification
- `database/migrations/2026_01_04_000009_create_assignments_table.php` — Assignments schema
- `database/migrations/2026_01_04_000010_create_submissions_table.php` — Submissions schema
- `routes/web/assignment.php` — Route definitions
- `docs/modules/assignment.md` — Module conceptual documentation
