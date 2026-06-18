---
name: medialibrary-development
description: SDLC Phase: IMPLEMENTATION (Sub-skill). Specialized file upload and media management — Spatie MediaLibrary collections, conversions, responsive images, and retrieval.
upstream:
  - feature-building
downstream:
  - pest-testing
  - sync-docs
---

# Media Library Development Skill

## When to Activate

Apply this skill when implementing file uploads, managing media collections and conversions, generating responsive images, or retrieving media URLs and paths. Activates whenever a Model needs file attachments.

## SDLC Context

| Role | Skill |
|------|-------|
| **Upstream (input)** | `feature-building` — roadmap task requiring file uploads |
| **This skill** | **IMPLEMENTATION (Media)** — produces MediaLibrary integrations |
| **Downstream (output)** | `pest-testing` — tests for upload functionality |
| | `sync-docs` — documentation after media changes |
| **Phase** | [Planning] → [Analysis] → [Design] → Implementation → [Testing] → [Maintenance] |

## Key References

- **User model (avatar)**: `app/User/Models/User.php` — `avatar` collection (single-file, thumb 200×200 WebP)
- **Settings model (branding)**: `app/Settings/Models/Setting.php` — branding images
- **Partnership model (MoU)**: `app/Partners/Partnership/Models/Partnership.php` — partnership documents
- **Document model**: `app/Document/Models/Document.php` — official documents
- **Logbook model**: `app/Journals/Logbook/Models/Logbook.php` — logbook attachments
- **RegistrationDocument model**: `app/Enrollment/Registration/Models/RegistrationDocument.php` — enrollment documents
- **Submission model**: `app/Assignment/Submission/Models/Submission.php` — assignment submissions
- **Media library docs**: `docs/infrastructure/media-library.md`

## Models with Media Collections (8 total)

| Model | Collection(s) | Type | Conversion |
|-------|---------------|------|------------|
| `User` | `avatar` | Single-file, replaceable | `thumb`: 200×200 WebP, non-queued |
| `Setting` | Branding images | Single/multi | Configurable, queued |
| `Partnership` | MoU documents | Single/multi | PDF thumbnail (if applicable) |
| `Document` | Official documents | Multi-file | Configurable |
| `Logbook` | Attachments | Multi-file | — |
| `RegistrationDocument` | Enrollment files | Multi-file | — |
| `Submission` | Assignment files | Multi-file | — |

## Model Integration

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class User extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('avatar')->singleFile();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(200)
            ->height(200)
            ->format('webp')
            ->nonQueued();
    }
}
```

### Rules

- Models must implement `HasMedia` and use `InteractsWithMedia`
- Collections defined in `registerMediaCollections()`
- Conversions defined in `registerMediaConversions(?Media $media = null)`
- `singleFile()` enables replacement on re-upload
- Conversions can be queued (`->queued()`) or immediate (`->nonQueued()`)

## Upload Flow

File uploads follow the Action pattern — Livewire handles the file input, the Action performs the actual upload:

```php
public function execute(User $user, UploadAvatarData $data): User
{
    return $this->transaction(function () use ($user, $data) {
        $user->addMedia($data->avatar)->toMediaCollection('avatar');
        $this->log('avatar_uploaded', $user);
        return $user;
    });
}
```

## Limits & Storage

| Setting | Value |
|---------|-------|
| Max file size | 10 MB (validated in Livewire and Action) |
| Allowed MIME types | Defined per-collection via `acceptsMimeTypes()` |
| Storage disk | Configurable (default: `public`, S3 for production) |
| Temporary URLs | `$model->getFirstTemporaryUrl(...)` for S3 with expiry |

## Retrieval

```blade
<img
    src="{{ $user->getFirstMediaUrl('avatar', 'thumb') }}"
    alt="{{ $user->name }}"
/>
```

- `getFirstMediaUrl('collection', 'conversion')` for URLs
- `getFirstMedia('collection')` for the Media model instance
- Fallback URLs for missing media: check `hasMedia('collection')` first

## Verification

- Model implements `HasMedia` and uses `InteractsWithMedia`?
- Collections in `registerMediaCollections()`?
- Conversions in `registerMediaConversions(?Media $media = null)`?
- `addMedia()` followed by `toMediaCollection()`?
- File size validated (Livewire for UX, Action for authority)?
- Media migration run (`php artisan migrate`)?
