# Deploy Topology & Caveats — Operations Context

> **Last updated:** 2026-08-16 **Changes:** initial — moved from `docs/known-issues.md`; deploy flow now
> fires `build-and-deploy.yml` **directly** from the public `internara` repo (dispatch pipeline removed)

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
  aaPanel/BT nginx for `https://internara.web.id`.
- **Branch workflow:** code lives on `main`; production tracks `docker-deploy`. To ship, fast-forward
  `docker-deploy` to `main` and push — the pipeline handles the rest.

## Caveats

| Caveat | Detail |
| ------ | ------ |
| **`git reset --hard origin/docker-deploy` runs on every deploy** | Any manual change inside `~/apps/internara` on the VPS is silently destroyed. Never hand-edit files there; change the repo and push. |
| **Health check gates success** | The deploy is green only when `https://internara.web.id` returns 200 within 60s. |
| **Build context is a Git URL** | `GIT_URL=https://github.com/reasvyn/internara.git#docker-deploy` (VPS `.env`). The Dockerfile clones/bundles the repo at build time; changes in the running container do not persist unless stored in `storage_data` / `app_data` / `mysql_data` volumes. |
| **Build cache bloat** | `deploy.sh` prunes images and builders, keeping the build cache under a `--keep-storage` limit (currently 2g) so the VPS disk stays healthy. |
| **Site reachable check** | `https://internara.web.id` → 200; title `Home | Internara - Management System`. |

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
- [Deployment](../../docs/infrastructure/deployment.md) — full VPS/CI/CD operational details
- [Dockerfile](../../Dockerfile) + `docker-compose.yml` — multi-stage build, volumes, `NGINX_PORT`
