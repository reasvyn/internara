# Deliverable Quality — Measurable Completion Criteria

A deliverable is "done" only when it meets the measurable criteria below — file completeness, Action
quality, Model quality, feature completeness, and the final quality gate. These are completion
criteria, not aspirations: a slice that fails any item is not ready for review or merge, and shipping
it forces the next agent to inherit defects. Each item exists because a specific failure follows when
it is skipped.

---

## File Completeness — Every New File Is Convention-Complete

**What it enforces:** Each new PHP file carries `declare(strict_types=1)` (except migrations and
config), contains no debug calls, uses constructor property promotion with `protected readonly`
where appropriate, and declares explicit return types and parameter type hints on every method.

**Why it matters:** Strict types turn silent coercion bugs into loud type errors (D1). Debug calls
left in committed code (`dd`, `dump`, `ray`, `var_dump`, `print_r`, `die`) either break requests in
production or leak state to the response (D2). Missing return/parameter types make the codebase's
contracts unverifiable by PHPStan and force readers to guess the shape of every value. These are the
cheapest rules to satisfy at write time and the most expensive to discover later.

**How to apply:** Write each file with the header and signatures complete from the first pass:

```php
<?php

declare(strict_types=1);

namespace App\Enrollment\Actions;

final readonly class RegisterInternAction extends BaseCommandAction
{
    public function __construct(private QuotaService $quota)
    {
    }

    public function execute(RegisterInternData $data): ActionResponse
    {
        // ...
    }
}
```

**Pitfalls to avoid:**

- Omitting `declare(strict_types=1)` because "this file is simple" — D1 has no size threshold.
- Leaving a temporary `dump()` "to be removed later" — later never comes; remove it before commit.
- Skipping type hints to save typing — PHPStan and every future reader pay for it.

**Verification:** `vendor/bin/pint --dirty --format agent` is clean and `scan_conventions.py`
reports no strict-types or debug-call violations.

---

## Action Quality — Correct Triad Base and Structured I/O

**What it enforces:** Every Action extends the correct triad base (`BaseCommandAction` for
mutations, `BaseReadAction` for complex queries, `BaseProcessAction` for multi-step orchestration),
exposes exactly one public `execute()` method, accepts a DTO when there are 3+ parameters (C7),
returns an `ActionResponse` for structured feedback, uses `$this->transaction()` + `$this->log()` in
Command/Process Actions, and delegates business rules to the Entity — surfacing violations as
`RejectedException` (C8).

**Why it matters:** The triad base classes provide the transaction and logging scaffolding; choosing
the wrong base either loses that scaffolding or adds it where a read-only query has no transaction to
own. A raw positional parameter list of 3+ values is unreadable and unverifiable — the DTO names the
contract (C7). Throwing `RuntimeException` for a business rule violation (C8) is indistinguishable
from an infrastructure failure, so the component can no longer route the error message to the user.

**How to apply:**

```php
final readonly class CreatePlacementAction extends BaseCommandAction
{
    public function execute(CreatePlacementData $data): ActionResponse
    {
        $placement = PlacementModel::findOrFail($data->placementId);

        if ($placement->asEntity()->isFull()) {
            throw new RejectedException(__('enrollment.quota_exceeded'));
        }

        return $this->transaction(fn () => $this
            ->executeAndLog(
                fn () => PlacementModel::create($data->toModelArray()),
                'create_placement',
            ));
    }
}
```

**Pitfalls to avoid:**

- A mutation on `BaseReadAction`, or a query on `BaseCommandAction`.
- Multiple public methods "for convenience" — `execute()` is the single entry point.
- Passing a raw `array` for 3+ parameters to avoid writing a DTO (C7).
- Throwing `RuntimeException` for a quota/state violation instead of `RejectedException` (C8).

**Verification:** `scan_class_contracts.py` reports the Action as conformant; `scan_violations.py`
reports no C7/C8 violations.

---

## Model Quality — BaseModel, `#[Fillable]`, Factory, Entity Bridge

**What it enforces:** Every new Model extends `BaseModel` (or `BaseAuthenticatable`), declares
fillable attributes via the `#[Fillable]` attribute, carries the `HasFactory` trait with a
`newFactory()` method, and exposes an `as{Role}(): EntityType` bridge when the model has business
rules the UI or Actions need to evaluate.

**Why it matters:** `#[Fillable]` is the declared mass-assignment control (D4) and the arch scanner's
target; skipping it risks mass assignment of unlisted columns. `HasFactory` + `newFactory()` is what
lets Pest tests build the model with the module's factory instead of hand-written test data. The
`as{Role}()` bridge is the sanctioned way for the UI and Actions to reach the Entity's pure business
rules — without it, callers re-implement rule logic inline and drift from the Entity.

**How to apply:**

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['student_id', 'internship_id', 'status'])]
final class Placement extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): PlacementFactory
    {
        return PlacementFactory::new();
    }

    public function asIntern(): Internship
    {
        return Internship::fromModel($this);
    }
}
```

**Pitfalls to avoid:**

- `$fillable = [...]` property instead of the `#[Fillable]` attribute (D4 violation).
- Extending `Model` directly, losing `BaseModel` behavior.
- No factory, forcing tests to use `Model::create()` with ad-hoc arrays.
- Inline rule checks (`if ($placement->status === 'confirmed')`) in the component instead of an
  `asEntity()->rule()` call.

**Verification:** `scan_conventions.py` (Fillable) and `scan_class_contracts.py` (Model contract)
report clean; every component-side rule check routes through an Entity bridge.

---

## Feature Completeness — Tests, Translations, Routes, Authorization, Cache, Docs

**What it enforces:** A complete feature includes: tests (happy path + edge cases, each traced to a
spec requirement ID — never padded), translations in both language files, routes registered in the
correct `routes/web/{module}.php` (or `{submodule}.php`), authorization via a Policy or
`$this->authorize()`, cache invalidation (event-driven or explicit `Cache::forget()` with keys
registered in `config/cache-keys.php`), and docs updated.

**Why it matters:** Each item prevents a distinct failure class. Untested requirements are spec gaps
that surface in production. Missing translations render raw keys to half the users. Routes in the
wrong file break the module's URL contract and the doc-link scans that assert it. Unauthorized
endpoints are the top OWASP exposure. Stale cache is the classic "it works locally but the deployed
data is old" bug. Stale docs are the drift the next agent inherits.

**How to apply:** Use the build-order checklist (`rules/build-order.md`) as the completeness
backbone: tests are written per requirement at step 13, translations at step 14, routes at step 12,
authorization at step 9, and docs at step 1. Verify cache invalidation specifically: any Action that
mutates data a read path caches must forget or invalidate the affected cache keys.

**Pitfalls to avoid:**

- Writing only the happy-path test "to save time" — spec-named rejections are requirements too.
- Registering a route in a shared `web.php` instead of the module file.
- Forgetting cache invalidation entirely — the stale-data bug is invisible in tests that don't cache.
- Ship code first, docs later — documentation-first means docs move with code.

**Verification:** Every feature slice maps to the completeness list; the module test suite passes;
`scan_violations.py` and `scan_doc_links.py` report clean.

---

## Final Gate — The Feature Is Not Done Until the Gates Pass

**What it enforces:** Before the feature is handed off for review/merge, the quality gates pass:
the module (or full) test suite, Pint, and PHPStan. A deliverable that fails any gate is returned to
the responsible slice, not merged "with a known failure".

**Why it matters:** The gates are the objective completion proof the orchestrated pipeline promises
its consumers (pest-testing, sync-docs, reviewers). Merging past a failing gate imports a known defect
into the branch — the exact thing the Pre-commit Checklist and the §Agent Workflow verification strategy
exist to prevent.

**How to apply:** Run the change-type verification from `AGENTS.md` §Verification Strategy that
matches the feature (module suite or full suite once for new business logic), `vendor/bin/pint
--dirty --format agent`, and `vendor/bin/phpstan analyse --no-progress`. The full suite and full
PHPStan are on-demand — run them here (this is a feature-completion gate) or when the user asks.

**Pitfalls to avoid:**

- Skipping PHPStan "because the tests pass" — static analysis catches type and contract errors the
  happy-path tests never exercise.
- Running only Pint without the tests, or vice versa.
- Treating a gate failure as "not caused by my change" without investigating — pre-existing failures
  get fixed or filed (Pre-existing Defects — Fix or File), never silently tolerated.

**Verification:** `php artisan test --compact` (module-scoped at minimum) passes, Pint is clean, and
PHPStan reports no new errors; results are stated in the final report.

---

## References

| Topic                            | Asset                                        |
| -------------------------------- | -------------------------------------------- |
| Pre-commit checklist             | `AGENTS.md` §Pre-commit Checklist            |
| Verification strategy            | `AGENTS.md` §Verification Strategy           |
| Arch-guard scanners              | `.agents/skills/arch-guard/SKILL.md`         |
| Build order & staged verification | `.agents/skills/feature-building/rules/build-order.md` |
