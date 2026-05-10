<?php

declare(strict_types=1);

use App\Models\Document;
use App\Models\Internship;
use App\Models\InternshipDocumentRequirement;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('belongs to an internship', function () {
    $internship = Internship::factory()->create();
    $doc = Document::factory()->create();
    $requirement = InternshipDocumentRequirement::factory()->create([
        'internship_id' => $internship->id,
        'document_id' => $doc->id,
    ]);

    expect($requirement->internship)->toBeInstanceOf(Internship::class)
        ->and($requirement->internship->id)->toBe($internship->id);
});

it('belongs to a document', function () {
    $internship = Internship::factory()->create();
    $doc = Document::factory()->create();
    $requirement = InternshipDocumentRequirement::factory()->create([
        'internship_id' => $internship->id,
        'document_id' => $doc->id,
    ]);

    expect($requirement->document)->toBeInstanceOf(Document::class)
        ->and($requirement->document->id)->toBe($doc->id);
});

it('has many registration documents', function () {
    $internship = Internship::factory()->create();
    $doc = Document::factory()->create();
    $requirement = InternshipDocumentRequirement::factory()->create([
        'internship_id' => $internship->id,
        'document_id' => $doc->id,
    ]);

    expect($requirement->registrationDocuments)->toHaveCount(0);
});

it('enforces unique internship and document pair', function () {
    $internship = Internship::factory()->create();
    $doc = Document::factory()->create();

    InternshipDocumentRequirement::factory()->create([
        'internship_id' => $internship->id,
        'document_id' => $doc->id,
    ]);

    expect(fn () => InternshipDocumentRequirement::factory()->create([
        'internship_id' => $internship->id,
        'document_id' => $doc->id,
    ]))->toThrow(QueryException::class);
});
