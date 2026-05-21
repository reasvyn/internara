# Mentee Domain

## Purpose

Mentee is the student's lens into the internship system. While the User domain handles identity 
(who the person is) and the Registration domain handles enrollment (how they joined the program), 
Mentee provides the student's day-to-day experience: their personalized dashboard, visibility 
into who their mentor is and how to contact them, aggregated progress tracking against program 
requirements, and self-service entry points to all internship tools. This is a thin domain — it 
does not own substantial data of its own. Instead, it aggregates data owned by other domains 
(Assignments, Logbook, Attendance, Evaluations, Briefings) and presents it from the student's 
perspective. The domain exists as a conceptual boundary because the student experience is 
distinct enough from identity management and registration workflows to warrant its own home.

## Boundary

**In scope:** Mentee enrollment (links a user with student role to their active internship 
registration), student dashboard (aggregated view of assignments, logbook entries, attendance 
records, evaluations, briefings, announcements, deadlines), mentor visibility (who is assigned as 
primary and co-mentors, contact information, supervision schedule), progress tracking (completed 
vs. pending requirements aggregated in real-time from all source domains), self-service access 
routing to internship tools (logbook, submissions, attendance clock-in, evaluations, guidance 
documents), mentee-specific profile extensions beyond the User Profile (optional, thin).

**Out of scope:** Mentor-side supervision and management tools (Mentor domain provides the 
mentor's dashboard and student oversight), user identity data and base profile (User domain owns 
User and Profile models), registration and application workflows (Registration domain), task 
creation and grading (Assignment domain), rubric and assessment definition (Assessment domain), 
attendance clock-in recording (Attendance domain — the clock-in UI is in Attendance, but the 
mentee dashboard links to it), logbook entry writing (Logbook domain — the editor is in 
Logbook, but the mentee dashboard links to it).

## Key Concepts

**Mentee Enrollment.** A mentee enrollment is the link between a user (with student role) and 
their internship context. It connects the user's identity to their active registration, their 
mentor assignments, and their program participation. The enrollment is not a separate stored 
record in most cases — it is a derived relationship: a user is a mentee when they have the 
student role AND an active, approved registration. The enrollment determines what the student 
sees: which program they belong to, which dashboard elements are relevant, which tools are 
available, which deadlines apply. A student may have multiple historical enrollments (completed 
past internships) but only one active enrollment at any time. Historical enrollments are 
accessible for portfolio review but do not grant access to current internship tools.

**Student Dashboard.** The dashboard is the student's home page and the primary entry point to 
all internship activities. It is a read-only aggregation layer — all data lives in its source 
domains but is collected and presented here for convenience. Typical dashboard sections include: 
CURRENT STATUS (brief summary — "You are in week 5 of 12"), UPCOMING DEADLINES (assignment due 
dates, briefing dates, assessment dates, report deadlines — pulled from Assignment and Schedule 
domains), PENDING ACTIONS (unsubmitted assignments, unacknowledged logbook entries, incomplete 
guidance acknowledgements, missing attendance clock-ins — actionable items that need the 
student's attention), RECENT ACTIVITY (latest logbook entry, most recent grade, last evaluation 
received — a snapshot of recent events), ATTENDANCE SUMMARY (this week's clock-in status, 
running attendance percentage, absence request status — pulled from Attendance domain), MENTOR 
INFO (primary mentor name, contact, next supervision session — pulled from Mentor and Schedule 
domains), and QUICK ACTIONS (buttons linking to logbook entry creation, attendance clock-in, 
assignment submission, and profile editing).

**Mentor Visibility.** Students can see who their assigned mentors are. The display shows: 
primary mentor (name, title, email, phone, photo), co-mentors (if any, same information), and the 
supervision schedule (recurring session times and upcoming one-off meetings — pulled from 
Schedule domain). This information is read-only from the student's perspective — mentor 
assignment management belongs to the Mentor and Admin domains. Having clear mentor visibility 
reduces the student's uncertainty about who to contact for what purpose.

**Progress Tracking.** Students can view their progress toward program completion at any time. 
The progress view shows requirements defined by the Internship domain with completion status 
computed in real-time from the source domains: assignments completed vs. total, attendance 
percentage vs. minimum, logbook entries submitted vs. expected, briefings attended vs. required, 
evaluations received vs. scheduled, guidance documents acknowledged vs. assigned, and total hours 
logged vs. minimum. Each requirement shows a status indicator (completed, in-progress, not 
started, behind), a progress bar or percentage, and the specific threshold. This real-time 
visibility helps students self-manage and prioritize areas that need attention. The data is never 
cached stale — it queries the source domains on every dashboard load.

**Self-Service Access.** The mentee dashboard serves as a navigation hub. Every section provides 
direct links to the relevant domain's tools: "Write Logbook" opens the logbook entry editor 
(Logbook domain), "Clock In" opens the attendance clock-in interface (Attendance domain), "My 
Assignments" opens the task and submission view (Assignment domain), "My Evaluations" opens the 
evaluation review page (Evaluation domain), "Required Documents" opens the guidance 
acknowledgement page (Guidance domain), "My Schedule" opens the calendar view (Schedule domain), 
and "Edit Profile" opens the profile editor (User domain). The dashboard does not duplicate these 
tools — it only provides entry points.

## Requirements

### User Stories

| Role | Story |
|------|-------|
| Student | As a student, I want to see a personalized dashboard so that I have an overview of my internship status |
| Student | As a student, I want to see upcoming deadlines and pending actions so that I know what needs my attention |
| Student | As a student, I want to view my mentor's contact information so that I know who to reach out to |
| Student | As a student, I want to track my progress against program requirements so that I can self-manage |
| Student | As a student, I want quick links to all internship tools (logbook, clock-in, assignments) so that I navigate efficiently |

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateMenteeAction` | Creates a mentee enrollment record |
| `UpdateMenteeAction` | Updates a mentee's profile or status |
| `DeleteMenteeAction` | Removes a mentee enrollment |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Mentee` (enrollment link between user and internship) |
| **Entity** | `MenteeState` (active registration checks, clock-in/logbook/assignment gating, duration calculations) |
| **Livewire** | `StudentDashboard` |

## Dependencies

| Dependency | Reason |
|---|---|
| User | User model and Profile are the foundation of the mentee identity; the mentee is a user 
with a student role |
| Registration | Mentee enrollment is derived from the active registration record; no 
registration means no mentee state |
| Core | BaseAction, BaseModel, SmartLogger |

## Important Rules

- A user is a mentee only when they have the student role AND an active registration — this is 
a derived state, not a separately stored attribute.
- The student dashboard aggregates data from source domains in real-time — no stale cached data 
is displayed to the student.
- Mentor information is displayed read-only; mentorship management belongs to the Mentor and 
Admin domains exclusively.
- Students without an active registration cannot access the mentee dashboard or any internship 
tools.
- Mentee-specific data extensions (beyond the User Profile) are optional and shallow; the User 
Profile is the canonical personal data store.
- The dashboard aggregation queries must be optimized for performance — students should see 
their dashboard in under one second despite querying multiple domains.
- Historical mentee enrollments (past internships) provide portfolio access but do not confer any 
current internship tool access.
- All Livewire components return `: View` for type safety.
