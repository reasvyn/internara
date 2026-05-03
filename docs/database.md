# Database Documentation: Internara

## 1. Overview

Internara uses Laravel's database abstraction layer with support for multiple database drivers. The
primary development database is **SQLite**, with production support for **MySQL** and
**PostgreSQL**.

### Configuration

- **Config File**: `config/database.php`
- **Environment Variables**: `DB_CONNECTION`, `DB_HOST`, `DB_DATABASE`, etc.
- **Default Connection**: SQLite (`.env` → `DB_CONNECTION=sqlite`)

## 2. Database Connections

### SQLite (Default for Development)

```env
DB_CONNECTION=sqlite
DB_DATABASE=/absolute/path/to/database/database.sqlite
```

- File: `database/database.sqlite`
- Used for: Local development, testing, feature tests
- Options: `foreign_key_constraints=true`, `busy_timeout`, `journal_mode`, `synchronous`

### MySQL (Production Ready)

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=internara
DB_USERNAME=root
DB_PASSWORD=
```

- Supports: Production deployments, CI testing
- Character set: `utf8mb4`, Collation: `utf8mb4_unicode_ci`

### PostgreSQL (Optional)

- Also configured in `config/database.php`
- Uncomment and configure as needed

## 3. Migration System

### Migration Files (`database/migrations/`)

#### Core Tables (Batch 1)

| Table        | Migration         | Purpose                                    |
| ------------ | ----------------- | ------------------------------------------ |
| `setups`     | 2026_04_29_000001 | System installation status                 |
| `audit_logs` | 2026_04_29_092442 | System audit trail (Spatie Activity Log)   |
| `pulse_*`    | 2026_04_29_092623 | Laravel Pulse monitoring tables (5 tables) |
| `users`      | 2026_04_29_092750 | User authentication & profiles             |
| `settings`   | 2026_04_29_100804 | System & user settings                     |
| `cache`      | 2026_04_29_104704 | Application cache storage                  |
| `jobs`       | 2026_04_29_104707 | Queue jobs storage                         |

#### Authentication & Authorization

| Table                   | Migration            | Purpose                     |
| ----------------------- | -------------------- | --------------------------- |
| `password_reset_tokens` | (in users migration) | Password reset tokens       |
| `sessions`              | (in users migration) | User sessions               |
| `activation_tokens`     | 2026_04_23_203431    | Setup activation tokens     |
| `permissions`           | 2026_04_29_120000    | Spatie Permissions          |
| `roles`                 | 2026_04_29_120000    | Spatie Roles                |
| `model_has_permissions` | 2026_04_29_120000    | Role-Permission assignments |
| `model_has_roles`       | 2026_04_29_120000    | User-Role assignments       |
| `role_has_permissions`  | 2026_04_29_120000    | Role-Permission mappings    |

#### School & Department

| Table                   | Migration         | Purpose                           |
| ----------------------- | ----------------- | --------------------------------- |
| `schools`               | 2026_04_29_105433 | School profile data               |
| `departments`           | 2026_04_29_105436 | Academic departments              |
| `department_competency` | 2026_04_30_021949 | Pivot: departments ↔ competencies |

#### Internship System

| Table                      | Migration         | Purpose                  |
| -------------------------- | ----------------- | ------------------------ |
| `internships`              | 2026_04_29_105438 | Internship programs      |
| `internship_companies`     | 2026_04_29_112711 | Company partners         |
| `internship_placements`    | 2026_04_29_112700 | Mentee placements       |
| `internship_registrations` | 2026_04_29_112702 | Mentee registrations    |
| `requirement_submissions`  | 2026_04_29_113312 | Mentee submissions      |

#### Academic & Assessment

| Table                     | Migration         | Purpose                     |
| ------------------------- | ----------------- | --------------------------- |
| `assignments`             | 2026_04_30_021949 | Mentee assignments         |
| `assignment_types`        | 2026_04_30_021953 | Assignment categories       |
| `submissions`             | 2026_04_30_021952 | Assignment submissions      |
| `assessments`             | 2026_04_30_021953 | Assessment records          |
| `competencies`            | 2026_04_30_021952 | Competency definitions      |
| `student_competency_logs` | 2026_04_30_021953 | Mentee competency tracking |

#### Attendance & Journal

| Table              | Migration         | Purpose                  |
| ------------------ | ----------------- | ------------------------ |
| `attendance_logs`  | 2026_04_29_111619 | Daily attendance records |
| `absence_requests` | 2026_04_29_111622 | Absence requests         |
| `journal_entries`  | 2026_04_29_114909 | Mentee journal entries  |

#### Documents & Media

| Table                | Migration              | Purpose                     |
| -------------------- | ---------------------- | --------------------------- |
| `document_templates` | 2026_04_29_113725      | Official document templates |
| `official_documents` | 2026_04_29_114925      | Generated PDF documents     |
| `media`              | (Spatie Media Library) | File attachments            |

#### Monitoring & Evaluation

| Table               | Migration         | Purpose                     |
| ------------------- | ----------------- | --------------------------- |
| `supervision_logs`  | 2026_04_29_115847 | Mentor supervision records (school_teacher or industry_supervisor) |
| `monitoring_visits` | 2026_04_29_115850 | Company site visits         |

#### Account Management

| Table                       | Migration         | Purpose                         |
| --------------------------- | ----------------- | ------------------------------- |
| `profiles`                  | 2026_04_29_105301 | Extended user profiles          |
| `account_status_history`    | 2026_04_23_123729 | Account status changes          |
| `account_restrictions`      | 2026_04_23_123832 | Account restrictions            |
| `super_admin_approvals`     | 2026_04_23_203502 | Admin approval workflow         |
| `gdpr_deletion_logs`        | 2026_04_23_203503 | GDPR compliance logs            |
| `login_and_clone_detection` | 2026_04_23_222100 | Security: clone/login detection |
| `notifications`             | 2026_04_30_022555 | User notifications              |

## 4. Key Database Standards

### S1 - Secure: UUID Primary Keys

All models use UUIDs instead of auto-incrementing IDs:

```php
// Migration
$table->uuid('id')->primary();

// Model
use App\Models\Concerns\HasUuid;
class User extends Model
{
    use HasUuid;
}
```

**Benefits**:

- Non-guessable IDs (security)
- Safe for public APIs
- No information leakage about record counts

### S2 - Sustain: Mass Assignment Protection

All models must use Laravel 13 PHP 8 Attributes for mass assignment:

```php
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['name', 'email', 'username', 'password', 'setup_required'])]
class User extends Model
{
    // No $fillable property needed
}
```

**Note**: `@see` docs/standards.md for Laravel 13 Modernization guidelines.

### S3 - Scalable: Indexes & Foreign Keys

- Foreign key constraints enabled by default
- Compound indexes added for high-growth tables (`add_compound_indexes_to_high_growth_tables`)
- UUID foreign keys: `$table->foreignUuid('user_id')->constrained()->cascadeOnDelete()`

## 5. Factories & Seeders

### Model Factories (`database/factories/`)

Factories exist for all major models. Key factories:

| Factory                         | Model                  | Purpose                |
| ------------------------------- | ---------------------- | ---------------------- |
| `UserFactory`                   | User                   | Authentication testing |
| `ProfileFactory`                | Profile                | User profiles          |
| `SchoolFactory`                 | School                 | School data            |
| `DepartmentFactory`             | Department             | Department data        |
| `InternshipFactory`             | Internship             | Internship programs    |
| `InternshipCompanyFactory`      | InternshipCompany      | Company partners       |
| `InternshipPlacementFactory`    | InternshipPlacement    | Placements             |
| `InternshipRegistrationFactory` | InternshipRegistration | Registrations          |
| `AssignmentFactory`             | Assignment             | Assignments            |
| `AssignmentTypeFactory`         | AssignmentType         | Assignment types       |
| `SubmissionFactory`             | Submission             | Submissions            |
| `AssessmentFactory`             | Assessment             | Assessments            |
| `CompetencyFactory`             | Competency             | Competencies           |
| `StudentCompetencyLogFactory`   | StudentCompetencyLog   | Competency logs        |
| `NotificationFactory`           | Notification           | Notifications          |
| `SettingFactory`                | Setting                | System settings        |
| `DepartmentCompetencyFactory`   | DepartmentCompetency   | Pivot table            |

### Database Seeders (`database/seeders/`)

- **DatabaseSeeder**: Main seeder (calls other seeders)
- **AppSettingSeeder**: System settings initialization

Run seeders:

```bash
php artisan db:seed
php artisan db:seed --class=AppSettingSeeder
```

## 6. Testing & Database

### In-Memory SQLite (Fastest for Testing)

```php
// phpunit.xml
<env name="DB_CONNECTION" value="sqlite"/>
<env name="DB_DATABASE" value=":memory:"/>
```

- Used by: Pest PHP tests
- Benefits: Fast, isolated, no file cleanup

### RefreshDatabase Trait

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

class TestCase extends TestCase
{
    use RefreshDatabase; // Rolls back after each test
}
```

### Running Migrations for Tests

```bash
# Fresh migrate with seed
php artisan migrate:fresh --seed

# Test with coverage
./vendor/bin/pest --coverage
```

## 7. Eloquent Models (`app/Models/`)

### Core Models

All models use:

- `HasUuid` trait (UUID primary keys)
- `strict_types=1`
- Laravel 13 PHP 8 Attributes (`#[Fillable]`, `#[Hidden]`, `#[Appends]`)
- `casts(): array` method for attribute casting
- Business rules in model methods

Key models:

- **User**: Authentication, roles, profile relationship
- **Internship**: Program management, status tracking
- **InternshipRegistration**: Mentee applications
- **Assignment/Submission**: Academic workflow
- **Assessment**: Grading & feedback
- **AttendanceLog**: Daily attendance
- **JournalEntry**: Mentee journals

### Relationships

Example: User model relationships

```php
public function profile(): HasOne { return $this->hasOne(Profile::class); }
public function registrations(): HasMany { return $this->hasMany(InternshipRegistration::class, 'student_id'); }
public function teachingRegistrations(): HasMany { return $this->hasMany(InternshipRegistration::class, 'mentor_id'); }
public function mentoringRegistrations(): HasMany { return $this->hasMany(InternshipRegistration::class, 'mentor_id'); }
```

## 8. Spatie Packages Integration

### Activity Log (`spatie/laravel-activitylog`)

- Table: `activity_log` (via `audit_logs` migration)
- Tracks: Model changes, user actions, system events
- Usage: `activity()->log('User created internship')`

### Permission (`spatie/laravel-permission`)

- Tables: `permissions`, `roles`, `model_has_permissions`, `model_has_roles`, `role_has_permissions`
- Features: Role-based access control (RBAC), permissions

### Media Library (`spatie/laravel-medialibrary`)

- Table: `media`
- Features: File uploads, conversions, UUID protection

### Model Status (`spatie/laravel-model-status`)

- Added to models: `use HasStatuses;`
- Tracks: Status transitions (e.g., Pending → Active → Archived)

## 9. Pulse Monitoring Tables

Laravel Pulse creates 5 tables:

- `pulse_aggregates`: Performance metrics
- `pulse_entries`: Request logs
- `pulse_values`: System values
- `pulse_notifications`: Pulse notifications
- Uses: Real-time monitoring at `/pulse`

## 10. Database Maintenance

### Backup

```bash
# SQLite
cp database/database.sqlite database/backups/database_$(date +%Y%m%d).sqlite

# MySQL
mysqldump -u root -p internara > backup_$(date +%Y%m%d).sql
```

### Optimization

```bash
php artisan db:optimize        # Optimize database
php artisan cache:clear         # Clear query cache
php artisan config:clear        # Clear config cache
```

### Health Check

```bash
php artisan about           # Show database connection status
php artisan migrate:status   # Check migration status
```

## 11. GDPR & Data Privacy

### Data Deletion Logs

- Table: `gdpr_deletion_logs`
- Purpose: Track user data deletion requests (GDPR compliance)
- Retention: Logs kept for audit (not user data)

### Account Lifecycle

- Tables: `account_status_history`, `account_restrictions`
- Statuses: Pending → Active → Idle → Archived → Deactivated
- Automated: Pulse monitors inactivity

## 12. Performance Considerations

### High-Growth Tables

Indexed for performance:

- `audit_logs`: `subject_type`, `subject_id`, `created_at`
- `internship_registrations`: `student_id`, `internship_id`, `status`
- `attendance_logs`: `student_id`, `date`
- `journal_entries`: `student_id`, `created_at`

### Query Optimization

- Use eager loading: `$internship->load('registrations.mentee')`
- Avoid N+1: Use `with()` in Repositories
- Pagination: `->paginate(20)` for list endpoints

---

**Last Updated**: April 30, 2026
