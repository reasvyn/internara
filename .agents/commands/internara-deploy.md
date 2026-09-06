---
name: internara-deploy
description: Deploy Internara via version tags — SemVer, release commit, staged rollout, VPS health. Use when deploy, release, version, tag, bump version, ship, or VPS is mentioned.
---

# Deploy Command — Version-Tag Driven Release (Internara)

Delegates to the `internara-deployer` agent (deploy specialist). Shipping is a **version tag**, not a
branch fast-forward: `git tag vX.Y.Z && git push origin vX.Y.Z` runs the staged QA in
`.github/workflows/release.yml` and the `deploy` job SSHs to the VPS. See
`.agents/context/deploy-topology.md` + `docs/guides/infra/deployment.md`.

Scope: $ARGUMENTS

If $ARGUMENTS is empty, ask what to release and confirm the SemVer class by reviewing changes since the
last released tag (`git describe --tags --abbrev=0`).

## Steps

1. **Determine X.Y.Z (SemVer).** Review `git log vX.Y.Z..HEAD --oneline --stat`; classify Major/Minor/
   Patch by the heaviest change (`docs/guides/upgrading.md` §7). Do not default to patch.
2. **Bump all version-mentioning docs in one commit** (SSOT `composer.json` canonical):
   `composer.json` + `package.json` (+ lockfile) + `README.md` + `docs/project-vision.md` +
   `docs/guides/upgrading.md` at the same `X.Y.Z`; `grep -R "0\.X\." docs/ README.md` must be clean.
3. **Create and push the tag.** `git tag -a vX.Y.Z -m "release vX.Y.Z — <semver reason>"`,
   `git push origin vX.Y.Z`. For staged rollout push `-dev.N` → `-beta.N` → `-rc.N` first.
4. **Never hand-edit the VPS** — `git reset --hard $VERSION_TAG` destroys manual changes. Change repo,
   bump, tag, push.
5. **Verify.** `docker compose ps` + health `deploy ok:` on VPS; `python3 tools/scan_doc_links.py` broken 0.

## Validation

- [ ] Release commit: all 5 version files + lockfile at same `X.Y.Z`; tag pushed
- [ ] No stale `0.X.` version pinned anywhere in `docs/` or `README.md`
- [ ] VPS on the new tag (matches `git describe --tags`), health 200 within 60s