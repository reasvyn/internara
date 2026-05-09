# Access Control (RBAC) & Lifecycle

## Roles

Defined in `App\Enums\Auth\Role` (implements `LabelEnum`):

| Role | Domain | Purpose |
|---|---|---|
| `SUPER_ADMIN` | Admin | Infrastructure, global config, user lifecycle |
| `ADMIN` | Admin | School-level management |
| `TEACHER` | Mentor | Academic supervision |
| `STUDENT` | Mentee | Participants |
| `SUPERVISOR` | Mentor | Industry evaluation |

Labels are translatable via `__("permission::role.{$value}")`.

## Enforcement

| Layer | Mechanism |
|---|---|
| Routes | `->middleware(['auth', 'role:super_admin\|admin'])` — pipe-delimited OR |
| Livewire | Policy or Gate checks before mutations |
| Actions | Authority verification over target data |
| Policies | 20 policy classes using shared `BasePolicy`, `AuthorizesRoles`, `AuthorizesOwnership` |

## Account Lifecycle

`App\Enums\Auth\AccountStatus` defines the full state machine:

```
provisioned → activated → verified
                ↓           ↓
            suspended    restricted → inactive → archived
                ↓           ↓
            archived    suspended
```

| Status | Login? | Notes |
|---|---|---|
| `provisioned` | No | Awaiting user claim |
| `activated` | Limited | Awaiting verification |
| `verified` | Yes | Fully operational |
| `protected` | Yes | System-critical (Super Admin), immutable terminal |
| `restricted` | Conditional | Access-constrained |
| `suspended` | No | Temporarily deactivated |
| `inactive` | Yes (warning) | Extended non-use |
| `archived` | No | Terminal — logically deleted |

Key methods on `AccountStatus`: `allowsLogin()`, `isTerminal()`, `canTransitionTo()`, `validTransitions()`, `color()`, `label()`.

Super Admin users bypass all Gate checks via `Gate::before`.

## Security Principles

- **IDOR protection**: Every request verifies ownership of the target resource
- **Audit trail**: All role/permission changes logged via audit system
- **Least privilege**: Users receive only permissions required for their role