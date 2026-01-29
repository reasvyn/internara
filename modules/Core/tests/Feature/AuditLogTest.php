<?php

declare(strict_types=1);

use Modules\Internship\Models\InternshipPlacement;
use Modules\User\Models\User;

uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

test('it records audit log when a placement is updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $placement = InternshipPlacement::factory()->create(['company_name' => 'Old Company']);

    $placement->update(['company_name' => 'New Company']);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'subject_id' => $placement->id,
        'subject_type' => InternshipPlacement::class,
        'action' => 'updated',
    ]);

    $log = \Modules\Core\Models\AuditLog::first();
    expect($log->payload['company_name'])->toBe('New Company');
});

test('it records audit log when a placement is deleted', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $placement = InternshipPlacement::factory()->create();
    $id = $placement->id;

    $placement->delete();

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'subject_id' => $id,
        'subject_type' => InternshipPlacement::class,
        'action' => 'deleted',
    ]);
});
