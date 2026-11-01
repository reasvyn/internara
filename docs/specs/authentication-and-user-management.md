# YB7RG-95EVB — Authentication, Identity & Role-Based Access Control (RBAC)

**Document Version:** 1.0.0  
**Status:** Approved Specification  
**Modules:** `Auth`, `User`, `Core`  
**Phase:** `Phase 3 — Identity & Access Governance`  
**Governing Specs:** #465 (`[YB7RG]` Authentication), #470 (`[CQVSK]` Password Confirmation), #469 (`[D9TKW]` Password Reset), #471 (`[SHQ1J]` Account Recovery Slips), #482 (`[95EVB]` User CRUD & Status), #484 (`[EWCZ0]` Account Slips), #472 (`[OCEMS]` Profile Management), #452 (`[T4B26]` RBAC & Authorization)  

---

## Description

This specification governs the foundational authentication, user identity lifecycle, and access governance system of the Internara platform. It defines multi-role credential verification, brute-force rate-limiting defenses, self-service and offline account activation workflows, sudo-mode password confirmation, user lifecycle states (`Pending`, `Active`, `Suspended`, `Archived`), and a strict three-layer Flat Role-Based Access Control (RBAC) architecture with derived functional roles and cross-role proxy capabilities.

---

## 1. Problem Statements

- **PS-1 — Authorization Leakage in Hierarchical RBAC**: Traditional hierarchical role models (e.g., `SUPERADMIN > ADMIN > TEACHER > STUDENT`) lead to unintended privilege inheritance. Giving a teacher the capability to manage classroom attendance can inadvertently permit an admin to bypass pedagogical checks, or worse, expose sensitive industrial partner contracts to instructional staff. Roles must be decoupled into flat concrete identities with explicit functional groupings.
- **PS-2 — Onboarding Friction for Low-Tech Students**: Vocational high school students frequently lack active personal email addresses upon initial school enrollment. Standard email-based verification links stall student onboarding. The system must support physical, offline account activation slips with QR codes and temporary credential seeds.
- **PS-3 — Privileged Route Tampering & Session Hijacking**: Sensitive administrative actions (database exports, user purges, school profile alterations) risk unauthorized execution if an administrator leaves their physical workstation unlocked. High-risk actions require sudo-mode re-authentication with timed grace periods.
- **PS-4 — External Partner Mentorship Disengagement**: External company supervisors often manage multiple apprentices alongside demanding operational responsibilities. If an industrial mentor disengages or fails to verify attendance/journals, students risk academic failure. The platform must provide authorized teachers with an audited cross-role proxy capability to act on behalf of company supervisors when justified.

---

## 2. Goals & Non-Goals

### Goals

- **Flat RBAC with Derived Functional Roles** — Enforce 5 flat concrete roles (`super_admin`, `admin`, `teacher`, `supervisor`, `student`) mapped at runtime to 3 functional roles (`admin-group`, `mentor`, `mentee`). *Why:* Prevents permission inheritance leakage while providing reusable policy checks.
- **Three-Layer Defense in Depth** — Guard access across HTTP Route Middleware (`CheckRoleMiddleware`), Livewire Component authorization (`$this->authorize()`), and Eloquent Policies (`BasePolicy` with `AuthorizesRoles` and `AuthorizesOwnership`). *Why:* Guarantees that bypassing one layer still triggers rejection at subsequent layers.
- **Dual Onboarding Workflows** — Support self-service email activation alongside printable physical Account Activation Slips featuring QR codes and temporary credentials. *Why:* Accommodates students in rural or low-connectivity environments without initial email access.
- **Sudo-Mode Password Confirmation** — Intercept destructive and sensitive state mutations, requiring password re-confirmation with a 15-minute sliding session grace period. *Why:* Protects institutional integrity against unattended terminal hijacking.
- **Audited Cross-Role Proxy** — Enable authorized school teachers to sign off on industry assessments or attendance logs when an external supervisor is unreachable, logging the proxy action in the immutable audit trail. *Why:* Ensures student academic progression is never blocked by external operational delays.

### Non-Goals

- **Social OAuth Authentication (Google, Facebook, GitHub)**. *Why:* Violates data sovereignty and creates external corporate tracking dependencies.
- **Multi-Tenant Account Switching**. *Why:* The platform is strictly single-tenant per institution; users belong to exactly one school installation.
- **Granular Custom Per-User Permission Checkboxes**. *Why:* Permitting arbitrary permission combinations per user introduces combinatorial testing explosion and configuration drift. All permissions derive cleanly from the user's assigned role.

---

## 3. User Stories / Use Cases

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| UC-AUTH-001 | User authenticates using valid email or username and password, reaching their role dashboard | P0 | F | Full |
| UC-AUTH-002 | Unauthenticated or throttled user is locked out after 5 consecutive failed login attempts | P0 | F | Full |
| UC-AUTH-003 | User resets forgotten password via signed email token expiring within 60 minutes | P0 | F | Full |
| UC-AUTH-004 | Administrator executes sensitive mutation and completes sudo-mode password confirmation | P0 | F | Full |
| UC-AUTH-005 | Student activates newly provisioned account using physical activation slip credentials | P0 | F | Full |
| UC-USR-001 | Administrator creates, updates, and transitions user account states across lifecycle | P0 | F | Full |
| UC-USR-002 | User updates personal profile information, while official identifiers remain read-only | P1 | F | Full |
| UC-RBAC-001 | System blocks unauthorized role from accessing protected route and returns HTTP 403 | P0 | A | Full |
| UC-RBAC-002 | Teacher exercises cross-role proxy to verify student journal with explicit audit reason | P1 | F | Full |

### 3.1 Authentication & Password Workflows

#### UC-AUTH-001 — Multi-Role Login Flow
- **Preconditions**: User exists with `Active` account status.
- **Flow**:
  1. User navigates to `/login`.
  2. Submits email or username and plaintext password via `LoginForm`.
  3. `LoginAction` verifies credentials against `users` table via `Hash::check()`.
  4. Session is regenerated (`session()->regenerate()`) to prevent fixation attacks.
  5. Dispatches `LoginSucceeded` event and redirects user to role-specific dashboard.
- **Postconditions**: Authenticated session active; last login timestamp updated.

#### UC-AUTH-002 — Rate-Limited Brute Force Defense
- **Preconditions**: Attacker or user submits incorrect password repeatedly.
- **Flow**:
  1. `AuthThrottleMiddleware` inspects IP and username signature key.
  2. Increments failed attempt count in cache.
  3. Upon reaching 5 failed attempts within 60 seconds, rejects subsequent requests with HTTP 429 Too Many Requests and a countdown timer.
  4. Dispatches `LoginFailed` event and logs security alert via `SmartLogger`.
- **Postconditions**: IP locked out for remainder of 60-second window.

#### UC-AUTH-004 — Sudo-Mode Password Confirmation
- **Preconditions**: Administrator is authenticated and attempts to access backup manager or purge user.
- **Flow**:
  1. Controller or Livewire action invokes `EnsurePasswordIsConfirmed` middleware.
  2. If session `auth.password_confirmed_at` is older than 900 seconds (15 minutes), redirects to `/confirm-password`.
  3. Administrator re-enters password via `ConfirmPasswordForm`.
  4. System validates password, updates session timestamp, and returns user to intended destination.
- **Postconditions**: Sudo grace period active for 15 minutes.

### 3.2 User Management & RBAC Governance

#### UC-AUTH-005 — Physical Account Activation Slip Redemption
- **Preconditions**: Administrator printed batch activation slips containing temporary password seeds.
- **Flow**:
  1. Student navigates to `/activate-account` or scans QR code on physical slip.
  2. Inputs temporary username/code printed on slip.
  3. System prompts student to define their permanent secure password.
  4. Account status transitions from `Pending` to `Active`; temporary activation token revoked.
- **Postconditions**: Account activated; student proceeds to mobile dashboard.

#### UC-RBAC-002 — Teacher Cross-Role Proxy Execution
- **Preconditions**: Industrial supervisor is unreachable; student has pending attendance/logbook records.
- **Flow**:
  1. Assigned School Supervising Teacher opens student record.
  2. Clicks "Verify via Teacher Proxy" action.
  3. Inputs justification notes explaining external supervisor absence.
  4. Policy `HasMentorProxy` allows mutation.
  5. System marks record as verified, saving `verified_by = teacher_id` and `is_proxy = true`, logging audit event.
- **Postconditions**: Student progression unblocked; audit record immutable.

---

## 4. Functional Requirements

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| FR-AUTH-001 | Multi-identifier login supporting either RFC email or alphanumeric username | P0 | F | Full |
| FR-AUTH-002 | Progressive authentication rate limiting enforcing 5-attempt ceiling per IP/account window | P0 | F | Full |
| FR-AUTH-003 | Self-service password reset generating signed 60-minute single-use email tokens | P0 | F | Full |
| FR-AUTH-004 | Session invalidation revoking all active sessions across devices upon password mutation | P0 | F | Full |
| FR-AUTH-005 | Sudo-mode password confirmation gate protecting sensitive administrative operations | P0 | F | Full |
| FR-AUTH-006 | Physical account activation slip generator with temporary credentials and QR codes | P0 | F | Full |
| FR-USR-001 | User CRUD actions partitioned by role (Admin, Teacher, Supervisor, Student) | P0 | F | Full |
| FR-USR-002 | Lifecycle account status transitions (`Pending`, `Active`, `Suspended`, `Archived`) | P0 | U | Full |
| FR-USR-003 | Immediate session termination upon account transition to `Suspended` status | P0 | F | Full |
| FR-USR-004 | Student profile protection locking NISN, Department, and Cohort against non-admin edits | P0 | U | Full |
| FR-USR-005 | Role-tailored dashboard aggregation delivering personalized metrics per user type | P0 | F | Full |
| FR-RBAC-001 | Flat role enumeration defining 5 mutually exclusive concrete roles | P0 | U | Full |
| FR-RBAC-002 | Dynamic functional role derivation resolving `admin-group`, `mentor`, and `mentee` | P0 | U | Full |
| FR-RBAC-003 | Super Admin gate bypass evaluating `Gate::before()` unconditionally true | P0 | A | Full |
| FR-RBAC-004 | Three-layer defense enforcement spanning route middleware, Livewire, and policies | P0 | A | Full |
| FR-RBAC-005 | Cross-role proxy authorization permitting teachers to act on behalf of absent industry mentors | P1 | F | Full |

### 4.1 Authentication & Session Security

#### FR-AUTH-001 — Flexible Identifier Authentication (`LoginAction`)
- Accepts `LoginData` carrying `login` (email or username), `password`, and `remember` flag.
- Normalizes identifier: if string contains `@`, queries `users.email`; otherwise queries `users.username`.
- Authenticates only records where `deleted_at IS NULL` and `status === AccountStatus::ACTIVE`.

#### FR-AUTH-002 — Rate Limiting Defense (`AuthThrottleMiddleware`)
- Computes rate-limit key: `Str::transliterate(Str::lower($login).'|'.$request->ip())`.
- Allows maximum 5 attempts per 60 seconds (`RateLimiter::tooManyAttempts($key, 5)`).
- Rejects with HTTP 429 and `Retry-After` header indicating remaining cooldown seconds.

#### FR-AUTH-004 — Global Session Invalidation
- Upon password update via `ResetPasswordAction` or `UpdateUserPasswordAction`:
  - Re-hashes password with Bcrypt (work factor 12).
  - Regenerates remember token.
  - Flushes other session entries via `Auth::logoutOtherDevices($password)`.
  - Dispatches `PasswordUpdated` event.

#### FR-AUTH-005 — Sudo Confirmation Gate
- Guarded routes enforce `auth.password_confirmed` middleware.
- When confirmed, sets session timestamp `auth.password_confirmed_at = time()`.
- Valid for 900 seconds (15 minutes). Once elapsed, access is intercepted until re-confirmed.

### 4.2 Account Lifecycle & User Management

#### FR-USR-002 — Lifecycle Account Statuses (`AccountStatus`)
- States supported:
  - `Pending`: Account created but awaiting student activation slip redemption.
  - `Active`: Normal authenticated access granted.
  - `Suspended`: Account locked by administrator due to disciplinary or security concerns.
  - `Archived`: Graduated student account preserved for regulatory 5-year audit compliance.
- Middleware `CheckAccountStatusMiddleware` verifies status on every authenticated request. If `Suspended`, terminates session immediately.

#### FR-USR-004 — Student Profile Immutability Invariant
- Students can update: contact phone, personal avatar, bio, and emergency contact details.
- Students *cannot* update: National Student Identification Number (NISN), assigned vocational department, enrollment cohort, or placement status. Any attempt to modify these fields via mass-assignment is rejected with `RejectedException`.

#### FR-USR-005 — Role-Tailored Dashboards
- `StudentDashboard`: Today's attendance status, recent logbook entries with mentor review state, upcoming assignments.
- `TeacherDashboard`: Pending student logbooks awaiting review, attendance anomalies, scheduled field monitoring visits.
- `SupervisorDashboard`: Placed apprentices, unverified attendance records, competency evaluation status.
- `AdminDashboard`: School-wide placement rate, company quota saturation, department breakdown, system health alerts.

### 4.3 Role-Based Access Control (RBAC) Architecture

#### FR-RBAC-001 & FR-RBAC-002 — Flat Roles & Derived Functional Roles
- **Concrete Roles** (stored in `users.role` enum column):
  - `super_admin`: Full system control and technical settings.
  - `admin`: Institutional operations (departments, programs, quotas).
  - `teacher`: Academic supervision, monitoring visits, school-side assessments.
  - `supervisor`: Industrial field supervision, daily attendance validation, rubric scoring.
  - `student`: Fieldwork participation, daily journals, assignment submissions.
- **Functional Roles** (runtime resolution via `Role::resolvesTo()`):
  - `admin-group`: Resolves from `super_admin` and `admin`.
  - `mentor`: Resolves from `teacher` and `supervisor`.
  - `mentee`: Resolves from `student`.
- Enables concise policy checks: `$user->isMentor()` instead of `$user->role === 'teacher' || $user->role === 'supervisor'`.

#### FR-RBAC-003 — Super Admin Unconditional Bypass
- Configured in `AuthServiceProvider`:
  ```php
  Gate::before(function (User $user, string $ability) {
      if ($user->isSuperAdmin()) {
          return true; // Bypass all gates immediately
      }
  });
  ```
- Super admin bypass does not require querying a database permissions table, guaranteeing zero database query overhead for root system operations.

#### FR-RBAC-004 — Three-Layer Authorization Pipeline
1. **Layer 1 — HTTP Route**: `CheckRoleMiddleware` inspects concrete role: `Route::middleware('role:admin,super_admin')`.
2. **Layer 2 — Livewire Component**: Component calls `$this->authorize('update', $user)`.
3. **Layer 3 — Eloquent Policy**: Policy executes fine-grained ownership and state invariants:
   ```php
   public function update(User $actor, User $target): bool
   {
       return $this->isAdmin($actor) || $actor->id === $target->id;
   }
   ```

#### FR-RBAC-005 — Cross-Role Mentor Proxy (`HasMentorProxy`)
- When an external company supervisor is unresponsive for 72+ hours:
  - School Supervising Teacher assigned to the student's group can approve logbooks or submit verified scores.
  - Policy requires: Actor is assigned teacher for student + valid reason supplied.
  - Stores `proxy_actor_id = teacher_id`, `proxy_reason = string`, and `is_proxy = true`.
  - Emits immutable `AuditTrail` event.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|---|---|---|---|---|---|
| NFR-SEC-001 | Password hashing security standard | Bcrypt with cost factor 12 | P0 | U | Full |
| NFR-SEC-002 | Brute-force throttling window | 5 attempts / 60 seconds per IP | P0 | F | Full |
| NFR-SEC-003 | Password reset token validity window | Exactly 60 minutes TTL | P0 | F | Full |
| NFR-SEC-004 | Sudo confirmation grace period | Sliding window of 15 minutes (900s) | P0 | F | Full |
| NFR-SEC-005 | PII masking in security log storage | 100% redaction of passwords & tokens | P0 | A | Full |
| NFR-PRF-001 | RBAC policy evaluation latency | P95 < 5ms per check | P1 | U | Full |
| NFR-PRF-002 | Authentication response time | P95 < 250ms on standard VPS | P1 | F | Full |

### 5.1 Security & Protection (ISO 25010: Security)
- **NFR-SEC-001 (Password Encryption)**: Passwords are encrypted using Bcrypt with a minimum cost factor of 12. Plaintext passwords are never logged, serialized, or returned in API responses.
- **NFR-SEC-005 (Audit PII Redaction)**: Log records generated by `SmartLogger` automatically strip `password`, `password_confirmation`, `recovery_key`, and personal identification numbers from query payloads before writing to disk.

### 5.2 Performance & Efficiency (ISO 25010: Performance Efficiency)
- **NFR-PRF-001 (Policy Speed)**: Flat RBAC evaluations use in-memory PHP Enum comparisons (`Role::is()`). No recursive database tree queries are executed during permission checks.

---

## 6. API / Data Contracts

### 6.1 Role Enum Contract (`Role.php`)

```php
namespace App\Modules\Auth\Domain\Permission\Enums;

enum Role: string implements LabelEnum
{
    case SUPER_ADMIN = 'super_admin';
    case ADMIN       = 'admin';
    case TEACHER     = 'teacher';
    case SUPERVISOR  = 'supervisor';
    case STUDENT     = 'student';

    public function resolvesTo(): array
    {
        return match ($this) {
            self::SUPER_ADMIN, self::ADMIN => ['admin-group'],
            self::TEACHER, self::SUPERVISOR => ['mentor'],
            self::STUDENT                   => ['mentee'],
        };
    }

    public function is(Role ...$roles): bool
    {
        return in_array($this, $roles, true);
    }
}
```

### 6.2 Account Status Enum (`AccountStatus.php`)

```php
namespace App\Modules\User\Enums;

enum AccountStatus: string implements StatusEnum
{
    case PENDING   = 'pending';
    case ACTIVE    = 'active';
    case SUSPENDED = 'suspended';
    case ARCHIVED  = 'archived';
}
```

### 6.3 Primary Data Transfer Objects (DTO)

```php
// 1. Login DTO
namespace App\Modules\Auth\Domain\Login\Data;

final readonly class LoginData
{
    public function __construct(
        public string $login,
        public string $password,
        public bool $remember = false,
    ) {}
}

// 2. User Creation DTO
namespace App\Modules\User\Domain\UserManagement\Data;

final readonly class CreateUserData
{
    public function __construct(
        public string $name,
        public string $email,
        public string $username,
        public string $password,
        public Role $role,
        public ?string $departmentId = null,
        public ?string $identifierNumber = null, // NISN or NIP
        public AccountStatus $status = AccountStatus::ACTIVE,
    ) {}
}
```

### 6.4 Key Actions & Signatures

```php
// Authentication Actions
namespace App\Modules\Auth\Domain\Login\Actions;
final class LoginAction extends BaseCommandAction
{
    public function execute(LoginData $data): bool;
}

namespace App\Modules\Auth\Domain\Password\Actions;
final class ConfirmPasswordAction extends BaseCommandAction
{
    public function execute(string $password): bool;
}

// User Lifecycle Actions
namespace App\Modules\User\Domain\UserManagement\Actions;
final class CreateUserAction extends BaseCommandAction
{
    public function execute(CreateUserData $data): User;
}

final class SetUserStatusAction extends BaseCommandAction
{
    public function execute(User $user, AccountStatus $newStatus, ?string $reason = null): User;
}

final class GenerateAccountSlipAction extends BaseCommandAction
{
    public function execute(User $user): AccountSlipData;
}
```

### 6.5 HTTP Middleware Mapping

- `role:{roles}`: `App\Modules\Auth\Domain\Permission\Http\Middleware\CheckRoleMiddleware`
- `auth.status`: `App\Modules\User\Http\Middleware\CheckAccountStatusMiddleware`
- `auth.sudo`: `App\Modules\Auth\Domain\Password\Http\Middleware\EnsurePasswordConfirmed`
- `throttle.auth`: `App\Modules\Auth\Domain\Login\Http\Middleware\AuthThrottleMiddleware`

---

## 7. Design Decisions

| ID | Requirement | Priority | Layer | Status |
|---|---|---|---|---|
| DD-RBAC-001 | Flat Concrete Roles with Derived Functional Roles | P0 | U | Full |
| DD-RBAC-002 | Gate::before Unconditional Super Admin Bypass | P0 | A | Full |
| DD-RBAC-003 | Physical Slip Onboarding for Zero-Email Student Populations | P0 | F | Full |
| DD-RBAC-004 | Sliding 15-Minute Sudo-Mode Session Protection | P0 | F | Full |
| DD-RBAC-005 | Audited Teacher Proxy Delegation for Unreachable Industry Mentors | P1 | F | Full |

### 7.1 Key Architecture Decisions

#### DD-RBAC-001 — Flat Roles with Derived Functional Roles
- *Decision*: Roles are completely flat with zero database inheritance. Behavioral groupings (`admin-group`, `mentor`, `mentee`) are computed in PHP via enum method `resolvesTo()`.
- *Rationale*: Eliminates silent privilege escalation bugs common in hierarchical models while simplifying policy checks.

#### DD-RBAC-002 — Super Admin Gate::before Bypass
- *Decision*: Super Admin privileges are enforced via `Gate::before()` returning `true` immediately.
- *Rationale*: Avoids inserting thousands of granular permission strings into the database and guarantees the system cannot be bricked by accidental permission deletions.

#### DD-RBAC-003 — Physical Slip Onboarding
- *Decision*: Allow schools to bulk-print paper slips with temporary credentials and QR codes for initial account activation.
- *Rationale*: Matches real Indonesian SMK field conditions where rural students may not have reliable email access before school orientation.

#### DD-RBAC-005 — Cross-Role Proxy Delegation
- *Decision*: Allow school teachers to approve student records on behalf of absent industry supervisors, requiring a mandatory reason note and recording an immutable proxy audit flag.
- *Rationale*: Real-world DUDI supervisors often fail to respond before grading deadlines. A rigid system would leave students unable to graduate.

---

## 8. Success Metrics

- **Zero Privilege Escalations**: 0 vulnerabilities where a lower role gains unauthorized access to higher-tier operational actions.
- **Onboarding Completion Rate**: > 98% of students activate their account within the first week of school orientation via either email or physical slips.
- **Brute Force Protection**: 100% of malicious password spraying attempts throttled after 5 failed tries.
- **Graduation Unblocking**: Median proxy resolution time for disengaged industry supervisors is under 24 hours.

---

## 9. Roadmap

- **Phase 3 (Current Baseline)**: Multi-role authentication, rate-limiting, flat RBAC, sudo confirmation, physical activation slips, and user lifecycle states.
- **Phase 4 (Next)**: Hardware FIDO2/WebAuthn biometric passkey support for administrators, and temporary delegation expiry timers for teacher proxy approvals.

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
|---|---|---|---|---|
| R-1 | If a teacher abuses the cross-role proxy to falsify student grades. Mitigated by immutable audit logging and coordinator sign-off oversight. | Resolved | Security Lead | [#452](https://github.com/reasvyn/internara/issues/452) |
| R-2 | If a printed account activation slip is stolen before student redemption. Mitigated by mandatory immediate password change on first login. | Resolved | Product Lead | [#484](https://github.com/reasvyn/internara/issues/484) |
| A-1 | We assume each user has exactly one primary concrete role at any given time. | Accepted | Architect | — |
| A-2 | We assume password confirmation (sudo mode) applies to database dumps, user deletion, and school metadata changes. | Accepted | Security Lead | [#470](https://github.com/reasvyn/internara/issues/470) |

---

## Quick References

- [`docs/index.md`](../index.md) — Documentation index
- [`docs/prd.md`](../prd.md) — Product Requirement Document (PRD)
- [`docs/adr/adr-flat-rbac-with-functional-roles.md`](../adr/adr-flat-rbac-with-functional-roles.md) — Architectural decision on Flat RBAC
- [`docs/adr/adr-cross-role-proxy.md`](../adr/adr-cross-role-proxy.md) — Architectural decision on Cross-Role Proxy
- [`docs/refs/modules/auth.md`](../refs/modules/auth.md) — Auth module conceptual reference
- [`docs/refs/modules/user.md`](../refs/modules/user.md) — User module conceptual reference
- [GitHub Spec Issues](https://github.com/reasvyn/internara/issues?q=label%3Aspec) — Authoritative spec issues #442–#509
