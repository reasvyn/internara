# Technical Debt Annotations

Debt annotations carry both intent and an owner/date — an annotation without them is noise that
cannot be actioned.

| Annotation | Meaning | Convention |
|-----------|---------|-----------|
| `TODO(username, YYYY-MM-DD): message` | Planned work | Include author and date |
| `FIXME(username, YYYY-MM-DD): message` | Known bug | Include author and date |
| `HACK` | Suboptimal code that works | Must explain why |
| `XXX` | Danger — fragile or risky code | Must explain the risk |

**Why it matters:** An undated `TODO` says *something needs doing* but never says *when it was
added* or *by whom* — so it can't be prioritized or tracked, and it silently rots. `HACK`/`XXX`
without an explanation commit future readers to reverse-engineering *why* the code is the way it is
(a reasoning trap). A dated, owned annotation is a tracked decision; an unowned one is a void.

**How to apply:**
- `TODO` / `FIXME` — always `TODO(username, 2026-08-17): what and why`; tie to a GitHub issue when
  the work matters.
- `HACK` — include the reason and the trade-off made (`HACK: caching ref result; TTL 60s to avoid
  recompute`).
- `XXX` — include the specific risk (`XXX: relies on input already normalized upstream`).
- Prefer a GitHub issue + a pointing annotation over annotation-only debt.

**Pitfalls to avoid:**
- `TODO: fix later` — no owner, no date, no issue (un-actionable).
- `FIXME` for an already-filed bug — duplicate tracking; reference the issue number instead.
- Leaving annotations behind in refactored code that no longer applies (`code-refactoring` keeps an
  eye on this).

**Detection:** `rg "TODO|FIXME|HACK|XXX" app/ tests/` during review; each hit must carry the
author/date (or an issue reference).
