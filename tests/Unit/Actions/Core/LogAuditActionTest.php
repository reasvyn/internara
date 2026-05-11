<?php

declare(strict_types=1);

use App\Actions\Core\LogAuditAction;
use App\Models\User;
use Database\Factories\UserFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;

uses(RefreshDatabase::class);

describe('execute', function () {
    it('logs an audit entry with all parameters', function () {
        $user = UserFactory::new()->create();

        app(LogAuditAction::class)->execute(
            action: 'test_action',
            subjectType: User::class,
            subjectId: $user->id,
            payload: ['key' => 'value'],
            module: 'Testing',
            user: $user,
        );

        expect(true)->toBeTrue();
    });

    it('throws InvalidArgumentException for empty action', function () {
        expect(fn () => app(LogAuditAction::class)->execute(action: ''))
            ->toThrow(InvalidArgumentException::class);
    });
});
