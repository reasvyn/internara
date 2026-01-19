# Policy Patterns: Authorization Logic

In Internara, **Policies** are the primary mechanism for enforcing authorization. Every Eloquent
model **must** have a Policy. This guide outlines the standard patterns for implementing secure and
readable access control logic.

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

## 2. Pattern: Super Supervisor

Allowing a teacher or mentor to view or edit records for students assigned directly to them.

```php
public function view(User $user, Journal $journal)
{
    // Check if the user is the assigned academic supervisor
    $isSupervisor = $journal->registration->teacher_id === $user->id;

    return $user->can('journal.view') && $isSupervisor;
}
```

---

## 3. Pattern: The "Super Admin" Bypass

Super Admins bypass all policies. This is typically handled in the `AuthServiceProvider`'s `before`
method, but it's important to remember when designing your logic.

```php
Gate::before(function ($user, $ability) {
    return $user->hasRole('super-admin') ? true : null;
});
```

---

## 4. Best Practices

1.  **Keep it Thin**: Policies should only contain authorization logic. Complex queries to determine
    relationships should be moved to a Service.
2.  **Explicit Naming**: Match policy method names to standard CRUD actions (`view`, `create`,
    `update`, `delete`, `restore`, `forceDelete`).
3.  **Default to Deny**: If a condition isn't met, return `false`.

---

_Consistent use of Policies ensures that Internara's security posture remains strong as new features
are added. Never authorize an action directly in a Controller or Livewire component without a Policy
check._
