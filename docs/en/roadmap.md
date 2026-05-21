# Roadmap

## Current State

### Source Code
- **465 PHP files** across **24 domains**
- **151 Actions**, **87 Livewire components**, **50 Models**, **27 Enums**, **25 Entities**, **18 Policies**, **7 States**
- **Total**: ~3,700 lines of domain docs, ~2,700 lines of ERD docs, **16 ADRs** (1,100 lines)

### Test Suite
- **462 tests** (760 assertions)
- **62 unit** tests — good coverage of entities and enums
- **8 feature** tests — critically low
- **10 arch** tests — enforce structural rules

---

## Priority 0: Critical Gaps

### P0.1 Feature Tests (147 uncovered Actions)

Only 4 of 151 Actions have feature tests.

| Domain | Actions | Feature Tests | Gap |
|---|---|---|---|
| Assessment | 17 | 0 | 🔴 |
| Internship | 16 | 0 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Admin | 12 | 2 | 🟡 |
| Attendance | 8 | 0 | 🔴 |
| Partnership | 8 | 0 | 🔴 |
| Mentor | 8 | 0 | 🔴 |
| Placement | 7 | 0 | 🔴 |
| Assignment | 7 | 0 | 🔴 |
| School | 9 | 0 | 🔴 |
| Registration | 5 | 0 | 🔴 |
| Document | 4 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Incident | 3 | 0 | 🔴 |
| Mentee | 3 | 0 | 🔴 |
| Schedule | 3 | 0 | 🔴 |
| Guidance | 2 | 0 | 🔴 |
| Evaluation | 2 | 1 | 🟡 |
| User | 2 | 2 | 🟢 |

### P0.2 Missing Policies (remaining gaps)

| Domain | Models | Status |
|---|---|---|
| Placement (PlacementChangeRequest) | 1 | 🟡 Uncovered |
| Admin (GdprDeletionLog) | 1 | 🟡 Cross-domain |
| Partnership (Partnership model) | 1 | 🟡 Cross-domain |
| User (Profile model) | 1 | 🟡 Cross-domain |

---

## Priority 1: Documentation

### P1.1 Complete Domain Documentation (backlog)
- Add cross-domain event flow documentation (which events fire, which listeners react)
- Ensure database.md delivers on its promised "full table reference" section

---

## Priority 2: Code Quality

### P2.1 Factory Discovery
23 models use `HasFactory` without an explicit `newFactory()` method.

### P2.2 Architecture Test Maintenance
10 arch tests exist. Needed additions:
- Event layer architecture tests
- Notification layer architecture tests
- Cross-domain import rules

---

## Priority 3: Future Enhancements

### P3.1 Media Library Integration
Formalize media collection definitions per model.

### P3.2 Real-Time Features
Laravel Echo and Reverb are installed. Candidates:
- Real-time notification delivery
- Live dashboard updates for mentors
- Attendance clock-in confirmations

### P3.3 Queue Job Formalization
Evaluate which operations should be queued (certificate generation, report rendering, batch notifications).

---

## Infrastructure Layer (Layer 1)

### I1.4 Define Reverb broadcasting channels 🟢

`routes/channels.php` exists with zero channel definitions.

---

## Summary

| Priority | Issue | Severity |
|---|---|---|
| P0.1 | 147 Actions without feature tests | 🔴 |
| P0.2 | 4 uncovered models without policies | 🟡 |
| P1.1 | Documentation backlog | 🟡 |
| P2.1 | 23 models missing explicit factory | 🟢 |
| P2.2 | Arch test coverage gaps | 🟢 |
| P3.1 | Media Library formalization | 🟢 |
| P3.2 | Real-time features | 🟢 |
| P3.3 | Queue job formalization | 🟢 |
| I1.4 | Define Reverb broadcasting channels | 🟢 |
