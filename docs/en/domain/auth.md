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

**Authentication.** Users authenticate with their email address and password. The login process 
performs three validations in sequence: (1) credential verification — does the email exist and 
does the password match; (2) account status check — does the account's current status permit 
login (PROVISIONED, SUSPENDED, and ARCHIVED accounts are blocked); (3) rate limit check — has 
this IP or email exceeded the maximum consecutive failed attempts. Every login attempt, 
successful or failed, is recorded with IP address, user agent, timestamp, and outcome. Successful 
logins may trigger additional actions: redirecting first-time users to the setup wizard, flagging 
accounts with expired passwords, or challenging with additional verification if enabled.

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

**Account Recovery.** Two recovery mechanisms exist. Password reset: a self-service flow where 
users request a password reset email, click a time-limited link, and set a new password. This 
works when the user can still access their email. Recovery slips: when the user cannot access 
their email (locked out, email inaccessible), an admin generates a recovery slip containing a 
single-use, time-limited recovery code. The user presents this code to unlock their account and 
reset their password. Recovery slips are the offline-capable, admin-mediated fallback for 
complete access loss. Both mechanisms are fully audited.

**Password Management.** Multiple password workflows: self-service change (user knows current 
password, provides it for confirmation, sets new password), self-service reset (user forgot 
password, receives email link, sets new password), admin-initiated reset (admin sets a temporary 
password for any user, logged as admin action), and recovery slip redemption (user enters 
recovery code, unlocks account, sets new password). All password operations record the actor, 
target user, timestamp, method, and outcome in the audit log.

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
