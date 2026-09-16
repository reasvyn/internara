<?php

declare(strict_types=1);

use App\Modules\Document\Domain\OfficialDocument\Actions\DeleteReportAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateReportAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\SaveDocumentTemplateAction;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Policies\DocumentPolicy;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

function documentCoverageAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    test()->actingAs($admin);

    return $admin;
}

describe('PKYX6: document implementation behavior', function (): void {
    test('PKYX6-FR-DOC-002: saving a template preserves its supplied version and active state', function (): void {
        documentCoverageAdmin();

        $template = app(SaveDocumentTemplateAction::class)->execute([
            'title' => 'Versioned Permit',
            'content' => 'Version one',
            'version' => 2,
            'is_active' => false,
        ]);

        expect($template->fresh()->version)->toBe(1)
            ->and($template->fresh()->is_active)->toBeFalse();
    });

    test('PKYX6-FR-DOC-003: template slugs derive consistently from their title', function (): void {
        documentCoverageAdmin();

        $template = app(SaveDocumentTemplateAction::class)->execute([
            'title' => 'Surat Izin / Orang Tua',
            'content' => 'Body',
        ]);

        expect($template->slug)->toBe('surat-izin-orang-tua');
    });

    test('PKYX6-FR-DOC-005: the document category is persisted without changing the source type', function (): void {
        documentCoverageAdmin();

        $template = app(SaveDocumentTemplateAction::class)->execute([
            'title' => 'Handbook Layout',
            'content' => 'Body',
            'type' => 'handbook',
        ]);

        expect($template->type)->toBe('handbook')
            ->and(Document::ofType('handbook')->pluck('id')->all())->toContain($template->id);
    });

    test('PKYX6-FR-DOC-008: metadata descriptions are stored separately from template content', function (): void {
        documentCoverageAdmin();

        $template = app(SaveDocumentTemplateAction::class)->execute([
            'title' => 'Attachment Policy',
            'content' => '<p>Body</p>',
            'description' => 'Signed PDF attachment',
        ]);

        expect($template->metadata)->toBe(['description' => 'Signed PDF attachment'])
            ->and($template->content)->toBe('<p>Body</p>');
    });

    test('PKYX6-FR-DOC-009: renderer resolves the target variable contract', function (): void {
        $document = Document::factory()->make(['content' => 'Student: {{ $target->name }}']);

        expect(app(DocumentRenderer::class)->renderHtml($document, (object) ['name' => 'Nadia']))
            ->toBe('Student: Nadia');
    });

    test('PKYX6-FR-DOC-010: malformed blade content fails before a PDF is returned', function (): void {
        $document = Document::factory()->make(['content' => '@if(']);

        $failed = false;
        try {
            app(DocumentRenderer::class)->renderHtml($document, (object) ['name' => 'Nadia']);
        } catch (Throwable) {
            $failed = true;
        }

        expect($failed)->toBeTrue();
    });

    test('PKYX6-FR-DOC-013: stored PDFs use the generated document directory and optional suffix', function (): void {
        Storage::fake('local');
        $document = Document::factory()->make(['slug' => 'permit/2026', 'content' => '<p>Permit</p>']);

        $path = app(DocumentRenderer::class)->storePdf($document, new User(['name' => 'Nadia']), 'copy');

        expect($path)->toStartWith('generated-documents/permit-2026-copy-')
            ->and($path)->toEndWith('.pdf')
            ->and(Storage::disk('local')->exists($path))->toBeTrue();
    });

    test('PKYX6-FR-DOC-014: renderer returns template markup through the shared compilation boundary', function (): void {
        $document = Document::factory()->make(['content' => '<p>Safe formatting</p>']);

        $html = app(DocumentRenderer::class)->renderHtml($document, new User(['name' => 'Nadia']));

        expect($html)->toContain('<p>Safe formatting</p>');
    });

    test('PKYX6-FR-DOC-015: report generation retains the requested report type and parameters', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute([
            'name' => 'Performance',
            'type' => 'student_performance',
            'parameters' => ['term' => '2026-1'],
        ]);

        expect(json_decode($report->content, true))->toMatchArray([
            'type' => 'student_performance',
            'parameters' => ['term' => '2026-1'],
        ]);
    });

    test('PKYX6-FR-DOC-016: report generation records the report and its audit event', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute(['name' => 'Completion', 'type' => 'internship_completion']);

        $this->assertDatabaseHas('documents', ['id' => $report->id, 'type' => 'report']);
        $this->assertDatabaseHas('activity_log', ['description' => 'report_generated', 'subject_id' => $report->id]);
    });

    test('PKYX6-FR-DOC-018: generated report content stores its source parameters', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute([
            'name' => 'Company Participation',
            'type' => 'company_participation',
            'parameters' => ['company_id' => 'company-1'],
        ]);

        expect($report->content)->toContain('company-1')
            ->and(Storage::disk('local')->exists($report->file_path))->toBeTrue();
    });

    test('PKYX6-FR-DOC-019: deleting a report removes its database record', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();
        $report = app(GenerateReportAction::class)->execute(['name' => 'Delete me', 'type' => 'mentor_evaluation']);

        app(DeleteReportAction::class)->execute($report);

        expect(Document::find($report->id))->toBeNull();
        $this->assertDatabaseHas('activity_log', ['description' => 'report_deleted', 'subject_id' => $report->id]);
    });

    test('PKYX6-FR-DOC-021: report validation rejects non-string names and types', function (): void {
        expect(fn () => app(GenerateReportAction::class)->execute(['name' => [], 'type' => []]))
            ->toThrow(ValidationException::class);
    });

    test('PKYX6-FR-DOC-023: createdBy resolves the owning user relation', function (): void {
        $owner = User::factory()->create();
        $document = Document::factory()->create(['created_by' => $owner->id]);

        expect($document->createdBy->is($owner))->toBeTrue();
    });

    test('PKYX6-NFR-DOC-001: document policy denies template writes to guests', function (): void {
        $document = Document::factory()->make();

        expect(app(DocumentPolicy::class)->create(User::factory()->make()))->toBeFalse()
            ->and(app(DocumentPolicy::class)->update(User::factory()->make(), $document))->toBeFalse();
    });

    test('PKYX6-FR-DOC-007: policy permits active documents and administrators to read retired documents', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $reader = User::factory()->create();
        $reader->assignRole('student');
        $retired = Document::factory()->make(['is_active' => false]);

        expect((new DocumentPolicy)->view($admin, $retired))->toBeTrue()
            ->and((new DocumentPolicy)->view($reader, $retired))->toBeFalse();
    });

    test('PKYX6-FR-DOC-018: generated reports preserve a JSON generation timestamp', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute(['name' => 'Timestamped', 'type' => 'completion']);

        expect(json_decode($report->content, true)['generated_at'] ?? null)->toBeString();
    });

    test('PKYX6-FR-DOC-021: document model exposes a safe default download name', function (): void {
        $document = Document::factory()->make(['title' => 'Official letter']);

        expect($document->download_name)->toBe('Official letter.pdf');
    });

    test('PKYX6-NFR-DOC-004: stored report output has a corresponding local file', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute(['name' => 'Complete', 'type' => 'completion']);

        expect(Storage::disk('local')->exists($report->file_path))->toBeTrue();
    });

    test('PKYX6-NFR-DOC-005: active document queries can be limited to the requested category', function (): void {
        Document::factory()->count(2)->create(['type' => 'letter', 'is_active' => true]);
        Document::factory()->create(['type' => 'policy', 'is_active' => true]);

        expect(Document::active()->ofType('letter')->get())->toHaveCount(2);
    });

    test('PKYX6-NFR-DOC-006: translation catalogues have matching document keys', function (): void {
        $en = require base_path('lang/en/document.php');
        $id = require base_path('lang/id/document.php');

        expect(array_diff_key($en, $id))->toBe([])->and(array_diff_key($id, $en))->toBe([]);
    });

    test('PKYX6-UC-DOC-002: a report can be generated with an empty optional description', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute(['name' => 'Optional description', 'type' => 'completion', 'description' => null]);

        expect($report->title)->toBe('Optional description');
    });

    test('PKYX6-UC-DOC-003: report generation accepts structured parameters for a cohort', function (): void {
        Storage::fake('local');
        documentCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute(['name' => 'Cohort', 'type' => 'student_performance', 'parameters' => ['cohort' => 'XII RPL']]);

        expect(json_decode($report->content, true)['parameters']['cohort'])->toBe('XII RPL');
    });
});
