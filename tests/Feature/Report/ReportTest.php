<?php

declare(strict_types=1);

use App\Models\GeneratedReport;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');
});

test('admin can view report index', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.index'));

    $response->assertOk();
})->todo('Implement report index route and view');

test('admin can queue a report for generation', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.reports.store'), [
            'report_type' => 'attendance_summary',
            'filters' => [],
        ]);

    $response->assertRedirect();
})->todo('Implement report generation queue');

test('admin can download a completed report', function () {
    $report = GeneratedReport::factory()->create([
        'user_id' => $this->admin->id,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.download', $report));

    $response->assertOk();
})->todo('Implement report download with file serving');

test('student cannot access admin reports', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $response = $this->actingAs($student)
        ->get(route('admin.reports.index'));

    $response->assertForbidden();
})->todo('Implement RBAC middleware for report routes');

test('user cannot download another user report', function () {
    $otherUser = User::factory()->create();
    $report = GeneratedReport::factory()->create([
        'user_id' => $otherUser->id,
        'status' => 'completed',
    ]);

    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.download', $report));

    $response->assertForbidden();
})->todo('Implement report ownership check in policy');
