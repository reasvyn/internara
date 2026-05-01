# Key Features Guideline

Detailed instructions on how to construct, format, and maintain key feature entries in `KEY_FEATURES_CHECKLIST.md`.

---

## Line Types

Every line in the feature list is one of three types. Only **feature lines** are tracked.

### 1. Feature Line (tracked)

```
- {status} | [1] [2] [3] | {priority} {roles-tag} Feature description
```

Starts with `- ` followed by the status marker. Segments are separated by ` | `.

### 2. Sub-Feature Line (tracked, child of parent)

```
  - {status} | [1] [2] [3] | Sub-feature description
```

Indented with 2 spaces. Inherits scope and roles from parent. Evolves independently with its own markers.

### 3. Note Line (not tracked)

```
  > Note text here — provides context, not tracked
```

Indented with 2 spaces, uses `> `. Provides context but is **not** part of the tracking system.

### Domain Notes Section

```
### Notes
- Context, decisions, references, or technical details
- Not tracked — does not have markers
```

Used at the domain level for information that applies to multiple features.

---

## Format Anatomy

```
- [v] | [v] [v] [v] | [MUST HAVE] [roles:Admin,Student] User registration
  │     │  │  │  │     │            │                   │
  │     │  │  │  │     │            │                   └── Description
  │     │  │  │  │     │            └── Role tag (optional)
  │     │  │  │  │     └── Priority tag
  │     │  │  │  └── Column 3: Documented
  │     │  │  └── Column 2: Tested
  │     │  └── Column 1: Implemented
  │     └── Separator: space | space
  └── Status marker
```

**Role tag is optional.** If the project does not use RBAC, omit it:

```
- [v] | [v] [v] [v] | [MUST HAVE] User registration
```

### Segment Breakdown

| Segment | Format | Example | Required |
|---------|--------|---------|----------|
| Status | `[marker]` | `[v]` | Yes |
| Separator | ` \| ` | `\|` | Yes |
| Columns | `[v/ ] [v/ ] [v/ ]` | `[v] [v] [ ]` | Yes |
| Separator | ` \| ` | `\|` | Yes |
| Priority | `[priority]` | `[MUST HAVE]` | Yes |
| Role tag | `[roles:...]` | `[roles:Admin]` | No |
| Description | plain text | `User registration` | Yes |

### Rules for Each Component

**Status marker**
- Exactly one of: `[ ]` `[P]` `[*]` `[R]` `[v]` `[+]` `[!]` `[x]`
- No spaces inside brackets
- No custom markers

**Implementation columns**
- Always exactly three columns separated by single spaces
- Each column is either `[v]` or `[ ]` (empty bracket)
- No partial states like `[*]` inside the column

**Priority tag**
- Exactly one of: `[MUST HAVE]` `[SHOULD HAVE]` `[COULD HAVE]` `[WON'T HAVE]`
- Do not invent custom priority tags

**Role tag**
- Format: `[roles:Role1,Role2]` (comma-separated, no spaces after comma)
- Roles must be defined in the Stakeholders section
- Use `[roles:ALL]` when the feature applies to every role
- Use `[roles:System]` for infrastructure/backend features with no user-facing surface
- **Optional** — omit if the project does not use role-based access control
- Do not invent roles not defined in Stakeholders

**Description**
- Written in project language, not technical jargon
- Starts with a noun or action phrase, not a sentence
- Max one line
- No inline comments, parentheses, or notes
- Use notes (`> `) for additional context on the line below

### Correct vs Incorrect

```
CORRECT:
- [v] | [v] [v] [v] | [MUST HAVE] [roles:Admin] Manage user accounts
  > Uses UUID-based users, email as unique identifier

INCORRECT (missing separator):
- [v] [v] [v] [v] [MUST HAVE] Manage user accounts

INCORRECT (inline note on feature line):
- [v] | [v] [v] [v] | [MUST HAVE] [roles:Admin] Manage user accounts (uses UUID)

INCORRECT (missing role tag):
- [v] | [v] [v] [v] | [MUST HAVE] Manage user accounts

INCORRECT (undefined role):
- [v] | [v] [v] [v] | [MUST HAVE] [roles:SuperUser] Manage user accounts

INCORRECT (custom priority):
- [v] | [v] [v] [v] | [URGENT] [roles:Admin] Manage user accounts

CORRECT (using [?] for audit-needed features):
- [?] | [ ] [ ] [ ] | [MUST HAVE] [roles:Admin] Manage user accounts
  > Needs deep audit — possible requirement mismatch or hidden issues

INCORRECT (misusing [?]):
- [?] | [v] [v] [v] | [MUST HAVE] [roles:Admin] Manage user accounts
  > Wrong: [?] should only be used when feature is NOT started ([ ]) or needs re-audit
```

---

## Status Markers

Tracks where a feature is in its lifecycle. One marker per feature.

| Marker | Meaning | When Used |
|--------|---------|-----------|
| `[ ]` | Not started | Feature is defined, no work has begun |
| `[P]` | Planned | Plan written, awaiting human approval |
| `[*]` | In progress | Implementation is underway |
| `[R]` | Ready for review | Code, tests, and docs complete |
| `[v]` | Verified | Supervisor APPROVED |
| `[+]` | Needs improvement | Works but needs enhancement or evolution |
| `[?]` | Needs audit | Deferred, needs deep audit to find root cause (requirement mismatch, hidden issues, or "something else") |
| `[!]` | Has critical issues | Blockers, bugs, or security concerns |
| `[x]` | Deprecated / removed | End of life |

### Evolution Flow

Features evolve in any direction — not just forward.

```
[ ] → [P] → [*] → [R] → Supervisor review → [v]
                                      │
                  [REQUEST CHANGES]   │   [APPROVE]
                    ┌─────────────────┘   │
                    │                     ▼
                  [*]                    [v]
                    │                     │
                 [BLOCKED]          ┌────┴────┐
                    │              │         │
                    ▼          evolves    regresses
                   [!]          ▼         ▼
                    │         [+]       [!]
                    └──► fixed │         │
                                │         │
                         improved ───► [v]

[v] ──► [?] (needs deep audit)
[?] ──► [*] (audit complete, fix begins)
[?] ──► [v] (audit finds no issues)
[?] ──► [!] (audit finds critical issues)
```

### Marker Behavior Rules

| Transition | Who | Trigger |
|------------|-----|---------|
| `[ ]` → `[P]` | Engineer | Plan created |
| `[P]` → `[*]` | Engineer | Human approved plan |
| `[*]` → `[R]` | Engineer | Code + tests + docs complete |
| `[R]` → `[v]` | Supervisor | Review APPROVED |
| `[R]` → `[*]` | Supervisor | Review REQUEST CHANGES |
| `[v]` → `[?]` | Supervisor or Engineer | Feature needs deep audit to find root cause |
| `[?]` → `[*]` | Engineer | Audit complete, fix work begins |
| `[?]` → `[v]` | Supervisor | Audit finds no issues, feature verified |
| `[?]` → `[!]` | Supervisor or Engineer | Audit finds critical issues |
| `[v]` → `[+]` | Supervisor or Engineer | Feature needs enhancement |
| `[v]` → `[!]` | Supervisor or Engineer | Bug, regression, or security issue |
| `[+]` → `[*]` | Engineer | Improvement work begins |
| `[+]` → `[v]` | Supervisor | Improvement verified |
| `[!]` → `[*]` | Engineer | Fix work begins |
| `[!]` → `[v]` | Supervisor | Fix verified |
| Any → `[x]` | Human | Feature explicitly deprecated |

### Key Principle

**A feature entry is permanent.** When a feature changes — gets enhanced, refactored, fixed, or partially deprecated — update the same entry. Do not create new feature entries for evolution of existing features.

---

## Implementation Markers (Quality Gates)

Three columns tracking gates that must pass. Updated by the Engineer.

| Column | Meaning | Gate Owner |
|--------|---------|------------|
| `[1]` | **IMPLEMENTED** — code is written, compiles, follows conventions | Engineer |
| `[2]` | **TESTED** — has passing tests, security checks, no failures | Engineer |
| `[3]` | **DOCUMENTED** — documentation synchronized with code | Engineer |

### Completion Rule

- **Ready for review** (`[R]`): all three markers are `[v]` → `[v] [v] [v]`
- **Verified/complete** (`[v]`): status is `[v]` AND all three markers are `[v]`

### Partial Progress

Markers reset on evolution or change request:

```
[v] [v] [ ] — code written and tested, documentation pending
[v] [ ] [ ] — code written, tests and documentation pending
[ ] [ ] [ ] — improvement identified but not yet started
```

---

## Feature Priority Tags

| Tag | Meaning |
|-----|---------|
| `[MUST HAVE]` | Critical for MVP, non-negotiable for production |
| `[SHOULD HAVE]` | Important, next priority after MVP |
| `[COULD HAVE]` | Nice-to-have, low priority |
| `[WON'T HAVE]` | Explicitly out of scope for current roadmap |

---

## Role Tags

### Purpose

Not every feature applies to every user. Role tags make it explicit which roles a feature serves, is scoped to, or affects. This prevents ambiguity about who can use what, and ensures the Engineer implements correct access control from the start.

### Format

```
[roles:Role1,Role2]
```

- Comma-separated, no spaces after comma
- Roles must be defined in the **Stakeholders** section
- Role names are case-sensitive and must match exactly

### Special Value

| Tag | Meaning |
|-----|---------|
| `[roles:ALL]` | Feature is visible or usable by every defined role |

### Usage Patterns

**Feature specific to one role:**
```
- [v] | [v] [v] [v] | [MUST HAVE] [roles:Admin] Manage user accounts
```

**Feature shared by multiple roles:**
```
- [v] | [v] [v] [v] | [MUST HAVE] [roles:Admin,Teacher] View student placements
```

**Feature available to everyone:**
```
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Dashboard
```

**Feature for system/internal (not role-scoped):**
```
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] Background job processing
```

**Project without RBAC — no role tag:**
```
- [v] | [v] [v] [v] | [MUST HAVE] User registration
- [v] | [v] [v] [v] | [SHOULD HAVE] Email notification system
```

Use `[roles:System]` for infrastructure, configuration, or backend features that have no direct user-facing surface but are required by the system.

### Role Tag Rules

- Role tags are **optional**. Include them only if the project uses role-based access control.
- If the project has no RBAC system, omit the role tag entirely from all feature entries.
- If the project has RBAC but a specific feature is not role-scoped, omit the tag for that feature.
- If a feature's role is unclear but the project uses RBAC, ask before adding.
- Do not invent roles not defined in Stakeholders.
- A feature that serves all roles should use `[roles:ALL]`, not list every role individually.
- `[roles:System]` does not imply the feature has a user interface.
- Sub-features inherit the parent's role tag by default but can override if they serve a different role.

### Role Coverage Audit (if RBAC is used)

Periodically verify that all defined roles have at least one feature tagged to them. A role with no tagged features is either:
- Not yet implemented (features are `[ ]` or `[P]`)
- Missing from the Stakeholders definition
- An unused role that should be removed

---

## Domain Structure

### Grouping Rules

- Use `### Domain: {name}` to group related features
- One domain per business area or system module
- Features within a domain are related by concern, not by implementation detail
- Use `---` between domains only if the domain has a `### Notes` section

### Sub-Feature Rules

- Sub-features are children of a parent feature, indented 2 spaces
- Sub-features have their own status and column markers
- Sub-features inherit scope and roles from parent but evolve independently
- Use sub-features when a feature has distinct, independently trackable components
- Do not nest deeper than one level (parent → sub-feature only)

### Note Placement

- **Inline note** (`> `): placed immediately below the feature it describes, indented 2 spaces
- **Domain notes** (`### Notes`): placed at the end of a domain, before `---` or the next domain
- Notes never appear on the same line as a feature

---

## Feature Evolution in Practice

### Scenario 1: Feature Gets Improved

```
# Before (feature is complete)
- [v] | [v] [v] [v] | [SHOULD HAVE] [roles:ALL] Email notification system

# Audit finds it needs template system and queue integration
- [+] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Email notification system
  > Needs: template system, queue integration, retry logic

# Engineer starts improvement
- [*] | [v] [ ] [ ] | [SHOULD HAVE] [roles:ALL] Email notification system
  > Template engine implemented, queue wiring in progress

# Improvement ready for review
- [R] | [v] [v] [v] | [SHOULD HAVE] [roles:ALL] Email notification system
  > Now supports templates, queue-based delivery, 3-retry with backoff

# Supervisor approves
- [v] | [v] [v] [v] | [SHOULD HAVE] [roles:ALL] Email notification system
  > Now supports templates, queue-based delivery, 3-retry with backoff
```

### Scenario 2: Feature Has a Bug

```
# Before (feature is complete)
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Password reset

# Bug reported: rate limiting bypassed
- [!] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Password reset
  > Rate limiting bypassed — tracked in issue 2026-01-20-bug-rate-limit.md

# Fix in progress
- [*] | [v] [ ] [ ] | [MUST HAVE] [roles:ALL] Password reset
  > Added rate limiting middleware, updating tests

# Fix verified
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Password reset
  > Rate limiting now enforced at middleware and action level
```

### Scenario 3: Feature Deprecation

```
# Before
- [v] | [v] [v] [v] | [WON'T HAVE] [roles:Admin] Legacy dashboard widgets

# Human confirms deprecation
- [x] | [v] [v] [x] | [WON'T HAVE] [roles:Admin] Legacy dashboard widgets
  > Replaced by new dashboard system — legacy code removed
```

---

## Complete Examples

### Example: Domain Without RBAC (no role tags)

```
### Domain: Core
- [v] | [v] [v] [v] | [MUST HAVE] User registration
  > Email-based, UUID identifiers
  - [v] | [v] [v] [v] | Email verification
  - [v] | [v] [v] [v] | Password reset
- [v] | [v] [v] [v] | [MUST HAVE] Dashboard
- [v] | [v] [v] [v] | [SHOULD HAVE] Settings page
```

---

### Example: Full Domain with Multiple Roles

```
### Domain: Authentication
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] User registration
  > Uses UUID-based users, email as unique identifier
  - [v] | [v] [v] [v] | Email verification flow
  - [v] | [v] [v] [v] | Invitation-based onboarding
- [*] | [v] [ ] [ ] | [MUST HAVE] [roles:ALL] Password reset
  > Email template needs refinement — tracked in issue 2026-01-15-audit-email-templates.md
  - [ ] | [ ] [ ] [ ] | Rate limiting on reset requests
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Session management
  > Token-based, 24h expiry, refresh on activity
- [+] | [v] [v] [v] | [SHOULD HAVE] [roles:ALL] Two-factor authentication
  > Exists but needs SMS as alternative to TOTP

### Notes
- Registration flow was redesigned in cycle 2 to support invitation-based onboarding
- TOTP uses RFC 6238 standard — no custom implementation
- Session storage uses database driver, Redis-ready
```

### Example: Multiple Domains with Role Separation

```
### Domain: System Core
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] MVC architecture with stateless Action layer
  - [v] | [v] [v] [v] | Layer separation enforced
  - [v] | [v] [v] [v] | UUID-based models with embedded business rules
  - [v] | [v] [v] [v] | FormRequest validation at boundary
- [v] | [v] [v] [v] | [MUST HAVE] [roles:System] Background job processing
  - [v] | [v] [v] [v] | Queue driver configured (database, Redis-ready)
  - [ ] | [ ] [ ] [ ] | Job retry and failure handling

---

### Domain: User Management
- [v] | [v] [v] [v] | [MUST HAVE] [roles:Admin] Manage user accounts
  > Full CRUD with role assignment and status control
  - [v] | [v] [v] [v] | User listing with pagination and search
  - [v] | [v] [v] [v] | Role assignment and modification
- [P] | [ ] [ ] [ ] | [SHOULD HAVE] [roles:Admin] Bulk user import
  > CSV-based onboarding for large cohorts

---

### Domain: Dashboard
- [v] | [v] [v] [v] | [MUST HAVE] [roles:ALL] Role-based dashboard
  > Content changes based on authenticated user's role
  - [v] | [v] [v] [v] | Admin dashboard widgets
  - [v] | [v] [v] [v] | Student dashboard widgets
  - [*] | [v] [ ] [ ] | Teacher dashboard widgets
```

---

## Anti-Patterns

### What Not To Do

| Anti-Pattern | Why | Correct Approach |
|-------------|-----|-----------------|
| Create new feature entry for evolution | Duplicates history, loses context | Update the existing entry |
| Put notes inline on feature line | Breaks visual scan, hard to parse | Use `> ` on next line |
| Skip the separator ` \| ` | Columns and status become ambiguous | Always use separators |
| Use custom markers or priority tags | Not in the tracking system | Use defined markers only |
| Use undefined roles in role tag | Makes RBAC unverifiable | Define role in Stakeholders first |
| Skip a column | Misaligns the tracking format | Always use all three columns |
| Nest features deeper than one level | Unreadable, hard to track | Parent → sub-feature only |
| Leave `[P]` without an approved plan | `[P]` means a plan exists | Create plan first, then mark `[P]` |
| Mark `[R]` without all three `[v]` | Not actually ready for review | Complete all gates before `[R]` |
| Mark `[v]` without Supervisor review | Self-review prohibited | Supervisor must approve |
| Track technical debt as a feature | Not a feature — it's context | Document in notes or create an issue |
| Use `[!]` for low-priority bugs | `[!]` is for critical issues only | Use notes for non-critical observations |
| Use `[?]` for started features | `[?]` is for audit-needed, not in-progress | Use `[*]` or `[ ]` instead |
| Leave `[?]` without audit plan | `[?]` means deep audit needed | Create audit plan, then mark `[?]` |

---

## Checklist for New Feature Entries

Before adding a feature entry, confirm:

- [ ] Feature belongs to an existing domain or a clearly defined new domain
- [ ] Description is in project language, not technical jargon
- [ ] Description is one line, no inline notes
- [ ] Status marker is appropriate for current state
- [ ] Separators ` \| ` are present between status, columns, and tags
- [ ] All three implementation columns are present
- [ ] Priority tag is from the defined set
- [ ] Role tag uses only defined roles or `[roles:ALL]` / `[roles:System]` (if project uses RBAC)
- [ ] Notes (if any) are on their own line with `> `
- [ ] Sub-features (if any) are indented 2 spaces
- [ ] Feature entry will be permanent — this is the one entry for this feature
