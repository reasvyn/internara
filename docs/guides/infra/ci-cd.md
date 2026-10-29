# CI/CD — Release Pipeline, Automation & Quality Gates

## Description

Continuous Integration and Deployment configuration for Internara, built around a single
tag-driven release pipeline (`.github/workflows/release.yml`) with four promotion stages,
reusable gate scripts under `.github/scripts/`, and comprehensive deployment safety mechanisms
including automatic rollback and release notes generation.

---


## Prerequisites

See [Installation](../installation.md#prerequisites) for full server requirements and verification commands.

## Steps

This document is reference-oriented. For installation and setup procedures, see:

1. [Installation](../installation.md) — server preparation and CLI provisioning
2. [Setup Wizard](../setup-wizard.md) — browser-based initial configuration
3. [Post-Setup](../post-setup.md) — initial data population after wizard completion

## Pipeline Overview

The whole pipeline is triggered by pushing a SemVer tag (`v*.*.*`) to GitHub. The stage is derived
from the tag suffix, following a two-tier release lifecycle: `development` (`vX.Y.Z-dev.N`) and
`production` (`vX.Y.Z`). Tags with `alpha`, `beta`, or `rc` suffixes indicate testing versions that
are not yet fully verified and run QA checks on CI without deploying to any host.

```mermaid
flowchart LR
    T[push tag v*.*.*] --> D{Detect stage}
    D -->|vX.Y.Z-dev.N| DEV[dev: validate + lint + build]
    D -->|vX.Y.Z-alpha.N| ALPHA[alpha: validate + lint + tests + build]
    D -->|vX.Y.Z-beta.N| BETA[beta: validate + lint + tests + build + audit]
    D -->|vX.Y.Z-rc.N| RC[rc: validate + lint + tests + guards + build + smoke + notes]
    D -->|vX.Y.Z| PROD[production: validate + all QA gates]
    PROD -->|pass| PROD_DEP[Deploy Production VPS<br/>internara.web.id]
    PROD_DEP -->|fail| PROD_ROLL[Auto-rollback]
```

### Stage mapping

| Pushed tag | Stage | Jobs run on GitHub Actions | Deploy Target |
| ------------------------ | ------------ | ------------------------------------------------------------ | --------------------------------------------- |
| `vX.Y.Z-dev.<N>` | Development (`dev`) | `validate` + `lint.sh` (Pint) + frontend build | None (CI only) |
| `vX.Y.Z-alpha.<N>` | Testing (`alpha`) | + `test.sh` (Pest) + frontend build | None (CI only milestone) |
| `vX.Y.Z-beta.<N>` | Testing (`beta`) | + `test.sh` (Pest, coverage gate) + `composer audit` | None (CI only milestone) |
| `vX.Y.Z-rc.<N>` | Testing (`rc`) | + `guards.sh` (arch + security + conventions) + `smoke.sh` + release notes | None (CI only milestone) |
| `vX.Y.Z` (final) | Release (`production`) | all of the above + release notes artifact | Production VPS (`internara.web.id`) |

Release stages operate in two tiers: `development (vX.Y.Z-dev.N) → release (vX.Y.Z)`. The `alpha`,
`beta`, and `rc` tags mark testing stages for code not yet fully verified. Only a final stable tag
deploys to production once all production QA gates pass.

### Versioning Rules & SSoT Contract

Internara enforces strict separation between repository configuration files and Git release tags:

1. **`composer.json` & `package.json` track ONLY stable versions:**
   - The `"version"` field in `composer.json` and `package.json` represents the current stable baseline release (e.g., `0.17.0`).
   - **Never commit or push pre-release or dev suffixes** (`-dev`, `-dev.N`, `-alpha`, `-beta`, `-rc`) into `composer.json` or `package.json`.
2. **Dev and Pre-release Versions are Git Tags only:**
   - Any interim development build or pre-release is created purely as a Git tag: `git tag vX.Y.Z-dev.N && git push origin vX.Y.Z-dev.N`.
   - The CI pipeline (`release.yml`) recognizes dev and testing stages from the tag pattern without modifying repository files.
3. **Validation Gate Enforcement:**
   - Stage `production`: `composer.json` must exactly match the release tag (`0.17.0` ↔ `v0.17.0`).
   - Stages `dev`, `alpha`, `beta`, `rc`: `composer.json` remains on the stable baseline and is not checked for pre-release suffixes.

### Hotfix branch — automated fast-track deploy

For critical fixes that must reach production immediately, pushing to the **`hotfix` branch** triggers
an automated fast-track workflow (`.github/workflows/hotfix.yaml`). It runs PHP syntax validation, Pint,
Prettier, and deploys directly to the production VPS with the standard health check and auto-rollback.
The fix must already be committed to `main` first so the branches do not drift.

| Aspect | Tag-driven release | `hotfix` branch |
| --------------------- | ----------------------------- | ---------------------------------- |
| Trigger | Push `v*.*.*` tag | Push to `hotfix` branch |
| QA | Full staged gates on CI | Fail-fast syntax + lint + format |
| Deploy | Via `deploy` job in `release.yml` | Via `deploy` job in `hotfix.yaml` |
| Version bump required | Yes | No |
| Rollback | Automatic-on-failure | Automatic-on-failure |
| Use for | Features, releases | Emergency production patches |

The automated flow:

```bash
# 1. Commit fix and push to hotfix (also keep main in sync)
git checkout hotfix && git merge main
git push origin hotfix

# 2. GitHub Actions (.github/workflows/hotfix.yaml) executes automatically:
#    - Job 'lint': validates syntax and code formatting
#    - Job 'deploy': SSH deploys hotfix with HEALTH_URL check and auto-rollback
```

Because `deploy.sh` builds from the checkout and gates on the 60s `HEALTH_URL` check, a
successful run guarantees the live site is serving the `hotfix` branch. Local quality gates still
apply — run `vendor/bin/pint --test`, targeted Pest tests, and arch scanners before
pushing. See [Deployment](deployment.md#hotfix-branch--automated-fast-track-deploy) for the
full procedure.

---

## Reusable gate scripts (`.github/scripts/`)

| Script | What it runs | Fail-fast order |
| ------------------- | ------------------------------------------------------- | --------------- |
| `lint.sh` | `vendor/bin/pint --test` | 1 (cheapest) |
| `test.sh` | `vendor/bin/pest --coverage --min=<MIN_COVERAGE>` (default 80) | 2 |
| `guards.sh` | `scan_violations` / `scan_security` / `scan_conventions` (all `--strict`) | 3 |
| `smoke.sh` | migrate + `route:list` on a clean SQLite DB (boot sanity) | 4 |
| `release-notes.sh` | Generate release notes from git log | — |
| `deploy.sh` | VPS-side: compose up with `.env.production` + prune + health check + auto-rollback | — |
| `backup.sh` | VPS-side: create backup metadata before deploy | — |
| `rollback.sh` | VPS-side: restore previous version and redeploy with `.env.production` | — |

---

## GitHub Actions workflow (`.github/workflows/release.yml`)

Single workflow, eight jobs:

1. **`stage`** — derives stage & version from the pushed tag (regex on the suffix: `dev` → dev;
   `alpha`/`beta`/`rc` → testing tag; no suffix → production).
2. **`validate`** — ensures `composer.json` version matches the tag, validates PHP syntax,
   `composer.json` schema, and runs npm audit. Runs for all stages (fail-fast).
3. **`dev`** — runs only when stage == `dev`: `lint.sh` + `npm run build` (with dependency caching).
4. **`alpha`** — stage == `alpha`: `lint.sh` + `test.sh` + `npm run build`.
5. **`beta`** — stage == `beta`: `lint.sh` + `test.sh` (with coverage) + build + `composer audit`.
6. **`rc`** — stage == `rc`: `lint.sh` + `test.sh` + `guards.sh` + build +
   `smoke.sh` + `composer audit` + release notes preview.
7. **`production`** — stage == `production`: all QA gates + release notes artifact upload.
8. **`deploy`** — `needs: [stage, validate, production]`, runs only when production QA succeeded: SSHs to
   the production VPS (`VPS_HOST`/`VPS_USER`/`VPS_SSH_KEY`), creates a backup, `git checkout $VERSION_TAG` +
   `git reset --hard $VERSION_TAG`, then `HEALTH_URL="https://internara.web.id" VERSION_TAG=$VERSION_TAG bash .github/scripts/deploy.sh`.
   Includes automatic rollback on failure.

Environment: PHP 8.5, SQLite in-memory for tests, `concurrency` grouped by tag to avoid parallel
runs for a re-pushed tag. Dependency caching enabled for both Composer and npm.

---

## Deployment Safety

### Automatic Rollback

The deploy process includes automatic rollback on failure:

1. **Before deploy**: `backup.sh` stores the current git revision and timestamp
2. **During deploy**: `deploy.sh` builds and starts containers, then waits for health check
3. **On failure**: If health check fails, `deploy.sh` automatically rolls back to the previous revision
4. **Manual rollback**: Run `ssh user@vps 'cd $HOME/app && bash .github/scripts/rollback.sh'`
   (or `cd $DEPLOY_DIR` when `DEPLOY_DIR` is set)

### Backup Retention

- Backups live in `<repo>/.backups/` and are **purged automatically once the new release passes its
  health check** — they only matter while a deploy is in flight.
- `KEEP_BACKUPS=<N>` on `deploy.sh` retains the `N` newest `backup_*.json` files (default `0` = purge
  everything). Scheduled database dumps under `storage/app/backup/*.sql.gz` are always purged.
- `backup.sh` itself keeps only the last 5 files per run (disk space management)
- Each backup contains: timestamp, git revision, tag, creation date

---

## Local Quality Commands

Same gates as CI, run locally:

```bash
vendor/bin/pint --test                        # PHP code style
vendor/bin/pest --coverage --min=80           # full test suite + coverage gate
npm run build                                 # Vite production build
vendor/bin/pint --test                      # PHP code style
vendor/bin/pest --coverage --min=80         # full test suite + coverage gate
python3 scripts/py/scan_violations.py --strict   # C1-C8 / D1-D6 invariants
python3 scripts/py/scan_security.py --strict     # security anti-patterns
python3 scripts/py/scan_conventions.py --strict  # conventions
npm run build                               # Vite production build

```

---

## Release workflow

See [Deployment](deployment.md) for the full VPS/CI/CD operational details.

### How to release (staged)

1. Bump `composer.json` `version` to `X.Y.Z` (and sync `package.json`, `README.md` badge,
   `docs/project-vision.md`, `docs/guides/upgrading.md`).
2. Push the final tag (or pre-release tags to run QA tiers first):

   ```bash
   git tag vX.Y.Z && git push origin vX.Y.Z
   # optional staged rollout:
   git tag vX.Y.Z-rc.1 && git push origin vX.Y.Z-rc.1
   ```

4. The pipeline runs QA; on a final tag, the deploy job ships `vX.Y.Z` to the VPS checkout.
5. Release notes are automatically generated and uploaded as artifacts.

### Secrets & Environment Configuration

Deploy secrets (`VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`) live in GitHub Actions secrets, never in the
repo. `deploy.sh` requires no credentials — the SSH key authenticates on the runner.

Runtime configuration for production lives in `.env.production` (or `.env`) on the VPS host
filesystem, never committed to git (strictly enforced by `.gitignore`). `deploy.sh` and `rollback.sh`
automatically detect `$DEPLOY_DIR/.env.production` and invoke Docker Compose with `--env-file .env.production`.

---

## Monitoring CI Health

- **Health Gate**: `deploy.sh` waits for `HEALTH_URL` (`https://internara.web.id`, product demo) to
  return 200 within 60s, else triggers automatic rollback.
- **Coverage**: measured per run via `--coverage --min`; no external coverage service required.
- **Diagnostics**: failed deploys surface in the runner logs with rollback instructions.
- **Artifacts**: Release notes are uploaded as build artifacts (retained for 90 days).

---

## References

- `.github/workflows/release.yml` — the pipeline definition
- `.github/scripts/*.sh` — reusable gates, deploy, backup, rollback & release notes scripts
- [Deployment](deployment.md) — environment setup, Docker stack, reverse proxy
- [Infrastructure](infrastructure.md) — tier-based infra design
- [Testing](testing.md) — test strategy & quality gates
---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
