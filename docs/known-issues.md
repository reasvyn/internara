# Known Issues & Limitations

> **Last updated:** 2026-06-15
> **Changes:** Login & Dashboard audit findings D-1 through D-5 resolved

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
| **Total** | **0** |

No open issues.

