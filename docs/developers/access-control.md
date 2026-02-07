# Identity & Access Management: RBAC Standards

This document formalizes the **Identity and Access Management (IAM)** framework for the Internara
project, adhering to **ISO/IEC 27001** (Access Control) and **ISO/IEC 29146** (Access Control
Framework). It defines the protocols for **Role-Based Access Control (RBAC)** to ensure system
security, data confidentiality, and administrative accountability.

> **Governance Mandate:** All roles and permissions must be traceable to the User Roles and
> administrative requirements defined in the authoritative **[Internara Specs](specs.md)**.

---

## 1. Authorization Philosophy: Least Privilege

Internara operates on the principle of **Least Privilege** and **Explicit Deny by Default**.

- **Least Privilege**: Users are granted only the minimum permissions necessary to execute their
  assigned domain functions.
- **Explicit Deny**: Access is denied unless a specific permission or role assignment is verified.
- **Modular Sovereignty**: Authorization logic is encapsulated within the module that owns the
  domain resource, utilizing framework-standard Policies.

---

## 2. Role Taxonomy & Traceability

Roles are defined in the `Core` module and mapped directly to the stakeholders identified in the
SSoT.

| Role                    | SSoT Stakeholder     | Primary Domain Function            |
| :---------------------- | :------------------- | :--------------------------------- |
| **Administrator**       | System Admin         | Full System Orchestration          |
| **Instructor**          | Instructor (Teacher) | Supervision, Mentoring, Assessment |
| **Staff**               | Practical Work Staff | Administration, Scheduling, V&V    |
| **Industry Supervisor** | Industry Supervisor  | On-site Feedback, Mentoring        |
| **Student**             | Student              | Journals, Attendance, Reporting    |

---

## 3. Permission Specification (ISO/IEC 29146)

Permissions represent granular capabilities and follow a strict **Module-Action** naming convention
to ensure semantic clarity.

- **Convention**: `{module}.{action}` (e.g., `attendance.report`, `journal.approve`).
- **Traceability**: Every permission must fulfill a specific functional requirement defined in the
  blueprint or SSoT.

---

## 4. Implementation Layers

### 4.1 UI Layer (Presentation Logic)

Authorization must be enforced at the UI level to prevent unauthorized interaction.

- **Directive**: Use `@can` for Blade templates and `$this->authorize()` within Livewire components.
- **Behavior**: Elements representing unauthorized actions must be suppressed from the interface
  entirely.

### 4.2 Service Layer (Business Logic)

The Service Layer provides the final defense against unauthorized execution.

- **Invariant**: Services must verify authorization before performing state-altering operations,
  especially for destructive actions (Delete/Force-Delete).

### 4.3 Policy Pattern (The Governance Layer)

Every domain model must be associated with a **Policy Class**. Policies encapsulate complex
authorization rules, including ownership checks and state-dependent access.

```php
public function update(User $user, Journal $journal): bool
{
    // Verification: Permission check AND ownership validation
    return $user->can('journal.update') && $user->id === $journal->user_id;
}
```

---

## 5. Configuration & Synchronization (Baselines)

Permissions are introduced through **Modular Seeders** to maintain a consistent security baseline.

### 5.1 Idempotent Seeding

Modules must define their permissions within a seeder class, utilizing the `PermissionService`
contract.

```php
namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Services\Contracts\PermissionService;

class AttendancePermissionSeeder extends Seeder
{
    public function run(PermissionService $service): void
    {
        $permissions = ['attendance.view', 'attendance.check-in', 'attendance.report'];

        foreach ($permissions as $name) {
            $service->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
```

### 5.2 Baseline Synchronization

Updates to the security baseline are performed via the native synchronization command:

```bash
php artisan permission:sync
```

---

_Security is a systemic invariant. By adhering to these RBAC standards, Internara ensures that
access control is disciplined, traceable, and compliant with international security frameworks._
