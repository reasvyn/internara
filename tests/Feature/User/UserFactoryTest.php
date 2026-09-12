<?php

declare(strict_types=1);

use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('95EVB-FR-USER-001: UserFactory', function (): void {
    test('usernames stay unique across bulk creation', function (): void {
        $users = User::factory()->count(10)->create();

        expect($users->pluck('username')->unique())->toHaveCount(10);
    });
});
