# Filesystem — File Storage & Directory Layout

> **Last updated:** 2026-07-11 **Changes:** sync — consolidate Media Library content into media-library.md reference

## Description

File storage architecture, directory structure, disk configuration, and media file handling
conventions.

## Storage Architecture

The application uses Laravel's filesystem abstraction, providing a unified API over multiple storage
backends. The same `Storage::disk('public')->put()` call works whether the underlying disk is a
local directory or an S3 bucket — switching requires only a configuration change.

### Disk Definitions

| Disk     | Driver | Default Root          | Purpose                                              | Web-Accessible   |
| -------- | ------ | --------------------- | ---------------------------------------------------- | ---------------- |
| `local`  | Local  | `storage/app/private` | Internal files, temporary uploads, exports           | ❌               |
| `public` | Local  | `storage/app/public`  | User-facing files (avatars, documents, certificates) | ✅ (via symlink) |
| `s3`     | S3     | Bucket root           | Production cloud storage                             | ✅ (via CDN)     |

### Storage by Deployment Tier

```
Tier 1 (Shared Hosting) — up to 500 registered users:
  └─ Local disk → storage/app/public → symlinked to public/storage/

Tier 2 (VPS):
  ├─ Local disk for active files
  └─ Periodic sync to S3 for backup

Tier 3 (Multi-Server / HA):
  └─ S3 as primary storage
      ├─ AWS S3, MinIO, DigitalOcean Spaces, or Cloudflare R2
      ├─ No local storage dependency
      └─ All servers share the same bucket
```

### Public Storage Symlink

```bash
php artisan storage:link
# Creates: public/storage → storage/app/public
```

Without this symlink, media URLs return 404.

---

## What Gets Stored Where

| Data                  | Storage                         | Backend     | Accessibility                |
| --------------------- | ------------------------------- | ----------- | ---------------------------- |
| User avatars          | Media library → `public` disk   | Local or S3 | Public URL                   |
| Uploaded documents    | Media library → `public` disk   | Local or S3 | Public URL (with auth guard) |
| Certificate PDFs      | Direct → `public/certificates/` | Local or S3 | Public URL (with auth guard) |
| Brand assets          | Direct → `public/brand/`        | Local or S3 | Public URL                   |
| Generated reports     | Direct → `local` disk           | Local only  | Download via controller      |
| Livewire temp uploads | Livewire temp → `local` disk    | Local only  | Temporary, auto-cleaned      |
| Internal exports      | Direct → `local` disk           | Local only  | Download via controller      |

---

## Media Library Integration

Files attached to Eloquent models are managed by `spatie/laravel-medialibrary`. For complete
documentation on media collections, image conversions, queue integration, and file upload flow, see
[Media Library](media-library.md).

---

## File Size Limits

| Scope             | Limit      | Configuration File         | Directive             |
| ----------------- | ---------- | -------------------------- | --------------------- |
| Uploaded file     | 10 MB      | `config/media-library.php` | `max_file_size`       |
| Livewire temp     | PHP config | `php.ini`                  | `upload_max_filesize` |
| HTTP request body | PHP config | `php.ini`                  | `post_max_size`       |

---

## S3-Compatible Cloud Storage

### Configuration

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=internara-uploads
AWS_ENDPOINT=https://s3.amazonaws.com
AWS_USE_PATH_STYLE_ENDPOINT=false
```

### Supported Providers

| Provider                | Endpoint                             | Notes                                  |
| ----------------------- | ------------------------------------ | -------------------------------------- |
| **AWS S3**              | `s3.amazonaws.com`                   | Native S3, no special config needed    |
| **MinIO** (self-hosted) | `http://minio:9000`                  | Set `AWS_USE_PATH_STYLE_ENDPOINT=true` |
| **DigitalOcean Spaces** | `{region}.digitaloceanspaces.com`    | S3-compatible API, flat pricing        |
| **Cloudflare R2**       | `{account}.r2.cloudflarestorage.com` | No egress fees, global CDN             |

### Hybrid Local + S3 (Tier 2)

For schools that need local storage with S3 backup:

```env
FILESYSTEM_DISK=local
```

```bash
0 4 * * * s3cmd sync /path/to/storage/app/public/ s3://internara-backups/
```

---

## File Upload Flow

```
User upload → Livewire temp (local disk)
                  ↓
          Media library attaches to model
                  ↓
          Queue worker (if async) processes conversions
                  ↓
          File accessible via getFirstMediaUrl()
```

Files are validated before upload: MIME type, file size, and extension checks run on the server
side. The media library stores files with UUID-based filenames to prevent name collisions and path
traversal.

---

## Where to Find It

- `config/filesystems.php` — disk definitions (local, public, s3)
- `config/media-library.php` — media library configuration
- `config/dompdf.php` — DomPDF configuration for certificate/report rendering
- `app/*/Models/*.php` — `registerMediaCollections()` and `registerMediaConversions()` methods
- `app/Certification/Certificate/Support/CertificateRenderer.php` — certificate PDF generation
- `database/migrations/` — media table migration
- [Infrastructure](infrastructure.md) — tier-based infrastructure design
- [Media Library](media-library.md) — detailed media library documentation
