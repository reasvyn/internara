# Policy Patterns: Authorization Logic

In Internara, **Policies** are the primary mechanism for enforcing authorization. Every Eloquent
model **must** have a Policy. This guide outlines the standard patterns for implementing secure and
readable access control logic.

> **Spec Alignment:** Authorization logic must enforce the **User Roles** defined in the
> **[Internara Specs](../internal/internara-specs.md)**. Only designated roles (Instructor, Staff,
> Industry Supervisor) may access specific resources.

---

## 1. Pattern: Permission + Ownership

This is the most common pattern. A user can only manage a resource if they have the system-wide
permission **AND** they are the owner of that specific record.

```php
public function update(User $user, Journal $journal)
{
    // Permission check AND Ownership check
    return $user->can('journal.update') && $user->id === $journal->user_id;
}
```

---

## 2. Pattern: Academic Supervision (Instructor & Industry Supervisor)

Allowing an **Instructor** or **Industry Supervisor** to view or edit records for students assigned
directly to them.

```php
public function view(User $user, Journal $journal)
{
    // Check if the user is the assigned academic supervisor OR industry mentor
    $isInstructor = $journal->registration->instructor_id === $user->id;
    $isMentor = $journal->registration->industry_supervisor_id === $user->id;

    return $user->can('journal.view') && ($isInstructor || $isMentor);
}
```

---

## 3. Pattern: The "Super Admin" Bypass

Super Admins bypass all policies. This is configured in the `AuthServiceProvider` but must be
considered when testing.

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

---

## 4. Best Practices

1.  **Strict Typing**: Always type-hint the Model and User in policy methods.
2.  **Explicit Naming**: Match policy method names to standard CRUD actions (`view`, `create`,
    `update`, `delete`).
3.  **Default to Deny**: If a condition isn't met, return `false`.

---

_Consistent use of Policies ensures that Internara's security posture remains strong. Never
authorize an action directly in a Controller or Livewire component without a Policy check._
