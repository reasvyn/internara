# Engineering Standards

## 1. Core Principles

All development must adhere to the **3S Doctrine** (Secure, Sustainable, Scalable) defined in `docs/architecture.md` section 2:

- **S1 (Secure)**: Security first. No hardcoded secrets. Strict input validation.
- **S2 (Sustain)**: Code must be readable and maintainable by humans.
- **S3 (Scalable)**: Designs must allow for growth in data, users, and features.

## 2. Architectural Standards

### Model Standards

- **UUIDs**: All models MUST use the `App\Models\Concerns\HasUuid` trait. No auto-incrementing IDs
  allowed.
- **Strict Types**: All files MUST declare `strict_types=1`.
- **Modern Attributes**: All models MUST use Laravel 13 PHP 8 Attributes for mass assignment and
  serialization (e.g., `#[Fillable]`, `#[Hidden]`, `#[Appends]`).
- **Casting**: Use the `protected function casts(): array` method instead of the `$casts` property.
- **Strictness**: Eloquent strictness (`preventLazyLoading`, `preventSilentlyDiscardingAttributes`)
  is enforced in non-production environments to ensure data integrity.
- **Rich Models**: Models should contain business rules (e.g., `canBeApproved()`,
  `calculateStatus()`).
- **No Side Effects**: Models MUST NOT send notifications or call external services directly. Use
  Events instead.

### Action Standards (Stateless Logic)

- **Single Responsibility**: One Action = One Use Case.
- **Stateless**: Actions MUST NOT have protected or private properties that store data between
  executions.
- **Standard Method**: Every action must have an `execute()` method.
- **Input Validation**: Actions should receive validated data from Form Requests (Controllers) or
  DTOs.
- **Side Effects**: Prefer Events for multiple side effects; direct calls are acceptable for simple
  cases.

### Job Standards

- **Modern Attributes**: All jobs SHOULD use Laravel 13 PHP 8 Attributes for configuration (e.g.,
  `#[Tries]`, `#[Timeout]`, `#[FailOnTimeout]`).

### Controller Standards

- **Thin Controllers**: Controllers must only handle Request validation (Form Requests) and Response
  returning.
- **Delegation**: Controllers MUST NOT contain business logic; they must delegate to Actions.
- **API Focus**: Controllers are for API endpoints; Livewire components handle Web UI.

### Livewire Standards

- **Stateful UI**: Livewire components handle stateful web interactions.
- **Delegation**: MUST delegate business logic to Actions, not implement directly.
- **Authorization**: Must check permissions using Policies or `authorize()`.

### Form Request Standards

- **Validation**: Use Form Requests for all incoming HTTP data (both Web and API).
- **Authorization**: Can include `authorize()` method for request-level authorization.
- **Messages**: Provide user-friendly validation messages.

### Repository Standards (Optional - Use Only When Needed)

- **Purpose**: Abstract complex queries or enable data source swapping.
- **When to Use**:
    - Complex queries reused across multiple Actions
    - Need to swap between Eloquent and API data sources
    - Queries with multiple joins, conditions, or performance-critical paths
- **When NOT to Use**:
    - Simple CRUD operations (use Eloquent directly in Actions)
    - Queries specific to a single Action (keep in the Action)
- **Return Type**: Must return Eloquent Models/Collections, not arrays.

### Event/Listener Standards (Optional - Use for Multiple Side Effects)

- **Purpose**: Decouple side effects (notifications, audit logs, emails) from core business logic.
- **When to Use**:
    - Multiple things need to happen after a business event
    - Side effects that might fail independently (e.g., email sending)
    - Need to trigger external integrations
- **When NOT to Use**:
    - Single, simple side effect (do it directly in Action)
    - When it reduces clarity without measurable benefit
- **Naming**: Events should be past tense (e.g., `InternshipCreated`, `MenteeRegistered`).

### Service Standards (Infrastructure Services)

- **Purpose**: Handle technical/infrastructure concerns, not business logic.
- **Examples**: Setup orchestration, PDF generation, external API integrations.
- **Constraint**: MUST NOT contain business rules (those belong in Models/Actions).

## 3. Coding Conventions

- **Naming**: Use business language (e.g., `ClockInAction` instead of `SaveAttendance`).
- **Formatting**: Use `Laravel Pint` for PHP and `Prettier` for JS/Blade.
- **Fail Fast**: Use Custom Exceptions to handle invalid business states early.
- **Documentation**: Document why lifecycle layers (Repositories, Events) were added in Decision
  Records.

## 4. Layer Separation Rules

- **Controllers/Livewire** → Can call Actions, Repositories (read-only), Policies
- **Actions** → Can call Models, Repositories, Events, Services (infrastructure only)
- **Models** → Can call other Models, dispatch Events
- **Repositories** → Can call Models (read-only), cannot call Actions
- **Events/Listeners** → Can call Actions, Services, Models (read-only)

## 5. Verification

Architectural integrity is automatically verified via Pest Arch tests in
`tests/Arch/LayerSeparationTest.php`. Any build failing these tests is considered non-compliant.

### Arch Test Coverage

- Controllers and Livewire components must not contain business logic
- Actions must be stateless and have `execute()` method
- Models must use UUIDs and contain business rules
- Repositories (if used) must only return Eloquent objects
- Services must not contain business rules
