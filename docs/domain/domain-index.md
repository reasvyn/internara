# Domain Index

> Last updated: 2026-06-03
> Changes: merge Auth into User domain; add Evaluation as standalone domain; update domain counts

> 16 domains organized by the internship (PKL) lifecycle. Each domain owns its complete vertical
> slice: persistence, business rules, UI components, authorization, and HTTP interface.

## Domains by Operational Flow

| # | Domain | Purpose | Key Aggregates |
|---|--------|---------|----------------|
| 1 | **Core** | Platform root: base classes, contracts, exceptions, logging, caching, middleware, DTOs, cross-domain Blade views | — (infrastructure) |
| 2 | **User** | Authentication, profiles, notifications, account lifecycle, recovery, RBAC | `Login/`, `Password/`, `ActivationToken/`, `AccountRecovery/`, `AccountStatus/`, `Profile/`, `Notification/` |
| 3 | **Academics** | School profile, departments (program keahlian), academic years, first-run wizard | `School/`, `Department/`, `AcademicYear/`, `Setup/` |
| 4 | **Partners** | Companies (DUDI), MoU agreements, contact management | `Company/`, `Partnership/` |
| 5 | **Program** | PKL program lifecycle, phases, groups, document requirements | `Internship/`, `InternshipPhase/`, `InternshipGroup/`, `DocumentRequirement/` |
| 6 | **Enrollment** | Student registration, placement, slot management, change requests | `Registration/`, `AccountApplication/`, `RegistrationDocument/`, `Placement/`, `PlacementChangeRequest/` |
| 7 | **Guidance** | Student role activation, supervision logs, handbooks, acknowledgements | `Mentee/`, `Mentor/`, `SupervisionLog/`, `Handbook/`, `HandbookAcknowledgement/` |
| 8 | **Journals** | Daily logbook, attendance (clock-in/out), absence requests, scheduling | `Attendance/`, `AbsenceRequest/`, `Logbook/`, `IndustryAssessment/`, `Schedule/` |
| 9 | **Assignments** | Task creation, submissions, grading workflow | `Assignment/`, `Submission/` |
| 10 | **Reports** | Student final reports, revisions, supervisor review | `Report/` |
| 11 | **Assessment** | Competency rubrics, scoring, presentations | `Assessment/`, `Rubric/`, `Competency/`, `Indicator/`, `Presentation/` |
| 12 | **Evaluation** | Program evaluation and user feedback | `Evaluation/` |
| 13 | **Certification** | Certificate issuance, templates, document generation, PDF rendering | `Certificate/`, `CertificateTemplate/`, `Document/` |
| 14 | **Incidents** | Issue reporting, investigation, resolution workflow | `IncidentReport/` |
| 15 | **Settings** | Runtime configuration, key-value store, branding, localization | `Setting/` |
| 16 | **Administration** | User CRUD, announcements, GDPR compliance, system oversight | `Announcement/`, `GdprDeletionLog/` |

## Reading Order

Domains are ordered by their position in the PKL lifecycle:

```
Foundation → Identity → Institution → Partners → Enrollment
→ Program → Execution → Evaluation → Certification
```

Start with **Core** and **User**, then proceed based on the feature you are implementing. Each
domain doc links to related upstream and downstream domains.

## DomainServiceProvider

Registered in `bootstrap/providers.php` alongside `AppServiceProvider`. Handles all cross-domain
infrastructure in a single place:

| Responsibility              | Method                         | Auto-Discovery                                                                           |
| --------------------------- | ------------------------------ | ---------------------------------------------------------------------------------------- |
| **Livewire components**     | `discoverLivewireComponents()` | ✅ Scans `app/Domain/*/Livewire/`, registers as `{kebab-domain}.{kebab-class}`           |
| **Policies**                | `discoverPolicies()`           | ✅ Scans `app/Domain/*/Policies/`, auto-links to model matching policy name              |
| **Blade namespaces**        | `registerBladeNamespaces()`    | ✅ Scans `resources/views/*/`, registers as `x-{domain}::` + `{domain}::` view namespace |
| **Blade: layouts**          | `boot()`                       | Manual: `resources/views/core/layouts/` → `x-core::layouts.*`                        |
| **Events**                  | `boot()`                       | Manual: `SetupFinalized` → `LogSetupFinalized` listener                                  |
| **Policies (cross-domain)** | `boot()`                       | Manual: `InternshipRegistrationPolicy`, `CompanyPolicy`                                  |
| **Container bindings**      | `register()`                   | Manual: `SendsNotifications` → `SendNotificationAction`                                  |

### Blade Namespace Convention

```
views/core/
├── layouts/          x-core::layouts.*       (app, base, guest, header, sidebar)
├── ui/               x-core::ui.*            (brand, logo, credits, navbar-actions, etc.)
├── widgets/          x-core::widgets.*       (stat-card, profile-summary, quick-link, etc.)
views/user/layouts/   user::layouts.*           (user-specific layouts)
views/{domain}/       {domain}::*               (auto-discovered per domain)
```

The `layouts`, `ui`, and `widgets` directories under `core/` are all accessed via the `core`
namespace — no need for separate namespace registrations.

### Excluded Directories

Directories excluded from auto-discovery in `registerBladeNamespaces()`: `components`, `emails`,
`errors`, `mcp`, `pdf`, `vendor`. These are either structural (not domain views) or belong to
third-party packages.

## References

- `docs/architecture.md` — 12-layer architecture, domain structure diagram
- `docs/conventions.md` — coding conventions for domain classes
- `docs/database.md` — database design, engine comparison, index strategy
