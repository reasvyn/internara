# Policy Patterns: Authorization Governance Standards

This document formalizes the **Authorization Policy Patterns** for the Internara project, adhering
to **ISO/IEC 29146** (Access Control Framework) and **ISO/IEC 27001** (Access Control). It defines
standardized logic for **Policy Enforcement Points (PEP)** to ensure consistent and context-aware
security across all domain modules.

> **Governance Mandate:** Authorization logic must strictly enforce the **User Roles** and
> administrative constraints defined in the authoritative
> **[Internara Specs](../internal/internara-specs.md)**.

---

## 1. Policy Enforcement Architecture

In Internara, **Policies** serve as the primary PEP for all domain resources. Every Eloquent model
must be associated with a Policy class to centralize and formalize access decisions.

- **Invariant**: Authorization checks must be performed at the earliest possible boundary (PEP)
  before any business logic is executed.
- **Protocol**: Controllers and Livewire components must utilize `$this->authorize()` or `@can` to
  invoke the associated Policy.

---

## 2. Standard Authorization Patterns

### 2.1 Pattern: Permission-Based Ownership (Default)
The system verifies that the subject possesses the required functional permission AND maintains
ownership of the specific resource.
- **Applicability**: Personal records (e.g., Student Journals, Personal Profiles).

```php
public function update(User $user, Journal $journal): bool
{
    // Verification: Capability (Permission) + Context (Ownership)
    return $user->can('journal.update') && $user->id === $journal->user_id;
}
```

### 2.2 Pattern: Hierarchical Supervision (Relational)
Allows subjects in supervisory roles (Instructor, Industry Supervisor) to access resources
associated with their subordinates (Students).
- **Applicability**: Mentoring logs, academic reports, and attendance records.

```php
public function view(User $user, Journal $journal): bool
{
    // Context-Aware Verification: Is the user an assigned supervisor for this record?
    $isInstructor = $journal->registration->instructor_id === $user->id;
    $isMentor = $journal->registration->industry_supervisor_id === $user->id;

    return $user->can('journal.view') && ($isInstructor || $isMentor);
}
```

### 2.3 Pattern: Administrative Override (Super-Admin)
The **Super-Admin** role maintains universal bypass capabilities to facilitate emergency system
orchestration and maintenance.
- **Implementation**: Defined via `Gate::before()` in the `AuthServiceProvider`.

---

## 3. Engineering Standards for Policies

### 3.1 Strict Typing Invariant
All policy methods must declare strict types for the `User` subject and the domain `Model` object.
Failure to use strict typing is considered an architectural defect.

### 3.2 Semantic CRUD Mapping
Policy methods must correspond 1:1 with the standard system actions:
- `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`.

### 3.3 Explicit Deny by Default
If any condition remains unsatisfied, the policy must return `false`. Silence or ambiguity in
policy logic is prohibited.

---

_By adhering to these standardized policy patterns, Internara ensures a resilient, context-aware,
and traceable authorization posture across its modular ecosystem._