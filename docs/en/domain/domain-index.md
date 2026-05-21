# Domain Index

> 24 business domains organized by the internship lifecycle.
> Each domain owns its complete vertical slice: persistence, business rules,
> UI components, authorization, and HTTP interface.

## Foundation

| Domain | File | Purpose |
|---|---|---|
| **Core** | [core.md](core.md) | Base classes, contracts, exceptions, logging infrastructure |
| **Shared** | [shared.md](shared.md) | Cross-domain utilities (Theme, CsvHandler, Environment, Locale) |

## Identity & Access

| Domain | File | Purpose |
|---|---|---|
| **Auth** | [auth.md](auth.md) | Login, authentication, RBAC, account lifecycle, recovery |
| **User** | [user.md](user.md) | User profiles, identity management, dashboard routing |

## Institution

| Domain | File | Purpose |
|---|---|---|
| **School** | [school.md](school.md) | Schools, departments, academic years |
| **Settings** | [settings.md](settings.md) | Runtime configuration, key-value store, branding |
| **Setup** | [setup.md](setup.md) | First-run installation wizard, environment audit, provisioning |

## Internship Lifecycle

| Domain | File | Purpose |
|---|---|---|
| **Partnership** | [partnership.md](partnership.md) | Companies, MoU agreements, contact management |
| **Placement** | [placement.md](placement.md) | Slot management, quotas, direct assignments, change requests |
| **Registration** | [registration.md](registration.md) | Student enrollment, applications, document upload, wizard |
| **Mentee** | [mentee.md](mentee.md) | Student role activation, dashboard, program participation |
| **Internship** | [internship.md](internship.md) | Program execution, briefings, reports, document requirements |

## Execution

| Domain | File | Purpose |
|---|---|---|
| **Mentor** | [mentor.md](mentor.md) | Mentoring, supervision logs, team groupings |
| **Attendance** | [attendance.md](attendance.md) | Clock-in/out, geo-location, absence requests |
| **Logbook** | [logbook.md](logbook.md) | Daily student journal entries, verification |
| **Schedule** | [schedule.md](schedule.md) | Calendar events, deadlines, briefing scheduling |
| **Assignment** | [assignment.md](assignment.md) | Task creation, submissions, grading workflow |
| **Guidance** | [guidance.md](guidance.md) | Handbooks, versioned documents, acknowledgements |
| **Incident** | [incident.md](incident.md) | Issue reporting, investigation, resolution workflow |

## Evaluation & Completion

| Domain | File | Purpose |
|---|---|---|
| **Assessment** | [assessment.md](assessment.md) | Rubrics, competencies, scoring, presentations |
| **Evaluation** | [evaluation.md](evaluation.md) | Mentor quality evaluation, feedback collection |
| **Document** | [document.md](document.md) | Template management, PDF rendering, report generation |
| **Certificate** | [certificate.md](certificate.md) | Credentialing, template-based issuance, revocation |

## Administration

| Domain | File | Purpose |
|---|---|---|
| **Admin** | [admin.md](admin.md) | User CRUD, announcements, GDPR compliance, system oversight |

## Reading Order

Domains are ordered by their position in the internship lifecycle:

```
Foundation → Identity → Institution → Partnership → Placement
→ Registration → Internship (Execution → Assessment → Certification)
```

Start with **Core** and **Auth**, then proceed based on the feature you are
implementing. Each domain doc links to related upstream and downstream domains.

## References

- `docs/en/architecture.md` — 12-layer architecture, domain structure diagram
- `docs/en/conventions.md` — coding conventions for domain classes
- `docs/en/erd/00-erd-index.md` — complete ERD organized by data lifecycle
