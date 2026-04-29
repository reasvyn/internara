# Engineering Standards

## 1. Core Principles
All development must adhere to the **3S Doctrine** defined in `AGENTS.md`:
- **S1 (Secure)**: Security first. No hardcoded secrets. Strict input validation.
- **S2 (Sustain)**: Code must be readable and maintainable by humans.
- **S3 (Scalable)**: Designs must allow for growth in data, users, and features.

## 2. Architectural Standards

### Model Standards
- **UUIDs**: All models MUST use the `App\Models\Concerns\HasUuid` trait. No auto-incrementing IDs allowed.
- **Strict Types**: All files MUST declare `strict_types=1`.
- **Rich Models**: Models should contain business rules (e.g., `canBeApproved()`, `calculateStatus()`).

### Action Standards (Stateless Logic)
- **Single Responsibility**: One Action = One Use Case.
- **Stateless**: Actions MUST NOT have protected or private properties that store data between executions.
- **Standard Method**: Every action must have an `execute()` method.

### Controller Standards
- **Thin Controllers**: Controllers must only handle Request validation and Response returning.
- **Delegation**: Controllers MUST NOT contain business logic; they must delegate to Actions.

## 3. Coding Conventions
- **Naming**: Use business language (e.g., `ClockInAction` instead of `SaveAttendance`).
- **Formatting**: Use `Laravel Pint` for PHP and `Prettier` for JS/Blade.
- **Fail Fast**: Use Custom Exceptions to handle invalid business states early.

## 4. Verification
Architectural integrity is automatically verified via Pest Arch tests in `tests/Arch/LayerSeparationTest.php`. Any build failing these tests is considered non-compliant.
