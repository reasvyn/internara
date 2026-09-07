# Assignment Grading — Teacher Scoring and Revision Feedback

> **Spec ID:** T657C

## Description

Specification of the assignment grading workflow: teacher/supervisor viewing pending submissions,
grading with numeric score (0–100) and written feedback, and revision request loop. The assignment
definition (creation, lifecycle, notifications) is in [assignment.md](T657Z-assignment.md).
The student submission lifecycle is in [assignment-submission.md](T657Z-assignment-submission.md).

---

## 1. Problem Statements

### PS-1 — Teacher/Supervisor Grading With Feedback

Educators need to grade submissions with numeric scores (0–100) and written feedback. Without
structured grading, feedback is scattered across messages and students have no centralized view
of their performance.

### PS-2 — Revision Request Loop

When a submission doesn't meet expectations, the evaluator should return it with feedback for
revision rather than giving a low score. This supports iterative learning. Without a revision
workflow, students would need to create entirely new submissions.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Allow teacher/supervisor to grade submissions with numeric score (0–100) and written feedback |
| G2  | Support revision request: return submission to student with feedback for revision |
| G3  | Log grading actions for audit trail |
| G4  | Notify students of grading and revision request results |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Rubric-based grading (uses Assessment module) |
| NG2  | Peer review or collaborative grading |
| NG3  | Auto-grading or plagiarism detection |

---

## 3. User Stories / Use Cases

### UC-T657C-1 — Teacher Grades a Submission

**Actor:** Teacher
**Preconditions:** SUBMITTED or REVISION_REQUIRED submission exists; teacher is authorized
**Flow:**
1. Teacher navigates to `/supervision/submissions/grading`
2. `SubmissionGrading` lists pending submissions
3. Teacher selects submission, enters score (0–100) and optional feedback
4. `grade()` calls `GradeSubmissionAction::execute(submission, score, feedback)`
5. Action validates score range, updates submission with score, feedback, `GRADED` status
**Postconditions:** Submission graded; student notified via `SubmissionFeedbackNotification`

### UC-T657C-2 — Teacher Requests Revision

**Actor:** Teacher
**Preconditions:** SUBMITTED submission exists
**Flow:**
1. Teacher views submission in `SubmissionGrading`
2. Enters feedback (min 10 chars), clicks "Request Revision"
3. `RequestSubmissionRevisionAction::execute(submission, feedback)` transitions to REVISION_REQUIRED
4. `SubmissionRevisionRequested` event dispatched
5. Student notified via `SubmissionFeedbackNotification`
**Postconditions:** Submission returned to student for revision

---

## 4. Functional Requirements

### Grading

| ID   | Requirement |
| ---- | ----------- |
| FR-T657C-GD1 | `SubmissionGrading` must be accessible at routes for admin, teacher, and supervisor roles |
| FR-T657C-GD2 | `GradeSubmissionAction` must validate score range: 0–100 (throw `RejectedException` otherwise) |
| FR-T657C-GD3 | `GradeSubmissionAction` must set `score`, `feedback`, `status=GRADED`, `graded_by`, `graded_at` |
| FR-T657C-GD4 | `GradeSubmissionAction` must log `submission_graded` |
| FR-T657C-GD5 | `SubmissionGrading` must filter submissions by: status (SUBMITTED, REVISION_REQUIRED), search (student name), assignment, status filter |

### Revision Request

| ID   | Requirement |
| ---- | ----------- |
| FR-T657C-RV1 | `RequestSubmissionRevisionAction` must guard status is SUBMITTED (throw `RejectedException` otherwise) |
| FR-T657C-RV2 | `RequestSubmissionRevisionAction` must set status to REVISION_REQUIRED and store feedback |
| FR-T657C-RV3 | `RequestSubmissionRevisionAction` must dispatch `SubmissionRevisionRequested` event |
| FR-T657C-RV4 | Feedback must be minimum 10 characters for revision request |

### Verification

| ID   | Requirement |
| ---- | ----------- |
| FR-T657C-VF1 | `VerifySubmissionAction` must set status to `verified`, `verified_by`, `verified_at` |
| FR-T657C-VF2 | Verification must be available to admin, teacher, and supervisor (via mentor proxy) |

### Notifications

| ID   | Requirement |
| ---- | ----------- |
| FR-T657C-NF1 | `SubmissionFeedbackNotification` must notify students on grading or revision request |
| FR-T657C-NF2 | Notifications must implement `ShouldQueue` for async delivery |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-T657C-S1 | All mutations must be authorized via `SubmissionPolicy` |
| NFR-T657C-U1 | Due dates must display in the user's local timezone |
| NFR-T657C-M1 | All PHP files must declare `strict_types=1` and follow PSR-12 |
| NFR-T657C-L1 | All user-facing strings must use `__()` translation helper |

---

## 6. API / Data Contracts

### Actions

| Action | Base | Accepts | Returns |
| ------ | ---- | ------- | ------- |
| `GradeSubmissionAction` | `BaseCommandAction` | `Submission, int $score, ?feedback` | `Submission` |
| `RequestSubmissionRevisionAction` | `BaseCommandAction` | `Submission, string $feedback` | `Submission` |
| `VerifySubmissionAction` | `BaseCommandAction` | `Submission` | `Submission` |

### Events

| Event | Dispatched By |
| ----- | ------------ |
| `SubmissionRevisionRequested` | `RequestSubmissionRevisionAction` |

### Notifications

| Notification | Trigger | Channels |
| ------------ | ------- | -------- |
| `SubmissionFeedbackNotification` | Grading or revision request (to student) | mail, broadcast, database |

### Policies

| Policy | Abilities |
| ------ | --------- |
| `SubmissionPolicy` | viewAny: admin/teacher/supervisor, view: admin/owner/mentorProxy, create: student, update: owner+SUBMITTED, verify: admin/mentorProxy, delete: admin |

### Routes

| Route | Component | Name | Middleware |
| ----- | --------- | ---- | ---------- |
| `GET /admin/submissions/grading` | `SubmissionGrading` | `sysadmin.submissions.grading` | `auth`, `role:super_admin\|admin` |
| `GET /supervision/submissions/grading` | `SubmissionGrading` | `supervision.submissions.grading` | `auth`, `role:teacher\|supervisor` |
| `GET /teacher/submissions/grading` | `SubmissionGrading` | `teacher.submissions.grading` | `auth`, `role:teacher` |

---

## 7. Design Decisions

### DD-1 — Score Range Enforcement at Action Layer

**Decision:** `GradeSubmissionAction` validates score range 0–100 at the Action layer.

**Rationale:** Score validation must be authoritative at the business logic layer. A direct database update or API call could bypass UI validation. The Action is the authoritative boundary.

### DD-2 — Dual Notification on Revision Request

**Decision:** `SubmissionFeedbackNotification` is sent inline in `RequestSubmissionRevisionAction`, not dispatched as an event.

**Rationale:** Revision requests are less frequent than grading but equally important to communicate immediately. Dispatching a `SubmissionRevisionRequested` event for the notification keeps the event system available for future listeners while ensuring the notification is always delivered.

---

## 8. Success Metrics

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Notification delivery | < 30s after event | Queued notification processing |
| Revision feedback visibility | Prominent on student view | Feedback displayed at top of submission |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [assignment-submission.md](T657Z-assignment-submission.md) | `Submission` model, `SubmissionStatus` enum, student submission flow |
| [notification-infrastructure.md](TXR2H-notification-infrastructure.md) | `SubmissionFeedbackNotification` channel registration |

### Build Guide
After implementing this spec, teachers can view pending submissions, score them (0–100), and request
revision with feedback. Students receive notifications for both outcomes.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [assignment.md](T657Z-assignment.md) | Teacher creates and publishes assignments; `AssignmentNotification` notifies students on publish |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References
