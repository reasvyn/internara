# Auth Domain

## Purpose

Auth is the security boundary — it controls who can access the system, what they can do, and 
how identity is proven and maintained over time. Every interaction with the application passes 
through Auth's gate. This domain encompasses authentication (verifying identity via email and 
password), authorization (roles and permissions that gate access across all domains), account 
lifecycle (a state machine governing what accounts can and cannot do at each stage), password 
management (change, reset, recovery), and account recovery mechanisms for users who have lost 
access. Without Auth, there is no way to distinguish between users, no way to protect data, and 
no way to audit who did what.

## Boundary

**In scope:** User authentication with email and password, password management (self-service 
change, email-based reset, admin-initiated reset), account recovery via recovery codes and 
recovery slips, role definition and permission management (five user roles plus two functional 
roles), account lifecycle state machine (PROVISIONED through ACTIVATED, VERIFIED, RESTRICTED, 
SUSPENDED, INACTIVE, to ARCHIVED, with the PROTECTED invariant), account lock/unlock with 
required reason logging, login attempt recording and rate limiting, clone account detection 
(detecting duplicate accounts by email, phone, or identifier), password confirmation for 
sensitive operations, middleware for route-level role enforcement, UserPolicy for authorizing 
user-level operations.

**Out of scope:** User identity data storage (User domain owns the User and Profile models with 
personal data), user CRUD outside of lifecycle management (Admin domain handles account creation 
and general CRUD), domain-specific authorization logic (each domain implements its own policies 
using Auth's role definitions as inputs), profile editing (User domain), audit log browsing 
beyond authentication events (Admin domain), GDPR compliance workflows (Admin domain).

## Key Concepts

**Authentication.** Users authenticate with their email address or username and password. The 
login process performs four validations in sequence: (1) user resolution — does the email or 
username exist; (2) account status check — does the account's current status permit login 
(PROVISIONED, SUSPENDED, ARCHIVED, and LOCKED accounts are blocked); (3) credential verification 
— does the password match the stored hash; (4) auto-lock protection — after 10 consecutive 
failed attempts, the account is automatically locked with reason `too_many_failed_attempts`.
Failed attempt counters are stored in cache for 1 hour and reset on successful login.

Every login attempt, successful or failed, is logged via SmartLogger with user ID, identifier,
and attempt count. A global `AuthThrottleMiddleware` limits all auth endpoints to 30 requests
per minute per IP, with per-endpoint inline rate limiters providing additional protection:

| Endpoint | Limit | Decay |
|---|---|---|
| Login | 5 attempts | 60 seconds |
| Forgot password | 3 attempts | 3600 seconds |
| Reset password | 5 attempts | 300 seconds |
| Confirm password | 5 attempts (NEW) | 300 seconds |
| Account recovery | 3 attempts | 300 seconds |

**Role-Based Access Control.** Five user roles define access levels: SUPER_ADMIN (unrestricted 
system access), ADMIN (operational management access), TEACHER (educational supervision), STUDENT 
(learner access), and SUPERVISOR (company-side oversight). Two functional roles — MENTOR and 
MENTEE — are derived from user roles rather than assigned directly. MENTOR resolves from 
TEACHER and SUPERVISOR roles; MENTEE resolves from STUDENT. This indirection decouples the 
mentoring subsystem from specific user roles, allowing a company supervisor and an academic 
teacher to both act as mentors without sharing a user role. Roles gate access at every layer: 
routes (CheckRoleMiddleware), Livewire components (authorization checks), policies (policy 
methods), and the UI (conditional rendering).

**Account Lifecycle.** User accounts progress through a carefully defined state machine. 
PROVISIONED: account created, awaiting activation — login blocked. ACTIVATED: email verified, 
basic access granted. VERIFIED: full identity confirmation completed, full access. From VERIFIED, 
accounts may transition to: RESTRICTED (limited access, warning issued, login still permitted but 
constrained), SUSPENDED (login blocked, requires admin action to resolve, reason recorded), 
INACTIVE (automatic state after prolonged inactivity, login blocked, can be reactivated by the 
user). The terminal state is ARCHIVED: data preserved, login permanently blocked, no recovery 
path. A special state, PROTECTED, applies to super admin accounts — it is immutable, ensuring 
at least one super admin account always exists in the system. Each transition has explicit 
preconditions and side effects.

**Account Recovery.** Three recovery mechanisms exist. See [Account Recovery](../account-recovery.md)
for complete documentation:
1. **Password Reset** — self-service flow via email link
2. **Recovery Slip** — admin-mediated offline codes for locked-out users
3. **Super Admin Recovery** — CLI-based recovery via `php artisan admin:recover`

**Password Management.** Multiple password workflows: self-service change (user knows current 
password, provides it for confirmation, sets new password), self-service reset (user forgot 
password, receives email link, sets new password), admin-initiated reset (admin sets a temporary 
password for any user, logged as admin action), and recovery slip redemption (user enters 
recovery code, unlocks account, sets new password). All password operations record the actor, 
target user, timestamp, method, and outcome in the audit log.

## Requirements

### User Stories

| Role | Story |
|------|-------|
| User | As a user, I want to log in with my email and password so that I can access the system |
| User | As a user, I want to reset my password via email so that I can regain access if I forget it |
| User | As a user, I want to confirm my password before sensitive operations so that my account stays secure |
| User | As a user, I want to recover my account via a recovery slip so that I can regain access if locked out |
| Admin | As an admin, I want to lock/unlock user accounts so that I can respond to security concerns |
| Admin | As an admin, I want to see account status history so that I can audit user lifecycle events |
| Admin | As an admin, I want to generate recovery slips so that users who lose email access can recover their accounts |
| Admin | As an admin, I want to manage roles and permissions so that users have appropriate access |
| System | As the system, I want to enforce the account state machine so that invalid transitions are impossible |
| System | As the system, I want to record all authentication attempts so that security incidents are traceable |

### Process Flow

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

PROTECTED: Immutable state — applies to super_admin accounts only.
           Cannot transition to any other state.
```

- **PROVISIONED**: Account created, awaiting activation — login blocked
- **ACTIVATED**: Email verified, basic access granted
- **VERIFIED**: Full identity confirmation, full access
- **RESTRICTED**: Limited access, warning issued, login permitted but constrained
- **SUSPENDED**: Login blocked, requires admin action, reason recorded
- **INACTIVE**: Automatic after prolonged inactivity, login blocked, user can reactivate
- **ARCHIVED**: Terminal — data preserved, login permanently blocked, no recovery
- **PROTECTED**: Immutable guarantee that at least one super_admin always exists

### Key Operations

| Action | Description |
|--------|-------------|
| `LoginAction` | Authenticates user with email and password |
| `ConfirmPasswordAction` | Confirms password for sensitive operations |
| `SendPasswordResetLinkAction` | Sends a password reset email |
| `ResetPasswordAction` | Resets password via email token |
| `UpdateUserPasswordAction` | Self-service password change |
| `ResetUserPasswordAction` | Admin-initiated password reset |
| `GenerateRecoverySlipAction` | Generates a single-use recovery slip for locked-out users |
| `RedeemRecoverySlipAction` | Redeems a recovery slip to unlock account and reset password |
| `LockUserAccountAction` | Locks a user account with a reason |
| `UnlockUserAccountAction` | Unlocks a previously locked account |
| `DetectUserAccountCloneAction` | Detects potential duplicate accounts |
| `UpdateRolePermissionsAction` | Updates role-permission assignments |

### Technical Reference

| Layer | Artifacts |
|-------|-----------|
| **Models** | `User` (via Auth domain's concern) |
| **Enums** | `AccountStatus` — 8 states with transition rules (see above); `Role` — 5 user roles (`SUPER_ADMIN`, `ADMIN`, `TEACHER`, `STUDENT`, `SUPERVISOR`) + 2 functional roles (`MENTOR`, `MENTEE`) |
| **Livewire** | `Login`, `RegistrationCenter`, `ForgotPassword`, `ResetPassword`, `ConfirmPassword`, `AccountRecovery`, `RecoverySlipManager`, `AccountLifecycleManager` |

## Dependencies

| Dependency | Reason |
|---|---|
| User | User model is the subject of authentication; Profile accessed for identity confirmation 
during recovery |
| Core | BaseAction for operations, BaseEntity for business rule encapsulation (Apprentice, 
RecoveryCodeState), SmartLogger for all authentication logging, StatusEnum contract for 
AccountStatus, LabelEnum contract for Role |
| Registration | RegistrationCenter Livewire component (guest registration route) |

## Important Rules

- Account lifecycle follows a strict state machine — invalid transitions are rejected at the 
domain logic level, not just the UI.
- ARCHIVED is terminal: login permanently blocked, data preserved indefinitely, no automated 
recovery path.
- PROTECTED accounts cannot transition to any other state — this guarantees at least one super 
admin always exists.
- Every login attempt must record IP address, user agent, and timestamp for security audit and 
incident investigation.
- Recovery codes expire after a configurable duration (default 24 hours) and are single-use — 
redeemed codes are invalidated.
- No user can change their own role through any interface — role changes require an authorized 
admin.
- Only super_admin can assign or revoke the super_admin role — enforced at the database seed 
level and the policy level.
- At least one super_admin account must always exist — deletion of the last super_admin is 
blocked by the domain logic.
- All authentication-related logging uses SmartLogger with higher retention priority than 
standard activity logs.
- All Livewire components return `: View` for type safety — `AccessManager`, `AccountRecovery`, 
`RecoveryCode`, and `RecoverySlipManager` were updated to match the existing pattern in 
`Login`, `ResetPassword`, `ConfirmPassword`, and `ForgotPassword`.
