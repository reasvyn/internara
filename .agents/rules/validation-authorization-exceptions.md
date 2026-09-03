# Validation, Authorization & Exceptions — Entry-Point Enforcement

Every interactive entry point (Livewire component, Action, route) must validate input, authorize the
caller, and fail with the correct exception. These rules define where validation lives, how
authorization is declared, and which exception type means what — so the UI consistently renders a
useful message instead of a 500.

---

## Intent

Validation is owned by Form Objects and Entity `rules()` methods (never FormRequest); authorization
is enforced by Policies extending `BasePolicy` with a super-admin bypass via `Gate::before`; business
rejections throw `RejectedException` (C8) and specific exceptions exist for specific scenarios.

## Rationale — What Fails Without It

- **Validation split across layers** duplicates rules and diverges: a rule changed in a Form Object
  but not in the Action's expected DTO allows data past one gate and rejects at the other. A single
  Rule source (Form Object for UX rules, Entity `rules()` for shared domain rules) means one edit
  fixes every Consumer.
- **Authorization inline in components** (`if (auth()->user()->id === $x)`) is untestable, easy to
  get wrong for a second persona, and duplicates policy logic. A Policy names the ability once and a
  `$this->authorize()` call enforces it consistently.
- **Forgetting the super-admin bypass** blocks a `superadmin` from acting on a record they should
  always be able to manage — a painful manual grant workaround. A `Gate::before` that returns `true`
  for `superadmin` makes bypass automatic and consistent.
- **Business rejections as `RuntimeException`** surface as generic 500 errors, hiding the reason from
  the user (C8). `RejectedException` is the expected-outcome signal the UI catches and renders as a
  toast message. Using one catch-all `RuntimeException` for everything also removes the ability to
  handle specific scenarios (quota exceeded, already exists, not found) differently.

## How to Apply

### Validation

- **Livewire component** → Form Object (`Livewire\Form`) with `rules()` + `messages()`; validate via
  `$this->form->validate()`.
- **Shared domain validation** (used by Actions, or by multiple components) → Entity static
  `rules()`, or a dedicated Rules class when it spans multiple entities.

```php
// Form Object — UX validation with real-time feedback
final class InternForm extends \Livewire\Form
{
    public string $email = '';

    public function rules(): array { return ['email' => ['required', 'email', 'max:255']]; }

    public function messages(): array { return ['email.required' => __('intern.email_required')]; }
}
```

### Authorization — `BasePolicy` + super-admin bypass

- CRUD abilities → a Policy extending `BasePolicy`, named `{Role}{Resource}Policy` in
  `app/{Module}/{SubModule}/Policies/`.
- Livewire components call `$this->authorize('{ability}', $model)`.
- Register the global super-admin bypass once in a service provider:

```php
Gate::before(fn ($user) => $user->hasRole('superadmin') ? true : null);
```

Returning `null` (not `false`) lets other gates still decide for non-super-admins.

### Exceptions — `RejectedException` for business rules

```php
if (! $entity->canBePlaced()) {
    throw new RejectedException(__('placement.quota_exceeded'));
}
```

- **Business rules** → `RejectedException` (expected outcome with a user-facing message).
- **Specific scenarios** → specific exception types (e.g. `NotFoundException`) so callers can branch.
- The UI catches `RejectedException` first, then `Throwable`:

```php
try {
    $action->execute($data);
} catch (RejectedException $e) {
    $this->toast()->error()->send($e->getMessage());
} catch (\Throwable $e) {
    $this->toast()->error()->send(__('common.error'));
}
```

## Anti-Patterns & Pitfalls

- `Validator::make()` inside a Command Action for UI-visible rules — validation belongs at the entry
  point, not buried in the mutation.
- Authorizing with role checks scattered in the component instead of `$this->authorize()`.
- A `Gate::before` that returns `false` for non-admins — that disables every other policy; it must
  return `true` for super-admin and `null` otherwise.
- `throw new RuntimeException('not allowed')` in a business path — 500 instead of a toast (C8).
- Catching `RejectedException` inside the Action "to normalize" it — the Action must let it
  propagate (see `rules/architecture-rules.md` §Business Layer).

## Verification

- `python3 tools/scan_violations.py` (C8: `RejectedException` not `RuntimeException`) clean.
- Every Livewire mutation path validates before calling an Action; every destructively-scoped method
  calls `$this->authorize()` or a Policy.
- `docs/guides/arch/policy-pattern.md` §Base Policy and `docs/guides/arch/exception-pattern.md`
  §Usage are the authoritative sources.
