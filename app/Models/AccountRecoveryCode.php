<?php

declare(strict_types=1);

namespace App\Models;

use App\Entities\AccountRecoveryCode\RecoveryCodeState;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['user_id', 'code_hash', 'generated_at', 'used_at', 'expires_at'])]
class AccountRecoveryCode extends BaseModel
{
    use HasFactory;

    protected $casts = [
        'generated_at' => 'datetime',
        'used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    public function asRecoveryCodeState(): RecoveryCodeState
    {
        return RecoveryCodeState::fromModel($this);
    }
}
