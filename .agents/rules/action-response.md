# ActionResponse Factory Methods

Structured action results are created with the `ActionResponse` factory methods, never with
ad-hoc `new ActionResponse(...)`.

```php
ActionResponse::ok($data, 'Operation completed');
ActionResponse::created($model, '{Entity} created');
ActionResponse::updated($model, '{Entity} updated');
ActionResponse::deleted('{Entity} removed');
ActionResponse::error('Something went wrong', $errors);
```

**When to use which:**

- `ok()` — read results, non-mutating operations
- `created()` — after `Model::create()` in a Command Action
- `updated()` — after `Model::update()` in a Command Action
- `deleted()` — after soft/hard delete in a Command Action
- `error()` — validation failures, infrastructure errors

**Why it matters:** The factories encode the response's semantic (created vs updated vs ok) and
produce a consistent shape (success flag, typed data, errors, message) that Livewire components
render into flash cards and redirects. Callers and tests depend on that shape (e.g., `$result->success`
truth check, `$result->data` for the mutated model).

**How to apply:** In the Action, return the method matching the mutation kind. Read Actions use
`ok()` (or a typed return where no response is needed). Never build the object manually — the
factory keeps the contract consistent.

**Pitfalls to avoid:**
- Using `ok()` for a mutation that just occurred — pick `created/updated/deleted`.
- Passing raw arrays where the factory expects the model or a typed DTO list.
- Returning `ActionResponse::error()` for a domain rejection — business rejections throw
  `RejectedException` (C8).

**Detection:** `rg "new ActionResponse" app/` should return nothing; factories are the only
constructor call sites.
