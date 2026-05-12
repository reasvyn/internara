# Bridge Pattern

Models expose Entities through named accessor methods. Never a generic `entity()` method.

## Convention

```php
class User extends Authenticatable
{
    // ✅ Named accessor
    public function asApprentice(): Apprentice
    {
        return Apprentice::fromModel($this);
    }
}
```

## Calling Convention

```php
// Callers always go through the named accessor
$user->asApprentice()->isSuspended();
$user->asApprentice()->canLogin();
$year->asAcademicYearState()->canBeDeleted();
$internship->asPeriod()->isAcceptingRegistrations();
$registration->asState()->canBeApproved();
$logbook->asState()->canBeVerified();
```

## What NOT to do

```php
// ❌ Generic entity() method
public function entity(): Apprentice
{
    return Apprentice::fromModel($this);
}
$user->entity()->isSuspended();  // Unclear what entity

// ❌ No entity at all
if ($user->latestStatus()?->name === 'suspended') { ... }
// → Should be: $user->asApprentice()->isSuspended()
```

## Naming Convention

| Model | Entity Method | Entity Class |
|---|---|---|
| `User` | `asApprentice()` | `Apprentice` |
| `AcademicYear` | `asAcademicYearState()` | `AcademicYearState` |
| `Internship` | `asPeriod()` | `InternshipPeriod` |
| `Registration` | `asState()` | `RegistrationState` |
| `Logbook` | `asState()` | `LogbookState` |
| `Submission` | `asState()` | `SubmissionState` |
| `School` | `asSchoolState()` | `SchoolState` |
| `Placement` | `asCapacity()` | `PlacementCapacity` |
