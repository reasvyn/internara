<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateDocumentAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateReportAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\RenderDocumentAction;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\Enrollment\Domain\Placement\Models\Placement;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\Program\Domain\InternshipGroup\Models\InternshipGroupMember;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

describe('7H5D6: official letter generation', function () {
    test('7H5D6-FR-OFFD-012: single letter renders synchronously within the request', function (): void {
        Queue::fake();
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $template = Document::factory()->create([
            'title' => 'Introduction Letter',
            'content' => '<h1>Introduction for {{ $target->name }}</h1>',
        ]);

        $rendered = app(GenerateDocumentAction::class)->execute($template, (object) ['name' => 'Amara Putri']);

        expect($rendered->file_path)->not->toBeNull()
            ->and(Storage::disk('local')->exists($rendered->file_path))->toBeTrue()
            ->and(Storage::disk('local')->size($rendered->file_path))->toBeGreaterThan(0)
            ->and(str_starts_with(Storage::disk('local')->get($rendered->file_path), '%PDF'))->toBeTrue();

        Queue::assertNothingPushed();
    });

    test('7H5D6-NFR-OFFD-002: rendered letter carries resolved values and no blade placeholders', function (): void {
        $template = Document::factory()->create([
            'content' => '<p>Dear {{ $target->name }} of {{ $target->site }}</p>',
        ]);

        $html = app(DocumentRenderer::class)->renderHtml($template, (object) ['name' => 'Amara Putri', 'site' => 'PT Maju Jaya']);
        $pdf = app(DocumentRenderer::class)->renderPdf($template, (object) ['name' => 'Amara Putri', 'site' => 'PT Maju Jaya']);

        expect($html)->toContain('Amara Putri')
            ->and($html)->toContain('PT Maju Jaya')
            ->and($html)->not->toContain('{{')
            ->and($html)->not->toContain('}}')
            ->and(str_starts_with($pdf, '%PDF'))->toBeTrue()
            ->and(strlen($pdf))->toBeGreaterThan(0);
    });

    test('7H5D6-UC-OFFD-001: introduction letter generates per registration carrying the student name', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create(['name' => 'Budi Santoso']);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $student->id,
        ]);
        $template = Document::factory()->create([
            'title' => 'Introduction Letter',
            'content' => '<p>Introducing {{ $target->mentee->user->name }}</p>',
        ]);

        $issued = app(RenderDocumentAction::class)->execute($template, $registration);

        expect($issued->type)->toBe('report')
            ->and($issued->title)->toContain('Budi Santoso')
            ->and($issued->file_path)->not->toBeNull()
            ->and(Storage::disk('local')->exists($issued->file_path))->toBeTrue()
            ->and(str_starts_with(Storage::disk('local')->get($issued->file_path), '%PDF'))->toBeTrue();
    });

    test('7H5D6-UC-OFFD-003: acceptance confirmation freezes the issued content snapshot', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create(['name' => 'Citra Dewi']);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $student->id,
        ]);
        $template = Document::factory()->create([
            'title' => 'Acceptance Letter',
            'content' => '<p>Accepted {{ $target->mentee->user->name }} for placement v1</p>',
        ]);

        $issued = app(RenderDocumentAction::class)->execute($template, $registration);
        $frozenContent = $issued->content;
        $frozenBytes = Storage::disk('local')->get($issued->file_path);

        $template->update(['content' => '<p>Accepted for placement v2 — redesigned</p>']);

        expect($issued->fresh()->content)->toBe($frozenContent)
            ->and($issued->fresh()->content)->toContain('v1')
            ->and(Storage::disk('local')->get($issued->file_path))->toBe($frozenBytes);
    });

    test('7H5D6-NFR-OFFD-004: every issuance stores its frozen variable snapshot for later audit', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create(['name' => 'Dedi Kurniawan']);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $student->id,
        ]);
        $template = Document::factory()->create([
            'title' => 'Completion Letter',
            'content' => '<p>Completed by {{ $target->mentee->user->name }}</p>',
        ]);
        $issuedAtSource = $template->content;

        $issued = app(RenderDocumentAction::class)->execute($template, $registration);
        $frozenBytes = Storage::disk('local')->get($issued->file_path);

        $template->update(['content' => '<p>Redesigned completion wording</p>']);

        $this->assertDatabaseHas('documents', [
            'id' => $issued->id,
            'type' => 'report',
            'content' => $issuedAtSource,
        ]);
        expect($issued->fresh()->content)->toBe($issuedAtSource)
            ->and(Storage::disk('local')->get($issued->file_path))->toBe($frozenBytes);
    });

    test('7H5D6-FR-OFFD-014: issuance record stores type, title, file path, and an audit entry', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create(['name' => 'Eka Prasetyo']);
        $registration = Registration::factory()->create(['student_id' => $student->id]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $student->id,
        ]);
        $template = Document::factory()->create([
            'title' => 'Acceptance Letter',
            'content' => '<p>Welcome {{ $target->mentee->user->name }}</p>',
        ]);

        $issued = app(RenderDocumentAction::class)->execute($template, $registration);

        $this->assertDatabaseHas('documents', [
            'id' => $issued->id,
            'type' => 'report',
            'title' => $issued->title,
            'file_path' => $issued->file_path,
        ]);
        $this->assertDatabaseHas('activity_log', [
            'description' => 'document_rendered',
            'subject_id' => $issued->id,
        ]);
    });

    test('7H5D6-NFR-OFFD-003: every generation and report path writes a SmartLogger entry', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $template = Document::factory()->create(['content' => '<p>Hi {{ $target->name }}</p>']);
        app(GenerateDocumentAction::class)->execute($template, (object) ['name' => 'Fajar Nugroho']);
        app(GenerateReportAction::class)->execute(['name' => 'Completion Summary', 'type' => 'internship_completion']);

        $this->assertDatabaseHas('activity_log', ['description' => 'document_generated']);
        $this->assertDatabaseHas('activity_log', ['description' => 'report_generated']);
    });

    test('7H5D6-FR-OFFD-005: letter variables resolve from live school, program, company, and student records', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create(['name' => 'Gita Permata']);
        $internship = Internship::factory()->create(['name' => 'PKL Angkatan 2026']);
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
        ]);
        InternshipGroupMember::factory()->create([
            'registration_id' => $registration->id,
            'user_id' => $student->id,
        ]);
        $placement = Placement::factory()->create(['internship_id' => $internship->id]);
        $registration->update(['placement_id' => $placement->id]);

        $template = Document::factory()->create([
            'content' => '<p>{{ $target->mentee->user->name }} at {{ $target->placement->company->name }} via {{ $target->internship->name }}</p>',
        ]);

        $html = app(DocumentRenderer::class)->renderHtml($template, $registration->fresh()->loadMissing([
            'mentee.user', 'internship', 'placement.company',
        ]));

        expect($html)->toContain('Gita Permata')
            ->and($html)->toContain($placement->company->name)
            ->and($html)->toContain('PKL Angkatan 2026');
    });

    test('7H5D6-FR-OFFD-020: generation with invalid input fails closed without persisting anything', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $before = Document::count();

        try {
            app(GenerateReportAction::class)->execute(['name' => '', 'type' => '']);
            expect(false)->toBeTrue('expected validation to abort generation');
        } catch (ValidationException) {
            expect(true)->toBeTrue();
        }

        expect(Document::count())->toBe($before)
            ->and(Storage::disk('local')->files('report'))->toBe([]);
    });

    test('7H5D6-FR-OFFD-023: issuance audit-logs through SmartLogger with document identifiers', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $template = Document::factory()->create([
            'type' => 'letter',
            'content' => '<p>Official {{ $target->name }}</p>',
        ]);
        $rendered = app(GenerateDocumentAction::class)->execute($template, (object) ['name' => 'Hadi Wijaya']);

        $entry = DB::table('activity_log')->where('description', 'document_generated')->first();

        expect($entry)->not->toBeNull()
            ->and((string) $entry->subject_id)->toBe((string) $rendered->id);

        $properties = json_decode((string) $entry->properties, true);
        expect($properties)->toBeArray()
            ->and($properties['payload']['document_id'] ?? null)->toBe($rendered->id);
    });
});
