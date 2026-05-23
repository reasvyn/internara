# Mentor Domain

## Purpose

Mentor provides the supervision toolkit for teachers and company supervisors who oversee students 
during internships. While the Mentee domain is the student's self-service view, Mentor is the 
overseer's command center: managing assigned students, reviewing their progress across all 
domains, keeping private supervision notes, facilitating structured guidance sessions, and 
managing the mentor-student relationship lifecycle. The mentor role is functional — it resolves 
from both teacher and supervisor user roles via Auth's functional role mapping. This means the 
mentoring subsystem operates identically whether the mentor is an academic teacher or a company 
supervisor, without either needing a special user role.

## Boundary

**In scope:** Mentor assignment management (assign primary and co-mentors to students, define 
assignment time periods), supervision logs (mentor's private notes about each student — 
observations, concerns, action items, meeting notes — never shared with the student), mentor 
dashboard (aggregated view of all assigned students with progress indicators, pending actions, 
flags, and quick-action links), bulk student operations (viewing, filtering, sorting, reporting 
across the entire mentee roster), supervision schedule management (set availability, recurring 
session times, one-off appointments), mentor handover workflow (transferring students between 
mentors with log transfer), mentor-student communication tools (in-app messaging or 
notification-based), supervision session tracking (scheduled vs. completed sessions).

**Out of scope:** Student-facing dashboard and self-service tools (Mentee domain), structured 
evaluation form submission (Evaluation domain — mentors complete evaluations there, not here), 
logbook entry creation (Logbook domain — mentor reviews entries, never creates them), 
assignment creation and grading (Assignment domain — mentors create tasks and grade submissions 
there), incident reporting and investigation (Incident domain), attendance recording and status 
management (Attendance domain), rubric and competency definition (Assessment domain).

## Key Concepts

**Mentor Assignments.** A mentor assignment links a user (with teacher or supervisor role) to a 
student's registration. Two assignment types exist. PRIMARY mentor: the main supervisor with 
overall responsibility for the student's progress, full visibility into all student data across 
all domains, and primary point of contact for the student. CO-MENTOR: a supporting role with 
configurable access scope (can be restricted to specific domains or activities). Each student 
must have exactly one primary mentor at all times during an active internship. A student can have 
multiple co-mentors. Assignments are time-bound — they have start and end dates matching the 
internship period. The assignment history is fully preserved: even after a student completes 
their internship, the assignment record remains in the system for audit and portfolio reference.

**Supervision Logs.** Private, confidential notes that mentors keep about their students. Unlike 
evaluations (which are shared with the student), supervision logs are mentor-only — visible 
only to the mentor who created them and to administrators with explicit access. Logs capture: 
observations from supervision sessions (how is the student doing?), professional development 
notes (areas of growth, skills being developed), concerns and flags (issues that may need 
attention), action items and follow-ups (things the mentor and student agreed to work on), and 
meeting summaries (what was discussed, decisions made, next steps). Logs can be tagged with 
categories (technical progress, professional behavior, attendance, communication, general) and 
can be linked to specific dates, events, or logbook entries. They are fully searchable and 
filterable within the mentor's own view. Supervision logs serve as the mentor's working memory 
— they are not shared, not evaluated, and not part of the student's formal record.

**Mentor Dashboard.** The mentor's primary workspace — a centralized view of all assigned 
students. Each student entry displays: name and program, current overall status indicator 
(green/amber/red based on an algorithm that considers attendance, submission rates, evaluation 
scores, and recent flags), key metrics at a glance (attendance percentage, assignments submitted 
vs. total, pending logbook acknowledgements, upcoming deadlines count, last evaluation score, 
days since last supervision session), recent activity summary (most recent logbook entry preview, 
latest grade, last clock-in), and quick-action buttons (view full profile, open logbook, review 
evaluations, add supervision note, view schedule, message student). The dashboard supports 
filtering by program, status color, cohort, or custom groups. It is sortable by any metric 
column. A "needs attention" filter surfaces students with flags — low attendance, overdue 
submissions, unacknowledged logbooks, declining evaluation scores.

**Supervision Schedule.** Mentors define when they are available for supervision sessions. The 
schedule shows recurring availability (e.g., every Tuesday at 10 AM and Thursday at 2 PM) and 
special one-off slots. Students can view their mentor's availability (read-only) and request 
appointments within available slots. The system tracks scheduled sessions vs. completed sessions, 
providing completion rate statistics. Supervision sessions can be linked to supervision log 
entries for context.

**Mentor Handover.** When a mentor needs to leave (end of contract, role change, extended leave, 
organizational restructuring), their assigned students need new mentors. The handover workflow: 
the system identifies students whose mentor assignment is ending and notifies the program 
coordinator. The coordinator identifies replacement mentors (or the outgoing mentor can recommend 
replacements). Supervision logs are transferred to the new mentor (read-only access for the new 
mentor — they can read the history but cannot edit old entries). The handover is logged with 
both the outgoing and incoming mentors' identities, the date, and any transfer notes. During the 
transition, the student always has at least one mentor assigned — the outgoing mentor remains 
assigned until the new mentor confirms acceptance.

## Requirements

### User Stories & Rules

| Role | Story |
|------|-------|
| Admin | As an admin, I want to assign primary and co-mentors to students so that every student has proper supervision |
| Mentor | As a mentor, I want to see my assigned students and their progress at a glance so that I can prioritize my attention |
| Mentor | As a mentor, I want to keep private supervision notes so that I can track observations and concerns |
| Mentor | As a mentor, I want to manage my supervision schedule so that students can book sessions with me |
| Mentor | As a mentor, I want to hand over my students to another mentor when needed so that supervision continues seamlessly |
| Student | As a student, I want to see who my mentors are so that I know who to contact |
| System | As the system, I want to ensure each student has exactly one primary mentor at all times during an active internship |

### Process Flow

```
Supervision Log Lifecycle:

PENDING ──→ IN_PROGRESS ──→ SUBMITTED ──→ VERIFIED ──→ COMPLETED
                  │                            │
                  ↓                            ↓
              CANCELLED                    CANCELLED
```

- Supervision logs are strictly confidential — visible only to the creating mentor and administrators
- Mentor handover requires new mentor's explicit acceptance
- Primary mentors have full data access; co-mentors have configurable access scope

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateMentorAction` | Creates a new mentor profile |
| `UpdateMentorAction` | Updates mentor details |
| `DeleteMentorAction` | Removes a mentor |
| `ToggleMentorActiveAction` | Toggles a mentor's active status |
| `CreateMentorProfileAction` | Creates a mentor's extended profile |
| `UpdateMentorProfileAction` | Updates the extended mentor profile |
| `CreateSupervisionLogAction` | Creates a private supervision note |
| `VerifySupervisionLogAction` | Verifies a supervision log entry |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Mentor`, `SupervisionLog`, `Team` |
| **Entities** | `MentorRole` (role-based capability checks — verify attendance, logbook, grade, etc.); `SupervisionStatus` (completed/active checks) |
| **Enums** | `SupervisionLogStatus` — `PENDING`, `IN_PROGRESS`, `SUBMITTED`, `VERIFIED`, `COMPLETED`, `CANCELLED`; `SupervisionType` — `GUIDANCE`, `SUPERVISORING`, `MONITORING` |
| **Livewire** | `MentorProfileManager`, `AssessInternship`, `EvaluateMentor`, `ReportNotes`, `ReportReview` — *(dashboards moved to `User/Livewire/Dashboards/`)* |
| **Policy** | `SupervisionLogPolicy` |

## Dependencies

| Dependency | Reason |
|---|---|
| Registration | Mentor-student assignments are linked through registration records — no 
registration means no mentorship relationship |
| User | Mentor and student identity for assignments, display, dashboard, and communication |
| Core | BaseAction, BaseModel, SmartLogger, BaseRecordManager |


- Each student must have exactly one primary mentor assigned at all times during an active 
internship — zero primary mentor is not a valid state.
- Mentor assignments are strictly time-bound to the internship period; assignments auto-expire 
when the period ends (data preserved, access revoked).
- Supervision logs are strictly confidential to the creating mentor and administrators — never 
visible to the student under any circumstances.
- A mentor cannot be assigned as mentor to themselves — this is enforced at the assignment 
creation level.
- Mentor handover requires the new mentor's explicit acceptance; the outgoing mentor cannot 
unilaterally transfer students without confirmation.
- Primary mentors have full data access across all domains for their assigned students; 
co-mentors have a configurable access scope set at assignment time.
- Mentors can view student data across other domains (attendance, logbook, evaluations, 
assignments) in read-only mode — all writes happen through the respective domain's interfaces.
- A mentor cannot delete their supervision logs — logs can only be archived (hidden from active 
view) but are preserved for audit.
- All Livewire components return `: View` for type safety.
