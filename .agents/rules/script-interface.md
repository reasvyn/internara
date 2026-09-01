# Script Interface — CLI Flags, Directory Layout & Output Path Convention

Every script in `tools/` exposes the same command-line interface so agents and CI can drive any
scanner without learning per-script quirks. A script that invents its own flags, ignores `--module`,
or writes reports somewhere unexpected breaks both the automation table in the skills and the
`arch-guard` quality-gate commands.

---

## Command Line Contract

```bash
python3 tools/{script_name}.py [OPTIONS]
```

**Required flags (all scripts MUST support):**

| Flag | Description | Default |
|------|-------------|---------|
| `--module`, `-m` | Target specific module (e.g., `Student`, `Academics`) | `null` (all) |
| `--output`, `-o` | Output file path | `tools/outputs/{timestamp}-{scan_name}.json` |
| `--format`, `-f` | Output format: `json`, `text`, `summary` | `json` |
| `--verbose`, `-v` | Include detailed context in findings | `false` |
| `--quiet`, `-q` | Only output summary, no findings | `false` |
| `--strict`, `-s` | Exit with code 1 on any finding | `false` |
| `--json` | Force JSON output to stdout (for piping) | `false` |

**Why every flag exists:**

- `--module` is what makes scans scopeable (S/M/L size triage by module). Without it an agent
  auditing one module must either scan the whole repo or hack around the script.
- `--output` lets CI and agents park reports where they need them instead of the default dir.
- `--format`/`--json` keep the human and the pipe happy: a human wants a summary table, `jq` wants
  raw JSON on stdout.
- `--quiet` is the batch/CI mode: no noise, exit code is the signal.
- `--strict` is the CI/CD gate: any finding flips the exit code to 1 so pipelines fail.

**Usage examples:**

```bash
# Full scan, auto-named output
python3 tools/scan_violations/cli.py

# Module-specific, strict mode
python3 tools/scan_violations/cli.py --module Student --strict

# Quiet summary only
python3 tools/scan_violations/cli.py --quiet

# Pipe to jq
python3 tools/scan_violations/cli.py --json | jq '.summary'
```

**Anti-patterns to avoid:** a script that silently ignores `--module`; a `--format` value not in the
enum; flags with different short/long spellings than the standard; an exit code that never changes.

---

## Directory Layout

```
tools/
├── scan_architecture.py      # Component counts, module stats
├── scan_class_contracts.py   # Action/Entity/DTO/Model/Enum contracts
├── scan_conventions.py       # strict_types, Fillable, debug, hardcoded strings
├── scan_dead_code.py         # Unused observers, DTOs, events
├── scan_doc_links.py         # Broken links in docs
├── scan_issues.py            # GitHub issue metrics
├── scan_naming.py            # Naming convention compliance
├── scan_security.py          # XSS, SQLi, mass assignment patterns
├── scan_tests.py             # Test pass/fail results
├── scan_violations.py        # C1-C8, D1-D6 violations
├── scan_files.py             # File inventory, LOC counts
├── outputs/                  # .gitignored
│   ├── .gitkeep
│   └── 20260711120000-violations.json
└── README.md                 # Human-readable script guide
```

**Why it matters:** a predictable `tools/` directory means the Automation-First discipline works —
an agent checks the table/documentation, finds the script, and runs it. A script living somewhere else
is invisible to that discipline, and an `outputs/` directory that is committed bloats the repo with
machine-generated artifacts.

**How to apply:** scripts are named `scan_{name}.py` for scanners; test files go in `tools/tests/`;
all reports land in `outputs/` (gitignored, `.gitkeep` committed); `README.md` documents every script.

---

## Output Path Convention

Default output path: `tools/outputs/{YYYYMMDDHHSSMS}-{scan_name}.json`

```python
from datetime import datetime
from pathlib import Path

OUTPUT_DIR = Path(__file__).parent / "outputs"

def default_output_path(scan_name: str) -> Path:
    timestamp = datetime.now().strftime("%Y%m%d%H%M%S")
    return OUTPUT_DIR / f"{timestamp}-{scan_name}.json"
```

**Why it matters:** the timestamped filename guarantees a unique report per run — old reports are
never silently overwritten, so an agent comparing two runs has both artifacts. The scan name embeds
which scanner produced it, so `outputs/` is searchable without opening files.

**Anti-patterns to avoid:** writing to the repo root or to a fixed non-timestamped name
(`report.json`); deleting an old report on each run (keep history for comparison, or clean up
explicitly).

---

## Verification / Detection

- Run `--help` and confirm all seven standard flags parse.
- Run with `--module {Module}` and confirm the scan is scoped (report `scan_type: module`,
  findings only from that module).
- Run `--json | jq '.summary'` and confirm valid JSON round-trips.
- Confirm `--strict` returns exit code 1 with findings and 0 without.
- Check `outputs/` for the timestamped file after a run.
