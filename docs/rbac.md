# Role-Based Access Control

## Role Hierarchy Design

The application defines five user roles in a flat hierarchy: super_admin,
admin, teacher, supervisor, and student. These are not arranged in a tree
where each role inherits the permissions of the roles below it. Instead,
each role has its own explicit set of permissions, and the super_admin role
bypasses all permission checks entirely.

Super admins manage the application infrastructure — they configure settings,
manage all user accounts, and have unrestricted access. There should be very
few super admin accounts.

Admins handle school-level operations: manage users, internships, companies,
departments, and the operational aspects of the internship program. They have
broad but not unrestricted access.

Teachers provide academic supervision: they can view and manage student
assignments and assessments, verify journals, and mentor students during
their internship.

Supervisors provide industry-side supervision: they register attendance,
verify journals from the company perspective, and evaluate student
performance at the placement site.

Students are the participants: they submit assignments, write journals,
clock attendance, and view their own records.

A second family of functional roles (Admin, Mentor, Mentee) exists for
business logic only. These are logical groupings resolved at runtime — they
are never stored in the database and never used in route middleware. A Mentor
resolves to teacher + supervisor; a Mentee resolves to student. This
separation keeps the route security layer simple (concrete roles only) while
allowing Actions to write role-agnostic business logic.

## Why Flat Roles Instead of Hierarchical Permissions

A hierarchical permission system (where each role inherits from a parent) is
tempting but leads to unexpected behavior. When a permission is added to a
higher role, all subordinate roles implicitly gain it — which may or may not
be desired. When a permission is removed from a higher role, the effect on
child roles is unclear. Explicit flat role definitions eliminate this
ambiguity. Each role's capabilities are enumerated and reviewed.

## How Gate::before Bypass Works

Laravel's authorization system evaluates policies for each ability check.
The `Gate::before` callback intercepts every authorization check before the
policy is consulted. For super_admin users, this callback returns `true`,
granting access to everything. For all other users, it returns `null`,
which means "I have no opinion — let the policy decide." This is distinct
from returning `false`, which would deny access even if the policy would
grant it.

This pattern means super_admin is not a role that has "all permissions"
assigned to it in the database. It simply skips the permission system
entirely. This is more efficient and guarantees that super_admin never
accidentally lacks a permission.

## What CheckRoleMiddleware Does

Route-level role verification is handled by `CheckRoleMiddleware`. This
middleware intercepts requests after authentication and checks whether the
authenticated user has at least one of the required roles. It accepts
pipe-delimited role names (e.g., `super_admin|admin`). If the user lacks any
of the required roles, the middleware returns a 403 response for
authenticated users or redirects to login for unauthenticated requests.

The middleware logs unauthorized access attempts for security monitoring.
It is applied to route groups in `routes/web.php` — each domain's routes are
gated by the roles that should have access.

## Where to Find It

Roles and permissions are defined in
`app/Domain/Auth/Enums/Role.php` and
`app/Domain/Auth/Enums/Permission.php`. The seeder is at
`database/seeders/RolePermissionSeeder.php`. The middleware is at
`app/Domain/Auth/Http/Middleware/CheckRoleMiddleware.php`. The
`Gate::before` registration is in `app/Providers/AppServiceProvider.php`.
Policies are in `app/Domain/*/Policies/`. The spatie package configuration
is in `config/permission.php`.
