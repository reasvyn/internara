# File Uploads & Media — Spatie MediaLibrary Integration

> **Last updated:** 2026-07-23 **Changes:** feat — initial file uploads and media specification

## Description

Defines the file upload and media management infrastructure built on Spatie MediaLibrary:
storage disk configuration, collection management, image conversions, file validation, and
retrieval patterns. Covers how modules store, organize, and serve uploaded files.

---

## 1. Problem Statements

### PS-1 — Inconsistent File Handling

Without centralized media infrastructure, each module implements its own upload logic: different
storage paths, different validation rules, different naming conventions. This leads to security
gaps (missing virus scanning, unvalidated file types) and maintenance burden.

### PS-2 — No Thumbnail Generation

Documents and images are stored at original resolution. Displaying them in lists or cards
requires downloading the full file, wasting bandwidth and storage.

### PS-3 — Orphaned Files

When a model is deleted, its uploaded files may remain on disk indefinitely. Without lifecycle
management, storage grows unbounded.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | All file uploads use Spatie MediaLibrary as the single storage abstraction |
| G2  | Files are organized into named collections per module |
| G3  | Image conversions generate responsive thumbnails automatically |
| G4  | File validation enforces allowed MIME types and max sizes per collection |
| G5  | Orphaned files are cleaned up when parent models are deleted |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Virus scanning of uploaded files |
| NG2  | Image editing or manipulation beyond thumbnails |
| NG3  | Cloud storage synchronization (S3 sync) |
| NG4  | Real-time file preview without download |
| NG5  | File versioning or revision history |

---

## 3. User Stories / Use Cases

### UC-1 — Student Uploads Profile Photo

**Actor:** Student
**Preconditions:** Authenticated, profile page open
**Flow:**
1. Student selects image file (< 2MB, JPEG/PNG)
2. File validated against `profile-images` collection rules
3. MediaLibrary stores file on configured disk
4. Thumbnail conversions generated (150x150, 300x300)
5. Profile updated with media reference
**Postconditions:** Profile photo visible with responsive thumbnails

### UC-2 — Admin Uploads Document Template

**Actor:** Admin
**Preconditions:** Document template management page
**Flow:**
1. Admin uploads PDF or DOCX file
2. File validated against `document-templates` collection rules
3. MediaLibrary stores original file
4. Template linked to document type
**Postconditions:** Template available for document generation

### UC-3 — System Cleans Up Deleted Model Files

**Actor:** System (automated)
**Preconditions:** Model with media is deleted
**Flow:**
1. Model deletion triggers MediaLibrary cleanup
2. All associated media files removed from disk
3. Thumbnail conversions also removed
**Postconditions:** No orphaned files on disk

---

## 4. Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| FR-MEDIA1 | All file uploads MUST use Spatie MediaLibrary (`InteractsWithMedia` trait) |
| FR-MEDIA2 | Files MUST be organized into named collections per module |
| FR-MEDIA3 | Image conversions MUST generate responsive thumbnails (150x150, 300x300) |
| FR-MEDIA4 | File validation MUST enforce allowed MIME types per collection |
| FR-MEDIA5 | File validation MUST enforce max file size per collection |
| FR-MEDIA6 | Storage disk MUST be configurable via `config/filesystems.php` |
| FR-MEDIA7 | Model deletion MUST trigger automatic media cleanup |
| FR-MEDIA8 | Media URLs MUST be generated via `getFirstMediaUrl()` or `getMedia()` |
| FR-MEDIA9 | Collection names MUST follow `{module}.{purpose}` convention |

---

## 5. Non-Functional Requirements

| ID       | Requirement |
| -------- | ----------- |
| NFR-MEDIA1 | Thumbnail generation MUST complete within 5 seconds per image |
| NFR-MEDIA2 | File upload MUST support files up to 10MB |
| NFR-MEDIA3 | Storage disk MUST support local filesystem and S3-compatible storage |
| NFR-MEDIA4 | Media retrieval MUST NOT block the HTTP response for large files |

---

## 6. API / Data Contracts

### MediaLibrary Integration on Model

```php
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

class Document extends BaseModel implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('document-templates')
            ->acceptsMimeTypes(['application/pdf', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'])
            ->maxFileSize(10240); // 10MB
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumbnail')
            ->width(150)
            ->height(150)
            ->nonQueued();
    }
}
```

### Collection Naming Convention

| Module | Collection | Accepted Types |
|--------|-----------|---------------|
| User | `profile-images` | JPEG, PNG, WebP |
| Document | `document-templates` | PDF, DOCX |
| Document | `handbook-files` | PDF |
| Certification | `certificate-templates` | PDF |
| Partners | `company-logos` | JPEG, PNG, SVG |
| Partners | `mou-documents` | PDF |

### Storage Disk Configuration

```php
// config/filesystems.php
'disks' => [
    'media' => [
        'driver' => 'local',
        'root' => storage_path('app/media'),
    ],
],
```

---

## 7. Design Decisions

### DD-1 — Spatie MediaLibrary Over Raw Storage

**Decision:** Use Spatie MediaLibrary instead of Laravel's raw `Storage` facade.

**Rationale:** MediaLibrary provides collection management, conversions, responsive images,
and model lifecycle hooks out of the box. Building this manually would duplicate significant
infrastructure.

**Trade-off:** Adds a package dependency. MediaLibrary's abstraction adds ~2ms overhead per
media operation. Acceptable for the feature set gained.

### DD-2 — Non-Queued Thumbnail Generation

**Decision:** Thumbnail conversions use `nonQueued()` (synchronous during upload).

**Rationale:** Thumbnails are needed immediately for display. Queuing would create a race
condition where the upload succeeds but thumbnails aren't ready when the page refreshes.

**Trade-off:** Upload takes ~200ms longer. Acceptable for user-facing uploads (< 10MB).

### DD-3 — Collection Per Module Purpose

**Decision:** Each module uses named collections per purpose (e.g., `profile-images`,
`document-templates`), not a single global collection.

**Rationale:** Collections provide scoped validation (MIME types, file sizes) and retrieval.
A student's profile photo and a certificate template have completely different constraints.

**Trade-off:** More collection definitions to maintain. Each is ~5 lines. Acceptable overhead.

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| File uploads using MediaLibrary | 100% |
| Orphaned files after model deletion | 0 |
| Thumbnail generation success rate | 99.9% |
| Upload failure rate (validation errors excluded) | < 0.1% |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [core-foundation.md](core-foundation.md) | `BaseCommandAction` for file operation logging |

### Build Guide
After implementing this spec, the system has file upload infrastructure via Spatie MediaLibrary with collections, conversions, responsive images, and file type validation. Every module that handles file uploads (logbook evidence, certificate PDFs, assignment submissions) depends on this. The next step is to build PDF generation, which uses media storage for rendered documents.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [pdf-generation.md](pdf-generation.md) | Generated PDFs are stored as media via this spec's infrastructure |

---

## Quick References

- `config/media-library.php` — MediaLibrary configuration
- `config/filesystems.php` — Storage disk definitions
- `docs/specs/system-requirements.md` — Spatie MediaLibrary dependency
- `docs/specs/certification.md` — Certificate template uploads
- `docs/specs/document-templates.md` — Document template uploads
