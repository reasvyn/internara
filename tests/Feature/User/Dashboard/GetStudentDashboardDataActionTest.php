<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\Models\DocumentAcknowledgement;
use App\Enrollment\Models\Registration;
use App\User\Dashboard\Actions\GetStudentDashboardDataAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('get student dashboard data action returns statistics correctly', function () {
    $student = User::factory()->create();
    $registration = Registration::factory()->create([
        'student_id' => $student->id,
        'status' => 'active',
    ]);

    // Create policy documents
    $policy1 = Document::factory()->create(['type' => 'policy', 'is_active' => true]);
    $policy2 = Document::factory()->create(['type' => 'policy', 'is_active' => true]);

    // Acknowledge one policy document
    DocumentAcknowledgement::factory()->create([
        'user_id' => $student->id,
        'document_id' => $policy1->id,
    ]);

    $action = new GetStudentDashboardDataAction;
    $data = $action->execute($student->id);

    expect($data)->toBeArray();
    expect($data['registration']->id)->toBe($registration->id);
    expect($data['handbookTotalCount'])->toBe(2);
    expect($data['handbookReadCount'])->toBe(1);
});
