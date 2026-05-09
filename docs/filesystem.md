# Filesystem

## Disks

| Disk | Root | Visibility | Use case |
|---|---|---|---|
| `local` | `storage/app/private` | Private (via route) | Internal files, PDFs, temp |
| `public` | `storage/app/public` | Public (`/storage` symlink) | Brand assets, user uploads |
| `s3` | `AWS_*` env vars | Configurable | Cloud storage (optional) |

Config: `config/filesystems.php`. Default: `local` (via `FILESYSTEM_DISK`).

> **No `private` disk exists** — always use `Storage::disk('local')` for private files.

## Usage

```php
// Store/report files
Storage::disk('local')->put($path, $content);
Storage::disk('local')->get($path);

// Brand assets
$path = $this->brand_logo->store('brand', 'public');
```

Spatie Media Library (`HasMedia` + `InteractsWithMedia`) is used on `School` and `Submission` models for file attachments with collections and conversions.

## Security

- Validate uploads with `mimes`, `max`, and `file` rules
- Use `basename()` on user-supplied filenames to prevent path traversal
- Private files require authorized routes for download