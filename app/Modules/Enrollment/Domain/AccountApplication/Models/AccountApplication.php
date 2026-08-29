<?php

declare(strict_types=1);

namespace App\Modules\Enrollment\Domain\AccountApplication\Models;

use App\Modules\Academics\Domain\Department\Models\Department;
use App\Modules\Core\Models\BaseModel;
use App\Modules\Enrollment\Domain\AccountApplication\Enums\AccountApplicationStatus;
use App\Modules\User\Models\User;
use Database\Factories\AccountApplicationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[
    Fillable([
        'name',
        'email',
        'student_id_number',
        'department_id',
        'form_data',
        'status',
        'processed_by',
        'processed_at',
        'rejection_reason',
    ]),
]
class AccountApplication extends BaseModel
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'form_data' => 'json',
            'processed_at' => 'datetime',
            'status' => AccountApplicationStatus::class,
        ];
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    protected static function newFactory(): AccountApplicationFactory
    {
        return AccountApplicationFactory::new();
    }
}
