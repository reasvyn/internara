# 81SMS — School Profile

> **Spec ID:** 81SMS
> **Status:** Full
> **Owner:** Academics
> **Depends on:** YB22J

## Description

Internara's school profile: the single-tenant school's identity (name, institutional code,
contacts, address, website, principal) stored as individual `Setting` records under the
`school.*` namespace, read through a typed `SchoolEntity`, written atomically through the
settings batch pipeline, and edited in the `SchoolEditor` page with live logo handling.
Departments and academic years scoped to this school are specified in
[department-management.md](4HWSB-department-management.md) and
[academic-year-management.md](XW6F5-academic-year-management.md).

---

## 1. Problem Statements

### PS-1 — One School, No Table

The deployment serves exactly one school, whose profile is eight strings — yet those strings
appear on certificates, reports, official documents, and the landing page. A dedicated
`schools` table for a singleton row would duplicate the caching, typing, and invalidation the
settings store already provides, while splitting reads between two systems every consumer
must then reconcile.
**→ Requirement:** FR-SCH-001/002 (entity over settings), DD-SCH-001 (no dedicated model).

### PS-2 — String Keys Do Not Belong in Business Code

Without a typed accessor, every certificate template and report query would call
`setting('school.principal_name')` from memory — misspellings rendering blank on official
documents, discovered only when a principal holds a misprinted certificate. Named accessors
turn that runtime embarrassment into a method the IDE autocompletes.
**→ Requirement:** FR-SCH-005 (accessors), FR-SCH-004 (single batch read).

### PS-3 — Half-Saved Identities Are Worse Than Stale Ones

A profile update touches eight keys; a failure after key five leaves the name new but the
email old, and outgoing letters carry the mismatch for weeks before anyone notices. Either
the whole profile lands or none of it does.
**→ Requirement:** FR-SCH-006/007 (atomic save), DD-SCH-003 (batch reuse).

### PS-4 — Cached Identity Must Track the Save

The entity resolves from cached settings for speed, which makes a save without invalidation
a lie the system tells itself: the admin sees the confirmation toast while every subsequent
page renders yesterday's address. Invalidation must be synchronous with the write.
**→ Requirement:** FR-SCH-014/015 (registry and sync invalidation).

---

## 2. Goals & Non-Goals

### Goals

- **Singleton identity on the settings store** — eight `school.*` keys, no dedicated model or migration. *Why:* PS-1 forbids duplicating infrastructure for one row.
- **Typed reads through a readonly entity** — named accessors, single batch query, purity from framework I/O. *Why:* PS-2 replaces string-key spelunking with autocompleted methods.
- **Atomic profile saves** — all keys commit together inside the batch transaction. *Why:* PS-3 makes partial identity corruption the failure to prevent.
- **Synchronous entity-cache invalidation** — the next read after any save is fresh. *Why:* PS-4 turns stale identity into a correctness bug.
- **Live logo handling independent of the form** — immediate upload, confirmed removal, always-accurate preview. *Why:* file failures must never block or corrupt textual saves.

### Non-Goals

- **Multi-school or multi-tenant support**. *Why:* single-tenant by product decision; one school per deployment, no scoping columns.
- **Logo generation, cropping, or resizing beyond media conversions**. *Why:* the media library's conversions suffice; an image editor is a different product.
- **Profile versioning, audit trail, or rollback UI**. *Why:* settings audit logging covers forensics; a versions interface is post-MVP depth.
- **Settings machinery, branding, theme, or locale**. *Why:* owned by [YB22J](YB22J-settings-infrastructure.md) and [52O1I](52O1I-branding-theme-locale.md); this spec consumes both.
- **Departments and academic years**. *Why:* separate specs own those entities; they reference this profile, not vice versa.

---

## 3. User Stories / Use Cases

One editor, three interactions: save the identity, change the face, remove the face. Each
carries its own consistency demand.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SCH-001 | Admin updates the school profile fields atomically with cache invalidation and dirty-state handling | P0 | F | Full |
| UC-SCH-002 | Admin uploads the school logo with immediate preview independent of the profile save | P0 | F | Full |
| UC-SCH-003 | Admin removes the school logo through a confirmation step with full cleanup | P1 | F | Full |

### 3.1 Profile Editing

#### UC-SCH-001 — Admin rewrites the school's identity

The new principal's name arrives on a Monday memo and the admin opens `/admin/school` to find
all eight fields already populated from the entity — name, institutional code, email, phone,
fax, address, website, principal. She edits two, and the Alpine dirty tracker arms itself on
first keystroke, guarding against accidental navigation. Save clears that guard before the
request leaves (so the save itself never triggers its own warning), validates, funnels the
payload through the atomic batch write, forgets the entity cache, and reloads the form from
fresh reads — the confirmation toast and the re-rendered fields agreeing, dirty flag at rest.

### 3.2 Logo Lifecycle

#### UC-SCH-002 — The crest lands before the form is saved

Certificates go out Friday and the crest file finally arrives Wednesday. The admin drops it
into the logo field and the preview swaps to the upload before she touches save — the
Livewire hook authorizes, validates image and size, stores through the media library,
persists the URL setting, and flashes confirmation, all outside the profile transaction. When
she later saves the textual fields, the logo is already live on every document template; had
the upload failed, the text save would have proceeded untouched rather than failing with it.

#### UC-SCH-003 — Removing the crest takes two deliberate clicks

A rebrand retires the old crest. The admin clicks remove, a confirmation modal asks whether
she means it — because this click deletes bytes, not just a pointer — and confirming runs
the removal action, clears the setting key, and empties the preview slot. There is no undo
beyond re-uploading, which is exactly why the modal exists: destructive, immediate, and
never accidental.

---

## 4. Functional Requirements

Entity and reads (§4.1), atomic writes (§4.2), form and validation (§4.3), the Livewire page
(§4.4), and cache plus routing (§4.5) form the complete surface. Every row below is
implemented.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) ·
`A` = Arch (structure/contracts). **Status legend:** `Planned` = not started ·
`Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SCH-001 | `SchoolEntity` is a `final readonly` value object extending `BaseEntity` with eight typed string properties including `fax` | P0 | U | Full |
| FR-SCH-002 | `SchoolEntity::KEYS` maps each property to its `school.*` setting key as the single source of truth | P0 | U | Full |
| FR-SCH-003 | `SchoolEntity` stays pure with no settings imports; hydration flows through `fromSettingsArray()` fed by callers | P0 | A | Full |
| FR-SCH-004 | `GetSchoolEntityAction` performs the single batch read of all eight keys and hydrates the entity; it is the only place that queries school keys | P0 | F | Full |
| FR-SCH-005 | `SchoolEntity` exposes named accessors for all eight properties with empty-string defaults | P0 | U | Full |
| FR-SCH-006 | `SaveSchoolProfileAction` accepts the payload plus an optional logo file and delegates to `BatchSetSettingAction` for atomic upsert with logging | P0 | F | Full |
| FR-SCH-007 | Profile writes execute inside the command transaction: all eight keys land or none do | P0 | F | Full |
| FR-SCH-008 | Logo bytes flow through `UploadBrandAssetAction` and removals through `RemoveBrandAssetAction` with setting cleanup | P0 | F | Full |
| FR-SCH-009 | `SchoolForm` carries all eight properties, loads from an injected entity, and serializes via `toPayload()` | P0 | F | Full |
| FR-SCH-010 | Form rules require the school name and validate contacts with type-specific rules; rules shared via `Entity::rules()` where a second form edits the same entity | P0 | F | Full |
| FR-SCH-011 | `SchoolEditor` mounts and saves through injected actions with policy authorization, form reload from fresh reads, success flash, and a `saved` event resetting dirty state | P0 | F | Full |
| FR-SCH-012 | Live logo upload validates and previews immediately; removal requires confirmation; save never triggers the unsaved-changes guard | P1 | F | Full |
| FR-SCH-013 | `logoPreviewUrl()` returns the pending-upload temporary URL or the current logo URL | P2 | F | Full |
| FR-SCH-014 | The `school_entity` cache key is registered in `config/cache-keys.php` as `academics.school.entity` | P0 | A | Full |
| FR-SCH-015 | Entity cache invalidates synchronously after every write; cached resolution completes within budget | P0 | F | Full |
| FR-SCH-016 | The editor lives at `GET /admin/school` (name `sysadmin.school`) behind `auth` plus `role:super_admin\|admin` with all mutations policy-authorized | P0 | F | Full |

### 4.1 Entity and Reads

#### FR-SCH-001 — Eight strings in an immutable shell

The entity holds name, institutional code, email, address, phone, fax, website, and principal
name — all strings, `final readonly`, extending `BaseEntity`. Immutability is doing quiet
work here: no consumer can "fix up" the school name on a local copy and diverge from the
store, because there are no setters to call. The `fax` field survives on lineage rather than
usage — legacy school letterheads still carry fax numbers, and dropping the column would
orphan data the setup wizard once collected.

#### FR-SCH-002 — One constant maps properties to keys

`KEYS` pairs each property name with its `school.*` key exactly once, and every other
consumer — the batch reader, the save mapper, the setup seeder — iterates that constant
instead of naming keys. When a key rename becomes necessary, the diff touches one constant
and the tests around it, not a grep across modules hoping every string literal was found.

#### FR-SCH-003 — Purity by construction, not by review

The entity imports nothing from the settings module — no service, no model, no helper — so
the forbidden-dependency question never reaches code review; the class simply cannot reach
what it cannot import. Hydration arrives as a plain array through `fromSettingsArray()`,
which keeps the entity instantiable in millisecond unit tests with hand-built arrays. The
legacy `get()` convenience delegates to the read action without a `use` import, deprecated
for new code that should inject the action directly.

#### FR-SCH-004 — One action reads, everyone else asks it

Before this action existed, three different call sites each queried school keys in their own
way — different key lists, different caching, different hydration. `GetSchoolEntityAction`
collapses that to one batch `Settings::get()` over the `KEYS` values and one hydration call,
so the query count for "who is this school" is exactly one, everywhere, forever. Livewire
components inject it rather than touching the entity's static conveniences, which keeps the
read path dependency-injected and test-seeable.

#### FR-SCH-005 — Accessors with graceful absence

Each of the eight accessors returns a string, defaulting to empty when the setting was never
configured — a fresh install renders blank fields rather than null explosions in certificate
templates. The contract callers rely on is totality: call any accessor on any entity, get a
string back, no exceptions, no null checks at the call site. Missing configuration shows as
emptiness in the UI, which is honest and debuggable.

### 4.2 Atomic Writes

#### FR-SCH-006 — The save action as a thin mapper

`SaveSchoolProfileAction` does deliberately little: prefix each payload key with `school.`,
wrap in entry data, hand the bundle to the batch action, handle the optional logo through
the brand-asset pipeline, forget the entity cache, and log the update with affected keys.
Thinness is the design — transactions, type detection, and observer hooks already live in
the batch path, so this action reuses rather than reimplements. Its constructor-injected
collaborators make the whole flow assertable in feature tests without touching HTTP.

#### FR-SCH-007 — Eight keys or zero

The transaction wraps the batch upsert so a database failure after key five rolls back keys
one through four — the profile the next reader sees is either fully new or fully old, never
a chimera. Observers fire inside the transaction, preserving the rollback-undoes-everything
property the observer ADR requires. The scenario this prevents (new name, stale email on
official letters) is precisely the kind of silent corruption no test would catch after the
fact, so the guarantee sits in the write path itself.

#### FR-SCH-008 — Bytes and pointers cleaned together

Logo upload stores through the media collection and persists the returned URL; removal
deletes the media row and forgets the setting key in the same handling. Either half alone
is a bug with a visible symptom — orphaned files nobody references, or a setting pointing
at bytes that no longer exist and rendering broken images on certificates. The pairing is
enforced in the action rather than trusted to caller discipline.

### 4.3 Form and Validation

#### FR-SCH-009 — The form mirrors the entity exactly

Eight properties, same names modulo snake case, loading from an injected entity and
serializing back through `toPayload()` with the same key suffixes. Symmetry between load
and payload is what makes round-trips lossless: a profile loaded, untouched, and saved
writes back identical values. The form never resolves the entity itself — callers inject
the read action and pass the result in — which keeps the class free of settings imports and
testable with fabricated entities.

#### FR-SCH-010 — Required name, validated contacts, shared rules

Only the name is required; a school without a name is not a school but a database accident,
while every contact field tolerates absence because half-configured installs are normal
mid-setup. Email and website carry type-specific rules so malformed values fail at the form
rather than on printed letterhead. Where a second surface edits the same fields — the setup
wizard demanding email upfront while this editor allows clearing it later — the shared rules
live once as `Entity::rules()` per the gradual-migration trigger, and the wizard's stricter
variant layers its requirement on top without forking the base.

### 4.4 The Livewire Page

#### FR-SCH-011 — Mount, save, and reset through the base view

Mount authorizes against the `Setting` policy and hydrates the form from the injected read
action; save authorizes again, validates, delegates through the base view's save handler,
reloads from fresh reads, flashes success, and dispatches `saved` so the Alpine dirty flag
stands down. The double authorization (mount and save) closes the crafted-request gap where
a client skips the page and calls the save directly. Reloading from fresh reads rather than
echoing submitted values proves the write actually landed instead of assuming it.

#### FR-SCH-012 — Uploads now, confirmation for destruction, no self-blocking guard

Three interaction details share one theme: the UI must never punish the operator for its own
bookkeeping. Logo selection uploads and validates immediately with a live preview; removal
pauses on a confirmation modal because it destroys bytes; and the unsaved-changes guard
clears on submit before the request dispatches, so saving can never trigger the very dialog
meant to catch navigation away. Each behavior was fixed in response to a real complaint —
the guard blocking its own save most memorably — rather than speculated upfront.

#### FR-SCH-013 — Preview prefers the pending file

While an upload sits selected but unconfirmed, the preview shows its temporary URL; once
settled, it shows the persisted logo; with neither, it shows nothing rather than a broken
frame. The precedence order matters because the operator's question is always "what will it
look like" — answered by the freshest available image, not by the last saved one.

### 4.5 Cache and Routing

#### FR-SCH-014 — The entity key in the registry

`school_entity` → `academics.school.entity` sits alongside every other settings key in
`config/cache-keys.php`, referenced by config path rather than literal. Registration is what
lets the nightly key audit assert completeness: any cache key the school profile touches
appears in exactly one file, greppable, with no string literal hiding in an action method.

#### FR-SCH-015 — Synchronous invalidation within budget

Every profile write forgets the entity key before the response returns — through the model
observer for settings-path writes and explicitly in the save action for the entity key —
so the confirmation toast and the next render cannot disagree. Cached resolution stays
under fifty milliseconds on hit; a slower hit means something bypassed the cache with a
direct query, which the read-path discipline (FR-SCH-004) already forbids.

#### FR-SCH-016 — Route and dual-layer authorization

`GET /admin/school`, named `sysadmin.school`, behind `auth` and `role:super_admin|admin` —
reachable by both operator roles, like the settings page it neighbors. The middleware gate
keeps anonymous and student traffic out; the `Setting` policy check inside mount and every
mutation keeps authorization at the business layer too, so direct action invocation without
the role meets denial rather than compliance.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SCH-001 | School setting keys match the key pattern to prevent injection | zero non-matching keys | P0 | A | Full |
| NFR-SCH-002 | Logo uploads validate MIME type and size server-side | 100% gated; `image\|max:2048` | P0 | F | Full |
| NFR-SCH-003 | Editor mutations authorize via the `Setting` policy at mount and on every mutation | 100% of mutations gated | P0 | F | Full |
| NFR-SCH-004 | Profile saves are atomic across all eight keys | zero partial saves | P0 | F | Full |
| NFR-SCH-005 | Cache invalidation is synchronous, never queued | zero stale reads after save | P0 | F | Full |
| NFR-SCH-006 | Logo upload previews live; removal requires confirmation; flashes confirm save, upload, and removal | 100% of flows acknowledged | P1 | B | Full |
| NFR-SCH-007 | Form inputs carry labels, uploads carry alt text, flashes announce via live regions | WCAG 2.1 AA on editor controls | P1 | B | Full |
| NFR-SCH-008 | Every editor string uses `__()` with mirrored `en`/`id` keys; consumers read via the entity, not raw setting calls | zero missing-key pairs; zero raw school-key reads | P0 | A | Full |
| NFR-SCH-009 | Cached entity resolution and save complete within budget | < 50ms hit; < 2s p95 save | P1 | F | Full |

### 5.1 Integrity and Access

#### NFR-SCH-001 — Keys that cannot smuggle syntax

The `school.*` keys travel into cache-key construction and batch lookups, so the lowercase
dotted pattern is load-bearing rather than cosmetic. A key with uppercase or whitespace
would cache under a namespace no invalidation clears — the exact stale-forever shape the
observer exists to prevent. Non-matching keys in the table are rename candidates, never
precedent.

#### NFR-SCH-002 — The 2 MB ceiling with teeth

Two megabytes admits any reasonable crest photograph while refusing print-shop TIFFs that
would bloat every certificate render. Enforcement runs on the stored bytes server-side; the
client `accept` attribute is courtesy, not control. An oversized file fails with a field
error while the textual form values wait patiently — the independence UC-SCH-002 promises.

#### NFR-SCH-003 — Authorization in two places on purpose

Middleware turns away the wrong roles at the door; the policy check inside mount and each
mutation turns away the right role calling the wrong way — a crafted Livewire payload that
skips the page entirely. Either layer alone leaves a gap the other closes, which is why the
dual-layer requirement from the project spec lands here as concrete checks rather than
aspiration.

### 5.2 Reliability and Experience

#### NFR-SCH-004 — Atomicity as a letterhead guarantee

The printed letterhead is the artifact that makes partial saves unforgivable: mismatched
name and email persist on paper long after the database is corrected. Transactional
all-or-nothing writes make that state unreachable, and the feature tests prove it by
failing mid-batch and asserting the earlier keys rolled back.

#### NFR-SCH-005 — Freshness before the toast fades

By the time the admin reads the success flash, the next navigation already renders — and it
must render the new profile, not the cached old one. Synchronous invalidation buys that
ordering unconditionally; a queued clearer would trade correctness for write-path speed the
school never asked for, since profile saves happen a few times a year, not a thousand times
a day.

#### NFR-SCH-006 — Every action answers visibly

Saves, uploads, and removals each end in a flash message; uploads additionally preview
without reload and removals pause on confirmation. The unifying demand is operator
confidence: no click should leave the admin wondering whether anything happened. Silent
success is treated as a defect here, not as minimalism.

### 5.3 Access and Localization

#### NFR-SCH-007 — An editor operable beyond the mouse

Labelled inputs, alt-texted upload previews, and live-region announcements make the editor
usable by keyboard and screen reader — the AA behaviors that matter most on a form the
school revisits rarely enough that nobody memorizes it. These live in markup rather than
component styling so they survive the next UI library migration untouched.

#### NFR-SCH-008 — Two languages, one read path

Indonesian and English operators edit the same profile through fully translated chrome,
with every key present in both locale files. Meanwhile consumers never call
`setting('school.*')` directly — the entity is the read path — so a future key rename
touches the constant and its tests instead of every certificate template in the system.

#### NFR-SCH-009 — Budgets that fit the usage shape

Fifty milliseconds on cache hit keeps the entity invisible inside page renders that read it
multiple times; two seconds at p95 for the full eight-field save keeps the admin waiting
comfortably, uploads excluded (they travel the immediate path). Breaching either budget
points at a bypassed cache or a bundled upload — both already forbidden elsewhere in this
spec, which is how performance requirements compose instead of repeating.

---

## 6. API / Data Contracts

### 6.1 SchoolEntity

```php
final readonly class SchoolEntity extends BaseEntity
{
    private const array KEYS = [
        'name'               => 'school.name',
        'institutional_code' => 'school.institutional_code',
        'email'              => 'school.email',
        'address'            => 'school.address',
        'phone'              => 'school.phone',
        'fax'                => 'school.fax',
        'website'            => 'school.website',
        'principal_name'     => 'school.principal_name',
    ];

    public function __construct(
        private string $name,
        private string $institutionalCode,
        private string $email,
        private string $address = '',
        private string $phone = '',
        private string $fax = '',
        private string $website = '',
        private string $principalName = '',
    ) {}

    public static function keys(): array;
    public static function fromSettingsArray(array $values): self;
    public static function fromModel(Model $model): static;
    public static function get(): self;
    public function name(): string;
    public function institutionalCode(): string;
    public function email(): string;
    public function address(): string;
    public function phone(): string;
    public function fax(): string;
    public function website(): string;
    public function principalName(): string;
}
```

`fromModel()` delegates to `get()` (no model dependency). `get()` is legacy compat
delegating to `GetSchoolEntityAction`; new code injects the action. All accessors return
`string`, defaulting to `''`.

### 6.2 Actions

```php
final class SaveSchoolProfileAction extends BaseCommandAction
{
    public function __construct(
        protected readonly BatchSetSettingAction $batchSetSetting,
        protected readonly UploadBrandAssetAction $uploadBrandAsset,
    ) {}

    public function execute(array $data, ?UploadedFile $logoFile = null): void;
}

final class GetSchoolEntityAction extends BaseReadAction
{
    public function execute(): SchoolEntity;
}
```

`GetSchoolEntityAction::execute()` is the only place querying school keys: one batch
`Settings::get(array_values(SchoolEntity::keys()))` hydrated via `fromSettingsArray()`.

### 6.3 SchoolForm Rules

| Field | Rules |
| ----- | ----- |
| `name` | `required\|string\|max:255` |
| `institutional_code` | `nullable\|string\|max:50` |
| `email` | `nullable\|email\|max:255` |
| `phone` | `nullable\|string\|max:50` |
| `fax` | `nullable\|string\|max:50` |
| `address` | `nullable\|string\|max:500` |
| `website` | `nullable\|url\|max:255` |
| `principal_name` | `nullable\|string\|max:255` |

`email` is `nullable` here but `required` in the setup wizard
([VEJCX](VEJCX-setup-wizard.md)): the wizard provisions a working contact address, the
editor allows clearing it later.

### 6.4 Setting Keys and Cache

| Key | Type | Default |
| --- | ---- | ------- |
| `school.name` | string | `''` |
| `school.institutional_code` | string | `''` |
| `school.email` | string | `''` |
| `school.address` | string | `''` |
| `school.phone` | string | `''` |
| `school.fax` | string | `''` |
| `school.website` | string | `''` |
| `school.principal_name` | string | `''` |

Cache: `school_entity` → `academics.school.entity`, forever. Route: `GET /admin/school`
(name `sysadmin.school`), middleware `auth`, `role:super_admin|admin`.

---

## 7. Design Decisions

Each decision records a settled tradeoff argued once in prose; the linked FR rows carry the
verifiable behavior, so no separate test layer is recorded here.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SCH-001 | School profile persists as `school.*` settings, not as a dedicated model and table | P0 | — | — |
| DD-SCH-002 | Reads flow through an immutable value object with a private key map | P0 | — | — |
| DD-SCH-003 | Saves reuse the settings batch action instead of a bespoke writer | P0 | — | — |
| DD-SCH-004 | Logo upload and removal run outside the profile transaction | P0 | — | — |
| DD-SCH-005 | The entity cache key joins the shared registry instead of living as a literal | P1 | — | — |

### 7.1 Representation

#### DD-SCH-001 — Eight strings do not deserve a table

A `schools` table with one row and eight string columns would need its own model, its own
cache story, its own invalidation, and an adapter everywhere the settings resolution chain
is expected — all to store what the key-value store already stores. The singleton shape is
exactly where a dedicated table pays the most ceremony for the least benefit, and the
singleton has no relations to justify Eloquent anyway. Settings rows with a typed reader
cover the need with zero new infrastructure.

#### DD-SCH-002 — Immutability plus a private map

The alternative — array access with string keys at every call site — was the pre-entity
reality, and it produced the misspelled-key-on-certificate incident PS-2 describes. A
readonly object with named accessors moves every keystroke under IDE and test visibility,
while the private constant keeps the property-to-key mapping in one owned place. No
setters exist because mutation has exactly one door: the save action.

### 7.2 Write Paths

#### DD-SCH-003 — Reuse the batch, don't re-own transactions

Writing a bespoke eight-key writer would duplicate transaction handling, type detection,
and observer triggering that the batch action already proves in tests. Delegation costs one
extra call frame and buys every future improvement to the batch path for free — including
invalidation semantics the school profile must never diverge from. The day the batch
action changes, the profile follows without a second migration.

#### DD-SCH-004 — Files fail differently from text

Bundling the logo bytes into the profile transaction couples two failure modes with
nothing in common: a 3 MB upload rejection should never discard eight valid text fields,
and a text validation error should never strand an uploaded file. Independent handling
lets each fail on its own terms with its own feedback, at the accepted cost of a possible
orphan file — small, local, and removable through the same removal action.

#### DD-SCH-005 — One registry for every key

A cache key literal inside the save action is invisible to the key audit and to the next
developer hunting a stale read. Registering `school_entity` in the shared config puts it
under the same greppability and bulk-invalidation story as every settings key, for the
price of one config line. The rule generalizes: any future entity cache starts in the
registry, never in a method body.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|----------------|
| Entity load on cache hit | < 50ms | feature-test timing |
| Entity load on cache miss | < 200ms | feature-test timing |
| Profile save (8 fields) | < 2s p95 | feature-test timing |
| Partial saves | 0 | transaction coverage |
| Saves without invalidation | 0 | observer coverage |
| Missing `en`/`id` key pairs | 0 | locale consistency scan |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [settings-infrastructure.md](YB22J-settings-infrastructure.md) (YB22J) | Typed store, `school.*` keys, batch writes, observer invalidation |

### Build Guide

With this spec implemented, the deployment knows who it serves: a named school with
contacts and a crest, readable in one cached call from any certificate, report, or
document template. Departments and academic years build outward from this identity.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [department-management.md](4HWSB-department-management.md) | Departments belong to this school's identity |
| 2 | [company-management.md](XI3LB-company-management.md) | Partners offer placements to this school's departments |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume one school per deployment for the lifetime of the instance; splitting an instance into two schools is unsupported and requires a fresh install | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Phase 2 Configuration specs and build order
- [Settings infrastructure](YB22J-settings-infrastructure.md) — the store, batch writes, and observer this spec reuses
- [Branding, theme & locale](52O1I-branding-theme-locale.md) — sibling consumer; logo pipeline shared via brand assets
- [Department management](4HWSB-department-management.md) — first consumer of the school identity
- [ADR: Entity-model separation](../adr/adr-entity-model-separation.md) — readonly entities and shared `rules()`
- [ADR: Eloquent observers](../adr/adr-eloquent-observers.md) — synchronous invalidation gates
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — validation-sharing trigger
