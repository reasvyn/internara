# 01 — Identity & Access

> **Lifecycle:** User account creation → Authentication → Authorization → Account lifecycle
> **Domains:** `Auth` (identity), `User` (profile)
> **Tables:** 10 (`users`, `profiles`, `password_reset_tokens`, `sessions`, `login_history`, `suspicious_login_attempts`, `account_restrictions`, `account_recovery_codes`, `activation_tokens`, `account_status_history`)
> **Polymorphic:** `model_has_roles`, `model_has_permissions` (covered in [13-admin.md](13-admin.md))

---

## Purpose

Every action in the system traces back to a `users` row. This lifecycle manages digital identity — registration, authentication, password recovery, account restrictions, login auditing, and profile enrichment. The data here is the root of all authorization decisions (via Spatie RBAC) and the foundation for all other lifecycles.

---

## Tables

### users — System-wide identity

The root actor record. Every human interacting with the system is a user.
All Eloquent relationships point back here.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | Stable identifier, never reused |
| name | varchar(255) | NOT NULL | Display name (legal name on certificates) |
| email | varchar(255) | UNIQUE, NULLABLE | Login credential + notification channel. Nullable for legacy/system accounts |
| username | varchar(255) | UNIQUE, NOT NULL | Primary login identifier. Immutable after creation |
| email_verified_at | timestamp | NULLABLE | Null = unverified. Controls access to email-dependent features |
| password | varchar(255) | NOT NULL | Bcrypt hash. `password` PII-masked in all logs |
| remember_token | varchar(100) | NULLABLE | Laravel "remember me" session token |
| setup_required | boolean | DEFAULT false | First-run flag. True means user must complete profile setup |
| locked_at | timestamp | NULLABLE | When the lock was applied. Null = not locked |
| locked_reason | varchar(255) | NULLABLE | Why the account was locked (abuse, inactivity, admin) |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** UNIQUE on `email`, UNIQUE on `username`

**Foreign Keys:** Referenced by ~28 BelongsTo relationships across all domains. Deletion cascades to profiles, mentees, mentors, logbooks, attendances, submissions.

**Status machine:** No explicit status column. Account state is derived:
- Active: `locked_at IS NULL`
- Locked: `locked_at IS NOT NULL`
- Email unverified: `email_verified_at IS NULL AND email IS NOT NULL`
- Setup pending: `setup_required = true`

**Data lifecycle considerations:**
- Users are NEVER hard-deleted. Anonymize via GDPR log instead
- `username` is the stable external identifier (used in imports)
- `email` can change; `username` should not

---

### profiles — Extended personal data

One-to-one enrichment of user records. Stores PII that is accessed less frequently.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id), UNIQUE, CAS | Cascade delete syncs with user lifecycle |
| phone | varchar(255) | NULLABLE | Contact number for emergencies |
| address | text | NULLABLE | Home address |
| gender | varchar(255) | NULLABLE | Optional demographic |
| blood_type | varchar(255) | NULLABLE | Emergency medical info |
| pob | varchar(255) | NULLABLE | Place of birth (official documents) |
| dob | date | NULLABLE | Date of birth (age verification) |
| emergency_contact_name | varchar(255) | NULLABLE | Who to call in emergency |
| emergency_contact_phone | varchar(255) | NULLABLE | Emergency contact number |
| emergency_contact_address | text | NULLABLE | Emergency contact address |
| bio | text | NULLABLE | Free-form biography |
| national_identifier | varchar(255) | NULLABLE | Government ID number (KTP/NIK) |
| registration_number | varchar(255) | NULLABLE | Student registration number (NIS/NISN) |
| school_id | varchar(36) | FK → schools(id), SNU | Home institution. Set null if school deleted |
| department_id | varchar(36) | FK → departments(id), SNU | Home department. Set null if dept deleted |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Indexes:** UNIQUE on `user_id`, composite on `[national_identifier, registration_number]`

**Foreign Keys:**
- `user_id` → `users(id)` ON DELETE CASCADE — profile deleted with user
- `school_id` → `schools(id)` ON DELETE SET NULL — preserve profile if school removed
- `department_id` → `departments(id)` ON DELETE SET NULL — preserve profile if dept removed

**Data lifecycle considerations:**
- Profile is created immediately after user creation (via `HasOne` relationship)
- `national_identifier` + `registration_number` are the primary search path for student records
- Emergency contact fields are intentionally separate from user contact (different person)

---

### password_reset_tokens — Password recovery tokens

Standard Laravel password reset mechanism.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| email | varchar(255) | PK | Matches users.email (without FK — allows pre-registration resets) |
| token | varchar(255) | NOT NULL | Hashed reset token |
| created_at | timestamp | NULLABLE | Used for token expiration (default 60 min) |

**No foreign key** — intentionally decoupled so reset flow works before user is fully provisioned.

---

### sessions — PHP session store

Laravel session persistence. Created in the same migration as `users`.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(255) | PK | Session ID hash |
| user_id | varchar(36) | FK → users(id), idx | Nullable for guest sessions |
| ip_address | varchar(45) | NULLABLE | IPv4 or IPv6 |
| user_agent | text | NULLABLE | Browser/client identifier |
| payload | longText | NOT NULL | Serialized session data |
| last_activity | integer | idx | Unix timestamp for GC pruning |

**Indexes:** `user_id`, `last_activity`

---

### login_history — Authentication audit log

Append-only record of every login attempt. Used for security monitoring and "last login" display.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id), idx | Identifies the attempting user |
| ip_address | varchar(45) | NOT NULL, idx | Source IP for geo/abuse analysis |
| user_agent | varchar(255) | NULLABLE | Browser fingerprint |
| successful | boolean | DEFAULT false | True = authentication passed |
| failure_reason | varchar(255) | NULLABLE | 'invalid_password', 'locked_account', etc. |
| latitude | double | NULLABLE | Geo-IP derived |
| longitude | double | NULLABLE | Geo-IP derived |
| country | varchar(255) | NULLABLE | Geo-IP derived |
| city | varchar(255) | NULLABLE | Geo-IP derived |
| device_fingerprint | varchar(255) | NULLABLE | Optional device tracking |
| created_at | timestamp | | Timestamp of attempt |
| updated_at | timestamp | | |

**Indexes:** `user_id`, `ip_address`, `created_at`

**Data lifecycle:**
- Append-only (never updated after creation for failure records)
- Successful logins DO update `updated_at` (last successful login tracking)
- Retention: pruned by `system:cleanup` command

---

### suspicious_login_attempts — Flagged authentication events

Records detected when the fraud/anomaly detection system flags a login.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id) | Affected user |
| ip_address | varchar(45) | NOT NULL | Source IP |
| user_agent | varchar(255) | NULLABLE | |
| suspicions | text | NOT NULL | JSON array of triggered rules |
| actions_taken | text | NOT NULL | What the system did (rate limit, notify admin, etc.) |
| severity | varchar(255) | DEFAULT 'medium' | 'low', 'medium', 'high', 'critical' |
| user_verified | boolean | DEFAULT false | Admin reviewed = true |
| detected_at | timestamp | NOT NULL | When the suspicious pattern triggered |
| resolved_at | timestamp | NULLABLE | When admin closed the investigation |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Status values for `severity`:**
- `low` — Minor anomaly (new IP, new device)
- `medium` — Moderate (multiple failures then success)
- `high` — Significant (known malicious IP, brute force pattern)
- `critical` — Emergency (confirmed breach, account takeover)

---

### account_restrictions — Temporary/permanent access controls

Granular feature-level restrictions (not full lockout). Supports partial account limitations.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id) | Restricted user |
| restriction_type | varchar(255) | NOT NULL | 'login', 'feature', 'module' |
| restriction_key | varchar(255) | NOT NULL | Specific feature/module name |
| restriction_value | text | NOT NULL | Configuration for the restriction |
| reason | text | NULLABLE | Why this was applied |
| applied_by_user_id | varchar(36) | FK → users(id) | Admin who applied |
| applied_at | timestamp | NOT NULL | When the restriction took effect |
| expires_at | timestamp | NULLABLE | Null = permanent |
| is_active | boolean | DEFAULT true | Toggle without deleting |
| metadata | text | NULLABLE | Additional context |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Restriction types:**
- `login` — Blocks login entirely (overrides `users.locked_at`)
- `feature` — Blocks specific feature access (e.g., "cannot submit assignments")
- `module` — Blocks entire domain module (e.g., "cannot access Assessment module")

---

### account_recovery_codes — One-time recovery tokens

Pre-generated codes for account recovery when password is lost and email is inaccessible.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id) | Code owner |
| code_hash | varchar(255) | NOT NULL | Bcrypt/SHA-256 of the recovery code |
| generated_at | timestamp | NULLABLE | Batch generation timestamp |
| used_at | timestamp | NULLABLE | Null = unused. One-time use only |
| expires_at | timestamp | NULLABLE | Code validity window |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Constraints:** Multiple codes per user (typically 8-10). Each code is single-use.

---

### activation_tokens — Email verification tokens

Used for email verification and account activation flows.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | FK → users(id) | Token owner |
| token | varchar(255) | NOT NULL | Random token string |
| token_type | varchar(255) | DEFAULT 'email' | 'email', 'registration', 'password_change' |
| expires_at | timestamp | NOT NULL | Token validity deadline |
| attempts | integer | DEFAULT 0 | Failed verification attempts |
| last_attempt_at | timestamp | NULLABLE | Anti-brute-force tracking |
| created_at | timestamp | | |
| updated_at | timestamp | | |

**Token types:**
- `email` — Email verification
- `registration` — Post-registration activation
- `password_change` — Secure password change confirmation

---

### account_status_history — Immutable status audit log

Append-only record of every user status change. No `updated_at` — entries are never modified.

| Column | Type | Constraints | Purpose |
|---|---|---|---|
| id | varchar(36) | PK, UUID | |
| user_id | varchar(36) | NOT NULL, idx | Subject user |
| old_status | varchar(255) | NULLABLE | Previous state (null on first entry) |
| new_status | varchar(255) | NOT NULL | New state after transition |
| reason | text | NULLABLE | Why the status changed |
| triggered_by_user_id | varchar(36) | NULLABLE | Who/what caused the change |
| triggered_by_role | varchar(255) | NULLABLE | Role at time of action |
| ip_address | varchar(45) | NULLABLE | Source of the status change request |
| user_agent | text | NULLABLE | |
| metadata | text | NULLABLE | Additional context (JSON) |
| created_at | timestamp | NOT NULL | When the change occurred |

**No `updated_at`** — this is an append-only log. No updates, no deletes.

---

## Key Queries & Traversal

### Find all active students with pending setup:

```sql
SELECT u.*, p.registration_number
FROM users u
LEFT JOIN profiles p ON p.user_id = u.id
JOIN model_has_roles mhr ON mhr.model_id = u.id
JOIN roles r ON r.id = mhr.role_id
WHERE u.setup_required = 1
  AND u.locked_at IS NULL
  AND r.name = 'student';
```

### Detect brute force attempts:

```sql
SELECT user_id, COUNT(*) as attempts, MAX(created_at) as last
FROM login_history
WHERE successful = 0
  AND created_at > NOW() - INTERVAL 15 MINUTE
GROUP BY user_id
HAVING COUNT(*) > 10;
```

---

## Cross-Lifecycle References

| Table | Referenced By | Lifecycle |
|---|---|---|
| `users.id` | ~28 tables across all domains | All |
| `profiles.user_id` | `users.id` (1:1) | 01-auth |
| `profiles.school_id` | `schools.id` | 02-institution |
| `profiles.department_id` | `departments.id` | 02-institution |

---

## Data Growth Projections

| Table | Growth Rate | Typical Row Size | Notes |
|---|---|---|---|
| `users` | Low (per enrollment period) | ~500 bytes | Stable, rarely deleted |
| `profiles` | Low (1:1 with users) | ~1 KB | |
| `login_history` | Medium (2-10 per user/day) | ~400 bytes | Prune after 90 days |
| `suspicious_login_attempts` | Low | ~1 KB | Reviewed manually |
| `account_status_history` | Low (per lifecycle event) | ~500 bytes | Permanent audit trail |
