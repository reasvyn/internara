# Known Issues & Limitations

> **Last updated:** 2026-06-15
> **Changes:** Login & Dashboard audit — findings D-1 through D-5 added

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

## Issues

| Severity | Count |
|----------|-------|
| HIGH     | 2 |
| MEDIUM   | 2 |
| LOW      | 1 |
| **Total** | **5** |

---

### D-1 — HIGH: Activation Code Input `maxlength="6"` Conflicts with Validation (16–19 chars)

| Attribute | Detail |
|-----------|--------|
| **Files** | `resources/views/auth/activation-token/activate-account.blade.php:23`, `app/Auth/Account/Livewire/ActivateAccount.php` |
| **Pattern violated** | UX consistency |
| **What's wrong** | The activation code input field has `maxlength="6"` in the Blade template, but the Livewire validation rule requires `min:16|max:19`. The HTML attribute blocks valid-length codes at the browser level before server validation runs, making account activation impossible via the web form. |
| **Fix** | Change `maxlength="6"` to `maxlength="19"` in the Blade template, or remove `maxlength` entirely since Livewire handles validation. |
| **Impact** | Runtime — users cannot activate provisioned accounts through the UI |

---

### D-2 — HIGH: No Super Admin–Specific Dashboard

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/User/Dashboard/Livewire/AdminDashboard.php`, `app/User/Services/DashboardService.php` |
| **Pattern violated** | `docs/architecture.md` — Role-based UI |
| **What's wrong** | `super_admin` routes to the same `AdminDashboard` as `admin`. The only difference is a single "System Settings" quick link. No super admin–specific widgets (audit log summary, pulse stats, backup status, system health trends) exist. |
| **Fix** | Create a dedicated `SuperAdminDashboard` Livewire component or add super_admin–specific cards to `AdminDashboard` gated by `auth()->user()->hasRole('super_admin')`. |
| **Impact** | Maintainability — super_admin lacks at-a-glance system oversight |

---

### D-3 — MEDIUM: Student Dashboard Timeline Section Has No Data Backend

| Attribute | Detail |
|-----------|--------|
| **Files** | `resources/views/user/dashboard/student.blade.php:98-100`, `app/User/Dashboard/Livewire/StudentDashboard.php` |
| **Pattern violated** | Dead code / placeholder |
| **What's wrong** | The student dashboard renders a "Timeline" card that always shows an empty state. The `StudentDashboard` Livewire component has no `$timeline` property and no Read Action populates it. The card is pure UI placeholder with no backend. |
| **Fix** | Either implement the timeline feed (recent activities, journal submissions, etc.) or remove the empty card. |
| **Impact** | UX — confusing empty state on student dashboard |

---

### D-4 — MEDIUM: No First-Login Onboarding for Non–Super-Admin Users

| Attribute | Detail |
|-----------|--------|
| **Files** | `app/Auth/Login/Listeners/SendSuperAdminWelcomeNotification.php` |
| **Pattern violated** | Missing feature |
| **What's wrong** | Only `super_admin` gets a welcome notification on first login. Students, teachers, and supervisors are redirected to their dashboard with no orientation, no "getting started" guide, and no first-time setup flow. The `dashboard-guide` floating help button is generic and not role-specific. |
| **Fix** | Add role-specific first-login notifications or a lightweight onboarding checklist modal for new non-admin users. |
| **Impact** | UX — new users lack guidance on first use |

---

### D-5 — LOW: Dashboard `_sidebar` View Unused by Role-Specific Dashboards

| Attribute | Detail |
|-----------|--------|
| **Files** | `resources/views/user/dashboard/_sidebar.blade.php`, `resources/views/user/dashboard/{admin,student,teacher,supervisor}.blade.php` |
| **Pattern violated** | Code duplication |
| **What's wrong** | The `_sidebar` partial (profile summary + quick links) is only included by the base `UserDashboard` (`index.blade.php`). The four role-specific dashboards each inline their own duplicate quick-links section rather than reusing the shared partial. |
| **Fix** | Replace inlined quick-links sections in admin/student/teacher/supervisor dashboards with `@include('user.dashboard._sidebar')`. |
| **Impact** | Maintainability — duplicated markup across 4 views |

---

## Audit Summary — 2026-06-15

| Severity | Count |
|----------|-------|
| HIGH     | 2 |
| MEDIUM   | 2 |
| LOW      | 1 |
| **Total** | **5** |

### By Category
- **Bug** (blocks functionality): D-1
- **Missing Feature**: D-2, D-4
- **Dead Code / Placeholder**: D-3
- **Code Quality**: D-5

