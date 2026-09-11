# Dashboard — Role-Based Data Aggregation & Caching

> **Spec ID:** CKKZC
> **Status:** Full
> **Owner:** User
> **Depends on:** YB7RG, TXR2H

## Description

Specification of Internara's role-based dashboard system: automatic routing to role-appropriate
dashboards, four role-specific data aggregation Read Actions with cached results, event-driven
cache invalidation, and a base Livewire component hierarchy. Widget data arrives through direct
Read Action imports, and all widget Reads stay lock-free. Login and authentication throttling are
covered in [authentication](YB7RG-authentication.md).

---

## 1. Problem Statements

### PS-1 — Role-Appropriate Dashboard Views

With five distinct roles, a single dashboard layout cannot serve everyone: admins need
system-wide statistics across people, internships, registrations, placements, attendance,
logbooks, certificates, and companies, while students need academic progress, teachers need
supervision queues, and supervisors need intern activity.
**→ Requirement:** FR-DASH-001–008 (role routing), FR-DASH-009–015 (role aggregation),
FR-DASH-025–033 (role components).

### PS-2 — Dashboard Data Freshness vs Performance

Dashboard statistics involve expensive aggregation queries — the admin view alone spans eight
models — so uncached loads take seconds while cached loads risk staleness. The system balances
freshness against speed with a short TTL plus targeted invalidation.
**→ Requirement:** FR-DASH-016–021 (300s caching), FR-DASH-022–024 (synchronous invalidation).

### PS-3 — Expensive Aggregation Queries

Role queries run `whereHas` subqueries through pivot tables and multi-table aggregations that
are unacceptable on every page load from shared school computers with limited bandwidth.
**→ Requirement:** FR-DASH-009–015 (delegated aggregation), FR-DASH-039 (lock-free Reads).

### PS-4 — Cache Invalidation on Structural Changes

Statistics depend on structural entities such as departments and academic years that change
rarely but affect every load. Without targeted invalidation, users see outdated counts long
after a structural change.
**→ Requirement:** FR-DASH-022–024 (event-driven invalidation).

### PS-5 — Cross-Role Proxy Viewing

Teachers sometimes act as supervisors for specific interns. Without proxy-aware routing, the
proxy user sees their own dashboard instead of the target role's dashboard.
**→ Requirement:** FR-DASH-008 (proxy resolution), FR-DASH-029 (supervisor gate).

---

## 2. Goals & Non-Goals

### Goals

- **Automatic role routing** — users land on the dashboard matching their highest-priority role. *Why:* the post-login landing must never require a manual choice.
- **Four role dashboards** — admin, student, teacher, and supervisor views with dedicated aggregation. *Why:* each role's questions are different enough to deserve their own queries.
- **Cached statistics with a five-minute TTL** — every widget reads through `Cache::remember()`. *Why:* read-heavy, write-light data should not pay aggregation cost on every load.
- **Synchronous invalidation on structural change** — department and academic-year events clear dashboard keys immediately. *Why:* the next request after an admin edit must read fresh data.
- **Shared base Livewire component** — `UserDashboard` carries user retrieval and recent activity. *Why:* common behavior written once instead of four times.
- **Proxy-aware routing** — teachers acting as supervisors reach the supervisor view. *Why:* cross-role supervision assignments from [rbac-and-authorization](T4B26-rbac-and-authorization.md) need a matching landing.
- **System readiness checks on the admin view** — database, mail, cache, queue, and storage probes. *Why:* the admin's first screen doubles as a smoke panel for school operators.
- **Aggregation delegated to Read Actions** — no inline queries in Livewire components. *Why:* lock-free, testable, reusable data paths per the architecture spec.

### Non-Goals

- **Real-time updates via WebSocket or server-sent events**. *Why:* refresh-on-visit with a five-minute TTL satisfies school operations without streaming infrastructure.
- **User-customizable widgets or layouts**. *Why:* fixed role layouts keep the dashboard testable and supportable at MVP.
- **Dashboard export to PDF or CSV**. *Why:* owned by the Reports module; the dashboard is a read-only landing.
- **Cross-tenant aggregation**. *Why:* single-tenant by product definition; there is nothing to aggregate across.
- **Mobile-specific dashboard layouts**. *Why:* the responsive shell from [layout-and-ui-system](8XMYS-layout-and-ui-system.md) already covers small screens.

---

## 3. User Stories / Use Cases

Use Cases are **optional** to test, like Design Decisions (§7). `Layer` / `Status` are filled here
because each journey below has a code-verifiable consequence at this spec's scope.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| UC-DASH-001 | Admin dashboard loads system-wide statistics from cache or fresh aggregation | P0 | F | Full |
| UC-DASH-002 | Student dashboard loads academic progress scoped to the student's own registration | P0 | F | Full |
| UC-DASH-003 | Teacher dashboard loads the supervision queue scoped to supervised registrations | P0 | F | Full |
| UC-DASH-004 | Supervisor dashboard loads intern activity and pending verification tasks | P0 | F | Full |
| UC-DASH-005 | Dashboard cache is invalidated synchronously when a department changes | P1 | F | Full |
| UC-DASH-006 | Dashboard cache is invalidated synchronously when an academic year changes | P1 | F | Full |
| UC-DASH-007 | User with an unrecognized role is rejected fail-closed with a 403 | P0 | F | Full |

### 3.1 Role Journeys

#### UC-DASH-001 — Admin Dashboard Loads with System Statistics

An admin opening the dashboard after login is redirected to the admin route, where the
component asks its Read Action for statistics. On a cache hit the numbers arrive in well under
a second; on a miss the Action fans out across its models, stores the result for five minutes,
and returns people counts, pipeline figures, placement data, verification queues, certificate
totals, and readiness probes. Either path renders the same shape, which is why callers never
branch on freshness.

#### UC-DASH-002 — Student Dashboard Loads Academic Progress

When a student lands on her dashboard, the component resolves her identity, executes the
student Read Action with her user id, and renders registration state, journal counts,
attendance percentage, assignment progress, and handbook coverage. A first-year student with no
registration yet sees zeros and a full attendance bar rather than an error page, because the
absence of a placement is a normal state, not a failure.

#### UC-DASH-003 — Teacher Dashboard Loads the Supervision Queue

A teacher with thirty supervised students needs one screen answering "what needs me today":
pending journals, ungraded submissions, unresolved incidents, and supervision counts. The
teacher Read Action scopes every query through the mentor relationship so the queue contains
exactly her students, and the per-user cache key keeps one teacher's queue from ever leaking
into another's.

#### UC-DASH-004 — Supervisor Dashboard Loads Intern Activity

Out at the partner company, a supervisor opens his dashboard to find active interns, pending
evaluations, journal verification queues, and attendance awaiting confirmation. The role gate
deliberately admits admins and proxy teachers alongside supervisors, since the same verification
work is performed by different hats depending on the placement's supervision assignment.

### 3.2 Freshness and Safety Journeys

#### UC-DASH-005 — Department Change Clears Dashboard Cache

An admin renames a department at 9:04 and reloads the dashboard at 9:05 expecting the new
name's counts. Because the department events fan out to a synchronous listener that forgets the
admin key, the very next load re-aggregates instead of serving four more minutes of stale
numbers. The listener runs inline precisely so no request can slip through the gap a queued job
would leave.

#### UC-DASH-006 — Academic Year Change Clears Dashboard Cache

Activating a new academic year rewrites what "current" means for nearly every widget, so the
year events trigger the same synchronous flush as department changes. Without this, the
dashboard would confidently display last year's pipeline as today's, and nobody would suspect
the cache because the numbers look plausible.

#### UC-DASH-007 — Unknown Role Is Rejected Fail-Closed

A user whose role matches no dashboard must never see a default dashboard with someone else's
data. The routing service falls through its ordered cases to a denial, the controller never
redirects, and the framework renders a 403. Open-by-default would be a data leak; closed-by-
default is a support ticket, and that trade is always correct.

---

## 4. Functional Requirements

A Functional Requirement is a verifiable behavior the system must support. `Priority` ranks
criticality on a P0–P3 scale. `Layer` declares the test layer (`U` Unit · `F` Feature ·
`B` Browser · `A` Arch). `Status` tracks implementation of the requirement itself.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| FR-DASH-001 | `GET /dashboard` invokes the dashboard controller, resolves the role route, and redirects | P0 | F | Full |
| FR-DASH-002 | `super_admin` and `admin` resolve to the admin dashboard route | P0 | F | Full |
| FR-DASH-003 | `student` resolves to the student dashboard route | P0 | F | Full |
| FR-DASH-004 | `teacher` resolves to the teacher dashboard route | P0 | F | Full |
| FR-DASH-005 | `supervisor` resolves to the supervisor dashboard route | P0 | F | Full |
| FR-DASH-006 | Unrecognized or missing roles throw a 403; the base component is not routable and every dashboard lives at `/<role>/dashboard` | P0 | F | Full |
| FR-DASH-007 | Role priority orders admin first, then student, teacher, supervisor, then denial | P0 | F | Full |
| FR-DASH-008 | Proxy resolution returns the supervisor dashboard for teachers acting as supervisors and null otherwise | P1 | F | Full |
| FR-DASH-009 | The admin Read Action extends the read base, takes no parameters, and returns the full system statistics shape | P0 | F | Full |
| FR-DASH-010 | The student Read Action accepts a user id and returns registration, journal, attendance, assignment, and handbook figures | P0 | F | Full |
| FR-DASH-011 | The teacher Read Action reads the authenticated id and returns supervision workload figures | P0 | F | Full |
| FR-DASH-012 | The supervisor Read Action reads the authenticated id and returns intern activity figures | P0 | F | Full |
| FR-DASH-013 | The student Read Action rejects unknown users with a business-rule exception | P0 | F | Full |
| FR-DASH-014 | The student Read Action handles missing registrations with zeroed counts and full attendance | P1 | F | Full |
| FR-DASH-015 | Teacher and supervisor Reads scope queries to supervised registrations through the mentor relationship | P0 | F | Full |
| FR-DASH-016 | All dashboard data is cached with a 300-second TTL | P0 | F | Full |
| FR-DASH-017 | Admin cache key resolves from the registered admin statistics key | P0 | A | Full |
| FR-DASH-018 | Student cache key appends the user id to the registered student key | P0 | A | Full |
| FR-DASH-019 | Teacher cache key appends the user id to the role-prefixed statistics key | P0 | A | Full |
| FR-DASH-020 | Supervisor cache key appends the user id to the role-prefixed statistics key | P0 | A | Full |
| FR-DASH-021 | Cache keys are declared in `config/cache-keys.php`; inline key strings are forbidden | P0 | A | Full |
| FR-DASH-022 | Department create, update, and delete events synchronously forget the admin dashboard key | P1 | F | Full |
| FR-DASH-023 | Academic year create, activate, update, and delete events synchronously forget the admin dashboard key | P1 | F | Full |
| FR-DASH-024 | Cache invalidation listeners execute synchronously, never queued | P1 | F | Full |
| FR-DASH-025 | The base dashboard component provides the authenticated user and five recent activity entries | P1 | F | Full |
| FR-DASH-026 | The admin component executes its Read Action on mount, runs readiness checks, and renders stats with readiness | P0 | F | Full |
| FR-DASH-027 | The student component gates to the student role, executes its Read Action, and renders progress | P0 | F | Full |
| FR-DASH-028 | The teacher component gates to teacher or admin, executes its Read Action, and renders the queue | P0 | F | Full |
| FR-DASH-029 | The supervisor component gates to supervisor, admin, or teacher, executes its Read Action, and renders activity | P0 | F | Full |
| FR-DASH-030 | Readiness checks probe database, mail, cache, queue, and storage without throwing | P1 | F | Full |
| FR-DASH-031 | Each dashboard injects its Read Action through the constructor or mount signature; service location is forbidden | P0 | A | Full |
| FR-DASH-032 | The admin render passes role-content, stats, and readiness to its view | P2 | F | Full |
| FR-DASH-033 | Super-admin-only system cards render only for the super admin role | P1 | F | Full |
| FR-DASH-034 | `GET /dashboard` requires authentication; each role dashboard requires its role middleware | P0 | F | Full |
| FR-DASH-035 | Role dashboard routes follow the `/<role>/dashboard` shape with matching route names | P0 | F | Full |
| FR-DASH-036 | Role middleware on the teacher and supervisor routes admits their documented proxy roles | P1 | F | Full |
| FR-DASH-037 | Dashboard views render labels through `__()` keys present in both locales | P0 | A | Full |
| FR-DASH-038 | Readiness status is conveyed through text in addition to color | P1 | B | Full |
| FR-DASH-039 | Widget Read Actions stay lock-free: no transactions, no audit writes, no queued dispatch inside the read path | P0 | A | Full |
| FR-DASH-040 | Widget data arrives through direct Read Action imports per the pragmatic cross-module hierarchy | P1 | A | Full |

### 4.1 Routing

#### FR-DASH-001 — Single entry redirects by role

The generic dashboard URL owns no pixels of its own; it resolves the caller's highest-priority
role and redirects to that role's dashboard. Keeping this hop in a thin controller over a
routing service means priority logic stays unit-testable while HTTP concerns stay at the edge.

#### FR-DASH-002 — Admin and super admin share the admin view

Both administrative roles land on the same system-wide dashboard because their operational
questions overlap completely, with super-admin-only cards gated inside the view rather than
splitting the route. One admin dashboard halves the aggregation surface that caching and
invalidation must cover.

#### FR-DASH-003 — Students reach the student view

A student hitting the generic URL arrives at the student dashboard carrying her own progress,
never anyone else's. The redirect is deterministic from role alone, so bookmarks and
post-login hops behave identically.

#### FR-DASH-004 — Teachers reach the teacher view

Teacher traffic resolves to the supervision queue, which reflects the teacher's daily reality
far better than system-wide counts would. Routing by role rather than by link keeps deep links
shareable: the same generic URL does the right thing for every hat.

#### FR-DASH-005 — Supervisors reach the supervisor view

Industry supervisors land on intern activity and verification queues scoped to their company
context. Their dashboard answers "who needs my sign-off" before they have to ask.

#### FR-DASH-006 — Unknown roles denied, base never routable

If no case matches, the service throws instead of guessing, and the base component exists only
for inheritance — it answers no URL. An engineer adding a sixth role gets a loud 403 in
testing rather than a silent misroute in production, which is exactly the alarm the fail-closed
default is for.

#### FR-DASH-007 — Ordered priority for multi-role users

Users holding several roles resolve through an ordered match with administrators first and
denial last. The ordering is load-bearing: without it, a teacher-admin would land on a
different dashboard depending on role iteration order, and "my bookmark shows the wrong page"
tickets would never reproduce.

#### FR-DASH-008 — Proxy resolution for cross-role supervision

A teacher assigned as a supervisor proxy needs the supervisor's lens without surrendering her
own role. The proxy resolver returns the supervisor route for exactly that case and null
otherwise, keeping the cross-role view an explicit, testable decision instead of middleware
luck.

### 4.2 Data Aggregation

#### FR-DASH-009 — Admin aggregation shape

The admin Read Action fans out across users, internships, registrations, placements,
attendance, logbooks, certificates, companies, throughput, and audit entries into one fixed
associative shape. Fixing the key set matters more than any single count: every consumer —
component, test, and future export — programs against the same contract.

#### FR-DASH-010 — Student aggregation shape

Given a user id, the student Action assembles registration state, journal totals, attendance
percentage, assignment progress, and handbook coverage. The shape is deliberately small because
it answers one question — "how am I doing" — and small shapes stay cache-friendly per user.

#### FR-DASH-011 — Teacher aggregation shape

The teacher Action reads the authenticated id itself rather than accepting it as a parameter,
which removes an entire class of "view another teacher's queue" bugs by construction. Its six
figures map one-to-one onto the supervision queue the component renders.

#### FR-DASH-012 — Supervisor aggregation shape

Active interns, pending evaluations, verified and pending journals, and pending attendance give
the supervisor a complete sign-off backlog in five numbers. Like its teacher sibling, it
self-scopes to the caller so the controller never handles identity plumbing.

#### FR-DASH-013 — Unknown student rejected as a business rule

Querying the student Action with a nonexistent user throws a translatable business-rule
exception rather than returning an empty dashboard that looks like a new enrollee. The
distinction protects support staff from misreading "no data" as "no registration."

#### FR-DASH-014 — Missing registration degrades to zeros

A freshly created student account has no registration row yet, and that is a normal morning,
not an edge case. Defaulting counts to zero with full attendance keeps the page welcoming
while the enrollment flow catches up, and no null ever reaches the view.

#### FR-DASH-015 — Mentor-scoped teacher and supervisor queries

Both staff Actions constrain their queries through the mentor pivot to the caller's supervised
registrations. The scope is the privacy boundary: without it, one teacher's dashboard would
expose another department's students, and the per-user cache keys would merely cache the leak
faster.

### 4.3 Caching

#### FR-DASH-016 — Five-minute TTL on all widget data

Every dashboard read flows through a remember-call with a 300-second TTL, balancing the
two-second aggregation cost against five minutes of acceptable staleness. During the morning
rush, when hundreds of students check progress within the same hour, the hit rate carries the
database.

#### FR-DASH-017 — Registered admin key

The admin statistics key resolves from the central cache-key registry rather than a string
literal, so a key rename touches config once instead of hunting string occurrences across
Actions and listeners. Greppability is a feature when invalidation must be audited.

#### FR-DASH-018 — Per-user student key

Student data is personal, so the cache key carries the user id and two students never share an
entry. The suffix convention is fixed in one place, which keeps key construction reviewable
instead of improvised per call site.

#### FR-DASH-019 — Per-user teacher key

Although the underlying statistics are system-shaped, teacher queries are scoped to supervised
registrations and therefore keyed per user. Sharing one key across teachers would serve one
teacher's queue to another — correct caching of the wrong data.

#### FR-DASH-020 — Per-user supervisor key

The supervisor key follows the same per-user discipline for the same reason: scoped queries
demand scoped keys. Symmetry across the three staff keys also means the invalidation story
stays uniform.

#### FR-DASH-021 — Registry-only keys, no inline strings

Any cache key string appearing outside the registry file is a violation, because inline keys
defeat the audit that invalidation depends on. The architecture scan enforces this the way it
enforces other structural rules: deterministically, before commit.

### 4.4 Cache Invalidation

#### FR-DASH-022 — Department events flush the admin key

Department creation, update, and deletion each dispatch events that a dedicated listener
answers by forgetting the admin dashboard key. Structural counts change rarely but matter
everywhere, so the flush is immediate and unconditional.

#### FR-DASH-023 — Academic year events flush the admin key

Year creation, activation, update, and deletion trigger the same synchronous flush through
their own listener. Activation is the sharpest case: flipping the current year rewrites the
meaning of "active" across widgets, and serving the old year's numbers afterward would be
confidently wrong.

#### FR-DASH-024 — Synchronous listeners, never queued

Both invalidation listeners run inline in the request that made the change, because a queued
flush leaves a window where the next dashboard load re-caches stale data for another five
minutes. Structural edits are low-frequency admin operations, so the inline cost is negligible
and the correctness gain is total.

### 4.5 UI Components

#### FR-DASH-025 — Base component with user and activity

The shared base exposes the authenticated user and the five most recent activity entries to
every role dashboard, so leaf components inherit identity and the activity rail without
repeating queries. Thirty lines of base class absorb what would otherwise be four copies of
the same two methods.

#### FR-DASH-026 — Admin component mounts stats and readiness

On mount the admin component executes its Read Action, runs the five infrastructure probes,
and hands stats plus readiness to the view. Separating data aggregation (the Action) from
health probing (the component) keeps domain reads cacheable while readiness stays live.

#### FR-DASH-027 — Student component with role gate

The student component aborts non-students at boot, then loads progress through its Action.
Gating in the component as well as the route middleware gives defense in depth: a route
misconfiguration degrades to a 403 page rather than a data leak.

#### FR-DASH-028 — Teacher component with widened gate

Teachers and admins pass the teacher gate, reflecting the reality that admins supervise
queues during staff shortages. The widened gate is documented and tested, not accidental,
because every extra admitted role is a deliberate trust decision.

#### FR-DASH-029 — Supervisor component with proxy gate

Supervisors, admins, and proxy teachers pass the supervisor gate, mirroring the cross-role
proxy model from the authorization spec. A teacher covering a factory visit sees exactly the
verification backlog she needs, under her own identity, with no role juggling.

#### FR-DASH-030 — Five readiness probes that never throw

Database connectivity, mail configuration, cache round-trip, queue driver, and storage
writability each resolve to a boolean, with exceptions caught and recorded as unhealthy.
A health panel that crashes while reporting health would be dark comedy; the catch-everything
contract keeps it merely informative.

#### FR-DASH-031 — Injected Actions, never located

Each dashboard receives its Read Action through injection rather than resolving it from the
container mid-method. The discipline keeps dependencies visible in signatures, which is what
lets tests substitute fakes and reviewers see the data path at a glance.

#### FR-DASH-032 — Admin view receives its full payload

The admin render passes the role-content flag alongside stats and readiness so the view can
branch super-admin cards without additional queries. Explicit payload passing beats implicit
view composers here because the data's freshness story belongs to the component.

#### FR-DASH-033 — Super-admin cards stay gated

System cards showing audit entries, runtime versions, and storage summaries render only for
the super admin role check. Operational internals are need-to-know, and the gate keeps curious
but unauthorized admins from browsing infrastructure details.

### 4.6 Routes and Presentation Rules

#### FR-DASH-034 — Authentication and role middleware

The generic dashboard requires authentication while each role route adds its role middleware,
so unauthenticated traffic never reaches aggregation and cross-role visits fail at the edge.
Middleware plus component gates give two independent checkpoints on every dashboard URL.

#### FR-DASH-035 — Uniform role route shape

Every role dashboard lives at its prefixed path with a matching route name, which keeps link
generation, menu config, and redirect targets mechanical. Uniformity here is what lets the
routing service, the menu, and the tests agree without a mapping table.

#### FR-DASH-036 — Proxy roles admitted at the edge

Teacher and supervisor routes admit their documented proxy roles in middleware, not just in
component gates, so a proxied teacher's navigation and direct links behave consistently.
Edge and component gates mirror each other deliberately — redundancy, not duplication.

#### FR-DASH-037 — Translated labels in both locales

All dashboard captions resolve through the translation helper with keys present in English
and Indonesian. The duality scan covers dashboard strings like any other user-facing copy,
because a supervision queue labeled in only one language fails half its users.

#### FR-DASH-038 — Status beyond color

Readiness indicators pair their color with explicit text, so a red-green distinction never
carries meaning alone. An operator checking system health on a monochrome printout or with
color-vision deficiency gets the same verdict as everyone else.

### 4.7 Cross-Cutting Discipline

#### FR-DASH-039 — Lock-free widget reads

Dashboard Reads never open transactions, write audit entries, or dispatch follow-up work —
they are deliberately crippled query paths. During enrollment-week contention this is what
keeps a thousand concurrent dashboard loads from serializing behind placement writes; the
morning rush reads while the office writes, and neither blocks the other.

#### FR-DASH-040 — Direct Read Action imports for widget data

Widgets obtain cross-module figures by importing the owning module's public Read Action, per
the pragmatic communication hierarchy's delegation rung. Events would hide a mandatory data
need behind fire-and-forget indirection, and contracts would add ceremony for a synchronous
call — direct delegation keeps the call graph readable exactly where readability matters most.

---

## 5. Non-Functional Requirements

Widget constraints with measurable targets. `Target` holds the concrete SLO; `N/A` means
architectural enforcement verified via scans or tests.

| ID | Requirement | Target | Priority | Layer | Status |
|----|-------------|--------|----------|-------|--------|
| NFR-DASH-001 | Cache hit rate exceeds 90% under normal repeated-visit usage | > 90% | P2 | F | Full |
| NFR-DASH-002 | Student cache entries are strictly per-user with no cross-user leakage | 0 leaks | P0 | F | Full |
| NFR-DASH-003 | Admin cache key namespace separates role-prefixed staff keys from the system key | 0 collisions | P1 | A | Full |
| NFR-DASH-004 | Dashboard renders remaining stats when an individual stat query fails | 100% partial render | P1 | F | Full |
| NFR-DASH-005 | Missing users and missing registrations degrade through defined paths, never unhandled nulls | 0 unhandled nulls | P0 | F | Full |
| NFR-DASH-006 | Invalidation listeners execute synchronously with no queued jobs on the path | 0 queued | P1 | F | Full |
| NFR-DASH-007 | Readiness probes catch exceptions and report unhealthy instead of throwing | 0 throws | P1 | F | Full |
| NFR-DASH-008 | Cache-hit dashboard loads stay interactive quickly; cache-miss loads stay within the miss budget | < 200ms hit / < 2s miss | P1 | B | Full |
| NFR-DASH-009 | Stale data after a structural change is bounded to a single request cycle | < 1 cycle | P1 | F | Full |
| NFR-DASH-010 | Dashboard chrome meets WCAG 2.1 Level AA | AA | P1 | B | Full |
| NFR-DASH-011 | Dashboard is fully navigable by keyboard in logical reading order | 0 dead ends | P1 | B | Full |
| NFR-DASH-012 | Readiness status never relies on color alone | text + color | P1 | B | Full |
| NFR-DASH-013 | All dashboard labels use the translation helper | 0 hardcoded | P0 | A | Full |
| NFR-DASH-014 | Translation keys exist in both locale files | 0 missing | P0 | A | Full |
| NFR-DASH-015 | Aggregation lives in Read Actions extending the read base under the Action Triad | 0 inline queries | P0 | A | Full |
| NFR-DASH-016 | Each dashboard component is a single-responsibility class in its module namespace | 1 concern each | P1 | A | Full |

### 5.1 Performance and Isolation

#### NFR-DASH-001 — Hit rate under normal usage

With a five-minute TTL covering repeated visits, the overwhelming majority of dashboard loads
should be cache hits. The ninety-percent figure is an operational expectation rather than a
gating test — it tells operators whether the TTL still fits traffic, not whether a deploy may
ship.

#### NFR-DASH-002 — Per-user isolation for students

A student opening her dashboard must be cryptographically certain the numbers are hers, which
per-user keys plus scoped queries jointly guarantee. The dedicated test logs in as two
students in sequence and asserts neither sees the other's figures — the kind of test that only
needs to fail once to justify its existence.

#### NFR-DASH-003 — Collision-free staff key namespaces

Role-prefixed staff keys share a namespace family with the system key, so the prefix scheme
must provably separate them. A collision here would be silent and plausible-looking, serving
one role's numbers under another's banner, which is why the scheme is reviewed as a contract
rather than left to convention.

### 5.2 Resilience

#### NFR-DASH-004 — Partial render on stat failure

When one upstream query breaks — a migration mid-deploy, a transient lock timeout — the
dashboard renders every surviving stat instead of a blank error page. Operators lose one
number, not the whole morning, and the failure surfaces where it can be fixed rather than
where it blocks work.

#### NFR-DASH-005 — Defined degradation for missing data

Unknown users throw the business exception; missing registrations zero out gracefully. Both
paths are specified so precisely because "no data" spans two meanings — an error versus a new
beginning — and confusing them either alarms unnecessarily or hides real breakage.

#### NFR-DASH-006 — No queued jobs on the invalidation path

Synchronous invalidation is a latency choice with a correctness payoff, and this constraint
locks it in against future "optimization" that would reintroduce the stale window. Any
proposal to queue these listeners must first answer where the next-request freshness guarantee
goes.

#### NFR-DASH-007 — Probes report, never throw

Each readiness probe converts failure into a false value with context, because the health
panel's entire job is describing brokenness. A probe that throws during an outage hides the
outage behind a framework error page — the one moment its report matters most.

#### NFR-DASH-008 — Hit and miss latency budgets

Cache hits should feel instant and even full aggregations should complete within a couple of
seconds on school hardware. These budgets are validated by timed browser journeys rather than
unit tests, since only the full stack — queries, cache, rendering — produces an honest number.

#### NFR-DASH-009 — Single-cycle staleness bound

After any structural change, at most one request cycle may serve pre-change numbers, and in
practice zero do. The bound exists so future caching layers — HTTP caches, edge caches — inherit
the same contract instead of quietly extending the stale window.

### 5.3 Access and Localization

#### NFR-DASH-010 — AA conformance for dashboard chrome

Dashboards inherit the shell's accessibility contract and add their own obligations: stat cards
readable by assistive technology, meaningful heading order over the widget grid. Conformance is
checked by browser journeys and manual review, matching the shell's approach.

#### NFR-DASH-011 — Keyboard traversal in reading order

Tab order follows the visual reading order across widgets, readiness panels, and navigation,
with no focus traps. The dashboard is many operators' home screen, so a keyboard dead end here
repeats dozens of times a day.

#### NFR-DASH-012 — Text alongside color for status

Every readiness dot, badge, and state pill pairs its hue with words. This duplicates the
functional rule at the constraint level deliberately: presentation requirements can drift
during redesigns, while the non-functional bar holds regardless of which component renders it.

#### NFR-DASH-013 — Translation helper on all labels

No dashboard string bypasses the localization helper — not headings, not empty states, not
probe labels. The architecture scan asserts this structurally, catching the hardcoded string
that review missed.

#### NFR-DASH-014 — Both locales carry every key

Each dashboard key resolves in English and Indonesian alike. Missing-key failures surface at
the worst moments — a supervisor switching locales mid-visit — so the duality check runs
before commit rather than after a support call.

### 5.4 Structural Discipline

#### NFR-DASH-015 — Triad-conformant aggregation

Every aggregation path is a read-base Action with no mutations, no inline component queries,
and no service-locator lookups. The class-contract scan proves conformance mechanically, which
matters because "just this once" inline queries are how dashboards traditionally rot.

#### NFR-DASH-016 — One concern per dashboard class

Each dashboard component lives in its module namespace owning exactly its role's view. The day
a dashboard starts computing another role's figures, reviewers have a named rule to point at
instead of a vague sense of unease.

---

## 6. API / Data Contracts

### 6.1 DashboardService

```php
// app/Modules/User/Services/DashboardService.php
class DashboardService
{
    public function getDashboardForUser(User $user): string;
    public function getProxyDashboardForUser(User $user): ?string;
    public function getSharedStats(): array;
}
```

### 6.2 DashboardController

```php
// app/Modules/User/Http/Controllers/DashboardController.php
class DashboardController extends BaseController
{
    public function __invoke(Request $request, DashboardService $dashboardService): RedirectResponse;
}
```

### 6.3 UserDashboard (Base Component)

```php
#[Layout('core::layouts.app')]
class UserDashboard extends Component
{
    public function getUser(): ?User;
    public function getRecentActivities(): Collection; // 5 most recent ActivityLog
    public function render(): View; // user.dashboard.index
}
```

### 6.4 Role-Specific Dashboard Components

```php
class AdminDashboard extends UserDashboard
{
    public array $stats = [];
    public array $readiness = []; // database, mail, cache, queue, storage
    public function mount(ReadAdminDashboardAction $statsAction): void;
    public function render(): View;
}

class StudentDashboard extends UserDashboard
{
    public ?Registration $registration = null;
    public int $totalJournals = 0;
    public int $verifiedJournals = 0;
    public float $attendancePercent = 100.0;
    public int $assignmentSubmittedCount = 0;
    public int $assignmentTotalCount = 0;
    public int $handbookReadCount = 0;
    public int $handbookTotalCount = 0;
    public function boot(): void;
    public function mount(ReadStudentDashboardAction $action): void;
}

class TeacherDashboard extends UserDashboard
{
    public int $supervisedStudents = 0;
    public int $pendingJournals = 0;
    public int $activeCompanies = 0;
    public int $ungradedSubmissions = 0;
    public int $supervisionLogsCount = 0;
    public int $unresolvedIncidents = 0;
    public function boot(): void;
    public function mount(ReadTeacherDashboardAction $action): void;
}

class SupervisorDashboard extends UserDashboard
{
    public int $activeInterns = 0;
    public int $pendingEvaluations = 0;
    public int $verifiedJournals = 0;
    public int $pendingJournals = 0;
    public int $pendingAttendance = 0;
    public function boot(): void;
    public function mount(ReadSupervisorDashboardAction $action): void;
}
```

### 6.5 Read Actions

```php
final class ReadAdminDashboardAction extends BaseReadAction
{
    public function execute(): array; // remember(admin key, 300s); fixed system-wide shape
}

final class ReadStudentDashboardAction extends BaseReadAction
{
    public function execute(string $userId): array; // remember(per-user key, 300s)
}

final class ReadTeacherDashboardAction extends BaseReadAction
{
    public function execute(): array; // self-scoped via Auth::id(); remember(per-user key, 300s)
}

final class ReadSupervisorDashboardAction extends BaseReadAction
{
    public function execute(): array; // self-scoped via Auth::id(); remember(per-user key, 300s)
}
```

### 6.6 Cache Invalidation Listeners

```php
final class ClearDashboardCacheOnDepartmentChange
{
    public function handle(DepartmentCreated|DepartmentDeleted|DepartmentUpdated $event): void;
}

final class ClearDashboardCacheOnYearChange
{
    public function handle(AcademicYearCreated|AcademicYearActivated|AcademicYearUpdated|AcademicYearDeleted $event): void;
}
```

### 6.7 Cache Keys

```php
// config/cache-keys.php
'admin_dashboard_stats' => 'sysadmin.dashboard.stats',
'dashboard_student'     => 'dashboard.student.',
```

### 6.8 Routes

```php
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
});

Route::prefix('admin')->name('sysadmin.')->middleware(['auth', 'role:super_admin|admin'])->group(function () {
    Route::livewire('/dashboard', AdminDashboard::class)->name('dashboard');
});

Route::prefix('student')->name('student.')->middleware(['auth', 'role:student'])->group(function () {
    Route::livewire('/dashboard', StudentDashboard::class)->name('dashboard');
});

Route::prefix('teacher')->name('teacher.')->middleware(['auth', 'role:teacher|admin'])->group(function () {
    Route::livewire('/dashboard', TeacherDashboard::class)->name('dashboard');
});

Route::prefix('supervisor')->name('supervisor.')->middleware(['auth', 'role:supervisor|admin|teacher'])->group(function () {
    Route::livewire('/dashboard', SupervisorDashboard::class)->name('dashboard');
});
```

### 6.9 Event → Listener Registration

```
DepartmentCreated   → ClearDashboardCacheOnDepartmentChange
DepartmentUpdated   → ClearDashboardCacheOnDepartmentChange
DepartmentDeleted   → ClearDashboardCacheOnDepartmentChange
AcademicYearCreated   → ClearDashboardCacheOnYearChange
AcademicYearActivated → ClearDashboardCacheOnYearChange
AcademicYearUpdated   → ClearDashboardCacheOnYearChange
AcademicYearDeleted   → ClearDashboardCacheOnYearChange
```

---

## 7. Design Decisions

Design Decisions are **optional** to test, like Use Cases (§3). `Layer` / `Status` stay `—` unless
a decision has a code-testable consequence.

| ID | Requirement | Priority | Layer | Status |
|----|-------------|----------|-------|--------|
| DD-DASH-001 | Role routing lives in a service with ordered matching, not in middleware | P0 | — | — |
| DD-DASH-002 | Widget statistics are cached with a 300-second TTL plus event-driven invalidation | P0 | — | — |
| DD-DASH-003 | Role dashboards extend a shared base component following the template-method shape | P1 | — | — |
| DD-DASH-004 | Student keys are per-user while admin data uses one system key | P0 | — | — |
| DD-DASH-005 | Invalidation listeners run synchronously rather than queued | P1 | — | — |
| DD-DASH-006 | Infrastructure readiness probes live in the component, not in the data Action | P1 | — | — |

### 7.1 Routing and Caching

#### DD-DASH-001 — Service routing over middleware

Middleware can permit or deny, but it cannot express "administrators first, then students, then
teachers" with a fallback denial. The routing service owns that ordered match in testable
isolation while the controller stays a thin redirect edge — a split that also keeps priority
changes out of route files.

#### DD-DASH-002 — TTL caching with structural invalidation

Read-heavy, write-light statistics tolerate minutes of staleness for everything except
structural edits, so a flat five-minute TTL covers the common case while synchronous
listeners cover the sharp one. The alternative — no cache, or per-row cache with complex
dependencies — would trade a simple, explainable contract for precision nobody's decisions
require.

### 7.2 Composition and Health

#### DD-DASH-003 — Base component with leaf specialization

Shared identity and activity behavior live in one small base while each role dashboard adds
its own aggregation and gates. Composition was considered, but inheritance matches the
uniform "mount, gate, render" lifecycle closely enough that four thin subclasses stay honest.

#### DD-DASH-004 — Key strategy by data shape

System-wide admin figures share one key because they are identical for every admin; personal
and scoped figures carry user ids because sharing them would leak across users. The key
strategy therefore mirrors the privacy shape of the data, which keeps cache review and
privacy review as a single conversation.

#### DD-DASH-005 — Inline invalidation

Structural changes are rare admin operations whose very next read must be fresh, and only
synchronous listeners guarantee that ordering. Queueing would buy throughput nobody needs
while selling a race condition every deploy would eventually meet.

#### DD-DASH-006 — Probes beside data, not inside it

Readiness checks test infrastructure while the data Action aggregates domain state — different
concerns with different freshness needs. Keeping probes in the component leaves the Action
cacheable and honest about what it owns, and keeps health logic out of the domain's tests.

---

## 8. Success Metrics

| Metric | Target | How to measure |
|--------|--------|----------------|
| Cache-hit dashboard load | < 200ms | Timed browser journey |
| Cache-miss dashboard load | < 2s | Timed browser journey |
| Staleness after structural change | < 1 request cycle | Edit-then-reload drill |
| Cache hit rate under normal usage | > 90% | Cache observation |
| Role routing correctness | 100% | Service tests per role |
| Proxy routing for assigned teachers | Supervisor view served | Proxy service test |
| Unknown-role handling | 403 fail-closed | Denial test |
| Cache keys registered centrally | 100% | Registry scan |

---

## 9. Roadmap

### Prerequisites

| Spec | What It Provides |
|------|------------------|
| [authentication](YB7RG-authentication.md) | Authenticated user with role and session state for personalization |
| [notification-infrastructure](TXR2H-notification-infrastructure.md) | Activity and notification sources surfaced on dashboards |

### Build Guide

After implementing this spec, every role lands on a relevant dashboard with cached statistics,
readiness probes, and proxy-aware routing — the post-login home for the whole product.
Dashboard stats are read-only views over other modules' data. Build the institutional structure
next: departments and academic years, whose counts and events already flow through these widgets.

### Next Steps

| Order | Spec | Connection |
|-------|------|------------|
| 1 | [department-management](4HWSB-department-management.md) | Department counts feed admin stats; department events invalidate them |
| 2 | [academic-year-management](XW6F5-academic-year-management.md) | Active-year state scopes widgets; year events invalidate them |

---

## 10. Risks & Assumptions

| ID | Risk / Assumption / Open Question | Status | Owner | GH Issue |
| --- | --------------------------------- | ------ | ----- | -------- |

## Quick References

- [Spec template](../templates/spec-template.md) — the 10-section skeleton + requirement-ID rules
- [Spec registry](index.md) — all feature specs grouped in 12 phases
- [Architecture](D2FT3-architecture.md) — Action Triad, lock-free Reads, and the communication hierarchy
- [Layout & UI system](8XMYS-layout-and-ui-system.md) — the shell dashboards render inside
- [Authentication](YB7RG-authentication.md) — roles and session state behind routing
- [RBAC & authorization](T4B26-rbac-and-authorization.md) — cross-role proxy model
- [Performance ADR](../adr/adr-performance-optimization.md) — Tier 0 no-regret rules behind caching
- [Cross-module communication ADR](../adr/adr-cross-module-communication.md) — why widgets use direct Read Action imports
