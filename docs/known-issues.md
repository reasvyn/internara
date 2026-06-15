# Known Issues & Limitations

> **Last updated:** 2026-06-15
> **Changes:** All remaining feature gap issues (B-1..B-8, B-15, C-20) removed — resolved or deprioritized

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
| **Total** | **0** |

No open issues.

