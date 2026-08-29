# Script Testing & Performance — Guarantee Correctness and Speed

A scanner that silently scans the wrong files is worse than no scanner — it produces clean reports
that certify false confidence. Scripts must be tested (at minimum manually against real modules) and
must stay fast enough to be run frequently. Slow or wrong scripts die from disuse.

---

## Manual Testing

Run the script through each behavior before integrating it into any skill:

```bash
# Test against a specific module
python3 tools/scan_{name}.py --module Student --strict

# Test output format
python3 tools/scan_{name}.py --json | jq '.summary'

# Test quiet mode
python3 tools/scan_{name}.py --quiet
```

**Why these three cases matter:** the module run proves scoping works; the `--json` pipe proves the
report matches the canonical schema and round-trips through JSON parsers; quiet mode proves the CI/batch
path produces no stdout noise.

**How to apply the `--strict` check:** run against a module with at least one known finding and verify
exit code 1; run against a clean module and verify exit code 0.

**Pitfall to avoid:** testing only against the full corpus — scoping bugs hide when the module filter
is never exercised.

---

## Automated Testing (Optional but preferred)

Place test scripts in `tools/tests/`:

```python
# tools/tests/test_scan_{name}.py
import subprocess
import json

def test_scan_produces_valid_json():
    result = subprocess.run(
        ["python3", "tools/scan_{name}.py", "--json"],
        capture_output=True,
        text=True,
    )
    assert result.returncode == 0
    data = json.loads(result.stdout)
    assert "summary" in data
    assert "findings" in data
```

**Why it matters:** automated tests turn scanner changes into a regression-checked activity. Run after
any edit to the script, before integrating into a skill, and as part of the quality gates. The suite
asserts the contract that downstream consumers (CI, `arch-guard`, `spec-audit`) depend on.

**How to apply:** test at least (a) valid JSON with `summary` and `findings`, (b) module scoping, (c)
`--strict` exit codes, (d) empty/no-crash on a missing module.

**Pitfall to avoid:** asserting only "it didn't crash" — verify the *content* (schema fields,
severity enum, id uniqueness) matches `output-format.md`.

---

## Performance Guidelines

| Guideline | Rationale |
|-----------|-----------|
| Pre-compile regex patterns | Avoid recompilation per file |
| Use `pathlib` over `os.path` | Consistent, readable |
| Stream large files | Don't load entire codebase into memory |
| Cache file reads when possible | Single scan may read same file multiple times |
| Target < 30s for full scan | Keep developer feedback loop fast |

**Why each guideline:**

- **Pre-compiled regexes:** as covered in `script-structure.md`, recompiling per file across ~650 files
  adds seconds of pure waste.
- **`pathlib` over `os.path`:** consistent join/glob semantics, no `os.path.join` chains that hide path
  intent.
- **Stream large files:** a single CSV export or migration dump should be processed line-by-line
  (`for line in file:`) rather than `.read_text()` into memory when the file can be huge.
- **Cache file reads:** if two scanners in the same script read the same file, read once and pass the
  content through, or memoize; redundant I/O doubles the run time.
- **< 30s full scan target:** a scanner that takes minutes is run only on request, defeating the
  Automation-First discipline of running it often. If a scan grows past the target, optimize the hot
  path before extending surface.

**Anti-patterns to avoid:** re-reading a file per regex pass; scanning files twice because two scanner
functions each run the full discovery; building a giant in-memory content blob for a full-corpus
analysis.

---

## Verification / Detection

```bash
time python3 tools/{name}.py --module Student       # scoping + speed smoke test
python3 tools/tests/test_scan_{name}.py             # automated suite, if present
```

Confirm: module filter actually reduces the finding/scan scope; full-scan wall time < 30s (or trending
toward it); no duplicate full-corpus reads within a single run; the automated suite (if present) passes
after every script edit.
