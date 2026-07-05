# Issue Quality — Completeness & Actionability Checklist

Checklist to ensure every GitHub Issue is actionable by a developer or AI agent without requiring
additional context.

## Structural Completeness

- [ ] Title: `{type}: {module}/{submodule} — {description}`
- [ ] Description: concrete problem statement (not solution)
- [ ] Scope & Impact: module, files, severity, priority
- [ ] Recommended Approach: at least 1 option (2 if trade-offs exist)
- [ ] Design Decisions: decision table + rationale (for technical issues)

## Actionability (Ready to Execute)

- [ ] Developer/AI agent can start working without asking questions
- [ ] Acceptance criteria defined (for feature/refactor)
- [ ] Steps to reproduce (for bugs) — reproducible
- [ ] Environment documented (for bugs)
- [ ] Relevant pattern docs linked
- [ ] File paths to be modified specified

## Quality Gates

- [ ] One issue = one concern (do not combine)
- [ ] Scope specific (not "fix enrollment module")
- [ ] Impact measurable (not "system is slow" but "query 3s → 200ms")
- [ ] No sensitive information (credentials, tokens)
- [ ] Label matches type: bug/enhancement/security/refactor/perf/test/docs/chore
- [ ] Check for duplicates with existing issues before submitting

## Destructive Patterns

- ❌ Issue combining bug + feature request
- ❌ Title too generic ("Fix bugs" — which bugs?)
- ❌ Description is just "please fix this" without details
- ❌ No recommended approach for technical issues
- ❌ Including credentials, API keys, or personal data
