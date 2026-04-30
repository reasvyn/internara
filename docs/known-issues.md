# Known Issues

This document tracks known problems, technical debt, and blockers that affect the project. Issues here are acknowledged and have a planned resolution path.

---

## P0 — Legacy Modules Block Test Suite

**Status**: Active  
**Impact**: Tests cannot execute — quality checks are blind  
**Since**: Modular monolith → MVC migration (incomplete)

### Symptom
Running `./vendor/bin/pest` fails immediately:
```
Trait "Modules\Core\Academic\Models\Concerns\HasAcademicYear" not found
```

### Root Cause
The `modules/` directory contains legacy code from the pre-MVC modular monolith. It is still autoloaded by Composer and referenced by:
- `app/Console/Kernel.php` → imports `Modules\Status\Services\Jobs\DetectIdleAccountsJob`
- `config/modules.php` and `config/modules-livewire.php` → module configuration still present

### Resolution
Tracked in: `.agents/todo/2026-04-30-fix-checklist-accuracy-and-test-blocker.md` — Step 1

**Option A** (Recommended): Remove `modules/` directory, remove module packages from `composer.json`, replace the Kernel reference with equivalent in-app code or remove the scheduled job.

**Option B**: Fix the missing trait/classes so modules load correctly, then plan a proper migration path.

---

## P1 — Stale Documentation Counts

**Status**: Being corrected  
**Impact**: Developers may act on outdated numbers  
**Since**: Ongoing — counts become stale as code evolves

### Symptom
Documentation contained hard-coded counts (models, migrations, factories, tests, config files, etc.) that quickly become inaccurate as the project evolves.

### Resolution
All manual counts have been removed from `docs/`. Documentation now describes:
- **What exists** (by name and location)
- **How it works** (patterns and principles)
- **Where to add new things** (AI quick reference tables)

For current counts, use codebase inspection:
```bash
find app/Models -name '*.php' ! -path '*/Concerns/*' | wc -l
find database/migrations -name '*.php' | wc -l
find database/factories -name '*Factory.php' | wc -l
```

---

## P2 — Author Signature "Fatal Error" Claim

**Status**: Documented, not yet resolved  
**Impact**: S1 claim is inaccurate — no actual protection exists  
**Since**: Checklist audit 2026-04-30

### Symptom
The feature checklist claimed `[v] [v] [v] Author signature protection (fatal error on mismatch)`. The actual implementation (`app/Livewire/Layout/AppSignature.php`) is a display component only — it renders author credits but does not enforce or verify anything.

### Resolution
Checklist corrected to `[v] [!] [v]`. Decision needed: implement actual signature verification enforcement, or remove the security claim entirely.

---

*Last updated: April 30, 2026*
