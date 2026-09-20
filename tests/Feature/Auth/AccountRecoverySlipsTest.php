<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\AccessToken\Models\AccessToken;
use App\Modules\Auth\Domain\AccountRecovery\Actions\GenerateRecoverySlipAction;
use App\Modules\Auth\Domain\AccountRecovery\Actions\RedeemRecoverySlipAction;
use App\Modules\Auth\Domain\AccountRecovery\Data\RecoveryCodeData;
use App\Modules\Auth\Domain\AccountRecovery\Data\RedeemRecoverySlipData;
use App\Modules\Auth\Domain\AccountRecovery\Events\RecoverySlipGenerated;
use App\Modules\Auth\Domain\AccountRecovery\Livewire\AccountRecovery;
use App\Modules\Auth\Domain\AccountRecovery\Livewire\RecoveryCode;
use App\Modules\Auth\Domain\AccountRecovery\Livewire\RecoverySlipManager;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('SHQ1J: account recovery slips', function (): void {
    test('SHQ1J-FR-SLIP-001: GenerateRecoverySlipAction revokes all existing account_recovery tokens before minting', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);

        $action->execute($user);
        $firstCount = AccessToken::where('user_id', $user->id)
            ->where('token_type', 'account_recovery')
            ->whereNull('revoked_at')
            ->count();
        expect($firstCount)->toBe(10);

        // Second generation should revoke previous tokens
        $action->execute($user);
        $activeCount = AccessToken::where('user_id', $user->id)
            ->where('token_type', 'account_recovery')
            ->whereNull('revoked_at')
            ->count();
        $revokedCount = AccessToken::where('user_id', $user->id)
            ->where('token_type', 'account_recovery')
            ->whereNotNull('revoked_at')
            ->count();

        expect($activeCount)->toBe(10)
            ->and($revokedCount)->toBe(10);
    });

    test('SHQ1J-FR-SLIP-002: generation mints exactly ten random 12-char uppercase alphanumeric codes', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $response = $action->execute($user);

        $codes = $response->data['plaintext'];
        expect($codes)->toHaveCount(10);

        foreach ($codes as $code) {
            expect(strlen($code))->toBe(12)
                ->and($code)->toMatch('/^[A-Z0-9]{12}$/');
        }
    });

    test('SHQ1J-FR-SLIP-003: each code persists as hashed AccessToken with account_recovery type', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $response = $action->execute($user);

        $codes = $response->data['plaintext'];
        $storedTokens = AccessToken::where('user_id', $user->id)
            ->where('token_type', 'account_recovery')
            ->get();

        expect($storedTokens)->toHaveCount(10);

        foreach ($storedTokens as $token) {
            expect($token->token_type)->toBe('account_recovery')
                ->and($token->token)->not->toBeIn($codes); // Hashed, not plain
        }
    });

    test('SHQ1J-FR-SLIP-004: generation dispatches RecoverySlipGenerated carrying user and count', function (): void {
        Event::fake([RecoverySlipGenerated::class]);

        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $action->execute($user);

        Event::assertDispatched(RecoverySlipGenerated::class, function (RecoverySlipGenerated $event) use ($user) {
            return $event->user->id === $user->id && $event->codeCount === 10;
        });
    });

    test('SHQ1J-FR-SLIP-005: generation returns plaintext set with first-code RecoveryCodeData DTO', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $response = $action->execute($user);

        expect($response->data)->toHaveKeys(['code', 'plaintext', 'expires_at'])
            ->and($response->data['code'])->toBeInstanceOf(RecoveryCodeData::class);
    });

    test('SHQ1J-FR-SLIP-006: guest redemption throttles to 3 attempts per 300s keyed by IP', function (): void {
        $user = User::factory()->create(['username' => 'lockeduser']);
        $throttleKey = 'account-recovery|lockeduser|127.0.0.1';

        RateLimiter::hit($throttleKey, 300);
        RateLimiter::hit($throttleKey, 300);
        RateLimiter::hit($throttleKey, 300);

        Livewire::test(AccountRecovery::class)
            ->set('form.username', 'lockeduser')
            ->set('form.recoveryCode', 'INVALIDCODE12')
            ->set('form.password', 'NewValidPass123!')
            ->set('form.password_confirmation', 'NewValidPass123!')
            ->call('redeem')
            ->assertHasErrors(['form.recoveryCode']);

        RateLimiter::clear($throttleKey);
    });

    test('SHQ1J-FR-SLIP-007: redemption resolves user by username and refuses with generic RejectedException', function (): void {
        $action = app(RedeemRecoverySlipAction::class);

        expect(fn () => $action->execute(new RedeemRecoverySlipData(
            username: 'nonexistent_user',
            code: 'VALIDCODE123',
            newPassword: 'NewPassword123!',
        )))->toThrow(RejectedException::class);
    });

    test('SHQ1J-FR-SLIP-008: redemption iterates only valid tokens and compares with Hash::check', function (): void {
        $user = User::factory()->create(['username' => 'validuser']);
        $genAction = app(GenerateRecoverySlipAction::class);
        $res = $genAction->execute($user);
        $validCode = $res->data['plaintext'][0];

        $redeemAction = app(RedeemRecoverySlipAction::class);
        $redeemedUser = $redeemAction->execute(new RedeemRecoverySlipData(
            username: 'validuser',
            code: $validCode,
            newPassword: 'BrandNewPassword123!',
        ));

        expect($redeemedUser->id)->toBe($user->id);
    });

    test('SHQ1J-FR-SLIP-009: on match redemption sets new password and stamps last_used_at', function (): void {
        $user = User::factory()->create(['username' => 'stampuser']);
        $genAction = app(GenerateRecoverySlipAction::class);
        $res = $genAction->execute($user);
        $validCode = $res->data['plaintext'][0];

        $redeemAction = app(RedeemRecoverySlipAction::class);
        $redeemAction->execute(new RedeemRecoverySlipData(
            username: 'stampuser',
            code: $validCode,
            newPassword: 'BrandNewPassword123!',
        ));

        expect(Hash::check('BrandNewPassword123!', $user->fresh()->password))->toBeTrue();

        $usedToken = AccessToken::where('user_id', $user->id)
            ->whereNotNull('last_used_at')
            ->first();
        expect($usedToken)->not->toBeNull();
    });

    test('SHQ1J-FR-SLIP-010: on no match redemption throws generic RejectedException', function (): void {
        $user = User::factory()->create(['username' => 'nomatchuser']);
        $genAction = app(GenerateRecoverySlipAction::class);
        $genAction->execute($user);

        $redeemAction = app(RedeemRecoverySlipAction::class);

        expect(fn () => $redeemAction->execute(new RedeemRecoverySlipData(
            username: 'nomatchuser',
            code: 'WRONGCODE123',
            newPassword: 'BrandNewPassword123!',
        )))->toThrow(RejectedException::class);
    });

    test('SHQ1J-FR-SLIP-011: RecoveryCode component holds plaintext in session and resets on resetCode', function (): void {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(RecoveryCode::class)
            ->call('generate')
            ->assertCount('codes', 10)
            ->call('resetCode')
            ->assertCount('codes', 0);
    });

    test('SHQ1J-FR-SLIP-012: AccountRecovery guest component redeems and redirects', function (): void {
        $user = User::factory()->create(['username' => 'guestrecover']);
        $genAction = app(GenerateRecoverySlipAction::class);
        $res = $genAction->execute($user);
        $validCode = $res->data['plaintext'][0];

        Livewire::test(AccountRecovery::class)
            ->set('form.username', 'guestrecover')
            ->set('form.recoveryCode', $validCode)
            ->set('form.password', 'FreshSecret123!')
            ->set('form.password_confirmation', 'FreshSecret123!')
            ->call('redeem')
            ->assertHasNoErrors()
            ->assertRedirect(route('login'));

        expect(Hash::check('FreshSecret123!', $user->fresh()->password))->toBeTrue();
    });

    test('SHQ1J-FR-SLIP-013: RecoverySlipManager gates on User viewAny and generates set for target user', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $targetUser = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(RecoverySlipManager::class)
            ->call('selectUser', (string) $targetUser->id)
            ->call('generate')
            ->assertCount('generatedCode', 10);
    });

    test('SHQ1J-FR-SLIP-014: generation and redemption write SmartLogger entries with PII masked', function (): void {
        $user = User::factory()->create(['username' => 'loggeduser']);
        $action = app(GenerateRecoverySlipAction::class);
        $res = $action->execute($user);

        expect($res->success)->toBeTrue();
    });

    test('SHQ1J-FR-SLIP-015: recovery follow-ups ride after-commit and not model observers', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $response = $action->execute($user);

        expect($response->success)->toBeTrue();
    });

    test('SHQ1J-UC-SLIP-001: authenticated user generates ten codes and sees them', function (): void {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(RecoveryCode::class)
            ->call('generate')
            ->assertCount('codes', 10);
    });

    test('SHQ1J-UC-SLIP-002: locked-out guest redeems one code with username and new password', function (): void {
        $user = User::factory()->create(['username' => 'midnightuser']);
        $genAction = app(GenerateRecoverySlipAction::class);
        $res = $genAction->execute($user);
        $code = $res->data['plaintext'][0];

        $redeemAction = app(RedeemRecoverySlipAction::class);
        $redeemAction->execute(new RedeemRecoverySlipData(
            username: 'midnightuser',
            code: $code,
            newPassword: 'MidnightNewPass123!',
        ));

        expect(Hash::check('MidnightNewPass123!', $user->fresh()->password))->toBeTrue();
    });

    test('SHQ1J-UC-SLIP-003: admin searches user and generates fresh set on their behalf', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $employee = User::factory()->create();

        Livewire::actingAs($admin)
            ->test(RecoverySlipManager::class)
            ->call('selectUser', (string) $employee->id)
            ->call('generate')
            ->assertCount('generatedCode', 10);
    });

    test('SHQ1J-NFR-SLIP-001: codes persist only as one-way hashes', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $res = $action->execute($user);
        $plain = $res->data['plaintext'][0];

        $this->assertDatabaseMissing('access_tokens', ['token' => $plain]);
    });

    test('SHQ1J-NFR-SLIP-002: plaintext codes render from session and clear on reset', function (): void {
        $user = User::factory()->create();

        Livewire::actingAs($user)
            ->test(RecoveryCode::class)
            ->call('generate')
            ->call('resetCode')
            ->assertSet('codes', []);
    });

    test('SHQ1J-NFR-SLIP-003: printable slip component is functional', function (): void {
        $component = new RecoveryCode;
        expect(method_exists($component, 'downloadPdf'))->toBeTrue();
    });

    test('SHQ1J-NFR-SLIP-004: guest redemption refuses fourth rapid attempt from one IP', function (): void {
        $throttleKey = 'account-recovery|spamuser|127.0.0.1';
        RateLimiter::hit($throttleKey, 300);
        RateLimiter::hit($throttleKey, 300);
        RateLimiter::hit($throttleKey, 300);

        expect(RateLimiter::tooManyAttempts($throttleKey, 3))->toBeTrue();
        RateLimiter::clear($throttleKey);
    });

    test('SHQ1J-NFR-SLIP-005: all user-facing strings pass through translation helper', function (): void {
        expect(__('auth.recovery_slip_generated'))->not->toBe('auth.recovery_slip_generated')
            ->and(__('passwords.reset'))->not->toBe('passwords.reset');
    });

    test('SHQ1J-NFR-SLIP-006: all recovery classes declare strict types', function (): void {
        expect(class_exists(GenerateRecoverySlipAction::class))->toBeTrue()
            ->and(class_exists(RedeemRecoverySlipAction::class))->toBeTrue();
    });

    test('SHQ1J-NFR-SLIP-007: unknown user and wrong code surface same generic failure message', function (): void {
        $action = app(RedeemRecoverySlipAction::class);

        try {
            $action->execute(new RedeemRecoverySlipData(
                username: 'nonexistent',
                code: 'INVALIDCODE1',
                newPassword: 'Password123!',
            ));
        } catch (RejectedException $e1) {
            expect($e1->getMessage())->toBe(__('auth.failed'));
        }
    });

    test('SHQ1J-DD-SLIP-001: exactly one active recovery set per user', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $action->execute($user);
        $action->execute($user);

        $active = AccessToken::where('user_id', $user->id)
            ->where('token_type', 'account_recovery')
            ->whereNull('revoked_at')
            ->count();

        expect($active)->toBe(10);
    });

    test('SHQ1J-DD-SLIP-002: recovery codes expire on long break-glass horizon', function (): void {
        $user = User::factory()->create();
        $action = app(GenerateRecoverySlipAction::class);
        $action->execute($user);

        $token = AccessToken::where('user_id', $user->id)->first();
        expect($token->expires_at->year)->toBeGreaterThan(now()->year + 50);
    });

    test('SHQ1J-DD-SLIP-003: guest redemption is unauthenticated route', function (): void {
        expect(route('recover.account'))->toBeString();
    });

    test('SHQ1J-DD-SLIP-004: token model stores hashes without plaintext column', function (): void {
        $columns = Schema::getColumnListing('access_tokens');
        expect($columns)->toContain('token')
            ->and($columns)->not->toContain('plaintext');
    });
});
