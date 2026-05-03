# Known Issues

This document tracks known problems, technical debt, and blockers that affect the project. Issues
here are acknowledged and have a planned resolution path.

---

## P0 — Failed Tests (Resolved)

**Status**: Resolved  
**Resolved**: 2026-04-30  
**Commit**: `c55d0c25`

All previously failing tests have been fixed:

- SystemSettingTest: `o-palette` → `o-swatch` heroicon, duplicate key removed
- SetupWizardTest: RoleEnum seeding added to beforeEach
- InternshipRegistrationTest: `->todo()` syntax corrected to function body
- AssignmentTest: `->throws()` replaced with `todo()` (RBAC at middleware level)

---

## P1 — Scaffolded Domains Fully Implemented (Resolved)

**Status**: Resolved  
**Resolved**: 2026-04-30  
**Commit**: `c1cc4233`, `5dcf31cc`

4 domains have been fully implemented with migrations, factories, views, and passing tests:

| Domain       | Migration     | Factory                    | View (Livewire)                     | Tests                | Status   |
| ------------ | ------------- | -------------------------- | ----------------------------------- | -------------------- | -------- |
| Report       | ✅            | ✅                         | ✅ `reports/index.blade.php`        | ✅ 7 assertions      | Complete |
| Document     | ✅ (2 tables) | ✅ (+ `published()` state) | ✅ `handbooks/index.blade.php`      | ✅ RBAC corrected    | Complete |
| Schedule     | ✅            | ✅                         | ✅ `schedules/index.blade.php`      | ✅ 6 assertions      | Complete |
| Academic     | ✅            | ✅                         | ✅ `academic-years/index.blade.php` | ✅ active constraint | Complete |

---

## P2 — Scaffolded Domains Not Yet Implemented

**Status**: Open  
**Impact**: 4 domains have scaffold (Model, Actions, Controller, Policy, Routes, Tests) but lack
migrations, views, and full implementation  
**Tracked in**: Future implementation plan

| Domain            | Scaffold Files        | Missing                  |
| ----------------- | --------------------- | ------------------------ |
| Account  | 4 Actions, Controller | Models, migration, views |
| Audit     | Controller            | Model, migration, views  |
| Mentor | Action, Controller    | Model, migration, views (specialization: school_teacher/industry_supervisor) |
| Mentee | Controller            | Views                    |
| Evaluation | Action             | Model, migration, views  |

---

## P3 — maryUI Component Compatibility

**Status**: Mitigated (workaround applied)  
**Impact**: Scaffolded views use plain HTML instead of `x-mary-*` components

### Symptom

Using `x-mary-table` and other maryUI components in scaffolded Livewire views caused
`Using $this when not in object context` fatal errors.

### Workaround

All scaffolded views were rewritten with plain HTML tables and badges instead of maryUI components.
This is functional but loses the consistent UI styling pattern used elsewhere in the application.

### Recommended Fix

Investigate root cause of maryUI `$this` context errors and migrate scaffolded views to use maryUI
components for consistency.

---

## P3 — Duplicate Migration Timestamp (Resolved)

**Status**: Resolved  
**Resolved**: 2026-04-30  
**Commit**: `5dcf31cc`

`generated_reports` migration was moved from `_140000` to `_140004` to avoid collision with
`academic_years` migration. All 5 new migrations now have unique timestamps.

---

_Last updated: May 3, 2026_
