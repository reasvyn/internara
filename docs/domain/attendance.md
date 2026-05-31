# Attendance Domain

## Purpose

Attendance tracks student presence during placement — clock-in/out, absence requests, and
compliance monitoring.

---

## Design Principles

### 1. Immutable Records

Attendance records are immutable after configurable window (default 24h). Corrections
require admin override with audit trail.

### 2. Dual Verification

Attendance is verified by both school mentor and company supervisor before finalization.

---

## Domain Boundary

The Attendance domain owns presence tracking during the placement program — the complete record of when students were present, when they were absent, and how their attendance rate measures against program requirements. It manages clock-in and clock-out timestamps with optional GPS location data for verification of on-site presence. It handles absence requests where students submit planned or unplanned absences with reasons and optional supporting documents. Absence approvals follow a two-tier model: single-day absences can be approved by the mentor, while extended absences require additional administrative approval. The domain continuously monitors attendance compliance and automatically notifies mentors when a student's attendance drops below a configurable threshold.

Attendance does not own student identity data (User/Mentee), schedule event definitions (Schedule), program requirements (Internship), or mentor assignment records (Mentor). It tracks presence and absence but does not manage what students should be attending — that is defined by program schedules and program requirements.

The domain depends on User for student identity, on Mentee for the student-program link, and on Mentor for the supervisor who approves absences. Attendance records reference these domains but own only the presence data itself.

---

## Key Features

- Record clock-in timestamps with optional GPS location data when students arrive at their placement site.
- Record clock-out timestamps that automatically calculate the total duration of attendance.
- Submit planned or unplanned absence requests with a reason and optional supporting documentation.
- Approve single-day absences by mentors, with extended absences requiring additional administrative approval.
- Create, filter, sort, and generate reports on attendance records through the administrative interface.
- Automatically notify mentors when a student's attendance rate falls below the configurable compliance threshold.
- Lock attendance records as immutable after a configurable time window, requiring admin override for corrections.
- Apply a digital signature via canvas or uploaded signature image to formally verify attendance records.
- Click a prominent clock-in button from the dashboard and confirm with an optional GPS location capture.
- Click a clock-out button that displays the total hours worked for the session before confirming.
- View a color-coded calendar showing each day as present, absent, late, or excused with a legend.
- View a compliance progress bar showing the percentage of required attendance days met for the program.
- Submit an absence request with a date picker, reason text area, and optional document upload via drag and drop.
- Verify on-site presence through geo-fencing that restricts clock-in to within a defined radius of the placement location.
