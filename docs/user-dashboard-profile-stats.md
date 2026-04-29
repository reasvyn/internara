# 📊 User Dashboard, Profile & Managerial Stats

Enterprise-grade documentation for Internara's dashboard system, profile management, and analytics widgets built with **3S Doctrine** (Secure, Sustain, Scalable).

---

## Table of Contents

1. [Feature Overview](#-feature-overview)
2. [Architecture & 3S Compliance](#-architecture--3s-compliance)
3. [Security Features (S1)](#-security-features-s1)
4. [Sustainability Features (S2)](#-sustainability-features-s2)
5. [Scalability Features (S3)](#-scalability-features-s3)
6. [User Dashboards](#-user-dashboards)
7. [Profile Management](#-profile-management)
8. [Managerial Stats & Widgets](#-managerial-stats--widgets)
9. [Technical Implementation](#-technical-implementation)
10. [Testing & Quality](#-testing--quality)
11. [Troubleshooting](#-troubleshooting)

---

## 🎯 Feature Overview

### User Dashboards
Role-based dashboard system providing contextual information for each user type:
- **Admin Dashboard**: Institutional summary, at-risk students, activity feed, system status
- **Student Dashboard**: Internship program info, final grades, certificate downloads
- **Teacher Dashboard**: Assigned students, readiness status, supervision tools
- **Mentor Dashboard**: Company interns, mentoring timeline, evaluation tools

### Profile Management
Comprehensive profile system with:
- Role-specific fields (NIP for teachers, National ID for students)
- Security tab with password change and current password verification
- Avatar upload with validation (image type, 1MB max)
- PII (Personally Identifiable Information) encryption in logs

### Managerial Stats & Widgets
Modular statistics and widget system:
- **AnalyticsAggregator**: Centralized stats with caching (5-15 minutes TTL)
- **Stat Component**: Reusable card component with icon, title, value, variant
- **Widget System**: Slot-based injection for flexible dashboard composition
- **ActivityWidget**: Recent activity feed with causer information
- **AppInfoWidget**: Application metadata and credits

---

## 🏗️ Architecture & 3S Compliance

### 3S Doctrine Implementation

| Dimension | Implementation | Status |
|-----------|----------------|--------|
| **S1 (Secure)** | • `current_password` validation for password changes<br>• Timing-safe token comparison (`hash_equals()`)<br>• PII masking in logs<br>• Authorization checks on all dashboards | ✅ Compliant |
| **S2 (Sustain)** | • PSR-12 compliance (`declare(strict_types=1)`)<br>• 90%+ test coverage (Pest PHP)<br>• Localized strings (`__('module::key')`)<br>• No hardcoded strings in views | ✅ Compliant |
| **S3 (Scalable)** | • UUID-ready architecture<br>• Service contracts for loose coupling<br>• Slot system for widget injection<br>• Cache layer with TTL (5-15 min) | ✅ Compliant |

### Service Layer Architecture

```
┌─────────────────────────────────────────────────────────────┐
│                    UI Layer (Livewire)                      │
│  Dashboard.php, Profile/Index.php, Widgets/*.php         │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                  Service Layer (Contracts)                  │
│  DashboardService, ProfileService, AnalyticsAggregator     │
└───────────────────────────┬─────────────────────────────────┘
                            │
┌───────────────────────────▼─────────────────────────────────┐
│                     Data Layer (Eloquent)                   │
│  User, Profile, Registration, Activity (spatie/laravel-   │
│  activitylog)                                             │
└─────────────────────────────────────────────────────────────┘
```

---

## 🔐 Security Features (S1)

### Authentication & Authorization

**Dashboard Access Control:**
```php
// Admin Dashboard - Role-based access
abort_unless(
    auth()->user()->role === 'super_admin' || auth()->user()->role === 'admin',
    403
);

// Teacher/Mentor - Scoped to own data
public function getStudentsProperty(): Collection
{
    return $service->get(['teacher_id' => auth()->id()]);
}
```

**Profile Security:**
- ✅ **Password Change**: Requires `current_password` validation rule
- ✅ **Avatar Upload**: Validates image type (`jpg,png,gif`) and size (max 1MB)
- ✅ **Email Uniqueness**: Excludes current user from unique check
- ✅ **Role-Specific Fields**: Protected from unauthorized updates

### Data Protection

**PII Masking (Log Module):**
```php
// modules/Log/src/Logging/PiiMaskingProcessor.php
class PiiMaskingProcessor
{
    public function __invoke(array $record): array
    {
        // Mask emails, phones, national IDs in logs
        $record['message'] = $this->maskPii($record['message']);
        return $record;
    }
}
```

**Sensitive Data Handling:**
- ❌ No PII in URL parameters
- ❌ No sensitive data in error messages
- ✅ Encrypted setup tokens (AES-256 via `Crypt::encrypt()`)
- ✅ Activity logs exclude sensitive fields automatically (spatie/activitylog)

### Input Validation

**Profile Validation Rules:**
```php
// modules/Profile/src/Livewire/Index.php
protected array $rules = [
    'name' => 'required|string|max:255',
    'email' => 'required|email|unique:users,email,' . auth()->id(),
    'current_password' => 'required_with:password|current_password',
    'password' => 'nullable|string|min:8|confirmed',
    'avatar' => 'nullable|image|max:1024', // 1MB
];
```

**Dashboard Stats:**
- ✅ Cache layer prevents query exhaustion (DoS mitigation)
- ✅ Rate limiting on setup routes (20 attempts/IP/60s)
- ✅ Timing-safe comparisons for tokens

---

## ♻️ Sustainability Features (S2)

### Code Quality Standards

**PSR-12 Compliance:**
```php
<?php

declare(strict_types=1); // Mandatory on all PHP files

namespace Modules\Admin\Livewire;

use Livewire\Component;
// ...
```

**Localization (No Hardcoded Strings):**
```blade
{{-- ✅ GOOD --}}
<x-ui::stat 
    :title="__('admin::ui.dashboard.stats.total_interns')"
    :value="$totalInterns"
/>

{{-- ❌ BAD (now fixed) --}}
<x-ui::stat :title="'Total Kunjungan'" /> 
```

**Test Coverage:**
| Module | Test Files | Coverage |
|--------|-----------|----------|
| Profile | 5 files (Feature, Unit, Security) | ~90%+ |
| Admin Dashboard | 2 files (Feature, Unit) | ~85%+ |
| Analytics | 1 file (AnalyticsAggregatorTest) | ~80%+ |
| ActivityWidget | 0 files (missing) | ⚠️ Needs coverage |

### Performance Optimization

**AccountLifecycleDashboard (Fixed in v1.2.0):**
```php
// ✅ OPTIMIZED: Single query with groupBy
$statusCounts = User::select('account_status', DB::raw('count(*) as count'))
    ->groupBy('account_status')
    ->pluck('count', 'account_status')
    ->toArray();

// ❌ BEFORE: 8 separate queries
$this->statusStats = [
    'provisioned' => User::where('account_status', 'pending')->count(),
    'activated' => User::where('account_status', 'activated')->count(),
    // ... 6 more queries
];
```

**Cache Strategy (AnalyticsAggregator):**
| Method | Cache Key | TTL | Rationale |
|--------|-----------|-----|----------|
| `getInstitutionalSummary()` | `institutional_summary_{year}` | 15 min | Expensive aggregation |
| `getSecuritySummary()` | `security_summary` | 5 min | Security data freshness |
| `getUserDistribution()` | `user_distribution` | 10 min | User stats stability |

---

## 📈 Scalability Features (S3)

### Modular Architecture

**Slot System for Widget Injection:**
```php
// modules/Admin/src/Providers/AdminServiceProvider.php
protected function viewSlots(): array
{
    return [
        'admin.dashboard.side' => [
            'livewire:admin::widgets.app-info-widget' => [
                'order' => 999,
            ],
        ],
    ];
}
```

**Widget Registration:**
```blade
{{-- Render widgets in view --}}
<x-ui::slot-render name="admin.dashboard.side" />
```

### Service Contracts for Loose Coupling

**ProfileService Contract:**
```php
// modules/Profile/src/Services/Contracts/ProfileService.php
interface ProfileService
{
    public function findById(string $id): ?Profile;
    public function create(array $data): Profile;
    public function update(Profile $profile, array $data): void;
    public function delete(Profile $profile): void;
    public function getByUserId(string $userId): ?Profile;
}
```

**Benefits:**
- ✅ Easy to mock in tests
- ✅ Swap implementations without breaking consumers
- ✅ Clear interface documentation

### UUID Architecture (Project Standard)

All entities use UUID v4 primary keys:
- ✅ Prevents enumeration attacks
- ✅ Decoupled from other modules (no cross-module foreign keys)
- ✅ Scalable across distributed systems

---

## 📊 User Dashboards

### Admin Dashboard

**Access:** `route('admin.dashboard')` (SuperAdmin/Admin only)

**Features:**
1. **Summary Statistics Cards** (via `AnalyticsAggregator`)
   - Total Interns
   - Active Partners
   - Placement Rate
   - Active Sessions (SuperAdmin only)

2. **Recent Assessments Table**
   - Student name, company, final grade
   - Actions: View Certificate, Transcript

3. **At-Risk Students Monitoring**
   - Identifies students with low engagement/scores
   - Real-time calculation (no cache)

4. **Recent Activity Feed** (last 8 entries)
   - From `spatie/laravel-activitylog`
   - Causer avatar, name, description, time

5. **System Status Card** (SuperAdmin only)
   - Failed Logins (7 days)
   - Throttled Attempts
   - Queue Failed Jobs
   - Last Backup timestamp
   - Database Size

6. **User Distribution Stats** (SuperAdmin only)
   - Counts per role (student, teacher, mentor, admin)
   - Active sessions count

**Livewire Component:**
```php
// modules/Admin/src/Livewire/Dashboard.php
class Dashboard extends Component
{
    use WithPagination;

    public function render(): View
    {
        return view('admin::livewire.dashboard', [
            'summary' => app(AnalyticsAggregator::class)->getInstitutionalSummary(),
            'atRisk' => $this->getAtRiskStudents(),
            'activities' => $this->getRecentActivities(),
        ]);
    }
}
```

### Student Dashboard

**Access:** `route('student.dashboard')` (Students only)

**Features:**
- Welcome subtitle with user name
- Requirements completion alert (if incomplete)
- My Program card (company name, internship name)
- Score Card (final grade if assessed)
- Download Certificate/Transcript buttons
- Slot-rendered content:
  - `student.dashboard.requirements`
  - `student.dashboard.active-content`
  - `student.dashboard.sidebar`
  - `student.dashboard.quick-actions`

### Teacher Dashboard

**Access:** `route('teacher.dashboard')` (Teachers only)

**Features:**
- Total Students stat card
- Students Table with:
  - Student Name
  - Placement Company
  - Status (badge with color)
  - Readiness Status (Ready/Not Ready with tooltip)
  - Actions: Supervise, Assess, Transcript (if ready)

**Readiness Check:**
```php
// modules/Teacher/src/Livewire/Dashboard.php
public function getReadiness(string $id): array
{
    return app(AssessmentService::class)->getReadinessStatus($id);
}
```

### Mentor Dashboard

**Access:** `route('mentor.dashboard')` (Mentors only)

**Features:**
- Total Interns stat card
- Interns Table with:
  - Student Name
  - Internship Program
  - Status (badge with color)
  - Actions: Mentoring, Evaluate

---

## 👤 Profile Management

### Access & Routing

**Route:**
```php
// modules/Profile/routes/web.php
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/profile', Index::class)->name('profile.index');
});
```

### Livewire Component

**File:** `modules/Profile/src/Livewire/Index.php`

**Properties Managed:**
1. **User Data**: `name`, `email`, `username`, `avatar`
2. **Basic Profile**: `phone`, `address`, `bio`, `gender`, `blood_type`, 
   `emergency_contact_name`, `emergency_contact_phone`, `emergency_contact_address`
3. **Role-Specific**:
   - Teacher: `nip` (Employee ID)
   - Student: `national_identifier`, `registration_number`, `class_name`, `passport_photo`
4. **Security**: `current_password`, `password`, `password_confirmation`

### Methods

**`mount()`**: Initializes form with current user/profile data

**`saveInfo()`**: Validates and saves basic info + profile data
```php
public function saveInfo(): void
{
    $this->validate();
    
    // Update user
    auth()->user()->update([
        'name' => $this->name,
        'email' => $this->email,
    ]);
    
    // Update profile
    $this->profileService->upsertManagedProfile(auth()->id(), [
        'phone' => $this->phone,
        'address' => $this->address,
        // ...
    ]);
}
```

**`saveSpecialFields()`**: Saves role-specific fields
- Validates NIP for teachers (unique)
- Validates National ID for students (unique)

**`savePassword()`**: Updates password with current password verification
```php
public function savePassword(): void
{
    $this->validate([
        'current_password' => 'required|current_password',
        'password' => 'required|string|min:8|confirmed',
    ]);
    
    auth()->user()->update([
        'password' => bcrypt($this->password),
    ]);
}
```

**`updatedAvatar()`**: Handles avatar upload
- Validates image type (jpg, png, gif)
- Validates file size (max 1MB)
- Stores in `storage/app/public/avatars/`

### View Structure

**Tabs:**
1. **Basic Info** (`profile::ui.tabs.basic_info`)
   - Full Name, Username (readonly), Email
   - Phone, Gender, Blood Type, Address, Bio
   - Emergency Contact (students only)

2. **Special Fields** (`profile::ui.tabs.special_fields`)
   - NIP (Teachers)
   - National Identifier, Registration Number, Class Name, Passport Photo (Students)

3. **Security** (`profile::ui.tabs.security`)
   - Current Password (required for changes)
   - New Password + Confirmation
   - Link to password reset (if forgot)

### Localization

**Files:**
- `modules/Profile/lang/en/ui.php` (English)
- `modules/Profile/lang/id/ui.php` (Indonesian)

**Coverage:** ✅ 100% - All user-facing strings use `__('profile::ui.*')`

---

## 📈 Managerial Stats & Widgets

### AnalyticsAggregator (Central Stats Orchestrator)

**File:** `modules/Admin/src/Analytics/Services/AnalyticsAggregator.php`

**Methods:**

| Method | Returns | Cache TTL | Description |
|--------|---------|-----------|-------------|
| `getInstitutionalSummary()` | `total_interns`, `active_partners`, `placement_rate` | 15 min | Main dashboard stats |
| `getAtRiskStudents()` | Array of students with risk levels | None (real-time) | At-risk monitoring |
| `getSecuritySummary()` | `failed_logins`, `throttled_attempts` | 5 min | Security monitoring |
| `getRecentActivities()` | Last N activities with causer | None (real-time) | Activity feed |
| `getInfrastructureStatus()` | `queue_pending`, `queue_failed`, `db_size` | None (real-time) | System health |
| `getUserDistribution()` | `by_role`, `active_sessions` | 10 min | User stats |

**Usage:**
```php
$aggregator = app(AnalyticsAggregator::class);
$summary = $aggregator->getInstitutionalSummary();
// Returns: ['total_interns' => 150, 'active_partners' => 25, ...]
```

### Widget Components

#### AppInfoWidget

**File:** `modules/Admin/src/Livewire/Widgets/AppInfoWidget.php`

**Purpose:** Displays application metadata (version, license, author credits)

**Data Source:** `app_info.json` (base path)

**View:** `modules/Admin/resources/views/livewire/widgets/app-info-widget.blade.php`

**Localization:** ✅ Fixed in v1.2.0 - Uses `__('admin::ui.dashboard.widget.*')`

#### ActivityWidget

**File:** `modules/Log/src/Livewire/ActivityWidget.php`

**Purpose:** Simplified activity feed for dashboard embedding (last 5 activities)

**Data Source:** `ActivityService::query()` with `with(['causer'])`

**Features:**
- Causer avatar, name, description
- Time diff (e.g., "2 hours ago")
- Links to relevant resources

**Localization:** ✅ Uses `__('log::ui.activity_feed')`

### UI Stat Component

**File:** `modules/UI/resources/views/components/stat.blade.php`

**Props:**
- `title` - Stat label (localized)
- `value` - Stat value (number or string)
- `icon` - Icon name (Tabler icons)
- `description` - Optional description
- `variant` - `primary`, `secondary`, `accent`, `info`, `success`, `warning`, `error`

**Usage:**
```blade
<x-ui::stat
    :title="__('admin::ui.dashboard.stats.total_interns')"
    :value="$totalInterns"
    icon="tabler.users"
    variant="primary"
/>
```

### Slot System for Widget Injection

**SlotManager:** `modules/UI/src/Core/SlotManager.php`
**SlotRegistry:** `modules/UI/src/Core/SlotRegistry.php`

**Registration Example:**
```php
// modules/Log/src/Providers/LogServiceProvider.php
protected function viewSlots(): array
{
    return [
        'admin.dashboard.side' => [
            'livewire:log::widgets.activity-widget' => [
                'order' => 10,
            ],
        ],
    ];
}
```

**Rendering in View:**
```blade
{{-- In admin::livewire.dashboard.blade.php --}}
<x-ui::slot-render name="admin.dashboard.side" />
```

---

## 🛠️ Technical Implementation

### Dashboard Service Layer

**AnalyticsAggregator Caching:**
```php
// modules/Admin/src/Analytics/Services/AnalyticsAggregator.php
public function getInstitutionalSummary(): array
{
    return Cache::remember(
        key: "institutional_summary_{$this->academicYear}",
        ttl: now()->addMinutes(15),
        callback: function () {
            return [
                'total_interns' => $this->calculateTotalInterns(),
                'active_partners' => $this->calculateActivePartners(),
                'placement_rate' => $this->calculatePlacementRate(),
            ];
        }
    );
}
```

### Profile Service Layer

**ProfileService Methods:**
```php
// modules/Profile/src/Services/ProfileService.php
class ProfileService extends EloquentQuery implements Contract
{
    public function getByUserId(string $userId): ?Profile
    {
        return $this->model->newQuery()->firstOrCreate(['user_id' => $userId]);
    }
    
    public function upsertManagedProfile(string $userId, array $data): Profile
    {
        $profile = $this->getByUserId($userId);
        $profile->fill($data);
        $profile->save();
        return $profile;
    }
}
```

### Widget Data Flow

```
User Request
    ↓
Livewire Component (Dashboard.php)
    ↓
AnalyticsAggregator / Service
    ↓
Cache Check (Redis/File)
    ↓ (if miss)
Database Query (Eloquent)
    ↓
Return + Cache Store
    ↓
Blade View (with <x-ui::stat /> components)
    ↓
JSON Response (Livewire)
    ↓
DOM Update (Alpine.js)
```

---

## 🧪 Testing & Quality

### Test Coverage Status

| Feature | Test Files | Status | Coverage |
|---------|-----------|--------|----------|
| **Admin Dashboard** | `modules/Admin/tests/Feature/Livewire/DashboardTest.php`<br>`modules/Admin/tests/Unit/Analytics/Services/AnalyticsAggregatorTest.php` | ✅ Good | ~85%+ |
| **Student Dashboard** | `modules/Student/tests/Feature/Livewire/DashboardTest.php` | ✅ Basic | ~70%+ |
| **Teacher Dashboard** | `modules/Teacher/tests/Feature/Livewire/DashboardTest.php` | ✅ Basic | ~70%+ |
| **Profile Index** | `modules/Profile/tests/Feature/Livewire/ProfileIndexTest.php`<br>`modules/Profile/tests/Unit/Services/ProfileServiceTest.php` | ✅ Good | ~90%+ |
| **Profile Security** | `modules/Profile/tests/Feature/Security/PiiEncryptionTest.php` | ✅ Good | ~95%+ |
| **ActivityWidget** | None found | ❌ Missing | 0% |
| **AppInfoWidget** | None found | ❌ Missing | 0% |

### Test Patterns (Pest PHP)

**Feature Test Example:**
```php
// modules/Admin/tests/Feature/Livewire/DashboardTest.php
it('can display institutional summary', function () {
    loginAsSuperAdmin();
    
    $this->get(route('admin.dashboard'))
        ->assertSee('Total Interns')
        ->assertSee('Active Partners');
});

it('hides system status from non-super-admins', function () {
    loginAsAdmin(); // Not super_admin
    
    $this->get(route('admin.dashboard'))
        ->assertDontSee('System Status');
});
```

**Unit Test Example:**
```php
// modules/Admin/tests/Unit/Analytics/Services/AnalyticsAggregatorTest.php
it('calculates placement rate correctly', function () {
    // Create 10 registrations, 8 with placements
    Registration::factory()->count(10)->create();
    Placement::factory()->count(8)->create();
    
    $aggregator = app(AnalyticsAggregator::class);
    $summary = $aggregator->getInstitutionalSummary();
    
    expect($summary['placement_rate'])->toBe(80.0);
});
```

### Code Quality Tools

**Pint (PSR-12):**
```bash
./vendor/bin/pint modules/Admin modules/Profile modules/UI
```

**PHPStan (Static Analysis):**
```bash
./vendor/bin/phpstan analyse modules/Admin src/Livewire/Dashboard.php
```

**Test Suite:**
```bash
./vendor/bin/pest modules/Admin/tests modules/Profile/tests
```

---

## 🔧 Troubleshooting

### Dashboard Stats Not Updating

**Problem:** Stats show old data.

**Cause:** Cache not invalidated after data changes.

**Solution:**
```bash
# Clear all cache
php artisan cache:clear

# Or clear specific key
php artisan cache:forget institutional_summary_2025-2026
```

**Better Solution:** Implement cache invalidation in Service classes:
```php
// After creating a new registration
Cache::forget("institutional_summary_{$academicYear}");
```

### Profile Avatar Upload Fails

**Problem:** Avatar upload returns validation error.

**Checks:**
1. File type: Must be `jpg`, `png`, or `gif`
2. File size: Maximum 1MB (1024 KB)
3. Directory permissions: `storage/app/public/avatars/` must be writable

**Fix permissions:**
```bash
chmod -R 755 storage/app/public/avatars/
php artisan storage:link
```

### Widgets Not Displaying

**Problem:** Widgets not showing in dashboard slots.

**Checks:**
1. Module is enabled in `modules_statuses.json`
2. ServiceProvider registers `viewSlots()` method
3. Correct slot name used in view

**Debug:**
```blade
{{-- Add to dashboard.blade.php --}}
@foreach(\Modules\UI\Core\SlotManager::get('admin.dashboard.side') as $widget)
    <p>Widget: {{ $widget['component'] }}</p>
@endforeach
```

### Activity Feed Empty

**Problem:** Recent Activity shows no entries.

**Cause:** `spatie/laravel-activitylog` not logging activities.

**Fix:** Ensure models use `LogsActivity` trait:
```php
// modules/User/src/Models/User.php
use Spatie\Activitylog\Traits\LogsActivity;

class User extends Model
{
    use LogsActivity;
    
    protected static $logAttributes = ['name', 'email', 'role'];
}
```

---

## 📚 Additional Resources

- **Installation Guide**: [docs/installation.md](installation.md)
- **Module Catalog**: [docs/modules-catalog.md](modules-catalog.md)
- **Testing Infrastructure**: [docs/testing-infrastructure.md](testing-infrastructure.md)
- **Architecture**: [docs/architecture.md](architecture.md)

---

**Document Version:** 1.2.0  
**Last Updated:** April 29, 2026  
**Compliance:** S1 (Secure), S2 (Sustain), S3 (Scalable)
