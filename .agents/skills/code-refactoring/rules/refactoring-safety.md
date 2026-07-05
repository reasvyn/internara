# Refactoring Safety — Prevent Destructive Changes

Checklist to ensure refactoring does not break existing behavior.

## Safety Gates (Before Refactoring)

- [ ] Test suite passes: `php artisan test --compact`
- [ ] `git status` clean (start from clean slate)
- [ ] No `dd/dump/ray` in codebase
- [ ] If no tests exist for target code, write characterization tests first

## During Refactoring

- [ ] One concern per commit — do not mix refactor with feature fix
- [ ] After each step: compile/test, no more than 5 minutes without verification
- [ ] Strangler pattern: new code alongside old, verify equivalence, remove old
- [ ] Do NOT change public API signature in Action/Entity without updating all callers

## Verification (After Refactoring)

- [ ] Test suite passes (structural changes did not alter behavior)
- [ ] `vendor/bin/pint --dirty --format agent` — code style clean
- [ ] `vendor/bin/phpstan analyse --no-progress` — static analysis passes
- [ ] No new `TODO`/`FIXME` without date
- [ ] `declare(strict_types=1)` present in new files
- [ ] Imports sorted (Pint handles this automatically)
- [ ] Dead code cleaned up (unused imports, variables, methods)

## Destructive Patterns to Avoid

- ❌ Changing Action base class without updating all method signatures
- ❌ Moving Entity methods to Model (violates Entity purity)
- ❌ Removing event dispatch without checking for listeners
- ❌ Refactoring and feature fix in the same commit
- ❌ Altering behavior that is already tested
