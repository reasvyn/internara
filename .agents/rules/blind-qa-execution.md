# Blind QA Execution — Independence Doctrine

## Intent

The QA protocol is **blind**: it evaluates the codebase as if it were an unknown project being
reviewed for the first time, against purely external benchmarks (OWASP, ISO 25010, CWE/SANS, WCAG,
PSR, Laravel community practice). Project documentation, architecture docs, and module references
must **not** be loaded before or during execution. This rule is the independence doctrine — what
"blind" means, why it matters, and the ten key rules that govern the audit.

## Rationale

The value of a QA audit collapses if it inherits the project's blind spots. An internal auditor
naturally excuses internal conventions — "we know the CSP is off in dev", "the policy checks were
reviewed last month" — and evaluates patterns against the project's own rules. A blind audit cannot
do that: with no project-context loaded, a finding can only be judged against the external standard
it violates. That is the entire point of the protocol and the reason it exists *alongside* `arch-guard`
rather than replacing it:

| Aspect | `arch-guard` | `qa-protocol` |
|--------|--------------|---------------|
| Rule base | Project-defined invariants (C1-C8, D1-D6, naming) | Global industry standards (OWASP, CWE, ISO, WCAG, PSR) |
| Project knowledge | Required — checks the internal conventions | Forbidden — blind against external benchmarks |
| Output | Internal issues | GitHub Issues + compliance scorecard |

The failure mode of a non-blind audit is a self-confirming pass: a codebase can be internally
consistent and still fail every global standard — and nobody sees it.

## How to Apply — The Ten Key Rules

1. **Blind execution** — Do NOT load project documentation before or during execution.
2. **External standards only** — All findings reference external standards (OWASP, CWE, PSR, ISO,
   WCAG, etc.), never C1-C8/D1-D6.
3. **Evidence-based** — Every finding includes file path, line number, and concrete evidence.
4. **Severity follows CVSS** — Use the Common Vulnerability Scoring System for security findings
   (`rules/compliance-scorecard.md` §CVSS).
5. **No fixes during audit** — Record findings, create issues; fixes happen downstream.
6. **Blocker exception** — If a finding actively prevents the audit from running (e.g. the app will
   not boot), fix minimally first, then continue.
7. **Comprehensive scope** — Check every module, every route, every model — not just the changed code.
8. **Independent of project rules** — C1-C8, D1-D6, etc. are NOT part of this audit; only global
   standards are.
9. **Create Issues and commit** — Every audit must end with GitHub Issues created for each finding,
   skill files committed, and a summary report delivered to the user.
10. **Overlap transparency** — When a QA finding overlaps with an `arch-guard` finding, note the
    overlap in the issue body but still file independently.

## Examples

```text
GOOD (blind):
  Finding: "Stored XSS — user content rendered with {!! !!} in
    resources/views/document/handbook/show.blade.php:41.
    Violates OWASP A03 (Injection) / CWE-79."

BAD (not blind):
  Finding: "Violates D1 strict_types convention in CreateAssignmentAction."
  — That is an internal rule (arch-guard territory), nothing to do with a global standard.
```

A fully audited finding references the standard it violates and the evidence, and never leans on
"the project documented this behavior":

```text
Title: [QA] 🔴 Unescaped user content rendered in handbook view
Standard: OWASP A03 — Injection / CWE-79
Evidence: resources/views/document/handbook/show.blade.php:41 — {!! $document->content !!}
Fix direction: Escape with {{ }} or sanitize the content before output.
Overlap: also filed internally by arch-guard (XSS scan) — filed independently here.
```

## Anti-Patterns & Pitfalls

- **Loading `docs/` "just for context"** — breaks blindness; findings start rationalizing internal
  decisions.
- **Scoring against project rules** — importing C1-C8/D1-D6 into severity ratings defeats rule 8.
- **Skipping the blind pass "because I know this codebase."** — the discipline is the method; the
  session counts as blind execution, not the recollection.
- **Fixing findings during the audit** — except the minimal blocker intervention of rule 6. Fixes go
  downstream.
- **Reviewing only diffs** — rule 7 demands full coverage: every module, route, and model, not the
  code that changed last week.

## Verification & Detection

- The session loads **no** project architecture/convention/module docs before or during the audit
  (only the `qa-protocol` skill itself and the standards it cites).
- Every filed issue names an external standard (OWASP category, CWE-ID, PSR, ISO characteristic,
  WCAG criterion) plus file:line evidence.
- The audit run ends with all three rule-9 artifacts: GitHub Issues, committed skill/rules files,
  and a user report.
