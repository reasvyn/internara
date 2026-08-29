# Compliance Scorecard — Measuring Against Global Standards

## Intent

The compliance scorecard is the audit's quantitative output: one table, per global standard, of the
score achieved and the notes explaining it. It converts the six phases of findings into a single
readable statement of *how compliant* the codebase is, so the user can triage by standard rather than
by issue list alone. It also fixes severity assignment — security findings follow CVSS v3.1.

## Rationale

A list of findings is informative but not comparable across audits or across standards. The scorecard
makes the result *scoreable*:

- **Comparability** — "OWASP 7/10 categories clean" means something repeatable on the next audit;
  a bare list does not.
- **Prioritized framing** — the scorecard highlights which standard is weakest, so triage effort goes
  to the biggest compliance gap, not the loudest issue.
- **Severity consistency** — without a shared severity baseline, two auditors assign different
  priorities to the same finding. CVSS v3.1 gives a single scale for every security finding.

The failure mode of skipping the scorecard is a report nobody can act on: findings are filed, but the
*hole* — the standard the codebase systematically fails — stays invisible.

## How to Apply

Deliver the scorecard in the final report (and in the user summary of Phase 6):

| Standard | Score | Notes |
|----------|-------|-------|
| OWASP Top 10 | X/10 categories clean | ... |
| CWE/SANS Top 25 | X/25 CWEs absent | ... |
| ISO 25010 | X/8 characteristics met | ... |
| PSR-1/4/12 | Pass/Fail | ... |
| WCAG 2.1 AA | X/11 criteria met | ... |
| Laravel Best Practices | X/Y checks pass | ... |

### ISO 25010 characteristic scoring

If scored per characteristic, assign 1–5:

| Score | Rating |
|-------|--------|
| 5 | Excellent — fully meets all sub-characteristics |
| 4 | Good — mostly meets; minor gaps |
| 3 | Adequate — meets core requirements; notable gaps |
| 2 | Poor — significant deficiencies |
| 1 | Very Poor — fails to meet requirements |

### CVSS v3.1 severity classification

Security findings get a severity from CVSS v3.1 — the authoritative scale, not an ad-hoc label:

| Rating | Score Range |
|--------|-------------|
| None | 0.0 |
| Low | 0.1 — 3.9 |
| Medium | 4.0 — 6.9 |
| High | 7.0 — 8.9 |
| Critical | 9.0 — 10.0 |

For web-application findings, a simplified approximation is allowed *only* as a triage aid:

```
Base Score ≈ Impact × Exploitability

Impact:     no data access 2.6 · limited 5.0 · full data 7.5 · full system control 10.0
Exploitability: requires auth 5.0 · special conditions 3.9 · no-auth trivial 8.0 · no-auth automated 10.0
```

## Examples

```markdown
## Compliance Scorecard
| Standard | Score | Notes |
|----------|-------|-------|
| OWASP Top 10 | 8/10 categories clean | A03 injection findings in handbook view; A07 weak lockout |
| CWE/SANS Top 25 | 22/25 CWEs absent | CWE-79, CWE-89, CWE-862 present |
| ISO 25010 | 6/8 characteristics met | Security & Performance short |
| PSR-1/4/12 | Pass | Pint clean |
| WCAG 2.1 AA | 9/11 criteria met | Contrast + keyboard focus gaps |
| Laravel Best Practices | 12/14 checks pass | Eloquent-in-Blade + $request->all() present |
```

## Anti-Patterns & Pitfalls

- **Severity by gut instead of CVSS** — assigning "High" to a 3.5-score finding inflates the report
  and misdirects triage.
- **Scoring rows the audit did not actually cover** — an untested standard must be marked as such,
  not estimated.
- **Overloading the scorecard with every finding** — the scorecard is the summary; the issues carry
  the detail.
- **Using the simplified CVSS calculator as the authoritative score** — it is a triage aid only;
  official scoring wins for a contested finding.

## Verification & Detection

- The report contains the full scorecard table, no standard missing.
- Each score reconciles with the phase findings (e.g. "8/10 OWASP clean" matches 2 categories with
  findings).
- Every security finding's severity maps to a CVSS v3.1 range.

```bash
# An audit without a scorecard is incomplete — the final report must include the table above.
```
