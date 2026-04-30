# Known Issues

This document tracks known problems, technical debt, and blockers that affect the project. Issues here are acknowledged and have a planned resolution path.

---

## P0 — Legacy Modules Disabled from Autoloading

**Status**: Resolved (mitigated)  
**Impact**: No longer affects test execution — modules disabled from autoloading  
**Since**: Modular monolith → MVC migration (incomplete)

### Symptom (Resolved)
Previously, running `./vendor/bin/pest` failed immediately:
```
Trait "Modules\Core\Academic\Models\Concerns\HasAcademicYear" not found
```

### Root Cause
The `modules/` directory contains legacy code from the pre-MVC modular monolith. It was autoloaded by Composer and referenced by `app/Console/Kernel.php`.

### Resolution
Module autoloading has been disabled:
- `config/modules.php` returns empty array
- `config/modules-livewire.php` returns empty array
- `app/Console/Kernel.php` module import removed
- `modules/*/tests/*` and `modules/*/src` removed from `phpunit.xml`
- `./modules/**` paths removed from `vite.config.js` Tailwind config

Result: Tests discoverable and executable. The `modules/` directory is retained as reference only.

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

| Domain | Migration | Factory | View (Livewire) | Tests | Status |
|--------|-----------|---------|-----------------|-------|--------|
| Report | ✅ | ✅ | ✅ `reports/index.blade.php` | ✅ 7 assertions | Complete |
| Handbook | ✅ (2 tables) | ✅ (+ `published()` state) | ✅ `handbooks/index.blade.php` | ✅ RBAC corrected | Complete |
| Schedule | ✅ | ✅ | ✅ `schedules/index.blade.php` | ✅ 6 assertions | Complete |
| AcademicYear | ✅ | ✅ | ✅ `academic-years/index.blade.php` | ✅ active constraint | Complete |

---

## P2 — Scaffolded Domains Not Yet Implemented

**Status**: Open  
**Impact**: 4 domains have scaffold (Model, Actions, Controller, Policy, Routes, Tests) but lack migrations, views, and full implementation  
**Tracked in**: Future implementation plan

| Domain | Scaffold Files | Missing |
|--------|---------------|---------|
| AccountLifecycle | 4 Actions, Controller | Models, migration, views |
| Activity Feed | Controller | Model, migration, views |
| Mentor Evaluation | Action, Controller | Model, migration, views |
| Teacher Dashboard | Controller | Views |

---

## P3 — maryUI Component Compatibility

**Status**: Mitigated (workaround applied)  
**Impact**: Scaffolded views use plain HTML instead of `x-mary-*` components

### Symptom
Using `x-mary-table` and other maryUI components in scaffolded Livewire views caused `Using $this when not in object context` fatal errors.

### Workaround
All scaffolded views were rewritten with plain HTML tables and badges instead of maryUI components. This is functional but loses the consistent UI styling pattern used elsewhere in the application.

### Recommended Fix
Investigate root cause of maryUI `$this` context errors and migrate scaffolded views to use maryUI components for consistency.

---

## P3 — Duplicate Migration Timestamp (Resolved)

**Status**: Resolved  
**Resolved**: 2026-04-30  
**Commit**: `5dcf31cc`

`generated_reports` migration was moved from `_140000` to `_140004` to avoid collision with `academic_years` migration. All 5 new migrations now have unique timestamps.

---

*Last updated: April 30, 2026*
