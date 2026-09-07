# Assignment Submission — Student Work Submission Lifecycle

> **Spec ID:** T657B

## Description

Specification of the student submission lifecycle: draft creation, content submission, deadline
enforcement, file upload via Spatie MediaLibrary, and resubmission after revision. The
assignment definition (creation, lifecycle, notifications) is defined in
[assignment.md](T657Z-assignment.md). Teacher grading and revision requests are defined in
[assignment-grading.md](T657Z-assignment-grading.md).

---

## 1. Problem Statements

### PS-1 — Student Submission With Draft Workflow

Students need to submit their work progressively — starting with drafts, finalizing when ready,
and resubmitting after feedback. A single-submission model would force students to submit
incomplete work or lose their previous submission on revision.

### PS-2 — Deadline Enforcement With Overdue Detection

Assignments have due dates. Submissions after the deadline should be blocked. Without automated
enforcement, teachers must manually check dates and students may unknowingly submit late.

### PS-3 — File Upload Support for Submissions

Students must be able to attach files (PDF, DOC, DOCX, ZIP, PPT, PPTX) to their submissions. A
text-only submission channel would not accommodate the practical coursework products (reports,
project files).

### PS-4 — One Submission Per Student Per Assignment

Each student must have exactly one submission record per assignment — overwriting via the revision
loop, not creating new records. The DB-level unique constraint is the authoritative guard.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Support student submission with draft/submitted/revision_required workflow |
| G2  | Allow file uploads via Spatie MediaLibrary (pdf, doc, docx, zip, ppt, pptx) |
| G3  | Enforce deadline-based submission blocking at the Action layer |
| G4  | Enforce unique submission per student per assignment via DB constraint |
| G5  | Resubmit by updating existing record when in REVISION_REQUIRED status |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Multiple file uploads per submission (single attachment only) |
| NG2  | Submission version history (overwrites via resubmit) |
| NG3  | Plagiarism detection or similarity scoring |
| NG4  | Peer review or collaborative drafts |

---

## 3. User Stories / Use Cases

### UC-T657B-1 — Student Submits Work

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

### UC-T657B-2 — Student Resubmits After Revision

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

### Student Submission

| ID   | Requirement |
| ---- | ----------- |
| FR-T657B-SS1 | `SubmitAssignment` must be accessible at route `/student/assignments` with `auth` and `role:student` middleware |
| FR-T657B-SS2 | `SubmitAssignmentAction` must guard: assignment is PUBLISHED (throw `RejectedException` otherwise) |
| FR-T657B-SS3 | `SubmitAssignmentAction` must guard: assignment is not overdue (use `AssignmentRules::isOverdue()`) |
| FR-T657B-SS4 | `SubmitAssignmentAction` must guard: student has active/placed registration |
| FR-T657B-SS5 | `SubmitAssignmentAction` must guard: no existing submission in SUBMITTED or GRADED status |
| FR-T657B-SS6 | If existing submission is REVISION_REQUIRED: update content, transition to SUBMITTED, clear feedback |
| FR-T657B-SS7 | `Submission` model must enforce unique constraint on `(assignment_id, registration_id)` |
| FR-T657B-SS8 | `SubmitAssignmentData` DTO must contain `content` (string); file upload handled separately via MediaLibrary |
| FR-T657B-SS9 | File uploads must accept: pdf, doc, docx, zip, ppt, pptx; max 10MB |
| FR-T657B-SS10 | Content must be minimum 20 characters |

### Submission Status Machine

| ID   | Requirement |
| ---- | ----------- |
| FR-T657B-SM1 | `SubmissionStatus` enum must define: `DRAFT`, `SUBMITTED`, `VERIFIED`, `GRADED`, `REVISION_REQUIRED` |
| FR-T657B-SM2 | Valid transitions: DRAFT → [SUBMITTED]; SUBMITTED → [VERIFIED, GRADED, REVISION_REQUIRED]; REVISION_REQUIRED → [SUBMITTED]; VERIFIED → []; GRADED → [] |
| FR-T657B-SM3 | `SubmissionState::canBeEdited()` must return true only when status allows content mutation |
| FR-T657B-SM4 | `SubmissionState::isVerified()` must return true when status equals `VERIFIED` |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-T657B-S1 | All submission mutations must be authorized via `SubmissionPolicy` |
| NFR-T657B-S2 | `SubmissionPolicy` must enforce student-only create, owner-only update (when SUBMITTED) |
| NFR-T657B-S3 | Deadline enforcement must be checked at Action layer, not just UI |
| NFR-T657B-S4 | Cross-Role Proxy must be respected for supervisor verification via `HasMentorProxy` trait |
| NFR-T657B-R1 | Submission creation must be wrapped in a database transaction |
| NFR-T657B-R2 | Unique constraint on `(assignment_id, registration_id)` must prevent duplicate submissions at DB level |
| NFR-T657B-U1 | File upload must show progress indicator during upload |
| NFR-T657B-U2 | Revision feedback must be prominently displayed on student submission view |

---

## 6. API / Data Contracts

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

### SubmissionState Entity

```
App\Assignment\Submission\Entities\SubmissionState extends BaseEntity (final readonly)
  Constructor: (SubmissionStatus $status)
  Factory: fromModel(Model)
  Methods: canBeEdited(): bool, isVerified(): bool
```

### SubmissionStatus Enum

```
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

### Action

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `SubmitAssignmentAction` | `BaseCommandAction` | `User $student, Assignment, SubmitAssignmentData` | `Submission` |

### Route

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /student/assignments` | `SubmitAssignment` | `student.assignments` | `auth`, `role:student` |

---

## 7. Design Decisions

### DD-1 — File Upload via MediaLibrary, Not Through DTO

**Decision:** File uploads are handled in the Livewire component via `WithFileUploads` and stored via Spatie MediaLibrary, bypassing the `SubmitAssignmentData` DTO.

**Rationale:** The DTO carries business data (content) to the Action. File uploads are infrastructure concerns — they need Livewire's file upload handling, temporary storage, and MediaLibrary's collection management. Mixing file handling into the DTO would violate C6 (DTO must not import Model/Entity).

**Trade-off:** File upload logic lives in the Presentation layer rather than the Action layer. Rejected alternative: pass UploadedFile through DTO (violates C6, adds framework dependency to DTO).

### DD-2 — Revision Loop Updates the Same Record

**Decision:** When a submission is returned for revision, the student updates the existing record rather than creating a new one.

**Rationale:** Preserves the submission history (content changes are tracked in the DB via timestamps). The unique constraint on `(assignment_id, registration_id)` naturally enforces one submission per student per assignment. The REVISION_REQUIRED → SUBMITTED transition reuses the same record.

**Trade-off:** Previous content versions are overwritten. Rejected alternative: create new submission records (breaks unique constraint, complicates grading history).

### DD-3 — Deadline Enforcement at Action Layer

**Decision:** `SubmitAssignmentAction` checks `AssignmentRules::isOverdue()` before accepting a submission.

**Rationale:** Deadline enforcement must be authoritative at the business logic layer, not just hidden behind a disabled submit button in the UI. A determined user (or API call) could bypass UI restrictions. The Action layer is the single source of truth.

**Trade-off:** Students cannot submit late work at all (no grace period). Rejected alternative: allow late submission with a flag (adds complexity, may not align with school policy).

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Duplicate submissions per student per assignment | 0 | Unique constraint on `(assignment_id, registration_id)` |
| Late submissions accepted | 0 | `SubmitAssignmentAction` blocks overdue assignments |
| Invalid status transitions | 0 | `SubmissionStatus` enforces valid transitions |
| Revision feedback visibility | Prominent on student view | Feedback displayed at top of submission |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [assignment.md](T657Z-assignment.md) | `Assignment` model, `AssignmentStatus` enum, `AssignmentRules` entity |
| [placement.md](J9GBH-placement.md) | Active placement records — submissions require an active registration |

### Build Guide
After implementing this spec, students can submit work (text + file) to published assignments,
respecting deadlines and the unique-submission constraint. Resubmission via the revision loop
updates the same record.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [assignment-grading.md](T657Z-assignment-grading.md) | Teacher scores the submission or requests revision |
| 2 | [assignment.md](T657Z-assignment.md) | `AssignmentNotification` notifies students on assignment publish |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
