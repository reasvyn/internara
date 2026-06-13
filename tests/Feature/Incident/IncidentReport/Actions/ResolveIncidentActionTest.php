<?php

declare(strict_types=1);

use App\Incident\IncidentReport\Actions\ResolveIncidentAction;
use App\Incident\IncidentReport\Models\IncidentReport;
use App\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('resolves open incident', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $incident = IncidentReport::factory()->create();

    $result = app(ResolveIncidentAction::class)->execute($incident, [
        'resolution_notes' => 'Issue resolved',
        'status' => 'resolved',
    ]);

    expect($result->status->value)->toBe('resolved');
    expect($result->resolved_at)->not->toBeNull();
});

test('throws when resolving already closed incident', function () {
    $this->actingAs(User::factory()->create());

    $incident = IncidentReport::factory()->create(['status' => 'closed']);

    app(ResolveIncidentAction::class)->execute($incident, [
        'resolution_notes' => 'Already done',
        'status' => 'resolved',
    ]);
})->throws(RuntimeException::class, 'This incident is already closed.');
