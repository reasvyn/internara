# Known Issues & Limitations

> **Last updated:** 2026-06-15
> **Changes:** Profile & Account Recovery audit — findings P-1 through P-4 added

This document catalogs known gaps between documented requirements and actual implementation, as well as code quality issues found during systematic audits.

---

## CRITICAL

### C-2 — [RESOLVED] Action Triad Migration: All Actions Use Correct Base Classes

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — 139 Command → `BaseCommandAction`, 12 Read → `BaseReadAction`, 1 Process → `BaseProcessAction` |

---

### C-3 — [RESOLVED] SmartLogger PII Masking: All Calls Protected

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — All 40+ unprotected SmartLogger calls across app/ now chain `->withPiiMasking()` before `->save()` |

---

## MEDIUM

### C-12 — [RESOLVED] 20+ Destructive Actions Use `wire:confirm` Instead of Two-Step Modal

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — All 29 wire:confirm usages across 14 Livewire components replaced with x-core::ui.confirm modal + confirmAction() pattern |

---

### C-13 — [RESOLVED] Models with Business Logic (Should Be in Entities)

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — `ApiToken`: removed `isExpired/isRevoked/isValid` (use entity); `Registration`: removed `currentPhaseIndex/currentPhase` (entity exists); `User`: removed `latestStatus()`; `Report`: `captureSnapshot` is an action, out of scope |

---

### D-1 — [RESOLVED] Activation Code Input `maxlength="6"` Conflicts with Validation (16–19 chars)

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Changed `maxlength="6"` to `maxlength="19"` in `activate-account.blade.php:22` |

---

### D-2 — [RESOLVED] No Super Admin–Specific Dashboard

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Added 3 super_admin system cards (Audit Overview, System Info, Platform Overview) to admin dashboard gated by `hasRole('super_admin')` |

---

### D-3 — [RESOLVED] Student Dashboard Timeline Section Has No Data Backend

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Removed empty timeline placeholder card from student dashboard |

---

### D-4 — [RESOLVED] No First-Login Onboarding for Non–Super-Admin Users

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — `SendSuperAdminWelcomeNotification` now sends role-specific welcome messages for all roles |

---

### D-5 — [RESOLVED] `_sidebar.blade.php` Removed

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — `_sidebar.blade.php` deleted; inline markup restored to each dashboard view directly |

---

## Issues

| Severity | Count |
|----------|-------|
| MEDIUM   | 2 |
| LOW      | 1 |
| **Total** | **3** |

---

### P-1 — MEDIUM: SuperAdminIntegrityRules Entity Uses `DB::` Facade for Queries

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/Auth/SuperAdmin/Entities/SuperAdminIntegrityRules.php:43` |
| **Pattern violated** | `docs/architecture/entity-pattern.md` — Entities must be pure business rules with zero I/O |
| **What's wrong** | `SuperAdminIntegrityRules::countSuperAdmins()` executes `DB::table('model_has_roles')...count()` directly. Entities should not perform database queries — they should receive all state via constructor injection. The `superAdminCount` is already passed via constructor, so `countSuperAdmins()` is only called as a fallback when `$model->super_admin_count` is null. |
| **Fix** | Remove `countSuperAdmins()` method and the `DB` import. Ensure the `superAdminCount` is always provided by the caller (Action or Livewire component) rather than falling back to a query inside the entity. |
| **Impact** | Maintainability — entity violates purity principle; testing requires mocking DB |

---

### P-2 — MEDIUM: 6 Livewire Components Missing Feature Tests

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/User/Profile/Livewire/ProfileEditor.php`, `app/Auth/AccountRecovery/Livewire/RecoveryCode.php`, `app/Auth/AccountRecovery/Livewire/AccountRecovery.php`, `app/Auth/AccountRecovery/Livewire/RecoverySlipManager.php`, `app/User/Notifications/Livewire/NotificationCenter.php`, `app/User/Notifications/Livewire/NotificationBell.php` |
| **Pattern violated** | `docs/infrastructure/testing.md` — every Livewire component should have a feature test |
| **What's wrong** | 6 Livewire components in the profile, recovery, and notification scope have no corresponding feature test file. The actions and models are tested, but the UI components (which handle validation, authorization, and Action delegation) are untested. |
| **Fix** | Create feature tests for each component: `Livewire::test(...)` → interact → assert state/redirect/flash. |
| **Impact** | Quality — UI logic is untested; regressions in form validation, authorization, and error handling may go undetected |

---

### P-3 — LOW: GenerateRecoverySlipAction Does Not Dispatch an Event

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/Auth/AccountRecovery/Actions/GenerateRecoverySlipAction.php` |
| **Pattern violated** | `docs/architecture.md` §Action Triad — Command Actions SHOULD dispatch events for significant state changes |
| **What's wrong** | `GenerateRecoverySlipAction` generates 10 recovery codes and logs the action, but dispatches no event. Other modules cannot react to recovery slip generation (e.g., notify the user via email, invalidate cache). |
| **Fix** | Create and dispatch a `RecoverySlipGenerated` event after successful generation, then register listeners in `config/event.php` as needed. |
| **Impact** | Maintainability — cross-module reactions to recovery slip generation require modifying the Action |

