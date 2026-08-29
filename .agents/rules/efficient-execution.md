# Efficient Test Execution — Batch, Scope, and Memory

## Intent

Run tests in the smallest scope that gives confidence, batch executions instead of running after
every change, and budget memory. Targeted commands are the default; the full suite is the exception.

## Rationale

Every test run costs time and RAM. The full suite is ~2GB RAM and 10-15 minutes; a single test class
is ~200MB and 5-60 seconds. **NEVER run tests after every individual change** — the cost compounds
linearly with edit count, and most edits do not touch the code path a given test group exercises. A
disciplined execution order keeps verification fast enough that it actually happens.

Two failure modes this prevents:

1. **Run-after-every-edit:** dozens of full-suite-equivalent runs per session, 10+ minutes each — the
   session drowns in test time and the agent starts skipping verification to finish.
2. **Wrong scope:** running the full suite for a change that only touches one class (waste), or
   running only a single test for a cross-module refactor (false confidence — integration risk is
   missed).

## How to Apply

### Targeted Test Commands

```bash
# Single test class (fastest)
php artisan test --compact --filter={ClassName}

# Multiple classes batched (use &&)
php artisan test --compact --filter="ActionResponse|BaseFormRequest|LangChecker" \
  && php artisan test --compact --filter="CertificateStatus"

# Run tests for a specific module
vendor/bin/pest --testsuite={ModuleName}   # Run tests for the specified module (replace {ModuleName})
# Run full suite (all modules)
php artisan test --compact                        # All tests
```

### Batch Execution Rule

**NEVER run tests after every individual change.** Follow this order:

1. Make ALL planned changes to ALL files.
2. Run `vendor/bin/pint --dirty --test` on every changed PHP file (quick syntax + style check).
3. Verify logic with tinker or artisan commands if possible.
4. Run targeted test(s) — only the affected test class(es).
5. Only if changes affect core infrastructure → run full suite.

### Test Memory Considerations

| Suite scope | RAM | Runtime |
|-------------|-----|---------|
| Full suite | ~2GB | 10-15 minutes |
| Feature suite | ~1.5GB | 8-12 minutes |
| Unit suite | ~500MB | 2-4 minutes |
| Single test class | ~200MB | 5-60 seconds |

Choose the smallest scope that gives confidence.

## Anti-Patterns & Pitfalls

- **Per-edit test runs:** the canonical waste (see Batch Execution Rule).
- **Scope creep downward:** running `--filter={ClassName}` when a cross-module refactor needs
  `--testsuite={ModuleName}` — isolated tests cannot see integration breakage.
- **Scope creep upward:** full suite for a single-method refactor — the added runtime buys no added
  signal.
- **`&&`-chaining multiple full-runs:** batching is efficient; chaining the *entire suite* twice for
  one change is not.
- **Forgetting the memory budget:** running module suites concurrently on a memory-limited box
  OOMs — run sequentially, batch by scope.

## Verification / Detection

- Track run scope against change type (see `rules/verification-strategy.md`): single method →
  filtered, cross-module → module suite, dependency bump → module suites, new core behavior → full
  suite ONCE.
- If a run takes longer than the scope implies (single class > ~60s), check for unintended full-suite
  includes or a broad `--filter` regex.
