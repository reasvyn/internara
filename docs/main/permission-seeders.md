# Permission Seeders: Bootstrapping Access

This guide explains how Internara manages the initial population of roles and permissions via
modular seeders. This process is critical for setting up new environments and ensuring that system
access is consistent across all installations.

---

## 1. The Seeding Architecture

Permissions are not defined in a single monolithic file. Instead, they are distributed across the
modules that "own" the functionality.

- **Core Module**: Seeds the foundational roles (Super Admin, Teacher, Student).
- **Domain Modules**: Seed permissions specific to their functionality (e.g., `attendance.view`).

---

## 2. Implementing a Module Seeder

Every module that introduces new permissions should have a seeder class in its `database/seeders`
directory.

### 2.1 Standard Pattern

Use the `PermissionService` to ensure that permissions are created idempotently.

```php
namespace Modules\Attendance\Database\Seeders;

use Illuminate\Database\Seeder;
use Modules\Permission\Services\Contracts\PermissionServiceInterface;

class AttendancePermissionSeeder extends Seeder
{
    public function run(PermissionServiceInterface $service): void
    {
        $permissions = ['attendance.view', 'attendance.check-in', 'attendance.report'];

        foreach ($permissions as $name) {
            $service->firstOrCreate(['name' => $name, 'guard_name' => 'web']);
        }
    }
}
```

---

## 3. Synchronizing Access

After adding new permissions to a seeder, run the synchronization command to update the database and
refresh the cache.

```bash
php artisan permission:sync
```

---

_Seeders are the foundation of our RBAC system. Always ensure that your seeder logic is
idempotent—running it multiple times should not create duplicate records or cause errors._
