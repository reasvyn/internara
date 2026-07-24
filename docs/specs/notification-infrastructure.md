# Notification Infrastructure — Cross-Module In-App & Multi-Channel Notifications

> **Last updated:** 2026-07-24 **Changes:** feat — initial spec documenting the notification
> infrastructure: custom database channel, notification center UI, bell badge, unread count caching,
> event-driven cache invalidation, and cross-module notification dispatch

## Description

Specification of Internara's notification infrastructure: a custom database channel that persists
in-app notifications via the `SendsNotifications` contract, a `NotificationCenter` Livewire
component with read/delete/batch operations, a `NotificationBell` with cached unread counts,
event-driven cache invalidation, and a library of cross-module notification classes used by auth,
programs, assignments, incidents, announcements, and backup subsystems.

---

## 1. Problem Statements

### PS-1 — Cross-Module Notification Without a Unified Channel

Every module (Auth, Program, Assignment, Incident, SysAdmin) needs to notify users of important
events — registration updates, assignment publications, incident reports, announcements. Without a
shared notification channel, each module would implement its own persistence and delivery, leading
to duplicated logic, inconsistent schemas, and no single place for users to view notifications.

### PS-2 — No Centralized Notification View

Users need a single location to see all notifications regardless of which module produced them. A
notification bell in the header and a full notification center page provide both a quick glance
(small badge count) and a detailed view (list with read/unread filtering, search, batch operations).

### PS-3 — Unread Count Performance on Every Page Load

The notification bell appears on every page. Without caching, every page load triggers a
`SELECT COUNT(*)` query against the `notifications` table filtered by `user_id` and `is_read = false`.
For users with hundreds of notifications, this adds unnecessary latency on every navigation.

### PS-4 — Cache Invalidation on Notification State Changes

When a notification is sent, read, or deleted, the unread count cache must be invalidated
immediately. If the cache is stale, the bell badge shows incorrect counts, confusing users about
their actual unread notification count.

### PS-5 — Laravel's Default Database Notification Mismatch

Laravel's built-in `database` channel stores notifications in a `notifications` table with a
`type` column containing a fully-qualified class name and a `data` JSON blob. This schema does not
support structured `type`/`title`/`message`/`link` fields needed for a user-friendly notification
center. A custom channel is required to persist notifications with the right shape.

---

## 2. Goals & Non-Goals

### Goals

| ID  | Goal |
| --- | ---- |
| G1  | Provide a custom database notification channel that persists structured notifications (type, title, message, data, link) |
| G2  | Define a `SendsNotifications` contract so the channel delegates persistence to a Command Action |
| G3  | Provide a `NotificationCenter` Livewire component with paginated list, search, filter by read/unread, mark-as-read, mark-all-as-read, batch operations, and delete |
| G4  | Provide a `NotificationBell` Livewire component showing unread count with cached queries and event-driven invalidation |
| G5  | Emit `NotificationSent` and `NotificationRead` events to trigger cache invalidation via `ClearUnreadNotificationCache` listener |
| G6  | Provide a `NotificationPolicy` enforcing ownership-based authorization for view/update and admin-only for create/delete |
| G7  | Provide reusable notification classes across modules: `WelcomeNotification`, `GeneralNotification`, `AssignmentNotification`, `RegistrationNotification`, `IncidentReportedNotification`, `AnnouncementNotification`, `AccountStatusNotification`, `ActivationCodeNotification`, `SuperAdminRecoveredNotification`, `CredentialChangedNotification` |
| G8  | Cache unread count via `Cache::remember()` with 60-second TTL and registered cache key in `config/cache-keys.php` |

### Non-Goals

| ID   | Non-Goal |
| ---- | -------- |
| NG1  | Real-time push notifications via WebSocket or Server-Sent Events — broadcast channel exists but is not wired to a frontend listener |
| NG2  | User-configurable notification preferences per type (e.g., mute "announcement" notifications) |
| NG3  | Notification grouping, threads, or conversation-style views |
| NG4  | Email delivery management (retry, bounce handling) — relies on Laravel's mail driver |
| NG5  | Push notifications to mobile devices |
| NG6  | Notification templates or admin-configurable notification content |

---

## 3. User Stories / Use Cases

### UC-1 — Student Views Notification Center

**Actor:** Student
**Preconditions:** User authenticated, has at least one notification
**Flow:**
1. Student clicks the notification bell in the header
2. `NotificationBell` component shows unread count badge
3. Student navigates to notification center page
4. `NotificationCenter` mounts, calls `query()` → `Notification::where('user_id', Auth::id())`
5. Notifications displayed in paginated table with title and received date
6. Student clicks a notification row → `viewNotification($id)` marks it as read and opens viewer
7. `MarkAsReadAction` updates `is_read` and `read_at`, dispatches `NotificationRead` event
8. `ClearUnreadNotificationCache` listener invalidates `notification_unread:{userId}` cache
9. `NotificationBell` receives `notification-read` Livewire event, refreshes unread count
**Postconditions:** Notification marked as read, bell count decremented, notification detail shown

### UC-2 — Admin Marks All Notifications as Read

**Actor:** Admin
**Preconditions:** User authenticated with admin role, has unread notifications
**Flow:**
1. Admin opens notification center and clicks "Mark All as Read"
2. `markAllAsRead()` calls `MarkAllAsReadAction::execute(Auth::id())`
3. Action batch-updates all unread notifications for the user: `is_read = true`, `read_at = now()`
4. Action calls `Cache::forget(config('cache-keys.notification_unread') . $userId)`
5. Livewire dispatches `notifications-read` event
6. `NotificationBell` listener refreshes unread count
**Postconditions:** All notifications marked read, cache cleared, bell shows 0

### UC-3 — Admin Batch-Deletes Selected Notifications

**Actor:** Admin
**Preconditions:** User authenticated with admin role, has selected notifications in the center
**Flow:**
1. Admin selects multiple notifications via checkboxes
2. Admin clicks delete → `askDeleteSelected()` shows confirmation modal
3. Admin confirms → `confirmAction(DeleteNotificationAction)` calls `performBulkAction()`
4. For each selected ID: `DeleteNotificationAction::execute($notification)` deletes and clears cache
**Postconditions:** Selected notifications removed, cache cleared

### UC-4 — System Sends Welcome Notification on First Login

**Actor:** System (event-driven)
**Preconditions:** User has just logged in for the first time (`first_login_at` is null)
**Flow:**
1. `LoginSucceeded` event dispatched after successful authentication
2. `SendRoleWelcomeNotification` listener receives event
3. Listener checks `$user->first_login_at !== null` → returns if not first login
4. Listener resolves role from `$roleWelcomeMap` and calls `SendsNotifications::execute()`
5. `SendNotificationAction` creates `Notification` record, emits `NotificationSent`
6. `ClearUnreadNotificationCache` invalidates unread cache
**Postconditions:** Welcome notification persisted, unread cache invalidated

### UC-5 — System Sends Multi-Channel Notification for Assignment Publication

**Actor:** System (triggered by teacher creating assignment)
**Preconditions:** Teacher published an assignment, students are enrolled
**Flow:**
1. Assignment creation triggers `AssignmentNotification` dispatch to enrolled students
2. Notification's `via()` returns `['mail', 'broadcast', CustomDatabaseChannel::class]`
3. `CustomDatabaseChannel::send()` calls `$notification->toCustomDatabase($notifiable)`
4. Channel validates `type` and `title` keys exist, logs warnings if missing
5. Channel calls `SendsNotifications::execute()` → `SendNotificationAction`
6. In-app notification persisted; mail and broadcast dispatched in parallel
**Postconditions:** Students receive in-app notification, email, and broadcast

### UC-6 — User Receives Incident Report Notification

**Actor:** System (triggered by student reporting incident)
**Preconditions:** Student submitted an incident report
**Flow:**
1. `IncidentReportedNotification` dispatched to relevant supervisors/admins
2. `via()` returns `['mail', 'broadcast', CustomDatabaseChannel::class]`
3. `toCustomDatabase()` returns structured data with `type: 'incident_reported'`, severity, description
4. Database notification persisted with `data` JSON containing `incident_id` and `severity`
**Postconditions:** Supervisors see incident notification in their notification center

---

## 4. Functional Requirements

### Custom Database Channel

| ID   | Requirement |
| ---- | ----------- |
| FR-C1 | `CustomDatabaseChannel` must accept a `SendsNotifications` instance via constructor injection |
| FR-C2 | `CustomDatabaseChannel::send()` must extract the notifiable's primary key via `$notifiable->getKey()` or `$notifiable->id` |
| FR-C3 | `CustomDatabaseChannel::send()` must silently return if the notifiable has no valid user ID (null or empty) |
| FR-C4 | `CustomDatabaseChannel::send()` must call `$notification->toCustomDatabase($notifiable)` to get structured data |
| FR-C5 | `CustomDatabaseChannel::send()` must log a warning via `SmartLogger` if `type` or `title` keys are missing from the returned data |
| FR-C6 | `CustomDatabaseChannel::send()` must delegate to `SendsNotifications::execute()` with `userId`, `type`, `title`, `message`, `data`, and `link` |
| FR-C7 | All notification classes must implement `toCustomDatabase($notifiable)` returning an array with keys: `type` (string), `title` (string), `message` (?string), `link` (?string), `data` (?array) |

### SendsNotifications Contract

| ID   | Requirement |
| ---- | ----------- |
| FR-S1 | `SendsNotifications` interface must define `execute(string $userId, string $type, string $title, ?string $message, ?array $data, ?string $link): mixed` |
| FR-S2 | `SendNotificationAction` must implement `SendsNotifications` and extend `BaseCommandAction` |
| FR-S3 | `SendNotificationAction::execute()` must validate `userId`, `type` (max 50), and `title` (max 255) via `Validator` |
| FR-S4 | `SendNotificationAction::execute()` must find the `User` by ID or throw `ModelNotFoundException` |
| FR-S5 | `SendNotificationAction::execute()` must create a `Notification` record within a transaction and emit `NotificationSent` event |
| FR-S6 | `SendNotificationAction::execute()` must log the send via `$this->log('notification_sent', ...)` |

### Notification Model

| ID   | Requirement |
| ---- | ----------- |
| FR-M1 | `Notification` model must use `#[Fillable]` attribute with: `user_id`, `type`, `title`, `message`, `data`, `link`, `is_read`, `read_at` |
| FR-M2 | `Notification` model must cast `data` → `array`, `is_read` → `boolean`, `read_at` → `datetime` |
| FR-M3 | `Notification` model must define `user(): BelongsTo` relationship to `User` |
| FR-M4 | `notifications` table must have a composite index on `[user_id, is_read]` |
| FR-M5 | `notifications` table `user_id` foreign key must use `onDelete('cascade')` |

### Notification Center UI

| ID   | Requirement |
| ---- | ----------- |
| FR-U1 | `NotificationCenter` must extend `BaseRecordManager` and render `user.notifications.notification-center` |
| FR-U2 | `NotificationCenter::query()` must return `Notification::where('user_id', Auth::id())` |
| FR-U3 | `NotificationCenter::headers()` must return columns: `title` (label from `__('notifications.ui.message_col')`), `created_at` (sortable, hidden on small screens), `actions` |
| FR-U4 | `NotificationCenter::applySearch()` must search across `title` and `message` fields using `LIKE` |
| FR-U5 | `NotificationCenter::applyFilters()` must support `status` filter: `unread` → `is_read = false`, `read` → `is_read = true` |
| FR-U6 | `NotificationCenter::viewNotification($id)` must find notification by user ownership, mark as read if unread, dispatch `notification-read` Livewire event, and show viewer |
| FR-U7 | `NotificationCenter::markAsRead($id)` must delegate to `MarkAsReadAction` and dispatch `notification-read` |
| FR-U8 | `NotificationCenter::markAllAsRead()` must delegate to `MarkAllAsReadAction`, flash success message, and dispatch `notifications-read` |
| FR-U9 | `NotificationCenter::markSelectedAsRead()` must delegate to `MarkBatchAsReadAction` with selected IDs, clear selection, and dispatch `notifications-read` |
| FR-U10 | `NotificationCenter::confirmAction()` must use `performBulkAction()` for batch delete, catching `RejectedException` |

### Notification Bell

| ID   | Requirement |
| ---- | ----------- |
| FR-B1 | `NotificationBell` must extend `Component`, maintain `int $unreadCount` property |
| FR-B2 | `NotificationBell::mount()` must call `updateUnreadCount()` |
| FR-B3 | `NotificationBell::updateUnreadCount()` must use `Cache::remember()` with key `config('cache-keys.notification_unread') . $userId` and 60-second TTL |
| FR-B4 | `NotificationBell::updateUnreadCount()` must return 0 if user is not authenticated |
| FR-B5 | `NotificationBell::getListeners()` must register `notification-read` and `notifications-read` events mapped to `updateUnreadCount` |

### Mark Actions

| ID   | Requirement |
| ---- | ----------- |
| FR-A1 | `MarkAsReadAction::execute(Notification)` must set `is_read = true` and `read_at = now()` only if currently unread, emit `NotificationRead` event, and return fresh model |
| FR-A2 | `MarkAllAsReadAction::execute(string $userId)` must batch-update all unread notifications for the user and call `Cache::forget()` on the unread cache key |
| FR-A3 | `MarkBatchAsReadAction::execute(string $userId, array $ids)` must update only notifications matching both `id IN $ids` and `user_id`, and call `Cache::forget()` on the unread cache key |
| FR-A4 | `DeleteNotificationAction::execute(Notification)` must delete the notification within a transaction and call `Cache::forget()` on the unread cache key |

### Events & Listeners

| ID   | Requirement |
| ---- | ----------- |
| FR-E1 | `NotificationSent` event must extend `BaseEvent`, carry the `Notification` model, and expose `eventName()` → `'notification.sent'` |
| FR-E2 | `NotificationRead` event must extend `BaseEvent`, carry the `Notification` model, and expose `eventName()` → `'notification.read'` |
| FR-E3 | `ClearUnreadNotificationCache` listener must handle `NotificationSent`, `NotificationRead`, and `ProfileUpdated` events |
| FR-E4 | `ClearUnreadNotificationCache` must extract `user_id` from the event and call `Cache::forget(config('cache-keys.notification_unread') . $userId)` |

### Authorization

| ID   | Requirement |
| ---- | ----------- |
| FR-P1 | `NotificationPolicy::viewAny()` must return `true` for all authenticated users |
| FR-P2 | `NotificationPolicy::view()` must check `$notification->user_id === $user->id` |
| FR-P3 | `NotificationPolicy::create()` must check `$this->isAdmin($user)` |
| FR-P4 | `NotificationPolicy::update()` must check `$notification->user_id === $user->id` |
| FR-P5 | `NotificationPolicy::delete()` must check `$this->isAdmin($user)` |

### Notification Classes by Module

| ID   | Requirement |
| ---- | ----------- |
| FR-N1 | `WelcomeNotification` must use channels `['mail', 'broadcast', CustomDatabaseChannel::class]`, implement `ShouldQueue`, and include `toCustomDatabase()` with `type: 'system_welcome'` |
| FR-N2 | `GeneralNotification` must accept `$type`, `$title`, `$message`, `$link`, `$data`, `$sendEmail` and conditionally include `'mail'` channel based on `$sendEmail` flag |
| FR-N3 | `AssignmentNotification` must use channels `['mail', 'broadcast', CustomDatabaseChannel::class]`, implement `ShouldQueue`, and include assignment title and due date in `toCustomDatabase()` |
| FR-N4 | `RegistrationNotification` must use channels `['mail', 'broadcast', CustomDatabaseChannel::class]`, implement `ShouldQueue`, and include internship name and status in `toCustomDatabase()` |
| FR-N5 | `IncidentReportedNotification` must use channels `['mail', 'broadcast', CustomDatabaseChannel::class]`, implement `ShouldQueue`, and include incident severity and ID in `toCustomDatabase()` |
| FR-N6 | `AnnouncementNotification` must use channels `['mail', 'broadcast', CustomDatabaseChannel::class]`, implement `ShouldQueue`, and include announcement title and message in `toCustomDatabase()` |
| FR-N7 | `AccountStatusNotification` must use channels `['mail', 'broadcast', CustomDatabaseChannel::class]`, implement `ShouldQueue`, and include account status and optional reason in `toCustomDatabase()` |
| FR-N8 | `ActivationCodeNotification` must use channels `['mail', CustomDatabaseChannel::class]` (no broadcast), and include activation code info in `toCustomDatabase()` |
| FR-N9 | `CredentialChangedNotification` must use channel `['mail']` only (no database channel), and send a security warning email with optional support email |
| FR-N10 | `SuperAdminRecoveredNotification` must use channels `['mail', 'broadcast', CustomDatabaseChannel::class]`, implement `ShouldQueue`, and include recovered email in `toCustomDatabase()` |
| FR-N11 | `RecoveryOtpNotification` must use channel `['mail']` only (no database channel) and send the OTP code |
| FR-N12 | `TestMailNotification` must use channel `['mail']` only (no database channel) for settings test email |

---

## 5. Non-Functional Requirements

| ID    | Requirement |
| ----- | ----------- |
| NFR-P1 | `NotificationBell` unread count must be served from cache in < 10ms on cache hit (60s TTL) |
| NFR-P2 | `NotificationCenter` page load must complete in < 500ms for users with up to 1000 notifications |
| NFR-P3 | Unread count cache must be invalidated within one request cycle of notification state change |
| NFR-S1 | Users can only view/update their own notifications — enforced by `NotificationPolicy` and `WHERE user_id = Auth::id()` query scoping |
| NFR-S2 | Admin-only create/delete enforced by `NotificationPolicy::create()` and `NotificationPolicy::delete()` via `isAdmin()` check |
| NFR-S3 | `CustomDatabaseChannel` must silently skip delivery if notifiable has no valid user ID — no exception thrown |
| NFR-S4 | Notification `type` field is capped at 50 characters to prevent unbounded storage |
| NFR-R1 | `NotificationCenter` must handle empty notification lists gracefully — render empty state |
| NFR-R2 | `NotificationBell` must default to 0 unread count if `Auth::id()` returns null |
| NFR-R3 | `MarkAsReadAction` must be idempotent — marking an already-read notification is a no-op |
| NFR-U1 | All notification UI labels must use `__()` translation helper with keys from `notifications.*` namespace |
| NFR-U2 | Notification bell must be visible on every authenticated page via the app layout |
| NFR-U3 | `NotificationCenter` must support keyboard navigation for list items and action buttons |
| NFR-M1 | All notification classes must implement `toCustomDatabase()` returning the standard array contract (→ DD-1) |
| NFR-M2 | All notification classes must use `declare(strict_types=1)` |
| NFR-M3 | Unread count cache key must be registered in `config/cache-keys.php` as `notification_unread` — no ad-hoc key strings |
| NFR-M4 | Cross-module notification dispatch must use either `SendsNotifications::execute()` directly or `$user->notify()` with a class implementing `toCustomDatabase()` |

---

## 6. API / Data Contracts

### 6.1 SendsNotifications Interface

```php
// app/Core/Contracts/SendsNotifications.php
interface SendsNotifications
{
    public function execute(
        string $userId,
        string $type,
        string $title,
        ?string $message = null,
        ?array $data = null,
        ?string $link = null,
    ): mixed;
}
```

### 6.2 CustomDatabaseChannel

```php
// app/Core/Channels/CustomDatabaseChannel.php (55 lines)
class CustomDatabaseChannel
{
    public function __construct(protected readonly SendsNotifications $sendNotification) {}
    public function send(mixed $notifiable, Notification $notification): void;
    // Calls $notification->toCustomDatabase($notifiable)
    // Validates type/title keys, logs warnings if missing via SmartLogger
    // Delegates to SendsNotifications::execute()
}
```

### 6.3 SendNotificationAction

```php
// app/User/Notifications/Actions/SendNotificationAction.php (77 lines)
final class SendNotificationAction extends BaseCommandAction implements SendsNotifications
{
    public function execute(
        string $userId, string $type, string $title,
        ?string $message = null, ?array $data = null, ?string $link = null,
    ): Notification;
    // Validates via NotificationData DTO and Validator
    // Creates Notification within transaction
    // Logs 'notification_sent', emits NotificationSent event
}
```

### 6.4 NotificationData DTO

```php
// app/User/Notifications/Data/NotificationData.php (19 lines)
final readonly class NotificationData extends BaseData
{
    public function __construct(
        public string $userId,
        public string $type,
        public string $title,
        public ?string $message = null,
        public ?array $data = null,
        public ?string $link = null,
    ) {}
}
```

### 6.5 Notification Model

```php
// app/User/Notifications/Models/Notification.php (33 lines)
#[Fillable(['user_id', 'type', 'title', 'message', 'data', 'link', 'is_read', 'read_at'])]
class Notification extends BaseModel
{
    // Casts: data → array, is_read → boolean, read_at → datetime
    public function user(): BelongsTo;
}
```

### 6.6 notifications Table Schema

```php
// database/migrations/2026_01_01_000004_create_notifications_table.php
Schema::create('notifications', function (Blueprint $table) {
    $table->uuid('id')->primary();
    $table->foreignUuid('user_id')->constrained('users', 'id')->onDelete('cascade');
    $table->string('type', 50);
    $table->string('title');
    $table->text('message')->nullable();
    $table->json('data')->nullable();
    $table->string('link')->nullable();
    $table->boolean('is_read')->default(false);
    $table->timestamp('read_at')->nullable();
    $table->timestamps();
    $table->index(['user_id', 'is_read']);
});
```

### 6.7 Mark Actions

```php
// app/User/Notifications/Actions/MarkAsReadAction.php (40 lines)
final class MarkAsReadAction extends BaseCommandAction
{
    public function execute(Notification $notification): Notification;
    // Sets is_read=true, read_at=now() if unread; emits NotificationRead; returns fresh model
}

// app/User/Notifications/Actions/MarkAllAsReadAction.php (37 lines)
final class MarkAllAsReadAction extends BaseCommandAction
{
    public function execute(string $userId): int;
    // Batch-updates all unread for user; clears unread cache; returns updated count
}

// app/User/Notifications/Actions/MarkBatchAsReadAction.php (32 lines)
final class MarkBatchAsReadAction extends BaseCommandAction
{
    public function execute(string $userId, array $ids): int;
    // Updates unread notifications matching ids+userId; clears unread cache; returns count
}

// app/User/Notifications/Actions/DeleteNotificationAction.php (33 lines)
final class DeleteNotificationAction extends BaseCommandAction
{
    public function execute(Notification $notification): void;
    // Deletes within transaction; clears unread cache
}
```

### 6.8 Events

```php
// app/User/Notifications/Events/NotificationSent.php
final class NotificationSent extends BaseEvent
{
    public function __construct(public Notification $notification) {}
    public function eventName(): string { return 'notification.sent'; }
}

// app/User/Notifications/Events/NotificationRead.php
final class NotificationRead extends BaseEvent
{
    public function __construct(public Notification $notification) {}
    public function eventName(): string { return 'notification.read'; }
}
```

### 6.9 ClearUnreadNotificationCache Listener

```php
// app/User/Notifications/Listeners/ClearUnreadNotificationCache.php (25 lines)
final class ClearUnreadNotificationCache
{
    public function handle(NotificationSent|NotificationRead|ProfileUpdated $event): void;
    // Extracts user_id from event, calls Cache::forget() on notification_unread key
}
```

### 6.10 NotificationPolicy

```php
// app/User/Notifications/Policies/NotificationPolicy.php (37 lines)
class NotificationPolicy extends BasePolicy
{
    public function viewAny(User $user): bool;          // true
    public function view(User $user, Notification $n): bool; // ownership check
    public function create(User $user): bool;           // admin only
    public function update(User $user, Notification $n): bool; // ownership check
    public function delete(User $user, Notification $n): bool; // admin only
}
```

### 6.11 Cache Keys

```php
// config/cache-keys.php
'notification_unread' => 'notification.unread:',  // Suffix: userId → notification.unread:{userId}
```

### 6.12 Notification Classes Summary

| Class | Channels | Queueable | Module | Type Key |
|-------|----------|-----------|--------|----------|
| `WelcomeNotification` | mail, broadcast, custom_database | Yes | User | `system_welcome` |
| `GeneralNotification` | custom_database (+mail if $sendEmail) | Yes | User | configurable |
| `AssignmentNotification` | mail, broadcast, custom_database | Yes | Assignment | `assignment_published` |
| `SubmissionFeedbackNotification` | mail, broadcast, custom_database | Yes | Assignment | `submission_feedback` |
| `RegistrationNotification` | mail, broadcast, custom_database | Yes | Program | `internship_registration_update` |
| `InternshipCreatedNotification` | mail, broadcast, custom_database | Yes | Program | `internship_created` |
| `IncidentReportedNotification` | mail, broadcast, custom_database | Yes | Incident | `incident_reported` |
| `AnnouncementNotification` | mail, broadcast, custom_database | Yes | SysAdmin | `announcement` |
| `AccountStatusNotification` | mail, broadcast, custom_database | Yes | User | `account_status_change` |
| `ActivationCodeNotification` | mail, custom_database | No | User | `activation_code` |
| `SuperAdminRecoveredNotification` | mail, broadcast, custom_database | Yes | Auth | `super_admin_recovery` |
| `CredentialChangedNotification` | mail | No | Auth | N/A (email only) |
| `RecoveryOtpNotification` | mail | Yes | Auth | N/A (email only) |
| `TestMailNotification` | mail | No | Settings | N/A (email only) |

### 6.13 Notification-to-Database `toCustomDatabase()` Contract

Every notification class that uses `CustomDatabaseChannel` must implement `toCustomDatabase($notifiable)` returning:

```php
[
    'type'    => string,     // Required. Categorical key, max 50 chars (e.g., 'assignment_published')
    'title'   => string,     // Required. Human-readable title
    'message' => ?string,    // Optional. Detailed message body
    'link'    => ?string,    // Optional. Deep link to relevant page
    'data'    => ?array,     // Optional. Arbitrary structured payload (e.g., IDs, metadata)
]
```

---

## 7. Design Decisions

### DD-1 — Custom Database Channel Over Laravel's Default

**Decision:** Use a custom `CustomDatabaseChannel` class instead of Laravel's built-in `database` channel.
**Rationale:** Laravel's `database` channel stores a `type` (FQCN) and a `data` JSON blob in the
`notifications` table. The notification center UI needs structured `type`, `title`, `message`, and
`link` columns for efficient querying, filtering, and display. The custom channel maps the
`toCustomDatabase()` array to these columns via `SendNotificationAction`.
**Trade-off:** Extra class (55 lines) and a custom contract (`SendsNotifications`). But the
notification center UI gains structured queries (`WHERE type = ...`, `WHERE title LIKE ...`) that
the default JSON blob approach cannot support efficiently. The `BackupFailedNotification` is the
only class still using Laravel's native `database` channel — it does not need the structured fields.

### DD-2 — Cache::remember for Unread Count with 60s TTL

**Decision:** Cache unread count via `Cache::remember()` with a 60-second TTL, invalidated on
every notification state change.
**Rationale:** The notification bell is rendered on every page. A 60s TTL ensures the count is
never more than 60 seconds stale, while avoiding redundant queries on rapid page navigation.
Event-driven invalidation via `ClearUnreadNotificationCache` ensures freshness on send/read/delete.
**Trade-off:** If the cache driver is restarted between invalidation and the next page load, the
count may briefly show 0. Acceptable — the next notification event will repopulate it.

### DD-3 — Event-Driven Cache Invalidation Over Direct Cache Forget

**Decision:** Cache invalidation is triggered via `NotificationSent` and `NotificationRead` events
caught by `ClearUnreadNotificationCache`, rather than calling `Cache::forget()` directly in the
notification center Livewire component.
**Rationale:** Notifications are sent from many locations (listeners, actions, controllers). The
event-driven approach ensures the unread cache is always invalidated regardless of dispatch origin.
The `ClearUnreadNotificationCache` listener also handles `ProfileUpdated` to cover edge cases.
**Trade-off:** Extra event classes (2 × 18 lines) and a listener (25 lines). Mitigated by
centralizing invalidation logic and avoiding scattered `Cache::forget()` calls.

### DD-4 — SendsNotifications Contract Over Direct Model Access

**Decision:** `CustomDatabaseChannel` accepts a `SendsNotifications` interface via constructor
injection, rather than directly calling `Notification::create()`.
**Rationale:** The contract allows `SendNotificationAction` to handle validation, logging,
event dispatch, and transaction management in one place. The channel remains thin (55 lines)
and focused on mapping `toCustomDatabase()` data to the action's parameters.
**Trade-off:** Extra abstraction layer. But `SendNotificationAction` is the single place where
notification persistence, activity logging, and event dispatch happen — violating this would
scatter concerns across every notification class.

### DD-5 — Ownership-Based Authorization, Not Role-Based

**Decision:** `NotificationPolicy` uses ownership checks (`user_id === $user->id`) for view/update
and `isAdmin()` for create/delete, rather than role-based permissions.
**Rationale:** Notifications are per-user data. A student should see only their own notifications.
Admin create/delete is for system-generated notifications. This aligns with the principle of
least privilege — no role can view another user's notifications.
**Trade-off:** Super admins cannot view other users' notifications via the policy. This is
acceptable — admin notification management is not a current requirement.

### DD-6 — 60s TTL for Bell, Not 300s Like Dashboard

**Decision:** Notification bell unread count uses a 60-second TTL, not the 300-second TTL used
for dashboard statistics (→ dashboard.md DD-2).
**Rationale:** The unread count is a small, frequently-changing value (one query). Dashboard stats
involve 15+ complex aggregation queries. A 60s TTL balances freshness with performance for a
value that changes on every notification send/read/delete. The 5-minute dashboard TTL would make
the bell badge feel unresponsive.
**Trade-off:** More frequent cache misses than dashboard. Negligible — the count query is a
single indexed `COUNT(*)` on `[user_id, is_read]`.

---

## 8. Success Metrics

### Performance

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Bell unread count (cache hit) | < 10ms | `Cache::remember()` TTL 60s |
| Notification center page load | < 500ms | Paginated query with index on `[user_id, is_read]` |
| Cache invalidation latency | < 1 request cycle | Synchronous event listener |
| Cache hit rate (bell) | > 85% | 60s TTL covers rapid page navigation |

### Coverage

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Notification classes using `toCustomDatabase()` | 10 of 14 classes | All except CredentialChanged, RecoveryOtp, TestMail, BackupFailed |
| Cache key registration | 100% in `config/cache-keys.php` | `notification_unread` key present |
| Module notification coverage | Auth, User, Program, Assignment, Incident, SysAdmin | At least one notification class per module |

### Reliability

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Unread count accuracy | 99.9% | Event-driven invalidation + 60s TTL |
| Bell badge reflects state | Within 60s of any change | Cache TTL + event invalidation |
| Policy enforcement | 100% ownership check | `NotificationPolicy` enforced on all queries |

### Negative Metrics (What Should NOT Happen)

| Metric | Target | Measurement |
| ------ | ------ | ----------- |
| Cross-user notification leakage | 0 incidents | `WHERE user_id = Auth::id()` on all queries |
| Unbounded notification storage | None | No auto-cleanup in scope (→ NG6) |
| Stale bell count > 60s | 0 | Cache invalidation on every state change |

---

## 9. Roadmap

### Prerequisites

This spec can only be implemented after the following specs are **fully complete**:

| Spec | What It Provides |
|------|-----------------|
| [base-classes.md](base-classes.md) (#2) | `BaseCommandAction`, `BaseEvent`, `BaseData`, `BaseModel`, `BasePolicy` base classes |
| [logging-and-error-handling.md](logging-and-error-handling.md) (#6) | `SmartLogger` for notification event logging |
| [event-system.md](event-system.md) (#7) | `BaseEvent` contract, event dispatch and listener registration patterns |
| [authentication.md](authentication.md) (#17) | `User` model with `Notifiable` trait, authenticated user context, `LoginSucceeded` event |
| [rbac-and-authorization.md](rbac-and-authorization.md) (#8) | `isAdmin()` policy helper, role-based `create`/`delete` gate |
| [settings-infrastructure.md](settings-infrastructure.md) (#14) | `Settings::get()` used by `CredentialChangedNotification` for support email |

### Build Guide

After implementing this spec, the notification infrastructure is the cross-cutting backbone that every
module dispatches events through. `SendNotificationAction` is the single entry point for in-app
notifications; `CustomDatabaseChannel` bridges Laravel's notification system to the custom schema.
Notification classes live in each module's `Notifications/` directory and follow the `toCustomDatabase()`
contract. The next phase is to wire existing module actions (registration, assignment, incident) to
dispatch notifications through this infrastructure.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [registration.md](registration.md) | `RegistrationNotification` dispatches on registration status change |
| 2 | [assignment.md](assignment.md) | `AssignmentNotification` and `SubmissionFeedbackNotification` dispatch on assignment publish/grade |
| 3 | [incident.md](incident.md) | `IncidentReportedNotification` dispatches when students report incidents |
| 4 | [daily-activity.md](daily-activity.md) | Logbook submission notifications for supervisors |
| 5 | [user-crud-and-status.md](user-crud-and-status.md) | `ActivationCodeNotification` and `AccountStatusNotification` dispatch on user lifecycle |

---

## Quick References

- `app/Core/Contracts/SendsNotifications.php` — notification dispatch contract (17 lines)
- `app/Core/Channels/CustomDatabaseChannel.php` — custom channel bridging `toCustomDatabase()` to `SendsNotifications` (55 lines)
- `app/User/Notifications/Actions/SendNotificationAction.php` — core persistence action implementing `SendsNotifications` (77 lines)
- `app/User/Notifications/Actions/MarkAsReadAction.php` — single notification mark-as-read (40 lines)
- `app/User/Notifications/Actions/MarkAllAsReadAction.php` — bulk mark-all-as-read (37 lines)
- `app/User/Notifications/Actions/MarkBatchAsReadAction.php` — batch mark-selected-as-read (32 lines)
- `app/User/Notifications/Actions/DeleteNotificationAction.php` — single notification delete (33 lines)
- `app/User/Notifications/Models/Notification.php` — Eloquent model with `#[Fillable]` (33 lines)
- `app/User/Notifications/Data/NotificationData.php` — DTO for notification payload (19 lines)
- `app/User/Notifications/Events/NotificationSent.php` — event emitted on notification create (18 lines)
- `app/User/Notifications/Events/NotificationRead.php` — event emitted on notification read (18 lines)
- `app/User/Notifications/Listeners/ClearUnreadNotificationCache.php` — cache invalidation listener (25 lines)
- `app/User/Notifications/Livewire/NotificationCenter.php` — full notification center UI (153 lines)
- `app/User/Notifications/Livewire/NotificationBell.php` — header bell badge with cached count (53 lines)
- `app/User/Notifications/Policies/NotificationPolicy.php` — ownership-based authorization (37 lines)
- `app/User/Notifications/WelcomeNotification.php` — new user welcome (mail + broadcast + database) (73 lines)
- `app/User/Notifications/GeneralNotification.php` — configurable multi-purpose notification (61 lines)
- `app/User/Notifications/TestMailNotification.php` — settings test email (mail only) (26 lines)
- `app/Auth/Notifications/CredentialChangedNotification.php` — credential change security alert (mail only) (46 lines)
- `app/Auth/SuperAdmin/Notifications/SuperAdminRecoveredNotification.php` — admin recovery alert (67 lines)
- `app/Auth/SuperAdmin/Notifications/RecoveryOtpNotification.php` — OTP delivery (mail only) (33 lines)
- `app/Auth/Login/Listeners/SendRoleWelcomeNotification.php` — first-login welcome dispatch (48 lines)
- `app/User/UserManagement/Notifications/ActivationCodeNotification.php` — activation code delivery (42 lines)
- `app/User/AccountStatus/Notifications/AccountStatusNotification.php` — account status change alert (71 lines)
- `app/Assignment/Notifications/AssignmentNotification.php` — assignment publication alert (79 lines)
- `app/Assignment/Submission/Notifications/SubmissionFeedbackNotification.php` — grading feedback alert (87 lines)
- `app/Program/Notifications/RegistrationNotification.php` — registration status update (69 lines)
- `app/Program/Internship/Notifications/InternshipCreatedNotification.php` — new internship alert (66 lines)
- `app/Incident/IncidentReport/Notifications/IncidentReportedNotification.php` — incident report alert (75 lines)
- `app/SysAdmin/Announcement/Notifications/AnnouncementNotification.php` — announcement delivery (59 lines)
- `app/SysAdmin/Backups/Notifications/BackupFailedNotification.php` — backup failure alert (uses native `database` channel) (34 lines)
- `app/SysAdmin/Backups/Listeners/SendBackupFailedNotification.php` — backup failure dispatch (21 lines)
- `config/cache-keys.php` — `notification_unread` key registration
- `database/migrations/2026_01_01_000004_create_notifications_table.php` — notifications table schema
- **Related spec:** [authentication.md](authentication.md) — User model with `Notifiable` trait, login events
- **Related spec:** [dashboard.md](dashboard.md) — Dashboard bell integration, role-based welcome notifications
- **Related spec:** [base-classes.md](base-classes.md) (#2) — Base classes (`BaseCommandAction`, `BaseEvent`, `BaseData`)
