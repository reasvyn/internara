<?php

declare(strict_types=1);

use App\Modules\Enrollment\Domain\Registration\Models\Registration;

describe('MBB5R-FR-REG-012: Registration::isPending', function (): void {
    test('returns true when status is pending', function (): void {
        $registration = new Registration(['status' => 'pending']);

        expect($registration->isPending())->toBeTrue();
    });

    test('returns false when status is not pending', function (): void {
        $active = new Registration(['status' => 'active']);
        $missing = new Registration;

        expect($active->isPending())->toBeFalse()
            ->and($missing->isPending())->toBeFalse();
    });
});
