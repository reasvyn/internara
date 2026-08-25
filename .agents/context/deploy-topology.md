# Deploy Topology & Caveats — Operations Context

> **Last updated:** 2026-08-25 **Changes:** v0.15.3 — compose now passes APP_LOCALE/FAKER/TIMEZONE (env-wiring caveat added)

## Description

How the app gets from a Git push to a live site, and the operational traps to avoid when working on
CI/CD, the VPS, or Docker. Read this before touching `.github/`, the Dockerfiles, or `docker-deploy`.

---

## Topology

- **Public repo (`internara`), push to `docker-deploy`** fires `.github/workflows/build-and-deploy.yml`
  directly — no intermediate dispatch repo. The workflow has two jobs:
  1. `build` — verifies both Docker images compile (`docker/build-push-action` + gha cache).
  2. `deploy` — SSHs to the VPS (secrets `VPS_HOST`, `VPS_USER`, `VPS_SSH_KEY`) and runs
     `.github/scripts/deploy.sh`.
- **VPS layout:** app source at `~/apps/internara` on branch `docker-deploy`; stack is 3 containers
  (`app`, `db` = mysql:8, `web` = nginx) with `NGINX_PORT=8080`; reverse proxy is host-level
  aaPanel/BT nginx for `https://internara.web.id` (product demo).
- **Branch workflow:** code lives on `main`; production tracks `docker-deploy`. To ship, fast-forward
  `docker-deploy` to `main` and push — the pipeline handles the rest.

## Caveats

| Caveat | Detail |
| ------ | ------ |
| **Host `.env` feeds only vars referenced in `docker-compose.yml`** | Compose interpolates `${VAR}` strictly per the `environment:` mapping — an entry in host `.env` that is not mapped there never reaches the container (v0.15.2 incident: `APP_LOCALE=id`/`APP_TIMEZONE=Asia/Jakarta` sat inert in `.env` while the app ran `en`/UTC). New env vars must be added to the compose `environment:` block, then shipped through a release. |
| **Branch-push deploys resolve the tag from `composer.json`** | On `docker-deploy` branch pushes, `VERSION_TAG = v{composer.json version}`. The VPS then `git checkout` that tag — NOT the pushed commit. If `composer.json` is stale, the VPS checks out an old tag that may lack current scripts (v0.14.3 incident: checked out v0.14.0 → `.github/scripts/deploy.sh: No such file or directory` → exit 127). **Rule: every release must bump `composer.json` `version` AND create the matching `v*.*.*` tag on main before pushing `docker-deploy`.** Tag pushes (`v*.*.*`) bypass this and use the ref name directly. |
| **`git reset --hard origin/docker-deploy` runs on every deploy** | Any manual change inside `~/apps/internara` on the VPS is silently destroyed. Never hand-edit files there; change the repo and push. |
| **Health check gates success** | The deploy is green only when `https://internara.web.id` (product demo) returns 200 within 60s. |
| **Build context is a Git URL** | `GIT_URL=https://github.com/reasvyn/internara.git#docker-deploy` (VPS `.env`, product demo). The Dockerfile clones/bundles the repo at build time; changes in the running container do not persist unless stored in `storage_data` / `app_data` / `mysql_data` volumes. |
| **Build cache bloat** | `deploy.sh` prunes images and builders, keeping the build cache under a `--keep-storage` limit (currently 2g) so the VPS disk stays healthy. |
| **Site reachable check** | `https://internara.web.id` (product demo) → 200; title `Home | Internara - Management System`. |

---

## AI Agent Guides

| If you need to... | Do this |
| ----------------- | ------- |
| Deploy the latest `main` to the VPS | Fast-forward `docker-deploy` to `main` and push — `build-and-deploy.yml` handles the rest |
| Inspect the live stack | SSH to the VPS, `cd ~/apps/internara`, `docker compose ps` |
| Add/change deploy logic | Edit `.github/workflows/build-and-deploy.yml` + `.github/scripts/deploy.sh`; keep `deploy.sh` idempotent and cache-aware |
| Diagnose a failed deploy | Check the `deploy` job logs for `deploy ok:` / health-check failures; confirm `GIT_URL` matches `docker-deploy` |

---

## Quick References

- `.github/workflows/build-and-deploy.yml` — the pipeline (build + deploy jobs)
- `.github/scripts/deploy.sh` — VPS-side deploy script (compose up, prune, health check)
- [Deployment](../../docs/guides/infra/deployment.md) — full VPS/CI/CD operational details
- [Dockerfile](../../Dockerfile) + `docker-compose.yml` — multi-stage build, volumes, `NGINX_PORT`
