# Account Slips — PDF Credential Distribution

> **Last updated:** 2026-07-22 **Changes:** feat — split from `user-management.md` to cover PDF
> account slip generation, single/batch delivery, email distribution, and the
> `DownloadsAccountSlips` trait as an independent initiative

## Description

Complete specification of Internara's account slip subsystem for PDF credential distribution.
Defines single and batch PDF slip generation via DomPDF with custom card dimensions, activation
code lifecycle management through `AccessToken` generation, email delivery via
`ActivationCodeNotification`, the `DownloadsAccountSlips` Livewire trait for in-component slip
operations, the `AccountSlipController` HTTP layer, and the account slip modal UI. This subsystem
spans the User Management and SysAdmin modules and serves as the primary credential distribution
mechanism for Indonesian vocational schools.

---

## 1. Problem Statements

### PS-1 — Credential Distribution After Account Creation

When administrators create user accounts (individually or via CSV import), they must distribute
login credentials to users. Indonesian vocational schools (SMA/SMK) require printed credential
distribution as the standard practice — students receive physical account slips with their
username, temporary password, and activation instructions. Without a slip generation system,
administrators must manually write or copy credentials, which is error-prone and does not scale.

### PS-2 — Batch Import Requires Bulk Slip Generation

CSV import may create 500+ student accounts in a single operation. Generating account slips
one-by-one for each imported user is impractical. The system must support batch PDF generation
that produces a single multi-page or multi-card PDF containing all imported users' credentials,
ready for printing and physical distribution.

### PS-3 — Printed Slips as Indonesian School Standard

Indonesian schools operate in environments where digital-only credential delivery is unreliable.
Students may not have personal email access, school internet may be intermittent, and teachers
need physical handout materials for classroom distribution. PDF account slips designed for
standard paper printing (custom card dimensions) are the accepted credential delivery mechanism
in this educational context.

### PS-4 — Activation Code Lifecycle for Slip Content

Account slips must display a valid activation code that the student uses to activate their
account. Activation codes are time-limited (30-day expiry) and must be freshly generated each
time a slip is viewed or downloaded. If a code expires or is lost, the administrator must be
able to regenerate it without recreating the account. The slip content (name, username, email,
activation code) must reflect the current state of the user record at generation time.

### PS-5 — Email Distribution as Alternative Channel

While PDF slips are the primary distribution method, administrators also need the option to send
credentials directly via email. This is useful for teacher accounts, supervisor accounts, or
any situation where digital delivery is preferred over printed distribution.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Generate single-user PDF account slips via DomPDF with custom card dimensions (241×156mm) |
| G2  | Generate batch PDF account slips (multiple users in a single PDF) for bulk printing |
| G3  | Display user credentials: name, username, email, activation code on each slip |
| G4  | Freshly generate activation codes via `AccessToken::generateFor()` at slip generation time |
| G5  | Provide email distribution of activation codes via `ActivationCodeNotification` |
| G6  | Support activation code regeneration without account recreation |
| G7  | Integrate slip operations into user management Livewire components via `DownloadsAccountSlips` trait |
| G8  | Display account slip modal with credential preview, download, regenerate, and send actions |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Customizable slip templates or school-configurable layouts |
| NG2  | Digital certificate-style slips with QR codes or digital signatures |
| NG3  | Automatic email delivery of slips on account creation (on-demand only) |
| NG4  | Slip generation for non-user entities (certificates, reports, etc.) |
| NG5  | Bulk email delivery of slips to all imported users in one operation |
| NG6  | Slip PDF storage on disk (PDFs are streamed to browser, not persisted) |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Downloads Account Slip After Single User Creation

**Actor:** Admin / Super Admin
**Preconditions:** Admin has created a user account (or navigated to an existing user)
**Flow:**
1. Admin clicks the account slip action for a specific user in the user management table
2. Account slip modal opens, showing: name, username, email, activation code
3. Activation code is freshly generated via `AccessToken::generateFor()` (type: `activation`)
4. Admin clicks "Download Slip" button
5. `downloadSlip()` method redirects to `AccountSlipController::download()` route
6. Controller calls `GenerateAccountSlipAction::execute($user)`
7. Action renders `user.user-management.account-slip-pdf` Blade view with user data
8. DomPDF renders HTML to PDF with custom paper size [0, 0, 241, 156] (mm)
9. PDF is streamed to browser as `account-slip-{username}.pdf`
**Postconditions:** Admin receives PDF account slip for the user, ready for printing

### UC-2 — Admin Batch Downloads Slips for Imported Users

**Actor:** Admin
**Preconditions:** Multiple users have been selected via checkboxes in the user management table
**Flow:**
1. Admin selects multiple users via checkboxes in the table
2. Admin clicks "Download Selected Slips" batch action
3. `downloadSelectedSlips()` method checks that `selectedIds` is not empty
4. If empty, shows warning flash: "No records selected"
5. If not empty, redirects to `AccountSlipController::downloadBatch()` with `ids` query parameter
6. Controller parses comma-separated IDs, fetches users via `User::whereIn('id', $ids)->get()`
7. Controller calls `GenerateAccountSlipAction::executeBatch($users)`
8. For each user: generates activation code, renders PDF Blade view, appends to HTML
9. DomPDF renders concatenated HTML to single PDF with custom paper size [0, 0, 241, 156] (mm)
10. PDF is streamed to browser as `account-slips-batch.pdf`
**Postconditions:** Admin receives single PDF containing all selected users' account slips

### UC-3 — Admin Regenerates Activation Code

**Actor:** Admin
**Preconditions:** Account slip modal is open for a user
**Flow:**
1. Admin clicks "Regenerate Code" button in the account slip modal
2. `regenerateCode()` method calls `AccessToken::generateFor()` for the `slipUser`
3. New activation code replaces the current `slipCode` value
4. Flash success message: "Code regenerated"
5. Modal updates to display the new activation code
**Postconditions:** New activation code displayed; previous code invalidated

### UC-4 — Admin Sends Activation Code via Email

**Actor:** Admin
**Preconditions:** Account slip modal is open with a valid activation code
**Flow:**
1. Admin clicks "Send Code" button in the account slip modal
2. `sendCode()` method checks that `slipUser` and `slipCode` are set
3. Sends `ActivationCodeNotification` to the user with the current code
4. Notification delivered via `mail` channel (email) and `CustomDatabaseChannel` (in-app)
5. Flash success message: "Code sent"
**Postconditions:** User receives email with activation code and activation link

### UC-5 — Admin Views Account Slip Preview Before Download

**Actor:** Admin
**Preconditions:** Admin has triggered account slip for a user
**Flow:**
1. Account slip modal opens with `showAccountSlip = true`
2. Modal displays user info card: name, username (monospace), email
3. Modal displays activation code prominently (large monospace, selectable text, 30-day expiry note)
4. Modal shows three action buttons: Download Slip, Regenerate Code, Send Code
5. Admin reviews the information before deciding to download or send
**Postconditions:** Admin has visual confirmation of slip content before action

---

## 4. Functional Requirements

### PDF Generation

| ID   | Requirement |
| ---- | ----------- |
| FR-AS1 | `GenerateAccountSlipAction` must extend `BaseCommandAction` |
| FR-AS2 | `execute(User $user): Response` must generate a single-user PDF via DomPDF |
| FR-AS3 | `executeBatch(array $users): Response` must generate a multi-user PDF via DomPDF |
| FR-AS4 | PDF paper size must be custom: `[0, 0, 241, 156]` (width: 241mm, height: 156mm) |
| FR-AS5 | Single-user PDF must be streamed as `account-slip-{username}.pdf` |
| FR-AS6 | Batch PDF must be streamed as `account-slips-batch.pdf` |
| FR-AS7 | PDF must be rendered from `user.user-management.account-slip-pdf` Blade view |
| FR-AS8 | Blade view must receive `$user` (User model) and `$code` (plain-text activation code) |
| FR-AS9 | PDF generation must log an `account_slip_generated` activity with user context |

### Activation Code Lifecycle

| ID   | Requirement |
| ---- | ----------- |
| FR-AC1 | Each slip generation must create a fresh activation code via `AccessToken::generateFor($user, 'activation', ['name' => 'Account Activation'])` |
| FR-AC2 | `DownloadsAccountSlips::showSlip(string $id)` must generate activation code and store in `$slipCode` |
| FR-AC3 | `regenerateCode()` must invalidate the previous code by generating a new `AccessToken` |
| FR-AC4 | Activation codes must expire after 30 days (enforced by `AccessToken` model) |
| FR-AC5 | `$slipCode` must contain the `plain_text` value from `AccessToken::generateFor()` result |

### DownloadsAccountSlips Trait

| ID   | Requirement |
| ---- | ----------- |
| FR-DA1 | Trait must provide `$showAccountSlip` (bool), `$slipUser` (?User), `$slipCode` (string) properties |
| FR-DA2 | `showSlip(string $id)` must find User by ID, generate activation code, set modal open state |
| FR-DA3 | `regenerateCode()` must generate new activation code, flash success message |
| FR-DA4 | `sendCode()` must send `ActivationCodeNotification` with current code, flash success message |
| FR-DA5 | `downloadSlip()` must redirect to `sysadmin.users.account-slip` named route |
| FR-DA6 | `downloadSelectedSlips()` must redirect to `sysadmin.users.account-slips.batch` route with comma-separated IDs |
| FR-DA7 | `downloadSelectedSlips()` must flash warning if `selectedIds` is empty |
| FR-DA8 | All flash messages must use `__()` translation helper |

### Account Slip Modal

| ID   | Requirement |
| ---- | ----------- |
| FR-M1 | Modal must use `x-mary-modal` with `wire:model="showAccountSlip"` |
| FR-M2 | Modal must display: name, username (monospace), email, activation code (large, selectable) |
| FR-M3 | Modal must show activation code expiry note: 30 days |
| FR-M4 | Modal must provide "Download Slip" button wired to `downloadSlip` |
| FR-M5 | Modal must provide "Regenerate Code" button wired to `regenerateCode` with spinner |
| FR-M6 | Modal must provide "Send Code" button wired to `sendCode` with spinner |
| FR-M7 | Modal must provide "Close" action button |
| FR-M8 | Modal must use separator and `backdrop-blur-sm` styling, size `sm` |

### HTTP Controller

| ID   | Requirement |
| ---- | ----------- |
| FR-CTL1 | `AccountSlipController` must be a `final` class in `App\SysAdmin\Http\Controllers` |
| FR-CTL2 | `download(User $user, GenerateAccountSlipAction $action)` must delegate to `$action->execute($user)` |
| FR-CTL3 | `downloadBatch(Request $request, GenerateAccountSlipAction $action)` must parse comma-separated `ids` parameter |
| FR-CTL4 | `downloadBatch` must fetch users via `User::whereIn('id', $ids)->get()` and pass array to `$action->executeBatch()` |
| FR-CTL5 | Controller must receive `GenerateAccountSlipAction` via constructor injection (no service locator) |

### Routes

| ID   | Requirement |
| ---- | ----------- |
| FR-R1 | Single slip route: `GET /admin/users/{user}/account-slip` → `AccountSlipController::download` |
| FR-R2 | Batch slip route: `GET /admin/users/account-slips/download?ids=...` → `AccountSlipController::downloadBatch` |
| FR-R3 | Both routes must require `auth` middleware |
| FR-R4 | Both routes must require `role:super_admin\|admin` middleware |
| FR-R5 | Route names: `sysadmin.users.account-slip` (single), `sysadmin.users.account-slips.batch` (batch) |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | Single account slip PDF generation must complete in < 3 seconds |
| NFR-P2 | Batch PDF generation for 50 users must complete in < 15 seconds |
| NFR-P3 | Account slip modal must open in < 200ms (activation code generation only) |
| NFR-S1 | Activation codes must be generated server-side, never exposed in client JavaScript |
| NFR-S2 | Account slip routes must enforce admin role authorization |
| NFR-S3 | User data on PDF slips must not be accessible to other users or public |
| NFR-S4 | Activation token must not be stored in plaintext in database (hashed by `AccessToken` model) |
| NFR-R1 | Batch slip generation must not fail entirely if one user's code generation fails |
| NFR-R2 | `downloadSlip()` and `sendCode()` must silently return if `$slipUser` is null (defensive guard) |
| NFR-R3 | PDF generation errors must not expose stack traces to the admin user |
| NFR-U1 | Account slip PDF must include proper heading structure (H1 for school name, H2 for user info) |
| NFR-U2 | Activation code must be displayed in monospace font for readability and copy-paste |
| NFR-U3 | Modal must display all user info fields with clear labels (uppercase, tracking-wider) |
| NFR-U4 | Download button must show spinner during PDF generation |
| NFR-U5 | Flash messages must confirm success/failure for: code regeneration, code send, batch selection |
| NFR-A1 | Account slip modal must meet WCAG 2.1 Level AA (keyboard navigable, screen reader accessible) |
| NFR-A2 | Activation code text must use `select-all` class for easy copying |
| NFR-A3 | Modal must trap focus while open |
| NFR-A4 | PDF must include text alternatives for all visual elements |
| NFR-M1 | `GenerateAccountSlipAction` must use Action single-responsibility (no Livewire mutations) |
| NFR-M2 | `DownloadsAccountSlips` trait must not directly mutate models — must delegate to Actions |
| NFR-M3 | All PHP files must declare `strict_types=1` |
| NFR-L1 | All user-facing strings in account slip UI must use `__()` translation helper |
| NFR-L2 | Translation keys must exist in both `lang/en/` and `lang/id/` locale files |

---

## 6. API / Data Contracts

### GenerateAccountSlipAction

```php
// app/User/UserManagement/Actions/GenerateAccountSlipAction.php
final class GenerateAccountSlipAction extends BaseCommandAction
{
    private const int CARD_W = 241; // mm
    private const int CARD_H = 156; // mm

    public function execute(User $user): Response;
    // 1. Logs 'account_slip_generated' activity
    // 2. Generates activation code via AccessToken::generateFor()
    // 3. Renders 'user.user-management.account-slip-pdf' Blade view
    // 4. Returns PDF as streamed response (account-slip-{username}.pdf)

    public function executeBatch(array $users): Response;
    // 1. Iterates over $users array
    // 2. For each: generates activation code, renders Blade view, appends HTML
    // 3. Returns concatenated HTML as single PDF (account-slips-batch.pdf)

    private function download(User $user): Response;
    // Single-user PDF generation (called by execute)
}
```

### DownloadsAccountSlips Trait

```php
// app/User/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php
trait DownloadsAccountSlips
{
    public bool $showAccountSlip = false;
    public ?User $slipUser = null;
    public string $slipCode = '';

    public function showSlip(string $id): void;
    // Finds user, generates activation code, opens modal

    public function regenerateCode(): void;
    // Generates new activation code, flashes success

    public function sendCode(): void;
    // Sends ActivationCodeNotification, flashes success

    public function downloadSlip(): void;
    // Redirects to single slip download route

    public function downloadSelectedSlips(): void;
    // Redirects to batch slip download route with selected IDs
}
```

### AccountSlipController

```php
// app/SysAdmin/Http/Controllers/AccountSlipController.php
final class AccountSlipController
{
    public function download(User $user, GenerateAccountSlipAction $action): mixed;
    // Delegates to $action->execute($user)

    public function downloadBatch(Request $request, GenerateAccountSlipAction $action): mixed;
    // Parses 'ids' query param, fetches users, delegates to $action->executeBatch()
}
```

### ActivationCodeNotification

```php
// app/User/UserManagement/Notifications/ActivationCodeNotification.php
class ActivationCodeNotification extends Notification
{
    public function __construct(public readonly User $user, public readonly string $code);

    public function via(object $notifiable): array;
    // Returns ['mail', CustomDatabaseChannel::class]

    public function toMail(object $notifiable): object;
    // Subject: __('user.activation.email_subject')
    // Greeting: user name
    // Content: activation code, action link (route('activate')), 30-day expiry

    public function toCustomDatabase(object $notifiable): array;
    // In-app notification with type 'activation_code'
}
```

### PDF Blade View Contract

```
View: user.user-management.account-slip-pdf
Path: resources/views/user/user-management/account-slip-pdf.blade.php
Variables:
  $user — App\User\Models\User (name, username, email)
  $code — string (plain-text activation code)
Output: HTML rendered by DomPDF to PDF
Paper: [0, 0, 241, 156] mm (custom card)
```

### Routes

```
GET  /admin/users/{user}/account-slip        → AccountSlipController::download
     Name: sysadmin.users.account-slip
     Middleware: auth, role:super_admin|admin

GET  /admin/users/account-slips/download?ids={csv}  → AccountSlipController::downloadBatch
     Name: sysadmin.users.account-slips.batch
     Middleware: auth, role:super_admin|admin
```

### DomPDF Configuration

```
Config: config/dompdf.php
Paper size override: [0, 0, 241, 156] (set per-request, not global config)
Default paper size: a4 (global default, overridden for account slips)
PDF backend: CPDF
Font directory: storage_path('fonts')
Chroot: realpath(base_path())
Enable remote: false (no external resources in PDF)
DPI: 96
```

### Activity Log

```
Event: account_slip_generated
Context: ['user_id' => $user->id]
Logged by: GenerateAccountSlipAction via $this->log()
```

---

## 7. Design Decisions

### DD-1 — PDF Account Slips via DomPDF (Not HTML or Email)

**Decision:** Account slips are generated as PDF via `barryvdh/laravel-dompdf`, not rendered as
HTML pages or sent as email bodies.
**Rationale:** Indonesian schools require printed credential distribution. Teachers physically hand
out account slips to students in classrooms. PDF is the universal format for printable documents
across all operating systems and printers. DomPDF runs server-side without external services or
API dependencies, which is critical for self-hosted deployments in schools with limited
infrastructure.
**Trade-off:** DomPDF has limited CSS support (no flexbox, no grid, limited font loading). Mitigated
by using simple table-based or block layouts in the slip template. Complex visual designs are
not required for credential slips.

### DD-2 — Custom Card Dimensions (241×156mm)

**Decision:** Account slips use custom paper size `[0, 0, 241, 156]` (241mm wide, 156mm tall)
instead of standard A4 or Letter.
**Rationale:** Account slips are compact credential cards, not full-page documents. The custom
size produces a card-like format that fits multiple slips per A4 page when printed, reducing
paper waste. The dimensions are chosen to accommodate the school logo, user credentials, and
activation code without excessive white space.
**Trade-off:** Custom sizes may not align perfectly with all printer driver defaults. Mitigated by
the slip being a standalone PDF that can be positioned on standard paper at print time.

### DD-3 — Fresh Activation Code Per Slip Generation

**Decision:** Each time a slip is viewed, downloaded, or regenerated, a new `AccessToken` of
type `activation` is created via `AccessToken::generateFor()`.
**Rationale:** Activation codes are time-limited (30-day expiry). Generating a fresh code at slip
view time ensures the displayed code is always valid and ready for use. If an admin downloads
a slip, then later regenerates, the previous code is automatically superseded. This avoids
stale/expired codes appearing on printed slips.
**Trade-off:** Multiple `AccessToken` records may exist for a single user (only the latest is
valid). Mitigated by the activation system accepting any valid non-expired token.

### DD-4 — DownloadsAccountSlips as Livewire Trait (Not Standalone Component)

**Decision:** Slip download/send operations are encapsulated in a `DownloadsAccountSlips` trait
consumed by `UserManager` and other role-specific managers, rather than being a standalone
Livewire component.
**Rationale:** Account slip operations are tightly coupled to the user management table context
(selected users, current user context). A trait keeps the slip logic co-located with the manager
that owns the selection state (`$selectedIds`). Each manager (UserManager, StudentManager,
TeacherManager, SupervisorManager) can compose in the trait independently.
**Trade-off:** Trait state (`$showAccountSlip`, `$slipUser`, `$slipCode`) is mixed into the
consuming component's property namespace. Mitigated by using the `slip` prefix on all properties
to avoid collisions.

### DD-5 — PDF Streamed, Not Stored

**Decision:** PDF account slips are streamed directly to the browser (`->stream()`) and not
persisted to disk storage.
**Rationale:** Account slips contain sensitive credentials (activation codes) that change on each
generation. Storing PDFs would create stale credential artifacts that could be accessed later.
Streaming ensures each PDF reflects the current state of the user and their latest activation
code. The PDF generation cost is acceptable for the use case (on-demand, admin-initiated).
**Trade-off:** PDF cannot be cached or pre-generated. Regenerating the same slip produces a new
activation code each time. This is intentional — re-generation implies the previous code should
be replaced.

### DD-6 — Batch Slips as Concatenated HTML to Single PDF

**Decision:** Batch slip generation concatenates individual user Blade renders into a single
HTML string, then renders the entire string as one DomPDF document.
**Rationale:** Producing a single multi-card PDF is more convenient for printing than downloading
individual PDFs per user. Concatenation is simple — each user's card is an independent HTML
block that stacks vertically. DomPDF handles the multi-page rendering automatically when content
exceeds the custom card dimensions.
**Trade-off:** If one user's rendering fails, the entire batch could fail. Mitigated by wrapping
each iteration in the foreach loop and the fact that Blade rendering failures are rare for
well-defined views.

---

## 8. Success Metrics

### 8.1 Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Single slip generation | < 3s | `GenerateAccountSlipAction::execute()` total time including DomPDF render |
| Batch slip generation (50 users) | < 15s | `GenerateAccountSlipAction::executeBatch()` total time |
| Account slip modal open | < 200ms | `showSlip()` method — User lookup + AccessToken generation |
| Activation code regeneration | < 200ms | `regenerateCode()` method — AccessToken generation only |

### 8.2 Functionality

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Single slip PDF correctness | All fields rendered | Name, username, email, activation code visible in PDF |
| Batch slip PDF correctness | All users rendered | Each user card present in batch PDF with unique credentials |
| Activation code validity | 100% of generated codes work | Codes accepted by activation system |
| Email delivery | Code received by user | `ActivationCodeNotification` sent via mail channel |

### 8.3 User Experience

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Modal preview accuracy | Matches PDF content | Admin sees same data in modal as in PDF |
| Flash message clarity | User understands result | Success/failure messages for all operations |
| Empty selection handling | Graceful warning | `downloadSelectedSlips()` warns when no users selected |
| Button responsiveness | Spinner during action | Download/send buttons show loading state |

### 8.4 Security

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Route authorization | Admin-only access | `role:super_admin\|admin` middleware on both routes |
| No credential leakage | PDF not publicly accessible | Routes require authentication |
| Activation code freshness | Generated at view time | `AccessToken::generateFor()` called on each `showSlip()` |
| Previous code invalidation | Old codes superseded | New `AccessToken` replaces previous (implicit by token model) |

### 8.5 Architecture Compliance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| No model mutations in Livewire (C1) | 0 violations | All mutations delegated to Actions |
| No service locator (C2) | 0 violations | `AccountSlipController` uses constructor injection |
| Strict types (D1) | 100% files | `declare(strict_types=1)` in all PHP files |
| User strings use `__()` (D3) | 100% of UI strings | All flash messages and labels use translation helper |

---

## 9. Roadmap

### Prerequisites
This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [user-crud-and-status.md](user-crud-and-status.md) | User entities — account slips are generated for placed students |

### Build Guide
After implementing this spec, the system can generate account slips — credential documents given to students for their internship placement. Slips contain student info, company details, and placement dates. The next phase is daily operations — once students are placed with slips, they begin logging activities and attendance.

### Next Steps
| Order | Spec | Connection |
|-------|------|------------|
| 1 | [daily-activity.md](daily-activity.md) | Students with active placements (confirmed by account slips) begin logbook entries |

---

## Quick References

- `app/User/UserManagement/Actions/GenerateAccountSlipAction.php` — PDF slip generation (single + batch)
- `app/User/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php` — Livewire trait for slip operations
- `app/SysAdmin/Http/Controllers/AccountSlipController.php` — HTTP controller for PDF download routes
- `app/User/UserManagement/Notifications/ActivationCodeNotification.php` — Email notification with activation code
- `app/Auth/AccessTokens/Models/AccessToken.php` — Activation token generation and lifecycle
- `resources/views/user/user-management/components/account-slip-modal.blade.php` — Slip modal UI
- `resources/views/user/user-management/account-slip-pdf.blade.php` — PDF Blade template (DomPDF)
- `config/dompdf.php` — DomPDF configuration (paper size, fonts, rendering backend)
- `routes/web/sysadmin.php:32-34` — Account slip route definitions
- `app/User/UserManagement/Actions/CreateUserAction.php` — User creation with activation code notification
- `docs/specs/user-crud-and-status.md` — User CRUD and AccountStatus state machine
- `docs/specs/csv-import-export.md` — CSV import/export
- `docs/modules/user.md` — User module conceptual documentation
