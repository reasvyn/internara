# Business Rules in Entities

Move ALL business rules from Models/Actions/Components into Entities.

## What to Move

| Inline Code → | Entity Method | Why Entity |
|---|---|---|
| `$user->locked_at !== null` | `$user->asApprentice()->isLocked()` | Lock logic may change |
| `$year->is_active && !$hasRegistrations` | `$year->asAcademicYearState()->canBeDeleted()` | Multiple fields involved |
| `$status->value === 'suspended'` | `$status->isTerminal()` | Enum method is fine too |
| `$internship->status !== 'draft' && !$withinWindow` | `$internship->asPeriod()->isAcceptingRegistrations()` | Date calc + status check |
| `$registration->status === 'pending' && $mentors->count() >= 2` | `$registration->asState()->canBeApproved()` | Cross-entity check |

## Example: Before → After

### Before (logic scattered)

```php
// In Livewire component
if ($internship->status->value === 'active' && $internship->registration_end_date > now()) {
    // allow registration
}

// In Action
if ($internship->status !== 'draft') {
    throw new \Exception('Only draft can be edited');
}
```

### After (logic in Entity)

```php
// Entity encapsulates both rules
final readonly class InternshipPeriod extends BaseEntity
{
    public function isAcceptingRegistrations(): bool
    {
        return in_array($this->status, [InternshipStatus::PUBLISHED, InternshipStatus::ACTIVE])
            && now()->between($this->regStart, $this->regEnd);
    }

    public function isEditable(): bool
    {
        return $this->status === InternshipStatus::DRAFT;
    }
}

// Component and Action just call the Entity
if ($internship->asPeriod()->isAcceptingRegistrations()) { ... }
if (! $internship->asPeriod()->isEditable()) { throw ...; }
```

## Enum Business Rules

Enums can also contain business rules. This is valid and follows the same principle:

```php
enum InternshipStatus: string implements LabelEnum
{
    case DRAFT = 'draft';
    case PUBLISHED = 'published';
    case ACTIVE = 'active';

    public function canTransitionTo(self $target): bool
    {
        return in_array($target, $this->validTransitions(), true);
    }

    public function validTransitions(): array
    {
        return match ($this) {
            self::DRAFT => [self::PUBLISHED, self::CANCELLED],
            self::PUBLISHED => [self::ACTIVE, self::CANCELLED],
            self::ACTIVE => [self::COMPLETED, self::CANCELLED],
        };
    }
}
```
