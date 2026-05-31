# Known Issues and Gotchas
> Last updated: 2026-06-01
> Changes: docs: comprehensive documentation vs implementation audit — add new findings, remove resolved


## Implementation — Critical

### InternshipPlacementPolicy Referenced but File Missing 🔴

**File:** `app/Providers/DomainServiceProvider.php:14,57`

`DomainServiceProvider` imports `App\Domain\Placement\Policies\InternshipPlacementPolicy` and registers it via `Gate::policy(Placement::class, ...)`. The file does not exist — only `PlacementPolicy.php` and `PlacementChangeRequestPolicy.php` exist in that directory. This causes a fatal `ClassNotFoundError` on every request.

**Fix:** Either create the file at `app/Domain/Placement/Policies/InternshipPlacementPolicy.php` or remove the import and registration from `DomainServiceProvider`.

### Duplicate CompanyPolicy in Two Domains 🔴

**Files:** `app/Domain/Internship/Policies/CompanyPolicy.php` and `app/Domain/Partnership/Policies/CompanyPolicy.php`

Two CompanyPolicy implementations exist with different logic. The Internship version (46 lines, with placements-exists guard on delete) overrides the Partnership version (37 lines, simpler) via manual `Gate::policy()` in `DomainServiceProvider:61`. Auto-discovery maps `Partnership\Policies\CompanyPolicy` → `Partnership\Models\Company`, but the manual registration replaces it.

**Fix:** Consolidate into `Partnership/Policies/CompanyPolicy.php`. Update `DomainServiceProvider` to either let auto-discovery handle it or point to the Partnership version.

### InternshipRegistrationPolicy in Wrong Domain 🔴

**File:** `app/Domain/Internship/Policies/InternshipRegistrationPolicy.php`

This policy gates `Registration\Models\Registration` but lives in the Internship domain. Registered manually in `DomainServiceProvider:60`. The Registration domain already has its own `RegistrationPolicy.php` but the Internship version overrides it.

**Fix:** Move to `Registration/Policies/InternshipRegistrationPolicy.php` and update `DomainServiceProvider`.

### 564 Files Without declare(strict_types=1) 🔴

Convention requires `declare(strict_types=1)` in every PHP file. Across the entire `app/Domain/` directory, 564 files are missing this declaration. Domains most affected: Auth, Assessment, Admin, Internship, Placement, Registration, and several others.

**Fix:** Batch-add `declare(strict_types=1)` to all affected files. This is a mechanical change that should be done with a script.

### 2 Entities Importing Models 🔴

| Entity | Imports | Violation |
|--------|---------|-----------|
| `Auth/Entities/SuperAdminIntegrityRules.php` | `User\Models\User` | Entity imports Model + cross-domain import |
| `Mentee/Entities/MenteeState.php` | `Registration\Models\Registration` | Entity imports Model + cross-domain import |

Convention requires entities to have zero framework dependencies and never import Models.

**Fix:** Replace Model references with primitives or DTOs in entity constructors. Use `fromModel()` to bridge persistence.

---

## Documentation — Missing Domain Features

### Site Visit Scheduling & Logging 🔴

Teachers must conduct site visits to placement locations. Mentor domain has supervision logs but they are private notes, not formal visit records with scheduling, travel planning, or visit result logging.

**Suggested domain:** Mentor.
**Priority:** High — regulatory requirement for Indonesian vocational education.

### Geo-fencing for Attendance Verification 🟠

Current GPS coordinate capture provides location but not verification. Geo-fencing would define allowed radius around placement locations and reject clock-ins outside the zone.

**Suggested domain:** Attendance.
**Priority:** Medium — strengthens regulatory compliance.

### Placement Agreement Generation 🟠

Schools need auto-generated administrative letters from placement data: surat tugas, MoU summaries, placement confirmation letters.

**Suggested domain:** Document (templates) + Placement (data source).
**Priority:** Medium — reduces administrative burden.

### 360-Degree Evaluation 🟡

Current evaluation is student→mentor and student→company only. An ideal system includes self-evaluation, mentor→student assessment, and peer feedback.

**Suggested domain:** Evaluation.
**Priority:** Low — future enhancement.

### Offline Mode Design 🟡

Product definition states offline-capable but no domain doc designs how Attendance and Logbook work without connectivity.

**Suggested domain:** Attendance, Logbook.
**Priority:** Low — important for schools with unreliable internet.

### Bulk Communication to Segmented Groups 🟡

Announcements target by role only. Admins need to message "all students at Company X" or "all mentors for Program Y".

**Suggested domain:** Admin.
**Priority:** Low — future enhancement.

### Digital Signature / E-Sign 🟡

Mentor verification relies on click-to-verify. Electronic signature would strengthen legal validity of logbook and attendance records.

**Suggested domain:** Logbook, Attendance.
**Priority:** Low — future enhancement.

---

## Documentation — Domain Boundary Gaps

### Mentor: Mixed Responsibilities 🟠

Mentor domain mixes two distinct responsibilities: supervision logs (private mentor notes) and grading (assignment assessment). These serve different workflows with different authorization rules.

### Evaluation: Limited Perspective Coverage 🟡

Evaluation collects only student→mentor and student→company feedback. Missing: mentor→student performance, self-evaluation, and program quality evaluation.

---

## Documentation — UX Feature Gaps

| UX Feature | Domain | Priority |
|------------|--------|----------|
| Mobile-responsive clock-in button (large touch target) | Attendance | 🟠 |
| Offline queue with background sync | Attendance, Logbook | 🟡 |
| Canvas-based digital signature pad | Logbook, Attendance | 🟡 |
| Placement location map view | Placement, Mentor | 🟡 |
| Progress ring/badge widget on dashboards | Mentee, User | 🟡 |

---

## Infrastructure

### K5. SQLite for Production — No Concurrent Write Support 🔴

SQLite uses file-level locking. Under concurrent load, "database is locked" errors occur. Use MySQL 8+ or PostgreSQL 15+ in production. Status: documented guidance.

### H6. Duplicate Livewire Instances ⏳

ThemeSwitcher and LangSwitcher are mounted in both sidebar and navbar. Resolved by CSS media queries (desktop shows navbar, mobile shows sidebar) but still two component instances per page. Status: monitored, not critical.

---

## Backlog

### Feature Test Coverage

| Domain | Actions | Feature Tests | Gap |
|--------|---------|---------------|-----|
| Assessment | 17 | 0 | 🔴 |
| Internship | 21 | 7 | 🔴 |
| Auth | 12 | 0 | 🔴 |
| Attendance | 8 | 0 | 🔴 |
| Mentor | 8 | 0 | 🔴 |
| Assignment | 7 | 0 | 🔴 |
| School | 9 | 0 | 🔴 |
| Document | 4 | 0 | 🔴 |
| Logbook | 4 | 0 | 🔴 |
| Certificate | 4 | 0 | 🔴 |
| Incident | 3 | 0 | 🔴 |
| Mentee | 3 | 0 | 🔴 |
| Schedule | 3 | 0 | 🔴 |
| Registration | 6 | 2 | 🟡 |
| Evaluation | 3 | 1 | 🟡 |
| Admin | 14 | 9 | 🟢 |
| Guidance | 2 | 2 | 🟢 ✅ |
| Partnership | 8 | 8 | 🟢 ✅ |
| Placement | 7 | 7 | 🟢 ✅ |
| Setup | 9 | 9 | 🟢 ✅ |
| Settings | 6 | 6 | 🟢 ✅ |
| User | 8 | 5 | 🟢 |

### GD8. Acknowledgement Not Used as Gate ⏳

Handbook acknowledgement is purely informational — no action is blocked.

### Livewire Form Object Migration ⏳

~45 components still manage form state via flat public properties. Completed: Setup, Auth, Profile, Settings, Internship, Guidance, Registration, Placement.

### Cross-Domain Event Flow Undocumented ⏳

Which events fire and which listeners react is not documented.

### Real-Time Features (Future) ⏳

Laravel Echo and Reverb installed but no real-time channels active.

### BaseAction Cannot Enforce execute() Signature ⏳

No abstract execute() method on BaseAction. Each Action defines its own signature.

---

## Summary

| # | Issue | Category | Severity | Status |
|---|-------|----------|----------|--------|
| I1 | InternshipPlacementPolicy file missing — fatal error | Implementation | 🔴 Critical | Open |
| I2 | Duplicate CompanyPolicy (Internship + Partnership) | Implementation | 🔴 Critical | Open |
| I3 | InternshipRegistrationPolicy in wrong domain | Implementation | 🔴 Critical | Open |
| I4 | 564 files without `declare(strict_types=1)` | Implementation | 🔴 Critical | Open |
| I5 | 2 Entities importing Models | Implementation | 🔴 Critical | Open |
| D1 | Site visit scheduling & logging | Documentation | 🔴 High | Open |
| D2 | Geo-fencing for attendance verification | Documentation | 🟠 Medium | Open |
| D3 | Placement agreement generation | Documentation | 🟠 Medium | Open |
| D4 | Mentor: mixed responsibilities boundary | Documentation | 🟠 Medium | Open |
| D5 | Mobile-responsive clock-in button | Documentation | 🟠 Medium | Open |
| D6 | 360-degree evaluation | Documentation | 🟡 Low | Open |
| D7 | Offline mode design | Documentation | 🟡 Low | Open |
| D8 | Bulk communication to segmented groups | Documentation | 🟡 Low | Open |
| D9 | Digital signature / e-sign | Documentation | 🟡 Low | Open |
| D10 | Evaluation: limited perspective coverage | Documentation | 🟡 Low | Open |
| D11 | Offline queue with background sync | Documentation | 🟡 Low | Open |
| D12 | Canvas-based digital signature pad | Documentation | 🟡 Low | Open |
| D13 | Placement location map view | Documentation | 🟡 Low | Open |
| D14 | Progress ring/badge on dashboards | Documentation | 🟡 Low | Open |
| B1 | Feature test coverage — 68 Actions uncovered | Backlog | 🔴 High | Open |
| B2 | GD8 — Acknowledgement not used as gate | Backlog | 🟠 Medium | Open |
| B3 | Livewire Form Object migration (~45 components) | Backlog | 🟡 Low | Open |
| B4 | Cross-domain event flow undocumented | Backlog | 🟡 Low | Open |
| B5 | Real-time features (Echo + Reverb) | Backlog | 🟡 Low | Open |
| B6 | BaseAction cannot enforce execute() signature | Backlog | 🟡 Low | Open |
| I6 | SQLite for production — no concurrent writes | Infrastructure | ⏳ Known | Guidance |
| I7 | Duplicate Livewire instances (theme/lang switcher) | Infrastructure | ⏳ Known | Monitored |

**Categories:** I = Implementation, D = Documentation, B = Backlog  
**Severity:** 🔴 Critical/High = must fix, 🟠 Medium = should fix, 🟡 Low = nice to have, ⏳ = known/acknowledged
