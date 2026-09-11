# EWCZ0 — Account Slips

> **Spec ID:** EWCZ0
> **Status:** Full
> **Owner:** User
> **Depends on:** 95EVB

## Description

Printable credential slips for Internara: single and batch PDF generation through DomPDF
at custom card size, a freshly minted activation code on every slip, email delivery as the
alternate channel, and the `DownloadsAccountSlips` trait plus modal that bring slip
operations into every user manager. Credential scope uses the `ASLIP` prefix throughout —
the `SLIP` scope belongs to [account recovery slips](SHQ1J-account-recovery-slips.md) and
the two systems share nothing but the paper metaphor.

---

## 1. Problem Statements

### PS-1 — Credential Distribution After Account Creation

When administrators create user accounts (individually or via CSV import), they must distribute
login credentials to users. Indonesian vocational schools require printed credential
distribution as the standard practice — students receive physical account slips with their
username, temporary password, and activation instructions. Without a slip generation system,
administrators must manually write or copy credentials, which is error-prone and does not scale.
**→ Requirement:** FR-ASLIP-001–009 (PDF generation), UC-ASLIP-001.

### PS-2 — Batch Import Requires Bulk Slip Generation

CSV import may create 500+ student accounts in a single operation. Generating account slips
one-by-one for each imported user is impractical. The system must support batch PDF generation
that produces a single multi-card PDF containing all imported users' credentials,
ready for printing and physical distribution.
**→ Requirement:** FR-ASLIP-003 (batch execute), UC-ASLIP-002, NFR-ASLIP-005.

### PS-3 — Printed Slips as Indonesian School Standard

Indonesian schools operate in environments where digital-only credential delivery is unreliable.
Students may not have personal email access, school internet may be intermittent, and teachers
need physical handout materials for classroom distribution. PDF account slips designed for
standard paper printing (custom card dimensions) are the accepted credential delivery mechanism
in this educational context.
**→ Requirement:** FR-ASLIP-004 (card size), FR-ASLIP-007/008 (slip view contract).

### PS-4 — Activation Code Lifecycle for Slip Content

Account slips must display a valid activation code that the student uses to activate their
account. Activation codes are time-limited (30-day expiry) and must be freshly generated each
time a slip is viewed or downloaded. If a code expires or is lost, the administrator must be
able to regenerate it without recreating the account. The slip content (name, username, email,
activation code) must reflect the current state of the user record at generation time.
**→ Requirement:** FR-ASLIP-010–014 (code lifecycle), UC-ASLIP-003.

### PS-5 — Email Distribution as Alternative Channel

While PDF slips are the primary distribution method, administrators also need the option to send
credentials directly via email. This is useful for teacher accounts, supervisor accounts, or
any situation where digital delivery is preferred over printed distribution.
**→ Requirement:** FR-ASLIP-019 (send path), UC-ASLIP-004.

---

## 2. Goals & Non-Goals

### Goals

- **Single-user PDF slips at card size** — DomPDF, 241×156mm, streamed to the browser. *Why:* classroom handouts need a printable card, not a web page.
- **Batch PDFs for bulk printing** — all selected users in one multi-card file. *Why:* five hundred imports cannot mean five hundred downloads.
- **Current-state credentials on every slip** — name, username, email, fresh activation code. *Why:* a slip showing last month's code is worse than no slip.
- **Fresh activation code per generation** — minted at view time, 30-day expiry. *Why:* printed codes must be alive when the student types them.
- **Regeneration without account surgery** — new code, same account, old code superseded. *Why:* lost slips must be recoverable without touching identity.
- **Email delivery as second channel** — activation notification on demand. *Why:* staff accounts and connected students prefer inbox over paper.
- **Slip operations inside the managers** — trait plus modal in every role table. *Why:* slips happen where the users are listed, not on a separate pilgrimage.
- **Preview before print** — modal showing exactly what the PDF will carry. *Why:* printing five hundred wrong slips is the mistake this prevents.

### Non-Goals

- **School-configurable slip layouts**. *Why:* one fixed card keeps printing predictable across schools.
- **QR codes or digital signatures on slips**. *Why:* verification ceremony belongs to certificates, not credentials.
- **Automatic slip email on account creation**. *Why:* delivery stays on-demand; bulk auto-send is explicitly out.
- **Slips for non-user entities**. *Why:* certificates and reports own their own rendering paths.
- **Bulk email blasts to all imported users**. *Why:* one-click mass credential email is a phishing-shaped feature.
- **PDF persistence on disk**. *Why:* stored slips would be stale-credential archives; streaming keeps every PDF current.

---

## 3. User Stories / Use Cases

Single slip, batch slip, code maintenance, email, and preview. Each row is verified by a
Feature test with real PDF rendering or notification delivery.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-ASLIP-001 | Admin downloads a single user's PDF slip with a fresh activation code | P0 | F | Full |
| UC-ASLIP-002 | Admin batch-downloads one PDF for all checked users | P0 | F | Full |
| UC-ASLIP-003 | Admin regenerates a user's activation code from the slip modal | P0 | F | Full |
| UC-ASLIP-004 | Admin emails the activation code to the user from the slip modal | P1 | F | Full |
| UC-ASLIP-005 | Admin previews slip contents in the modal before downloading or sending | P1 | F | Full |

### 3.1 Generation

#### UC-ASLIP-001 — One student, one card, one minute

The admin clicks the slip action on a new student's row; the modal opens showing name,
monospace username, email, and a freshly minted activation code with its 30-day note.
Download streams `account-slip-{username}.pdf` — a 241×156mm card rendered from current
record state. The whole errand, from click to printable file, fits inside a minute, which
is exactly the budget it has during enrollment week.

#### UC-ASLIP-002 — Morning after the big import

Five hundred accounts landed overnight; five hundred separate downloads would end the
morning. The admin checks all, hits the batch action, and receives one multi-card PDF —
each card with its own fresh code — ready for the print shop. Empty selection answers with
a warning instead of an empty file, because the honest response to "print nothing" is a
question, not a blank page.

### 3.2 Code Care & Preview

#### UC-ASLIP-003 — The lost slip gets a new code

A student returns with a crumpled, month-old slip whose code long expired. No new account,
no password surgery: one Regenerate click mints a replacement, the modal shows it, the old
code dies quietly. Recovery takes seconds and teaches nothing to nobody — the account never
changed, only its key did.

#### UC-ASLIP-004 — The connected teacher gets email

For the new teacher with a working inbox, paper is the slow path. Send Code dispatches the
activation notification carrying the current code — by mail and in-app — with a confirmation
flash closing the loop. Same credential, different envelope, chosen per recipient rather
than per system.

#### UC-ASLIP-005 — Look before you print

Before any download or send, the modal lays out the card's contents — identity block,
prominent selectable code, expiry note, three action buttons — so the admin verifies with
eyes, not faith. Preview is the cheapest quality gate in the building: one glance prevents
a reprint run.

---

## 4. Functional Requirements

PDF generation, code lifecycle, trait, modal, controller, routes. Every row is implemented
and verified.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-ASLIP-001 | `GenerateAccountSlipAction` extends `BaseCommandAction` | P0 | A | Full |
| FR-ASLIP-002 | `execute(User $user)` renders one user's PDF through DomPDF and streams it | P0 | F | Full |
| FR-ASLIP-003 | `executeBatch(array $users)` renders all given users into one multi-card PDF and streams it | P0 | F | Full |
| FR-ASLIP-004 | PDF paper size is the custom card `[0, 0, 241, 156]` (241mm by 156mm) | P0 | F | Full |
| FR-ASLIP-005 | Single-user PDFs stream as `account-slip-{username}.pdf` | P1 | F | Full |
| FR-ASLIP-006 | Batch PDFs stream as `account-slips-batch.pdf` | P1 | F | Full |
| FR-ASLIP-007 | PDFs render from the `user.user-management.account-slip-pdf` Blade view | P0 | F | Full |
| FR-ASLIP-008 | The view receives the User model as `$user` and the plaintext code as `$code` | P0 | F | Full |
| FR-ASLIP-009 | Every generation writes a PII-masked `account_slip_generated` activity entry with user context | P0 | F | Full |
| FR-ASLIP-010 | Every slip generation mints a fresh code via `AccessToken::generateFor()` for `activation` | P0 | F | Full |
| FR-ASLIP-011 | `showSlip(string $id)` mints the code and holds it in `$slipCode` | P0 | F | Full |
| FR-ASLIP-012 | Regeneration mints a new token, superseding the previous code | P0 | F | Full |
| FR-ASLIP-013 | Activation codes expire after 30 days per the `AccessToken` model | P0 | F | Full |
| FR-ASLIP-014 | `$slipCode` holds the `plain_text` value from the generation result | P0 | F | Full |
| FR-ASLIP-015 | Slip generation for an ineligible account (archived or suspended) is refused via `RejectedException` | P0 | F | Full |
| FR-ASLIP-016 | The trait exposes `$showAccountSlip`, `$slipUser`, and `$slipCode` state | P1 | F | Full |
| FR-ASLIP-017 | `showSlip(string $id)` finds the user, mints the code, and opens the modal | P0 | F | Full |
| FR-ASLIP-018 | `regenerateCode()` mints a replacement code and flashes translated success | P0 | F | Full |
| FR-ASLIP-019 | `sendCode()` delivers `ActivationCodeNotification` with the current code and flashes translated success | P1 | F | Full |
| FR-ASLIP-020 | `downloadSlip()` redirects to the single-slip named route | P0 | F | Full |
| FR-ASLIP-021 | `downloadSelectedSlips()` redirects to the batch route with comma-separated ids | P0 | F | Full |
| FR-ASLIP-022 | `downloadSelectedSlips()` flashes a translated warning on empty selection | P1 | F | Full |
| FR-ASLIP-023 | Every trait flash message resolves through `__()` | P0 | A | Full |
| FR-ASLIP-024 | The modal uses `x-ts-modal` bound to `showAccountSlip` at size `sm` | P1 | F | Full |
| FR-ASLIP-025 | The modal shows name, monospace username, email, and a large selectable activation code | P0 | F | Full |
| FR-ASLIP-026 | The modal states the 30-day code expiry beside the code | P1 | F | Full |
| FR-ASLIP-027 | The modal wires a Download Slip button to `downloadSlip` | P0 | F | Full |
| FR-ASLIP-028 | The modal wires a Regenerate Code button to `regenerateCode` with spinner state | P0 | F | Full |
| FR-ASLIP-029 | The modal wires a Send Code button to `sendCode` with spinner state | P1 | F | Full |
| FR-ASLIP-030 | The modal offers a Close action | P1 | F | Full |
| FR-ASLIP-031 | The modal uses a separator with `backdrop-blur-sm` styling | P2 | F | Full |
| FR-ASLIP-032 | `AccountSlipController` is a `final` class receiving `GenerateAccountSlipAction` by constructor injection | P0 | A | Full |
| FR-ASLIP-033 | `download()` delegates to `$action->execute($user)` | P0 | F | Full |
| FR-ASLIP-034 | `downloadBatch()` parses the comma-separated `ids` query parameter | P0 | F | Full |
| FR-ASLIP-035 | `downloadBatch()` loads users via `whereIn` and delegates to `$action->executeBatch()` | P0 | F | Full |
| FR-ASLIP-036 | Constructor injection is the only resolution path; no service-locator calls | P0 | A | Full |
| FR-ASLIP-037 | Single-slip route is `GET /admin/users/{user}/account-slip` → `download` | P0 | F | Full |
| FR-ASLIP-038 | Batch route is `GET /admin/users/account-slips/download?ids=...` → `downloadBatch` | P0 | F | Full |
| FR-ASLIP-039 | Both routes require `auth` middleware | P0 | F | Full |
| FR-ASLIP-040 | Both routes require `role:super_admin\|admin` middleware | P0 | F | Full |
| FR-ASLIP-041 | Route names are `admin.users.account-slip` and `admin.users.account-slips.batch` | P1 | F | Full |
| FR-ASLIP-042 | Slip operations enforce dual-layer authorization: route/policy gating plus Action-level `RejectedException` refusal | P0 | A | Full |

### 4.1 PDF Generation

#### FR-ASLIP-001 — The generator wears the uniform

Extending `BaseCommandAction` gives generation its transaction discipline, error mapping,
and `$this->log()` audit path for free. A PDF Action might look read-only, but it mints
tokens and writes audit entries — side effects that deserve the Command contract. The scan
proves the inheritance; reviewers never wonder.

#### FR-ASLIP-002 — One user in, one card out

Trace the call: controller hands a User, the Action mints a fresh code, renders the Blade
card with user plus code, DomPDF converts, the response streams. Each step depends on the
last, which is why one Action owns the chain instead of scattering it across controller
helpers. Single-purpose, single-owner, single test path.

#### FR-ASLIP-003 — Many users in, one file out

Batch execution loops the same single-card render per user and concatenates the HTML
before one DomPDF pass — the print shop receives one file, not a zip of five hundred. Per
user a fresh code, per batch a single filename. The loop reuses the single path's pieces
so batch and single can never disagree about what a card contains.

#### FR-ASLIP-004 — A card, not a page

241 by 156 millimeters fits the credential facts without an ocean of margin and tiles
neatly onto standard stock at print time. Standard A4 would waste most of the sheet per
student and look like a ransom note of whitespace. The custom size is set per request, so
the global A4 default serving every other document stays untouched.

- Dimensions live as named constants on the Action, not magic numbers at the call site.
- DomPDF receives the box per render; no global config is mutated.

#### FR-ASLIP-005 — Filenames that file themselves

`account-slip-{username}.pdf` sorts, searches, and identifies in a downloads folder without
opening. The username inside the filename matches the username inside the card — a small
consistency with outsized value when an admin downloads twelve singles in a row.

#### FR-ASLIP-006 — One name for every batch

`account-slips-batch.pdf`, always: the plural marks it, the fixed name keeps print-shop
instructions simple ("print the batch file"). Contents vary; the handle never does, so
spoken instructions survive from term to term.

#### FR-ASLIP-007 — One view, every card

All cards render from the same Blade template, single and batch alike. A branding change
touches one file and reaches both paths simultaneously — the alternative, two templates
drifting apart until batch cards show last year's logo, is precisely what this row
forecloses.

#### FR-ASLIP-008 — Two variables, fully specified

`$user` carrying identity, `$code` carrying the plaintext secret: the view's entire world,
named and typed. Constraining the view's inputs keeps credential rendering reviewable —
nothing else is in scope to leak, because nothing else is in scope at all.

#### FR-ASLIP-009 — Printing leaves a masked trail

Each generation logs `account_slip_generated` with the subject's id through SmartLogger —
actor, target, timestamp — with contact details masked before either channel. When a Plain
code is later misused, this entry answers who printed what for whom and when, without
itself becoming a credential leak investigators must then protect.

### 4.2 Activation Code Lifecycle

#### FR-ASLIP-010 — Freshness is generated, not assumed

Minting at generation time through `AccessToken::generateFor()` guarantees the printed
code is alive, full-validity, and tied to this moment. Reusing a stored code would print
whatever remains of its window — possibly hours. Freshness by construction beats expiry
arithmetic in every reviewer's head.

#### FR-ASLIP-011 — Viewing mints, holding displays

`showSlip()` performs the mint the instant the modal opens, so the displayed code was born
seconds ago with its whole 30 days ahead. Display and validity start together; there is no
stale-code window between "generated yesterday" and "viewed today" because generation and
viewing are the same gesture.

#### FR-ASLIP-012 — Regeneration replaces, never duplicates

The new token supersedes the old: exactly one live code per user after the click. Two
simultaneously valid codes would double the attack surface and confuse every "which code
do I type?" conversation. Replacement semantics keep the answer singular.

#### FR-ASLIP-013 — Thirty days, enforced by the token

Expiry lives in the `AccessToken` model, not in slip code — slips display the window, they
do not implement it. Thirty days covers the print-to-activate journey with margin while
bounding the life of a secret traveling on paper. Centralized expiry means one policy
change reaches every slip ever printed that is still unredeemed.

#### FR-ASLIP-014 — Plaintext travels in memory only

`$slipCode` carries the one-time plaintext the hashed store can never return — shown,
emailed, rendered, then gone with the request. Persisting it anywhere would defeat the
hashing; the property's lifetime is the session's lifetime, and nothing longer.

#### FR-ASLIP-015 — Dead accounts get no new keys

Minting a live code for an archived or suspended user would hand a working key to a closed
door — confusing at best, a policy hole at worst. The `RejectedException` refusal names
the account state so the admin learns why instead of staring at a generic failure. Business
rules, enforced where the business happens.

### 4.3 Trait

#### FR-ASLIP-016 — Three properties, one concern

Open flag, subject, code: the trait's entire state surface, prefixed against collisions in
whatever manager hosts it. Three properties is the whole API the modal needs — anything
more would leak manager internals into shared code. Small surface, many hosts.

#### FR-ASLIP-017 — One call opens the whole flow

Find, mint, open: `showSlip()` performs the trio that every manager's slip button needs,
identically. Centralizing here means the student, teacher, and supervisor tables cannot
diverge in behavior — the slip experience is one experience with many doors.

#### FR-ASLIP-018 — Regeneration confirms itself

New code minted, translated success flashed, modal updated in place. The flash matters:
without it the admin cannot tell a fresh code from the one already on screen, and prints
the old card twice. Feedback closes the loop the mint opened.

#### FR-ASLIP-019 — Sending confirms itself too

Notification dispatched with the current code, translated success flashed. Delivery runs
through mail plus the in-app channel, so the code survives a down mail server in the
recipient's notification box. One gesture, two channels, zero ambiguity about whether it
sent.

#### FR-ASLIP-020 — Downloads leave through the named door

Redirecting to `admin.users.account-slip` instead of building responses inline keeps HTTP
mechanics in the controller and state mechanics in the component. The trait asks; the route
serves. Separation here is what lets the controller stay thin enough to read in one
glance.

#### FR-ASLIP-021 — Batches leave through the batch door

Comma-separated ids ride the redirect to the batch route — the selection serialized into
a GET the controller parses back. Simple, bookmarkable, and visible in logs, which beats a
POST body for an operation the admin may need to describe to support later.

#### FR-ASLIP-022 — Nothing checked, kindly said

Empty selection meets a translated warning, not an empty PDF or an exception page. The
kindest error in the system: it assumes a misclick, says so politely, and leaves all state
untouched for the retry that follows within seconds.

#### FR-ASLIP-023 — Every word translatable

All trait flashes resolve through `__()` — the modal speaks Indonesian or English with the
session, never a hardcoded fragment. Shared-trait strings multiply across every host
manager, so one hardcoded sentence here would echo in five tables.

### 4.4 Modal

#### FR-ASLIP-024 — The TallstackUI shell at small size

`x-ts-modal` bound to the open flag, size `sm`: the card preview needs focus, not acreage.
Standard shell means standard keyboard and backdrop behavior inherited, not reimplemented.
Small is a choice — the modal previews a card, it does not reproduce the print shop.

#### FR-ASLIP-025 — Identity plus secret, clearly separated

Name, monospace username, email above; large selectable code below — layout that teaches
which part is typed once (identity) and which is typed once and secrets away (code).
Monospace plus select-all makes transcription errors rare; visual hierarchy makes the
code unmissable.

#### FR-ASLIP-026 — The deadline sits beside the code

"Valid 30 days" next to the code sets the student's clock at first glance. Expiry
discovered at login time feels like betrayal; expiry stated at handover feels like terms.
One line prevents a month-later support conversation.

#### FR-ASLIP-027 — Download one click away

The button calls `downloadSlip` and nothing else — no options, no dialogs, no format
choices. Single-purpose buttons survive tired admins at midnight; clever ones do not. The
wire name matches the trait method exactly, keeping the template greppable.

#### FR-ASLIP-028 — Regeneration shows its work

Spinner state on the regenerate button covers the mint round-trip, so double-clicks do not
mint double codes. Async honesty in one attribute: the button admits it is busy instead of
inviting the click that creates the duplicate the admin then cannot distinguish.

#### FR-ASLIP-029 — Sending shows its work too

Same spinner discipline on send — delivery takes time, and the button says so while it
happens. Two spinners, one rule: every async gesture in the modal reports its own
progress, and none accepts a second press mid-flight.

#### FR-ASLIP-030 — An obvious way out

Close always present, always working, keyboard reachable. A modal without a clear exit
traps the admin's attention hostage; this one releases it on demand. Small row, large
courtesy.

#### FR-ASLIP-031 — Quiet, consistent chrome

Separator plus soft-blur backdrop at small size: the modal looks like every other modal in
the system. Visual consistency is not vanity here — admins operate five managers, and
chrome that shifts per table slows every interaction by a fraction that compounds.

### 4.5 Controller

#### FR-ASLIP-032 — Final class, injected collaborator

`final` forbids the subclass that would override `download` into inconsistency; constructor
injection names the Action dependency where tests can see and substitute it. No service
locator, no hidden resolution — the controller's needs are its signature.

#### FR-ASLIP-033 — Single download is one line

Delegation, pure and simple: receive User, call execute, return the stream. A controller
action this thin cannot harbor bugs — there is nowhere for them to hide. Thinness here is
verified by reading, all eight words of it.

#### FR-ASLIP-034 — The query string is parsed, not trusted

Comma-separated `ids` split, trimmed, and filtered before any query runs — malformed
segments die at parse time, never reaching the database as confusing errors. Input
hygiene at the boundary keeps the failure messages about the request, not the schema.

#### FR-ASLIP-035 — Batch loads exactly the asked set

`whereIn` on the parsed ids, then delegation to `executeBatch()`: the file contains the
checked rows and only them. Exactness is a privacy property when selections are sensitive
— the batch must not freelance with extra records.

#### FR-ASLIP-036 — Injection is the only path

Banning service-locator calls forces every dependency into the constructor, where the
container, the tests, and the next maintainer all find it. Hidden resolution is how
controllers become untestable; this row keeps them honest by construction.

### 4.6 Routes & Authorization

#### FR-ASLIP-037 — The single-slip address

Fixed path, route-model-bound user, one controller method: the address printed in admin
guides and linked from the trait. Stability matters — spoken instructions ("open the
user's slip page") assume it — so the path is specified, not incidental.

#### FR-ASLIP-038 — The batch address with its query

Fixed path plus `ids` parameter: the batch counterpart, equally stable. GET keeps the
operation describable and log-visible; the parameter contract is pinned so trait and
controller cannot drift apart.

#### FR-ASLIP-039 — Login required, no exceptions

`auth` on both routes: slips carry secrets, and secrets never travel to anonymous
browsers. The middleware runs before any user lookup, so unauthenticated probes learn
nothing — not even whether an id exists.

#### FR-ASLIP-040 — Admin roles only

`role:super_admin|admin` narrows further: authenticated students must not mint codes for
anyone, including themselves. Credential power concentrates in the hands already holding
user administration. Two middleware, two concentric walls.

#### FR-ASLIP-041 — Names both ends agree on

`admin.users.account-slip` and `admin.users.account-slips.batch`: the trait redirects by
these names, the routes register them. Naming the contract kills the string-duplication
bug where a renamed route orphans a redirect nobody tested.

#### FR-ASLIP-042 — Two layers say no

Route and policy gating stop unauthorized clicks; the Action's `RejectedException`
refusal stops direct invocations that skip HTTP entirely. Tinker sessions and future
callers meet the same wall the browser meets — authorization travels with the operation,
not with its wrapper.

---

## 5. Non-Functional Requirements

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-ASLIP-001 | Activation codes are minted server-side and never exposed in client script | Zero code logic in JS | P0 | A | Full |
| NFR-ASLIP-002 | Slip routes enforce admin-role authorization | Non-admin requests refused | P0 | F | Full |
| NFR-ASLIP-003 | One user's slip data is never reachable by another user or the public | Auth + role on every path | P0 | F | Full |
| NFR-ASLIP-004 | Activation tokens persist hashed; plaintext exists only in transit | Zero plaintext at rest | P0 | A | Full |
| NFR-ASLIP-005 | One user's code failure never aborts the whole batch | Valid cards still land | P0 | F | Full |
| NFR-ASLIP-006 | Null subject or code makes trait actions return silently instead of erroring | Zero null-pointer flashes | P1 | F | Full |
| NFR-ASLIP-007 | PDF failures surface a generic message; stack traces never reach the admin | Generic message only | P1 | F | Full |
| NFR-ASLIP-008 | Slip PDFs carry proper heading structure for school name and user info | H1 school, H2 identity | P2 | F | Full |
| NFR-ASLIP-009 | Codes render monospace for readability and copy-paste | Monospace + select-all | P1 | F | Full |
| NFR-ASLIP-010 | Modal fields carry clear uppercase tracking-wider labels | Labeled every field | P1 | F | Full |
| NFR-ASLIP-011 | Download and send controls show spinner state while working | No double-submit | P1 | F | Full |
| NFR-ASLIP-012 | Every slip operation confirms via flash, success or failure | Zero silent outcomes | P1 | F | Full |
| NFR-ASLIP-013 | The modal is keyboard-navigable with trapped focus and screen-reader labels | Focus trapped; Esc closes | P1 | B | Full |
| NFR-ASLIP-014 | Generation stays in the Action; Livewire never mutates models | Zero model writes in trait | P0 | A | Full |
| NFR-ASLIP-015 | The trait delegates persistence to Actions and owns none | Zero direct writes | P0 | A | Full |
| NFR-ASLIP-016 | All PHP files declare `strict_types=1` | 100% of slip files | P0 | A | Full |
| NFR-ASLIP-017 | Every user-facing string in slip UI passes through `__()` | Zero hardcoded strings | P0 | A | Full |
| NFR-ASLIP-018 | Translation keys exist in both `lang/en/` and `lang/id/` | Keys mirrored both locales | P0 | A | Full |
| NFR-ASLIP-019 | Slip activity entries mask PII before either log channel | Zero raw PII in logs | P0 | F | Full |

### 5.1 Security

#### NFR-ASLIP-001 — Secrets are born on the server

No code value, generator, or token material ever appears in JavaScript — minting happens
in PHP, the client receives only the finished string to display. Client-side secret logic
would expose the mint to anyone reading page source; server-side keeps the machinery
where the authorization already runs.

#### NFR-ASLIP-002 — The door checks badges twice

Auth plus role middleware on both routes: the combination that keeps students, guests, and
expired sessions out of credential generation. Tested by knocking unauthorized — each
refusal asserted, each bypass attempt logged nowhere because it never gets inside.

#### NFR-ASLIP-003 — Your slip is yours alone

Route binding plus role plus ownership-blind admin scope means no URL guessing reaches
another user's credentials. The invariant is simple to state and critical to hold: slips
are admin-vended per recipient, never browsable, never enumerable.

#### NFR-ASLIP-004 — Hashes at rest, plaintext in flight

The token store holds hashes; only the freshly minted response carries plaintext, once.
A database backup, a log file, a curious query — none reveal a usable code. Hashing is the
difference between a leaked table and a leaked system.

#### NFR-ASLIP-019 — The audit trail wears a mask

Names, emails, and codes are masked before the slip activity entry reaches either channel,
so the log proves printing happened without becoming a credential archive itself. An
investigator reading the trail learns who, when, and for whom — never the secret that
would let them impersonate the answer.

### 5.2 Reliability

#### NFR-ASLIP-005 — Batches bend around the broken

One user's mint failure skips that card with a recorded reason while the rest still print
— five hundred minus one beats zero plus an error page. Partial success is the specified
batch behavior, matching the import pipeline's philosophy row for row.

#### NFR-ASLIP-006 — Nulls exit quietly

Stale modals, raced deletions, double clicks: when subject or code is missing, the trait
returns instead of exploding. Defensive silence here is correct — the user's next click
rebuilds valid state, and an exception page would punish them for the system's timing.

#### NFR-ASLIP-007 — Failures speak generically

PDF engine errors become "generation failed, try again" — never a stack trace naming
paths, versions, and internals to whoever happens to be operating. Details go to the
system log with context; the admin gets composure, not internals.

### 5.3 Experience & Structure

#### NFR-ASLIP-008 — Documents with a skeleton

H1 for the school, H2 for identity: heading structure that survives PDF conversion into
something assistive tech and print pipelines can navigate. Structure costs nothing at
authoring time and pays every time the document outlives its first printing.

#### NFR-ASLIP-009 — Codes built for transcription

Monospace plus select-all turns a 32-character secret from a typing ordeal into a copy
gesture. Every character distinguishable, the whole string selectable in one action —
small typographic choices with outsized effect on activation success rates.

#### NFR-ASLIP-010 — Labels that announce themselves

Uppercase, letter-spaced labels above every field: name, username, email, code each
introduced before shown. Structure the eye can scan at arm's length — the distance of a
teacher holding a freshly printed card across a desk.

#### NFR-ASLIP-011 — Buttons that admit busyness

Spinners on download and send during their async work: the interface confesses it is
occupied instead of inviting the second click that mints the duplicate. Honest controls
produce patient operators.

#### NFR-ASLIP-012 — No silent outcomes

Regeneration, send, batch selection — each answers with a flash, success or failure. An
operation whose result the admin must infer from scrolling is an operation half built.
Confirmation is the closing bracket on every gesture.

#### NFR-ASLIP-013 — A modal everyone can operate

Keyboard traversal, trapped focus, Escape to close, labeled controls for screen readers:
the modal merged its accessibility rows into one because they ship and regress as a unit.
Credential work must not require a mouse — many school machines barely have a working one.

#### NFR-ASLIP-014 — The Action does, the component asks

Generation, minting, logging: all inside the Action. The trait and modal request and
render; they never write. One home for side effects means one place to test them, and the
scan proves the components stayed clean.

#### NFR-ASLIP-015 — Delegation all the way down

The trait owns zero persistence calls — every write flows through an Action's `execute()`.
Shared UI code that writes directly becomes five managers' worth of divergent writes; a
trait that only asks keeps all five identical. Asking scales; writing diverges.

#### NFR-ASLIP-016 — Strict types, every file

`declare(strict_types=1)` atop all slip PHP: coercion surprises die at the call boundary
instead of surfacing as a misrendered card. Strictness is cheapest at the top of the file
and most expensive everywhere else.

#### NFR-ASLIP-017 — Every word through the helper

`__()` on all slip strings — buttons, flashes, labels, expiry notes. Slips travel to
parents and community boards; untranslated fragments there embarrass the school, not just
the software. The helper keeps every word accountable.

#### NFR-ASLIP-018 — Both locales, no gaps

Keys mirrored in English and Indonesian, asserted by scan. A missing key renders as a raw
identifier on a printed card handed to a parent — the most public failure surface in the
system. Mirroring is verified, not assumed.

---

## 6. API / Data Contracts

### 6.1 GenerateAccountSlipAction

```php
// app/Modules/User/UserManagement/Actions/GenerateAccountSlipAction.php
final class GenerateAccountSlipAction extends BaseCommandAction
{
    private const int CARD_W = 241; // mm
    private const int CARD_H = 156; // mm

    public function execute(User $user): Response;
    // Refuses ineligible accounts (RejectedException); logs account_slip_generated (PII-masked);
    // mints activation code; renders account-slip-pdf view; streams account-slip-{username}.pdf

    public function executeBatch(array $users): Response;
    // Per user: guard → mint → render → append; single DomPDF pass;
    // streams account-slips-batch.pdf; per-user failure skips that card only

    private function download(User $user): Response; // single-user render path
}
```

### 6.2 DownloadsAccountSlips Trait

```php
// app/Modules/User/UserManagement/Livewire/Concerns/DownloadsAccountSlips.php
trait DownloadsAccountSlips
{
    public bool $showAccountSlip = false;
    public ?User $slipUser = null;
    public string $slipCode = ''; // plain_text from generateFor(); request-lived only

    public function showSlip(string $id): void;        // find + mint + open
    public function regenerateCode(): void;            // mint replacement + flash
    public function sendCode(): void;                  // notify + flash
    public function downloadSlip(): void;              // redirect single route
    public function downloadSelectedSlips(): void;     // redirect batch route or warn
}
```

### 6.3 AccountSlipController

```php
// app/Modules/SysAdmin/Http/Controllers/AccountSlipController.php
final class AccountSlipController
{
    public function __construct(protected readonly GenerateAccountSlipAction $slips) {}

    public function download(User $user): mixed;            // delegates execute()
    public function downloadBatch(Request $request): mixed; // parses ids, delegates executeBatch()
}
```

### 6.4 ActivationCodeNotification

```php
// app/Modules/User/UserManagement/Notifications/ActivationCodeNotification.php
class ActivationCodeNotification extends Notification
{
    public function __construct(public readonly User $user, public readonly string $code);
    public function via(object $notifiable): array; // ['mail', CustomDatabaseChannel::class]
    public function toMail(object $notifiable): object;
    // Subject __('user.activation.email_subject'); greeting with user name;
    // body: code + action link (activate route) + 30-day expiry note
    public function toCustomDatabase(object $notifiable): array; // type 'activation_code'
}
```

### 6.5 PDF Blade View Contract

```
View: user.user-management.account-slip-pdf
Path: resources/views/user/user-management/account-slip-pdf.blade.php
Variables: $user (name, username, email), $code (plaintext activation code)
Output: HTML rendered by DomPDF; paper [0, 0, 241, 156] mm set per request
```

### 6.6 DomPDF Configuration

```
Config: config/dompdf.php — global default a4, overridden per slip request
Backend: CPDF; font dir storage_path('fonts'); chroot realpath(base_path())
Remote: disabled (no external resources in slips); DPI 96
```

### 6.7 Activity Log

```
Event: account_slip_generated — context ['user_id' => $user->id]
Logged by GenerateAccountSlipAction via $this->log() with PII masking (FR-ASLIP-009)
```

### 6.8 Routes

```
GET /admin/users/{user}/account-slip            → AccountSlipController::download
    Name: admin.users.account-slip · Middleware: auth, role:super_admin|admin
GET /admin/users/account-slips/download?ids={csv} → AccountSlipController::downloadBatch
    Name: admin.users.account-slips.batch · Middleware: auth, role:super_admin|admin
```

---

## 7. Design Decisions

Choices behind the slip system; each names the shape it produced.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-ASLIP-001 | PDF slips via DomPDF, server-side, no external services | P0 | — | — |
| DD-ASLIP-002 | Custom 241×156mm card instead of A4/Letter | P1 | — | — |
| DD-ASLIP-003 | Fresh activation code minted on every view, download, and regeneration | P0 | — | — |
| DD-ASLIP-004 | Slip operations as a Livewire trait, not a standalone component | P1 | — | — |
| DD-ASLIP-005 | PDFs streamed, never stored on disk | P0 | — | — |
| DD-ASLIP-006 | Batch as concatenated single-card renders in one DomPDF pass | P0 | — | — |

### 7.1 Rendering Shape

#### DD-ASLIP-001 — Paper needs PDF, schools need offline

HTML pages cannot be handed across a desk; emailed bodies cannot be printed uniformly.
DomPDF runs inside the deployment with no external service — critical for self-hosted
schools — and produces the universal printable artifact. Its limited CSS is no hardship:
credential cards want tables and blocks, not flexbox artistry. Boring technology for a
job where reliability outranks beauty.

#### DD-ASLIP-002 — Cards tile, pages waste

A full page per student burns paper and looks absurd; the compact card carries logo,
identity, and code with room to spare and tiles efficiently onto standard stock. Custom
dimensions per request keep the global A4 default honest for everything else. Print
economics decided the size, not aesthetics.

#### DD-ASLIP-003 — Codes minted at the moment of need

View, download, regenerate — each mints, so displayed codes are always alive with full
validity. The alternative, reusing stored codes, prints whatever remains of old windows
and teaches admins to distrust the card. Multiple token rows per user are the accepted
cost; only the latest works, and the model enforces that without anyone thinking about it.

### 7.2 Delivery Shape

#### DD-ASLIP-004 — The trait lives where the selection lives

Slip actions operate on the manager's checked rows and current user — context a standalone
component would have to be handed expensively. A trait composes into each role manager
independently, sharing behavior while staying co-located with selection state. The
`slip` prefix on all state keeps the mixed-in properties collision-free.

#### DD-ASLIP-005 — Streamed because stored would lie

Persisted PDFs would fossilize credentials that change on every regeneration — stale
secrets sitting on disk waiting to be found. Streaming renders current state on demand:
current user, current code, no archive of superseded secrets. Regeneration implying
replacement is not a side effect here; it is the design.

#### DD-ASLIP-006 — One pass, many cards

Concatenating single-card HTML then rendering once yields a single multi-page PDF the
print shop handles natively — simpler than merging files, friendlier than zips. Per-card
failures skip instead of sinking the batch, so one broken record cannot hold five hundred
cards hostage. Simple composition, robust at scale.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|---------------|
| Single-slip correctness | Name, username, email, code all rendered | PDF content assertions |
| Batch completeness | Every selected user carded with unique codes | Per-card assertions in batch PDF |
| Code validity | 100% of printed codes activate | Activation acceptance test |
| Email delivery | Code received via mail channel | Notification assertions |
| Preview fidelity | Modal matches PDF contents | Side-by-side content assertions |
| Empty selection | Warning, no file | Empty-selection test |
| Route gating | Admin-only on both routes | Unauthorized-access tests |
| Freshness | Code minted at each view | Token-timestamp assertions |
| Supersession | Old code dead after regenerate | Old-code rejection test |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|-----------------|
| [user-crud-and-status](95EVB-user-crud-and-status.md) | User entities slips are generated for |

### Build Guide

Slips close the provisioning loop: accounts created singly or by import become printable,
emailable credentials. Placed students with slips in hand move into daily operations.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [daily-activity](1KSWL-daily-activity.md) | Students with active placements begin logbook entries |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec registry](index.md) — Enrollment phase; this spec is `EWCZ0` with status Full
- [User CRUD & status](95EVB-user-crud-and-status.md) — user entities behind every slip
- [Bulk import](O2KCR-csv-import-export.md) — batch provisioning that feeds batch slips
- [Account recovery slips](SHQ1J-account-recovery-slips.md) — owns the `SLIP` scope; no overlap
- [Architecture](D2FT3-architecture.md) — Action Triad, Entity rules, DTO boundary
- [Project initialization](QLHDO-project-initialization.md) — global FR-GLB/NFR every row inherits
