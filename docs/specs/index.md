# Feature Specifications — `docs/specs/`

> **Last updated:** 2026-07-21 **Changes:** feat — add partnership, internships, enrollment, system-settings specs (11 total)

## Description

Comprehensive feature specifications for the Internara system. Each spec defines problem
statements, goals/non-goals, user stories, functional/non-functional requirements, API/data
contracts, design decisions, and success metrics.

Specs are the **authoritative source** for feature implementation. When code and spec disagree,
update the spec first, then implement.

---

## Directory

| Spec | Module | Status |
| ---- | ------ | ------ |
| [Install & Setup](install-and-setup.md) | Setup | ✅ Complete |
| [Core Infrastructure](core-infra.md) | Core | ✅ Complete |
| [Cache & Session](cache-and-session.md) | Core | ✅ Complete |
| [Logging & Error Handling](logging-and-error-handling.md) | Core | ✅ Complete |
| [Login & Dashboard](login-and-dashboard.md) | Auth / User | ✅ Complete |
| [User Management](user-management.md) | User / SysAdmin | ✅ Complete |
| [Institutional & Academics](institutional-and-academics.md) | Academics / Settings | ✅ Complete |
| [Internships](internships.md) | Program | ✅ Complete |
| [Partners — Company & Partnership](partnership.md) | Partners | ✅ Complete |
| [System Settings](system-settings.md) | Settings | ✅ Complete |
| [Enrollment](enrollment.md) | Enrollment | ✅ Complete |

---

## How Specs Are Used

```
spec-writing → docs/specs/{feature}.md → feature-building → code-writing → pest-testing
```

1. **`spec-writing`** skill produces a spec document in this directory
2. **`feature-building`** skill reads the spec as the primary implementation guide
3. **`code-writing`** skill implements against the spec's FR/NFR IDs
4. **`pest-testing`** skill verifies implementation matches spec requirements

---

## Spec Template

Every spec follows the 10-section format defined in `.agents/skills/spec-writing/SKILL.md`:

1. Problem Statements
2. Goals & Non-Goals
3. User Stories / Use Cases
4. Functional Requirements (FR-IDs)
5. Non-Functional Requirements (NFR-IDs)
6. API / Data Contracts
7. Design Decisions (DD-IDs)
8. Success Metrics

---

## Quick References

- `.agents/skills/spec-writing/SKILL.md` — Spec writing conventions and template
- `.agents/skills/feature-building/SKILL.md` — How specs feed into implementation
- `docs/key-features.md` — Feature inventory (source of truth for what exists)
- `docs/foundation/project-requirements.md` — High-level feature specs
- `docs/modules/index.md` — Module dependency graph
