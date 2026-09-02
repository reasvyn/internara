# Deploy Topology & Caveats — Operations Context

## Description

How the app gets from a Git tag push to a live site, and the operational traps to avoid when working on
CI/CD, the VPS, or Docker. Read this before touching `.github/`, the Dockerfiles, or a release.

---

## Topology

- **Public repo (`internara`), push a SemVer tag** fires `.github/workflows/release.yml` — a single
  tiered CI/CD orchestrator. The stage is derived from the tag, and all QA stages run on GitHub
  Actions (no VPS load):
  - `vX.Y.Z-dev.<N>` → **development** — lint + frontend build
  - `vX.Y.Z-beta.<N>` → **testing/QA** — lint + full test suite + build
  - `vX.Y.Z-rc.<N>` → **staging/RC** — lint + tests + arch guards + build + smoke + security
  - `vX.Y.Z` (final) → **production** — all of the above, then the deploy job SSHs to the VPS
    (secrets `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`) and runs `.github/scripts/deploy.sh`.
- **Only the PRODUCTION stage deploys.** A final tag never reaches the VPS unless every QA stage
  passes. Releases are promoted upward: `development → testing → staging → production`.
- **Manual/hotfix deploys use the `hotfix` branch** (the legacy `docker-deploy` and `production` branches are gone — `production` was renamed to `hotfix`). From the VPS repo: ensure `git fetch` (refspec now `+refs/heads/*:refs/remotes/origin/*`), then
  `git checkout hotfix && git reset --hard origin/hotfix` (reset against `origin/hotfix`, not the local branch name — the local branch can be stale after a fetch), then
  `VERSION_TAG=hotfix bash .github/scripts/deploy.sh` (builds from `GIT_URL=...#hotfix`,
  applies compose limits from the local `hotfix` checkout, drops the app_data volume, awaits health).
  The VPS was historically detached at `v0.15.8` with a `docker-deploy`-only refspec — a manual deploy
  then silently reused the OLD compose limits; always verify `docker stats` limits after deploying.
- Each QA stage delegates to a reusable helper under `.github/scripts/`: `lint.sh` (Pint),
  `test.sh` (Pest with coverage gate), `guards.sh` (C1-C8 / D1-D6 + security + conventions scanners),
  `smoke.sh` (migrate + route:list on a clean SQLite DB).
- **VPS layout:** app source at `$HOME/apps/internara` (`$HOME = /home/andreas`, derived from the
  SSH user `VPS_USER`, not hardcoded); stack is 3 containers (`app`, `db` = mysql:8, `web` = nginx)
  with `NGINX_PORT=8080`; reverse proxy is host-level aaPanel/BT nginx for
  `https://internara.web.id` (product demo). The VPS checks out the pushed tag directly
  (`git checkout $VERSION_TAG && git reset --hard $VERSION_TAG`).

## Caveats

| Caveat | Detail |
| ------ | ------ |
| **Host `.env` feeds only vars referenced in `docker-compose.yml`** | Compose interpolates `${VAR}` strictly per the `environment:` mapping — an entry in host `.env` that is not mapped there never reaches the container (v0.15.2 incident: `APP_LOCALE=id`/`APP_TIMEZONE=Asia/Jakarta` sat inert in `.env` while the app ran `en`/UTC). New env vars must be added to the compose `environment:` block, then shipped through a release. |
| **Every release must bump `composer.json` AND create the matching `v*.*.*` tag** | `deploy.sh` builds `GIT_URL=https://github.com/reasvyn/internara.git#${VERSION_TAG}` and the VPS `git checkout $VERSION_TAG`. If the tag is missing or stale, the image rebuilds from the wrong/branch ref. Version bump guide: see AGENTS.md §Version Bump Guide. |
| **`app_data` volume freezes `/app/public` between deploys (2026-09-02 incident)** | The `app_data` volume mounts at `/app/public`. The entrypoint only seeds `/app` from `/opt/app-src` when `/app/artisan` is missing — which is never true (artisan comes from the image layer), so `public/` keeps the **first-boot** content forever. All gitignored `public/build/*` assets produced by a newer `npm run build` never reach the container, even though `/app/app` (image layer) updates fine. `docker volume rm -f` in `deploy.sh` silently no-ops while the old containers still reference the volume. **Fix (landed):** `docker/entrypoint.sh` now runs `cp -a /opt/app-src/public/. /app/public/` on every boot (merges, never deletes, so runtime uploads/branding survive). Immediate manual refresh: `docker exec internara-app-1 sh -c "cp -a /opt/app-src/public/. /app/public/"`. |
| **PHP-only fixes apply immediately, asset fixes don't** | Symptom of the same freeze: a PHP/blade fix deploys on rebuild (image layer), but a frontend fix (CSS/JS bundle) appears dead even though the image is correct — check `/opt/app-src/public/build/manifest.json` vs `/app/public/build/manifest.json`; if they differ, the volume is stale. |
| **`git reset --hard $VERSION_TAG` runs on every deploy** | Any manual change inside `$HOME/apps/internara` on the VPS is silently destroyed. Never hand-edit files there; change the repo, bump version, tag, and push. |
| **Health check gates success** | `deploy.sh` is green only when `HEALTH_URL` (`https://internara.web.id`, product demo) returns 200 within 60s; else `docker compose ps` is dumped for diagnosis. |
| **Build context is a Git URL** | `GIT_URL=...git#${VERSION_TAG}`. The Dockerfile clones/bundles the repo at build time; changes in the running container do not persist unless stored in `storage_data` / `app_data` / `mysql_data` volumes. `--no-cache` + volume-prune ensure the tag change is picked up. |
| **Build cache bloat** | `deploy.sh` prunes images and builders, keeping the cache under a `--keep-storage` limit (default 2g) so the VPS disk stays healthy. |

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Release `main` to production | Bump `composer.json` `version`, create `git tag vX.Y.Z`, `git push origin vX.Y.Z` — `release.yml` runs full QA then deploys |
| Ship a QA candidate first | Use pre-release tags: `vX.Y.Z-dev.N` (dev only), `vX.Y.Z-beta.N` (+ tests), `vX.Y.Z-rc.N` (+ guards/smoke) — promote upward to final `vX.Y.Z` |
| Add/change deploy logic | Edit `.github/workflows/release.yml` + `.github/scripts/deploy.sh`; keep `deploy.sh` idempotent and cache-aware |
| Diagnose a failed deploy | Check the `deploy` job logs for `deploy ok:` / health-check failures; confirm `VERSION_TAG` matches an existing pushed `v*.*.*` tag |

---

## Quick References

- `.github/workflows/release.yml` — the pipeline (stage detection, 4 QA jobs, deploy)
- `.github/scripts/*.sh` — reusable gates (`lint.sh`, `test.sh`, `guards.sh`, `smoke.sh`) + `deploy.sh` (VPS-side)
- [Deployment](../../docs/guides/infra/deployment.md) — full VPS/CI/CD operational details
- [Dockerfile](../../Dockerfile) + `docker-compose.yml` — multi-stage build, volumes, `NGINX_PORT`
