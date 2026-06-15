# Known Issues & Limitations

> **Last updated:** 2026-06-15
> **Changes:** resolve P-1 (remove DB:: from entity), P-2 (add 6 Livewire feature tests), P-3 (dispatch RecoverySlipGenerated event); fix UpdateProfileAction validate signature conflict

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

All issues have been resolved. See [RESOLVED] entries above for details.

---

### P-1 — [RESOLVED] SuperAdminIntegrityRules Entity Uses `DB::` Facade for Queries

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Removed `countSuperAdmins()` method and `DB` import from entity. `fromModel()` now expects `super_admin_count` to be loaded by caller. Updated `User::asSuperAdminIntegrityRules()` to eagerly load count via `loadCount`. All 9 direct callers migrated to `$user->asSuperAdminIntegrityRules()`. |

---

### P-2 — [RESOLVED] 6 Livewire Components Missing Feature Tests

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Feature tests created for all 6 components: `ProfileEditorTest` (7 tests), `RecoveryCodeTest` (4 tests), `AccountRecoveryTest` (4 tests), `RecoverySlipManagerTest` (6 tests), `NotificationCenterTest` (5 tests), `NotificationBellTest` (5 tests). Total 48 assertions across 31 new tests. |

---

### P-3 — [RESOLVED] GenerateRecoverySlipAction Does Not Dispatch an Event

| Attribute | Detail |
|-----------|--------|
| **Resolved** | 2026-06-15 — Created `RecoverySlipGenerated` event in `app/Auth/AccountRecovery/Events/` with `user` and `codeCount` properties. `GenerateRecoverySlipAction::execute()` now dispatches `RecoverySlipGenerated` after successful log call. Listeners can be registered in `config/event.php`. |

