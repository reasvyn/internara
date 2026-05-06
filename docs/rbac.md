# Access Control (RBAC) & Lifecycle

## Roles

Defined in `App\Domain\Auth\Enums\Role`:

| Role | Value | Purpose |
|---|---|---|
| SuperAdmin | `super_admin` | Infrastructure, global config, user lifecycle |
| Admin | `admin` | School-level management (departments, settings) |
| Teacher | `teacher` | Academic supervision (verification, monitoring, assessment) |
| Student | `student` | Daily operations (attendance, logbooks, assignments) |
| Supervisor | `supervisor` | Industry evaluation and site visits |

Domain note: Students map to the **Mentee** domain in code. Teachers and Supervisors map to the **Mentor** domain. UI and business rules use the specific role names above.

## Enforcement

| Layer | Mechanism |
|---|---|
| Routes | `Route::middleware(['role:super_admin\|admin'])` |
| Livewire | Policy or Gate checks before mutations |
| Actions | Authority verification over target data |

## Account Lifecycle

Defined in `App\Domain\Auth\Enums\AccountStatus`:

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
