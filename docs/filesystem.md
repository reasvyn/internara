# Filesystem
> Last updated: 2026-05-27
> Changes: docs: comprehensive infrastructure, architecture, and conventions overhaul


## Storage Architecture

The application uses Laravel's filesystem abstraction, providing a unified API over
multiple storage backends. The same `Storage::disk('public')->put()` call works whether the
underlying disk is a local directory or an S3 bucket — switching requires only a
configuration change.

### Disk Definitions

| Disk | Driver | Default Root | Purpose | Web-Accessible |
|---|---|---|---|---|
| `local` | Local | `storage/app/private` | Internal files, temporary uploads, exports | ❌ |
| `public` | Local | `storage/app/public` | User-facing files (avatars, documents, certificates) | ✅ (via symlink) |
| `s3` | S3 | Bucket root | Production cloud storage | ✅ (via CDN) |

### Storage by Deployment Tier

```
Tier 1 (Shared Hosting):
  └─ Local disk → storage/app/public → symlinked to public/storage/
  └─ No S3 needed

Tier 2 (VPS):
  ├─ Local disk for active files
  └─ Periodic sync to S3 for backup

Tier 3 (Multi-Server):
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

| Data | Storage | Backend | Accessibility |
|---|---|---|---|
| User avatars | Media library → `public` disk | Local or S3 | Public URL |
| Uploaded documents | Media library → `public` disk | Local or S3 | Public URL (with auth guard) |
| Certificate PDFs | Direct → `public/certificates/` | Local or S3 | Public URL (with auth guard) |
| Brand assets | Direct → `public/brand/` | Local or S3 | Public URL |
| Generated reports | Direct → `local` disk (private) | Local only | Download via controller |
| Livewire temp uploads | Livewire temp → `local` disk | Local only | Temporary, auto-cleaned |
| Internal exports | Direct → `local` disk (private) | Local only | Download via controller |

---

## Media Library Integration

Files attached to Eloquent models are managed by `spatie/laravel-medialibrary`.
It provides media collections, automatic file naming, image conversions, and queue-based
processing.

### Collections

| Model | Collection | Files | Purpose |
|---|---|---|---|
| `User` | `avatar` | Single | Profile picture |
| `School` | `logo` | Single | Institution logo |
| `Document` | `file` | Single | Uploaded document template |
| `Submission` | `file` | Multiple | Assignment submission files |
| `RegistrationDocument` | `file` | Single | Identity or requirement document |
| `Logbook` | `photos` | Multiple | Daily activity photo documentation |
| `Partnership` | `mou_document` | Single | Signed MoU agreement |
| `Certificate` | `output` | Single | Generated certificate PDF |

### Adding a New Collection

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class YourModel extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('documents')
            ->acceptsMimeTypes(['application/pdf', 'image/jpeg'])
            ->maxFileSize(10 * 1024 * 1024); // 10 MB
    }
}
```

### Retrieving Files

```php
$url = $user->getFirstMediaUrl('avatar');           // URL of first file
$url = $user->getFirstMediaUrl('avatar', 'thumb');  // URL of thumbnail conversion
$media = $user->getFirstMedia('avatar');             // Full media object
$files = $model->getMedia('documents');              // All files in collection
```

---

## Image Conversions

When images are uploaded, the media library generates resized versions automatically.

| Conversion | Width | Format | Queued | Purpose |
|---|---|---|---|---|
| `thumb` | 400px | WebP | Yes | Avatars, thumbnails in tables |

### Driver

| Driver | Quality | Setup |
|---|---|---|
| `gd` (default) | Good | Built into PHP, no setup |
| `imagick` | Better | Requires `ext-imagick`, higher compression quality |

```env
IMAGE_DRIVER=imagick
```

### Queue Integration

Conversions are queued by default (`queue_conversions_by_default: true` in
`config/media-library.php`). The queue connection is inherited from `QUEUE_CONNECTION`:

- **Tier 1** (sync): conversions run inline, uploads take longer
- **Tier 2+** (Redis): conversions run asynchronously via queue worker

To make a conversion synchronous (available immediately):

```php
$this->addMediaConversion('thumb')->nonQueued();
```

---

## File Size Limits

| Scope | Limit | Configuration File | Directive |
|---|---|---|---|
| Uploaded file | 10 MB | `config/media-library.php` | `max_file_size` |
| Livewire temp | PHP config | `php.ini` | `upload_max_filesize` |
| HTTP request body | PHP config | `php.ini` | `post_max_size` |

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

| Provider | Endpoint | Notes |
|---|---|---|
| **AWS S3** | `s3.amazonaws.com` | Native S3, no special config needed |
| **MinIO** (self-hosted) | `http://minio:9000` | Set `AWS_USE_PATH_STYLE_ENDPOINT=true` |
| **DigitalOcean Spaces** | `{region}.digitaloceanspaces.com` | S3-compatible API, flat pricing |
| **Cloudflare R2** | `{account}.r2.cloudflarestorage.com` | No egress fees, global CDN |

### Hybrid Local + S3 (Tier 2)

For schools that need local storage with S3 backup:

```env
FILESYSTEM_DISK=local
# Backup to S3 via cron script or s3cmd
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

Files are validated before upload: MIME type, file size, and extension checks run on the
server side. The media library stores files with UUID-based filenames to prevent name
collisions and path traversal.

---

## Where to Find It

- `config/filesystems.php` — disk definitions (local, public, s3)
- `config/media-library.php` — media library configuration
- `config/dompdf.php` — DomPDF configuration for certificate/report rendering
- `app/Domain/*/Models/*.php` — `registerMediaCollections()` and
  `registerMediaConversions()` methods
- `app/Domain/Certificate/Support/CertificateRenderer.php` — certificate PDF generation
- `database/migrations/` — media table migration
- `docs/infrastructure.md` — tier-based infrastructure design
- `docs/media-library.md` — detailed media library documentation
