# 14 — Infrastructure

> **Laravel/Spatie system tables.** Not part of business data, but essential for application operation.
> **Tables:** 11

---

## Purpose

Internal framework and package tables. These handle caching, queue processing, file storage, session management, performance monitoring, and migration tracking. They do not represent business concepts and should not be directly queried in application code.

---

## Tables

### cache / cache_locks (Laravel Cache)

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| key | varchar(255) | PK | Cache key |
| value | text | NOT NULL | Serialized cache value |
| expiration | integer | NOT NULL | Unix timestamp for TTL |

**cache_locks:**

| Column | Type | Constraints |
|---|---|---|
| key | varchar(255) | PK |
| owner | varchar(255) | NOT NULL |
| expiration | integer | NOT NULL |

**Driver:** Configurable via `CACHE_STORE` env. Default: `file`. Production: `redis` recommended.

### jobs / job_batches / failed_jobs (Laravel Queue)

**jobs** — Pending queue items:

| Column | Type | Constraints |
|---|---|---|
| id | integer | PK, AUTO_INCREMENT |
| queue | varchar(255) | NOT NULL, idx |
| payload | text | NOT NULL |
| attempts | integer | NOT NULL |
| reserved_at | integer | NULLABLE |
| available_at | integer | NOT NULL |
| created_at | integer | NOT NULL |

**job_batches** — Batch job tracking:

| Column | Type | Constraints |
|---|---|---|
| id | varchar(255) | PK |
| name | varchar(255) | NOT NULL |
| total_jobs | integer | NOT NULL |
| pending_jobs | integer | NOT NULL |
| failed_jobs | integer | NOT NULL |
| failed_job_ids | text | NOT NULL |
| options | text | NULLABLE |
| cancelled_at | integer | NULLABLE |
| created_at | integer | NOT NULL |
| finished_at | integer | NULLABLE |

**failed_jobs** — Failed job persistence:

| Column | Type | Constraints |
|---|---|---|
| id | integer | PK, AUTO_INCREMENT |
| uuid | varchar(255) | NOT NULL |
| connection | text | NOT NULL |
| queue | text | NOT NULL |
| payload | text | NOT NULL |
| exception | text | NOT NULL |
| failed_at | datetime | NOT NULL DEFAULT CURRENT_TIMESTAMP |

**Queue driver:** Configurable via `QUEUE_CONNECTION` env. Default: `database`. Production: `redis` recommended.

**Critical:** The queue worker (`php artisan queue:work`) must be running for notifications, media conversions, and scheduled tasks.

### sessions (Laravel Session)

| Column | Type | Constraints |
|---|---|---|
| id | varchar(255) | PK |
| user_id | varchar(36) | NULLABLE, idx |
| ip_address | varchar(45) | NULLABLE |
| user_agent | text | NULLABLE |
| payload | longText | NOT NULL |
| last_activity | integer | NOT NULL, idx |

### media (Spatie Media Library)

Polymorphic file attachment storage.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | integer | PK, AUTO_INCREMENT | |
| model_type | varchar(255) | NOT NULL | Polymorphic owner model |
| model_id | integer | NOT NULL | Polymorphic owner ID |
| uuid | varchar(255) | NULLABLE | Stable identifier for URL generation |
| collection_name | varchar(255) | NOT NULL | Named collection (e.g., 'avatar', 'document', 'certificate') |
| name | varchar(255) | NOT NULL | Original filename without extension |
| file_name | varchar(255) | NOT NULL | Stored filename with extension |
| mime_type | varchar(255) | NULLABLE | MIME type detection |
| disk | varchar(255) | NOT NULL | Filesystem disk name |
| conversions_disk | varchar(255) | NULLABLE | Separate disk for generated conversions |
| size | integer | NOT NULL | File size in bytes |
| manipulations | text | NOT NULL | JSON: applied image manipulations |
| custom_properties | text | NOT NULL | JSON: application-specific metadata |
| generated_conversions | text | NOT NULL | JSON: generation status of conversions |
| responsive_images | text | NOT NULL | JSON: responsive image variants |
| order_column | integer | NULLABLE | Sorting within collection |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Usage:** Attach files to any model via `$model->addMedia($file)->toMediaCollection('collection_name')`. The `collection_name` differentiates file purposes (e.g., a Registration model might have 'identity_card', 'report_document', 'certificate_photo' collections).

### pulse_aggregates / pulse_entries / pulse_values (Laravel Pulse)

Laravel Pulse performance monitoring tables.

**pulse_entries** — Raw performance data:

| Column | Type |
|---|---|
| id | integer PK |
| timestamp | integer |
| type | varchar(255) |
| key | text |
| key_hash | varchar(255) |
| value | integer (nullable) |

**pulse_aggregates** — Bucketed aggregations:

| Column | Type |
|---|---|
| id | integer PK |
| bucket | integer |
| period | integer |
| type | varchar(255) |
| key | text |
| key_hash | varchar(255) |
| aggregate | varchar(255) |
| value | numeric |
| count | integer (nullable) |

**pulse_values** — Slowest/fastest records:

| Column | Type |
|---|---|
| id | integer PK |
| timestamp | integer |
| type | varchar(255) |
| key | text |
| key_hash | varchar(255) |
| value | text |

**Retention:** Pulse data is auto-pruned by the pulse:work command. Configure retention in `config/pulse.php`.

### migrations (Laravel)

| Column | Type | Constraints |
|---|---|---|
| id | integer | PK, AUTO_INCREMENT |
| migration | varchar(255) | NOT NULL |
| batch | integer | NOT NULL |

---

## Operational Notes

### Queue Worker
```bash
php artisan queue:work           # Process jobs
php artisan queue:listen         # Development mode (restarts on code change)
```

### Cache Commands
```bash
php artisan cache:clear          # Flush application cache
php artisan config:cache         # Config optimization (production)
php artisan optimize:clear       # Clear all caches (development)
```

### Media
```bash
php artisan media-library:clean  # Remove orphaned media records
```

### Pulse
```bash
php artisan pulse:work           # Record performance entries
php artisan pulse:check          # Verify Pulse is recording
```

### Maintenance
```bash
php artisan queue:prune-failed   # Clean failed jobs
php artisan system:cleanup       # Prune logs, expired records, temp files
```
