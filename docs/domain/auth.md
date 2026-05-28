# Auth Domain

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

## Layer Structure

```
app/Domain/Auth/
├── Actions/         → 12 Command Actions for auth operations
├── Entities/        → 3 entities (Apprentice, RecoveryCodeState, SuperAdminIntegrityRules)
├── Enums/           → AccountStatus (8 states), Role (5 user + 2 functional)
├── Http/
│   ├── Middleware/   → AuthThrottleMiddleware, CheckRoleMiddleware
│   └── Requests/    → RoleRequest
├── Livewire/        → 9 components + 5 Forms for auth UI
├── Models/          → AccountRecoveryCode
├── Notifications/   → 3 notification classes
└── Policies/        → UserPolicy
```

---

## Actions

### Authentication Actions

| Action | Input | Description |
|---|---|---|
| `LoginAction` | identifier (email/username), password, remember | Authenticates with 5-step validation. Clears failed attempts on success. Uses `CacheKeys::AUTH_LOGIN_FAILURES` for atomic rate counting. Session regeneration inside Action. |
| `ConfirmPasswordAction` | password | Validates password for sensitive operations within configurable timeout window |
| `SendPasswordResetLinkAction` | email | Generates single-use time-limited token, sends reset link |
| `ResetPasswordAction` | token, email, password | Validates and consumes token, updates password |
| `UpdateUserPasswordAction` | user, currentPassword, newPassword | Self-service password change with current password confirmation |
| `ResetUserPasswordAction` | user, newPassword | Admin-initiated password reset (no current password needed) |

### Account Lifecycle Actions

| Action | Input | Description |
|---|---|---|
| `LockUserAccountAction` | user, reason | Locks account (blocks super_admin). Uses `BaseAction::transaction()`. |
| `UnlockUserAccountAction` | user | Unlocks previously locked account |
| `DetectUserAccountCloneAction` | — | Scans for duplicate accounts by email, phone, or identifier |

### Recovery Actions

| Action | Input | Description |
|---|---|---|
| `GenerateRecoverySlipAction` | user | Generates 10 single-use recovery codes (no expiry) |
| `RedeemRecoverySlipAction` | username, code, newPassword | Validates, consumes code, unlocks account, updates password |

### Permission Actions

| Action | Input | Description |
|---|---|---|
| `UpdateRolePermissionsAction` | role, permissions | Updates Spatie permission assignments for a role |

---

## Enums

### AccountStatus (8 states)

Implements `LabelEnum`, `StatusEnum`, and `ColorableEnum`.

```
PROVISIONED ──→ ACTIVATED ──→ VERIFIED ──→ RESTRICTED ──→ VERIFIED
                    │             │             │
                    │             ├──→ SUSPENDED ──→ ACTIVATED
                    │             │
                    │             ├──→ INACTIVE ──→ ACTIVATED
                    │             │
                    │             └──→ ARCHIVED (terminal)
                    │
                    └──→ ARCHIVED (terminal)

PROTECTED: Immutable — no transitions out
```

| Status | Login Allowed | Terminal | Description |
|---|---|---|---|
| PROVISIONED | ❌ | ❌ | Created, awaiting activation |
| ACTIVATED | ✅ | ❌ | Email verified, basic access |
| VERIFIED | ✅ | ❌ | Full identity confirmed |
| PROTECTED | ✅ | ✅ | Super admin only, immutable |
| RESTRICTED | ✅ | ❌ | Limited access, warning issued |
| SUSPENDED | ❌ | ❌ | Login blocked, admin action needed |
| INACTIVE | ✅ | ❌ | Prolonged inactivity, can reactivate |
| ARCHIVED | ❌ | ✅ | Data preserved, permanently blocked |

### Role (5 user + 2 functional)

Implements `LabelEnum`.

| Role | Type | Description |
|---|---|---|
| SUPER_ADMIN | user | Unrestricted — bypasses all gates |
| ADMIN | user | School-level management |
| TEACHER | user | Academic supervision |
| STUDENT | user | Participant |
| SUPERVISOR | user | Industry supervisor |
| MENTOR | functional | Derived from TEACHER + SUPERVISOR |
| MENTEE | functional | Derived from STUDENT |

---

## Entities

### Apprentice

Encapsulates account access checks. Pure business logic, no framework dependencies.

```php
final readonly class Apprentice extends BaseEntity
{
    public function isSuspended(): bool
    public function isArchived(): bool
    public function isInactive(): bool
    public function isLocked(): bool
    public function requiresSetup(): bool
    public function canTransitionTo(AccountStatus $target): bool
    public function status(): AccountStatus
}
```

### RecoveryCodeState

Encapsulates recovery code validity checks.

| Method | Purpose |
|---|---|
| `isValid()` | Code exists and not yet redeemed |
| `isRedeemed()` | Code has been consumed |
| `isExpired()` | Code has exceeded its time limit |

### SuperAdminIntegrityRules

Encapsulates super admin invariants:

| Method | Purpose |
|---|---|
| `isImmutable()` | Super admin name/username must match config defaults |
| `canBeDeleted()` | False if this is the last super admin |
| `hasCanonicalCredentials()` | Name is "Administrator", username is "superadmin" |

---

## Middleware

### AuthThrottleMiddleware

Applied to all auth routes. Provides per-endpoint rate limiting:

| Endpoint | Max Attempts | Decay Period |
|---|---|---|
| Login | 5 | 60 seconds |
| Forgot password | 3 | 3600 seconds |
| Reset password | 5 | 300 seconds |
| Confirm password | 5 | 300 seconds |
| Account recovery | 3 | 300 seconds |

Login uses composite key `login:{ip}:{email_hash}` to prevent attacker from rotating IPs.
All other endpoints use `auth-throttle:{ip}`.

### CheckRoleMiddleware

Route-level role verification. Accepts pipe-delimited role names:

```php
->middleware(['auth', 'role:super_admin|admin'])
```

Returns 403 for unauthorized users, redirects to login for unauthenticated requests.
All unauthorized attempts are logged via SmartLogger.

---

## Form Requests

### RoleRequest

Validates role and permission assignment operations. Enforces that:
- Only super_admin can assign the super_admin role
- No user can change their own role
- Reserved authoritative names are blocked for non-super-admin users

---

## Notifications

| Notification | Channel | Trigger |
|---|---|---|
| `AccountStatusNotification` | mail, database, broadcast | Account locked, unlocked, status changed |
| `SuperAdminRecoveredNotification` | mail, database | Super admin account recovered via CLI |
| `WelcomeNotification` | mail, database, broadcast | New account created |

All notifications implement `ShouldQueue` and use `CustomDatabaseChannel` for in-app delivery.

---

## Models

### AccountRecoveryCode

| Column | Type | Purpose |
|---|---|---|
| `user_id` | UUID FK | Owner of the recovery code |
| `code_hash` | string | Bcrypt hash of the recovery code |
| `generated_at` | timestamp | When the code was created |
| `used_at` | timestamp, nullable | When the code was consumed |
| `expires_at` | timestamp, nullable | Code expiry (null = never expires for recovery slips) |

---

## Policies

### UserPolicy

Gates user management operations:

| Method | Access |
|---|---|
| `viewAny`, `view` | All authenticated users (self) |
| `create` | Admin or super_admin |
| `update` | Owner or admin |
| `delete` | Super_admin only (blocks deletion of last super admin) |
| `lock`, `unlock` | Admin or super_admin |

---

## Dependency Graph

```
Auth Domain
├── Core      → BaseAction, BaseEntity, CacheKeys, SmartLogger, PasswordRules,
│                HandlesActionErrors, LabelEnum, StatusEnum, ColorableEnum
├── User      → User model (subject of authentication), Profile accessed
│                for identity confirmation during recovery
├── Setup     → SetupSuperAdminAction (recovery flow), FinalizeSetupAction
└── Admin     → AdminPromoteCommand (super admin promotion)
```

---

## Where to Find It

- `app/Domain/Auth/Enums/AccountStatus.php` — 8-state account lifecycle
- `app/Domain/Auth/Enums/Role.php` — role definitions with functional mapping
- `app/Domain/Auth/Entities/Apprentice.php` — account access checks
- `app/Domain/Auth/Entities/SuperAdminIntegrityRules.php` — super admin invariants
- `app/Domain/Auth/Actions/LoginAction.php` — 5-step authentication
- `app/Domain/Auth/Actions/LockUserAccountAction.php` — atomic lock with super admin protection
- `app/Domain/Auth/Http/Middleware/AuthThrottleMiddleware.php` — per-endpoint rate limiting
- `app/Domain/Auth/Http/Middleware/CheckRoleMiddleware.php` — route-level role gating
- `app/Domain/Auth/Policies/UserPolicy.php` — user management authorization
- `app/Domain/Auth/Notifications/` — notification classes
- `app/Domain/Auth/Livewire/` — auth UI components
- `docs/rbac.md` — RBAC design and role hierarchy
- `docs/account-recovery.md` — recovery mechanisms
