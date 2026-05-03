# Architecture & Standards

## 1. Principles (3S Doctrine)

Internara follows the **3S Doctrine** to ensure code quality and system integrity:

- **Secure (S1)**: Security is absolute. Input must be validated at the boundary. Business rules must be protected in Models. No hardcoded secrets.
- **Sustainable (S2)**: Code must be readable and follow domain language. Patterns must be consistent to ensure long-term maintainability.
- **Scalable (S3)**: Architecture must allow for growth. Components are decoupled via Actions and Events.

## 2. Core Structure

The application uses a **Domain-Driven Structure** where code is organized by business context rather than technical layer.

### Folder Hierarchy

```
app/
├── Domain/              # Backend/Domain Layer (Bounded business contexts)
│   ├── {Domain}/
│   │   ├── Actions/    # Stateless use cases
│   │   ├── Models/     # Rich models with business rules
│   │   ├── Data/       # DTOs for structured data
│   │   ├── Enums/      # Domain-specific enums
│   │   ├── Policies/   # Authorization logic
│   │   ├── Events/     # Domain events
│   │   ├── Listeners/  # Domain event listeners
│   │   ├── Notifications/ # Domain-specific notifications
│   │   └── Repositories/ # Complex queries
│
├── Livewire/            # Frontend/Presentation Layer (Separated by domain)
│   ├── {Domain}/       # Reactive UI components grouped by domain context
│
├── Http/                # Presentation Layer (HTTP)
│   ├── Controllers/    # Flat controllers for web/API
│   ├── Requests/       # Form Requests grouped by {Domain}
│   └── Middleware/     # Shared HTTP infrastructure
│
├── Console/             # Presentation Layer (CLI)
│   └── Commands/       # Artisan Commands grouped by {Domain}
│
├── Providers/           # Infrastructure Layer (Service Providers)
└── Shared/              # Cross-cutting UI components (Components, Layouts)
```

## 3. Layer Standards

### Models (Rich State)
- **Standard**: Must use `HasUuid` trait. Primary keys are UUIDs.
- **Business Rules**: Logic for "Is this allowed?" or "What is the status?" belongs here.
- **Modern Syntax**: Use PHP 8 Attributes (`#[Fillable]`, `#[Hidden]`) and the `casts(): array` method.
- **Strictness**: Lazy loading and silent attribute discarding are disabled in development.

### Actions (Stateless Logic)
- **Standard**: One class = One use case. Must have an `execute()` method.
- **Stateless**: Actions must not store state in properties; they are orchestrators.
- **Dependency**: Inject services and other actions via the constructor.

### Livewire (Reactive UI)
- **Standard**: Components handle UI state and interaction only.
- **Location**: Located in `app/Livewire/{Domain}/`.
- **Delegation**: Must delegate all mutations and complex logic to Actions.
- **Authorization**: Always verify permissions via Policies or Gates.

### Form Requests (Validation)
- **Standard**: Located in `app/Http/Requests/{Domain}/`.
- **Purpose**: Centralized input validation and authorization for Controllers.

### Artisan Commands
- **Standard**: Located in `app/Console/Commands/{Domain}/`.
- **Purpose**: CLI interface for domain-specific tasks.

### Jobs (Background Work)
- **Standard**: Located in `app/Domain/{Domain}/Jobs/`.
- **Usage**: Use for long-running or async tasks (PDF generation, bulk emails).

### Supplementary Layers
- **DTOs (Data)**: Located in `app/Domain/{Domain}/Data/`. Use for structured data transfer between layers.
- **Events/Listeners**: Use to decouple side effects from core logic.
- **Repositories**: Use **only** for complex, reusable queries. Simple CRUD belongs in Actions/Models.

## 4. Communication Rules

1. **Directional Flow**: `UI (Livewire/Controller)` → `Action` → `Model/Repository`.
2. **Cross-Domain**: Domains should communicate via **Events** or **Shared Actions** to avoid tight coupling.
3. **No Side Effects in Models**: Models should not trigger notifications or external calls; dispatch an Event instead.
4. **Thin Controllers**: Controllers are only for request/response mapping and delegating to Actions.

## 5. Coding Conventions

- **Naming**: Use domain-specific terms (e.g., `Supervisor` instead of `Mentor` for industry guidance).
- **Roles**: Standard roles are `SuperAdmin`, `Admin`, `Student`, `Teacher`, `Supervisor`.
- **Validation**: Always use `FormRequest` classes for input validation.
- **Fail Early**: Use custom Exceptions (located in `app/Domain/{Domain}/Exceptions/`) to handle invalid business states.
- **Format**: PHP code must be formatted with `Laravel Pint`.

## 6. Verification

Architectural integrity is enforced via automated **Pest Arch** tests in `tests/Arch/`. These tests prevent layer violations and ensure standards (like UUID usage) are maintained.
