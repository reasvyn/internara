# Routing & Menus

## Route Naming

All routes use dot-separated hierarchical names via `->name()`:
```php
Route::livewire('/users', UserManager::class)->name('admin.users');
Route::get('/users/{user}', [UserController::class, 'show'])->name('admin.users.show');
```

Format: `{prefix}.{resource}.{action}`

## Route Organization

Routes are organized by domain in `routes/web.php`:

```php
// Public (guest)
Route::middleware('guest')->group(function () { ... });

// Authenticated
Route::middleware('auth')->group(function () { ... });

// Admin
Route::prefix('admin')->name('admin.')->middleware(['auth', 'role:super_admin|admin'])->group(function () { ... });

// Student
Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () { ... });

// Teacher
Route::prefix('teacher')->name('teacher.')->middleware(['auth', 'role:teacher'])->group(function () { ... });

// Supervisor
Route::prefix('supervisor')->name('supervisor.')->middleware(['auth', 'role:supervisor'])->group(function () { ... });

// Supervision (teacher + supervisor)
Route::prefix('supervision')->name('supervision.')->middleware(['auth', 'role:teacher|supervisor'])->group(function () { ... });
```

## Middleware

| Middleware | Purpose |
|---|---|
| `auth` | Authenticated sessions |
| `guest` | Non-authenticated |
| `setup.protected` | Setup wizard flow |
| `role:{role1\|role2}` | Role-based gating (pipe-delimited OR) |

## Sidebar Menu

Menu items are defined in `config/menu.php`. Groups ordered by internship lifecycle:

```php
'foundation' => [/* School, Academic Years, Departments */],
'internship' => [/* Programs, Companies, Placements */],
'registration' => [/* Applications, Registrations */],
'people' => [/* Users by role */],
'assessment' => [/* Rubrics, Submissions */],
'operations' => [/* Attendance, Assignments, Logbook */],
// Role-specific portals...
'reports' => [/* Reports, Lifecycle, GDPR */],
'system' => [/* Settings, Handbooks, Schedules */],  // Config at bottom
```

Each menu item: `['route' => 'route.name', 'icon' => 'o-icon-name', 'label' => 'translation.key']`
