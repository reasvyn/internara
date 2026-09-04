# Version Bump Guide

> **Curated mandatory known context** — how to bump versions and release Internara. Read before releasing a new version.

## Version Bump Pre-flight Checklist

All places that mention `version` must be checked and kept in sync before bumping `composer.json:version`. Use this checklist, not a hard-coded list (grep to verify):

```bash
grep -r '"version"' --include="composer.json" --include="*.php" --include="*.md" | grep -E "0\.15|version"
# Check: composer.json, package.json, package-lock.json, config/app.php, AppInfo, docs/specs/index.md, docs/refs, README, .github/workflows
```

1. `composer.json:version` + `package.json:version` (+ lock) — primary
2. `config/app.php:version` (if set) + `AppInfo::version()` fallback — verify `config('app.version')` vs `composer.json`
3. `docs/specs/index.md` Build Order + `docs/refs/modules/**` (if versioned) — no hard `0.14.0` left
4. `README.md` badges / `docs/project-vision.md` — if versioned
5. `.agents/context/*` and `.agents/rules/*` — never hard-code the version number here; use `grep` above to find stragglers, then bump
6. After bump: `git tag vX.Y.Z` + `git push origin vX.Y.Z` (per `deploy-topology.md`), verify `git describe --tags` + `ssh internara-vps "git describe"`

## Release Flow

Tag-driven 4-stage pipeline:

| Tag pattern | Stage | QA gates |
|-------------|-------|----------|
| `vX.Y.Z-dev.N` | Development | lint + frontend build |
| `vX.Y.Z-beta.N` | Testing/QA | lint + full test suite + build |
| `vX.Y.Z-rc.N` | Staging/RC | lint + tests + arch guards + build + smoke + security |
| `vX.Y.Z` (final) | Production | all of the above, then deploy via SSH to VPS |

Only the **production** stage deploys. A final tag never reaches the VPS unless every QA stage passes.

---

*Source: AGENTS.md §Version Bump Guide. For deploy topology details, see `.agents/context/deploy-topology.md`.*
