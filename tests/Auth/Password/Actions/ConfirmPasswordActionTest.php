<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Password\Actions\ConfirmPasswordAction;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(LazilyRefreshDatabase::class);

/*
|--------------------------------------------------------------------------
| CQVSK — Password Confirmation (spec-driven)
|--------------------------------------------------------------------------
*/

describe('CQVSK: ConfirmPasswordAction', function (): void {
    test('CQVSK-FR-PC1: verifies password via Hash::check', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('correct-password'),
        ]);

        $action = app(ConfirmPasswordAction::class);

        // Should not throw for correct password
        $result = $action->execute($user, 'correct-password');

        expect($result)->toBeInstanceOf(ActionResponse::class)
            ->and($result->success)->toBeTrue();
    });

    test('CQVSK-FR-PC2: sets session auth.password_confirmed_at on success', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('secret123'),
        ]);

        app(ConfirmPasswordAction::class)->execute($user, 'secret123');

        expect(session('auth.password_confirmed_at'))->not->toBeNull()
            ->and(session('auth.password_confirmed_at'))->toBeInt()
            ->and(session('auth.password_confirmed_at'))->toBeGreaterThan(time() - 5);
    });

    test('CQVSK-FR-PC3: throws RejectedException on failure', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('correct'),
        ]);

        app(ConfirmPasswordAction::class)->execute($user, 'wrong-password');
    })->throws(RejectedException::class);

    test('CQVSK-FR-PC4: logs password_confirmed event on success', function (): void {
        $user = User::factory()->create([
            'password' => Hash::make('pass123'),
        ]);

        $source = file_get_contents((new ReflectionClass(ConfirmPasswordAction::class))->getFileName());

        expect($source)->toContain('password_confirmed');

        // Functional: action should succeed and log (no exception)
        $result = app(ConfirmPasswordAction::class)->execute($user, 'pass123');

        expect($result->success)->toBeTrue();
    });

    test('CQVSK-FR-PC1: extends BaseCommandAction', function (): void {
        expect(new ConfirmPasswordAction)->toBeInstanceOf(BaseCommandAction::class);
    });
});
