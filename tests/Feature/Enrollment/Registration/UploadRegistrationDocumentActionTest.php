<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Enrollment\Registration\Actions\UploadRegistrationDocumentAction;
use App\Enrollment\Registration\Models\Registration;
use App\Enrollment\Registration\Models\RegistrationDocument;
use App\Program\Internship\Models\Internship;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;

uses(LazilyRefreshDatabase::class);

test('uploads document for registration', function () {
    $document = Document::factory()->create();
    $internship = Internship::factory()->create(['required_document_ids' => [$document->id]]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);
    $file = UploadedFile::fake()->create('document.pdf', 100);

    app(UploadRegistrationDocumentAction::class)->execute($registration, [
        $document->id => $file,
    ]);

    $this->assertDatabaseHas('registration_documents', [
        'registration_id' => $registration->id,
        'document_id' => $document->id,
        'status' => 'pending',
    ]);
});

test('ignores documents not required by internship', function () {
    $required = Document::factory()->create();
    $unrequired = Document::factory()->create();
    $internship = Internship::factory()->create(['required_document_ids' => [$required->id]]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);
    $file = UploadedFile::fake()->create('doc.pdf', 100);

    app(UploadRegistrationDocumentAction::class)->execute($registration, [
        $unrequired->id => $file,
    ]);

    $this->assertDatabaseMissing('registration_documents', [
        'registration_id' => $registration->id,
        'document_id' => $unrequired->id,
    ]);
});

test('uploads multiple documents at once', function () {
    $doc1 = Document::factory()->create();
    $doc2 = Document::factory()->create();
    $internship = Internship::factory()->create(['required_document_ids' => [$doc1->id, $doc2->id]]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);
    $file1 = UploadedFile::fake()->create('doc1.pdf', 100);
    $file2 = UploadedFile::fake()->create('doc2.pdf', 100);

    app(UploadRegistrationDocumentAction::class)->execute($registration, [
        $doc1->id => $file1,
        $doc2->id => $file2,
    ]);

    expect(RegistrationDocument::where('registration_id', $registration->id)->count())->toBe(2);
});
