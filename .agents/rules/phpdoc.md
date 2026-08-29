# PHPDoc Conventions — Supplement Native Types, Never Duplicate Them

## Intent

The project uses **PHP 8.4 native type hints as the primary documentation mechanism**. PHPDoc
**supplements** native types — it documents what the signature cannot express (business meaning,
side effects, exceptions). It must never duplicate what native types already say.

## Rationale

Native types (`string $name`, `ActionResponse` return) are enforced by the runtime and the compiler;
PHPDoc type annotations are neither — they are unenforced prose that rots. Every `@param string $x`
written next to a `string $x` signature is dead weight: it adds a second, unsynchronized copy of
the same fact that will eventually drift. Worse, a PHPDoc `@param` that disagrees with the real
signature actively misleads readers. The rule is: **type information lives in the signature; PHPDoc
carries what the signature cannot — the business question, the failure modes, the side effects.**

## How to Apply

### When to Use PHPDoc

| Situation | Required? | Tags |
|-----------|-----------|------|
| Action class | Yes | `@throws RejectedException` (list all business rule exceptions) |
| Entity business methods | Recommended | Brief description of the business question |
| Complex algorithm | Yes | Multi-line description of the approach |
| Non-obvious side effect | Yes | `@see` pointing to the listener/event |
| Bridge method (`as*Entity`) | Yes | `@see \App\{Module}\Entities\{Entity}` |
| Simple getter/property access | No | Native types are sufficient |

### When NOT to Use PHPDoc

- **Never** `@author`, `@version`, `@created`, `@package` — metadata lives in git.
- **Never** duplicate what native type hints already express (`@param string $name` when the
  signature is `string $name`).
- **Never** use PHPDoc as a substitute for proper typing.

### Format Rules

```php
/**
 * Brief one-line description for simple methods.
 */
public function execute(): ActionResponse
{
    // ...
}

/**
 * Multi-line description for complex methods.
 *
 * Explains the business context, side effects, or non-obvious behavior.
 * Use blank line between description and tags.
 *
 * @throws RejectedException when the record is in a terminal state
 * @throws RejectedException when a duplicate exists
 */
public function execute(CreateUserData $data): ActionResponse
{
    // ...
}
```

Rules:
- One-line for simple methods, multi-line for complex.
- No blank line between description and first tag in multi-line blocks.
- `@throws` lists **specific** exception types, not generic `Exception`.
- `@see` for cross-references to related classes.
- No `@param` / `@return` when native types are present.

## Anti-Patterns & Pitfalls

- **`@author`/`@version`/`@created`/`@package`:** authorship and versioning belong to git, not to
  the docblock — these tags duplicate git and go stale immediately.
- **Duplicate type annotations:** `@param string $name` beside `string $name`. Delete them; if the
  signature is not clear, improve the signature, don't annotate it.
- **Generic `@throws Exception`:** hides which failure modes callers must handle. List the concrete
  `RejectedException` cases (C8: business rules use `RejectedException`).
- **PHPDoc as type crutch:** writing `@return User|null` instead of typing the return as
  `?User`. Proper typing is the fix, not a docblock.
- **Blank line between description and tags in a multi-line block:** the convention is no blank line
  before the first tag (blank line *between* the description body and the tags block, as shown).

## Verification / Detection

- `vendor/bin/pint` — enforces docblock style (no blank line before tags, spacing).
- `vendor/bin/phpstan analyse` — catches `@throws`/`@param` that contradict native types.
- `python3 tools/scan_class_contracts.py` — verifies Action/Entity/DTO class contract annotations.
- Grep for forbidden tags: `grep -rn "@author\|@version\|@created\|@package" app/`.
- Grep for redundant tags: `grep -rn "@param \|@return " app/` and review each — any tag that merely
  restates the signature should be removed.
