# Assignment Workflow

**Event:** Creating, submitting, and grading assignments during the internship.

**Phase:** 4 — Operations

**Previous Event:** [Student Registration](student-registration.md)

**Next Events:** [Supervision Process](supervision-process.md), [Assessment & Scoring](assessment-scoring.md)

---

## Overview

Assignments are tasks given to students by teachers to measure specific competencies. Students submit their work, and teachers grade or verify the submissions. Assignments are linked to internships and may be configured as graded or pass/fail.

## Trigger

- Teacher identifies a learning task for students (assignment creation)
- Student completes and submits work (submission)
- Teacher reviews and scores (grading)

## Pre-conditions

- Students have **active** registrations in the internship
- Internship status is **Active**
- For creation: user is TEACHER, ADMIN, or SUPER_ADMIN
- For submission: user is STUDENT with active registration
- For grading: user is TEACHER, ADMIN, SUPER_ADMIN, or SUPERVISOR

## Actors

| Actor | Role | Create | Publish | Submit | Grade | Verify |
|---|---|---|---|---|---|---|
| Teacher | TEACHER | Yes | Yes | No | Yes | Yes |
| Admin | ADMIN, SUPER_ADMIN | Yes | Yes | No | Yes | Yes |
| Student | STUDENT | No | No | Yes | No | No |
| Supervisor | SUPERVISOR | No | No | No | Yes (limited) | No |

---

## Assignment Status Lifecycle

```
DRAFT  ──►  PUBLISHED  ──►  CLOSED
```

## Submission Status Lifecycle

The submission lifecycle (DRAFT → SUBMITTED → VERIFIED/GRADED/REVISION_REQUIRED) mirrors the logbook state machine defined in the [System Lifecycle](system-lifecycle.md#assignment--submission-state-machine).

---

## Event A: Creating an Assignment

### Flow

```
Teacher → Admin → Assignments → Create → Fill Details → Save (Draft)
```

Navigate to **Admin → Assignments**.

| Field | Validation | Description |
|---|---|---|
| **Title** | Required, max 255 | Assignment title |
| **Description** | Required | Detailed instructions |
| **Internship** | Required | Which internship this belongs to |
| **Assignment Type** | Required, exists | Type (e.g., report, presentation, code) |
| **Due Date** | Required, future | Submission deadline |
| **Max Score** | Required, numeric | Maximum achievable score |
| **Attachment** | Optional | Reference document |

The `CreateAssignmentAction` creates the assignment with status **DRAFT**. The teacher can continue editing until ready to publish.

---

## Event B: Publishing an Assignment

### Flow

```
Teacher → Select Assignment → Publish
```

`PublishAssignmentAction`:

1. Validates assignment is in **DRAFT** status
2. Transitions to **PUBLISHED**
3. Sends `AssignmentNotification` to all students with active registrations in the internship
4. The assignment appears in students' portals

---

## Event C: Student Submits Work

### Flow

```
Student → Assignments → Select Assignment → Upload Work → Submit
```

Navigate to **Student → Assignments**.

| Field | Validation | Description |
|---|---|---|
| **Content** | Required | Text response or description |
| **Attachment** | Optional, file | Uploaded work file |

### Save as Draft

Student saves progress as DRAFT. Can continue editing later.

### Submit

Creates or updates the submission:

- If DRAFT exists → upgrades to SUBMITTED
- If REVISION_REQUIRED exists → resubmits as SUBMITTED
- New submission → creates with status SUBMITTED

---

## Event D: Teacher Grades Submission

### Flow

```
Teacher → Assignment → Submissions → Select Student → Grade
```

The `GradeSubmissionAction`:

| Field | Validation | Description |
|---|---|---|
| **Score** | Required, 0-100 | Numeric grade |
| **Feedback** | Optional | Comments to student |
| **Status** | Required | VERIFIED or REVISION_REQUIRED |

If **VERIFIED**:
- Score is recorded
- `verifySubmissionAction` equivalent (marks verified_by, verified_at)
- Student receives notification with score and feedback
- Terminal state — no further changes

If **REVISION_REQUIRED**:
- Status set to REVISION_REQUIRED
- Student receives feedback and can resubmit
- Previous score is not recorded

### Re-grading

If a submission needs adjustment after grading:
- Admin can re-open and re-grade
- Status transitions back to GRADED with updated score

---

## Key Rules

| Rule | Enforcement |
|---|---|
| **One submission per student per assignment** | Single submission record |
| **Score bounded 0-100** | Validation on grade action |
| **Must be PUBLISHED to submit** | Student can only submit to published assignments |
| **DRAFT to PUBLISH is one-way** | Once published, cannot revert to draft |
| **Notification on publish** | All enrolled students notified automatically |
| **Supervisor can grade** | Broader than verify — supervisor can score but not verify logbooks |

## Seamless Connection

Assignments and submissions contribute to:

- **[Assessment & Scoring](assessment-scoring.md)** — assignment scores can be auto-imported into the final assessment
- **[Period Closing](period-closing.md)** — all submissions must be graded before the period can be closed
