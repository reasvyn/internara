# Media Library — File Uploads & Media Management

> **Last updated:** 2026-06-13
> **Changes:** sync — initial metadata sync with new format

## Description
Internara uses [spatie/laravel-medialibrary](https://spatie.be/docs/laravel-medialibrary) to associate files with Eloquent models. This package handles uploads, storage, image conversions, and file retrieval — replacing the need to manually manage file paths, validation, and processing for each model.

---


## Storage Architecture

The media library stores files on Laravel filesystem disks:

| Disk     | Driver | Default Root          | Purpose                                |
| -------- | ------ | --------------------- | -------------------------------------- |
| `local`  | Local  | `storage/app/private` | Internal files, temporary uploads      |
| `public` | Local  | `storage/app/public`  | User-facing files (avatars, documents) |
| `s3`     | S3     | Bucket root           | Production cloud storage               |

The `public` disk requires a symlink:

```bash
php artisan storage:link
# Creates: public/storage → storage/app/public
```

---

## Media Collections

Each model that implements `HasMedia` registers named collections. A collection is a named group of files — a model can have multiple collections, each with its own rules.

| Model                  | Collection     | Files    | Purpose                            |
| ---------------------- | -------------- | -------- | ---------------------------------- |
| `User`                 | `avatar`       | Single   | Profile picture                    |
| `School`               | `logo`         | Single   | Institution logo                   |
| `Document`             | `file`         | Single   | Uploaded document template         |
| `Submission`           | `file`         | Multiple | Assignment submission files        |
| `RegistrationDocument` | `file`         | Single   | Identity or requirement document   |
| `Logbook`              | `photos`       | Multiple | Daily activity photo documentation |
| `Partnership`          | `mou_document` | Single   | Signed MoU agreement               |
| `Certificate`          | `output`       | Single   | Generated certificate PDF          |

### Adding a New Collection

In your model's `registerMediaCollections()` method:

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
$url = $model->getFirstMediaUrl('avatar');           // URL of first file
$media = $model->getFirstMedia('avatar');            // Full media object
$files = $model->getMedia('documents');              // All files in collection
$thumb = $model->getFirstMediaUrl('avatar', 'thumb'); // Thumbnail conversion
```

---

## Image Conversions

When images are uploaded, the media library can generate resized versions automatically. Conversions are defined in the model:

```php
public function registerMediaConversions(?Media $media = null): void
{
    $this->addMediaConversion('thumb')
        ->width(400)
        ->format('webp')
        ->nonQueued();
}
```

| Conversion | Width | Format | Queued        |
| ---------- | ----- | ------ | ------------- |
| `thumb`    | 400px | WebP   | Yes (default) |

The image driver defaults to `gd` (built into PHP). For higher quality conversions, switch to `imagick`:

```env
IMAGE_DRIVER=imagick
```

### Queue Integration

Conversions are queued by default (`queue_conversions_by_default: true` in `config/media-library.php`). The queue connection is inherited from `QUEUE_CONNECTION`:

- **Tier 1 (Shared Hosting — up to 500 registered users):** conversions run synchronously during the upload request
- **Tier 2+ (Redis, dual pipeline):** conversions process asynchronously via the `default` queue worker

If a conversion must be available immediately (synchronous):

```php
$this->addMediaConversion('thumb')->nonQueued();
```

---

## File Size Limits

| Scope                     | Limit                     | Configuration                                |
| ------------------------- | ------------------------- | -------------------------------------------- |
| Uploaded file             | 10 MB                     | `config/media-library.php` → `max_file_size` |
| Livewire temporary upload | PHP `upload_max_filesize` | `php.ini`                                    |
| HTTP request body         | PHP `post_max_size`       | `php.ini`                                    |

---

## S3-Compatible Cloud Storage

For multi-server deployments, configure S3-compatible object storage:

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
| **MinIO** (self-hosted) | Your server URL                      | Set `AWS_USE_PATH_STYLE_ENDPOINT=true` |
| **DigitalOcean Spaces** | `{region}.digitaloceanspaces.com`    | S3-compatible API                      |
| **Cloudflare R2**       | `{account}.r2.cloudflarestorage.com` | No egress fees                         |

### Migrating from Local to S3

When moving from local disk to S3 as the primary storage backend:

```bash
# Sync existing files to S3
aws s3 sync storage/app/public s3://internara-media/ --storage-class STANDARD_IA

# Then run the migration command to update media library paths
php artisan media:migrate-to-s3
```

---

## File Upload Flow

```
User uploads file → Livewire temporary upload → media library attaches to model
                                                    ↓
                                            Queue worker processes conversions
                                                    ↓
                                            File accessible via getFirstMediaUrl()
```

Files are validated before upload: MIME type, file size, and extension checks run on the server side. The media library stores files with UUID-based filenames to prevent name collisions and path traversal.

---

## Where to Find It

- `config/media-library.php` — global media library configuration
- `config/filesystems.php` — disk definitions (local, public, s3)
- `app/*/Models/*.php` — `registerMediaCollections()` and `registerMediaConversions()` methods on each model
- `database/migrations/` — the `media` table migration
- [Filesystem](filesystem.md) — storage architecture and disk definitions
- [Infrastructure](infrastructure.md) — tier-based infrastructure design
