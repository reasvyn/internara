# Schedule Domain

## Purpose

Schedule handles the time dimension of internships — it answers "when do things happen?" This 
includes briefing dates, assessment periods, supervision sessions, company visits, deadlines of 
various types, and any other time-boxed events that give structure to an internship timeline. 
Unlike domain-specific deadlines (assignment due dates live in Assignment, attendance session 
times live in Attendance), Schedule manages the shared calendaring that multiple domains 
reference: briefing dates affect Attendance (mandatory briefings must be attended), assessment 
dates affect Assessment (evaluation events happen on specific dates), supervision sessions affect 
Mentor (mentor availability and session scheduling), and deadline markers affect everything. 
Schedule provides a unified calendar so that everyone — students, mentors, admins — can see 
what is happening and when.

## Boundary

**In scope:** Event definitions (title, description, start and end times with timezone, location 
or virtual link, category, program association), recurring event support (daily, weekly, 
biweekly, monthly, custom patterns with end conditions), event categorization (briefing, 
assessment, supervision, visit, deadline, holiday, general), event visibility rules (who sees 
which events based on role, program enrollment, and involvement), calendar views (day, week, 
month, agenda — tailored to each role's needs), event reminders and notifications (configurable 
timing per category, multiple reminders per event, in-app and email delivery), event conflict 
detection (overlapping events for same participant, flagged but not prevented).

**Out of scope:** Attendance recording against events (Attendance domain applies attendance 
status — did the student show up? — Schedule only knows the event exists), briefing content 
and materials management (Internship domain defines what briefings cover and what materials are 
needed), assessment period configuration and scoring (Assessment domain owns evaluation period 
setup and scoring rubrics), task due dates (Assignment domain owns task-specific deadlines), 
program date ranges (Internship domain defines when the program starts and ends — events must 
fall within those bounds).

## Key Concepts

**Events.** An event is any scheduled occurrence with a defined time, place, and purpose. Each 
event record includes: a title and description (what the event is about), start and end times 
with timezone support, a location (physical address, room name, or virtual meeting link), an 
event category (determines display, visibility defaults, and notification behavior), a program 
association (all events belong to a specific internship program), and an optional recurrence 
rule. Events can be single-occurrence (happens once and done) or recurring (happens on a 
pattern). Recurrence rules support: daily, weekly on specific days, biweekly, monthly on a 
specific date or weekday, and custom patterns. Each recurrence rule must have an end condition 
— either an end date or a maximum occurrence count. Recurring events generate individual 
instances that can be modified independently: you can cancel one instance of a recurring briefing 
without affecting the rest of the series.

**Event Categories.** Events are classified into categories that drive consistent behavior. 
BRIEFING: a mandatory or optional informational session — appears on attendance tracking views, 
visible to all relevant students and mentors. ASSESSMENT: an evaluation event — visible to the 
student being assessed and the assessment panel. SUPERVISION: a mentor-student meeting — 
visible only to the mentor and the specific student(s) involved, supports conflict detection. 
VISIT: a company site visit — visible to the student, mentor, and company contact. DEADLINE: a 
submission cutoff or milestone — displayed as a marker on the calendar rather than a time 
block, no attendance expected. HOLIDAY: a scheduled non-working day — no attendance expected, 
clock-in blocked or not required. GENERAL: a catch-all for uncategorized events — visible to 
program participants. Each category has configurable defaults for visibility scope, reminder 
timing, and whether attendance is tracked.

**Calendar Views.** The schedule provides role-appropriate calendar views through a Livewire 
component. Students see: mandatory events for their program (briefings, assessments), their 
personal supervision sessions, deadlines, and holidays. Mentors see: events for all their 
assigned students (supervision sessions, assessments they participate in), plus program-level 
events they need to attend (mentor meetings, briefings they are presenting), plus their own 
availability windows. Admins see: all events across all programs, with powerful filtering by 
program, category, date range, and mentor. Each view supports four display modes: DAY (detailed 
hourly view), WEEK (five-day or seven-day view), MONTH (overview grid), and AGENDA (scrollable 
list of upcoming events sorted by date). Views are responsive and optimized for both desktop and 
mobile use.

**Reminders and Notifications.** Events can have configured reminders sent before the event start 
time. Reminder timing is set per event category (e.g., briefings remind 1 day before and 1 hour 
before; supervision sessions remind 2 hours before; deadlines remind 3 days, 1 day, and 1 hour 
before). Multiple reminders per event are supported with independent timing. Reminders are 
delivered through in-app notifications (visible in the notification center) and email (sent to 
the participant's email address). Reminder delivery is best-effort — if an event is created 
less than the reminder lead time before its start, the system may not send that reminder (it will 
send any that still have time before the event). Participants can also view all upcoming events 
without relying on reminders.

**Conflict Detection.** When an event is created or a participant is added to an event, the 
system checks for time overlaps with the participant's existing events. An overlap is defined as: 
two events whose time ranges intersect (start A < end B AND start B < end A). When an overlap is 
detected, the system presents a warning to the event creator showing the conflicting event(s) 
with their titles, times, and participants. The conflict is flagged but does not prevent the 
event from being saved — the event creator decides whether to proceed despite the conflict. 
Conflict information is displayed in all calendar views so that participants can identify and 
manage their own scheduling issues.

## Requirements

### User Stories & Rules

- **Admin:** As an admin, I want to create events for my program so that students and mentors know what is happening
- **Admin:** As an admin, I want to create recurring events so that regular sessions are managed efficiently
- **Admin:** As an admin, I want to categorize events so that display and visibility are consistent
- **Student:** As a student, I want to view my program's calendar so that I can plan my schedule
- **Mentor:** As a mentor, I want to see my supervision sessions and mentees' events so that I can manage my time
- **Admin:** As an admin, I want event conflict detection so that scheduling problems are surfaced
- Events must have start and end times that fall within their parent internship program's date 
range — no events outside the program period are permitted.
- Recurring events must have an explicit end condition (end date or occurrence count) — 
indefinite recurrence with no end is not permitted.
- Past events (those whose end time has passed) are immutable — their details cannot be 
modified. Corrections require canceling the past event and creating a new one.
- Event cancellation preserves the original event record with a CANCELLED status and an optional 
cancellation reason — the record is never deleted.
- Every event must belong to exactly one internship program — orphan events without a program 
are not allowed to be created.
- Conflicting events (same participant, overlapping time ranges) are flagged with a warning but 
are not prevented from being saved.
- Event visibility is role-scoped: students see student-relevant events, mentors see their 
mentees' events plus program events, admins see everything with filtering.
- Reminder delivery is best-effort; events created with less than the reminder lead time 
remaining may not produce that reminder.
- Events can be moved (rescheduled) only within the parent program's date range — moving an 
event outside the range is not permitted.

### Key Operations

| Action | Description |
|--------|-------------|
| `CreateScheduleAction` | Creates a new event (single or recurring) |
| `UpdateScheduleAction` | Updates an existing event |
| `DeleteScheduleAction` | Deletes or cancels an event |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `Schedule` (event with title, times, location, category, recurrence) |
| **Entity** | `ScheduleStatus` (ongoing/upcoming checks) |
| **Livewire** | `ScheduleIndex` |
| **Policy** | `SchedulePolicy` |

## Dependencies

| Dependency | Reason |
|---|---|
| Internship | Programs define the calendar context — events must have a program parent and 
dates must fall within the program's date range |
| Core | BaseAction, BaseModel, SmartLogger |


