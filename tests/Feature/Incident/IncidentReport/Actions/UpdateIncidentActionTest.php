<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Actions\UpdateIncidentAction;
use App\Incident\IncidentReport\Models\IncidentReport;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('updates incident fields', function () {
    $incident = IncidentReport::factory()->create();

    $result = app(UpdateIncidentAction::class)->execute($incident, [
        'description' => 'Updated description',
        'severity' => 'high',
    ]);

    expect($result->description)->toBe('Updated description');
    expect($result->severity->value)->toBe('high');
});
