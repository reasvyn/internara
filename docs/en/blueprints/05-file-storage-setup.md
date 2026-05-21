# Blueprint 05: File Storage & Media Library

## Storage Architecture

Internara uses Spatie Media Library for all file attachments. The underlying
Laravel filesystem provides disk abstraction — local storage for development,
S3-compatible object storage for production scaling.

| Disk | Driver | Default Root | Purpose |
|---|---|---|---|
| `local` | Local | `storage/app/private` | Internal files, temporary uploads |
| `public` | Local | `storage/app/public` | User-facing files (avatars, documents) |
| `s3` | S3 | Bucket root | Production cloud storage |

The public storage symlink (`public/storage` → `storage/app/public`) must
exist for user-uploaded files to be accessible via URL.

## Media Library Collections

Each model that implements `HasMedia` registers named collections:

| Model | Collections | Files |
|---|---|---|
| `User` | `avatar` | Profile picture (single file) |
| `School` | `logo` | Institution logo (single file) |
| `Document` | `file` | Uploaded document template (single file) |
| `Submission` | `file` | Assignment submission files |
| `RegistrationDocument` | `file` | Student identity/requirement docs |
| `Partnership` | `mou_document` | Signed MoU agreement (single file) |
| `Certificate` | `output` | Generated certificate PDF |

Add new collections by calling `$this->addMediaCollection('name')` in the
model's `registerMediaCollections()` method.

## Image Conversions

When images are uploaded, the media library generates conversions:

| Conversion | Width | Format | Queued |
|---|---|---|---|
| `thumb` | 400px | WebP | Yes (default) |

Conversions are processed by the queue worker. The image driver defaults to
`gd` (built into PHP). For better quality, switch to `imagick` in production:

```env
IMAGE_DRIVER=imagick
```

## File Size Limits

| Scope | Limit | Configuration |
|---|---|---|
| Uploaded file | 10 MB | `config/media-library.php` → `max_file_size` |
| Livewire temp | PHP `upload_max_filesize` | `php.ini` |
| Request body | PHP `post_max_size` | `php.ini` |

## Production Storage

For multi-server deployments, configure S3-compatible storage:

```env
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=internara-uploads
AWS_ENDPOINT=https://s3.amazonaws.com  # or your MinIO/DO Spaces/R2 endpoint
AWS_USE_PATH_STYLE_ENDPOINT=false
```

Supported S3-compatible providers:
- **AWS S3** — `s3.amazonaws.com`
- **MinIO** (self-hosted) — set `AWS_ENDPOINT` + `AWS_USE_PATH_STYLE_ENDPOINT=true`
- **DigitalOcean Spaces** — `{region}.digitaloceanspaces.com`
- **Cloudflare R2** — `{account}.r2.cloudflarestorage.com`

## Queue Integration

Media conversions are queued by default (`queue_conversions_by_default: true`).
The queue connection is inherited from `QUEUE_CONNECTION`. In production with
Redis, conversions process asynchronously without slowing down HTTP responses.

## References

- `config/filesystems.php` — disk definitions
- `config/media-library.php` — media library configuration
- `app/Domain/*/Models/*.php` — model `registerMediaCollections()` methods
- `docs/en/erd/14-infra.md` — `media` table schema
