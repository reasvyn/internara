# Test Diagnosis — Quick Reference

## Reading Failure Output

```
FAILED  Tests\Core\Services\LangCheckerTest > `LangChecker with real…   
Expected: :step | Setup
To contain: Internara
at tests/Core/Services/LangCheckerTest.php:86
```

1. **Test class** → `Tests\Core\Services\LangCheckerTest` (namespace reflects `tests/Core/`)
2. **Test description** → `LangChecker with real…` (truncated, check full with `--verbose`)
3. **Expected vs actual** → `Expected: :step | Setup` / `To contain: Internara`
4. **File:line** → `LangCheckerTest.php:86`

## Run-Fix-Repeat Protocol

```bash
# Diagnose: run the exact failing test
php artisan test --compact --filter="LangCheckerTest"

# Fix the issue (edit source or test)

# Verify: run again
php artisan test --compact --filter="LangCheckerTest"

# Verify siblings if needed
php artisan test --compact --filter="LangChecker"
```

## Is It Pre-existing?

```bash
# Stash changes, switch to main, run test
git stash && git checkout main
php artisan test --compact --filter="FailingTest"

# If it fails on main too → pre-existing issue, NOT your change
# Restore your branch
git checkout - && git stash pop
```

If pre-existing: flag to user, do NOT fix unless asked.
