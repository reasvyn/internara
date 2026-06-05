<?php

declare(strict_types=1);

namespace App\User\AccountRecovery\Models;

use App\Core\Models\BaseModel;
use App\User\AccountRecovery\Entities\RecoveryCodeState;
use Database\Factories\AccountRecoveryCodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;

#[Fillable(['user_id', 'code_hash', 'generated_at', 'used_at', 'expires_at'])]
class AccountRecoveryCode extends BaseModel
{
    use HasFactory;

    protected static function newFactory(): AccountRecoveryCodeFactory
    {
        return AccountRecoveryCodeFactory::new();
    }

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
