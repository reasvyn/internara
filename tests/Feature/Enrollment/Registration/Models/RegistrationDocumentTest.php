<?php

declare(strict_types=1);

use App\Document\Models\Document;
use App\Enrollment\Registration\Models\Registration;
use App\Enrollment\Registration\Models\RegistrationDocument;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

test('registration document has fillable attributes', function () {
    $doc = new RegistrationDocument;

    expect($doc->getFillable())->toContain('registration_id', 'document_id', 'status', 'admin_notes', 'verified_by', 'verified_at');
});

test('registration document belongs to registration', function () {
    $registration = Registration::factory()->create();
    $document = Document::factory()->create();
    $regDoc = RegistrationDocument::factory()->create([
        'registration_id' => $registration->id,
        'document_id' => $document->id,
    ]);

    expect($regDoc->registration)->toBeInstanceOf(Registration::class);
    expect($regDoc->registration->id)->toBe($registration->id);
});

test('registration document belongs to document', function () {
    $document = Document::factory()->create();
    $regDoc = RegistrationDocument::factory()->create(['document_id' => $document->id]);

    expect($regDoc->document)->toBeInstanceOf(Document::class);
    expect($regDoc->document->id)->toBe($document->id);
});

test('registration document has default pending status', function () {
    $regDoc = RegistrationDocument::factory()->create();

    expect($regDoc->status->value)->toBe('pending');
});

test('registration document has unique reg_doc constraint', function () {
    $registration = Registration::factory()->create();
    $document = Document::factory()->create();
    RegistrationDocument::factory()->create([
        'registration_id' => $registration->id,
        'document_id' => $document->id,
    ]);

    expect(fn () => RegistrationDocument::factory()->create([
        'registration_id' => $registration->id,
        'document_id' => $document->id,
    ]))->toThrow(Exception::class);
});
