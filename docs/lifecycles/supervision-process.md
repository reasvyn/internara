# Supervision Process

**Event:** Documenting mentoring and guidance sessions between supervisors and students.

**Phase:** 4 — Operations

**Previous Event:** [Student Registration](student-registration.md)

**Next Event:** [Assessment & Scoring](assessment-scoring.md)

---

## Overview

Supervision logs document the mentoring interactions between supervisors (school teachers and industry supervisors) and students. Each session records the date, topic, and discussion notes. School teachers verify supervision logs created by industry supervisors.

## Trigger

- Scheduled or ad-hoc supervision session between mentor and student
- Industry supervisor completes a mentoring session
- School teacher conducts a guidance session

## Pre-conditions

- Student has an **active** registration
- Supervisor is attached to the student's registration as a mentor
- Internship status is **Active**
- For creation: user has role TEACHER or SUPERVISOR
- For verification: user has role TEACHER (school teacher only)

## Actors

| Actor | Role | Can create | Can verify |
|---|---|---|---|
| School Teacher | TEACHER | Yes (for own students) | Yes |
| Industry Supervisor | SUPERVISOR | Yes (for assigned students) | No |
| Admin | ADMIN, SUPER_ADMIN | Yes | Yes |

---

## Supervision Types

| Type | Performed by | Description | Verifiable by teacher? |
|---|---|---|---|
| **Guidance** | School Teacher | Academic supervision, progress check | N/A (self-created) |
| **Mentoring** | Industry Supervisor | Industry mentoring, skills coaching | Yes |
| **Monitoring** | Both | Periodic progress monitoring | Yes (if created by supervisor) |

The `SupervisionType` enum defines these types with the enum cases: `GUIDANCE`, `SUPERVISORING`, `MONITORING`.

## Supervision Status Lifecycle

The supervision log lifecycle (IN_PROGRESS → SUBMITTED → COMPLETED/VERIFIED) is defined in the [System Lifecycle](system-lifecycle.md#supervision-state-machine). Teacher-created logs auto-verify; supervisor logs require teacher verification.

### Type Determination

The system automatically determines the supervision type based on who is creating the log:

```
registration.teacher_id === auth()->id()  →  type = 'guidance'  (by teacher)
registration.mentor_id === auth()->id()   →  type = 'mentoring' (by supervisor)
```

This logic is embedded in the `CreateSupervisionLogAction`.

---

## Event A: Creating a Supervision Log

### Flow

```
Mentor → Supervision → New Log → Fill Details → Save
```

Navigate to the supervision section. For teachers: **Teacher → Supervision**. For supervisors: **Mentor → Supervision**.

| Field | Validation | Description |
|---|---|---|
| **Student** | Required, exists | Select from assigned students |
| **Date** | Required, date | Session date |
| **Topic** | Required, max 255 | Session topic |
| **Notes** | Required | Discussion notes |
| **Supervision Type** | Auto-determined | Based on role (guidance / mentoring) |

The `CreateSupervisionLogAction`:

1. Finds the registration
2. Determines the type based on whether the creator is the teacher or supervisor
3. Creates the log entry
4. If created by a school teacher, auto-verifies (status: COMPLETED)
5. If created by an industry supervisor, status is SUBMITTED (pending teacher verification)

---

## Event B: Verifying a Supervision Log

**Who:** School Teacher (only teachers can verify per `MentorRole::canVerifySupervisionLog()`)

### Flow

```
Teacher → Supervision → Supervisors' Logs → Review → Verify
```

The `VerifySupervisionLogAction`:

1. Validates the log is not already verified
2. Sets `is_verified = true`
3. Records the verifying teacher's ID and timestamp
4. Sets status to VERIFIED
5. Guards against double-verification

---

## Key Rules

| Rule | Enforcement |
|---|---|
| **Mentor must be assigned** | Can only create logs for students with active registrations where mentor is attached |
| **Auto-type determination** | Type is set based on role (guidance for teacher, mentoring for supervisor) |
| **Auto-verify for teachers** | Teacher-created logs are immediately verified |
| **Supervisor logs need verification** | Industry supervisor logs remain SUBMITTED until teacher verifies |
| **No double-verification** | `is_verified` guard prevents re-verification |

## Seamless Connection

Supervision logs document the mentoring aspect of the internship. They contribute to:

- **[Assessment & Scoring](assessment-scoring.md)** — supervision quality may be factored into evaluation
- **[Period Closing](period-closing.md)** — all supervision logs should be verified before closing the period
- **[Mentor Evaluation](mentor-evaluation.md)** — supervision log activity informs mentor performance reviews
