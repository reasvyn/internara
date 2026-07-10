# Chapter 22: System Observability

> **Last updated:** 2026-06-16 **Changes:** sync — initial metadata sync with new format

## Description

This chapter covers system observability tools: **health checks**, **Laravel Pulse monitoring**,
**audit logs**, **system cleanup**, and **backup management**.

---

## 22.1 System Health Check

The `system:health` command performs a comprehensive 15-point system verification. Run it anytime to
diagnose problems:

```bash
php artisan system:health
```

### 22.1.1 What It Checks

| #   | Check                  | What It Verifies                                       |
| --- | ---------------------- | ------------------------------------------------------ |
| 1   | Environment            | `.env` file exists                                     |
| 2   | Setup Status           | System has been installed                              |
| 3   | PHP Version            | PHP 8.4 or higher                                      |
| 4   | Required Extensions    | All mandatory PHP extensions loaded                    |
| 5   | Recommended Extensions | Redis, PCNTL, POSIX (optional but recommended)         |
| 6   | PHP Memory             | At least 128 MB (256 MB recommended)                   |
| 7   | Database               | Connection works and tables exist                      |
| 8   | Migrations             | All migration files have been run                      |
| 9   | Storage                | Storage directories are writable                       |
| 10  | Disk Space             | Less than 85% disk usage (warning at 85%, fail at 95%) |
| 11  | Queue                  | No excessive failed jobs                               |
| 12  | Cache                  | Cache driver responds to read and write                |
| 13  | App Key                | Application key is set and valid                       |
| 14  | Storage Link           | `public/storage` symlink exists                        |
| 15  | Maintenance Mode       | Application is live (not in maintenance mode)          |

Output example:

```
  ╔══════════════════════════════════════════════════╗
  ║          Internara System Health Check           ║
  ╚══════════════════════════════════════════════════╝

  Environment ............. 2026-06-16 14:30:00
  App Env ................. local

  Service                        Status    Details
  ------------------------------------------------------------
  Environment                     OK        .env file detected
  Setup Status                   OK        System installed (6 steps completed)
  PHP Version                    OK        PHP 8.4.4 (required: 8.4.0+)
  Extensions                     OK        All 12 required extensions loaded
  Recommended Extensions         WARN      Missing: redis, pcntl
  PHP Memory                     OK        256M (minimum 128M met)
  Database                       OK        sqlite — connected, 55 tables
  Migration Status               OK        All 53 migrations up to date
  Storage                        OK        All storage directories are writable
  Disk Space                     OK        12% used — 42.5 GB free
  Queue                          OK        0 pending, 0 failed
  Cache                          OK        Cache driver responding
  App Key                        OK        Application key is set and valid
  Storage Link                   OK        public/storage link exists
  Maintenance Mode               OK        Application is live
```

### 22.1.2 JSON Output

For integration with external monitoring tools:

```bash
php artisan system:health --json
```

Returns a JSON array of all checks with pass/fail/warn status.

---

## 22.2 Laravel Pulse Dashboard

Laravel Pulse provides a real-time performance monitoring dashboard. It is accessible at `/pulse`
and restricted to administrators.

### 22.2.1 Standard Pulse Recorders

| Recorder      | What It Tracks                           | Threshold |
| ------------- | ---------------------------------------- | --------- |
| Slow Queries  | Database queries exceeding the threshold | 1,000 ms  |
| Slow Requests | HTTP requests exceeding the threshold    | 1,000 ms  |
| Slow Jobs     | Queued jobs exceeding the threshold      | 1,000 ms  |
| Exceptions    | All unhandled exceptions                 | Always    |
| Cache         | Cache hit/miss ratio                     | Always    |
| Queues        | Queue throughput                         | Always    |

### 22.2.2 Custom Internara Pulse Cards

| Card                   | What It Displays                                    |
| ---------------------- | --------------------------------------------------- |
| **Registrations Card** | Total, pending, active, and completed registrations |
| **System Card**        | Total users and unread notifications                |

### 22.2.3 Recording Snapshots

Custom Internara metrics are recorded via a scheduled command:

```bash
php artisan pulse:record-snapshots
```

This runs every hour via the scheduler and records registration lifecycle data and system statistics
for the custom Pulse dashboard cards.

---

## 22.3 Audit Logs

The audit log (activity log) records every significant action in the system: who did what, when, and
to whom.

Navigate to **Admin → Audit Log** to view the activity log.

### 22.3.1 What Is Logged

| Category           | Examples                                             |
| ------------------ | ---------------------------------------------------- |
| User actions       | User created, role assigned, account activated       |
| Internship actions | Program created, student registered, grade finalised |
| Assessment actions | Rubric created, assessment finalised                 |
| System actions     | Setting changed, backup created, announcement sent   |

### 22.3.2 Viewing the Audit Log

- **Filter by user** — see actions performed by a specific person
- **Filter by module** — focus on a specific module (e.g., Assessment, Journals)
- **Filter by action** — search for specific event types
- **Sort by date** — newest or oldest first

### 22.3.3 Audit Log Retention

Audit logs are retained for 365 days by default. The `system:cleanup` command prunes older entries
automatically via the scheduler.

---

## 22.4 System Cleanup

The `system:cleanup` command performs routine maintenance:

```bash
php artisan system:cleanup
```

### 22.4.1 What It Cleans

| Task                    | Description                                    |
| ----------------------- | ---------------------------------------------- |
| Expired password resets | Remove old password reset tokens               |
| Stale cache tags        | Prune expired cache entries                    |
| Failed jobs             | Remove old failed queue job records            |
| Activity log            | Prune activity log entries older than 365 days |
| Media library           | Remove orphaned media records                  |
| Old log files           | Remove Laravel log files older than 30 days    |

### 22.4.2 Automatic Cleanup

The cleanup command runs automatically every night via the scheduler. You can also run it manually:

```bash
# With confirmation prompt
php artisan system:cleanup

# Skip confirmation
php artisan system:cleanup --force

# Custom log retention (keep logs for 60 days)
php artisan system:cleanup --log-retention=60
```

---

## 22.5 Cache Warming

To improve first-request performance after a deployment, run the cache warming command:

```bash
php artisan system:cache-warm
```

This pre-warms:

- **Settings cache** — application configuration values
- **Brand cache** — colour scheme and branding values
- **Config cache** — all Laravel config files
- **View cache** — compiled Blade templates
- **Event cache** — registered event/listener mappings

---

## 22.6 System Backups

The backup system allows super administrators to schedule and manage database and file backups.

### 22.6.1 Available Commands

| Command                                     | Description                            |
| ------------------------------------------- | -------------------------------------- |
| `php artisan system:backup`                 | Run a backup using configured settings |
| `php artisan system:backup --type=database` | Database only                          |
| `php artisan system:backup --type=storage`  | Uploaded files only                    |
| `php artisan system:backup --type=both`     | Both database and files                |
| `php artisan system:backup --force`         | Skip pre-flight checks                 |

### 22.6.2 Managing Backups

Navigate to **Admin → Backups** to:

- View backup history with status and file size
- Run a manual backup
- Delete old backups

### 22.6.3 Backup Configuration

Backup settings are configured in **System Settings → Backups**:

| Setting          | Description                        | Default |
| ---------------- | ---------------------------------- | ------- |
| Auto Backup      | Enable scheduled automatic backups | Off     |
| Frequency        | How often to run backups           | Daily   |
| Schedule Time    | Time of day for backup             | 02:00   |
| Retention        | Days to keep backups               | 30 days |
| Include Database | Include database dump              | Yes     |
| Include Storage  | Include uploaded files             | Yes     |

### 22.6.4 Storage Location

Backup files are stored at `storage/app/backup/` and are not publicly accessible. Naming convention:
`backup_{type}_{date}.{ext}`.

---

## 22.7 GDPR Deletion Logs

When a user's data is deleted for GDPR compliance, a record of the deletion is kept in the **GDPR
Deletion Logs**. This ensures compliance audit capability without retaining the actual user data.

Navigate to **Admin → GDPR Logs** to view the deletion history.

---

## 22.8 Account Clone Detection

The **Account Clone Detector** scans for users who may have multiple accounts with similar
identifying information (same email patterns, names, or ID numbers). This helps administrators
identify and merge duplicate accounts.

---

## 22.9 Scheduled Tasks

The following tasks run automatically via the scheduler:

| Frequency    | Command                  | Purpose                           |
| ------------ | ------------------------ | --------------------------------- |
| Every minute | `announcements:publish`  | Publish scheduled announcements   |
| Hourly       | `pulse:record-snapshots` | Record Pulse custom metrics       |
| Hourly       | `pulse:check`            | Pulse health monitoring           |
| Daily        | `system:cleanup`         | Prune old data                    |
| Daily        | `system:backup`          | Run scheduled backup (if enabled) |

---

## 22.10 Troubleshooting

### Health check shows FAIL

Each failed check includes a description of the problem. Common fixes:

- **PHP version** — upgrade to PHP 8.4+
- **Extensions missing** — install the listed PHP extensions
- **Database connection** — check your `.env` database settings
- **Storage not writable** — run `chmod -R 775 storage bootstrap/cache`
- **Storage link missing** — run `php artisan storage:link`

### Pulse dashboard shows no data

Pulse records are ingested on each request. Give the system a few minutes of traffic before
expecting data. For custom Internara cards, run `php artisan pulse:record-snapshots` manually.

### Backup failed

- Check disk space (backups require significant free space)
- Verify database connection settings
- Check the queue worker is running for large backups
- View the failed backup details in the backup history

### Cleanup command reports errors

Some cleanup tasks depend on specific tables. If a package is not installed, its cleanup task will
be skipped with a warning. This is normal and not a cause for concern.

---

**← Previous: [Chapter 21: Announcement & Notifications](21-announcement-and-notifications.md)**
