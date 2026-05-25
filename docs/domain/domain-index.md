# Domain Index

> 24 business domains organized by the internship lifecycle.
> Each domain owns its complete vertical slice: persistence, business rules,
> UI components, authorization, and HTTP interface.

## Foundation

| Domain | Purpose | Reference |
|---|---|---|
| **Core** | Base classes, contracts, exceptions, logging infrastructure | [API Reference](core-reference.md) |
| **Shared** | Cross-domain utilities (Theme, CsvHandler, Environment, Locale) | [API Reference](shared-reference.md) |

## Identity & Access

| Domain | Purpose | Reference |
|---|---|---|
| **Auth** | Login, authentication, RBAC, account lifecycle, recovery | [API Reference](auth-reference.md) |
| **User** | User profiles, identity management, dashboard routing | [API Reference](user-reference.md) |

## Institution

| Domain | Purpose | Reference |
|---|---|---|
| **School** | Schools, departments, academic years | [API Reference](school-reference.md) |
| **Settings** | Runtime configuration, key-value store, branding | [API Reference](settings-reference.md) |
| **Setup** | First-run installation wizard, environment audit, provisioning | [API Reference](setup-reference.md) |

## Internship Lifecycle

| Domain | Purpose | Reference |
|---|---|---|
| **Partnership** | Companies, MoU agreements, contact management | [API Reference](partnership-reference.md) |
| **Placement** | Slot management, quotas, direct assignments, change requests | [API Reference](placement-reference.md) |
| **Registration** | Student enrollment, applications, document upload, wizard | [API Reference](registration-reference.md) |
| **Mentee** | Student role activation, dashboard, program participation | [API Reference](mentee-reference.md) |
| **Internship** | Program execution, reports, document requirements | [API Reference](internship-reference.md) |

## Execution

| Domain | Purpose | Reference |
|---|---|---|
| **Mentor** | Mentoring, supervision logs, team groupings | [API Reference](mentor-reference.md) |
| **Attendance** | Clock-in/out, geo-location, absence requests | [API Reference](attendance-reference.md) |
| **Logbook** | Daily student journal entries, verification | [API Reference](logbook-reference.md) |
| **Schedule** | Calendar events, deadlines, event scheduling | [API Reference](schedule-reference.md) |
| **Assignment** | Task creation, submissions, grading workflow | [API Reference](assignment-reference.md) |
| **Guidance** | Handbooks, versioned documents, acknowledgements | [API Reference](guidance-reference.md) |
| **Incident** | Issue reporting, investigation, resolution workflow | [API Reference](incident-reference.md) |

## Evaluation & Completion

| Domain | Purpose | Reference |
|---|---|---|
| **Assessment** | Rubrics, competencies, scoring, presentations | [API Reference](assessment-reference.md) |
| **Evaluation** | Mentor quality evaluation, feedback collection | [API Reference](evaluation-reference.md) |
| **Document** | Template management, PDF rendering, report generation | [API Reference](document-reference.md) |
| **Certificate** | Credentialing, template-based issuance, revocation | [API Reference](certificate-reference.md) |

## Administration

| Domain | Purpose | Reference |
|---|---|---|
| **Admin** | User CRUD, announcements, GDPR compliance, system oversight | [API Reference](admin-reference.md) |

## Reading Order

Domains are ordered by their position in the internship lifecycle:

```
Foundation → Identity → Institution → Partnership → Placement
→ Registration → Internship (Execution → Assessment → Certification)
```

Start with **Core** and **Auth**, then proceed based on the feature you are
implementing. Each domain doc links to related upstream and downstream domains.

## DomainServiceProvider

Registered in `bootstrap/providers.php` alongside `AppServiceProvider`.
Handles all cross-domain infrastructure in a single place:

| Responsibility | Method | Auto-Discovery |
|---|---|---|
| **Livewire components** | `discoverLivewireComponents()` | ✅ Scans `app/Domain/*/Livewire/`, registers as `{kebab-domain}.{kebab-class}` |
| **Policies** | `discoverPolicies()` | ✅ Scans `app/Domain/*/Policies/`, auto-links to model matching policy name |
| **Blade namespaces** | `registerBladeNamespaces()` | ✅ Scans `resources/views/*/`, registers as `x-{domain}::` + `{domain}::` view namespace |
| **Blade: layouts** | `boot()` | Manual: `resources/views/shared/layouts/` → `x-shared::layouts.*` |
| **Events** | `boot()` | Manual: `SetupFinalized` → `LogSetupFinalized` listener |
| **Policies (cross-domain)** | `boot()` | Manual: `UserPolicy`, `InternshipPlacementPolicy`, `InternshipRegistrationPolicy`, `CompanyPolicy` |
| **Container bindings** | `register()` | Manual: `SendsNotifications` → `SendNotificationAction` |

### Blade Namespace Convention

```
views/shared/
├── layouts/          x-shared::layouts.*       (app, base, guest, header, sidebar)
├── ui/               x-shared::ui.*            (brand, logo, credits, navbar-actions, etc.)
├── widgets/          x-shared::widgets.*       (stat-card, profile-summary, quick-link, etc.)
views/auth/layouts/   auth::layouts.*           (auth-specific layouts)
views/setup/layouts/  setup::layouts.*          (setup wizard layout)
views/{domain}/       {domain}::*               (auto-discovered per domain)
```

The `layouts`, `ui`, and `widgets` directories under `shared/` are all accessed
via the `shared` namespace — no need for separate namespace registrations.

### Excluded Directories

Directories excluded from auto-discovery in `registerBladeNamespaces()`:
`components`, `emails`, `errors`, `mcp`, `pdf`, `vendor`. These are either
structural (not domain views) or belong to third-party packages.

## References

- `docs/architecture.md` — 12-layer architecture, domain structure diagram
- `docs/conventions.md` — coding conventions for domain classes
- `docs/erd/00-erd-index.md` — complete ERD organized by data lifecycle
