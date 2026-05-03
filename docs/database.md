# Database Documentation

## 1. Standards (3S Doctrine)

### S1 - Secure: UUID Primary Keys
All models use UUIDs instead of auto-incrementing IDs to prevent ID guessing and information leakage.
- **Migration**: `$table->uuid('id')->primary();`
- **Model**: Must use `App\Domain\Core\Concerns\HasUuid`.

### S2 - Sustain: Mass Assignment
All models use PHP 8 Attributes for mass assignment.
- **Syntax**: `#[Fillable(['field'])]`, `#[Hidden(['field'])]`.

### S3 - Scalable: Relationships & Indexes
- **Foreign Keys**: Always use constrained UUID foreign keys: `$table->foreignUuid('xxx_id')->constrained()->cascadeOnDelete();`.
- **Indexing**: High-growth tables (Audit, Attendance, Logbook) have compound indexes on common filter columns.

## 2. Model Structure

Models are organized by **Domain**:
- `app/Domain/{Domain}/Models/`
- Shared Auth models: `app/Domain/Auth/Models/` (User, Profile)

## 3. Spatie Integrations

The system integrates several Spatie packages for core functionality:
- **Activity Log**: Tracks system-wide actions and model changes.
- **Permission**: Handles Role-Based Access Control (RBAC).
- **Media Library**: Manages file attachments with UUID protection.
- **Model Status**: Manages state transitions for complex entities (e.g., Internship Registration).

## 4. Monitoring (Laravel Pulse)

System performance and health are monitored via Laravel Pulse. Table data is stored in `pulse_*` tables and viewable at the `/pulse` dashboard.

## 5. Testing

- **In-Memory**: SQLite `:memory:` is used for fast, isolated test execution.
- **Factories**: Every model must have a corresponding factory in `database/factories/`.
- **Seeders**: Initial system state and settings are managed via `database/seeders/`.
