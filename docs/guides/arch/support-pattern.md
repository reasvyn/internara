# Support Pattern — Static Utilities, Purity Rules & Boundaries

> **Last updated:** 2026-08-27 **Changes:** rewrite — integrate global standards (Utility Pattern, Pure Functions, Static Methods, Immutability) with anti-pattern table, Quick References

## Description

Defines the Support utility layer — purely static helper classes with minimal or no framework
dependencies. Grounded in **Utility Pattern**, **Pure Functions**, **Static Methods**, and
**Immutability** — all mapped to Internara's Support vs Service vs Action boundaries.

---

## Non-Negotiable

Hard rules. Violations are architecture violations.

1. **Public static methods only — no instance methods.** Support classes MUST use only `public static` methods. No `public function` instance methods. No constructor injection. This is the defining boundary between Support and Service.

2. **No constructor injection.** Support classes MUST NOT have `__construct()` parameters. If you need constructor injection, it is a Service — move it.

3. **No side effects.** Support classes MUST NOT write to the database, dispatch events, call Actions, manage transactions, or log to the activity log. They are pure transformations and helpers.

4. **Minimal framework dependencies.** MAY use static framework calls (`config()`, `__()`, `trans()`) but SHOULD prefer pure PHP where possible. MUST NOT depend on the Laravel container or instance facades.

5. **No domain business logic.** Support classes MUST NOT contain rules about internships, students, grades, enrollments, or other business concepts. Domain logic belongs in Actions + Entities.

6. **No database writes.** Persistence goes through Command Actions. Read-only Model queries from static methods are acceptable for simple lookups in module-level Support.

---

## How to Apply

### 1. Utility Pattern

Support classes implement the Utility pattern — a collection of related static methods that perform a single concern (color math, string masking, array manipulation). Each method is independently testable with no shared state.

### 2. Pure Functions

Ideally, Support methods are pure functions: same input → same output, no side effects. This makes them trivially testable and safe to call from anywhere without worrying about state contamination.

### 3. Static Methods Trade-offs

| Advantage | Disadvantage |
|-----------|-------------|
| No instantiation needed | Cannot be overridden (no polymorphism) |
| No shared state | Harder to mock in tests (but Laravel `App::mock()` works) |
| Trivially testable | No constructor injection (by design) |
| Clear intent (utility) | Tight coupling to class (but that's OK for utilities) |

### 4. When to Choose Support Over Action

- The operation is a pure transformation (color math, string masking, array manipulation)
- The operation has no side effects (no DB, no events, no transactions)
- The operation is used by multiple callers across modules
- The operation does not need authorization or logging

---

## Anti-Patterns

| You see... | It should be... | Violation |
|-----------|----------------|-----------|
| `SmartLogger` with instance methods in Support | Move to Service | Support/Service boundary |
| Support class with `__construct(private Cache $cache)` | Rename and move to Service | Constructor injection in Support |
| Support calling `CreateUserAction` | Remove — Support has no side effects | Support calls Actions |
| Support with `DB::table('users')->insert(...)` | Move to Command Action | Support writes to database |
| Support with `public function formatSomething()` | Convert to `public static function` | Instance method in Support |
| Support extending `Translator` or framework class | Move to Service | Framework class dependency |
| Support with domain rule (`isStudentEligible()`) | Extract to Entity + Action | Domain logic in Support |
| `Support/` mixing pure utilities with framework-heavy classes | Split: pure → Support, framework → Service | Mixed boundaries |

---

## Quick References

- `docs/conventions.md` §Support Boundaries — when Support is appropriate
- `docs/guides/arch/service-pattern.md` — Service vs Support decision tree
- `docs/guides/arch/action-pattern.md` — Action Triad reference
- `app/Core/Support/Color.php` — example of correct Support (pure PHP)
- `app/Core/Support/PiiMasker.php` — example of correct Support (pure PHP)
- [Helper Class Pattern](https://en.wikipedia.org/wiki/Helper_class) — static helper classes
- [Pure Functions](https://en.wikipedia.org/wiki/Pure_function) — no side effects
- [Static Methods](https://www.php.net/manual/en/language.oop5.static.php) — PHP static
- [Immutability](https://en.wikipedia.org/wiki/Immutable_object) — unchangeable state
