# Known Issues & Limitations

> **Last updated:** 2026-06-16
> **Changes:** fix AA-4 (AutoCalculateAssessmentAction — add attendance, supervision, monitoring visit data); fix AA-12 (hardcoded strings → __() in 6 views + new lang files)

---

## Open Issues

### EM-2 — HIGH: 20+ Test Failures in Enrollment & Program

Root cause (`RegistrationState` Carbon typing) **fixed**. Remaining: unit tests missing `LazilyRefreshDatabase`, `DeleteInternshipAction` logic, test data fixtures.

### J-2 — HIGH: 29 Test Failures in Journals

Same root cause — `RegistrationState` Carbon typing **fixed**.

### AA-2 — HIGH: 20 Test Failures in Assignment & Assessment

Same root cause — `RegistrationState` Carbon typing **fixed**.

### EM-6 — MEDIUM: Missing Event Dispatch (16 Actions)

Enrollment and Program Command Actions perform state changes but dispatch no events.

### EM-11 — MEDIUM: Missing Test Coverage (Enrollment/Program)

Livewire components, Forms, and Policies in Enrollment/Program untested.

### J-5 — MEDIUM: Missing Event Dispatch (12 Actions)

All Journals Command Actions dispatch no events.

### J-9 — MEDIUM: Missing Test Coverage (Journals)

`LogbookManager`, `LogbookEntry`, `AttendanceManager`, `StudentClockIn`, `AbsenceRequestForm` untested.

### AA-5 — MEDIUM: Missing Event Dispatch (19 Actions)

All Assignment and Assessment actions dispatch no events.

---

## Registration Link on Home Page

Confirmed: registration/apply link available at `/`.
