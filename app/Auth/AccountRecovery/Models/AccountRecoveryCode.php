<?php

declare(strict_types=1);

namespace App\Auth\AccountRecovery\Models;

use App\Auth\AccountRecovery\Entities\RecoveryCodeState;
use App\Core\Models\BaseModel;
use Illuminate\Database\Eloquent\Attributes\Fillable;

#[Fillable(['user_id', 'token', 'token_type', 'expires_at', 'attempts', 'last_attempt_at'])]
class AccountRecoveryCode extends BaseModel
{
    protected $table = 'activation_tokens';

    protected $casts = [
        'expires_at' => 'datetime',
        'last_attempt_at' => 'datetime',
        'attempts' => 'integer',
    ];

    public function asRecoveryCodeState(): RecoveryCodeState
    {
        return RecoveryCodeState::fromModel($this);
    }
}
