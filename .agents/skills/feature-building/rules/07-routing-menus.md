# Routing & Menu Registration

## What It Enforces

Routes are organized by module in `routes/web/{domain}.php` files, required from `routes/web.php`. Routes use dot-separated hierarchical names via `->name()`. Menu items are defined in `config/menu.php` grouped by internship lifecycle phase.

## Why It Matters

Domain-split route files keep routing concerns close to their module. Dot-separated names provide a predictable, hierarchical naming scheme. Role-based route groups enforce authorization at the routing layer, reducing per-method checks.

## When It Applies

Every new feature with a UI route must:
- Define the route in the appropriate `routes/web/{domain}.php` file
- Use dot-separated name: `{prefix}.{resource}.{action}`
- Place the route in the correct role group (guest, auth, admin, student, mentor)
- Register a menu item in `config/menu.php` with route name, icon, and translation key

Route organization by role:
- Guest routes: no middleware beyond `web`
- Authenticated routes: `auth` middleware
- Admin routes: `prefix('admin')` + `role:super_admin|admin` middleware
- Student routes: `prefix('student')` + `role:student` middleware
- Mentor routes: `prefix('supervision')` + `role:teacher|supervisor` middleware

Livewire components use `Route::livewire()` for direct component binding without a Controller.

Exceptions: API routes (if any) follow their own conventions with API versioning.
