<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Enrollment\Registration\Models\Registration;
use App\User\Dashboard\Actions\ReadStudentDashboardAction;
use App\User\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('get student dashboard data action returns statistics correctly', function () {
    $student = User::factory()->create();
    $registration = Registration::factory()->create([
        'student_id' => $student->id,
        'status' => 'active',
    ]);

    $handbook1 = Document::factory()->create(['type' => 'handbook', 'is_active' => true]);
    $handbook2 = Document::factory()->create(['type' => 'handbook', 'is_active' => true]);

    activity()
        ->performedOn($handbook1)
        ->causedBy($student)
        ->inLog('document')
        ->withProperties(['ip_address' => '127.0.0.1'])
        ->event('acknowledged')
        ->log('acknowledged');

    $action = new ReadStudentDashboardAction;
    $data = $action->execute($student->id);

    expect($data)->toBeArray();
    expect($data['registration']->id)->toBe($registration->id);
    expect($data['handbookTotalCount'])->toBe(2);
    expect($data['handbookReadCount'])->toBe(1);
});
