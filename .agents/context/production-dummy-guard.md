# Production Dummy-Data Guard — Intentional Constraint

> **Last updated:** 2026-08-16 **Changes:** initial — moved from `docs/known-issues.md`, agent-adapted

## Description

Demo data is **deliberately blocked in production** at three independent layers. Do not weaken these
guards, do not "simplify" them, and do not try to seed dummy data by relaxing the production checks.

---

## The Three Guards

| Guard | Location | Behavior |
| ----- | -------- | -------- |
| `DummySeeder` | `database/seeders/DummySeeder.php` | throws `RuntimeException` when `APP_ENV=production` (dummy-data spec **NFR-S1**) |
| `SeedDummyDataAction` | `app/Setup/Installation/Actions/SeedDummyDataAction.php` | skips + logs when `APP_ENV=production` (installation spec **NFR-S13**, defense-in-depth **DD-10**) |
| `setup:install --with-dummy` | `app/Setup/Installation/Console/Commands/SetupInstallCommand.php` | never reaches the seeder on production installs |

These three layers are the product's defense-in-depth against accidentally seeding fake data into a
live school's database. Any change to them requires a spec amendment first.

## Why the production Docker image cannot seed

The runtime image is built with `composer install --no-dev`, so the generator toolchain is absent:

- `fakerphp/faker` is in `require-dev` — **not installed** in the image.
- `Tests\Support\DummyData` (the generator, `tests/Support/DummyData.php`) lives under the `Tests\`
  PSR-4 namespace which is **`autoload-dev` only** — not registered in the optimized production
  autoloader.
- The `composer` binary itself is **not present** in the runtime image (only in the builder stage).

---

## Safe procedure to fill demo data on a production VPS (demo instance)

Use this ONLY on a demo/staging VPS that must show populated data — never on a school's real
production database. The procedure seeds without touching the running stack or relaxing the guards:

1. Verify the app container and DB are healthy (`docker compose ps`).
2. Run a **throwaway one-off container** from the deployed `internara-app` image, attached to the
   compose network (project dir `internara`, network `internara_default`):
   - pass the DB + `APP_KEY` environment from the deploy `.env`
   - override `APP_ENV=local` **for that container only** (satisfies NFR-S1/S-13 without changing
     the deployment)
   - download the composer installer, run `composer install --no-scripts` (adds dev deps +
     regenerates the `Tests\` autoloader inside the ephemeral container layer)
   - run `php -d memory_limit=512M artisan db:seed --class=Database\Seeders\DummySeeder --force`
3. Use `--rm` — the container and its composer/vendor changes are discarded; **only the DB rows
   persist** (the generator wraps everything in a transaction).
4. Verify counts in MySQL (users, companies, internships, placements, registrations, documents).

The operational helper scripts live outside the repo (`/tmp/opencode/vps_runssh*.py` +
`/tmp/opencode/vps_seed_dummy.py`); they are ops tooling, intentionally **not** committed to the
repository because the repo's spec forbids production seeding.

## Demo accounts (deterministic, `config/dummy.php`)

- Admin: `admin@example.com`
- Teachers: `teacher1..4@example.com`
- Supervisors: `supervisor1..6@example.com`
- Students: `student1..24@example.com`
- Password (all): `password` (from `config/dummy.php` `password` key)

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Fill demo data on the production VPS | Use the one-off container procedure above; never relax the production guards |
| Touch `DummySeeder` / `SeedDummyDataAction` | Treat the three-layer guard as intentional; changes require a spec amendment first |
| List demo accounts | Read `config/dummy.php` — deterministic and documented in `config/dummy.php` `password` key |
| Understand why seeding fails in the image | Check `composer.json` (`require-dev`), `composer.json` `autoload-dev`, and the multi-stage `Dockerfile` |

---

## Quick References

- `database/seeders/DummySeeder.php` — production guard + entry point
- `app/Setup/Installation/Actions/SeedDummyDataAction.php` — `setup:install --with-dummy` path
- `tests/Support/DummyData.php` — the demo dataset generator (dev-only autoload)
- `config/dummy.php` — demo account configuration
- [Dummy Data Spec](../../docs/specs/3UOZP-dummy-data.md) — NFR-S1, FR-E2/E4/E5, UC-2
- [Installation](../../docs/guides/installation.md) — setup wizard + provisioning flow
