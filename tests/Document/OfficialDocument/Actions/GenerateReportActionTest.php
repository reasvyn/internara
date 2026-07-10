<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Document\OfficialDocument\Actions\GenerateReportAction;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Validation\ValidationException;

uses(LazilyRefreshDatabase::class);

test('generates report with valid data', function () {
    $document = app(GenerateReportAction::class)->execute([
        'name' => 'Annual Report',
        'type' => 'yearly',
        'description' => 'Yearly internship report',
    ]);

    expect($document)->toBeInstanceOf(Document::class);
    expect($document->title)->toBe('Annual Report');
});

test('throws validation error with missing required fields', function () {
    app(GenerateReportAction::class)->execute([]);
})->throws(ValidationException::class);
