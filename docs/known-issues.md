# Known Issues & Limitations

> **Last updated:** 2026-06-18
> **Changes:** add CACHE-1, LW-1, TEST-1, TEST-2 from main module audit

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

## Fixed Issues (This Session)

| ID | Severity | Description |
|----|----------|-------------|
| **AA-13** | LOW | `CompileLogbookReportAction` now extends `BaseReadAction` |
| **AA-14** | LOW | `DetectUserAccountCloneAction` now extends `BaseReadAction` |
| **CACHE-1** | MEDIUM | `school_entity` key registered in `config/cache-keys.php` |
| **LW-1** | MEDIUM | 3 Livewire components now catch `RejectedException` correctly |
| **TEST-1** | HIGH | 12 missing Action test files created |
| **TEST-2** | MEDIUM | 18 missing Entity unit test files created |

---

## Registration Link on Home Page

Confirmed: registration/apply link available at `/`.
