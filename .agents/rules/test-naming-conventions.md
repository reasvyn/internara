# Test Naming Conventions — Spec-Traceable Descriptions

## Intent

Test descriptions are the visible contract between the suite and the spec. Every `test()` is named
with its full `{SpecID}-{ReqID}:` prefix so traceability is visible in test output, and tests are
grouped under a `describe("{SpecID}: ...")` block. The naming is what makes a suite
self-explaining: anyone reading the output can map each green dot to a spec requirement and each
failure to the requirement it broke.

## Rationale

A test named `it creates a resource` passes and fails in isolation from why it matters. When the
suite runs, the output must tell an agent or developer *which requirement* broke, so the fix can be
anchored to the spec (Spec-First Doctrine) instead of to a guess. Prefixing the full `{SpecID}-{ReqID}:`
into the description means:

- **Test output is auditable.** `pest --compact` output and CI logs show exactly which requirement
  each test covers.
- **Spec-audit is mechanical.** A bidirectional scan (requirement → test, test → requirement) can
  find gaps and orphans by parsing descriptions alone.
- **Archaeology ends.** No test exists whose "why" requires reading three files of history.

The cost of skipping the prefix is subtle: a suite that looks green can silently contain zero tests
for half its requirements.

## How to Apply

The convention is exact — prefix **every** `test()` with the full `{SpecID}-{ReqID}:`:

```php
test("{SpecID}-{ReqID}: Test description...", function () {
    // ...
});

test("{SpecID}-{ReqID}: Test description...", function () {
    // ...
})->throws(RejectedException::class);
```

- `{SpecID}` is the spec file ID (e.g. `YB7RG`).
- `{ReqID}` is the requirement ID inside it (`FR-12`, `NFR-3`, `UC-2`).
- Group related tests under `describe("{SpecID}: Test description...")`. The `describe` carries the
  spec ID plus a short human description; **each `test()` inside still prefixes the full
  `{SpecID}-{ReqID}:`** — the prefix on `test()` is never dropped.

## Examples

```php
describe("YB7RG: Authentication", function () {
    test("YB7RG-FR-12: Student can log in with valid credentials", function () {
        // ...
    });

    test("YB7RG-FR-13: Login rejects invalid password", function () {
        // ...
    })->throws(RejectedException::class);
});
```

A rejection expectation is expressed with the Pest `->throws()` modifier on the same test, so a
requirement that *defines a rejection* is one test, not two.

## Anti-Patterns & Pitfalls

- **Dropping the prefix inside `describe`.** "Because the describe already says the spec ID" — the
  prefix must still appear on each `test()`, or the per-test traceability (and any automated parse)
  is lost.
- **Vague descriptions.** `test("creates a resource")` — no spec ID, no requirement ID, no behavior
  statement. Rewrite as `test("YB7RG-FR-12: creates a resource when valid data is provided")`.
- **Requirement IDs without a spec anchor.** A prefix like `FR-12` with no spec context (or a
  made-up ID) breaks the mapping just as surely as no prefix.
- **Inlining the ID in the body only.** The ID belongs in the description, not just in a comment —
  the description is what test output and scanners see.
- **Mirroring implementation names.** Naming tests after methods (`CreateUserAction::execute`) hides
  the requirement; name after behavior the spec promises.

## Verification & Detection

- Every `test(` call in the tree matches `test("{SpecID}-{ReqID}: ...")`.
- Every `describe(` starts with `{SpecID}: ` and contains no unprefixed `test()` inside it.

```bash
# Tests without a spec-requirement prefix (candidate orphans)
rg -n 'test\("' tests/ | rg -v '^[^"]*test\("[A-Z0-9]{5}-(FR|NFR|UC)-'
```
