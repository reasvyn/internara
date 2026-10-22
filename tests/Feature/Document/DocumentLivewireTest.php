<?php

declare(strict_types=1);

namespace Tests\Feature\Document;

use App\Modules\Document\Domain\Handbook\Enums\HandbookAudience;
use App\Modules\Document\Domain\Handbook\Livewire\HandbookManager;
use App\Modules\Document\Domain\Handbook\Livewire\StudentHandbookList;
use App\Modules\Document\Domain\OfficialDocument\Actions\DeleteReportAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateReportAction;
use App\Modules\Document\Domain\OfficialDocument\Livewire\ReportsManager;
use App\Modules\Document\Domain\OfficialDocument\Livewire\TemplateManager;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Models\Document;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

describe('7H5D6 / ZUFG8: Document Livewire UI Components', function (): void {
    test('7H5D6-FR-DOC-001: HandbookManager renders headers, lists handbooks, and controls modal', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Document::create([
            'title' => 'Panduan Magang Industri 2026',
            'slug' => 'panduan-magang-industri-2026',
            'type' => DocumentCategory::HANDBOOK->value,
            'content' => 'Isi panduan pelaksanaan magang kejuruan',
            'metadata' => [
                'audience' => HandbookAudience::STUDENT->value,
                'version' => '1.0',
            ],
            'is_active' => true,
        ]);

        $test = Livewire::test(HandbookManager::class);
        $test->assertOk()
            ->assertSee('Panduan Magang Industri 2026');

        expect(collect($test->instance()->headers())->pluck('index')->all())
            ->toContain('title', 'audience', 'version', 'is_active', 'created_at');

        $test->call('create');
        expect($test->get('showModal'))->toBeTrue();
    });

    test('7H5D6-FR-DOC-002: StudentHandbookList renders for authenticated student', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        Document::create([
            'title' => 'Buku Saku Siswa Magang',
            'slug' => 'buku-saku-siswa-magang',
            'type' => DocumentCategory::HANDBOOK->value,
            'content' => 'Panduan tata tertib magang',
            'metadata' => [
                'audience' => HandbookAudience::STUDENT->value,
                'version' => '2.0',
            ],
            'is_active' => true,
        ]);

        $test = Livewire::test(StudentHandbookList::class);
        $test->assertOk()
            ->assertSee('Buku Saku Siswa Magang');
    });

    test('ZUFG8-FR-DOC-003: TemplateManager renders document templates and categories', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        Document::create([
            'title' => 'Surat Pengantar Magang',
            'slug' => 'surat-pengantar-magang',
            'type' => DocumentCategory::LETTER->value,
            'content' => 'Template resmi permohonan magang',
            'is_active' => true,
        ]);

        $test = Livewire::test(TemplateManager::class);
        $test->assertOk()
            ->assertSee('Surat Pengantar Magang');

        expect(collect($test->instance()->headers())->pluck('index')->all())
            ->toContain('title', 'type', 'is_active', 'created_at')
            ->and($test->instance()->categories())->toBeArray();
    });

    test('7H5D6-FR-DOC-004: ReportsManager generates and deletes official reports', function (): void {
        Storage::fake('local');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $test = Livewire::test(ReportsManager::class);
        $test->assertOk();

        // Generate report
        $test->call('generateReport', 'internship_completion', app(GenerateReportAction::class));

        $generated = Document::where('type', 'report')->first();
        expect($generated)->not->toBeNull()
            ->and($generated->title)->toBe('Internship Completion Summary');

        // Delete report
        $test->call('deleteReport', $generated, app(DeleteReportAction::class));
        expect(Document::where('id', $generated->id)->exists())->toBeFalse();
    });
});
