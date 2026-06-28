# Chapter 10: System Settings & Backups

> **Last updated:** 2026-06-15
> **Changes:** sync — initial metadata sync with new format

## Description
This chapter covers how to configure your Internara system — branding, mail, locale, and appearance
— and how to manage automated backups.

---


## 10.1 Accessing System Settings

Only the Super Admin can access system settings. Navigate to **Admin → Settings** or go directly to
`/admin/settings`.

The settings page is organized into three main sections: **General Configuration**, **Appearance &
Colors**, and **Mail Services**. A sidebar on the right shows **System Information** and **Visual
Identity** controls.

---

## 10.2 General Configuration

The General card controls how your system identifies itself:

| Field | Description |
|-------|-------------|
| **Brand Name** | Your school or institution name (e.g. "SMKN 1 Jakarta") |
| **Site Title** | The browser tab title for Internara pages |
| **Default Language** | System language — **Bahasa Indonesia** or **English** |
| **Active Academic Year** | The current academic year (e.g. "2025/2026") used as the default for new programs |

Select the correct academic year from the dropdown. This setting determines which academic year new
internships default to.

---

## 10.3 Appearance & Color Scheme

### 10.3.1 Using Presets

The Appearance card offers **6 preset color palettes**. Each preset has four colors (primary,
secondary, accent, background). Click a palette to preview:

- **Sky** — blue tones
- **Emerald** — green tones (default)
- **Violet** — purple tones
- **Rose** — pink tones
- **Ocean** — teal tones
- **Slate** — grey tones

The currently active preset shows a checkmark. Click **Save** to apply it.

### 10.3.2 Custom Colors

If you prefer a custom look, set each color individually using the color pickers. Clicking any color
input clears the selected preset, switching to custom mode. Changes appear immediately in the
color input but are only applied system-wide after saving.

The four color values are:

| Color | Purpose | Default |
|-------|---------|---------|
| **Primary** | Main brand color — buttons, links, active elements | `#059669` (emerald) |
| **Secondary** | Supporting UI elements | `#6b7280` (grey) |
| **Accent** | Highlight and call-to-action elements | `#f97316` (orange) |
| **Base** | Page background | `#ffffff` (white) |

---

## 10.4 Visual Identity

### 10.4.1 Brand Logo

To upload a logo:

1. Click the **logo placeholder** (a building icon) or the current logo image
2. Select an image file (PNG, JPEG, or WebP, max 1 MB)
3. The logo uploads automatically

To remove the logo, hover over the image and click the **X** button that appears.

### 10.4.2 Favicon

To upload a favicon (browser tab icon):

1. Click the **favicon placeholder** (a globe icon) or the current favicon
2. Select an image file (PNG, JPEG, or ICO, max 512 KB)
3. The favicon uploads automatically

To remove the favicon, hover over the image and click the **X** button.

---

## 10.5 Mail Services

The Mail card configures SMTP settings for outgoing emails (password resets, notifications, etc.).

| Field | Description |
|-------|-------------|
| **From Address** | The email address shown in the "From" field of outgoing emails |
| **From Name** | The name shown alongside the from address |
| **SMTP Host** | Your mail server hostname (e.g. `smtp.gmail.com`) |
| **SMTP Port** | The SMTP port (587 for TLS, 465 for SSL) |
| **Encryption** | TLS, SSL, or None |
| **Username** | SMTP authentication username |
| **Password** | SMTP authentication password |

### 10.5.1 Testing Mail Settings

Before saving, click **Test SMTP Connection** to send a test email to the from address. If the test
fails, check your mail server credentials and firewall settings.

> **Note:** The mail password is stored encrypted in the database.

---

## 10.6 Saving Changes

Click the **Save** button at the bottom of the page. A success message confirms the update. All
changes take effect immediately — no server restart needed. Cached values (colors, brand info) are
invalidated automatically.

---

## 10.7 Backups

Backups protect your data in case of system failure or accidental data loss. Internara can back up
the database, uploaded files, or both.

> **Note:** Backups must be enabled via the `BACKUP_ENABLED` environment variable before they appear
> in the interface. Your system administrator can set this to `true` in your `.env` file. See the
> [Deployment Guide](../infrastructure/deployment.md) for details.

### 10.7.1 Accessing Backup Manager

Navigate to **Admin → Backups** (`/admin/backups`). Both Super Admin and Admin roles can access
this page.

The backup page shows:

- **Stats cards** — total backups, completed count, failed count, latest backup size
- **Filter bar** — filter by type (Database / Storage / Full) or status
- **Data table** — every backup with type, status, size, creator, and date

### 10.7.2 Creating a Backup

Click the **Create Backup** dropdown and choose one of three types:

| Type | Content | File Format |
|------|---------|-------------|
| **Database** | Complete database dump (tables, data, routines) | `.sql.gz` or SQLite file |
| **Storage** | All uploaded files from `storage/app/public` | `.tar.gz` |
| **Full** | Database + storage combined | `.tar.gz` |

1. Click **Create Backup**
2. Select the backup type
3. Wait for the process to complete — the table updates automatically
4. Check the status badge: green (success) or red (failed)

If a backup fails, hover over the **Failed** badge to see error details.

### 10.7.3 Filters

Use the **Type** filter to show only database, storage, or full backups. Use the **Status** filter
to show pending, running, completed, or failed backups.

### 10.7.4 Deleting a Backup

Only completed or failed backups can be deleted. Click the trash icon on any deletable backup row
and confirm the deletion in the dialog. The file is removed from disk and the record is deleted.

### 10.7.5 Automated Retention

Backups older than 30 days are automatically removed by the cleanup process. This retention period
is configurable via the `BACKUP_RETENTION_DAYS` environment variable.

---

## 10.8 CLI Backup Commands

System administrators can also manage backups via the command line:

```bash
# Create a full backup (database + storage)
php artisan system:backup

# Create a database-only backup
php artisan system:backup --type=database

# Create a storage-only backup
php artisan system:backup --type=storage

# Run cleanup after backup
php artisan system:backup --cleanup

# Force backup even if backups are disabled
php artisan system:backup --force
```

The CLI reports the backup file size on completion and shows any errors encountered.

---

## 10.9 Troubleshooting

**Settings won't save**
- Ensure all required fields (Brand Name, Site Title) are filled in
- Check that the active academic year is selected
- Verify the default locale is either `id` or `en`

**Colors not updating after save**
- Clear your browser cache
- Try switching to a preset palette and saving, then customize from there

**Test email fails**
- Verify SMTP host and port are correct
- Check that your mail server allows connections from your server's IP
- Try TLS on port 587 (most common) or SSL on port 465
- Ensure the from address is accepted by your mail server

**Backup fails**
- Check server disk space — backups require free space
- Verify database credentials are correct in `.env`
- Ensure the `storage/app/backup/` directory is writable
- Check the failed backup's error details in the table
- Try a database-only or storage-only backup to isolate the issue

---

**← Previous:** [User Profile & Recovery](09-user-profile-and-recovery.md)
**Next →** [Back to Manual Index](00-guide-index.md)
