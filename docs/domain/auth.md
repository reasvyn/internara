# Auth Domain
> Last updated: 2026-05-31
> **Status:** ✅ **Fully Implemented** — all 40 files in [reference](auth-reference.md) exist

## Purpose

Auth is the security boundary — it controls who can access the system, what they can do, and
how identity is proven and maintained over time. Every interaction passes through Auth's gate.

This domain encompasses authentication (verifying identity), authorization (roles and permissions),
account lifecycle (state machine governing what accounts can do), password management (change,
reset, recovery), and account recovery mechanisms.

---

## Design Principles

### 1. Defense in Depth

Authentication is validated at multiple layers:

| Layer | Mechanism |
|---|---|
| Network | Rate limiting per IP and per endpoint |
| Route | `CheckRoleMiddleware` gates access by role |
| Application | 5-step sequential validation in LoginAction |
| Session | Regeneration on login/logout to prevent fixation |
| Account | Auto-lock after 10 failed attempts; status-based login gating |

No single layer is trusted. Each layer assumes the layer below it may have been bypassed.

### 2. Single-Use, Time-Limited Tokens

All tokens (password reset, account recovery) follow the same contract:
- **Time-limited** — configurable expiry
- **Single-use** — consumed atomically on first successful validation
- **Timing-attack resistant** — validated with `hash_equals()`
- **Rate-limited** — per-endpoint configuration

### 3. Account Lifecycle as Enforced State Machine

Account status follows `StatusEnum` with explicit transitions. Invalid transitions are rejected
at the enum level, not just the UI. The state machine guarantees:

- Terminal states (`ARCHIVED`, `PROTECTED`) cannot transition further
- `PROTECTED` accounts (super admin) are immutable — cannot be locked, deleted, or transitioned
- At least one super admin must always exist — deletion of the last super admin is blocked
- Login is gated by `AccountStatus::allowsLogin()` — each status explicitly permits or denies access

### 4. Functional Role Indirection

MENTOR and MENTEE are derived from user roles, not assigned directly:

```
MENTOR  ⟵  TEACHER, SUPERVISOR
MENTEE  ⟵  STUDENT
```

This decouples the mentoring subsystem from specific user types. A company supervisor and an
academic teacher both resolve to MENTOR without needing the same user role. Adding a new
mentor-like role (e.g., COACH) requires updating only `Role::resolvesTo()`.

### 5. Audit Every Authentication Event

All authentication events are logged via SmartLogger:
- Login success/failure (with identifier, attempt count, IP)
- Account lock/unlock (with acting admin, reason)
- Password changes (actor, target, method)
- Rate limiting events
- Account status transitions

This provides a complete security audit trail for incident investigation.

---

## Domain Boundary

The Auth domain owns the entire security boundary of the system — who can enter, what they can access, and how their identity is proven and maintained over time. It encompasses authentication (login via email or username with sequential validation and auto-lock after failed attempts), authorization through a five-role RBAC system (super_admin, admin, teacher, student, supervisor) plus two derived functional roles (mentor and mentee), password management (reset, confirm, change), account lifecycle via an eight-state state machine (provisioned through protected), and account recovery mechanisms including admin-issued recovery slips and CLI emergency recovery. Rate limiting is enforced per endpoint at multiple layers.

Auth does not own user profile data, dashboard routing, notification management, or avatar handling — those belong to the User domain. It does not manage runtime configuration (Settings), school profiles (School), program definitions (Internship), or any operational domain data. Auth controls access to those domains but does not manage their content.

The domain references User identities for authentication and role resolution but does not own the User model's extended profile fields. It depends on Settings for configurable thresholds (max attempts, token expiry durations) and on Core for logging, exception handling, and base contracts, but delegates business ownership of those settings and services to their respective domains.

---

## Key Features

- Authenticate users via email or username with sequential validation and automatic account lock after configurable failed attempts.
- Allow guest users to request a password reset email and set a new password through a time-limited, single-use token.
- Require password re-confirmation before performing sensitive account operations.
- Enforce per-endpoint rate limiting on login, password reset, password confirmation, and account recovery endpoints.
- Generate one-time recovery slips that administrators deliver offline to locked-out users for account recovery.
- Manage account lifecycle through eight states with strict transition rules, terminal states, and protected super-admin accounts.
- Assign five base user roles plus two derived functional roles that control access across all business domains.
- Detect potential duplicate accounts by matching email, phone number, or national identifier.
- Complete a step-by-step account recovery form with guided instructions for each recovery stage.
- Display recovery codes with one-click copy and one-click download options for safekeeping.
- Receive clear inline validation messages when login credentials are incorrect or the account is locked.
- View a lockout countdown timer after exceeding the maximum failed login attempts.
