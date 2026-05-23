# Filesystem

## Storage Architecture

The application uses Laravel's filesystem abstraction, which provides a
unified API over multiple storage backends. The abstraction means that
storage drivers can be swapped without changing application code — the same
`Storage::disk('public')->put()` call works whether the underlying disk is
a local directory or an S3 bucket.

Two local disks are defined: `local` for private files and `public` for
publicly accessible files. The `local` disk stores files that must not be
directly web-accessible — generated documents, temporary uploads, internal
exports. Access to these files requires authentication and authorization.
The `public` disk stores files that are served directly by the web server —
media library uploads, generated certificates, brand assets. It is symlinked
from `public/storage` to `storage/app/public`.

For production deployments with horizontal scaling, a cloud disk (S3 or
S3-compatible like MinIO, DigitalOcean Spaces, Cloudflare R2) is recommended.
Cloud storage decouples file storage from the application server, allowing
any server in a pool to serve any file without requiring shared NFS mounts.

## Media Library Integration

Files attached to Eloquent models are managed by the spatie/laravel-medialibrary
package. This package provides media collections (named groups of files per
model), automatic file naming, image conversions (thumbnails, responsive
sizes), and queue-based processing. It replaces the need to manually manage
file paths, upload validation, and image resizing for each model.

Media collections are defined on each model. For example, the User model has
an `avatar` collection (single file, with a 200x200 WebP thumbnail conversion),
and various document models have a `file` collection (single file, no
conversions). The collection system ensures that files are attached to the
correct model and retrieved with simple method calls like
`$user->getFirstMediaUrl('avatar')`.

Conversions are queued by default so they do not block the HTTP response.
The queue worker processes them asynchronously. This means a user uploading
a profile picture gets an immediate response while the thumbnail generation
happens in the background.

## What Gets Stored Where

User avatars are stored through the media library on the `public` disk. The
avatar collection stores the original image and automatically generates a
thumbnail conversion.

Uploaded documents — internship documents, student submissions, partnership
agreements — are stored through the media library on the `public` disk. File
types and sizes are validated before upload. Maximum file size is 10 MB.

Generated certificate PDFs are stored directly on the `public` disk (not
through the media library). They are rendered dynamically from Blade
templates using DomPDF and saved to a `certificates/` directory with the
certificate number as filename.

Brand assets (logo, favicon) are uploaded via the admin settings panel and
stored in the `public/brand/` directory. The URL is saved as a setting value.

The `local` disk stores generated documents (rendered from template content
using DomPDF) and any internal files that should not be publicly accessible.

## Where to Find It

Filesystem configuration is in `config/filesystem.php`. Media library
configuration is in `config/media-library.php`. Media collections are
defined in each model's `registerMediaCollections()` method. The storage
symlink is created by `php artisan storage:link`. Certificate PDF generation
is in `app/Domain/Certificate/Support/CertificateRenderer.php`. DomainPDF
configuration is in `config/dompdf.php`.
