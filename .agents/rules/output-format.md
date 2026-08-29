# Output Format — JSON Schema & Output Quality Rules

> **Last updated:** 2026-08-17 **Changes:** extracted from SKILL.md — comprehensive rewrite

Every script MUST produce JSON conforming to one canonical schema. This is what makes scan findings
machine-consumable: `arch-guard` aggregates, `issue-writing` quotes, `spec-audit` classifies, and CI
parses — all against the same shape. A script that emits a custom JSON shape silently breaks every
downstream consumer.

---

## Canonical JSON Schema

```json
{
  "scan_version": "2.0.0",
  "scan_name": "violations",
  "scan_type": "full|module|targeted",
  "module": null,
  "timestamp": "2026-07-11T12:00:00+07:00",
  "execution_time_ms": 1234,
  "summary": {
    "total_checks": 100,
    "passed": 95,
    "failed": 5,
    "by_severity": {
      "critical": 0,
      "high": 2,
      "medium": 2,
      "low": 1
    }
  },
  "findings": [
    {
      "id": "RULE-001",
      "rule": "C1",
      "severity": "high|medium|low|critical",
      "category": "architecture|security|naming|convention|performance",
      "file": "app/Student/Livewire/StoreStudentForm.php",
      "line": 42,
      "column": 5,
      "message": "Human-readable description",
      "suggestion": "How to fix",
      "reference": "docs/guides/arch/action-pattern.md",
      "context": {}
    }
  ],
  "metadata": {
    "php_version": "8.4",
    "laravel_version": "13.0",
    "total_php_files": 650,
    "total_modules": 22
  }
}
```

**Field contract and why:**

| Field | Why it's required |
|-------|-------------------|
| `scan_version` | Lets consumers branch on schema changes; missing version = unversioned breakage |
| `scan_name` / `scan_type` / `module` | Identifies the scan identity and scope so a report file is self-describing |
| `timestamp` / `execution_time_ms` | Per-row provenance and performance telemetry (target <30s full scan) |
| `summary.total_checks / passed / failed / by_severity` | The gate numbers CI and the summary table render; severity buckets must total correctly |
| `findings[].id` | Unique per run — referenceable in conversation and issues |
| `findings[].rule` | Which rule family/id fired (C1, S2, ...) |
| `findings[].severity` | One of `critical|high|medium|low` — consistent prioritization |
| `findings[].category` | One of `architecture|security|naming|convention|performance|system` |
| `findings[].file` / `line` | Actionable — the developer knows exactly where to look |
| `findings[].message` / `suggestion` | Actionable — what the problem is and what to do |
| `findings[].reference` | Traceable — links to the authoritative doc for the rule |
| `metadata` | Runtime context (PHP/Laravel versions, corpus size) for repro |

---

## Output Quality Rules

| Rule | Rationale |
|------|-----------|
| Every finding MUST have file + line | Actionable — developer knows where to look |
| Every finding MUST have message + suggestion | Actionable — developer knows what to do |
| Every finding MUST have reference | Traceable — links to authoritative doc |
| Severity MUST be one of: critical, high, medium, low | Consistent prioritization |
| Category MUST be one of: architecture, security, naming, convention, performance, system | Consistent categorization |
| IDs MUST be unique per scan run | Referencee findings in conversations |

**Why these exist:** a finding with no `file`/`line` cannot be acted on; without a `suggestion` the
developer is told something is wrong but not how to fix it; without a `reference` the severity and
remediation cannot be verified against the authoritative doc. Uniqueness of `id` keeps two runs of the
same scan distinguishable (`C1-001` from run A is not `C1-001` from run B).

---

## Report Construction Contract

The report is built from typed dataclasses and serialized with `vars()`:

```python
@dataclass
class Finding:
    id: str
    rule: str
    severity: str  # critical | high | medium | low
    category: str  # architecture | security | naming | convention | performance
    file: str
    line: int
    column: int = 0
    message: str = ""
    suggestion: str = ""
    reference: str = ""
    context: dict[str, Any] = field(default_factory=dict)

@dataclass
class ScanResult:
    scan_version: str
    scan_name: str
    scan_type: str  # full | module | targeted
    module: str | None
    timestamp: str
    execution_time_ms: int
    summary: dict[str, Any]
    findings: list[dict[str, Any]]
    metadata: dict[str, Any]
```

**How to apply:** findings are stored as `Finding` objects and converted to dicts via `vars(f)` at
serialization. Never hand-assemble a parallel dict shape — the dataclass is the single source of shape
truth.

---

## Anti-Patterns / Pitfalls

- **Swallowing schema drift:** editing `scan_version` casually — consumers branch on it; bump only on
  real breaks and document them.
- **Mutating findings in place during build_report:** `summary.failed` must equal `len(findings)`, and
  `by_severity` must total `failed`. If these disagree, the report is self-inconsistent and CI gates
  render wrong numbers.
- **Hardcoding severities as strings that aren't in the enum** (`"warning"`, `"warn"`) — consumers
  classify by the four buckets; anything else crashes aggregation.
- **Missing file/line on a finding** to "keep the report smaller" — it breaks the actionable contract.
- **Writing to stdout AND a file with conflicting content** — `--json` must equal what lands in the
  file.

## Verification / Detection

```bash
python3 tools/{name}.py --json | jq '.summary'         # shape smoke test
python3 tools/{name}.py --json | jq '.findings[0]'     # field contract test
python3 tools/{name}.py --json | python3 -c "import json,sys; d=json.load(sys.stdin); assert sorted(d) == ['scan_version','scan_name','scan_type','module','timestamp','execution_time_ms','summary','findings','metadata']"
```

Check per-run `id` uniqueness, severity enum membership, category enum membership, and that
`summary.failed == len(findings)`.