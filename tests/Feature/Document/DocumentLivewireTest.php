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

    test('7H5D6-FR-DOC-005: StudentHandbookList supports search, filtering, reader modal and acknowledgment flow', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $this->actingAs($student);

        $doc = Document::create([
            'title' => 'Tata Tertib Praktik Kerja',
            'slug' => 'tata-tertib-praktik-kerja',
            'type' => DocumentCategory::HANDBOOK->value,
            'content' => 'Rincian tata tertib dan disiplin',
            'metadata' => [
                'target_audience' => HandbookAudience::STUDENT->value,
                'description' => 'Pedoman kedisiplinan di tempat magang',
                'version' => 1,
            ],
            'version' => 1,
            'is_active' => true,
        ]);

        $component = Livewire::test(StudentHandbookList::class);
        $component->assertOk()
            ->assertSee('Tata Tertib Praktik Kerja')
            ->assertSet('filter', 'all')
            ->assertSet('showReaderModal', false);

        expect($component->instance()->stats)->toBe([
            'total' => 1,
            'read' => 0,
            'pending' => 1,
            'percent' => 0,
        ]);

        // Search test
        $component->set('search', 'Kedisiplinan')
            ->assertSee('Tata Tertib Praktik Kerja');

        $component->set('search', 'Nonexistent')
            ->assertDontSee('Tata Tertib Praktik Kerja');

        $component->call('resetFilters')
            ->assertSet('search', '')
            ->assertSee('Tata Tertib Praktik Kerja');

        // Open reader modal
        $component->call('openReader', $doc->id)
            ->assertSet('showReaderModal', true)
            ->assertSet('readingHandbookId', $doc->id);

        // Acknowledge flow
        $component->call('acknowledge', $doc->id, app(\App\Modules\Document\Domain\Handbook\Actions\AcknowledgeHandbookAction::class))
            ->assertHasNoErrors();

        $component->call('closeReader')
            ->assertSet('showReaderModal', false);

        expect($component->instance()->stats['read'])->toBe(1)
            ->and($component->instance()->stats['pending'])->toBe(0)
            ->and($component->instance()->stats['percent'])->toBe(100);

        // Filter by completed
        $component->call('setFilter', 'completed')
            ->assertSee('Tata Tertib Praktik Kerja');

        // Filter by pending (now empty)
        $component->call('setFilter', 'pending')
            ->assertDontSee('Tata Tertib Praktik Kerja');
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
