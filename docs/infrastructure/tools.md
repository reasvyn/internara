# Developer Tools — Scripts Documentation

> **Last updated:** 2026-07-11 **Changes:** feat — initial documentation of 11 scan scripts

## Description

Python devtool scripts in `scripts/` for codebase scanning, validation, and metrics.
Each script is self-contained, accepts `--module` and `--output` flags, and produces
JSON output to `scripts/outputs/{timestamp}-{scan_name}.json` (gitignored).

Can be run standalone or piped to `jq`.

---

## Output Convention

All scripts follow the same output schema:

```json
{
  "scan_version": "1.0.0",
  "scan_name": "violations",
  "scan_type": "full|module",
  "module": null,
  "timestamp": "2026-07-11T12:00:00+07:00",
  "execution_time_ms": 1234,
  "summary": {
    "total_checks": 8,
    "passed": 6,
    "failed": 2,
    "by_severity": { "critical": 0, "high": 1, "medium": 1, "low": 0 }
  },
  "findings": [...],
  "metadata": {}
}
```

**Default output path:** `scripts/outputs/{YYYYMMDDHHMMSS}-{scan_name}.json`

---

## CLI Flags (All Scripts)

| Flag | Short | Description | Default |
|------|-------|-------------|---------|
| `--module` | `-m` | Target specific module (e.g., `Student`, `Auth`) | `null` (all) |
| `--output` | `-o` | Output file path | auto-generated |
| `--format` | `-f` | Output format: `json`, `text`, `summary` | `json` |
| `--verbose` | `-v` | Include detailed context in findings | `false` |
| `--quiet` | `-q` | Only output summary, no findings | `false` |
| `--strict` | `-s` | Exit with code 1 on any finding | `false` |
| `--json` | | Force JSON output to stdout | `false` |

**Examples:**

```bash
# Full scan, auto-named output
python3 scripts/scan_violations.py

# Module-specific, strict mode
python3 scripts/scan_violations.py --module Student --strict

# Quiet summary only
python3 scripts/scan_violations.py --quiet

# Pipe to jq
python3 scripts/scan_violations.py --json | jq '.summary'
```

---

## Scan Scripts — Architecture & Invariants

### scan_violations.py

**Purpose:** Detect C1-C8 and D1-D6 invariant violations.

| Rule | What it checks |
|------|---------------|
| C1 | No Model mutations in Livewire |
| C2 | No `app()->make()` / `resolve()` (service locator) |
| C3 | No `DB::raw()` / `whereRaw()` without bindings |
| C4 | No inline cache keys (should use `config/cache-keys.php`) |
| C5 | Entity forbidden imports (Actions, Services, Livewire, Controllers) |
| C6 | DTO forbidden imports (Models, Entities, Actions, Repositories) |
| C7 | Command/Process Actions with 3+ params not using DTO |
| C8 | `RuntimeException` instead of `RejectedException` in Actions/Entities |
| D1 | Missing `declare(strict_types=1)` |
| D2 | Debug calls (`dd`, `dump`, `ray`, `var_dump`, `print_r`, `die`, `exit`) |
| D4 | Missing `#[Fillable]` attribute on Models |

```bash
python3 scripts/scan_violations.py
python3 scripts/scan_violations.py --module Auth --strict
```

---

### scan_class_contracts.py

**Purpose:** Verify Action, Entity, DTO, Model, and Enum contracts.

| Category | What it checks |
|----------|---------------|
| Actions | Correct base class, `execute()` method, no `handle()`, single public method |
| Entities | `final readonly`, `fromModel()`, `toArray()`, forbidden imports |
| DTOs | `final readonly` extending `BaseData`, forbidden imports |
| Models | `#[Fillable]` attribute, `entity()` bridge, no business methods |
| Enums | Backing type, `label()`, `validTransitions()` for StatusEnums |

```bash
python3 scripts/scan_class_contracts.py
python3 scripts/scan_class_contracts.py --module Assessment
```

---

### scan_security.py

**Purpose:** Detect security vulnerabilities.

| Rule | What it checks |
|------|---------------|
| S1 | XSS — unescaped Blade output `{!! !!}` |
| S2 | SQL injection — raw query construction |
| S4 | CSRF — forms missing `@csrf` (skips Livewire forms) |
| S6 | Missing authorization on sensitive Livewire methods |
| S8 | Hardcoded secrets/tokens/passwords |
| S9 | File uploads without validation |

```bash
python3 scripts/scan_security.py
python3 scripts/scan_security.py --module Auth
```

---

### scan_naming.py

**Purpose:** Check file, class, method, and variable naming conventions.

| Category | What it checks |
|----------|---------------|
| File naming | Files match expected pattern for their layer (Actions, Entities, etc.) |
| Class naming | Classes match expected pattern for their layer |
| Anti-patterns | `handle()` in Actions, snake_case variables in PHP |
| Directory naming | PascalCase for all layer directories |
| Method naming | Action return types, Entity question methods return `bool` |

```bash
python3 scripts/scan_naming.py
python3 scripts/scan_naming.py --module Journals
```

---

## Scan Scripts — Code Quality

### scan_conventions.py

**Purpose:** Check basic coding conventions.

| What it checks |
|----------------|
| `declare(strict_types=1)` present |
| `#[Fillable]` attribute on Models (not `$fillable`/`$guarded`) |
| Debug calls in committed code |
| Hardcoded user-facing strings (missing `__()`) |

```bash
python3 scripts/scan_conventions.py
```

---

### scan_dead_code.py

**Purpose:** Detect unused code.

| What it checks |
|----------------|
| Observers not registered in `EventServiceProvider` |
| DTOs never imported by Actions |
| Events without listeners |
| Listeners without events |

```bash
python3 scripts/scan_dead_code.py
```

---

### scan_doc_links.py

**Purpose:** Validate all relative links in markdown files.

| What it checks |
|----------------|
| `[text](path)` resolves to existing file |
| `[text](path#anchor)` matches existing heading |
| No broken cross-references |

```bash
python3 scripts/scan_doc_links.py
```

---

## Scan Scripts — Metrics & Inventory

### scan_architecture.py

**Purpose:** Codebase architecture metrics — component counts per module.

| What it reports |
|-----------------|
| PHP files per module |
| Livewire components per module |
| Actions, Entities, Models, Enums per module |
| Total codebase statistics |

```bash
python3 scripts/scan_architecture.py
python3 scripts/scan_architecture.py --module Program
```

---

### scan_files.py

**Purpose:** File inventory — counts and lines of code per module.

| What it reports |
|-----------------|
| File counts by type (PHP, Blade, JS, CSS) |
| Lines of code per module |
| Total codebase size |

```bash
python3 scripts/scan_files.py
```

---

### scan_tests.py

**Purpose:** Run test suite and parse per-module results.

| What it reports |
|-----------------|
| Pass/fail counts per module |
| Test execution time |
| Failed test details |

```bash
python3 scripts/scan_tests.py
python3 scripts/scan_tests.py --module User
```

---

### scan_issues.py

**Purpose:** Fetch GitHub issues and summarize by module and severity.

| What it reports |
|-----------------|
| Open issues per module |
| Issues by severity label |
| Stale issues |

```bash
python3 scripts/scan_issues.py
```

---

## Adding New Scripts

**Quick checklist:**
1. Create `scripts/scan_{name}.py` following existing script structure
2. Accept standard CLI flags (`--module`, `--output`, `--format`, `--quiet`, `--strict`, `--json`)
3. Produce JSON output matching the standard schema
4. Output to `scripts/outputs/{timestamp}-{scan_name}.json`
5. Add entry to this document
6. Test: `python3 scripts/scan_{name}.py --module {Module}`
7. Commit: `chore(scripts): add {name} scan`

---

## Quick References

| Topic | Location |
|-------|----------|
| Architecture patterns | `docs/architecture/*.md` |
| Coding conventions | `docs/conventions.md` |
| Module index | `docs/modules/index.md` |
