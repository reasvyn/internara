<?php

declare(strict_types=1);

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateReportAction;
use App\Modules\Document\Domain\OfficialDocument\Actions\SaveDocumentTemplateAction;
use App\Modules\Document\Models\Document;
use App\Modules\Document\Policies\DocumentPolicy;
use App\Modules\Document\Services\DocumentRenderer;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

function pkyx6Admin(object $test): User
{
    $admin = User::factory()->create();
    $admin->assignRole('admin');
    $test->actingAs($admin);

    return $admin;
}

describe('PKYX6: document templates and report', function (): void {
    test('PKYX6-FR-DOC-001: templates create and update through the command action (also UC-DOC-001)', function (): void {
        $action = app(SaveDocumentTemplateAction::class);

        expect($action)->toBeInstanceOf(BaseCommandAction::class);

        $template = $action->execute(['title' => 'Surat Pengantar', 'content' => 'Hello {{ $target->name }}', 'type' => 'template']);

        expect($template->slug)->toBe('surat-pengantar')
            ->and($template->is_active)->toBeTrue();

        $updated = $action->execute(['id' => $template->id, 'title' => 'Surat Pengantar', 'content' => 'Updated body']);

        expect($updated->id)->toBe($template->id)
            ->and($updated->content)->toBe('Updated body');
    });

    test('PKYX6-FR-DOC-004: retired templates stay readable but policy gates viewing (also FR-DOC-007)', function (): void {
        $admin = pkyx6Admin($this);
        $student = User::factory()->create();
        $student->assignRole('student');
        $policy = new DocumentPolicy;

        $live = Document::factory()->create(['is_active' => true]);
        $retired = Document::factory()->create(['is_active' => false]);

        expect($policy->view($admin, $retired))->toBeTrue()
            ->and($policy->view($student, $live))->toBeTrue()
            ->and($policy->view($student, $retired))->toBeFalse()
            ->and($policy->create($student))->toBeFalse()
            ->and($policy->delete($student, $live))->toBeFalse();
    });

    test('PKYX6-FR-DOC-006: documents carry UUID keys with declared casts', function (): void {
        $document = Document::factory()->create();

        expect(Str::isUuid($document->id))->toBeTrue()
            ->and($document->is_active)->toBeBool()
            ->and($document->metadata)->toBeArray();
    });

    test('PKYX6-FR-DOC-011: renderer previews HTML from template and context (also DD-DOC-001)', function (): void {
        $renderer = app(DocumentRenderer::class);
        $document = Document::factory()->make(['content' => 'Hello {{ $target->name }}!']);
        $target = new User(['name' => 'Sinta']);

        expect($renderer->renderHtml($document, $target))->toContain('Hello Sinta!');
    });

    test('PKYX6-FR-DOC-012: renderer compiles HTML to PDF bytes', function (): void {
        $document = Document::factory()->make(['content' => '<h1>Slip</h1>']);

        $pdf = app(DocumentRenderer::class)->renderPdf($document, new User(['name' => 'Sinta']));

        expect($pdf)->toStartWith('%PDF');
    });

    test('PKYX6-FR-DOC-017: report requests validate identity and parameters', function (): void {
        expect(fn () => app(GenerateReportAction::class)->execute([]))->toThrow(ValidationException::class);
    });

    test('PKYX6-FR-DOC-020: on-the-fly rendering persists nothing', function (): void {
        Storage::fake('local');
        $document = Document::factory()->make(['content' => '<h1>Preview</h1>']);

        $pdf = app(DocumentRenderer::class)->renderPdf($document, new User(['name' => 'Sinta']));

        expect($pdf)->toStartWith('%PDF')
            ->and(Storage::disk('local')->allFiles())->toBe([]);
    });

    test('PKYX6-DD-DOC-003: generated PDFs stay on the local disk', function (): void {
        Storage::fake('local');
        pkyx6Admin($this);

        $report = app(GenerateReportAction::class)->execute([
            'name' => 'Monthly recap',
            'type' => 'internship_completion',
            'description' => null,
            'parameters' => [],
        ]);

        expect($report->file_path)->toStartWith('reports/')
            ->and(Storage::disk('local')->exists($report->file_path))->toBeTrue();
    });

    test('PKYX6-FR-DOC-022: template and report mutations write audit entries', function (): void {
        pkyx6Admin($this);

        app(SaveDocumentTemplateAction::class)->execute(['title' => 'Audited Template', 'content' => 'Body']);

        expect(DB::table('activity_log')->where('description', 'document_template_saved')->exists())->toBeTrue();

        app(GenerateReportAction::class)->execute(['name' => 'Audited Report', 'type' => 'completion']);

        expect(DB::table('activity_log')->where('description', 'report_generated')->exists())->toBeTrue();
    });

    test('PKYX6-NFR-DOC-003: stored paths expose no server internals', function (): void {
        Storage::fake('local');

        $report = app(GenerateReportAction::class)->execute(['name' => 'Path probe', 'type' => 'completion']);

        expect($report->file_path)->not->toContain('/app')
            ->and($report->file_path)->not->toContain(base_path())
            ->and($report->file_path)->toStartWith('reports/');
    });

    test('PKYX6-FR-DOC-024: template and report actions share the layer bases with strict types', function (): void {
        foreach (glob(base_path('app/Modules/Document/Domain/OfficialDocument/Actions/*.php')) as $file) {
            $source = file_get_contents($file);

            expect($source)->toContain('extends BaseCommandAction')
                ->and($source)->toContain('declare(strict_types=1)');
        }
    });
});
