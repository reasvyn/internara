# Known Issues — Intentional States & Developer Notes

> **Last updated:** 2026-08-16 **Changes:** amend — deploy topology switched back to direct
> build-and-deploy workflow in this repo (private-repo dispatch removed); dummy account label
> (`admin@example.com` is admin, not superadmin); intentional production-guard/seed constraints,
> dependency pins, pre-existing scanner baselines

## Description

Catalog of deliberate design decisions, deployment constraints, and environmental quirks that
developers and operators must know before working on internara. Anything here is either **intentional
by design** (do not "fix" it without a spec change) or a **tooling/deploy caveat** (work around it,
don't repeat the failure).

---

## Production Dummy-Data Guard (Intentional)

The demo dataset is **deliberately blocked in production** at three independent layers — do not weaken
them:

| Guard | Location | Behavior |
| ----- | -------- | -------- |
| `DummySeeder` | `database/seeders/DummySeeder.php` | throws `RuntimeException` when `APP_ENV=production` (dummy-data spec **NFR-S1**) |
| `SeedDummyDataAction` | `app/Setup/Installation/Actions/SeedDummyDataAction.php` | skips + logs when `APP_ENV=production` (installation spec **NFR-S13**, defense-in-depth **DD-10**) |
| `setup:install --with-dummy` | `app/Setup/Installation/Console/Commands/SetupInstallCommand.php` | never reaches the seeder on production installs |

### Why the production image cannot seed

The runtime Docker image is built with `composer install --no-dev`, so it lacks everything the
generator needs:

- `fakerphp/faker` is in `require-dev` — **not installed** in the image.
- `Tests\Support\DummyData` (the generator, `tests/Support/DummyData.php`) lives under the
  `Tests\` PSR-4 namespace which is **`autoload-dev` only** — not registered in the optimized
  production autoloader.
- The `composer` binary itself is **not present** in the runtime image (only in the builder stage).

### Safe procedure to fill demo data on a production VPS (demo instance)

To populate a production-staging VPS with demo data **without** touching the running stack or the
guards:

1. Verify the app container and DB are healthy (`docker compose ps`).
2. Run a **throwaway one-off container** from the deployed `internara-app` image, attached to the
   compose network (project dir is `internara`, network `internara_default`):
   - pass the DB + `APP_KEY` environment from the deploy `.env`
   - override `APP_ENV=local` **for that container only** (satisfies NFR-S1/S-13 without changing the
     deployment)
   - download the composer installer, run `composer install --no-scripts` (adds dev deps + regenerates
     the `Tests\` autoloader inside the ephemeral container layer)
   - run `php -d memory_limit=512M artisan db:seed --class=Database\Seeders\DummySeeder --force`
3. Use `--rm` — the container and its composer/vendor changes are discarded; **only the DB rows
   persist** (the generator wraps everything in a transaction).
4. Verify counts in MySQL (users, companies, internships, placements, registrations, documents).

The operational helper scripts live outside the repo (`/tmp/opencode/vps_runssh*.py` +
`/tmp/opencode/vps_seed_dummy.py`); they are ops tooling, intentionally **not** committed to the
repository because the repo's spec forbids production seeding.

### Demo accounts (deterministic, `config/dummy.php`)

- Admin: `admin@example.com`
- Teachers: `teacher1..4@example.com`
- Supervisors: `supervisor1..6@example.com`
- Students: `student1..24@example.com`
- Password (all): `password` (from `config/dummy.php` `password` key)

---

## Deploy Topology & Caveats

- **Public repo (`internara`) push to `docker-deploy`** fires `.github/workflows/build-and-deploy.yml`
  directly: a `build` job verifies both Docker images compile, then a `deploy` job SSHs to the VPS
  (secrets `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`) and runs `.github/scripts/deploy.sh`.
- **VPS layout:** app source at `~/apps/internara` on branch `docker-deploy`; stack is 3 containers
  (`app`, `db`=mysql:8, `web`=nginx) with `NGINX_PORT=8080`; reverse proxy is host-level
  aaPanel/BT nginx for `https://internara.web.id`.
- **`git reset --hard origin/docker-deploy` is part of every deploy** — any manual change made inside
  `~/apps/internara` on the VPS is silently destroyed on the next deploy. Do not hand-edit files
  there; change the repo and push.
- **Health check:** the deploy pipeline considers the deploy successful only when
  `https://internara.web.id` returns 200 within 60s.
- **Build context is a Git URL:** `GIT_URL=https://github.com/reasvyn/internara.git#docker-deploy`
  (in the VPS `.env`). The Dockerfile clones/bundles the repo at build time; changes in the running
  container do not persist unless stored in the `storage_data`/`app_data`/`mysql_data` volumes.
- Full operational details: [Deployment](infrastructure/deployment.md).

---

## Dependency Pins & Tooling Quirks

### `symfony/console` pinned at 7.4.16 (downgrade, intentional)

`nunomaduro/collision 8.9.5` requires `symfony/console ^7.4.14 || ^8.1.1`. Upgrading `console` to
8.1.x cascades the whole `symfony/*` 8.0.x stack to 8.1.x (via `symfony/event-dispatcher <8.1`
conflicts), so the lockfile **downgrades `symfony/console` 8.0.15 → 7.4.16** — the same resolution
Dependabot PR #378 produced. Update it only when the surrounding symfony stack moves to 8.1.

### `codeload.github.com` unreachable from the dev machine

Composer package downloads (GitHub zipballs) hang from the developer's network. Workaround:
`composer update <pkg> --prefer-source` (clones via the git protocol, which works). Packagist
metadata and `git fetch/push` are unaffected.

### npm `ERESOLVE` peerOptional conflict

`prettier-plugin-blade@3.2.0` declares an optional peer `@prettier/plugin-php@^0.24.0`, but the root
project uses `^0.25.0`. Plain `npm install`/`npm update` re-resolution fails with `ERESOLVE`.
Workaround: `npm update --legacy-peer-deps` — the Dockerfile already runs
`npm ci --legacy-peer-deps`.

---

## Codebase Intentional States

- **Exception hierarchy defined twice:** `docs/specs/SE5Q9-*.md` (FR-E1–E7) and
  `docs/specs/89SRA-*.md` (FR-EH1–9) both describe exception contracts. `ExceptionsTest` is mapped
  to `89SRA`. Resolve the duplication in a spec pass before touching exception behavior.
- **Pre-existing arch-guard baselines (deferred, not regressions):**
  - `scan_violations.py` — 32 findings
  - `scan_security.py` — 11 findings (Blade templates)
  - `scan_conventions.py` — 232 findings (Blade templates)
  All are pre-existing and unrelated to recent work; fix in dedicated cleanup sessions.
- **Spec-ID convention:** specs are named `docs/specs/{XXXXX}-{description}.md` with a
  `> **Spec ID:** XXXXX` metadata line; the registry is `docs/specs/index.md`. Do not reintroduce
  sequential numbering.

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Fill demo data on the production VPS | Use the one-off container procedure above; never relax the production guards |
| Deploy the latest `main` to the VPS | Fast-forward `docker-deploy` to `main` and push (dispatch pipeline handles the rest) |
| Update a composer package on the dev machine | Add `--prefer-source` (codeload unreachable) |
| Run `npm update` on the dev machine | Add `--legacy-peer-deps` |
| Change exception behavior | First reconcile SE5Q9 vs 89SRA duplication, then update `ExceptionsTest` (mapped to 89SRA) |
| Add a new spec | Use the 5-char alphanumeric ID + register in `docs/specs/index.md` |

---

## Quick References

- `database/seeders/DummySeeder.php` — production guard + entry point
- `app/Setup/Installation/Actions/SeedDummyDataAction.php` — `setup:install --with-dummy` path
- `tests/Support/DummyData.php` — the demo dataset generator (dev-only autoload)
- `config/dummy.php` — demo account configuration
- [Deployment](infrastructure/deployment.md) — full VPS/CI/CD topology
- [Dummy Data Spec](specs/3UOZP-dummy-data.md) — NFR-S1, FR-E2/E4/E5, UC-2
