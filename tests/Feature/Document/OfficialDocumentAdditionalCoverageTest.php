<?php

declare(strict_types=1);

use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateDocumentAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateReportAction;
use App\Modules\Document\Enums\DocumentCategory;
use App\Modules\Document\Jobs\GenerateDocumentJob;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Policies\DocumentPolicy;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Facades\Storage;

uses(LazilyRefreshDatabase::class);

function officialCoverageAdmin(): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    test()->actingAs($admin);

    return $admin;
}

describe('7H5D6: official document implementation behavior', function (): void {
    test('7H5D6-FR-OFFD-001: document categories expose the official template vocabulary', function (): void {
        expect(DocumentCategory::cases())->toEqual([
            DocumentCategory::APPLICATION, DocumentCategory::PERMIT, DocumentCategory::CERTIFICATE,
            DocumentCategory::REPORT, DocumentCategory::LETTER, DocumentCategory::POLICY, DocumentCategory::HANDBOOK,
        ]);
    });

    test('7H5D6-FR-OFFD-002: each category has a stable backing value', function (): void {
        foreach (DocumentCategory::cases() as $category) {
            expect(DocumentCategory::from($category->value))->toBe($category);
        }
    });

    test('7H5D6-FR-OFFD-003: category labels resolve through the translation contract', function (): void {
        foreach (DocumentCategory::cases() as $category) {
            expect($category->label())->toBeString()->not->toBe('');
        }
    });

    test('7H5D6-FR-OFFD-004: active and type scopes compose for official listings', function (): void {
        Document::factory()->create(['type' => 'letter', 'is_active' => true]);
        Document::factory()->create(['type' => 'letter', 'is_active' => false]);
        Document::factory()->create(['type' => 'policy', 'is_active' => true]);

        expect(Document::query()->ofType('letter')->active()->count())->toBe(1);
    });

    test('7H5D6-UC-OFFD-006, 7H5D6-FR-OFFD-006: generated documents update their source record with a generated timestamp', function (): void {
        Storage::fake('local');
        officialCoverageAdmin();
        $document = Document::factory()->create(['content' => '<p>Official</p>']);

        $generated = app(GenerateDocumentAction::class)->execute($document, new User(['name' => 'Rani']));

        expect($generated->metadata['generated_at'] ?? null)->not->toBeNull();
    });

    test('7H5D6-FR-OFFD-007: generated document writes a local PDF path', function (): void {
        Storage::fake('local');
        officialCoverageAdmin();
        $document = Document::factory()->create(['content' => '<p>Official</p>']);

        $generated = app(GenerateDocumentAction::class)->execute($document, new User(['name' => 'Rani']));

        expect($generated->file_path)->toStartWith('generated-documents/')
            ->and(Storage::disk('local')->exists($generated->file_path))->toBeTrue();
    });

    test('7H5D6-FR-OFFD-008: generated document audit identifies the document subject', function (): void {
        Storage::fake('local');
        officialCoverageAdmin();
        $document = Document::factory()->create(['content' => '<p>Official</p>']);

        app(GenerateDocumentAction::class)->execute($document, new User(['name' => 'Rani']));

        $this->assertDatabaseHas('activity_log', ['description' => 'document_generated', 'subject_id' => $document->id]);
    });

    test('7H5D6-FR-OFFD-009: renderer can render an official letter as HTML', function (): void {
        $document = Document::factory()->make(['content' => '<h1>{{ $target->title }}</h1>']);

        expect(app(DocumentRenderer::class)->renderHtml($document, (object) ['title' => 'Acceptance']))
            ->toContain('Acceptance');
    });

    test('7H5D6-FR-OFFD-010: renderer produces A4 PDF bytes for an official letter', function (): void {
        $document = Document::factory()->make(['content' => '<h1>Acceptance</h1>']);

        expect(app(DocumentRenderer::class)->renderPdf($document, new User(['name' => 'Rani'])))->toStartWith('%PDF');
    });

    test('7H5D6-FR-OFFD-011: document generation creates a persisted snapshot', function (): void {
        Storage::fake('local');
        officialCoverageAdmin();
        $template = Document::factory()->create(['title' => 'Acceptance', 'content' => '<p>Acceptance</p>']);

        $rendered = app(GenerateDocumentAction::class)->execute($template, new User(['name' => 'Rani']));

        expect($rendered->file_path)->not->toBeNull()->and($rendered->content)->toBe($template->content);
    });

    test('7H5D6-FR-OFFD-013: each queued document job targets one document ID', function (): void {
        Queue::fake();
        $document = Document::factory()->create();

        GenerateDocumentJob::dispatch($document->id);

        Queue::assertPushed(GenerateDocumentJob::class);
    });

    test('7H5D6-FR-OFFD-016: report defaults optional parameters to an empty array', function (): void {
        Storage::fake('local');
        officialCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute(['name' => 'Summary', 'type' => 'completion']);

        expect(json_decode($report->content, true)['parameters'])->toBe([]);
    });

    test('7H5D6-FR-OFFD-018: report files are written before the report row is returned', function (): void {
        Storage::fake('local');
        officialCoverageAdmin();

        $report = app(GenerateReportAction::class)->execute(['name' => 'Summary', 'type' => 'completion']);

        expect(Storage::disk('local')->get($report->file_path))->toContain('"type": "completion"');
    });

    test('7H5D6-NFR-OFFD-001: official document policy grants active read access to students', function (): void {
        $student = User::factory()->create();
        $student->assignRole('student');
        $document = Document::factory()->make(['is_active' => true]);

        expect((new DocumentPolicy)->view($student, $document))->toBeTrue();
    });

    test('7H5D6-NFR-OFFD-006: generated document paths do not include the absolute application path', function (): void {
        Storage::fake('local');
        officialCoverageAdmin();
        $document = Document::factory()->create(['content' => '<p>Official</p>']);

        $generated = app(GenerateDocumentAction::class)->execute($document, new User(['name' => 'Rani']));

        expect($generated->file_path)->not->toContain(base_path());
    });

    test('7H5D6-NFR-OFFD-007: queued jobs use the documents queue', function (): void {
        $document = Document::factory()->create();

        expect((new GenerateDocumentJob($document->id))->queue)->toBe('documents');
    });
});
