<?php

declare(strict_types=1);

use App\Auth\SuperAdmin\Actions\RecoverSuperAdminAction;
use App\Core\Exceptions\RejectedException;
use App\User\Enums\AccountStatus;
use App\User\Models\User;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

beforeEach(function () {
    Cache::flush();
});

test('resets password for existing super admin', function () {
    $user = User::factory()->create(['email' => 'admin@test.id']);
    $user->assignRole('super_admin');
    $user->setStatus(AccountStatus::PROTECTED);

    app(RecoverSuperAdminAction::class)->execute(
        email: 'admin@test.id',
        password: 'new-password-123',
    );

    $user->refresh();

    expect(Hash::check('new-password-123', $user->password))->toBeTrue();
    expect($user->locked_at)->toBeNull();
    expect($user->locked_reason)->toBeNull();
});

test('throws exception for non-existent email', function () {
    app(RecoverSuperAdminAction::class)->execute(
        email: 'nonexistent@test.id',
        password: 'new-password-123',
    );
})->throws(ModelNotFoundException::class);

test('rate limits after 3 failed attempts for non-existent email', function () {
    foreach (range(1, 3) as $i) {
        try {
            app(RecoverSuperAdminAction::class)->execute(
                email: 'ghost@test.id',
                password: 'irrelevant',
            );
        } catch (Throwable) {
            // Expected — email doesn't exist, counter increments
        }
    }

    app(RecoverSuperAdminAction::class)->execute(
        email: 'ghost@test.id',
        password: 'irrelevant',
    );
})->throws(RejectedException::class, 'Too many recovery attempts.');
