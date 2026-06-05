<?php

declare(strict_types=1);

namespace Database\Factories;

use App\User\AccountRecovery\Models\AccountRecoveryCode;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AccountRecoveryCodeFactory extends Factory
{
    protected $model = AccountRecoveryCode::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'code_hash' => Hash::make(strtoupper(str()->random(12))),
            'generated_at' => now(),
            'expires_at' => null,
        ];
    }
}
