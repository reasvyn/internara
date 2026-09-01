---
name: qa-protocol
description:
    'SDLC Phase: QUALITY GATE. Independent blind QA audit against global industry standards (OWASP,
    ISO 25010, CWE/SANS, WCAG, PSR, Laravel best practices). 6-phase protocol producing GitHub
    Issues and a compliance scorecard. No project-specific rules — purely external benchmarks.'
downstream:
    - issue-writing
    - code-refactoring
    - security-audit
---

# QA Protocol — Independent / Blind Quality Assurance

> **Prerequisite:** None. This skill is INDEPENDENT of project documentation. Do NOT load project
> conventions, architecture docs, or module references before executing. This is a blind test
> against industry standards, not project-specific rules.

## When to Activate

Use this skill for comprehensive quality assurance audits against global industry standards.
Activates during ANALYSIS phase or as a periodic quality gate. Completely independent from internal
audit protocols — this skill evaluates the codebase as if it were an unknown project being reviewed
for the first time.

**Key distinction from `arch-guard`:**

- `arch-guard` checks compliance with project-defined rules (C1-C8, D1-D6, etc.)
- `qa-protocol` checks compliance with global industry standards (OWASP, ISO 25010, CWE, etc.)
- This skill does NOT know or care about the project's internal conventions
- Findings are evaluated purely against external benchmarks

## Agent Workflow

This skill follows **6 phases**, each corresponding to a major quality domain. Execute phases in
order. Each phase can produce blockers — if a phase encounters a blocker that prevents execution,
fix it before continuing (minimal intervention only). Follow `AGENTS.md §Agent Workflow` for the
canonical 5-step pipeline (Understand → Plan → Implement → Verify → Summarize), **Size Triage** and commit format — this skill adds the 6-phase QA
protocol below — nothing else. See the Skill Rules section for the rule assets governing each phase
and standard.

```
PHASE 1: Automated Scanning
  → PHASE 2: Security Audit
    → PHASE 3: Quality & Reliability
      → PHASE 4: Standards Compliance
        → PHASE 5: Performance & Efficiency
          → PHASE 6: Report, Issues & Commit
```

**Size-aware:** classify the audit per `AGENTS.md §Agent Workflow` Size Triage. A full-project QA is **L** —
inform the user, propose a session plan (e.g., by module), and run each phase per session. Never
attempt all 6 phases on the entire codebase in a single pass.

## Phase Overview

| Phase                        | Goal                                                          | ISO 25010                                            | Time      | Rules                                                                                                            |
| ---------------------------- | ------------------------------------------------------------- | ---------------------------------------------------- | --------- | ---------------------------------------------------------------------------------------------------------------- |
| 1 — Automated Scanning       | Run static analysis, dependency audit, code style             | Maintainability, Security (partial)                  | 10-20 min | `dependency-audit`, `static-analysis`, `psr-standards`                                                           |
| 2 — Security Audit           | OWASP Top 10 (2021) + CWE/SANS Top 25                         | Security                                             | 30-60 min | `owasp-top10`, `sans-top25`, `cwe-sans`                                                                          |
| 3 — Quality & Reliability    | Error handling, logging, validation, auth, tests, duplication | Reliability, Maintainability, Functional Suitability | 30-60 min | `error-handling`, `logging`, `input-validation`, `authentication-authorization`, `test-coverage`, `code-quality` |
| 4 — Standards Compliance     | PSR, Laravel best practices, WCAG, session, crypto            | Maintainability, Usability, Portability              | 20-40 min | `psr-standards`, `laravel-best-practices`, `wcag`, `session-management`, `cryptography`                          |
| 5 — Performance & Efficiency | DB, caching, memory, frontend performance                     | Performance Efficiency                               | 15-30 min | `performance`                                                                                                    |
| 6 — Report, Issues & Commit  | Consolidate, file GitHub Issues, commit, report               | —                                                    | 15-30 min | `github-issues-output`, `compliance-scorecard`                                                                   |

Each phase's run steps, record-worthy data, and blocker table live in its rule asset — load it only
when the audit reaches that phase.

## Phase 6 — Report, Issues & Commit

The terminal phase consolidates findings, files issues, and reports:

1. **Consolidate** — deduplicate across phases (keep highest severity, note other phases);
   cross-reference `arch-guard` overlaps (file independently — QA perspective differs); assign final
   severity via CVSS mapping (`.agents/rules/owasp-top10.md` §Scoring).
2. **Create GitHub Issues** — one per finding using the `issue-writing` template: title
   `[QA] {severity_emoji} {title}`, labels `qa-audit`/`security`/severity, body with summary,
   affected standard (OWASP A01, CWE-79, PSR-12, WCAG 2.1.1), evidence (file:line), recommended fix
   direction, and overlap note. Batch via `gh issue create`; group related findings.
3. **Commit skill changes** — verify with `git status` + `git diff`, commit only intended files
   (`docs(qa-protocol): ...`).
4. **User report** — deliver the compliance scorecard + top findings summary (template:
   `.agents/rules/github-issues-output.md`).

**Phase 6 blockers:** `gh` CLI not authenticated → report findings directly (skip issue creation);
no findings at all → still report and commit (a clean audit is valuable).

## Key Rules

1. **Blind execution** — Do NOT load project documentation before or during execution
2. **External standards only** — All findings reference external standards (OWASP, CWE, PSR, etc.)
3. **Evidence-based** — Every finding includes file path, line number, and concrete evidence
4. **Severity follows CVSS** — Use Common Vulnerability Scoring System for security findings
5. **No fixes during audit** — Record findings, create issues; fixes happen downstream
6. **Blocker exception** — If a finding actively prevents the audit from running (e.g., app won't
   boot), fix minimally first
7. **Comprehensive scope** — Check every module, every route, every model — not just the changed
   code
8. **Independent of project rules** — C1-C8, D1-D6, etc. are NOT part of this audit; this audit uses
   only global standards
9. **Create Issues and commit** — Every audit must end with GitHub Issues created for each finding,
   skill files committed, and a summary report delivered to the user
10. **Overlap transparency** — When a QA finding overlaps with an `arch-guard` finding, note the
    overlap in the Issue body but still file independently

## Phase Context

| Role           | Skill                                                                |
| -------------- | -------------------------------------------------------------------- |
| **Upstream**   | `feature-building` (implementation), `arch-guard` (internal audits)  |
| **This skill** | **ANALYSIS** — independent blind QA audit                            |
| **Downstream** | `issue-writing` (file findings), `security-audit` (security overlap) |

## Skill Handoffs (Actionable)

| Condition                               | Action                                                  |
| --------------------------------------- | ------------------------------------------------------- |
| QA finding overlaps an internal finding | Note overlap in issue body; still file independently    |
| Findings produced                       | Load `issue-writing` to create structured GitHub Issues |
| Security-heavy findings                 | Load `security-audit` for deeper security analysis      |
| Audit is **L** size (full project)      | Split into per-module sessions; inform the user first   |

## Skill Rules

### Protocol

| Rule                                 | Asset                           | Applies when                                       |
| ------------------------------------ | ------------------------------- | -------------------------------------------------- |
| 6-phase execution order & blockers   | `.agents/rules/six-phase-protocol.md`   | Running any QA audit (Phase 1-6)                   |
| Blind QA execution doctrine          | `.agents/rules/blind-qa-execution.md`   | Every QA audit — before and during execution       |
| Compliance scorecard & CVSS severity | `.agents/rules/compliance-scorecard.md` | Scoring results and assigning severities (Phase 6) |
| GitHub issues output & report        | `.agents/rules/github-issues-output.md` | Filing findings, committing, reporting (Phase 6)   |

### Standard Coverage

| Rule                                   | Asset                                   | Applies when                           |
| -------------------------------------- | --------------------------------------- | -------------------------------------- |
| OWASP Top 10 (2021)                    | `.agents/rules/owasp-top10.md`                  | Phase 2 security audit                 |
| SANS Top 25 rankings                   | `.agents/rules/sans-top25.md`                   | Phase 2 CWE prioritization             |
| CWE/SANS taxonomy & severity           | `.agents/rules/cwe-sans.md`                     | Phase 2 CWE cross-reference            |
| ISO 25010 quality model                | `.agents/rules/iso25010.md`                     | Scoring characteristics (Phase 3-5)    |
| Authentication & authorization         | `.agents/rules/authentication-authorization.md` | Phase 3 auth checks                    |
| API security (rate limit, CSRF, CORS)  | `.agents/rules/api-security.md`                 | Any HTTP/API endpoint audit            |
| Cryptography                           | `.agents/rules/cryptography.md`                 | Phase 4 crypto checks                  |
| Session management                     | `.agents/rules/session-management.md`           | Phase 4 session checks                 |
| Error handling                         | `.agents/rules/error-handling.md`               | Phase 3 error checks                   |
| Logging & log hygiene                  | `.agents/rules/logging.md`                      | Phase 3 logging checks                 |
| Input validation                       | `.agents/rules/input-validation.md`             | Phase 3 validation checks              |
| Dependency audit                       | `.agents/rules/dependency-audit.md`             | Phase 1 `composer audit` / `npm audit` |
| PSR standards                          | `.agents/rules/psr-standards.md`                | Phase 1 & 4 PSR checks                 |
| Laravel best practices                 | `.agents/rules/laravel-best-practices.md`       | Phase 4 framework audit                |
| WCAG accessibility                     | `.agents/rules/wcag.md`                         | Phase 4 accessibility audit            |
| Performance & efficiency               | `.agents/rules/performance.md`                  | Phase 5 performance audit              |
| Code quality (complexity, duplication) | `.agents/rules/code-quality.md`                 | Phase 3 code-quality checks            |
| Test coverage quality                  | `.agents/rules/test-coverage.md`                | Phase 3 test audit                     |

## References

| Standard               | Source                                                 |
| ---------------------- | ------------------------------------------------------ |
| OWASP Top 10 (2021)    | https://owasp.org/Top10/                               |
| CWE/SANS Top 25 (2024) | https://cwe.mitre.org/top25/                           |
| ISO 25010              | ISO/IEC 25010:2023                                     |
| WCAG 2.1               | https://www.w3.org/TR/WCAG21/                          |
| PSR-1                  | https://www.php-fig.org/psr/psr-1/                     |
| PSR-4                  | https://www.php-fig.org/psr/psr-4/                     |
| PSR-12                 | https://www.php-fig.org/psr/psr-12/                    |
| CVSS v3.1              | https://www.first.org/cvss/v3.1/specification-document |
| Laravel Security       | https://laravel.com/docs/master/security               |
| PHP Security           | https://www.php.net/security/                          |
