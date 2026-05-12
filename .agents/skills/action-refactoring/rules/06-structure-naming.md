# Structure & Naming

## File Location

```
app/Actions/
├── {Domain}/
│   ├── {Verb}{Noun}Action.php
│   └── ...
├── Core/
│   └── LogAuditAction.php
└── Shared/
    └── ...
```

Examples:
- `app/Actions/User/CreateUserAction.php`
- `app/Actions/School/ActivateAcademicYearAction.php`
- `app/Actions/Internship/UpdateInternshipAction.php`
- `app/Actions/Core/LogAuditAction.php`

## Naming Conventions

| Pattern | Example |
|---|---|
| `{Verb}{Noun}Action` | `CreateUserAction`, `DeleteAcademicYearAction` |
| `{Verb}{Noun}Action` | `ActivateAcademicYearAction`, `FinalizeAssessmentAction` |
| `{Verb}{Noun}Action` | `UploadBrandAssetAction`, `SetSettingAction` |

Verbs: `Create`, `Update`, `Delete`, `Activate`, `Deactivate`, `Finalize`, `Verify`, `Submit`, `Approve`, `Reject`, `Upload`, `Set`, `Reset`, `Generate`, `Validate`, `Provision`, `Setup`, `Install`, `Recover`, `Initialize`

## Method Signature

```php
// Create → returns the created model
public function execute(array $data): Model

// Update → returns the updated model
public function execute(Model $model, array $data): Model

// Delete → returns void
public function execute(Model $model): void

// Action with side effects → returns result data
public function execute(array $data): array

// Toggle/activate → returns the model
public function execute(Model $model): Model
```

## File Header Order

```php
<?php

declare(strict_types=1);

namespace App\Actions\{Domain};

use App\Models\ModelName;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class {Verb}{Noun}Action
{
    use HandlesActionErrors;

    public function __construct(
        protected readonly LogAuditAction $logAuditAction,
    ) {}

    public function execute(...): ...
    {
        // ...
    }
}
```
