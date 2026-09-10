# Notification Infrastructure — Cross-Module In-App & Multi-Channel Notifications

> **Spec ID:** TXR2H
> **Status:** Full
> **Owner:** User
> **Depends on:** SE5Q9, NUCY3

## Description

Internara's shared notification backbone: a custom database channel that persists structured
in-app notifications through the `SendsNotifications` contract, a `NotificationCenter` page and a
cached `NotificationBell` for every authenticated user, and a small library of queued domain
notifications used by auth, program, assignment, incident, announcement, and backup flows.

---

## 1. Problem Statements

### PS-1 — Cross-Module Notification Without a Unified Channel

Every module needs to reach users — registration updates, assignment publications, incident
reports, announcements, backup failures. Without a shared channel each module invents its own
persistence and delivery, schemas drift, and no single screen shows everything a user missed
during a busy enrollment week.
**→ Requirement:** FR-NOTIF-001/004 (channel + contract), FR-NOTIF-008 (shared payload shape).

### PS-2 — No Centralized Notification View

A student who missed three days of announcements, an assignment posting, and a placement update
needs one place to catch up. A header bell alone answers how many are unread but never what they
say or which ones still need action.
**→ Requirement:** FR-NOTIF-010/011/012 (center list, search, viewer), UC-NOTIF-001.

### PS-3 — Unread Count Performance on Every Page Load

The bell renders on every authenticated page. An uncached `COUNT(*)` over the notifications table
on each navigation turns a 400-student morning login rush into hundreds of redundant indexed
counts that all return the same small number.
**→ Requirement:** FR-NOTIF-015 (cached count), NFR-NOTIF-001 (invalidation window).

### PS-4 — Cache Invalidation on Notification State Changes

A bell that still shows three unread after the student read everything destroys trust in the
whole center. Sends, reads, batch reads, and deletes arrive from many call sites, so invalidation
tied to one screen always misses a path.
**→ Requirement:** FR-NOTIF-017/018/019 (actions emit + forget), DD-NOTIF-002 (event-driven invalidation).

### PS-5 — Laravel Default Database Notification Mismatch

Laravel's built-in database channel stores a fully-qualified class name plus an opaque JSON blob.
That shape cannot answer the center's everyday queries — filter by category, search titles,
render a link — without parsing JSON on every row.
**→ Requirement:** FR-NOTIF-006/007 (structured columns + index), DD-NOTIF-001 (custom channel).

---

## 2. Goals & Non-Goals

### Goals

- **Structured in-app persistence** — every in-app notification lands as a typed row with title, message, link, and payload. *Why:* the center can query, filter, and render without JSON parsing.
- **Single contract for sending** — `SendsNotifications` is the only path from channel to storage. *Why:* validation, logging, and events live in one auditable place instead of scattering across modules.
- **One center plus one bell** — a full paginated center for triage and a cached header bell for glanceability. *Why:* students catch up in one visit yet see the count everywhere.
- **Ownership-scoped access** — users see only their own rows. *Why:* notifications carry personal placement and account facts that must never leak across users.
- **Queued domain fan-out** — welcome, assignment, registration, incident, announcement, and account-status notifications leave the request via the queue. *Why:* an admin publishing to 800 students must not wait for 800 mail sends.
- **Localized, logged, rejection-safe delivery** — `__()` strings, SmartLogger with PII masking, `RejectedException` for business failures. *Why:* the same trust contract every global requirement demands.

### Non-Goals

- **Real-time push over sockets.** *Why:* the broadcast channel exists but no frontend listener is wired at MVP; polling the cached bell is sufficient for school cadence.
- **Per-recipient preference matrices.** *Why:* mute-per-type settings are post-MVP depth; the MVP always delivers and lets the center filter instead.
- **Threads, grouping, digests.** *Why:* school volume is tens per year per user, not a social feed; scheduled digest daemons are explicitly deferred.
- **Mail deliverability management.** *Why:* retry and bounce handling belong to the mail driver, not this spec.
- **Admin-authored templates.** *Why:* notification copy is code-reviewed and translated, not edited at runtime.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled here
because each story below has a code-testable consequence verified by the listed layer.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-NOTIF-001 | Student opens the notification center, searches and filters, opens a row and sees the bell count drop | P0 | F | Full |
| UC-NOTIF-002 | User marks all notifications as read and the bell settles at zero | P0 | F | Full |
| UC-NOTIF-003 | User selects several rows, marks them read or deletes them in one confirmation | P1 | F | Full |
| UC-NOTIF-004 | First-login student receives a role-appropriate welcome notification without duplicates | P0 | F | Full |
| UC-NOTIF-005 | Teacher action fans out to enrolled students as in-app rows plus queued mail without blocking the request | P0 | F | Full |

### 3.1 Reading and Triage

#### UC-NOTIF-001 — Student catches up after three absent days

A student at SMKN 1 Gesi returned after a network outage to find the bell showing eleven unread
with no idea which one was the placement update that mattered. She opened the center, typed the
company name into search, filtered to unread, and worked the list from newest to oldest. Opening
the placement row marked it read, the viewer showed the deep link to her placement page, and the
bell dropped by one without a full page reload. The shape matters because catching up is the
dominant reading pattern during enrollment and assessment weeks, when several modules notify at
once and recency plus search beat any grouped or threaded view.

#### UC-NOTIF-002 — Admin clears the backlog before accreditation review

When an admin opens the center on accreditation-evidence morning, dozens of stale system notices
from the past month compete with the two that still matter. The mark-all-as-read control exists
for that exact moment. One confirmation flips every unread row for that user, forgets the cached
count, and the bell settles at zero on the next render. The operation is deliberately scoped to
the acting user only, so clearing one admin's backlog never touches another admin's unread set,
and the flash message confirms the sweep while the Livewire event tells the bell to refresh.

### 3.2 Acting and Receiving

#### UC-NOTIF-003 — Supervisor triages a week of incident copies

Morning supervision at a 300-student school means a dozen incident copies and assignment reminders
accumulated over the weekend. The supervisor checks five rows, marks them read in one step, then
deletes two obsolete reminders after a confirmation modal. The batch path validates ownership on
every id before touching anything, so a crafted request naming another user's row fails closed
instead of partially applying. Selection clears afterward, the bell refreshes once rather than
per row, and a business-rule failure surfaces as a friendly translated toast instead of a stack
trace.

#### UC-NOTIF-004 — First-login welcome that arrives exactly once

Early pilots sent the welcome notice on every login until students learned to ignore the bell
entirely. The current flow keys off the first-login marker: the login event fires, the listener
checks whether the marker is still empty, resolves the role-appropriate welcome copy, and sends
through the contract. A second login finds the marker set and stays silent. The welcome row is a
real persisted notification like any other, so it participates in search, read tracking, and
cache invalidation, and a duplicate send would be visible immediately as two identical rows.

#### UC-NOTIF-005 — Assignment publishing reaches eight hundred students

A teacher publishing a new assignment the night before a deadline cannot wait for eight hundred
mail sends to finish inside the request. The publish path writes the assignment, then fans out
through the notification class whose delivery channels include mail alongside the custom database
channel. The in-app rows persist synchronously so the center is correct immediately, while mail
and broadcast leave through the queue. If the transaction that created the assignment rolls back,
the fan-out is discarded with it, so students never receive a notification pointing at an
assignment that does not exist.

---

## 4. Functional Requirements

Global defaults from QLHDO and D2FT3 apply (localization, authorization, Action Triad,
`RejectedException`, SmartLogger with PII masking). `Status` is `Full` for every row because this
spec is implemented and verified.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) · `A` = Arch (structure/contracts).
**Status legend:** `Planned` = not started · `Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-NOTIF-001 | CustomDatabaseChannel accepts a SendsNotifications instance via constructor injection and exposes only send() | P0 | A | Full |
| FR-NOTIF-002 | Channel send() resolves the notifiable user id via getKey() or id and returns silently when no valid id exists | P0 | F | Full |
| FR-NOTIF-003 | Channel send() maps toCustomDatabase() output to NotificationData and SmartLogger-warns when type or title is missing | P0 | F | Full |
| FR-NOTIF-004 | SendsNotifications contract defines execute(NotificationData) and SendNotificationAction implements it as a BaseCommandAction | P0 | A | Full |
| FR-NOTIF-005 | SendNotificationAction validates userId, type, and title, creates the row in a transaction, logs notification_sent, and emits NotificationSent | P0 | F | Full |
| FR-NOTIF-006 | Notification model uses #[Fillable] for user_id, type, title, message, data, link, is_read, read_at with array, boolean, and datetime casts plus a user() BelongsTo | P0 | A | Full |
| FR-NOTIF-007 | notifications table carries a composite index on user_id and is_read with user_id cascading on user delete | P0 | A | Full |
| FR-NOTIF-008 | Every database-persisted notification class implements toCustomDatabase() returning type, title, message, link, and data | P0 | A | Full |
| FR-NOTIF-009 | Notification fan-out dispatches after commit and is discarded when the originating transaction rolls back | P0 | F | Full |
| FR-NOTIF-010 | NotificationCenter extends BaseRecordManager and scopes its query to rows owned by the authenticated user | P0 | F | Full |
| FR-NOTIF-011 | Center search matches title and message and the status filter separates unread from read | P1 | F | Full |
| FR-NOTIF-012 | Center viewNotification() verifies ownership, marks unread rows read, emits notification-read, and opens the viewer | P0 | F | Full |
| FR-NOTIF-013 | Center markAsRead, markAllAsRead, and markSelectedAsRead delegate to MarkAsReadAction, MarkAllAsReadAction, and MarkBatchAsReadAction | P0 | F | Full |
| FR-NOTIF-014 | Center batch delete runs through performBulkAction() into DeleteNotificationAction and renders RejectedException as a translated toast | P1 | F | Full |
| FR-NOTIF-015 | NotificationBell caches the unread count with Cache::remember() on the registered notification_unread key with a 60-second TTL | P0 | F | Full |
| FR-NOTIF-016 | Bell mounts by refreshing the count, returns zero for guests, and re-refreshes on notification-read and notifications-read events | P0 | F | Full |
| FR-NOTIF-017 | MarkAsReadAction is idempotent, stamps is_read and read_at only on unread rows, and emits NotificationRead | P0 | F | Full |
| FR-NOTIF-018 | MarkAllAsReadAction and MarkBatchAsReadAction update only rows owned by the user and forget the unread cache key | P0 | F | Full |
| FR-NOTIF-019 | DeleteNotificationAction deletes inside a transaction and forgets the unread cache key | P0 | F | Full |
| FR-NOTIF-020 | NotificationPolicy grants viewAny to authenticated users, view and update to owners, and create and delete to admins | P0 | U | Full |
| FR-NOTIF-021 | Domain notifications for welcome, assignment, registration, incident, announcement, and account-status use mail plus broadcast plus database channels and implement ShouldQueue | P0 | F | Full |
| FR-NOTIF-022 | GeneralNotification toggles the mail channel on a sendEmail flag and always persists the database row | P1 | F | Full |
| FR-NOTIF-023 | Mail-only security notifications use the mail channel without touching the database channel | P0 | F | Full |
| FR-NOTIF-024 | Non-critical notification listeners implement ShouldQueue so fan-out never blocks the originating request | P1 | A | Full |

### 4.1 Channel and Sending Contract

#### FR-NOTIF-001 — Thin channel with one injected dependency

When the channel started calling the model directly, every validation fix had to be repeated in
each notification class, and two of them diverged within a month. Injecting the contract instead
keeps the channel at mapping work only: receive the Laravel notification, ask it for its
structured array, hand a typed DTO to the single Action. The constructor signature is the whole
public surface, which keeps the class scan-enforceable and leaves persistence, logging, and event
semantics exactly where reviewers expect them.

#### FR-NOTIF-002 — Silent skip when nobody is home

During a bulk import last semester a deactivated user row without a retrievable key reached the
channel and took down the entire batch with a null-pointer error. The guard that returns silently
on a missing user id exists because of that morning. Resolving through the standard key accessor
first and falling back to the id property covers both authenticatable shapes in the codebase, and
silence is correct here rather than an exception because a notification must never fail the
business transaction it annotates.

#### FR-NOTIF-003 — Warn loudly on malformed payloads

A missing title renders as an empty row that students cannot search and admins cannot diagnose.
Rather than persisting the broken row or throwing inside the send path, the channel logs a
SmartLogger warning naming the offending notification class, with PII masked before the payload
reaches any sink. In practice the warning fires during development when a new notification class
forgets a key, and stays quiet in production, which is exactly the sensitivity a mapping layer
should have.

#### FR-NOTIF-004 — One contract, one implementing Action

Early drafts let any caller write to the notifications table, and within weeks three modules had
three different validation rules for the same title length. The interface fixes a single method
shape carrying the Core DTO, and the Command Action is its only production implementation. The
Action extends the command base so transactions, logging, and error handling come structurally,
and tests can fake the contract at the boundary while feature tests exercise the real
implementation against the database.

#### FR-NOTIF-005 — Validate, persist, log, announce

A vocational school in Sintuk Toboh Gadang once received two hundred blank welcome rows because
an empty title slipped through an unvalidated path during setup week. The Action now validates
the user id, the fifty-character category key, and the title before opening a transaction, and
only on success writes the activity entry and emits the sent event. The ordering matters: the
event leaves after commit, so a listener can never invalidate cache for a row that later rolls
back, and the log line carries the category and recipient without ever including message bodies
that might hold personal data.

### 4.2 Persistence and Payload Shape

#### FR-NOTIF-006 — Model as a typed persistence adapter

If the model ever grew a helper that decided who should be notified, that logic would be
untestable without a database and invisible to the Action reviewers. The model therefore stays a
persistence adapter: mass-assignment limited to the eight listed attributes, casts guaranteeing
the payload arrives as an array and the flags as real booleans and dates, and a single
relationship back to the owning user. Business rules about who receives what live in Actions and
listeners, never here, which keeps the class small enough to review in one glance.

#### FR-NOTIF-007 — Index and cascade that match the read pattern

The two hottest queries in this subsystem are the bell count and the center list, both filtered
by owner and read flag. Without the composite index the morning login rush scanned growing
per-user histories on every navigation, and the slowdown appeared first on shared hosting where
it hurt most. The cascade on the user foreign key closes the other operational gap: deleting a
graduated student's account removes their notification rows in the same statement instead of
orphaning rows that later break the center's owner join.

#### FR-NOTIF-008 — Every stored notification speaks the same five keys

When each module invented its own payload keys, the center needed per-type rendering branches and
search missed half the corpus. The five-key contract ended that drift: a short category string for
filtering, a human title for the list, an optional body, an optional deep link, and an optional
structured bag for ids and metadata. A new notification class that honors the contract renders
correctly on day one, and the channel's warning from FR-NOTIF-003 catches the ones that forget.

#### FR-NOTIF-009 — Fan-out that respects the transaction

Consider an assignment publish whose database write fails halfway through: students who already
received mail about an assignment that does not exist will ask their teacher about a ghost. The
after-commit discipline prevents that story. Events carrying notifications leave only once the
originating transaction commits, and a rollback discards them silently. The same rule protects
registration and incident flows, where the notification is meaningless without the state change
it describes.

### 4.3 Center and Bell Interface

#### FR-NOTIF-010 — Center scoped to the viewer, always

Cross-user leakage would be the worst defect this subsystem can produce, since rows carry
placement and account facts. The center therefore builds its base query from the authenticated
id on every request, never from a route parameter or a selectable user picker. Extending the
record-manager base gives search, sort, and pagination behavior identical to every other admin
table, so the ownership scope is the only notification-specific logic a reviewer must verify.

#### FR-NOTIF-011 — Search and filter that match triage habits

During supervision weeks a mentor looks for one company's name across titles and bodies, then
narrows to unread to decide what still needs a visit. The search applies a like-match across
both text columns, and the status control maps to the read flag in the obvious direction. The
combination covers the two real triage questions — what is this about, and is it handled — and
nothing else was added because richer faceting never appeared in any school visit note.

#### FR-NOTIF-012 — Opening a row finishes the read

Students complained when opening a notification left the bell unchanged until they found a
separate mark-read button. The viewer path now finishes the job: it re-resolves the row under
the ownership scope, flips the flags only when currently unread, emits the Livewire event the
bell listens for, and then shows the detail with its deep link. A direct request naming another
user's id fails the ownership lookup before any state changes, which keeps the convenience from
becoming a confused-deputy hole.

#### FR-NOTIF-013 — Click handlers delegate, never write inline

Inline updates inside Livewire components were the original source of forgotten cache
invalidation and missing events. Each of the three mark paths now delegates to its dedicated
Action, which owns the update, the event, and the cache forget as one unit. The component keeps
only UI concerns: flashing the translated confirmation, clearing selection, and emitting the
event the bell needs. A reviewer checking correctness reads the Action, not the component.

#### FR-NOTIF-014 — Batch delete with a confirmation and a kind failure

Bulk deletion without confirmation once removed a semester of evidence notices an admin had meant
to archive, so the destructive path now requires an explicit modal step. The bulk runner feeds
each selected id through the delete Action under the ownership scope, and a business-rule refusal
arrives as a translated toast naming the problem in plain Indonesian rather than an exception
page. The cache forget happens per deletion inside the Action, so the bell cannot lag the list
even when only some rows succeed.

#### FR-NOTIF-015 — Bell count behind a short-lived cache entry

Without caching, every page load paid for a count query that almost always returned the number
the previous page had shown. The sixty-second remembered entry keyed by user id removes that tax
while staying fresh enough that nobody notices staleness during normal navigation. The key lives
in the centralized registry rather than as an inline string, so a rename or prefix change is a
single config edit instead of a hunt across components and listeners.

#### FR-NOTIF-016 — Bell that behaves for guests and live updates

A logged-out visitor hitting a cached layout must never see the previous user's number, so the
bell short-circuits to zero before touching the cache whenever no authenticated id exists. For
signed-in users the mount path populates the count once, and the two Livewire event mappings
keep it current as the center marks rows read. The event names are the only coupling between
center and bell, which keeps both components independently testable with faked events.

### 4.4 State-Change Actions

#### FR-NOTIF-017 — Mark-as-read that tolerates double clicks

Double-clicks and retried requests used to stamp a second timestamp and emit a second event,
which briefly flashed the bell before settling. The Action now checks the current flag first and
treats an already-read row as success without writing or announcing. The single-read path still
stamps both columns together and emits the read event carrying the fresh model, so listeners see
the state the user actually caused and nothing more.

#### FR-NOTIF-018 — Batch updates that stay inside one owner's rows

A batch request carrying a foreign id must never flip another user's flags, even when the caller
is authenticated. Both batch Actions constrain the update to the intersection of the supplied ids
and the acting user id, then forget that user's cache entry once. The returned count reflects
rows actually changed, which lets the component flash an honest message instead of assuming the
selection size, and an empty intersection succeeds quietly rather than erroring.

#### FR-NOTIF-019 — Delete that cleans up its own cache footprint

Deleting the row without forgetting the cached count left the bell showing a phantom unread
until the sixty-second entry expired, which read as a bug in every usability session. The delete
Action therefore wraps removal in a transaction and forgets the owner's cache entry in the same
unit. If the delete fails, the count is untouched, and the bell and the list can never disagree
beyond the single request that caused the change.

#### FR-NOTIF-020 — Policy that separates mine from admin-only

The authorization model fits in one glance because notifications are personal data with a small
administrative exception. Any signed-in user may list, and viewing or marking requires ownership
of that exact row, which the center enforces again at the query level for defense in depth.
Creating system rows and removing them administratively stays behind the admin check, so a
student can never mint a broadcast-looking row or clear another user's evidence trail.

### 4.5 Library and Queue Discipline

#### FR-NOTIF-021 — Domain library on three channels, always queued

Six domain moments — first welcome, assignment publication, registration change, incident report,
announcement broadcast, account-status change — share one delivery shape: persist the in-app row
plus queued mail and broadcast copies. Implementing the queue marker on each class keeps the
admin request fast when the recipient set spans hundreds of students, and the shared channel
tuple means a new domain notification copies an established pattern instead of inventing
delivery semantics. The category keys stay short and stable because the center filters on them.

#### FR-NOTIF-022 — General notice with an explicit mail choice

Most general notices are in-app only, but a school-closure message also needs mail. The flag on
the general class captures that decision at the call site instead of hiding it in configuration:
mail joins the channel list only when the caller passes true, while the database row persists in
both cases. The explicitness prevents accidental mail storms from routine notices and makes the
mail/no-mail choice visible in code review, where the blast radius is easiest to judge.

#### FR-NOTIF-023 — Security mail that never becomes a row

Password-change warnings, one-time codes, and settings test messages must reach the mailbox
without leaving a persistent in-app copy that outlives its usefulness or duplicates sensitive
material. These classes declare the mail channel only, so the custom database channel never sees
them and no row is written. The separation also keeps the center free of ephemeral security
tokens that would confuse triage and linger in backups.

#### FR-NOTIF-024 — Slow listeners ride the queue

When every listener ran synchronously, publishing an announcement to a full school held the admin
request open while mail, cache, and log work finished one after another. Non-critical listeners
now carry the queue marker, so the request commits and returns while fan-out drains in the
background. The rule is deliberately narrow: anything the next render depends on stays
synchronous, and everything else — mail, broadcast, secondary cache — moves off the request.

---

## 5. Non-Functional Requirements

`Target` is the concrete SLO. `Status` is `Full` for every row because this spec is implemented
and verified.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-NOTIF-001 | Unread-count cache invalidates within one request cycle of any send, read, batch-read, or delete | 1 request | P0 | F | Full |
| NFR-NOTIF-002 | Users can reach only their own rows at both policy and query-scoping layers | 0 cross-user reads | P0 | F | Full |
| NFR-NOTIF-003 | Channel delivery with no resolvable user id skips silently without throwing | 0 exceptions | P0 | F | Full |
| NFR-NOTIF-004 | Notification category key stays within storage bounds | 50 chars max | P1 | A | Full |
| NFR-NOTIF-005 | Mark-as-read is idempotent and safe to retry | 0 duplicate writes | P1 | F | Full |
| NFR-NOTIF-006 | Every user-facing string in center, bell, and mails passes through __() with mirrored en and id keys | 0 hardcoded strings | P0 | A | Full |
| NFR-NOTIF-007 | Bell renders on every authenticated page via the shared layout | 100% pages | P1 | B | Full |
| NFR-NOTIF-008 | All notification classes declare strict types | 100% files | P0 | A | Full |
| NFR-NOTIF-009 | Unread-count key is registered centrally with no ad-hoc key strings | 1 registry key | P0 | A | Full |
| NFR-NOTIF-010 | Empty histories and guest sessions render gracefully with zero-count defaults | 0 crashes | P1 | F | Full |

### 5.1 Freshness and Isolation

#### NFR-NOTIF-001 — Invalidation that keeps pace with clicks

At SMKN 1 Gesi a mentor marked a placement update read, switched tabs, and saw the bell still
lit, so she opened the center again and re-read the same row. The one-request window exists to
kill that exact distrust loop. Because every state change flows through Actions that emit and
forget together, the bell refreshes on the very next render, and the sixty-second TTL only
covers idle navigation where nothing changed.

#### NFR-NOTIF-002 — Two layers between users

A single ownership check is one refactor away from disappearing, which is why the policy gate
and the query scope both enforce the same boundary. The policy rejects direct model access from
another user, while the center's base query makes cross-user rows unreachable even before
authorization runs. Either layer alone would pass today's tests, but the pair survives the
future edit that weakens one of them.

#### NFR-NOTIF-003 — Delivery failures stay quiet by design

A notification about a deactivated account must never break the import or publish flow it
annotates. The silent skip converts what would be a batch-killing null dereference into a
non-event, and the absence of an exception is itself the contract under test. Genuine payload
bugs still surface through the SmartLogger warning from the channel mapping step, so quietness
here never hides developer error.

### 5.2 Storage and Idempotency

#### NFR-NOTIF-004 — Category keys with a hard ceiling

Unbounded category strings once threatened the composite index with bloat and made filtering
unpredictable across modules. The fifty-character ceiling keeps the column narrow, the index
fast, and category names deliberate. Longer taxonomy belongs in the structured payload bag, not
in the key the center filters on.

#### NFR-NOTIF-005 — Retries that change nothing twice

Mobile networks around partner workshops drop and retry requests routinely, and double taps are
the norm on small screens. Treating the second mark-as-read as a successful no-op keeps the
timestamp honest — it records when the user first read, not when the network retried — and keeps
event-driven cache work from doubling. The test simply marks twice and asserts a single write.

### 5.3 Presentation and Structure

#### NFR-NOTIF-006 — Every string through the translation helper

A hardcoded English fallback in the bell once shipped to an Indonesian-only school and stayed
for a semester because no test caught it. Routing center labels, bell text, and mail subjects
through the helper with mirrored language files makes the missing-key scan catch the next lapse
before release. Dynamic fragments like names and company titles travel as placeholders, never as
concatenation, so translators see whole sentences.

#### NFR-NOTIF-007 — Bell present wherever a signed-in user stands

The bell lives in the shared authenticated layout rather than per-page includes, so a new page
cannot forget it. Coverage is verified by a browser journey that visits the center, the
dashboard, and profile pages asserting the bell element on each. Guests see the layout without
the count, which keeps the component from ever leaking one user's number into another session.

#### NFR-NOTIF-008 — Strict typing across the subsystem

A loose-typed recipient id once coerced an empty string into a zero that matched no user yet
passed validation, producing a silent non-delivery nobody could reproduce. Declaring strict
types in every class of this subsystem turns that class of accident into an immediate, visible
failure during tests. The convention scan enforces the declaration so future files cannot opt
out quietly.

#### NFR-NOTIF-009 — One registered key, no inline strings

Three different inline spellings of the unread-count key once coexisted, and invalidation fixed
only the spelling it knew. The single registry entry ends that drift: components and listeners
resolve the same prefix from configuration, and a prefix migration touches one line. The scan
that forbids ad-hoc cache strings elsewhere applies here with full force.

#### NFR-NOTIF-010 — Graceful emptiness on both ends of auth

A new student with zero notifications and a logged-out visitor share a requirement: the bell
area must render without errors and without placeholder counts. The empty center shows its
designed empty state with guidance toward the bell, while the guest bell renders zero without
querying. Both paths are covered because they are the first impression each audience gets of
the subsystem.

---

## 6. API / Data Contracts

Non-negotiable precision — precise enough to implement against without asking.

### 6.1 SendsNotifications Contract

```php
// app/Modules/Core/Contracts/SendsNotifications.php
interface SendsNotifications
{
    public function execute(NotificationData $data): mixed;
}
```

### 6.2 NotificationData DTO

```php
// app/Modules/Core/Channels/Data/NotificationData.php
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

### 6.3 CustomDatabaseChannel

```php
// app/Modules/Core/Channels/CustomDatabaseChannel.php
class CustomDatabaseChannel
{
    public function __construct(protected readonly SendsNotifications $sendNotification) {}
    public function send(mixed $notifiable, Notification $notification): void;
}
```

### 6.4 SendNotificationAction

```php
// app/Modules/User/Domain/Notifications/Actions/SendNotificationAction.php
final class SendNotificationAction extends BaseCommandAction implements SendsNotifications
{
    public function execute(NotificationData $data): Notification;
}
```

### 6.5 Notification Model and Schema

```php
// app/Modules/User/Domain/Notifications/Models/Notification.php
#[Fillable(['user_id', 'type', 'title', 'message', 'data', 'link', 'is_read', 'read_at'])]
class Notification extends BaseModel
{
    public function user(): BelongsTo;
}
```

```php
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

### 6.6 State-Change Actions

```php
final class MarkAsReadAction extends BaseCommandAction
{
    public function execute(Notification $notification): Notification;
}

final class MarkAllAsReadAction extends BaseCommandAction
{
    public function execute(string $userId): int;
}

final class MarkBatchAsReadAction extends BaseCommandAction
{
    public function execute(string $userId, array $ids): int;
}

final class DeleteNotificationAction extends BaseCommandAction
{
    public function execute(Notification $notification): void;
}
```

### 6.7 Events and Cache Invalidation

```php
final class NotificationSent extends BaseEvent
{
    public function __construct(public Notification $notification) {}
    public function eventName(): string { return 'notification.sent'; }
}

final class NotificationRead extends BaseEvent
{
    public function __construct(public Notification $notification) {}
    public function eventName(): string { return 'notification.read'; }
}

final class ClearUnreadNotificationCache
{
    public function handle(NotificationSent|NotificationRead|ProfileUpdated $event): void;
}
```

### 6.8 Notification Payload Shape

```php
[
    'type'    => 'assignment_published', // required, max 50 chars
    'title'   => 'New assignment posted', // required
    'message' => '...',                   // optional
    'link'    => '/assignments/…',        // optional
    'data'    => ['assignment_id' => '…'], // optional
]
```

### 6.9 Cache Key

```php
// config/cache-keys.php
'notification_unread' => 'notification.unread:', // suffix userId
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence; per QLHDO these are recorded decisions, not test rows.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-NOTIF-001 | Persist through a custom database channel with structured columns instead of Laravel default storage | P0 | — | — |
| DD-NOTIF-002 | Cache the bell count with a 60-second TTL and invalidate through sent and read events | P0 | — | — |
| DD-NOTIF-003 | Route all persistence through the SendsNotifications contract instead of direct model writes | P0 | — | — |
| DD-NOTIF-004 | Authorize by ownership for reading and by admin role for system create and delete | P0 | — | — |
| DD-NOTIF-005 | Touch no model observers for notification state; use after-commit events that discard on rollback | P0 | — | — |
| DD-NOTIF-006 | Queue every non-critical notification listener so fan-out never blocks the request | P1 | — | — |

### 7.1 Channel and Delivery Shape

#### DD-NOTIF-001 — Structured columns instead of an opaque blob

Laravel's default channel was built for a generic-needs audience, and its opaque payload showed
it the first week the center tried to filter by category. Structured columns gave the center
indexed filtering and plain-text search with ordinary queries, at the cost of one small mapping
class plus its contract. The single mail-only exception proves the boundary: anything that never
needs listing never pays the mapping cost.

#### DD-NOTIF-002 — Short TTL with event-driven freshness

A longer dashboard-style TTL would have made the bell feel stuck after every read, while no
caching at all taxed every navigation. Sixty seconds with explicit invalidation on every state
change splits the difference: idle browsing stays cheap, active triage stays truthful. The
residual risk of a cache-store restart briefly zeroing the count was accepted because the next
event or expiry repopulates it without intervention.

### 7.2 Contracts and Authorization

#### DD-NOTIF-003 — Contract as the only door to storage

Allowing the channel to write rows directly felt simpler until two modules diverged on title
lengths and only one of them logged. Funneling every write through the single Action restored
one validation rule, one log line, and one event emission for the whole subsystem. The extra
interface reads as ceremony in isolation, yet it is what lets tests fake sending while feature
tests assert the real persistence path end to end.

#### DD-NOTIF-004 — Ownership first, admin exception narrow

Notifications describe one person's placements, grades-adjacent updates, and account events, so
the default is strict ownership with no cross-user viewing even for privileged roles. The narrow
administrative exception covers system-generated rows only, which matches how schools actually
operate: admins manage the system, they do not read students' inboxes. Widening that exception
later would need its own spec amendment precisely because the current narrowness is a privacy
promise.

### 7.3 Event and Queue Posture

#### DD-NOTIF-005 — Events, never observers, for notification state

An observer on the notification model fires inside the originating transaction, which means a
cache forget or a mail dispatch could run for a row that later rolls back. The event pair leaves
after commit instead, so rollback discards the fan-out and the bell never reflects phantom rows.
The observer criteria from the governing ADR also fail here on scope: notification side effects
span modules and need queuing, both of which observers cannot provide. Synchronous model hooks
remain reserved for single-model guards and same-request invalidation elsewhere.

#### DD-NOTIF-006 — Queue as the default for slow side effects

Holding the publish request open while hundreds of mail sends completed was the slowest page in
the system during pilot term. Marking mail, broadcast, and secondary listeners as queued moved
that work off the request while keeping the in-app row synchronous, so the center is correct
immediately and the mailbox catches up within seconds. Anything the next paint depends on stays
synchronous by rule, and everything else rides the queue without further debate.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Bell reflects a read or send | Within 1 request cycle | Manual triage journey + feature test on Actions |
| Cross-user notification leakage | 0 incidents | Policy tests + query-scope review |
| Domain modules covered | Auth, User, Program, Assignment, Incident, SysAdmin each dispatch at least one class | Code inventory vs §6.8 payload keys |
| Queued delivery for bulk fan-out | Admin publish returns while mail drains in background | Queue fake in publish test + timed manual run |
| Localization gaps | 0 hardcoded strings | D3 scan on center, bell, and mail views |
| Cache-key discipline | Single registered key, no inline strings | Cache-key scan + config review |

---

## 9. Roadmap

### Prerequisites

| Spec | What it provides |
|------|-----------------|
| [Base Classes](SE5Q9-base-classes.md) | `BaseCommandAction`, `BaseEvent`, `BaseData`, `BaseModel`, `BasePolicy` |
| [Event System](NUCY3-event-system.md) | `BaseEvent` contract, dispatch and listener registration |
| [Authentication](YB7RG-authentication.md) | `User` model with `Notifiable`, `LoginSucceeded` event |
| [RBAC & Authorization](T4B26-rbac-and-authorization.md) | `isAdmin()` helper, role gating |
| [Settings Infrastructure](YB22J-settings-infrastructure.md) | Settings lookup used by security mail content |

### Build Guide

Build the contract and the sending Action first, then the model and migration, then the
state-change Actions with their events, then the center and bell against faked events, and
finally the domain notification library one module at a time. Wire registration, assignment, and
incident dispatch only after the center renders correctly in isolation.

### Next Steps

| Order | Spec | Connection |
| ----- | ---- | ---------- |
| 1 | [Announcement System](3S55V-announcement-system.md) | Broadcasts through this backbone via `AnnouncementNotification` |
| 2 | [Dashboard](CKKZC-dashboard.md) | Surfaces recent unread items as role widgets |
| 3 | [Job & Queue Infrastructure](8FVZA-job-queue-infrastructure.md) | Carries the queued fan-out this spec assumes |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume school notification volume stays in the tens per user per term, so no grouping, threading, or digest infrastructure is needed at MVP | Accepted | Maintainer | — |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all 62 feature specs + 2 meta, grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — module-first 4-layer model and Action Triad
- [Event System](NUCY3-event-system.md) — `BaseEvent` contract and listener conventions
- [Announcement System](3S55V-announcement-system.md) — largest consumer of this backbone
- [ADR: Cross-module communication](../adr/adr-cross-module-communication.md) — ranked-hierarchy rationale
- [ADR: Eloquent observers](../adr/adr-eloquent-observers.md) — observer-vs-event criteria
- [ADR: SmartLogger dual-channel](../adr/adr-smartlogger-dual-channel.md) — PII masking and channel routing
- [ADR: MVP spec trim](../adr/adr-mvp-spec-trim.md) — what was consolidated out of this spec
