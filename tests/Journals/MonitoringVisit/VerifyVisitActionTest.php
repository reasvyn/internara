<?php

declare(strict_types=1);

use App\Core\Exceptions\RejectedException;
use App\Journals\MonitoringVisit\Actions\VerifyVisitAction;
use App\Journals\MonitoringVisit\Models\MonitoringVisit;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('VerifyVisitAction', function () {
    test('verifies an unverified visit', function () {
        $admin = User::factory()->create();
        $visit = MonitoringVisit::factory()->create(['is_verified' => false]);

        $result = app(VerifyVisitAction::class)->execute($visit, $admin);

        expect($result->is_verified)->toBeTrue();
        expect($result->verified_by)->toBe($admin->id);
        expect($result->verified_at)->not->toBeNull();
    });

    test('throws when visit is already verified', function () {
        $admin = User::factory()->create();
        $visit = MonitoringVisit::factory()->create(['is_verified' => true]);

        app(VerifyVisitAction::class)->execute($visit, $admin);
    })->throws(RejectedException::class);
});
