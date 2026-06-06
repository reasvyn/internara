<?php

declare(strict_types=1);

namespace App\User\AccountRecovery\Models;

use App\Core\Models\BaseModel;
use App\User\AccountRecovery\Entities\RecoveryCodeState;
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
