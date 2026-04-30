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

Result: 197 tests discoverable and executable. The `modules/` directory is retained as reference only.

---

## P1 — Failed Tests (9 Total)

**Status**: Open  
**Impact**: 9 out of 216 tests fail  
**Tracked in**: `.agents/todo/2026-04-30-fix-test-failures-and-implement-domains.md` — Steps 1-4

| Category | Count | Cause |
|----------|-------|-------|
| Heroicons SVG missing | 7 | `o-palette` icon not found in SystemSettingTest views |
| Role not seeded | 1 | `super_admin` role missing in SetupWizardTest |
| Pest duplicate names | 2 | `->todo()` syntax conflicts in InternshipRegistrationTest |
| RBAC assertion | 1 | `->throws()` mismatch in AssignmentTest |

---

## P2 — Scaffolded Domains Not Yet Implemented

**Status**: Open  
**Impact**: 8 domains have scaffold (Model, Actions, Controller, Policy, Routes, Tests) but lack migrations, views, and full implementation  
**Tracked in**: `.agents/todo/2026-04-30-fix-test-failures-and-implement-domains.md` — Steps 6-9

| Domain | Scaffold Files | Missing |
|--------|---------------|---------|
| Report | Model, 2 Actions, Controller, Request, Policy, Test | Migration, factory, view, job |
| Handbook | 2 Models, 2 Actions, Controller, Request, Policy, Test | Migrations, factory, view |
| Schedule | Model, 3 Actions, Controller, 2 Requests, Policy, Test | Migration, factory, view |
| AcademicYear | Model, 2 Actions, Controller, Policy, Test | Migration, factory, view |
| AccountLifecycle | 4 Actions, Controller | Models, migration, views |
| Activity Feed | Controller | Model, migration, views |
| Mentor Evaluation | Action, Controller | Model, migration, views |
| Teacher Dashboard | Controller | Views |

---

*Last updated: April 30, 2026*
