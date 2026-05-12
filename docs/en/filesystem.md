# Filesystem

## Disks

| Disk | Root | Visibility | Use case |
|---|---|---|---|
| `local` | `storage/app/private` | Private (via authorized routes) | Internal files, PDFs, temporary storage |
| `public` | `storage/app/public` | Public (`/storage` symlink) | Brand assets, user uploads |
| `s3` | Configured via `AWS_*` env vars | Configurable | Cloud storage (optional) |

The default disk is `local`, configured via `FILESYSTEM_DISK` in your `.env`.

> There is no `private` disk name — always use `Storage::disk('local')` for private files.

Configuration is in `config/filesystems.php`.

## Media Library

`spatie/laravel-medialibrary` handles file attachments on the `School` and `Submission` models, with support for media collections, image conversions, and responsive images.

## Security

- Validate uploads with `mimes`, `max`, and `file` validation rules
- Use `basename()` on user-supplied filenames to prevent path traversal
- Private files require authorized routes for download
