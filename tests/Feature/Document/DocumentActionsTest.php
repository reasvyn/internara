<?php

declare(strict_types=1);

use App\Domain\Document\Actions\DeleteReportAction;
use App\Domain\Document\Actions\GenerateReportAction;
use App\Domain\Document\Actions\RenderDocumentAction;
use App\Domain\Document\Actions\SaveDocumentTemplateAction;
use App\Domain\Document\Models\Document;
use App\Domain\Mentee\Models\Mentee;
use App\Domain\Registration\Models\Registration;
use App\Domain\User\Models\User;
use Illuminate\Support\Facades\Storage;

describe('GenerateReportAction', function () {
    it('generates a report document', function () {
        Storage::fake('local');

        $report = app(GenerateReportAction::class)->execute([
            'name' => 'Monthly Summary',
            'type' => 'monthly',
            'description' => 'A monthly summary report',
            'parameters' => ['period' => 'January 2026'],
        ]);

        expect($report)->toBeInstanceOf(Document::class)
            ->and($report->name)->toBe('Monthly Summary')
            ->and($report->category->value)->toBe('report')
            ->and($report->is_active)->toBeTrue()
            ->and(Storage::disk('local')->exists('reports/'.$report->slug.'.json'))->toBeTrue();
    });
});

describe('DeleteReportAction', function () {
    it('deletes a report document', function () {
        $report = Document::factory()->create(['category' => 'report']);

        app(DeleteReportAction::class)->execute($report);

        expect(Document::find($report->id))->toBeNull();
    });
});

describe('SaveDocumentTemplateAction', function () {
    it('creates a new document template', function () {
        $doc = app(SaveDocumentTemplateAction::class)->execute([
            'name' => 'Internship Report Template',
            'category' => 'report',
            'content' => '<h1>Report Content</h1>',
        ]);

        expect($doc)->toBeInstanceOf(Document::class)
            ->and($doc->name)->toBe('Internship Report Template')
            ->and($doc->slug)->toBe('internship-report-template');
    });

    it('updates an existing template when id is provided', function () {
        $existing = Document::factory()->create(['name' => 'Old Template']);

        $doc = app(SaveDocumentTemplateAction::class)->execute([
            'id' => $existing->id,
            'name' => 'Updated Template',
            'category' => 'report',
        ]);

        expect($doc->id)->toBe($existing->id)
            ->and($doc->name)->toBe('Updated Template');
    });
});

describe('RenderDocumentAction', function () {
    it('renders a document and creates a new rendered document', function () {
        $template = Document::factory()->create(['content' => '<h1>Hello {{ $target->mentee->user->name }}</h1>']);
        $user = User::factory()->create();
        $mentee = Mentee::factory()->create(['user_id' => $user->id]);
        $registration = Registration::factory()->create(['mentee_id' => $mentee->id]);

        Storage::fake('local');

        $rendered = app(RenderDocumentAction::class)->execute($template, $registration);

        expect($rendered)->toBeInstanceOf(Document::class)
            ->and($rendered->category->value)->toBe('report')
            ->and($rendered->name)->toContain($template->name);
    });
});
