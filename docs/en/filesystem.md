# Filesystem

## Disks
| Disk | Root | Visibility | Use case |
|---|---|---|---|
| `local` | `storage/app/private` | Private (via authorized routes) | Internal files, generated PDFs, temporary storage |
| `public` | `storage/app/public` | Public (`/storage` symlink) | Brand assets, uploaded documents |
| `s3` | Configured via `AWS_*` env vars | Configurable | Cloud storage (optional) |

## Media Library
`spatie/laravel-medialibrary` handles file attachments on these models:

| Model | Collection | Purpose |
|---|---|---|
| `User` | `avatar` | Profile picture (single file, thumb conversion) |
| `School` | `logo` | School logo (single file) |
| `Document` | `file` | Uploaded document files (single file) |
| `RegistrationDocument` | `file` | Student-uploaded registration documents (single file) |
| `Submission` | `file` | Student assignment submissions (single file) |

## Document Storage
The `Document` model uses a hybrid approach:

- **Uploaded templates** → stored via MediaLibrary (`'file'` collection)
- **Generated reports/PDFs** → stored via Storage facade at `storage/app/local/generated-documents/`, referenced by the `file_path` column

The `DocumentRenderer` support class handles template rendering. It renders Blade content from `Document::content` using DomPDF and saves output to the local disk.

## Security
- Validate uploads with `mimes`, `max`, and `file` validation rules
- Use `basename()` on user-supplied filenames to prevent path traversal
- Private files require authorized routes for download
