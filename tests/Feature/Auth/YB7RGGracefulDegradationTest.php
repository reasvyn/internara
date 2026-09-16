<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Login\Livewire\Login;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('YB7RG: graceful degradation at the gate', function (): void {
    test('YB7RG-NFR-AUTH-010: login rejects cleanly when the database is unreachable', function (): void {
        $user = User::factory()->withPassword('secret-123')->create();

        // Real database failure, no mocks: dropping the table makes the
        // query builder throw a genuine QueryException inside the Action,
        // exercising the component's Throwable branch exactly as a downed
        // database would. The wrapping transaction rolls the DDL back.
        Schema::drop('users');

        $attempt = Livewire::test(Login::class)
            ->set('form.identifier', $user->email)
            ->set('form.password', 'secret-123')
            ->call('login');

        $attempt->assertHasErrors('form.identifier');

        $messages = $attempt->errors()->get('form.identifier');

        expect($messages)->toHaveCount(1)
            ->and($messages[0])->toBe(__('auth.failed'));

        foreach (['SQLSTATE', 'no such table', 'QueryException', 'Exception', 'SELECT', $user->email] as $leak) {
            expect($messages[0])->not->toContain($leak);
        }

        expect(auth()->check())->toBeFalse();
    });
});
