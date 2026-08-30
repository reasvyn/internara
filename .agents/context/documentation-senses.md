# Documentation Senses

> **Curated mandatory known context** — doc drift detection, mismatch resolution, tier selection, history discipline, and link integrity. Read before writing or updating documentation.

## Documentation Senses

### Doc Drift Detection

Doc drift happens when code changes but docs don't. Detect it by asking:

| Question | How to check |
|----------|-------------|
| Does the doc's file listing match the actual directory? | `ls app/Modules/{Module}/Domain/{Domain}/` vs doc |
| Does the Actions table list all current Actions? | `find app/Modules/{Module}/Domain/{Domain}/Actions -name '*Action.php'` |
| Does the Entity description match the actual methods? | Read the Entity class |
| Do the enum cases in the doc match the code? | Read the Enum class |
| Do the migration descriptions match the actual migrations? | Check `database/migrations/` |
| Are the cross-references still valid? | Verify every `[text](path)` resolves |

### Mismatch Resolution — Git History First

When code and docs disagree — or a claim cannot be confirmed in either — the discrepancy may be an **unrecorded change**. Do NOT assume the code is the source of truth just because it runs, nor that the doc is authoritative just because it was written first. **Both can be stale.**

Before picking a side:

1. **Check git history** (`git log -p -- {file}`, `git blame {file}`) for the code and the doc to see when each last changed
2. **Look for the intent** — does a commit message explain the change (e.g., a refactor that moved a file, or an intentional behavior change that skipped the docs)?
3. **If a commit explains it**, update the other side to match the documented intent
4. **If neither side explains it**, treat it as a finding: report it, don't silently decide

Only trust a claim after confirming it against the codebase **and** git history.

### Tier Selection

| Content type | Tier | Example |
|-------------|------|---------|
| "Why does this module exist?" | Conceptual | `docs/refs/modules/{module}.md` |
| "What business rules govern enrollment?" | Conceptual | `docs/refs/modules/enrollment.md` |
| "Which files implement the Action?" | Reference | `docs/refs/modules/enrollment-reference.md` |
| "What's the table schema?" | Reference | `docs/refs/modules/enrollment-reference.md` |
| "Why did we choose Actions over Services?" | Conceptual (architecture) | `docs/guides/arch/action-pattern.md` |
| "What's the Action contract?" | Reference (architecture) | `docs/guides/arch/action-pattern.md` |

**Rule of thumb:** If it explains *why*, it's conceptual. If it explains *what* or *how*, it's reference.

### History Discipline (Git as Source of Truth)

No inline `Last updated` metadata in markdown files. Document freshness and change history are tracked via git:

```bash
git log --follow -- <file>      # history of a doc
git diff -- <file>              # what changed in this branch
git log --since="14 days ago" --oneline -- docs/
```

Write a descriptive commit message (`type(scope): desc`); do not duplicate it inside the file.

### Link Integrity

Before committing any doc change:
1. Every `[text](path)` resolves to an existing file
2. Every `[text](path#anchor)` matches an existing heading
3. No content is duplicated — cross-reference instead
4. `## Where to Find It` is the standard footer (not `## References`)

---
*Source: AGENTS.md §Documentation Senses. For doc-writing patterns, see `docs/guides/arch/modular-pattern.md` §19.*