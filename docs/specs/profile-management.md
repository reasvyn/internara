# Profile Management — User Profile & Settings

> **Last updated:** 2026-07-24 **Changes:** feat — new spec for Phase 3 Identity & Auth;
> profile editing, avatar, password change, role-aware fields

## Description

User profile management page where authenticated users view and edit their personal
information, upload/remove avatar, change password, and access recovery codes. Role-aware
form fields show staff-specific fields (employment status, job title, ID number) only for
admin/teacher/super_admin roles. Super admin identity fields (name, username) are protected
from changes.

---

## 1. Problem Statements

### PS-1 — Users Need to Manage Their Profile

Users need a single page to view and edit their name, email, phone, address, bio, and
avatar. Without this, profile changes require admin intervention, creating unnecessary
support overhead.

### PS-2 — Role-Specific Profile Fields

Teachers and staff have additional profile data (employment status, job title, ID number,
competence field) that students and supervisors do not. A one-size-fits-all form would
either expose irrelevant fields or require separate pages per role.

### PS-3 — Super Admin Identity Protection

The super admin's name and username must never be changed (they are fixed by design — see
`setup-wizard.md`). The profile editor must enforce this constraint at both the UI and
business logic layers.

### PS-4 — Password Change From Profile

Users should be able to change their password from the profile page without navigating
to a separate password-reset flow. This requires verifying the current password before
setting a new one.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide `UpdateProfileAction` — validates and persists profile data + avatar upload |
| G2  | Provide `ReadProfileFormAction` — determines form fields based on user role |
| G3  | Provide `ProfileEditor` Livewire — profile form + password change + avatar management |
| G4  | Enforce super admin integrity (name/username cannot be changed) |
| G5  | Support avatar upload via Spatie MediaLibrary (`avatar` collection) |
| G6  | Log profile changes and dispatch `ProfileUpdated` event |
| G7  | Send `CredentialChangedNotification` when email or username changes |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | School profile management — see `school-profile.md` (#16) |
| NG2  | User CRUD by admins — see `user-crud-and-status.md` (#34) |
| NG3  | Two-factor authentication setup |
| NG4  | Profile visibility/privacy settings |

---

## 3. User Stories / Use Cases

### UC-1 — User Edits Profile

**Actor:** Any authenticated user
**Preconditions:** User is logged in; navigates to `/profile`
**Flow:**
1. `ProfileEditor` Livewire mounts, loads user with `profile` and `roles` relations
2. `ReadProfileFormAction` determines available fields based on role
3. Common fields: name, email, phone, address, bio (always visible)
4. Staff fields: employment_status, job_title, id_number, competence_field (admin/teacher/super_admin only)
5. Super admin: name and username fields shown but disabled
6. User edits fields and submits
7. `UpdateProfileAction` validates all fields, updates `users` and `profiles` tables
8. If email/username changed: dispatches `ProfileUpdated` event → `SendProfileChangedMail` listener sends notification
**Postconditions:** Profile updated; credential change notification sent if applicable

### UC-2 — User Uploads/Removes Avatar

**Actor:** Any authenticated user
**Preconditions:** User is on profile page
**Flow:**
1. User selects image file (max 2MB, image types only)
2. `ProfileEditor::updatedAvatar()` validates and uploads to Spatie MediaLibrary `avatar` collection
3. Avatar preview updates immediately via Livewire reactivity
4. User can click "Remove Avatar" to clear the `avatar` media collection
**Postconditions:** Avatar stored in media library; old avatar replaced or removed

### UC-3 — User Changes Password

**Actor:** Any authenticated user
**Preconditions:** User is on profile page
**Flow:**
1. User enters current password, new password, and confirmation
2. `ProfileEditor::updatePassword()` validates with `PasswordRules::default()` + confirmed
3. Delegates to `UpdateUserPasswordAction` (throttled: 5 per 300s)
4. On success: password updated, `CredentialChangedNotification` sent
**Postconditions:** Password changed; notification dispatched

### UC-4 — User Views Recovery Codes

**Actor:** Any authenticated user
**Preconditions:** User is on profile page
**Flow:**
1. Profile sidebar shows "Recovery Codes" link to `/profile/recovery`
2. User navigates to `RecoveryCode` Livewire (see `account-recovery-slips.md`)
**Postconditions:** User can generate/view/download recovery codes

---

## 4. Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| FR-UP1  | `UpdateProfileAction` must validate all profile fields with explicit rules |
| FR-UP2  | Action must update `users` table fields (name, email, username) in a transaction |
| FR-UP3  | Action must `updateOrCreate` on `profiles` table for profile-specific data |
| FR-UP4  | Action must upload avatar to Spatie MediaLibrary `avatar` collection (if provided) |
| FR-UP5  | Action must enforce super admin integrity: reject name/username changes for super admin via `RejectedException` |
| FR-UP6  | Action must dispatch `ProfileUpdated` event with profile, previous email, previous username |
| FR-UP7  | Action must log profile update via SmartLogger |
| FR-RP1  | `ReadProfileFormAction` must return `fields` array (always: name, email, phone, address, bio) |
| FR-RP2  | Action must return `staffFields` (employment_status, job_title, id_number, competence_field) only for super_admin, admin, teacher roles |
| FR-RP3  | Action must return `canChangeName` / `canChangeUsername` (both `false` for super admin) |
| FR-PE1  | `ProfileEditor` Livewire must load user with `profile` and `roles` relations on mount |
| FR-PE2  | Component must delegate form population to `ReadProfileFormAction` |
| FR-PE3  | Component must authorize via `ProfilePolicy` (admin or owner) |
| FR-PE4  | Component must handle avatar upload with validation (image types, max 2MB) |
| FR-PE5  | Component must support avatar removal (clear `avatar` media collection) |
| FR-PE6  | Component must provide `avatarPreviewUrl()` for Livewire file upload preview |
| FR-PE7  | Component must show role-aware ID number label (NISN for students, NIP for teachers) |
| FR-PW1  | `UpdateUserPasswordAction` must verify current password via `Hash::check()` |
| FR-PW2  | Action must throttle: max 5 attempts per 300 seconds per user+IP |
| FR-PW3  | On success: update password and dispatch `CredentialChangedNotification` |

---

## 5. Non-Functional Requirements

| ID      | Requirement |
| ------- | ----------- |
| NFR-L1  | Profile changes must be logged via SmartLogger with PII masking |
| NFR-E1  | `ProfileUpdated` event must carry previous email/username for change detection |
| NFR-S1  | Super admin name/username changes must be rejected at both UI and business logic layers |
| NFR-M1  | All actions must declare `strict_types=1` |

---

## 6. API / Data Contracts

### Actions

```php
// app/User/Profile/Actions/UpdateProfileAction.php
final class UpdateProfileAction extends BaseCommandAction
{
    public function execute(
        User $user,
        array $data,           // name, email, username, phone, address, bio, etc.
        ?UploadedFile $avatar = null,
    ): ActionResponse;
    // Enforces SuperAdminIntegrityRules
    // updateOrCreate on profiles table
    // Uploads avatar to MediaLibrary
    // Dispatches ProfileUpdated event
}

// app/User/Profile/Actions/ReadProfileFormAction.php
final class ReadProfileFormAction extends BaseReadAction
{
    public function execute(User $user): array;
    // Returns: fields, staffFields, canChangeName, canChangeUsername, role
}
```

### Livewire Component

```php
// app/User/Profile/Livewire/ProfileEditor.php
class ProfileEditor extends BaseFormView
{
    public ProfileForm $profileForm;
    public PasswordForm $passwordForm;
    public ?UploadedFile $avatar = null;
    public ?User $user = null;

    public function mount(): void;
    public function save(UpdateProfileAction $action): void;
    public function updatedAvatar(): void;
    public function confirmRemoveAvatar(): void;
    public function updatePassword(UpdateUserPasswordAction $action): void;
    public function avatarPreviewUrl(): ?string;
    public function getIdNumberLabel(): string;
}
```

### Models

```php
// app/User/Profile/Models/Profile.php
class Profile extends BaseModel
{
    // UUID primary key, belongsTo(User), belongsTo(Department), belongsTo(Company)
    // Fields: phone, address, bio, gender (Gender enum), blood_type (BloodType enum),
    //         pob, dob, emergency_contact (JSON), id_number, national_id_number,
    //         competence_field, employment_status, job_title, internal_notes,
    //         department_id, company_id
}

// User model additions:
// profile(): HasOne
// asSuperAdminIntegrityRules(): SuperAdminIntegrityRules
```

### Events & Listeners

```php
// app/User/Profile/Events/ProfileUpdated.php
class ProfileUpdated extends BaseEvent
{
    public function __construct(
        public Profile $profile,
        public ?string $previousEmail,
        public ?string $previousUsername,
    );
    public function eventName(): string; // 'profile.updated'
}

// app/User/Profile/Listeners/SendProfileChangedMail.php
class SendProfileChangedMail implements ShouldQueue
{
    // Sends CredentialChangedNotification when email or username changes
}
```

### Policy

```php
// app/User/Profile/Policies/ProfilePolicy.php
class ProfilePolicy extends BasePolicy
{
    // viewAny: admins only
    // view: admin or owner
    // update: admin or owner
}
```

### Route

| Route | Component | Middleware |
| ----- | --------- | ---------- |
| `GET /profile` | `ProfileEditor` (Livewire) | `auth` |

---

## 7. Design Decisions

### DD-1 — Single Page for All Profile Actions

**Decision:** Profile editing, password change, and avatar management are on one page
(`/profile`) rather than separate routes.
**Rationale:** Reduces navigation complexity. Users expect profile management to be a
single destination. The `ProfileEditor` component handles all three concerns via
separate Livewire methods.
**Trade-off:** The component is larger than a single-responsibility component. Acceptable
because the actions are thematically related and the component delegates business logic
to dedicated Actions.

### DD-2 — Super Admin Integrity at Business Logic Layer

**Decision:** Super admin name/username protection is enforced in `UpdateProfileAction`
via `SuperAdminIntegrityRules`, not just in the UI.
**Rationale:** UI-only protection can be bypassed via direct API calls or Livewire
payload manipulation. Business logic enforcement is the authoritative guard. The UI
disables fields for visual feedback, but the Action rejects changes regardless.

### DD-3 — Profile as Separate Table (Not Embedded in User)

**Decision:** Profile data lives in a `profiles` table with a one-to-one relationship
to `users`, rather than adding columns to `users`.
**Rationale:** Separates auth-critical fields (name, email, username, password) from
optional profile data (phone, address, bio, employment info). Keeps the `users` table
lean for auth queries. Allows profile to be null (new user without completed profile).

---

## 8. Success Metrics

| Metric | Target |
| ------ | ------ |
| Profile update | < 2 seconds from submit to confirmation |
| Avatar upload | < 5 seconds for 2MB image |
| Super admin protection | 0 successful name/username changes for super admin |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `BaseReadAction`, `ActionResponse`, `RejectedException` |
| [authentication.md](authentication.md) (#17) | `User` model, `AccessToken`, auth infrastructure |
| [rbac-and-authorization.md](rbac-and-authorization.md) (#8) | `ProfilePolicy`, role-based field visibility |
| [file-uploads-media.md](file-uploads-media.md) (#46) | Spatie MediaLibrary for avatar uploads |

### Build Guide
Implement `ReadProfileFormAction` and `UpdateProfileAction` first, then `ProfileEditor`
Livewire component. The `Profile` model and `profiles` table already exist from earlier
migration work. Avatar handling uses Spatie MediaLibrary's `avatar` collection.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [user-crud-and-status.md](user-crud-and-status.md) (#34) | Admin user management builds on profile infrastructure |

---

## Quick References

- `app/User/Profile/Actions/UpdateProfileAction.php` — Profile mutation (119 lines)
- `app/User/Profile/Actions/ReadProfileFormAction.php` — Form field determination (44 lines)
- `app/User/Profile/Livewire/ProfileEditor.php` — Profile page component
- `app/User/Profile/Livewire/Forms/ProfileForm.php` — Profile form binding
- `app/User/Profile/Livewire/Forms/PasswordForm.php` — Password form binding
- `app/User/Profile/Models/Profile.php` — Profile model (UUID PK)
- `app/User/Profile/Events/ProfileUpdated.php` — Profile change event
- `app/User/Profile/Listeners/SendProfileChangedMail.php` — Credential change notification
- `app/User/Profile/Policies/ProfilePolicy.php` — Authorization policy
- `resources/views/user/profile/profile-editor.blade.php` — Profile page view
- `resources/views/user/profile/components/profile-guide.blade.php` — Help modal
- `resources/views/core/widgets/profile-summary.blade.php` — Dashboard widget
- `database/migrations/2026_01_02_000006_create_profiles_table.php` — Profiles migration
- `lang/en/profile.php` — English translations (117 lines)
- **Related spec:** [authentication.md](authentication.md) (#17) — Login, activation, credential changes
- **Related spec:** [account-recovery-slips.md](account-recovery-slips.md) (#23) — Recovery codes (linked from profile sidebar)
