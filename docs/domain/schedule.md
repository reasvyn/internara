# Schedule Domain

## Purpose

Schedule manages calendar events — deadlines, site visits, presentations, and other
program events.

---

## Design Principles

### 1. Recurrence Without Duplication

Recurring events maintain a single definition with a recurrence rule — they are not stored
as multiple individual records. The recurrence engine expands the rule into visible
occurrences within the requested date range.

### 2. Conflict Awareness

Overlapping events generate warnings but do not block creation. The system detects time
conflicts and surfaces them to the user, who decides how to resolve them. Blocking
scheduling creates more problems than it solves in educational contexts.

### 3. Past Immutability

Events in the past are locked. Corrections require cancellation and recreation — the
original event remains in the record as cancelled, and a new event is created. This
preserves the audit trail of what was originally scheduled.

---

## Domain Boundary

The Schedule domain owns the event calendar — the creation, management, and display of all time-bound activities within the system. It handles individual events with titles, descriptions, start and end times, locations, categories, and program associations. It supports recurring events with daily, weekly, biweekly, and monthly repetition patterns, each with a configurable end condition. Calendar views are available in day, week, month, and agenda formats accessible to students, mentors, and administrators. The system provides configurable in-app and email reminders for upcoming events and detects scheduling conflicts with warnings.

Schedule does not own attendance records (Attendance), program definitions (Internship), student identity data (User), or any operational domain data. Schedule events may reference a program for contextual association, but the domain does not manage program details or lifecycle. It does not own the entities that attend scheduled events — only the event definitions themselves.

The domain depends on Internship for program context when events are program-specific, and on User for determining which events are visible to which users. It provides the temporal framework that other domains may reference, but events are self-contained within the Schedule domain.

---

## Key Features

- Create, update, and delete calendar events with title, description, time range, location, category, and program association.
- Define recurring events with daily, weekly, biweekly, or monthly repetition and a configurable end condition.
- Display events in day, week, month, and agenda calendar views for students, mentors, and administrators.
- Send configurable in-app and email reminders to participants ahead of upcoming events.
- Detect overlapping or conflicting events and display a warning when scheduling conflicts arise.
- Lock past events as immutable, requiring cancellation and recreation instead of direct edits for corrections.
- Browse a visual calendar with day, week, month, and agenda views toggled by a view switcher.
- Create events by clicking on a calendar time slot, opening a pop-up form pre-filled with the selected date and time.
- View color-coded events by category on the calendar for quick visual identification.
- Receive a pop-up reminder notification before an upcoming event with a configurable lead time.
- Filter calendar events by program or category using dropdown selectors above the calendar.
