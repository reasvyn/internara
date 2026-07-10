<?php

declare(strict_types=1);

use App\Reports\Report\Actions\FinalizeReportAction;
use App\Reports\Report\Models\Report;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('finalizes a draft report', function () {
    $report = Report::factory()->create([
        'status' => 'draft',
    ]);
    $user = User::factory()->create();
    $user->assignRole('admin');

    $result = app(FinalizeReportAction::class)->execute($report, $user->id);

    expect($result->status->value)->toBe('finalized');
    expect($result->finalized_by)->toBe($user->id);
    expect($result->finalized_at)->not->toBeNull();
});

test('throws exception if report is already finalized', function () {
    $report = Report::factory()->create([
        'status' => 'finalized',
    ]);

    app(FinalizeReportAction::class)->execute($report, 'admin-id');
})->throws(\App\Core\Exceptions\RejectedException::class, 'This report has already been finalized.');
