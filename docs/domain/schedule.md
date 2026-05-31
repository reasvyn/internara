# Schedule Domain

## Purpose

Schedule manages time-based entries — deadlines, site visits, presentations, and other
program events — as a simple record collection.

---

## Design Principles

### 1. Entry Status

Each schedule entry has a computed status based on its time range: upcoming, ongoing,
or past. Status is derived from the current time relative to the entry's start and end
dates, not stored as a fixed state.

### 2. Authoritative Time Source

The server clock is the single source of truth for determining past, present, and future
entries. Client-side time zone detection may adjust the display but never changes the
canonical time boundaries.

---

## Domain Boundary

The Schedule domain owns schedule entries — the creation, management, and listing of
time-bound records. Each entry has a title, description, start and end times, and an
optional program association. Entries are managed through a paginated listing interface
with basic create, read, update, and delete operations.

Schedule does not own attendance records (Attendance), program definitions (Internship),
student identity data (User), or any operational domain data. Schedule entries may
reference a program for contextual association, but the domain does not manage program
details or lifecycle. It does not own calendar rendering, recurrence engines, reminders,
conflict detection, or visual calendar views — those are aspirational features not yet
implemented.

The domain depends on Internship for program context and on User for participant identity.
It provides the temporal records that other domains may reference, but entries are
self-contained within the Schedule domain.

---

## Key Features

- Create, update, and delete schedule entries with title, description, and time range.
- View a paginated list of schedule entries sorted by date.
- Browse past, ongoing, and upcoming entries with computed status labels.
- Filter entries by program association using a dropdown selector.
