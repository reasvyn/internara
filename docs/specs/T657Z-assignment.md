# Assignment — Coursework Management & Publishing

> **Spec ID:** T657Z

## Description

Specification of the assignment coursework lifecycle: assignment creation, type/due-date metadata, draft-to-published state machine, and student notification on publish. The student submission lifecycle is defined in [assignment-submission.md](T657Z-assignment-submission.md). Teacher scoring and revision requests are defined in [assignment-grading.md](T657Z-assignment-grading.md).
---

## 1. Problem Statements

### PS-1 — Structured Coursework Distribution to Interns

Teachers and supervisors need to assign coursework (projects, reports, essays) to students during
their PKL period. Without a structured assignment system, tasks are communicated via chat or
email, making tracking and deadline enforcement impossible.


---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide full CRUD for assignments (project, report, essay types) scoped to internships |
| G2  | Manage assignment lifecycle: DRAFT → PUBLISHED → CLOSED |
| G5  | Enable teacher/supervisor grading with numeric score (0–100) and written feedback |
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

### UC-T657Z-1 — Teacher Creates and Publishes an Assignment

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

---

## 4. Functional Requirements

### Assignment Management

| ID   | Requirement |
| ---- | ----------- |
| FR-T657Z-AM1 | `AssignmentManager` must be accessible at route `admin/assignments` with `auth` and `role:super_admin\|admin` middleware |
| FR-T657Z-AM2 | `CreateAssignmentAction` must accept `assignmentType`, `internshipId`, `title`, `description`, `isMandatory`, `dueDate` and return `Assignment` |
| FR-T657Z-AM3 | `Assignment` model must use `#[Fillable]` with `internship_id`, `document_id`, `assignment_type`, `title`, `description`, `is_mandatory`, `due_date`, `status`, `created_by` |
| FR-T657Z-AM4 | `assignment_type` must support: `project`, `report`, `essay` (default: `project`) |
| FR-T657Z-AM5 | New assignments must default to `DRAFT` status |
| FR-T657Z-AM6 | `UpdateAssignmentAction` must filter null values for partial updates |
| FR-T657Z-AM7 | `DeleteAssignmentAction` must cascade-delete submissions via DB constraint |
| FR-T657Z-AM8 | `AssignmentPolicy::delete()` must require admin role AND no existing submissions |
| FR-T657Z-AM9 | `AssignmentManager` must support search (title, type, internship name) and filters (status, type, is_mandatory) |

### Assignment Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-T657Z-AL1 | `AssignmentStatus` enum must define: `DRAFT`, `PUBLISHED`, `CLOSED` |
| FR-T657Z-AL2 | Valid transitions: DRAFT → [PUBLISHED, CLOSED]; PUBLISHED → [CLOSED]; CLOSED → [] |
| FR-T657Z-AL3 | `PublishAssignmentAction` must guard that status is DRAFT (throw `RejectedException` otherwise) |
| FR-T657Z-AL4 | `PublishAssignmentAction` must dispatch `AssignmentPublished` event |
| FR-T657Z-AL5 | `PublishAssignmentAction` must send `AssignmentNotification` to all students registered for the internship |
| FR-T657Z-AL6 | `NotifyOnAssignmentPublished` listener must notify the assignment creator |

### Notifications

| ID   | Requirement |
| ---- | ----------- |
| FR-T657Z-NF1 | `AssignmentNotification` must notify students on assignment publish (channels: mail, broadcast, database) |
| FR-T657Z-NF3 | Notifications must implement `ShouldQueue` for async delivery |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-T657Z-S1 | All mutations must be authorized via `AssignmentPolicy` or `SubmissionPolicy` |
| NFR-T657Z-S2 | `SubmissionPolicy` must enforce student-only create, owner-only update (when SUBMITTED), admin-only delete |
| NFR-T657Z-S3 | Deadline enforcement must be checked at Action layer, not just UI |
| NFR-T657Z-S4 | Cross-Role Proxy must be respected for supervisor verification via `HasMentorProxy` trait |
| NFR-T657Z-R1 | Submission creation must be wrapped in a database transaction |
| NFR-T657Z-R2 | Unique constraint on `(assignment_id, registration_id)` must prevent duplicate submissions at DB level |
| NFR-T657Z-U1 | File upload must show progress indicator during upload |
| NFR-T657Z-U3 | Assignment due dates must display in the user's local timezone |
| NFR-T657Z-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-T657Z-L1 | All user-facing strings must use `__()` translation helper |
| NFR-T657Z-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

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

### Events

| Event | Dispatched By |
| ----- | ------------- |
| `AssignmentPublished` | `PublishAssignmentAction` |
| `SubmissionRevisionRequested` 
### Listeners

| Listener | Event | Queued |
| -------- | ----- | ------ |
| `NotifyOnAssignmentPublished` | `AssignmentPublished` | Yes |

### Notifications

| Notification | Trigger | Channels |
| ------------ | ------- | -------- |
| `AssignmentNotification` | Assignment published (to students) | mail, broadcast, database |

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

### DD-1 — Dual Notification on Publish

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

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Due date display | Timezone-aware | Shows in user's local timezone |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [placement.md](J9GBH-placement.md) | Active placement records — assignments are scoped to placements |

### Build Guide
After implementing this spec, teachers can create assignments with deadlines, students submit work (file uploads or text), and teachers grade submissions. Assignments are the coursework component of the PKL evaluation. The next step is to build document templates for certificate and report generation.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [assignment-submission.md](T657Z-assignment-submission.md) | Student submits work to the published assignment |
| 2 | [assignment-grading.md](T657Z-assignment-grading.md) | Teacher grades the submission or requests revision |
| 3 | [document-templates.md](PKYX6-document-templates.md) | Assignment grades feed into report cards generated from templates |

-------|------|------------|
| 1 | [document-templates.md](PKYX6-document-templates.md) | Assignment grades feed into report cards generated from templates |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
