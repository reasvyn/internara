# Access Control (RBAC) & Lifecycle

## Roles

Defined in `App\Enums\Auth\Role`:

| Case | Value | Purpose |
|---|---|---|
| `SUPER_ADMIN` | `super_admin` | Infrastructure, global config, user lifecycle |
| `ADMIN` | `admin` | School-level management (School staff/principals) |
| `TEACHER` | `teacher` | Academic supervision (School faculty/Teachers) |
| `STUDENT` | `student` | Participants (High school students) |
| `SUPERVISOR` | `supervisor` | Industry evaluation (Company mentors/Field Supervisors) |

Domain note:
- **Student** maps to the **Mentee** domain in code.
- **Teacher** and **Supervisor** map to the **Mentor** domain in code.
UI and business rules use the specific role names above.

## Enforcement

| Layer | Mechanism |
|---|---|
| Routes | `Route::middleware(['role:super_admin\|admin'])` |
| Livewire | Policy or Gate checks before mutations |
| Actions | Authority verification over target data |

## Account Lifecycle

Defined in `App\Enums\Auth\AccountStatus`:

| Status | Description | Login? |
|---|---|---|
| `provisioned` | Created, awaiting user claim | No |
| `activated` | Claimed, completed setup, awaiting verification | Yes (limited) |
| `verified` | Fully operational | Yes |
| `protected` | System-critical (Super Admin), immutable | Yes |
| `restricted` | Functional but access-constrained | Yes (conditional) |
| `suspended` | Temporarily deactivated | No |
| `inactive` | Extended period of non-use | Yes (with warning) |
| `archived` | Logically deleted, retained for compliance | No |

Terminal states: `archived`, `protected` — cannot transition out.

## Security Principles

- **IDOR protection**: Every request verifies ownership of the target resource
- **Audit trail**: All role and permission changes logged via the audit system
- **Least privilege**: Users receive only permissions required for their role
