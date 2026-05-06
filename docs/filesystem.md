# Filesystem

## Disks

| Disk | Root path | Visibility | Use case |
|---|---|---|---|
| `local` | `storage/app/private` | Private | Default disk — internal files, PDFs, temp |
| `public` | `storage/app/public` | Public (via `/storage` symlink) | Brand assets, user-uploaded images |
| `s3` | Configured via `AWS_*` env vars | Configurable | Cloud storage (optional) |

Config: `config/filesystems.php`. Default: `local`.

Symlink: `public/storage` → `storage/app/public` (created via `php artisan storage:link`).

## Usage Patterns

### Report Generation

PDF reports stored on the `local` (private) disk:

```php
// app/Domain/Document/Jobs/GenerateReportJob.php
Storage::disk('local')->put($tempPath, $pdf->output());

// app/Domain/Document/Actions/DownloadReportAction.php
Storage::disk('local')->exists($report->file_path);
Storage::disk('local')->get($report->file_path);
```

### Brand Assets

Logo and favicon uploaded to the `public` disk:

```php
// app/Livewire/Admin/SystemSetting.php
$path = $this->brand_logo->store('brand', 'public');
$path = $this->site_favicon->store('brand', 'public');
```

### Spatie Media Library

Models using `HasMedia` and `InteractsWithMedia` manage file attachments with collections and conversions. See `config/media-library.php` for disk and conversion settings.

## Security

- Validate file uploads with `mimes`, `max`, and `file` rules
- Use `basename()` on user-supplied filenames to prevent path traversal
- Private files on `local` disk require authorized routes for download
- Never store credentials or tokens in files
