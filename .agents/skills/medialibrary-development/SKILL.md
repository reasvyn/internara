---
name: medialibrary-development
---

# Media Library Development Skill

## When to Activate

Apply this skill when implementing file uploads, managing media collections and conversions,
generating responsive images, or retrieving media URLs and paths. Activate whenever a Model needs to
accept file attachments or when working with spatie/laravel-medialibrary in any form.

## Core Principles

### Model Integration

Models that accept file uploads must implement the `HasMedia` interface and use the
`InteractsWithMedia` trait. Media collections and conversions are defined directly on the Model in
`registerMediaCollections()` and `registerMediaConversions()`. Collections can be single-file
(replacing on re-upload) or multi-file. Conversions generate derived image sizes and formats.

### Separation of Concerns

File upload flows follow the Action pattern: the Livewire component handles the file input using
`WithFileUploads` and validates type/size. The Action handles the actual upload via
`$model->addMedia($file)->toMediaCollection($collection)` within a transaction. The Action also logs
the upload as a side effect.

This separation ensures that upload validation is defense-in-depth (Livewire for UX, Action for
authority) and that uploads are atomic with other mutations.

## Media Lifecycle

Collections are defined at the Model level with `registerMediaCollections()`. Each collection can
restrict MIME types, limit count, designate a storage disk, and set fallback URLs. Conversions are
defined at the Model level with `registerMediaConversions()` and can be queued or synchronous,
scoped to specific collections, and configured with fit/crop/resize parameters.

Responsive images generate multiple sizes from a single source for optimal loading. They can be
enabled per-file, per-conversion, or per-collection.

## Retrieval and Display

Media URLs and paths are retrieved through the Model: `getFirstMediaUrl('collection')`,
`getFirstMediaUrl('collection', 'conversion')`. Blade templates use these methods directly in img
tags. Fallback URLs handle missing media gracefully. Temporary URLs support S3 with expiry.

## Verification Before Finalizing

- Does the Model implement `HasMedia` AND use `InteractsWithMedia`?
- Are collections and conversions defined in the dedicated register methods?
- Is `registerMediaConversions` parameter typed as `?Media $media = null`?
- Is every `addMedia()` followed by `toMediaCollection()`?
- Are collection names using constants or config (not hardcoded strings)?
- Has the media migration been run?
