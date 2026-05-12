# Account Lifecycle

**Event:** Managing user account status transitions throughout the system lifetime.

**Phase:** 1 — Foundation (cross-cutting, applies throughout all phases)

**Previous Event:** [User Creation](user-creation.md)

**Next Events:** [Student Registration](student-registration.md), [Period Closing](period-closing.md), [Account Archiving](account-archiving.md)

---

## Overview

Every user account follows a status lifecycle from creation to eventual archival. The status determines whether the user can log in, what they can access, and what transitions are possible. This lifecycle is enforced by the `AccountStatus` enum and its transition rules.

## Status Definitions

The `AccountStatus` enum defines 8 statuses: PROVISIONED, ACTIVATED, VERIFIED, PROTECTED, RESTRICTED, SUSPENDED, INACTIVE, and ARCHIVED. Each has defined login behavior, terminality, and allowed transitions. See the [System Lifecycle](system-lifecycle.md#entity-user-account-accountstatus) for the complete state machine.

## State Machine

```
                  ┌─────────────────┐
                  │   PROVISIONED   │
                  └────────┬────────┘
                           │
                    ┌──────▼──────┐
              ┌─────┤  ACTIVATED  ├─────┐
              │     └──────┬──────┘     │
              │            │            │
         ┌────▼────┐ ┌────▼────┐  ┌────▼────┐
         │SUSPENDED│ │ VERIFIED │  │ARCHIVED │
         └────┬────┘ └──┬─┬─┬──┘  └─────────┘
              │      ┌───┘ │ │ └───┐
              │      │     │ │     │
              │  ┌───▼──┐ │ │ ┌───▼────┐
              │  │INACTIVE└─┘ │RESTRICTED│
              │  └───┬──┘     └───┬──────┘
              │      │           │
              └──────┴───────────┘
                         │
                    ┌────▼────┐
                    │ARCHIVED │
                    └─────────┘

Protected (immutable) — cannot enter or leave this state via transitions.
```

## Events

### Event A: Account Activation (Provisioned → Activated)

**Trigger:** User logs in with a provisioned account and completes setup.

**Who:** The user themselves.

**Flow:**
1. Admin creates a user account for a student (status: PROVISIONED)
2. Student receives login credentials (email/username + temporary password)
3. Student logs in for the first time
4. System detects `setup_required = true`
5. Student is redirected to the profile setup page
6. Student sets their password and fills in required profile fields
7. System transitions status to ACTIVATED

### Event B: Account Verification (Activated → Verified)

**Trigger:** Admin approves the user after they have activated their account.

**Who:** Super Admin or Admin.

**Flow:**
1. User's status is ACTIVATED (claimed account, awaiting approval)
2. Admin reviews the user in the account lifecycle manager
3. Admin approves the user
4. System transitions status to VERIFIED
5. User gains full access to all permitted features

### Event C: Account Suspension (Verified → Suspended)

**Trigger:** Admin suspends a user for policy violations or investigation.

**Who:** Super Admin or Admin.

**Flow:**
1. Admin finds the user in the account lifecycle manager
2. Admin clicks **Lock Account** (or **Suspend**)
3. `LockUserAccountAction` executes:
   - Sets `locked_at` timestamp
   - Sets `locked_reason`
   - Transitions status to SUSPENDED
4. User cannot log in — sees a lockout message with reason

**Direct suspension from other states:**
- PROVISIONED → SUSPENDED (admin suspends before user claims)
- ACTIVATED → SUSPENDED (admin suspends before verification)

### Event D: Account Reinstatement (Suspended → Verified)

**Trigger:** Admin unsuspends a user after review.

**Who:** Super Admin or Admin.

**Flow:**
1. Admin reviews the suspended account
2. Admin clicks **Unlock Account** (or **Reinstate**)
3. `UnlockUserAccountAction` executes:
   - Clears `locked_at`
   - Clears `locked_reason`
   - Transitions status to VERIFIED
4. User can log in again

### Event E: Restriction (Verified → Restricted)

**Trigger:** Admin restricts a user's access.

**Who:** Super Admin or Admin.

**Flow:**
1. Admin transitions the user to RESTRICTED
2. User can still log in but with constrained access
3. Useful for users who should have limited system interaction

### Event F: Account Inactivity (Verified → Inactive)

**Trigger:** System detects extended non-use (automatic or manual).

**Who:** System (can be automated) or Admin.

**Flow:**
1. User has not logged in for an extended period
2. Status is transitioned to INACTIVE
3. User can still log in but sees a warning about extended inactivity

### Event G: Account Archival (Any → Archived)

**Trigger:** End of academic period, graduation, or formal deletion request.

**Who:** Super Admin or Admin.

**Flow:**
1. Admin selects users to archive
2. `DeleteUserAction` or status transition sets ARCHIVED
3. User cannot log in
4. All related records (logbooks, submissions, etc.) are preserved in the database
5. This is a **terminal** state — no transitions out

See [Account Archiving](account-archiving.md) for full details.

### Security: Account Clone Detection

The system periodically checks for duplicate accounts:

1. `DetectUserAccountCloneAction` analyzes login patterns
2. Compares IP addresses, device fingerprints, and login timestamps
3. Flags suspicious duplicate access patterns
4. Results are displayed in the account lifecycle manager

## Transition Validation

See the [System Lifecycle](system-lifecycle.md#entity-user-account-accountstatus) for the complete account state transition map. The `AccountStatus::canTransitionTo()` method enforces valid transitions and terminal states.

## User Manager: Toggle Status

The User Manager provides a quick toggle:

1. If current status is VERIFIED → transitions to SUSPENDED
2. If current status is anything else → transitions to VERIFIED

This toggle sends an `AccountStatusNotification` to the affected user.

## Password Reset

Admin-initiated password reset flow:

1. Admin finds the user
2. Clicks **Reset Password**
3. `ResetUserPasswordAction` generates a random 10-character password
4. Password is hashed and saved
5. Admin sees the temporary password in a toast notification (displayed once, cannot be retrieved)

## Seamless Connection

The account lifecycle is active throughout all phases:

- During **[Student Registration](student-registration.md)**, new accounts start as PROVISIONED
- During **[Operations](logbook-workflow.md)**, active users must have VERIFIED status
- During **[Period Closing](period-closing.md)**, accounts may be bulk-transitioned
- During **[Account Archiving](account-archiving.md)**, accounts reach their terminal ARCHIVED state
