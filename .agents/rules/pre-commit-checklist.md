# Pre-commit Checklist

## Description

The final gate before every commit. Items marked "when requested" follow the on-demand policy in
[verification-strategy.md](verification-strategy.md).

---

- [ ] `declare(strict_types=1)` present
- [ ] No debug calls (`dd/dump/ray/var_dump/print_r/die`)
- [ ] All user-facing strings use `__()`
- [ ] Action uses correct triad base class
- [ ] Command/Process: DTO for 3+ params, returns ActionResponse
- [ ] Business rules delegated to Entity (not inline in Action)
- [ ] Cache keys registered in `config/cache-keys.php`
- [ ] No N+1 queries — eager loading verified
- [ ] No unescaped `{!! !!}` for user content
- [ ] `php artisan test --compact` passes (only when the user requests full verification)
- [ ] Every test traces to a spec requirement — no orphan tests, no padding (spec-driven testing)
- [ ] `vendor/bin/pint --dirty --format agent` clean
- [ ] Arch-guard scripts clean — `scan_violations.py`, `scan_class_contracts.py`, `scan_security.py`, `scan_naming.py`, `scan_conventions.py`, `scan_doc_links.py`
- [ ] `git status` + `git diff` reviewed — only intended files changed, nothing dropped
- [ ] Relevant docs updated (documentation-first approach)
