<?php

declare(strict_types=1);

use App\Domain\Document\Actions\QueueReportGenerationAction;
use App\Domain\User\Models\User;
use App\Enums\Auth\Role as RoleEnum;
use App\Jobs\Report\GenerateReportJob;
use App\Livewire\Admin\Report\ReportsManager;
use App\Models\GeneratedReport;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    foreach (RoleEnum::cases() as $role) {
        Role::firstOrCreate([
            'name' => $role->value,
            'guard_name' => 'web',
        ]);
    }

    $this->admin = User::factory()->create();
    $this->admin->assignRole('admin');

    Storage::fake('private');
});

test('admin can view report index', function () {
    $response = $this->actingAs($this->admin)->get(route('admin.reports.index'));

    $response->assertOk();
});

test('admin can queue a report for generation via Livewire', function () {
    Queue::fake();

    Livewire::actingAs($this->admin)
        ->test(ReportsManager::class)
        ->set('formData.report_type', 'attendance_summary')
        ->call('generateReport', app(QueueReportGenerationAction::class))
        ->assertHasNoErrors();

    Queue::assertPushed(GenerateReportJob::class);

    $this->assertDatabaseHas('generated_reports', [
        'user_id' => $this->admin->id,
        'report_type' => 'attendance_summary',
        'status' => 'pending',
    ]);
});

test('admin can download a completed report', function () {
    Storage::disk('private')->put('reports/test.pdf', 'report content');

    $report = GeneratedReport::factory()->create([
        'user_id' => $this->admin->id,
        'status' => 'completed',
        'file_path' => 'reports/test.pdf',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports.download', $report));

    $response->assertOk()->assertStreamedContent('report content');
});

test('student cannot access admin reports', function () {
    $student = User::factory()->create();
    $student->assignRole('student');

    $response = $this->actingAs($student)->get(route('admin.reports.index'));

    $response->assertForbidden();
});

test('user cannot download another user report', function () {
    Storage::disk('private')->put('reports/test.pdf', 'report content');

    $otherUser = User::factory()->create();
    $report = GeneratedReport::factory()->create([
        'user_id' => $otherUser->id,
        'status' => 'completed',
        'file_path' => 'reports/test.pdf',
    ]);

    $response = $this->actingAs($this->admin)->get(route('admin.reports.download', $report));

    $response->assertForbidden();
});

test('report shows failed status when generation fails', function () {
    $report = GeneratedReport::factory()
        ->failed()
        ->create([
            'user_id' => $this->admin->id,
        ]);

    Livewire::actingAs($this->admin)->test(ReportsManager::class)->assertSee('Failed');
});

test('report list shows only current user reports', function () {
    $otherUser = User::factory()->create();
    $otherUser->assignRole('admin');

    GeneratedReport::factory()->create(['user_id' => $otherUser->id, 'report_type' => 'other_report']);
    GeneratedReport::factory()->create(['user_id' => $this->admin->id, 'report_type' => 'my_report']);

    Livewire::actingAs($this->admin)->test(ReportsManager::class)
        ->assertSee('my_report')
        ->assertDontSee('other_report');
});
