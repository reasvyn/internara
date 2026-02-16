<?php

declare(strict_types=1);

use Modules\Internship\Models\Company;
use Modules\Internship\Models\InternshipPlacement;
use Modules\User\Models\User;


test('it records audit log when a placement is updated', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    $company = Company::factory()->create(['name' => 'Old Company']);
    $newCompany = Company::factory()->create(['name' => 'New Company']);
    $placement = InternshipPlacement::factory()->create(['company_id' => $company->id]);

    $placement->update(['company_id' => $newCompany->id]);

    $this->assertDatabaseHas('audit_logs', [
        'user_id' => $user->id,
        'subject_id' => $placement->id,
        'subject_type' => InternshipPlacement::class,
        'action' => 'updated',
    ]);

    $log = \Modules\Log\Models\AuditLog::first();
    expect($log->payload['company_id'])->toBe($newCompany->id);
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
