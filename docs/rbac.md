# Access Control

Internara uses **role-based access control (RBAC)** powered by `spatie/laravel-permission`.

## Roles

| Role | Domain | Purpose |
|---|---|---|
| `super_admin` | Admin | System infrastructure, global configuration, user lifecycle |
| `admin` | Admin | School-level management |
| `teacher` | Mentor | Academic supervision and assessment |
| `student` | Mentee | Internship participants |
| `supervisor` | Mentor | Industry supervisors and evaluation |

Role labels are translatable via language files.

## Enforcement

| Layer | Mechanism |
|---|---|
| Routes | Middleware: `->middleware(['auth', 'role:super_admin|admin'])` |
| Livewire | Policy or Gate checks before mutations |
| Actions | Authority verification over target data |
| Policies | Policy classes using shared `BasePolicy` with `AuthorizesRoles` and `AuthorizesOwnership` traits |

Users with the `super_admin` role bypass all Gate checks via `Gate::before`.

## Account Lifecycle

User accounts follow a state machine with 8 statuses (PROVISIONED → ACTIVATED → VERIFIED → [RESTRICTED | SUSPENDED | INACTIVE] → ARCHIVED, with PROTECTED as an immutable status for super admins). See [Account Lifecycle](lifecycles/account-lifecycle.md) for the full state definitions and transition rules.

## Security Principles

- **IDOR protection** — every request verifies ownership of the target resource
- **Least privilege** — users receive only the permissions required for their role
- **Audit trail** — all role and permission changes are logged
