# YB22J — Settings Infrastructure

> **Spec ID:** YB22J
> **Status:** Full
> **Owner:** Settings
> **Depends on:** SE5Q9

## Description

Internara's settings infrastructure: a type-aware key-value store with a five-layer resolution
chain, synchronous observer-driven cache invalidation, the System Settings admin page, and the
settings CRUD pipeline. Brand identity, theme, and locale behavior built on top of this store are
specified separately in [branding-theme-locale.md](52O1I-branding-theme-locale.md); school identity
fields are specified in [school-profile.md](81SMS-school-profile.md).

---

## 1. Problem Statements

### PS-1 — Fresh Installs Must Work Before Any Admin Clicks Anything

A school technician runs the installer on shared hosting, opens the site, and expects a working
school portal — not a wall of missing-configuration errors. At the same time, a staging copy of
the same codebase needs different SMTP credentials without forking the database. Both demands
resolve to one mechanism: a cascade where runtime overrides win, then static app info, then the
cached database, then config files, then a hardcoded default.
**→ Requirement:** FR-SET-005 (resolution chain), FR-SET-006/007 (helpers), DD-SET-003 (precedence).

### PS-2 — One Store, Seven Shapes of Value

The same `settings` table holds a school year string, a mail port integer, an SMTP password that
must never sit in plaintext, and boolean feature flags. Callers passing native PHP values should
not hand-cast on every read; the storage layer itself must detect the type on write and return
the correctly cast value on read.
**→ Requirement:** FR-SET-002 (SettingType), FR-SET-003 (auto-detection), FR-SET-004 (encryption).

### PS-3 — Stale Settings Are Silent Corruption

An admin raises a partnership slot quota during enrollment week; the very next student request —
milliseconds later — must observe the new quota. Any invalidation path that defers past the
response leaves a window where the old value renders and a phantom slot gets taken.
**→ Requirement:** FR-SET-012 (registry), FR-SET-013 (synchronous observer), DD-SET-001 (3-gate
justification).

---

## 2. Goals & Non-Goals

### Goals

- **Five-layer resolution for every setting** — runtime overrides down to hardcoded defaults, so fresh installs boot with zero admin input. *Why:* PS-1 demands working defaults plus environment overrides.
- **Type-aware storage with auto-detection** — callers pass PHP values; the store detects and casts. *Why:* PS-2 removes manual casting across dozens of call sites.
- **Synchronous cache invalidation** — the next request after a write always reads fresh. *Why:* PS-3 makes stale reads a correctness bug, not a performance tradeoff.
- **One admin page for system settings** — general, branding, and mail sections behind a single save pipeline. *Why:* operators configure the school in one place with one audit trail.
- **Tier-1 file-cache behavior with zero external services** — the whole store runs on file cache and sync queue by default. *Why:* the self-hosted single-tenant decision forbids mandatory Redis at MVP.

### Non-Goals

- **Branding, theme, and locale UI behavior**. *Why:* owned by [52O1I](52O1I-branding-theme-locale.md), which consumes this store.
- **Per-user preferences**. *Why:* single-tenant product; per-browser cookies cover theme and locale without account-scoped settings rows.
- **Settings import/export tooling**. *Why:* post-MVP operational depth; one status view of current values suffices.
- **UI-based versioning or rollback of settings**. *Why:* audit log plus redeploy covers recovery; a versions UI is unambiguous post-MVP depth.

---

## 3. User Stories / Use Cases

Every settings interaction is either an admin writing values or any caller reading them; the
three stories below cover both directions plus the mail-configuration loop that cannot be
verified any other way.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-SET-001 | Admin saves system settings across general, branding, and mail sections in one atomic operation with synchronous cache invalidation | P0 | F | Full |
| UC-SET-002 | Admin verifies mail configuration by sending a test email without persisting untested credentials | P0 | F | Full |
| UC-SET-003 | Any caller resolves a setting or brand value through the helpers and always observes the freshest committed value | P0 | F | Full |

### 3.1 Admin Workflows

#### UC-SET-001 — Admin saves system settings

Enrollment week, 7 a.m. The admin opens `/admin/settings` and the `SystemSetting` component
hydrates three independent forms from `Settings::get()` — general contact fields, the four brand
colors, the SMTP block. She corrects the support address, pastes the new mail password, and hits
save. Behind the button, `SaveSystemSettingsAction` funnels every entry through
`BatchSetSettingAction` inside one transaction; `SettingObserver` clears the touched keys before
the response returns, so the confirmation toast and the very next page render already agree. If
she also switched the active academic year to one eligible for activation, the activation runs as
part of the same save and the dashboard reflects the new year immediately.

#### UC-SET-002 — Admin tests mail before trusting it

Nobody discovers a broken SMTP password from a form success message — they discover it when
two hundred placement notifications silently fail. So the mail section offers a test send that
temporarily swaps the runtime mail config, dispatches one message to the entered address, then
restores the previous config untouched. The admin learns the credentials work before any real
notification depends on them, and a failed test leaves zero residue in the persisted settings.

### 3.2 Consumption

#### UC-SET-003 — Callers read through the helpers

A Livewire table needs the support email; a Blade layout needs the brand name; a midnight queue
worker needs the mail host. None of them touch the `Setting` model directly. Each calls
`setting('support_email')` or `brand('name')` and receives the value the resolution chain
currently yields — runtime override first, database second, config and defaults behind. The
story that matters here is negative: the day after an admin renames the school, no template
anywhere still renders the old name, because reads flow through cached helpers the observer
already invalidated.

---

## 4. Functional Requirements

The store contract (§4.1–§4.2), the write pipeline (§4.3), cache behavior (§4.4), and the admin
surface (§4.5–§4.6) together form the complete settings surface. Every row below is implemented.

**Layer legend:** `U` = Unit (no DB) · `F` = Feature (real DB) · `B` = Browser (E2E) ·
`A` = Arch (structure/contracts). **Status legend:** `Planned` = not started ·
`Partial` = in progress · `Full` = implemented & verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-SET-001 | `Setting` model uses the `key` string column as primary key, grouped by a `group` column | P0 | F | Full |
| FR-SET-002 | `SettingType` enum supports `STRING`, `INTEGER`, `FLOAT`, `BOOLEAN`, `JSON`, `ENCRYPTED`, `NULL` with `detect()` and `cast()` | P0 | U | Full |
| FR-SET-003 | `SetSettingAction` validates the key pattern and auto-detects the storage type | P0 | F | Full |
| FR-SET-004 | `SettingValueCast` transparently encrypts and decrypts `ENCRYPTED` values via Laravel `Crypt` | P0 | F | Full |
| FR-SET-005 | Resolution chain precedence is fixed: runtime overrides → `AppInfo` → database (cached) → config file → hardcoded default | P0 | F | Full |
| FR-SET-006 | `setting($key, $default, $skipCache)` helper resolves through the full chain with the fixed signature | P0 | F | Full |
| FR-SET-007 | `brand($key, $default)` helper resolves dynamic branding with config and `AppInfo` fallback | P0 | F | Full |
| FR-SET-008 | Settings run on Tier-1 defaults with zero external services: file cache, sync queue, database session, local disk | P1 | F | Full |
| FR-SET-009 | `BatchSetSettingAction` executes all upserts inside a single database transaction | P0 | F | Full |
| FR-SET-010 | `DeleteSettingAction` removes a setting by key and triggers observer cache invalidation | P1 | F | Full |
| FR-SET-011 | `SaveSystemSettingsAction` accepts `SystemSettingsData` and delegates to the batch action inside a transaction, activating the academic year when eligible | P0 | F | Full |
| FR-SET-012 | Every cache key used by settings is registered in `config/cache-keys.php`; no ad-hoc key strings | P0 | A | Full |
| FR-SET-013 | `SettingObserver` invalidates per-key, per-group, and global keys synchronously on created, updated, and deleted events, including theme and brand keys for theme-related settings | P0 | F | Full |
| FR-SET-014 | `SystemSetting` page lives at `/admin/settings` behind `auth` plus `role:super_admin\|admin`, rendering general, branding, and mail sections with system info and logo sidebars | P0 | F | Full |
| FR-SET-015 | The three form objects validate independently and share rules with their Entity via `Entity::rules()` wherever the same entity is edited from two forms | P1 | F | Full |
| FR-SET-016 | `TestMailSettingsAction` sends a test email with temporarily swapped config and restores the previous config afterward | P0 | F | Full |
| FR-SET-017 | Feature flags live under the `features.*` namespace as `BOOLEAN` settings, readable via `feature($key, $default)`, documented in `config/settings.php`, writable only by `super_admin` | P1 | F | Full |
| FR-SET-018 | Color settings store validated hex strings and image settings store media URL strings, never raw binary | P1 | F | Full |

### 4.1 Typed Store

#### FR-SET-001 — String primary key with groups

Somewhere in the migration history sits the decision that shapes every query against this table:
no UUID, no auto-increment — the `key` column itself is the primary key. Reads are key lookups
by construction, and the `group` column (`general`, `mail`, `branding`, `features`, …) lets the
admin page and the invalidation logic reason about whole families of settings at once. A setting
without a group is a setting the observer cannot bulk-invalidate, which is why the group column
is load-bearing rather than decorative.

#### FR-SET-002 — Seven storage types

Picture the `detect()` method meeting each value for the first time: a hostname string stays a
string, `587` arrives as an integer, a port passed as `"587"` keeps its string skin, `true`
becomes boolean, an array serializes to JSON, a password flagged encrypted takes the `Crypt`
path, and null stays null. Seven cases, no more — the deliberately closed set is what keeps
`cast()` total, so every stored value round-trips to the type its writer intended.

#### FR-SET-003 — Validated keys, detected types

The story this row prevents is an admin typo becoming a phantom setting: `Mail.Host` with a
capital and a dot, silently stored, never read by anything. The key pattern `^[a-z][a-z0-9_.]*$`
rejects it at the Action boundary before a row exists. Valid keys flow into `SettingType::detect()`,
so the overwhelmingly common call — pass a PHP value, get correct storage — needs no type
annotation from the caller at all.

#### FR-SET-004 — Encrypted values through Crypt

An SMTP password in plaintext is a backup-file leak waiting to happen, and school servers get
backed up as plain file copies. The `ENCRYPTED` path runs the value through Laravel's `Crypt`
(AES-256-CBC) on write and decrypts transparently on read, so `setting('mail_password')` returns
usable plaintext to the mailer while the database row and every backup of it stay opaque. The
failure mode that matters: key rotation without re-encryption orphans these rows, which is why
the `APP_KEY` presence check in the health command exists downstream.

### 4.2 Resolution and Helpers

#### FR-SET-005 — Five-layer precedence

During a staging drill, the team overrides the mail host via runtime config while the database
still holds production credentials — and staging must send through the override, period. The
chain makes that outcome structural: runtime first, then the static `AppInfo` metadata, then
the cached database row, then the config file, then the hardcoded default. Fresh installs with
an empty settings table still boot because the bottom two layers always answer; production
customization wins because the database outranks config. Debugging "which layer answered" is
the known cost, paid for with the `$skipCache` escape hatch that forces a database read.

#### FR-SET-006 — The setting() signature

Every consumer in the codebase — Blade, Livewire, Actions, console commands — funnels through
one memorized shape: `setting(string|array|null $key = null, mixed $default = null, bool
$skipCache = false)`. Passing null returns the underlying `Settings` service for batch work;
passing an array resolves many keys in one cached pass. The signature is frozen because dozens
of call sites depend on its exact arity, and the QLHDO global contract pins it alongside
`brand()` and `app_info()`.

#### FR-SET-007 — The brand() signature

Brand reads look like setting reads but resolve through a different door: `Brand::get()` checks
database branding first, then config, then `AppInfo` static metadata, catching exceptions along
the way and falling back instead of blowing up a public page render. `brand('name')` therefore
never throws on a half-configured install — the worst case is the compiled-in school name, which
is exactly what an unauthenticated landing page should show while setup is still in progress.

#### FR-SET-008 — Tier-1 defaults, no external services

The $5-shared-hosting school gets the same settings behavior as the VPS school: file cache
holds the `rememberForever()` entries, the sync queue means no worker to supervise, sessions
persist in the database migration the installer already ran. Nothing in this spec requires
Redis, and promoting to Redis later is an `.env` swap precisely because every cache call goes
through the framework drivers rather than a bespoke client. The day an operator asks "do we
need Redis for settings," the answer stays no until Pulse evidence says otherwise.

### 4.3 Writes

#### FR-SET-009 — Atomic batch upserts

A full-page save touches eighteen properties across three groups; a crash after row nine would
leave branding half-applied and the mail block inconsistent. The batch action wraps every
upsert in one transaction so the save either fully lands or fully rolls back. Observers fire
per row inside that transaction, which keeps invalidation synchronous while still letting a
rollback undo the writes — the transaction-safety property the observer ADR demands.

#### FR-SET-010 — Deletion with invalidation

Deleting a setting is rare — usually a feature flag retired or a stale override removed — but
the read path caches aggressively, so a delete that skips invalidation resurrects the value
from cache indefinitely. The delete action therefore routes through the same model events as
every other write, and the observer's `deleted` hook clears the key, group, and global entries
exactly as an update would.

#### FR-SET-011 — One DTO for the whole page

The `SystemSettingsData` object is the page's single validation surface: eighteen typed
properties assembled from the three forms, handed to one Action, persisted through the batch
path. When the payload carries a changed `active_academic_year` eligible for activation, the
same save triggers the activation flow instead of leaving the year flag pointing at a year the
scheduler does not recognize. One transaction, one audit entry, one user-visible outcome.

### 4.4 Cache and Observer

#### FR-SET-012 — Registry-only cache keys

`settings.all`, `settings.group.{group}`, `settings.{key}`, `theme.css_variables`,
`brand.colors`, `academics.school.entity` — each lives as a named entry in
`config/cache-keys.php`, and the observer references the registry rather than string
literals. Greppability is the point: when a stale-read report arrives, the investigator
searches one config file and finds every key the settings system can possibly touch. A new
ad-hoc `Cache::forget('settings_foo')` anywhere in the codebase is a review-blocking violation.

#### FR-SET-013 — Synchronous invalidation, theme keys included

The observer hooks `created`, `updated`, and `deleted` on the `Setting` model and runs inline
in the writing request — never queued, never deferred past commit. Each hook clears the
per-key entry, the global `settings.all`, and the owning group's entry; keys listed in
`config/settings.php` under `theme_cache_keys` additionally clear `theme.css_variables` and
`brand.colors`, because a brand-color write that leaves the CSS cache warm renders yesterday's
palette until the hour-long TTL expires. Whether this synchronous coupling is justified is
argued once, in DD-SET-001, through the ADR's three gates.

### 4.5 Admin Surface

#### FR-SET-014 — The settings page and its gate

The route is `/admin/settings`, named `admin.settings`, guarded by `auth` and
`role:super_admin|admin`. The layout pairs the three editable sections — general, color,
mail — with a sidebar showing system info, the current logo, and the favicon, plus a floating
help button whose modal explains each setting in plain language. Creation and deletion of raw
setting rows stay `super_admin`-only; `admin` may view and update values, which is the RBAC
split the security NFR pins down.

#### FR-SET-015 — Independent forms, shared rules

Each of the three form objects validates on its own, so a malformed mail port never blocks
saving the support address — the page reports per-section errors and persists per-section
success. Wherever the same entity is reachable from two forms, the rules live once as
`Entity::rules()` and both forms reference them, which is the gradual-migration ADR's
Stabilize phase made concrete: duplication collapses to a single source the moment the second
form appears, not in a speculative upfront abstraction.

#### FR-SET-016 — Test mail without residue

Swapping the live mailer config for a test, sending one message, and restoring the original
is a maneuver that must not leak: an exception mid-send still restores, and nothing about the
test persists to the settings table. The admin gets a delivered-or-failed verdict on the
credentials currently in the form, which is the only verification that actually predicts
whether tomorrow's notifications will go out.

### 4.6 Flags, Colors, Images

#### FR-SET-017 — Feature flags as boolean settings

There is no flags table, no flags package, no second caching story. A flag is a `features.*`
boolean row with all the standard machinery — type enforcement, observer invalidation,
group filtering — plus a `feature('key')` helper that hides the namespace and coerces to
bool, and a `features` section in `config/settings.php` declaring each flag's default and
owning module so toggles stay discoverable. Only `super_admin` creates or mutates them;
`admin` reads. The day flags need targeting rules or percentages, they graduate out of this
store — until then the reuse is deliberate economy.

#### FR-SET-018 — Colors and images without new enum cases

A brand color is a six-digit hex string validated at the form layer against
`^#[0-9a-fA-F]{6}$`; a logo is a media-library URL string, never binary, with the bytes owned
by Spatie Media Library. Neither needs a storage-level type because neither has distinct
storage semantics — `detect()` sees only strings either way, and a dedicated `COLOR` case
would add cast plumbing for zero benefit while complicating detection. The form layer plus
the `super_admin` write gate carry the safety burden instead.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-SET-001 | SMTP passwords and secrets persist only through Laravel `Crypt` (AES-256-CBC) | zero plaintext secret rows | P0 | F | Full |
| NFR-SET-002 | Setting keys match `^[a-z][a-z0-9_.]*$` to prevent injection via key strings | zero non-matching keys | P0 | A | Full |
| NFR-SET-003 | Only `super_admin` creates or deletes settings; `admin` views and updates | 100% of mutations policy-gated | P0 | F | Full |
| NFR-SET-004 | Full-page saves are atomic: all entries land or none do | zero partial saves | P0 | F | Full |
| NFR-SET-005 | Brand resolution never throws on a half-configured install; it falls back to `AppInfo` defaults | zero brand-related 500s on fresh installs | P1 | F | Full |
| NFR-SET-006 | Cached setting reads stay fast on Tier-1 file cache | < 5ms p99 cache hit | P1 | F | Full |
| NFR-SET-007 | Full-page save of ~18 properties completes promptly | < 2s p95 | P1 | F | Full |
| NFR-SET-008 | No ad-hoc setting key strings; every key is declared once and every read flows through `setting()` or `Settings::get()` | zero undeclared keys in scans | P1 | A | Full |
| NFR-SET-009 | Every user-facing settings string uses `__()` with mirrored keys in `lang/en/` and `lang/id/` | zero missing-key pairs | P0 | A | Full |

### 5.1 Security and Access

#### NFR-SET-001 — Secrets never rest in plaintext

The audit that matters here is brutally simple: dump the settings table and grep for the SMTP
password. With the encrypted cast in place, the dump shows ciphertext; without it, the password
sits beside the hostname in cleartext, copied into every file-copy backup the school ever
takes. Rotation hygiene and `APP_KEY` presence complete the story, but the row-level guarantee
is ciphertext at rest, always.

#### NFR-SET-002 — Key pattern as injection boundary

Setting keys flow into cache-key construction and group lookups, so a key containing path
traversal or whitespace could poison cache namespaces in ways that surface far from the write.
The lowercase dotted pattern closes that door at the Action boundary. Anything already in the
table that fails the pattern is a migration-era artifact to be renamed, not a precedent.

#### NFR-SET-003 — Creation and deletion stay super-admin

Value updates are daily operator work; creating new keys or deleting rows reshapes what the
application can configure at all. The policy split reflects that blast-radius difference:
`admin` edits values freely, but only `super_admin` alters the key space. A direct Action
call without the role meets the same denial as the UI, because the check lives in the policy
the Action consults, not in the Blade template.

### 5.2 Reliability and Performance

#### NFR-SET-004 — All-or-nothing saves

The enrollment-week version of a partial save is branding updated but the mail block half
written — the site looks right while notifications fail. Transaction wrapping makes that
state unreachable: the database guarantees the eighteen upserts commit together, and the
observer's in-transaction hooks keep the cache story consistent with the commit outcome.

#### NFR-SET-005 — Brand fallback under misconfiguration

A fresh install renders its public landing page before any admin has saved branding. If brand
resolution threw on missing rows, the first impression of the product would be a 500 error.
The dual-path fallback exists so the degraded-but-rendered page — compiled-in name, default
emerald palette — is the worst case, never an exception.

#### NFR-SET-006 — Cache-hit read latency

Settings are read on nearly every request, so the cached path must be effectively free: single
digit milliseconds at p99 on plain file cache. A slower hit means the cache driver is
misconfigured or a caller bypassed the helper with a per-request database query — both
detectable, both fixable without touching business logic.

#### NFR-SET-007 — Save latency budget

Eighteen upserts, one transaction, a handful of synchronous cache clears: the whole save must
finish inside two seconds at p95 on shared hosting. Breaching the budget points at media
uploads bundled into the save (they belong in the immediate-upload hooks) or at a queue
driver accidentally set to something synchronous-but-remote.

### 5.3 Maintainability and Localization

#### NFR-SET-008 — One declaration per key, one read path

The failure mode is a Livewire component reading `DB::table('settings')->where('key', ...)`
directly: it bypasses the resolution chain, the type casts, and the cache in one line, and
works just well enough to survive review. The scan story forbids it — every key declared in
exactly one place, every read through the helper or the service — so the chain stays the
single source of truth rather than a suggestion.

#### NFR-SET-009 — Bilingual settings UI

An Indonesian operator and an English-speaking developer read the same settings page in their
own language, toggled at runtime without restart. Every label passes through `__()`, and
every key exists in both `lang/en/setting.php` and `lang/id/setting.php`. A key present in
one file but missing in the other renders raw in one locale — the consistency scan catches
exactly that asymmetry.

---

## 6. API / Data Contracts

### 6.1 Helper Signatures

```php
setting(string|array|null $key = null, mixed $default = null, bool $skipCache = false): mixed
brand(string $key, mixed $default = null): mixed
feature(string $key, bool $default = false): bool
```

`setting(null)` returns the `Settings` service instance. `setting([...])` resolves a batch.
`feature($key)` resolves `features.{key}` through the full chain and casts to `bool`.

### 6.2 SettingType Enum

```php
enum SettingType: string implements LabelEnum
{
    case STRING = 'string';
    case INTEGER = 'integer';
    case FLOAT = 'float';
    case BOOLEAN = 'boolean';
    case JSON = 'json';
    case ENCRYPTED = 'encrypted';
    case NULL = 'null';

    public static function detect(mixed $value): self;
    public function cast(mixed $value): mixed;
}
```

### 6.3 Action Signatures

```php
SetSettingAction::execute(string $key, mixed $value): SettingEntity
BatchSetSettingAction::execute(SettingEntryData ...$entries): void
DeleteSettingAction::execute(string $key): void
SaveSystemSettingsAction::execute(SystemSettingsData $data): ActionResponse
TestMailSettingsAction::execute(array $mailConfig, string $recipient): void
```

All mutating actions extend `BaseCommandAction` and wrap writes in `transaction()`.

### 6.4 Cache-Key Registry (excerpt)

| Config Key | Cache Value | TTL |
| ---------- | ----------- | --- |
| `settings_all` | `settings.all` | forever |
| `settings_group` | `settings.group.` (prefix) | forever |
| `settings_key` | `settings.` (prefix) | forever |
| `theme_css_variables` | `theme.css_variables` | 3600s (1h) |
| `brand_colors` | `brand.colors` | 86400s (24h) |
| `school_entity` | `academics.school.entity` | forever |

Full registry: [config/cache-keys.php](../../config/cache-keys.php).

### 6.5 Key Settings

| Key | Group | Type | Default |
| --- | ----- | ---- | ------- |
| `brand_name` | branding | string | `AppInfo::name()` |
| `site_title` | branding | string | `brand('name')` |
| `primary_color` / `secondary_color` / `accent_color` / `base_color` | branding | string (hex) | emerald preset |
| `brand_logo` / `site_favicon` | branding | string (media URL) | bundled asset path |
| `brand.custom_css` | branding | string | `''` |
| `default_locale` | localization | string | `id` |
| `active_academic_year` | system | string | school year containing today (July–June) |
| `support_email` | general | string | `''` |
| `mail_host` / `mail_port` / `mail_encryption` / `mail_username` | mail | string/integer | `''` / `587` / `tls` / `''` |
| `mail_password` | mail | encrypted | `null` |
| `features.*` | features | boolean | per `config/settings.php` |

### 6.6 Route

```php
Route::livewire('/admin/settings', SystemSetting::class)
    ->name('admin.settings')
    ->middleware(['auth', 'role:super_admin|admin']);
```

---

## 7. Design Decisions

Each decision below records a settled tradeoff with its governing ADR; none carries a separate
test layer because the linked FR rows already pin the verifiable behavior.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-SET-001 | Cache invalidation uses a synchronous `SettingObserver` because it passes all three observer gates | P0 | — | — |
| DD-SET-002 | Storage types are auto-detected from PHP values instead of caller-declared | P1 | — | — |
| DD-SET-003 | Resolution precedence puts runtime overrides first and hardcoded defaults last | P0 | — | — |
| DD-SET-004 | Image and color settings are validated strings, not new `SettingType` cases | P1 | — | — |
| DD-SET-005 | Feature flags reuse the settings store instead of a dedicated package or table | P1 | — | — |
| DD-SET-006 | Validation migrates toward `Entity::rules()` sharing once a second form edits the same entity | P1 | — | — |

### 7.1 Invalidation and Types

#### DD-SET-001 — The observer that earns its coupling

The events-everywhere alternative was genuinely considered: a `SettingSaved` event with a
listener clearing keys would decouple the model from the cache layer. It fails on timing —
deferred listeners run after commit, leaving the stale window PS-3 forbids — and the ADR's
three gates confirm the observer: same module as the model, synchronous completion required
before the response, single-model scope with no fan-out. The coupling costs one small class
with one private method; an event plus listener registration would cost more moving parts for
strictly worse correctness.

#### DD-SET-002 — Detection over declaration

Requiring every `SetSettingAction` caller to name the type would push storage vocabulary into
thirty call sites that should not care. Detection inverts the burden: the store inspects the
PHP value once, centrally, and the ambiguous edges (`"1"` versus `1` versus `true`) resolve
by documented rule rather than caller discipline. The price is that callers must pass
correctly typed values — a string `"true"` will never become boolean — which is a fair trade
for removing type ceremony from the common path.

#### DD-SET-003 — Why runtime wins and defaults anchor

Fresh installs and staging overrides pull in opposite directions: the former needs the chain
to answer with nothing configured, the latter needs local overrides to beat committed data.
Runtime-first plus defaults-last satisfies both simultaneously, with `AppInfo` and config as
the middle layers that distinguish compiled-in product metadata from deployer-provided files.
The debugging cost is real but bounded — `$skipCache` plus the documented order answers
"which layer won" in one lookup.

### 7.2 Representation Choices

#### DD-SET-004 — No COLOR or IMAGE enum cases

Two new `SettingType` cases were prototyped on paper and rejected: `detect()` receives only
the stored string, so it cannot distinguish a hex color from any other string without
key-name sniffing — storage-level typing by key name would be a layering violation. Colors
stay hex-validated strings at the form boundary; images stay URL strings pointing at media
library rows. The enum keeps its seven storage-honest cases, and domain validation lives
where domain knowledge lives.

#### DD-SET-005 — Flags without a flags system

A dedicated feature-flag package buys targeting, percentages, and a toggles UI — none of
which a single-tenant school needs at MVP. The settings store already provides caching, type
safety, invalidation, and RBAC, so flags ride free as boolean rows with a thin helper for
ergonomics. The exit criterion is explicit: the day a flag needs an audience rule, it leaves
the store for a real flags mechanism instead of accumulating conditional hacks.

#### DD-SET-006 — Shared rules at the second form

The first form for an entity keeps its rules inline — co-located, obvious, zero indirection.
Duplication becomes real only when the second form appears (page editor plus setup wizard,
for instance), and that appearance is the documented trigger to hoist rules into
`Entity::rules()` with both forms referencing them. Earlier hoisting would speculate about
shapes still changing weekly during pilot; later hoisting lets two copies drift. The trigger
fires on count, not on calendar.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|----------------|
| Cached setting read | < 5ms p99 | Tier-1 file-cache timing |
| Full-page save (~18 properties) | < 2s p95 | feature-test timing + manual spot |
| Per-key invalidation | < 10ms | observer timing |
| Partial saves | 0 | transaction coverage in F-layer tests |
| Undeclared setting keys | 0 | key-registry scan |
| Missing `en`/`id` key pairs | 0 | locale consistency scan |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [base-classes.md](SE5Q9-base-classes.md) (SE5Q9) | `BaseCommandAction`, `BaseReadAction`, cache-key registry (`config/cache-keys.php`) |

### Build Guide

With this spec implemented, every module reads configuration from a cached, typed, invalidated
store through two memorized helpers. Branding, theme, locale, and school profile arrive next as
thin consumers of this machinery rather than new infrastructure.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [branding-theme-locale.md](52O1I-branding-theme-locale.md) | Consumes `brand.*`, `theme.*`, `locale.*` keys; observer clears theme and brand caches |
| 2 | [school-profile.md](81SMS-school-profile.md) | Stores `school.*` keys; entity cache follows the same observer path |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| -- | --------------------------------- | ------ | ----- | -------- |
| A-1 | We assume Tier-1 file cache sustains the < 5ms p99 read target at ≤ 500 users; Redis promotion remains a config-only swap if Pulse evidence disagrees | Accepted | Maintainer | — |

## Quick References

- [Spec registry](index.md) — Phase 2 Configuration specs and build order
- [Branding, theme & locale](52O1I-branding-theme-locale.md) — primary consumer of brand, theme, and locale keys
- [School profile](81SMS-school-profile.md) — consumer of `school.*` keys
- [Base classes](SE5Q9-base-classes.md) — `BaseCommandAction` and `BaseReadAction` contracts
- [ADR: Eloquent observers](../adr/adr-eloquent-observers.md) — the three-gate observer justification
- [ADR: Gradual migration](../adr/adr-gradual-migration.md) — DTO, validation, and invalidation migration paths
- [ADR: Performance optimization](../adr/adr-performance-optimization.md) — Tier 0/1/2 growth tiers
- [ADR: Self-hosted single-tenant](../adr/adr-self-hosted-single-tenant.md) — zero-external-services default
- [Cache-key registry](../../config/cache-keys.php) — every key this spec may touch
