# Announcement System — Targeted Multi-Role Communication

> **Last updated:** 2026-07-24 **Changes:** feat — initial spec documenting the announcement
> system: role-targeted creation, scheduling with cron-based auto-publish, status lifecycle,
> notification dispatch, and admin management UI

## Description

Specification of Internara's announcement system: an admin-facing capability for creating,
scheduling, and publishing announcements to specific user roles. Covers the Announcement model,
`AnnouncementStatus` state machine, `AnnouncementState` entity, Livewire management UI, three
action classes (create/send, publish, delete), a `PublishScheduledAnnouncementsCommand` artisan
command running every minute, and multi-channel notification dispatch via `AnnouncementNotification`.

---

## 1. Problem Statements

### PS-1 — No Mechanism for Admins to Communicate Targeted Messages

School administrators need to broadcast important information — schedule changes, policy updates,
emergency notices — to specific groups of users (students only, teachers only, all users). Without
an announcement system, admins have no structured way to reach targeted audiences. Email alone is
unreliable for reaching all user types, and there is no audit trail of what was communicated.

### PS-2 — No Scheduled Publishing for Time-Sensitive Messages

Announcements like "Exam schedule changes effective Monday" need to be composed in advance and
published automatically at a specific time. Without scheduling, admins must remember to manually
publish at the right moment, risking missed or delayed communications.

### PS-3 — No Lifecycle Management for Announcement Content

Announcements need a draft→published workflow so admins can prepare content without immediate
broadcast. Without this, every message is sent instantly on creation, leaving no room for review
or correction before the audience sees it.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide CRUD for announcements with title, message (Markdown), type (info/success/warning/error), optional link, and role targeting |
| G2  | Implement a three-state lifecycle: `draft` → `scheduled` → `published` with enforced transitions via `AnnouncementStatus` enum |
| G3  | Support scheduled publishing via `scheduled_at` timestamp, auto-published by `announcements:publish` artisan command running every minute |
| G4  | Dispatch multi-channel notifications (mail, broadcast, custom database) to targeted roles via `AnnouncementNotification` |
| G5  | Exclude the sender's own roles from notification recipients to avoid self-notification |
| G6  | Provide a Livewire management UI (`AnnouncementManager`) with inline form, list view, publish-now, and delete-with-confirmation |
| G7  | Enforce admin-only access via route middleware (`role:super_admin|admin`) and `AnnouncementManager::boot()` authorization |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Expiry or unpublishing — `PUBLISHED` is a terminal state with no reversal (→ DD-3) |
| NG2  | Per-user notification preferences or opt-out from announcements |
| NG3  | Announcement read tracking or analytics (opened, clicked) |
| NG4  | Rich text editing — Markdown-only via a markdown editor component |
| NG5  | Announcement attachments or embedded media |
| NG6  | Public-facing announcement display (e.g., login page banner) — announcements deliver via notification channel only |

---

## 3. User Stories / Use Cases

### UC-1 — Admin Creates and Immediately Publishes an Announcement

**Actor:** Admin (super_admin or admin role)
**Preconditions:** User authenticated with admin role, on the announcements page
**Flow:**
1. Admin clicks "New Announcement" button
2. `AnnouncementManager` sets `$showForm = true`, renders inline `AnnouncementForm`
3. Admin fills in title, message, selects type "warning", sets status to "published"
4. Admin leaves "Send to all users" toggle ON (default)
5. Admin clicks "Send Announcement" → `save()` calls `SendAnnouncementAction::execute()`
6. Action validates payload, creates `Announcement` with status `PUBLISHED`
7. Action calls `sendNotifications()` — queries all users, excludes admin's own roles, dispatches `AnnouncementNotification`
8. Notification sent via mail, broadcast, and custom database channel to each recipient
9. Flash message "Announcement sent successfully" displayed
**Postconditions:** Announcement persisted as published, notifications delivered to all non-admin users

### UC-2 — Admin Creates a Scheduled Announcement

**Actor:** Admin
**Preconditions:** User authenticated with admin role
**Flow:**
1. Admin fills in announcement form, selects status "scheduled"
2. Admin enters `scheduled_at` datetime (must be >= now, enforced by validation)
3. Admin submits → `SendAnnouncementAction::execute()` creates announcement with status `SCHEDULED`
4. No notifications sent yet (status is not `PUBLISHED`)
5. `announcements:publish` command runs every minute via scheduler
6. When `scheduled_at <= now()`, command finds the announcement, calls `PublishAnnouncementAction::execute()`
7. `PublishAnnouncementAction` transitions status to `PUBLISHED`, clears `scheduled_at`, dispatches notifications
**Postconditions:** Announcement auto-published at scheduled time, notifications delivered

### UC-3 — Admin Manually Publishes a Draft or Scheduled Announcement

**Actor:** Admin
**Preconditions:** Announcement exists with status `DRAFT` or `SCHEDULED`
**Flow:**
1. Admin sees publish icon (paper airplane) next to draft/scheduled announcements
2. Admin clicks → `confirmPublish($id)` shows confirmation modal
3. Admin confirms → `confirmAction()` checks `canTransitionTo(PUBLISHED)`
4. If valid, calls `PublishAnnouncementAction::execute()` — updates status, clears `scheduled_at`, sends notifications
5. Flash message "Announcement published" displayed
**Postconditions:** Announcement published, notifications dispatched

### UC-4 — Admin Deletes an Announcement

**Actor:** Admin
**Preconditions:** Announcement exists, user is the creator (ownership check in query)
**Flow:**
1. Admin clicks delete icon on an announcement row
2. `confirmDelete($id)` shows confirmation modal
3. Admin confirms → `confirmAction()` calls `DeleteAnnouncementAction::execute()`
4. Action deletes within a transaction, logs `announcement_deleted`
5. Flash message "Announcement deleted" displayed
**Postconditions:** Announcement removed from database

### UC-5 — System Auto-Publishes Scheduled Announcements via Cron

**Actor:** System (scheduler)
**Preconditions:** At least one announcement with status `SCHEDULED` and `scheduled_at <= now()`
**Flow:**
1. Laravel scheduler runs `announcements:publish` every minute (→ `routes/console.php:17`)
2. `PublishScheduledAnnouncementsCommand::handle()` queries `Announcement::where('status', SCHEDULED)->where('scheduled_at', '<=', now())`
3. For each due announcement, calls `PublishAnnouncementAction::execute()`
4. Action transitions status to `PUBLISHED`, dispatches notifications to targeted roles
5. Command outputs per-announcement status and completion count
**Postconditions:** All due scheduled announcements published, notifications delivered

---

## 4. Functional Requirements

### Announcement Model

| ID   | Requirement |
| ---- | ----------- |
| FR-M1 | `Announcement` model must use `#[Fillable]` attribute with: `title`, `message`, `type`, `status`, `scheduled_at`, `link`, `target_roles`, `created_by` (→ `Models/Announcement.php:17-27`) |
| FR-M2 | `Announcement` model must cast `target_roles` → `array`, `status` → `AnnouncementStatus::class`, `scheduled_at` → `datetime` (→ `Models/Announcement.php:33-39`) |
| FR-M3 | `Announcement` model must define `creator(): BelongsTo` relationship to `User` via `created_by` foreign key (→ `Models/Announcement.php:42-45`) |
| FR-M4 | `Announcement` model must provide query scopes: `published()`, `draft()`, `scheduled()`, `pendingPublish()` — the last filtering `SCHEDULED` where `scheduled_at <= now()` (→ `Models/Announcement.php:47-67`) |
| FR-M5 | `Announcement` model must provide `asAnnouncementState(): AnnouncementState` bridge method and status-check helpers: `isScheduled()`, `isDraft()`, `isPublished()` (→ `Models/Announcement.php:69-87`) |
| FR-M6 | `announcements` table must have foreign key `created_by` → `users.id` with `onDelete('cascade')` and indexes on `created_by`, `created_at`, `status` (→ migration `2026_01_01_000007`) |

### AnnouncementStatus Enum

| ID   | Requirement |
| ---- | ----------- |
| FR-E1 | `AnnouncementStatus` must be a backed string enum implementing `StatusEnum` contract with cases: `DRAFT = 'draft'`, `SCHEDULED = 'scheduled'`, `PUBLISHED = 'published'` (→ `Enums/AnnouncementStatus.php:9-13`) |
| FR-E2 | `AnnouncementStatus::canTransitionTo()` must enforce: `DRAFT` → `[SCHEDULED, PUBLISHED]`, `SCHEDULED` → `[PUBLISHED]`, `PUBLISHED` → `[]` (no transitions) (→ `Enums/AnnouncementStatus.php:24-35`) |
| FR-E3 | `AnnouncementStatus::isTerminal()` must return `true` only for `PUBLISHED` (→ `Enums/AnnouncementStatus.php:37-43`) |
| FR-E4 | `AnnouncementStatus::default()` must return `DRAFT` (→ `Enums/AnnouncementStatus.php:54-57`) |
| FR-E5 | `AnnouncementStatus::label()` must return localized strings via `__('announcement.status.{value}')` (→ `Enums/AnnouncementStatus.php:15-22`) |

### AnnouncementState Entity

| ID   | Requirement |
| ---- | ----------- |
| FR-EN1 | `AnnouncementState` must be `final readonly` extending `BaseEntity` with constructor params: `AnnouncementStatus $status`, `?Carbon $scheduledAt` (→ `Entities/AnnouncementState.php:12-17`) |
| FR-EN2 | `AnnouncementState::fromModel()` must hydrate from an Eloquent model, handling both already-cast enum and raw string values for status (→ `Entities/AnnouncementState.php:19-27`) |
| FR-EN3 | `AnnouncementState::isPendingPublish()` must return `true` when status is `SCHEDULED` and `scheduledAt <= now()` (→ `Entities/AnnouncementState.php:44-51`) |

### SendAnnouncementAction

| ID   | Requirement |
| ---- | ----------- |
| FR-A1 | `SendAnnouncementAction` must extend `BaseCommandAction` and accept an `array $data` payload (→ `Actions/SendAnnouncementAction.php:15-17`) |
| FR-A2 | `SendAnnouncementAction::execute()` must validate input via `Validator` with rules: title required|max:255, message required|max:5000, type required|in:info/success/warning/error, status nullable|in:draft/scheduled/published, scheduled_at nullable|date|after_or_equal:now, link nullable|max:500, target_roles nullable|array (→ `Actions/SendAnnouncementAction.php:19-28`) |
| FR-A3 | `SendAnnouncementAction::execute()` must create an `Announcement` record within a transaction, setting `created_by` to `auth()->id()` (→ `Actions/SendAnnouncementAction.php:34-44`) |
| FR-A4 | `SendAnnouncementAction::execute()` must call `sendNotifications()` only when status is `PUBLISHED` (→ `Actions/SendAnnouncementAction.php:46-48`) |
| FR-A5 | `SendAnnouncementAction::sendNotifications()` must query users, excluding the sender's own roles when `target_roles` is non-empty, then dispatch `AnnouncementNotification` via `Notification::send()` (→ `Actions/SendAnnouncementAction.php:60-80`) |
| FR-A6 | `SendAnnouncementAction::execute()` must log `announcement_sent` with title, status, and target_roles via `$this->log()` (→ `Actions/SendAnnouncementAction.php:50-54`) |

### PublishAnnouncementAction

| ID   | Requirement |
| ---- | ----------- |
| FR-A7 | `PublishAnnouncementAction` must extend `BaseCommandAction` and accept an `Announcement $announcement` (→ `Actions/PublishAnnouncementAction.php:14-16`) |
| FR-A8 | `PublishAnnouncementAction::execute()` must transition status to `PUBLISHED`, clear `scheduled_at` to null, send notifications to targeted users, and log `announcement_published` — all within a transaction (→ `Actions/PublishAnnouncementAction.php:17-44`) |
| FR-A9 | `PublishAnnouncementAction` must query notification recipients with the same role-exclusion logic as `SendAnnouncementAction::sendNotifications()` (→ `Actions/PublishAnnouncementAction.php:24-32`) |

### DeleteAnnouncementAction

| ID   | Requirement |
| ---- | ----------- |
| FR-A10 | `DeleteAnnouncementAction` must extend `BaseCommandAction` and accept an `Announcement $announcement` (→ `Actions/DeleteAnnouncementAction.php:10-12`) |
| FR-A11 | `DeleteAnnouncementAction::execute()` must delete the announcement within a transaction and log `announcement_deleted` with the title (→ `Actions/DeleteAnnouncementAction.php:13-21`) |

### AnnouncementManager Livewire Component

| ID   | Requirement |
| ---- | ----------- |
| FR-L1 | `AnnouncementManager` must extend `BaseRecordManager`, render `sysadmin.announcement.announcement-manager`, and authorize `viewAny` on `User::class` in `boot()` (→ `Livewire/AnnouncementManager.php:20-35`) |
| FR-L2 | `AnnouncementManager::query()` must scope to `Announcement::where('created_by', Auth::id())` — admins see only their own announcements (→ `Livewire/AnnouncementManager.php:48-51`) |
| FR-L3 | `AnnouncementManager::headers()` must return columns: `title` (sortable), `type`, `status`, `created_at` (sortable), `actions` (→ `Livewire/AnnouncementManager.php:37-46`) |
| FR-L4 | `AnnouncementManager::applySearch()` must filter by `title LIKE %search%` (→ `Livewire/AnnouncementManager.php:53-56`) |
| FR-L5 | `AnnouncementManager::save()` must delegate to `SendAnnouncementAction::execute()` with `$this->form->toPayload()`, flash success, and reset form (→ `Livewire/AnnouncementManager.php:58-71`) |
| FR-L6 | `AnnouncementManager::confirmAction()` must handle both `delete` and `publish` action types, verifying ownership via `where('created_by', Auth::id())->findOrFail($id)` before executing (→ `Livewire/AnnouncementManager.php:87-115`) |
| FR-L7 | `AnnouncementManager::render()` must pass `announcements` (paginated rows) and `roles` (all roles except super_admin, mapped to `id`/`name`) to the view (→ `Livewire/AnnouncementManager.php:123-134`) |

### AnnouncementForm Livewire Form Object

| ID   | Requirement |
| ---- | ----------- |
| FR-F1 | `AnnouncementForm` must extend `Livewire\Form` with properties: `title`, `message`, `type` (default `'info'`), `status` (default `DRAFT->value`), `scheduled_at`, `link`, `target_roles`, `sendToAll` (default `true`) (→ `Livewire/Forms/AnnouncementForm.php:11-27`) |
| FR-F2 | `AnnouncementForm::rules()` must validate: title required|max:255, message required|max:5000, type required|in:info/success/warning/error, scheduled_at required_if:status=scheduled and after_or_equal:now, target_roles.* exists:roles,name (→ `Livewire/Forms/AnnouncementForm.php:29-45`) |
| FR-F3 | `AnnouncementForm::toPayload()` must return array mapping form fields to action payload, nullifying `scheduled_at` when status is not `scheduled`, and nullifying `target_roles` when `sendToAll` is true (→ `Livewire/Forms/AnnouncementForm.php:47-58`) |

### AnnouncementNotification

| ID   | Requirement |
| ---- | ----------- |
| FR-N1 | `AnnouncementNotification` must implement `ShouldQueue` and use channels `['mail', 'broadcast', CustomDatabaseChannel::class]` (→ `Notifications/AnnouncementNotification.php:13-26`) |
| FR-N2 | `AnnouncementNotification::toMail()` must return `MailMessage` with subject = title, greeting = `__('Hello!')`, line = message, and conditional action link when `$this->link` is non-null (→ `Notifications/AnnouncementNotification.php:28-38`) |
| FR-N3 | `AnnouncementNotification::toBroadcast()` must return array with `title`, `message`, `link` keys (→ `Notifications/AnnouncementNotification.php:40-47`) |
| FR-N4 | `AnnouncementNotification::toCustomDatabase()` must return array with `type: 'announcement'`, `title`, `message`, `link`, `data: []` — following the standard notification contract (→ `Notifications/AnnouncementNotification.php:49-58`) |

### PublishScheduledAnnouncementsCommand

| ID   | Requirement |
| ---- | ----------- |
| FR-C1 | `PublishScheduledAnnouncementsCommand` must define signature `announcements:publish` and run every minute via Laravel scheduler (→ `Console/Commands/PublishScheduledAnnouncementsCommand.php:14-16`, `routes/console.php:17-19`) |
| FR-C2 | `PublishScheduledAnnouncementsCommand::handle()` must query announcements where `status = SCHEDULED AND scheduled_at <= now()`, then call `PublishAnnouncementAction::execute()` for each (→ `Console/Commands/PublishScheduledAnnouncementsCommand.php:20-36`) |
| FR-C3 | `PublishScheduledAnnouncementsCommand::handle()` must output per-announcement task status and a completion summary with count (→ `Console/Commands/PublishScheduledAnnouncementsCommand.php:32-43`) |

### Route & Access Control

| ID   | Requirement |
| ---- | ----------- |
| FR-R1 | Route `GET /admin/announcements` must map to `AnnouncementManager` with name `sysadmin.announcements` and middleware `['auth', 'role:super_admin|admin']` (→ `routes/web/sysadmin.php:48-50`) |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | `announcements:publish` command must process all due announcements in a single invocation — no per-announcement process forking |
| NFR-P2 | Notification dispatch for announcements must use `ShouldQueue` to avoid blocking the admin's request when targeting large user sets (→ `AnnouncementNotification` implements `ShouldQueue`) |
| NFR-S1 | Only users with `super_admin` or `admin` role may access the announcements page — enforced by route middleware and `AnnouncementManager::boot()` authorization (→ FR-R1, FR-L1) |
| NFR-S2 | Admins can only see and manage their own announcements — query scoped to `created_by = Auth::id()` (→ FR-L2, FR-L6) |
| NFR-S3 | Sender is excluded from their own notification recipients via role exclusion query (→ FR-A5, FR-A9) |
| NFR-S4 | `scheduled_at` validation enforces `after_or_equal:now` to prevent scheduling in the past (→ FR-A2, FR-F2) |
| NFR-U1 | All user-facing strings must use `__('announcement.*')` translation keys (→ `lang/en/announcement.php`) |
| NFR-U2 | Announcement message must support Markdown rendering with HTML sanitization (`html_input => strip`, `allow_unsafe_links => false`) (→ `announcement-manager.blade.php:99`) |
| NFR-U3 | The management UI must provide an inline guide (help button) explaining create, schedule, publish, and target workflows (→ `announcement-guide.blade.php`) |
| NFR-U4 | Delete and publish actions must require explicit user confirmation via modal dialog (→ FR-L6) |
| NFR-R1 | `SendAnnouncementAction` and `PublishAnnouncementAction` must wrap state changes and notification dispatch in a database transaction (→ FR-A3, FR-A8) |
| NFR-R2 | `PublishScheduledAnnouncementsCommand` must handle zero due announcements gracefully, outputting a "none found" info message (→ `Console/Commands/PublishScheduledAnnouncementsCommand.php:24-28`) |
| NFR-M1 | All announcement classes must use `declare(strict_types=1)` (→ D1 convention) |
| NFR-M2 | `AnnouncementStatus` enum must implement the `StatusEnum` contract with full transition validation (→ FR-E2) |
| NFR-M3 | `AnnouncementState` entity must be `final readonly` with `fromModel()` bridge (→ FR-EN1) |

---

## 6. API / Data Contracts

### 6.1 Announcement Model

```php
// app/SysAdmin/Announcement/Models/Announcement.php (93 lines)
#[Fillable(['title', 'message', 'type', 'status', 'scheduled_at', 'link', 'target_roles', 'created_by'])]
class Announcement extends BaseModel
{
    // Casts: target_roles → array, status → AnnouncementStatus::class, scheduled_at → datetime
    public function creator(): BelongsTo;           // → User via created_by
    public function scopePublished(Builder $q): Builder;
    public function scopeDraft(Builder $q): Builder;
    public function scopeScheduled(Builder $q): Builder;
    public function scopePendingPublish(Builder $q): Builder;  // SCHEDULED + scheduled_at <= now()
    public function asAnnouncementState(): AnnouncementState;
    public function isScheduled(): bool;
    public function isDraft(): bool;
    public function isPublished(): bool;
}
```

### 6.2 AnnouncementStatus Enum

```php
// app/SysAdmin/Announcement/Enums/AnnouncementStatus.php (58 lines)
enum AnnouncementStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';

    public function label(): string;                          // Localized via __()
    public function canTransitionTo(StatusEnum $target): bool; // DRAFT→[SCHEDULED,PUBLISHED], SCHEDULED→[PUBLISHED], PUBLISHED→[]
    public function isTerminal(): bool;                        // true only for PUBLISHED
    public function validTransitions(): array;
    public static function default(): self;                    // DRAFT
}
```

### 6.3 AnnouncementState Entity

```php
// app/SysAdmin/Announcement/Entities/AnnouncementState.php (52 lines)
final readonly class AnnouncementState extends BaseEntity
{
    public function __construct(
        private AnnouncementStatus $status,
        private ?Carbon $scheduledAt,
    ) {}

    public static function fromModel(Model $model): static;
    public function isPublished(): bool;
    public function isDraft(): bool;
    public function isScheduled(): bool;
    public function isPendingPublish(?Carbon $now = null): bool; // SCHEDULED + scheduledAt <= now
}
```

### 6.4 SendAnnouncementAction

```php
// app/SysAdmin/Announcement/Actions/SendAnnouncementAction.php (81 lines)
final class SendAnnouncementAction extends BaseCommandAction
{
    public function execute(array $data): Announcement;
    // Validates via Validator, creates Announcement in transaction, conditionally sends notifications if PUBLISHED
    // Logs 'announcement_sent' with title, status, target_roles

    public function sendNotifications(Announcement $announcement, array $config): void;
    // Queries users, excludes sender's roles when target_roles non-empty, dispatches AnnouncementNotification
}
```

### 6.5 PublishAnnouncementAction

```php
// app/SysAdmin/Announcement/Actions/PublishAnnouncementAction.php (46 lines)
final class PublishAnnouncementAction extends BaseCommandAction
{
    public function execute(Announcement $announcement): void;
    // Transitions to PUBLISHED, clears scheduled_at, sends notifications, logs 'announcement_published' — in transaction
}
```

### 6.6 DeleteAnnouncementAction

```php
// app/SysAdmin/Announcement/Actions/DeleteAnnouncementAction.php (22 lines)
final class DeleteAnnouncementAction extends BaseCommandAction
{
    public function execute(Announcement $announcement): void;
    // Deletes within transaction, logs 'announcement_deleted' with title
}
```

### 6.7 AnnouncementForm

```php
// app/SysAdmin/Announcement/Livewire/Forms/AnnouncementForm.php (59 lines)
class AnnouncementForm extends Form
{
    public string $title = '';
    public string $message = '';
    public string $type = 'info';
    public string $status = AnnouncementStatus::DRAFT->value;
    public ?string $scheduled_at = null;
    public ?string $link = null;
    /** @var string[] */
    public array $target_roles = [];
    public bool $sendToAll = true;

    public function rules(): array;
    public function toPayload(): array;
}
```

### 6.8 AnnouncementManager

```php
// app/SysAdmin/Announcement/Livewire/AnnouncementManager.php (135 lines)
class AnnouncementManager extends BaseRecordManager
{
    public AnnouncementForm $form;
    public bool $showForm = false;
    public bool $showConfirm = false;
    public ?string $confirmId = null;
    public string $confirmActionType = '';

    public function boot(): void;                          // authorize viewAny on User::class
    public function headers(): array;                      // title, type, status, created_at, actions
    protected function query(): Builder;                   // where('created_by', Auth::id())
    protected function applySearch(Builder $query): Builder;
    public function save(SendAnnouncementAction $action): void;
    public function confirmDelete(string $id): void;
    public function confirmPublish(string $id): void;
    public function confirmAction(): void;                 // dispatches to delete or publish
    public function resetForm(): void;
    public function render(): View;                        // passes announcements + roles to view
}
```

### 6.9 AnnouncementNotification

```php
// app/SysAdmin/Announcement/Notifications/AnnouncementNotification.php (59 lines)
class AnnouncementNotification extends Notification implements ShouldQueue
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $link = null,
    ) {}

    public function via($notifiable): array;               // ['mail', 'broadcast', CustomDatabaseChannel::class]
    public function toMail($notifiable): MailMessage;
    public function toBroadcast($notifiable): array;
    public function toCustomDatabase($notifiable): array;  // type: 'announcement'
}
```

### 6.10 PublishScheduledAnnouncementsCommand

```php
// app/SysAdmin/Announcement/Console/Commands/PublishScheduledAnnouncementsCommand.php (45 lines)
class PublishScheduledAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:publish';
    protected $description = 'Publish all scheduled announcements whose scheduled_at has passed';

    public function handle(PublishAnnouncementAction $action): int;
    // Queries due announcements, calls PublishAnnouncementAction for each, outputs per-task status
}
```

### 6.11 announcements Table Schema

```php
// database/migrations/2026_01_01_000007_create_announcements_table.php
Schema::create('announcements', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('created_by')->constrained('users', 'id')->onDelete('cascade');
    $table->string('title');
    $table->text('message');
    $table->string('type', 20)->default('info');
    $table->string('status', 20)->default('draft');
    $table->timestamp('scheduled_at')->nullable();
    $table->string('link')->nullable();
    $table->json('target_roles')->nullable();
    $table->timestamps();
    $table->index('created_by');
    $table->index('created_at');
    $table->index('status');
});
```

### 6.12 Route Definition

```php
// routes/web/sysadmin.php:48-50
Route::get('/admin/announcements', AnnouncementManager::class)
    ->name('sysadmin.announcements')
    ->middleware(['auth', 'role:super_admin|admin']);
```

### 6.13 Scheduler Registration

```php
// routes/console.php:17-19
Schedule::command('announcements:publish')
    ->everyMinute()
    ->description('Publish scheduled announcements whose scheduled_at has passed');
```

---

## 7. Design Decisions

### DD-1 — Role-Targeted Delivery Over Broadcast to All

**Decision:** Announcements can target specific roles (students, teachers, supervisors) or all users,
with the sender's own roles automatically excluded from recipients.
**Rationale:** A teacher creating a "homework reminder" should not receive their own notification. A
supervisor-specific policy update should not clutter students' notification center. Role-targeting
reduces notification fatigue and increases relevance.
**Trade-off:** The role-exclusion query adds a `whereDoesntHave` clause to the notification
recipient query. This is a lightweight join on the `model_has_roles` table, negligible for
Indonesian vocational schools with hundreds (not thousands) of users.

### DD-2 — Three-State Lifecycle (Draft → Scheduled → Published) Without Expiry

**Decision:** Announcements have three states (`draft`, `scheduled`, `published`) with `PUBLISHED`
as a terminal state. There is no `expired` or `archived` state, and no unpublishing.
**Rationale:** Published announcements are delivered as notifications. Once sent, they exist in each
user's notification center independently of the announcement record. Adding expiry would require
retroactively removing or hiding notifications — a complex, user-confusing behavior for minimal gain.
**Trade-off:** Old announcements remain in the list indefinitely. Acceptable for a single-tenant
school system where announcement volume is low (tens per year, not thousands).

### DD-3 — Scheduled Publishing via Per-Minute Cron Over Event/Listener

**Decision:** Scheduled announcements are published by an artisan command (`announcements:publish`)
running every minute via the Laravel scheduler, rather than using a delayed job or event listener.
**Rationale:** A per-minute cron is simple, debuggable, and idempotent — re-running picks up any
missed announcements. Delayed jobs (`Bus::delay()`) would require a queue worker running
continuously and provide no visibility into what's pending. Event listeners would require storing
delay metadata and cannot be inspected or retried manually.
**Trade-off:** Up to 60-second latency between `scheduled_at` time and actual publication. For
school announcements, this latency is acceptable. A `pendingPublish()` scope also allows manual
triggering if needed.

### DD-4 — No Dedicated Policy Class

**Decision:** Announcement access control is enforced via route middleware (`role:super_admin|admin`)
and component-level authorization (`$this->authorize('viewAny', User::class)` in `boot()`),
with ownership scoping at the query level (`where('created_by', Auth::id())`). There is no
dedicated `AnnouncementPolicy` class.
**Rationale:** The access model is simple — admins create and manage only their own announcements.
Route middleware handles role gating. Query scoping handles ownership. A policy class would add
boilerplate without additional protection for this simple model.
**Trade-off:** If announcement management expands (e.g., super_admin managing all announcements),
a policy will need to be extracted. The current approach is sufficient for the defined scope.

### DD-5 — Markdown-Only Content Over Rich Text Editor

**Decision:** Announcement messages use Markdown formatting rendered via `Str::markdown()` with
HTML sanitization (`html_input => strip`, `allow_unsafe_links => false`).
**Rationale:** Markdown is lightweight, portable, and sufficient for announcement text. A rich text
editor (TinyMCE, Trix) would add significant JS bundle size, XSS surface area, and complexity
for minimal benefit. The markdown editor component provides a preview, balancing simplicity with
usability.
**Trade-off:** Non-technical admins may find Markdown unfamiliar. The guide component and hint text
mitigate this. If Markdown adoption proves problematic, the `Str::markdown()` call can be swapped
for a rich text renderer without changing the data model.

---

## 8. Success Metrics

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Scheduled announcement publish latency | < 60 seconds past `scheduled_at` | `announcements:publish` runs every minute |
| Notification dispatch (non-blocking) | < 100ms admin request time | `ShouldQueue` on `AnnouncementNotification` |
| Announcement list page load | < 500ms | Paginated query with index on `created_by` |

### Coverage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Notification channel coverage | 3 channels (mail, broadcast, database) | `AnnouncementNotification::via()` |
| Role targeting accuracy | 0 self-notifications | Role exclusion in `sendNotifications()` |
| Scheduled publish reliability | 100% of due announcements published per cron cycle | Command output count matches query count |

### Reliability

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Transaction atomicity | State change + notification dispatch in single transaction | `SendAnnouncementAction`, `PublishAnnouncementAction` |
| Status transition enforcement | Invalid transitions rejected | `AnnouncementStatus::canTransitionTo()` |
| Ownership isolation | 0 cross-admin announcement visibility | `where('created_by', Auth::id())` scoping |

### Negative Metrics (What Should NOT Happen)

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Self-notification from own announcement | 0 incidents | Sender roles excluded from recipient query |
| Past-due scheduled announcement missed | 0 | `pendingPublish()` scope + per-minute cron |
| Unsanitized HTML in rendered announcement | 0 | `Str::markdown()` with `html_input => strip` |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `BaseEntity`, `BaseModel`, `StatusEnum` contract, `LabelEnum` contract |
| [rbac-and-authorization.md](rbac-and-authorization.md) | `role:` middleware, role-based access gating on routes |
| [notification-infrastructure.md](notification-infrastructure.md) | `CustomDatabaseChannel`, `SendsNotifications` contract, `AnnouncementNotification` dispatch backbone |

### Build Guide

After implementing this spec, the announcement system provides admins with a complete create→schedule→publish workflow with role-targeted notification delivery. `SendAnnouncementAction` is the entry point for creating announcements; `PublishAnnouncementAction` handles both manual and scheduled publishing; `PublishScheduledAnnouncementsCommand` ensures scheduled announcements are published within 60 seconds of their target time. The next step is to add announcement display on dashboards and login pages for greater visibility beyond the notification channel.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [dashboard.md](dashboard.md) | Recent announcements could appear as dashboard widgets for each role |

---

## Quick References

- `app/SysAdmin/Announcement/Models/Announcement.php` — Eloquent model with `#[Fillable]`, scopes, and entity bridge (93 lines)
- `app/SysAdmin/Announcement/Enums/AnnouncementStatus.php` — Three-state lifecycle enum implementing `StatusEnum` (58 lines)
- `app/SysAdmin/Announcement/Entities/AnnouncementState.php` — `final readonly` entity with `fromModel()` and `isPendingPublish()` (52 lines)
- `app/SysAdmin/Announcement/Actions/SendAnnouncementAction.php` — Create + conditional notification dispatch (81 lines)
- `app/SysAdmin/Announcement/Actions/PublishAnnouncementAction.php` — Status transition + notification dispatch (46 lines)
- `app/SysAdmin/Announcement/Actions/DeleteAnnouncementAction.php` — Transactional deletion with audit log (22 lines)
- `app/SysAdmin/Announcement/Livewire/AnnouncementManager.php` — Admin management UI with confirm actions (135 lines)
- `app/SysAdmin/Announcement/Livewire/Forms/AnnouncementForm.php` — Form object with validation rules (59 lines)
- `app/SysAdmin/Announcement/Notifications/AnnouncementNotification.php` — Multi-channel notification class (59 lines)
- `app/SysAdmin/Announcement/Console/Commands/PublishScheduledAnnouncementsCommand.php` — Cron command for scheduled publishing (45 lines)
- `database/migrations/2026_01_01_000007_create_announcements_table.php` — Table schema with indexes (39 lines)
- `database/factories/AnnouncementFactory.php` — Test factory (28 lines)
- `resources/views/sysadmin/announcement/announcement-manager.blade.php` — Management UI Blade template (136 lines)
- `resources/views/sysadmin/announcement/components/announcement-guide.blade.php` — Help guide overlay (67 lines)
- `routes/web/sysadmin.php:48-50` — Route definition with auth + role middleware
- `routes/console.php:17-19` — Scheduler registration (every minute)
- `lang/en/announcement.php` — English translation strings (52 lines)
- **Related spec:** [notification-infrastructure.md](notification-infrastructure.md) — `CustomDatabaseChannel`, `AnnouncementNotification` dispatch backbone
- **Related spec:** [rbac-and-authorization.md](rbac-and-authorization.md) — Role middleware and access gating
