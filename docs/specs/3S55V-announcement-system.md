# Announcement System — Targeted Multi-Role Communication

> **Spec ID:** 3S55V
> **Status:** Full
> **Owner:** SysAdmin
> **Depends on:** YB22J, TXR2H

## Description

Admin-facing targeted communication for Internara: announcements with role targeting, a
draft-to-published lifecycle with scheduled publishing, a Livewire manager owned per creator,
and queued multi-channel delivery through the shared notification backbone to every user in the
selected roles.

---

## 1. Problem Statements

### PS-1 — No Mechanism for Targeted Admin Communication

Schedule changes, policy updates, and emergency notices need to reach exactly the right group —
students only, mentors only, everyone except the sender. Email alone misses users without
reliable mailboxes, chat groups have no audit trail, and a paper notice never proves who was
told what before an accreditation visit.
**→ Requirement:** FR-ANN-011 (role-targeted recipients), FR-ANN-013/014 (owned manager), UC-ANN-001.

### PS-2 — No Scheduled Publishing for Time-Sensitive Messages

An exam-schedule change written on Friday must appear Monday morning without anyone remembering
to click publish over the weekend. Manual timing fails exactly when it matters most, during
holidays and night shifts when no admin is watching the console.
**→ Requirement:** FR-ANN-010/019 (publish Action + per-minute command), NFR-ANN-001 (latency window).

### PS-3 — No Lifecycle Management for Announcement Content

Without a draft state every keystroke is a broadcast, which means typos, wrong target roles,
and premature sends reach eight hundred inboxes before review. Admins need a private staging
state plus a scheduled staging state before anything leaves the school office.
**→ Requirement:** FR-ANN-005/006 (status machine), FR-ANN-007/008 (validated creation), DD-ANN-002.

---

## 2. Goals & Non-Goals

### Goals

- **Role-targeted creation** — title, Markdown body, severity type, optional link, and target roles or send-to-all. *Why:* relevance beats volume; targeted notices get read while broadcasts get ignored.
- **Staged lifecycle** — draft, scheduled, and terminal published states with enforced transitions. *Why:* review and timing live in the system instead of in admins' memories.
- **Scheduled auto-publishing** — a per-minute command publishes whatever became due. *Why:* time-sensitive notices land on time without night-shift staffing.
- **Queued targeted delivery** — published rows fan out through the notification backbone without blocking the admin request. *Why:* a send to every student must feel instant to the sender.
- **Ownership-scoped management with admin gating** — admins manage only their own rows behind role middleware. *Why:* no cross-admin interference or accidental deletion of a colleague's message.
- **Localized, sanitized, logged publishing** — `__()` strings, Markdown rendered without unsafe HTML, SmartLogger entries with PII masking. *Why:* the same trust contract every global requirement demands.

### Non-Goals

- **Expiry or unpublishing.** *Why:* published rows already live as independent notification copies; retroactive removal would confuse recipients for no school-scale gain.
- **Per-user opt-out or preference matrices.** *Why:* post-MVP depth; announcements are official school communication, not a subscription.
- **Read tracking or analytics.** *Why:* opened and clicked telemetry is deferred; delivery plus audit logging suffices at MVP.
- **Attachments or embedded media.** *Why:* links cover the document case without upload, scanning, and storage burden.
- **Public banners outside the notification channel.** *Why:* login-page display is a separate concern; delivery stays in the backbone the dashboard already reads.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled here
because each story below has a code-testable consequence verified by the listed layer.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-ANN-001 | Admin composes a notice and publishes immediately to all users except sender roles | P0 | F | Full |
| UC-ANN-002 | Admin schedules a notice for a future time and the per-minute command publishes it without manual action | P0 | F | Full |
| UC-ANN-003 | Admin manually publishes a draft or scheduled row after a confirmation step | P0 | F | Full |
| UC-ANN-004 | Admin deletes an owned announcement after a confirmation step | P1 | F | Full |

### 3.1 Publishing Flows

#### UC-ANN-001 — Flood warning goes out before apel pagi

Monday at dawn brings a flooded access road, and the vice principal needs every student and
teacher to know before the morning assembly while sparing fellow admins the noise. She opens the
manager, writes a short warning with the detour link, leaves the send-to-all toggle on, and hits
send. The Action persists the row as published under her id, fans out through the backbone to
everyone outside her own roles, and flashes a translated confirmation. Students wake to the bell
lit with a row that deep-links nowhere fancy, just the plain message that saves a wasted trip.

#### UC-ANN-002 — Exam change written Friday, seen Monday

The curriculum deputy finishes the revised exam timetable Friday afternoon but the change takes
effect Monday at seven. She saves the notice as scheduled with a Monday 06:30 timestamp and goes
home. Over the weekend nothing is delivered, and the row sits visible in her manager as
scheduled. At 06:30 the per-minute command finds it due, transitions it to published, clears the
timestamp, and fans out to the targeted roles. By assembly time the notice is simply there, and
nobody had to remember a password or open a laptop on Sunday night.

### 3.2 Managing Published and Staged Rows

#### UC-ANN-003 — Draft rescue after a second read

If a draft saved in a hurry names the wrong internship batch, the author spots it on re-read
before anyone else ever sees the row. The publish icon beside the draft asks for confirmation,
re-checks the transition against the state machine, and only then flips the row to published and
fans out. A scheduled row whose date slipped gets the same treatment: one confirmation publishes
now instead of waiting for the timestamp. The confirmation exists because publishing is the
irreversible step in this subsystem, and the state check exists because the row may have changed
since the list last rendered.

#### UC-ANN-004 — Removing a superseded notice

When a newer timetable supersedes last week's draft that never went out, the author clears it
with the delete control. The modal names the row so a mis-click on a neighboring title cannot
destroy the wrong content, ownership is re-resolved before the delete runs, and the success flash
confirms removal. Published rows delete the announcement record while leaving already-delivered
notification copies in recipients' centers untouched, which matches the mental model that sent
mail cannot be unsent.

---

## 4. Functional Requirements

Global defaults from QLHDO and D2FT3 apply (localization, dual-layer authorization, Action Triad,
`RejectedException`, SmartLogger with PII masking). `Status` is `Full` for every row because this
spec is implemented and verified.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ANN-001 | Announcement model uses #[Fillable] for title, message, type, status, scheduled_at, link, target_roles, and created_by | P0 | A | Full |
| FR-ANN-002 | Announcement model casts target_roles to array, status to AnnouncementStatus, and scheduled_at to datetime and exposes published, draft, scheduled, and pendingPublish scopes | P0 | F | Full |
| FR-ANN-003 | Announcement model bridges to AnnouncementState and exposes isDraft, isScheduled, and isPublished helpers | P0 | U | Full |
| FR-ANN-004 | announcements table keys created_by to users with cascade delete and indexes on created_by, created_at, and status | P0 | A | Full |
| FR-ANN-005 | AnnouncementStatus is a backed string enum implementing StatusEnum with draft, scheduled, and published cases and localized labels via __() | P0 | U | Full |
| FR-ANN-006 | AnnouncementStatus enforces draft to scheduled or published, scheduled to published, and published to nothing, with published as the only terminal state and draft as the default | P0 | U | Full |
| FR-ANN-007 | SendAnnouncementAction extends BaseCommandAction and validates title, message, type, status, scheduled_at, link, and target_roles | P0 | F | Full |
| FR-ANN-008 | SendAnnouncementAction creates the row in a transaction stamped with the acting user and logs announcement_sent with title, status, and targets | P0 | F | Full |
| FR-ANN-009 | SendAnnouncementAction fans out only when the row is published, delegating to a dedicated single-execute notifications Action | P0 | F | Full |
| FR-ANN-010 | PublishAnnouncementAction transitions an existing row to published, clears scheduled_at, notifies recipients, and logs announcement_published inside one transaction | P0 | F | Full |
| FR-ANN-011 | Recipient resolution targets the selected roles and always excludes the sender's own roles | P0 | F | Full |
| FR-ANN-012 | Announcement fan-out dispatches after commit, discards on rollback, and rides the queue via ShouldQueue | P0 | F | Full |
| FR-ANN-013 | AnnouncementManager extends BaseRecordManager, gates boot() to admins, and renders the manager view | P0 | F | Full |
| FR-ANN-014 | Manager query scopes rows to the acting creator, lists title, type, status, created_at, and actions, and searches titles | P0 | F | Full |
| FR-ANN-015 | Manager save() delegates the form payload to SendAnnouncementAction, flashes success, and resets the form | P0 | F | Full |
| FR-ANN-016 | Manager confirmAction() serves both delete and publish paths and re-resolves ownership before executing either | P0 | F | Full |
| FR-ANN-017 | AnnouncementForm validates its fields, requires a future scheduled_at only for scheduled rows, and nulls scheduled_at and target_roles appropriately in toPayload() | P0 | F | Full |
| FR-ANN-018 | AnnouncementNotification uses mail, broadcast, and database channels, implements ShouldQueue, and maps mail, broadcast, and database payloads from title, message, and link | P0 | F | Full |
| FR-ANN-019 | announcements:publish command runs every minute, selects rows where scheduled_at has passed, and delegates each to PublishAnnouncementAction | P0 | F | Full |
| FR-ANN-020 | GET /admin/announcements maps to AnnouncementManager behind auth and super_admin or admin role middleware | P0 | F | Full |

### 4.1 Model, State Machine, and Entity

#### FR-ANN-001 — Mass assignment limited to the eight owned columns

A school clerk once pasted a spreadsheet id into a form field that bound straight to the model
and overwrote authorship on thirty rows. The explicit fillable list closes that class of
accident: only the content, scheduling, targeting, and author columns are ever mass-assignable,
and everything else — timestamps, primary key — stays outside the binding surface. Reviewers
check this list the way they check a firewall rule, because it is one.

#### FR-ANN-002 — Casts and scopes that match the manager's questions

When target roles arrived as a JSON string instead of an array, the targeting query silently
matched nobody and a flood notice reached zero students. Casting roles to an array, status to
its enum, and the timestamp to a real date object makes the targeting and scheduling logic read
plain values instead of parsing strings. The four scopes answer the manager's only questions —
what is live, what is staged, and what became due — with the due scope combining both halves of
the scheduled-and-past condition so no caller can forget one.

#### FR-ANN-003 — Entity bridge for rule evaluation

Rules written against raw model attributes coupled the transition checks to column names and
made them untestable without a database. The bridge freezes the row into a small readonly value
carrying status plus timestamp, and the three predicates answer the lifecycle questions without
touching persistence. A column rename now ripples through one bridge method while every Action,
policy check, and test keeps talking to the named predicates.

#### FR-ANN-004 — Foreign key and indexes sized for the access pattern

The manager lists one admin's rows newest-first and the cron scans by status plus timestamp, so
the three indexes mirror those two queries exactly. Cascading the author key keeps leaver
cleanup total: removing a departed admin's account removes their drafts and schedules in the
same statement instead of orphaning rows that later break the creator join. At school volume the
table stays tiny, which is why a single covering strategy beats any clever partitioning.

### 4.2 Lifecycle Rules

#### FR-ANN-005 — Status values as a localized enum

Storing lifecycle as free text once produced a published row with a trailing space that the
manager never listed and the cron never picked up. The backed enum ends that drift: three exact
values, each with a translated label resolved through the announcement language files. Adding a
fourth state later requires touching the enum, its transitions, and its translations together,
which is precisely the friction a lifecycle change deserves.

#### FR-ANN-006 — Transitions that make publishing one-way

Early rehearsals let admins unpublish a notice after students had already acted on it, which
left two truths in the school — the bell said go, the manager said never mind. The transition
table makes publishing terminal: drafts may stage or go immediately, scheduled rows may only go
live, and nothing leaves published. The default keeps every new composition private until an
explicit choice says otherwise, so haste alone can never broadcast.

### 4.3 Creation and Publishing Actions

#### FR-ANN-007 — Validation that rejects wishful scheduling

A scheduled_at timestamp in the past used to persist happily and then sit due-but-unpublished
until someone noticed the cron skipping it for the wrong reason. The creation validator now
holds title length, body length, severity membership, status membership, link length, and role
shape in one place, with the future-timestamp rule engaging only for scheduled rows. Invalid
payloads fail before any row exists, which keeps the manager list free of half-formed
announcements that need archaeology to explain.

#### FR-ANN-008 — Creation stamped, logged, and transactional

Anonymous rows once made it impossible to answer which admin sent a confusing notice during an
accreditation interview. Stamping the acting user at creation inside the same transaction as the
row write makes authorship atomic with existence, and the sent log line records title, status,
and targets with personal data masked. If the write fails, no log claims a send happened, and
the audit trail never describes a row that does not exist.

#### FR-ANN-009 — Fan-out only for the published moment

Sending notifications for drafts would have spammed recipients with every keystroke-save, so the
creation path notifies exclusively on the published branch. Delegating that branch to a
dedicated Action with its own single execute method honors the one-entry-point rule while
keeping the creation Action readable: validate, persist, then conditionally hand off. Drafts and
schedules leave the method quietly, waiting for their own publishing moment.

#### FR-ANN-010 — Publish as one atomic flip plus fan-out

Half-published rows — status flipped but recipients never notified — were the worst failure in
pilot term because the manager claimed delivery while bells stayed dark. The publish Action
wraps the status flip, the timestamp clear, the recipient fan-out, and the published log line in
a single transaction. Either the school sees a published row whose recipients were notified, or
nothing changed at all, and the log distinguishes the manual publish from the creation-time
publish for later audits.

#### FR-ANN-011 — Targeting that never notifies the author

An admin who announces a student assembly does not need that same notice in her own bell, yet
the first implementation sent to everyone including the author until complaints arrived. The
recipient query now intersects the selected roles — or all users when send-to-all is set — with
an exclusion of the sender's own roles. The rule is shared between immediate and scheduled
publishing so both paths agree on who counts as a recipient, and targeting an empty role set
notifies nobody rather than everybody.

#### FR-ANN-012 — Fan-out that survives scale and failure correctly

Consider a publish to every student that fails after half the mails leave: retrying naively
doubles delivery for the first half. After-commit dispatch plus queueing resolves both halves:
the fan-out leaves only once the status flip commits, a rollback discards it entirely, and the
queue drains the recipient set without holding the admin request open. The combination is what
lets a one-minute cron publish to a full school while the admin who scheduled it sleeps.

### 4.4 Management Interface

#### FR-ANN-013 — Manager gated before it renders anything

Route middleware alone once left the component reachable through a stale deep link after a role
demotion, rendering an empty manager that still accepted form posts. The boot-time authorization
closes that gap by refusing the component itself to anyone outside the admin group, before any
query or form state exists. Extending the record-manager base then gives pagination, sorting,
and confirmation plumbing identical to every other admin table, so reviewers audit only the
announcement-specific scoping.

#### FR-ANN-014 — Owned list with honest columns and search

Showing every admin's rows in one list led to a deleted colleague's notice within the first
month, so the base query now filters to the acting creator on every request. The column set
answers the author's real questions — what did I write, how severe, where is it in the
lifecycle, when did I write it — and title search finds the row when the list grows past a
page. Hiding other admins' rows is deliberate narrowness, documented as a trade-off rather than
an oversight.

#### FR-ANN-015 — Save that delegates and then gets out of the way

Inline persistence inside the component once duplicated the Action's validation with subtle
differences, and the two disagreed on link length for a semester. The save path now converts
the form to its payload and hands it to the creation Action untouched, then flashes the
translated success and resets the form for the next notice. The component keeps no copy of the
business rules, which means the next validation change touches exactly one file.

#### FR-ANN-016 — One confirmation serving two irreversible choices

Delete and publish share a modal because both are one-way doors that deserve a pause, and the
shared handler re-resolves the row under the ownership scope before doing anything. A crafted
request naming another admin's id fails the lookup instead of acting across the boundary, and
the publish branch re-checks the state machine in case the row transitioned since the list
rendered. The modal names the pending action plainly so haste cannot confuse deleting with
publishing.

#### FR-ANN-017 — Form that knows when its own fields apply

The scheduled_at picker enabled for every status once produced published rows carrying stale
future timestamps that confused the audit log. The form rules require the timestamp only for
scheduled rows and demand it lie in the future, while the payload builder nulls it for every
other status and nulls the role list when send-to-all is set. The shape the Action validates is
therefore always clean, and the component never relies on the Action to forgive UI state it
should never have sent.

### 4.5 Delivery and Schedule

#### FR-ANN-018 — One class speaking three channels

Mail reaches parents' inboxes, broadcast feeds any live widgets, and the database row feeds the
center — and an announcement needs all three to land like official school communication. The
single notification class builds each representation from the same title, message, and link, so
the three copies can never disagree on wording. Carrying the queue marker keeps the admin
request fast even when the recipient query returns the whole student body, and the database
payload follows the backbone's standard five-key shape.

#### FR-ANN-019 — Per-minute publisher that delegates per row

A delayed-job design would have hidden pending announcements inside queue payloads nobody could
inspect or retry. The minute-cadence command instead queries the due scope visibly, hands each
row to the publish Action that owns transactions and fan-out, and reports per-row outcomes with
a closing count. Re-running after an outage publishes everything missed without duplicates,
because the due scope only matches rows still scheduled with a past timestamp.

#### FR-ANN-020 — Route that names its audience

The announcements URL lives under the admin prefix with both authentication and the admin-role
gate, so an unauthenticated visit redirects to login while a signed-in student receives a
refusal instead of an empty page. Naming the route lets the sidebar, the manager redirects, and
the audit log all reference one stable identifier. The middleware pair mirrors the component's
own boot check, giving the defense-in-depth posture the global authorization rule requires.

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO. `Status` is `Full` for every row because this spec is implemented
and verified.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-ANN-001 | Scheduled rows publish within one scheduler tick past their timestamp | <= 60s late | P0 | F | Full |
| NFR-ANN-002 | Publishing never blocks the admin request on recipientcount | Queue-drained fan-out | P0 | F | Full |
| NFR-ANN-003 | Announcement pages refuse non-admin visitors at both route and component layers | 0 unauthorized renders | P0 | F | Full |
| NFR-ANN-004 | Managers list only rows created by the acting admin | 0 cross-admin rows | P0 | F | Full |
| NFR-ANN-005 | Senders never receive their own announcement notifications | 0 self-receipts | P1 | F | Full |
| NFR-ANN-006 | Persisted schedules always lie at or after creation time | 0 past-dated rows | P1 | F | Full |
| NFR-ANN-007 | Every user-facing string passes through __() with mirrored en and id keys | 0 hardcoded strings | P0 | A | Full |
| NFR-ANN-008 | Rendered message HTML is sanitized Markdown with unsafe input stripped and unsafe links refused | 0 unsanitized fragments | P0 | F | Full |
| NFR-ANN-009 | Status flip and recipient fan-out commit or roll back as one unit | 1 transaction | P0 | F | Full |
| NFR-ANN-010 | All classes declare strict types with enum, entity, and base-class discipline intact | 100% files | P0 | A | Full |

### 5.1 Timing and Scale

#### NFR-ANN-001 — Lateness bounded by the tick

A Monday 06:30 exam notice that arrives at 08:00 has already failed, no matter how correct its
wording. The sixty-second bound ties lateness to the scheduler cadence itself: whatever becomes
due is picked up on the very next tick, and the due scope makes the selection idempotent across
retries. The residual minute of imprecision was accepted because school communication tolerates
a minute but never tolerates a missed morning.

#### NFR-ANN-002 — Request time independent of school size

During pilot term a 40-student test school published instantly while a 900-student school held
the admin's browser spinning for half a minute, and the difference was synchronous mail. Moving
fan-out onto the queue flattened that curve: the request commits the row and returns while the
worker drains recipients. The test fakes the queue and asserts the request completes without
waiting for delivery, which is the only assertion that survives enrollment growth.

### 5.2 Access and Targeting

#### NFR-ANN-003 — Two gates on the same door

Middleware protects the URL and the component protects itself, and either alone would pass a
casual test while leaving the stale-link hole open after demotions. Together they refuse
unauthenticated visitors with a redirect and authenticated non-admins with a denial, at both
layers. The pair is verified by requesting the page both ways, which catches the future edit
that weakens exactly one of them.

#### NFR-ANN-004 — Ownership as the listing boundary

When the list briefly showed every admin's rows, one admin edited another's draft announcement
and both blamed the system at the staff meeting. Scoping the query to the acting creator ended
the entire category: authors see their own work, nobody else's, and the re-check inside the
confirm handler closes the crafted-request variant. Central administration of others' rows would
need a policy extraction, which is deliberately left as future work.

#### NFR-ANN-005 — Silence for the sender's own roles

Self-notification reads as a bug in every usability session, because the author already knows
what she wrote. Excluding the sender's roles from the recipient query keeps the author's bell
clean while reaching every intended reader, and the zero-receipt property is asserted by
publishing as an admin and then counting that admin's new rows. The rule applies identically to
immediate and scheduled publishing so neither path surprises.

#### NFR-ANN-006 — No schedules in the past

Past-dated schedules created a paradoxical row — staged yet already due — that rendered
differently in the manager and the cron until someone published it by hand to clear the
confusion. Requiring the timestamp at or after now at both form and Action layers keeps that
state unreachable. The boundary admits equality so a publish-now-through-scheduling flow still
works, and anything earlier fails with a translated message before any row exists.

### 5.3 Presentation and Integrity

#### NFR-ANN-007 — Translation coverage without exceptions

An English-only validation message once reached Indonesian parents who then called the school
instead of following the notice. Passing every label, flash, mail subject, and validation
message through the helper with mirrored language files makes the missing-key scan the
backstop. Names, dates, and links travel as placeholders inside full sentences, so translators
never reassemble fragments.

#### NFR-ANN-008 — Markdown without the XSS bill

Rich-text editors were rejected for bundle size and attack surface, but raw Markdown rendering
without sanitization would have reintroduced script injection through announcement bodies. The
renderer strips raw HTML input and refuses unsafe link schemes, which preserves headings, lists,
and emphasis while neutralizing the payload an attacker would actually use. The guarantee is
verified by rendering a hostile fixture and asserting no script or javascript-scheme fragment
survives.

#### NFR-ANN-009 — Atomicity the manager can promise

The manager's success flash claims both persistence and delivery, so the two must share one
fate. Wrapping the status change, the fan-out dispatch, and the log write in a single
transaction makes the promise true: recipients exist exactly when the row claims published, and
a failure leaves the staged state untouched for retry. The test publishes under a forced
failure and asserts neither a flipped row nor a stray notification remains.

#### NFR-ANN-010 — Structural discipline across every file

Strict typing, the status-enum contract, the readonly entity bridge, and the base-class
lineage are individually small, yet together they are what let a reviewer trust an unfamiliar
file in seconds. The scans enforce the declarations so new files inherit the discipline
without a checklist, and the entity's final-readonly shape guarantees lifecycle rules stay
testable without a database.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 Announcement Model and Schema

```php
#[Fillable(['title', 'message', 'type', 'status', 'scheduled_at', 'link', 'target_roles', 'created_by'])]
class Announcement extends BaseModel
{
    public function creator(): BelongsTo;
    public function scopePublished(Builder $q): Builder;
    public function scopeDraft(Builder $q): Builder;
    public function scopeScheduled(Builder $q): Builder;
    public function scopePendingPublish(Builder $q): Builder;
    public function asAnnouncementState(): AnnouncementState;
    public function isScheduled(): bool;
    public function isDraft(): bool;
    public function isPublished(): bool;
}
```

```php
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

### 6.2 AnnouncementStatus Enum and AnnouncementState Entity

```php
enum AnnouncementStatus: string implements StatusEnum
{
    case DRAFT = 'draft';
    case SCHEDULED = 'scheduled';
    case PUBLISHED = 'published';

    public function label(): string;
    public function canTransitionTo(StatusEnum $target): bool;
    public function isTerminal(): bool;
    public function validTransitions(): array;
    public static function default(): self;
}
```

```php
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
    public function isPendingPublish(?Carbon $now = null): bool;
}
```

### 6.3 Actions and Form

```php
final class SendAnnouncementAction extends BaseCommandAction
{
    public function execute(array $data): Announcement;
}

final class SendAnnouncementNotificationsAction extends BaseCommandAction
{
    public function execute(Announcement $announcement, array $config): void;
}

final class PublishAnnouncementAction extends BaseCommandAction
{
    public function execute(Announcement $announcement): void;
}

final class DeleteAnnouncementAction extends BaseCommandAction
{
    public function execute(Announcement $announcement): void;
}
```

```php
class AnnouncementForm extends Form
{
    public string $title = '';
    public string $message = '';
    public string $type = 'info';
    public string $status = 'draft';
    public ?string $scheduled_at = null;
    public ?string $link = null;
    public array $target_roles = [];
    public bool $sendToAll = true;

    public function rules(): array;
    public function toPayload(): array;
}
```

### 6.4 Notification and Command

```php
class AnnouncementNotification extends Notification implements ShouldQueue
{
    public function __construct(
        public string $title,
        public string $message,
        public ?string $link = null,
    ) {}

    public function via($notifiable): array;
    public function toMail($notifiable): MailMessage;
    public function toBroadcast($notifiable): array;
    public function toCustomDatabase($notifiable): array;
}
```

```php
class PublishScheduledAnnouncementsCommand extends Command
{
    protected $signature = 'announcements:publish';

    public function handle(PublishAnnouncementAction $action): int;
}
```

```php
Route::get('/admin/announcements', AnnouncementManager::class)
    ->name('sysadmin.announcements')
    ->middleware(['auth', 'role:super_admin|admin']);

Schedule::command('announcements:publish')->everyMinute();
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence; per QLHDO these are recorded decisions, not test rows.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-ANN-001 | Deliver to selected roles with the sender roles excluded instead of broadcasting blindly | P0 | — | — |
| DD-ANN-002 | Stage through draft, scheduled, and terminal published with no expiry or unpublishing | P0 | — | — |
| DD-ANN-003 | Publish schedules with a per-minute command instead of delayed jobs or event listeners | P0 | — | — |
| DD-ANN-004 | Gate with route middleware plus component authorization and ownership scoping instead of a dedicated policy | P1 | — | — |
| DD-ANN-005 | Author in Markdown rendered with sanitization instead of a rich-text editor | P1 | — | — |
| DD-ANN-006 | Change status only in Actions with after-commit queued fan-out and never in model observers | P0 | — | — |

### 7.1 Targeting and Lifecycle

#### DD-ANN-001 — Relevance over reach

A supervisor-only policy update once reached two hundred students who queued at the office
asking whether it applied to them. Targeting by role with the author's own roles excluded
trades one extra query clause for the end of that category: notices reach the group they name
and nobody else. The cost is a join the school database never notices, and the payoff is a bell
students still trust because everything in it concerns them.

#### DD-ANN-002 — Staging with a terminal send

Letting published rows reopen would force the system to chase copies already sitting in
recipients' centers — edit them, hide them, or pretend they never sent — each option worse than
the last. Terminal publishing sidesteps the entire dilemma by declaring the send final, while
draft and scheduled states preserve every legitimate need to prepare, review, and time content.
Old rows accumulate slowly at school volume, so retention pressure never justifies the
complexity of expiry.

### 7.2 Scheduling and Access Shape

#### DD-ANN-003 — Visible cron over invisible delay

Delayed queue jobs hide the pending set inside opaque payloads that operators cannot list,
inspect, or retry without queue tooling. The minute-cadence command keeps pending work as
ordinary rows: listable in the manager, countable in one query, republishable by re-running one
command after an outage. The price is up to sixty seconds of lateness, which every school
calendar absorbs without notice.

#### DD-ANN-004 — Middleware plus scoping in place of a policy class

The access model has exactly two rules — admins only, and only your own rows — which a full
policy class would restate with boilerplate and no extra protection. Middleware enforces the
role gate on the URL, the component enforces it again on itself, and the query enforces
ownership on every row touched. Should central administration of others' rows ever become
required, the extraction point is known and named, and the current narrowness stands as the
documented privacy posture until then.

#### DD-ANN-005 — Markdown with guardrails

A rich editor would have added a JavaScript bundle, a storage story for embedded images, and a
far larger injection surface, all to format notices that are mostly a heading plus a paragraph.
Markdown covers that shape with zero client cost, and the sanitized renderer keeps the one
property that matters: no author-supplied markup survives as executable HTML. The inline guide
and preview carry non-technical admins across the unfamiliar syntax, and the renderer can be
swapped later without touching the stored bodies.

### 7.3 Side-Effect Posture

#### DD-ANN-006 — Actions and events, never observers, with queued fan-out

A status observer would fire inside the publishing transaction, dispatching mail for a row that
might still roll back, and it could never reach across to the notification module's queue
without breaking its single-model scope. Performing the flip in the Action and fanning out
through after-commit queued events satisfies all three ADR gates at once: cross-module reach,
deferrable work, and discard-on-rollback correctness. Synchronous observers stay reserved for
same-request single-model guarantees elsewhere, and this subsystem deliberately uses none.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Scheduled lateness | Within 60s past `scheduled_at` | Cron run against due fixtures + command output count |
| Admin request independence from recipient count | Publish returns while queue drains | Queue fake in publish test + timed manual run |
| Self-notifications | 0 incidents | Publish-as-admin then count admin's new rows |
| Invalid transitions accepted | 0 | Enum transition matrix test |
| Cross-admin row visibility | 0 rows | Manager query review + ownership tests |
| Unsanitized fragments rendered | 0 | Hostile Markdown fixture render test |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [Base Classes](SE5Q9-base-classes.md) | `BaseCommandAction`, `BaseEntity`, `BaseModel`, `StatusEnum` contract |
| [RBAC & Authorization](T4B26-rbac-and-authorization.md) | `role:` middleware and role gating |
| [Notification Infrastructure](TXR2H-notification-infrastructure.md) | `CustomDatabaseChannel`, `SendsNotifications` contract, delivery backbone |

### Build Guide

Build the enum and entity first with their transition matrix tests, then the model with its
scopes, then the creation and publish Actions against the backbone, then the manager and form in
isolation, and finally the minute command with due fixtures. Wire dashboard surfacing only after
center delivery reads correctly.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [Dashboard](CKKZC-dashboard.md) | Recent announcements surface as per-role widgets |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume announcement volume stays in the tens per year, so terminal published rows with no expiry or archiving suffice at MVP | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Notification Infrastructure](TXR2H-notification-infrastructure.md) — delivery backbone this spec fans out through
- [RBAC & Authorization](T4B26-rbac-and-authorization.md) — role middleware and admin gating
- [ADR: Cross-module communication](../adr/adr-cross-module-communication.md) — events vs delegation guidance
- [ADR: Eloquent observers](../adr/adr-eloquent-observers.md) — observer-vs-event criteria
- [ADR: MVP spec trim](../adr/adr-mvp-spec-trim.md) — what was consolidated out of this spec
