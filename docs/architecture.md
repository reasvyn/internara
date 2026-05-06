# Architecture Overview

Internara uses a **domain-first, action-oriented MVC** architecture:

- **Domain Layer** (`app/Domain/`) — Pure PHP business rules, framework-agnostic
- **Action Layer** (`app/Actions/`) — Single-purpose use case classes
- **Presentation Layer** (`app/Livewire/`, `app/Http/`, `app/Console/`) — User interfaces
- **Models** (`app/Domain/{Feature}/Models/`) — Eloquent persistence within domain boundaries

## Layered Structure

```
app/
├── Actions/                    # Use cases (entry points)
│   └── {Domain}/               # Grouped by business domain
│       └── *Action.php         # Single-purpose execute() method
├── Domain/                     # Business rules (pure PHP)
│   └── {Domain}/
│       ├── Data/               # Immutable DTOs (input/output)
│       ├── Enums/              # Status, type, category definitions
│       ├── Events/             # Domain events
│       ├── Exceptions/         # Domain-specific exceptions
│       ├── Models/             # Eloquent models (persistence)
│       ├── Notifications/      # Domain notifications
│       ├── Services/           # Framework-adjacent utilities (audit, etc.)
│       └── Policies/           # Authorization policies
├── Livewire/                   # Reactive UI components
├── Http/
│   ├── Controllers/            # Thin HTTP controllers
│   ├── Middleware/             # Request middleware
│   └── Requests/               # Form request validation
├── Console/Commands/           # CLI tools
├── Models/                     # Cross-cutting models (e.g., Setup)
└── Support/                    # Application-wide helpers (Settings, AppInfo)
```

## Key Principles

| Principle | Rule |
|---|---|
| Domain purity | No Laravel/Spatie imports in `Domain/{Feature}/Data/`, `Enums/`, `Exceptions/` |
| Action-DTO pattern | Actions accept DTOs as input, return entities or DTOs as output |
| Thin controllers | Controllers/Livewire components only handle request/response, delegate to Actions |
| Single responsibility | Each Action does one thing, named `*Action` with a single `execute()` method |
| Domain grouping | Actions and Domain classes share the same domain names (`Setup`, `User`, `Auth`, etc.) |

## Domain Map

| Domain | Purpose | Key Models |
|---|---|---|
| `Setup` | System installation & provisioning | `Setup` |
| `Auth` | Authentication & account lifecycle | — |
| `User` | User accounts & profiles | `User`, `Profile` |
| `School` | Institution & department management | `School`, `Department`, `AcademicYear` |
| `Internship` | Internship placements & tracking | `Internship`, `Placement`, `Company`, `Registration` |
| `Attendance` | Absence & attendance logging | `AttendanceLog`, `AbsenceRequest` |
| `Logbook` | Daily activity logs | `LogbookEntry` |
| `Assessment` | Competency evaluation | `Assessment`, `Competency`, `DepartmentCompetency` |
| `Assignment` | Tasks & submissions | `Assignment`, `Submission`, `AssignmentType` |
| `Mentor` | Supervisor monitoring | `SupervisionLog`, `MonitoringVisit` |
| `Mentee` | Student competency tracking | `CompetencyLog` |
| `Schedule` | Scheduling | `Schedule` |
| `Document` | Templates & generated reports | `DocumentTemplate`, `GeneratedReport`, `OfficialDocument` |
| `Guidance` | Handbooks & acknowledgements | `Handbook`, `HandbookAcknowledgement` |
| `Notification` | System notifications | `Notification` |
| `Dashboard` | Analytics & stats | — |
| `Core` | System-wide concerns | `Setting`, `AuditLog` |
| `Log` | Activity tracking | `ActivityLog` |
| `Shared` | Cross-domain contracts, traits, and data objects | — |

## Action Pattern

Every Action is a `final readonly class` with a single public `execute()` method:

```php
namespace App\Actions\Setup;

final readonly class InstallSystemAction
{
    public function __construct(
        private EnvironmentAuditor $auditor,
        private ProvisionSystemAction $provision,
    ) {}

    public function execute(bool $force = false): string
    {
        // audit → provision → generate token
    }
}
```

Actions are resolved via Laravel's service container and injected into controllers, Livewire components, or CLI commands.

## Data Flow

```
Request → Livewire/Controller → Action → Domain Service/Model → Response
                                    ↓
                              DTO (input/output)
                                    ↓
                              Audit Log (Core)
```

## Installation Flow

1. **CLI**: `php artisan setup:install` — audits environment, provisions database, generates setup token
2. **Web**: Token-protected URL → Setup Wizard (Livewire) — school, department, admin account
3. **CLI**: `php artisan setup:super-admin` — creates first super administrator

## Security

- Setup URLs are token-protected and time-limited
- Recovery commands require server console access
- All administrative actions recorded in the audit trail (`audit_logs` table)
- Domain exceptions are framework-agnostic; rendering handled at presentation layer
