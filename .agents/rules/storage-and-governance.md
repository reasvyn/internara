# Media Storage & Governance — Disks, Queues, and Verification Gate

MediaLibrary runs on infrastructure choices that only bite at scale: which disk stores files, which
queue processes conversions, and what the verification gate checks before a media feature ships.
These choices are codified in `config/media-library.php` and re-checked per deployment tier.

---

## Intent

Storage disk and queue driver are set in `config/media-library.php` (defaults: disk `public`, queue
`default`). Per-model/per-collection constraints (max size, accepted MIME types) live with the
collections. Before shipping any media feature, run the verification checklist so uploads, retrieval,
and security are all confirmed.

## Rationale — What Fails Without It

- **Wrong disk** silently strands files in a disk the web server cannot serve (or signs URIs that
  are never implemented) — every media URL 404s in production despite passing local tests.
- **Wrong/unconfigured queue** means conversions never run: originals upload fine (direct call) but
  `thumb` variants stay missing — a bug that only appears under the production queue driver.
- **No per-collection caps** makes the storage footprint unbounded and lets a pathological upload
  through (see the collections rule for the MIME guard).
- **Skipping the verification gate** lets a media feature ship with one of the above defects masked
  by the dev environment's forgiving config.

## How to Apply

### Config (defaults in `config/media-library.php`)

| Setting   | Default   | Applies when                                |
| --------- | --------- | ------------------------------------------- |
| Disk      | `public`  | Files must be directly URL-servable         |
| Queue     | `default` | Generating conversions in the background    |

- Match disk/queue to the deployment tier: `docs/guides/infra/deployment.md` documents how each
  tier configures storage (local vs S3-compatible) and queue (sync vs Redis). Align the config file
  with that tier's expectations.

### Verification checklist (before shipping)

- [ ] Upload goes through MediaLibrary, not `Storage::put()`
- [ ] MIME type validated server-side (collection `acceptsFile`, never just an extension check)
- [ ] Filename sanitized with `Str::slug()`
- [ ] Collection registered on the model (`registerMediaCollections()`)
- [ ] Max file size and accepted types defined on the collection
- [ ] Conversions defined if image processing is needed
- [ ] Upload handled in a Command Action, not Livewire
- [ ] Test covers upload and retrieval (traced to the governing spec FR — see `pest-testing`)

## Anti-Patterns & Pitfalls

- Configuring the disk per-call (`->toMediaCollectionOnDisk('avatar', 's3')`) while docs/config
  promise `public` — the deployment tier and the code diverge.
- Setting the queue to `sync` in production silently — every conversion blocks the request and
  production traffic stalls on image gen.
- Shipping media features with no governance test — MIME + size + conversion behave as unspec'd
  behavior.
- Ignoring `config/media-library.php`'s filesystem default and letting another package's config
  override storage — verify the actual default disk in `config/filesystems.php` too.

## Verification

- `python3 tools/scan_conventions.py` + `python3 tools/scan_security.py` clean for the feature's
  upload path.
- Targeted MediaLibrary test suite (`vendor/bin/pest --testsuite=...`) passes for upload/retrieval.
- `config/media-library.php` matches the deployment tier's stated disk/queue; `docs/refs/modules/`
  reference for the feature documents the collections added.
