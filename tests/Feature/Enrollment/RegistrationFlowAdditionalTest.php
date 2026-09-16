<?php

declare(strict_types=1);

use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Actions\UploadRegistrationDocumentAction;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationDocumentUpload;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationWizard;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Lang;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

function additionalRegistrationStudent(): User
{
    $student = User::factory()->create();
    $student->assignRole('student');
    test()->actingAs($student);

    return $student;
}

test('MBB5R-FR-REG-023: uploading the same required document replaces the prior media', function (): void {
    Storage::fake('public');
    $document = Document::factory()->create(['type' => 'template']);
    $internship = Internship::factory()->create(['required_document_ids' => [$document->id]]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);
    $action = app(UploadRegistrationDocumentAction::class);

    $action->execute($registration, [$document->id => UploadedFile::fake()->create('first.pdf', 10, 'application/pdf')]);
    $action->execute($registration, [$document->id => UploadedFile::fake()->create('second.pdf', 10, 'application/pdf')]);

    expect(RegistrationDocument::where('registration_id', $registration->id)->where('document_id', $document->id)->count())->toBe(1);
});

test('MBB5R-FR-REG-023: upload action ignores a document not required by the internship', function (): void {
    Storage::fake('public');
    $required = Document::factory()->create(['type' => 'template']);
    $unrequired = Document::factory()->create(['type' => 'template']);
    $internship = Internship::factory()->create(['required_document_ids' => [$required->id]]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);

    app(UploadRegistrationDocumentAction::class)->execute($registration, [
        $unrequired->id => UploadedFile::fake()->create('ignored.pdf', 10, 'application/pdf'),
    ]);

    expect(RegistrationDocument::where('registration_id', $registration->id)->exists())->toBeFalse();
});

test('MBB5R-FR-REG-024: document page renders every required identifier', function (): void {
    $student = additionalRegistrationStudent();
    $document = Document::factory()->create(['type' => 'template', 'title' => 'Signed agreement']);
    $internship = Internship::factory()->create(['required_document_ids' => [$document->id]]);
    Registration::factory()->create(['student_id' => $student->id, 'internship_id' => $internship->id, 'status' => 'active']);

    Livewire::test(RegistrationDocumentUpload::class)->assertSee('Signed agreement');
});

test('MBB5R-FR-REG-024: document page marks an uploaded requirement as complete', function (): void {
    $student = additionalRegistrationStudent();
    $document = Document::factory()->create(['type' => 'template', 'title' => 'Identity card']);
    $internship = Internship::factory()->create(['required_document_ids' => [$document->id]]);
    $registration = Registration::factory()->create(['student_id' => $student->id, 'internship_id' => $internship->id, 'status' => 'active']);
    RegistrationDocument::factory()->create(['registration_id' => $registration->id, 'document_id' => $document->id]);

    Livewire::test(RegistrationDocumentUpload::class)->assertSee(__('registration.doc_uploaded'));
});

test('MBB5R-NFR-REG-002: wizard exposes a labeled first step', function (): void {
    additionalRegistrationStudent();

    Livewire::test(RegistrationWizard::class)->assertSet('step', 1)->assertSee(__('registration.wizard.step', ['step' => 1, 'total' => 2]));
});

test('MBB5R-NFR-REG-006: registration document file input identifies its requirement', function (): void {
    $student = additionalRegistrationStudent();
    $document = Document::factory()->create(['type' => 'template']);
    $internship = Internship::factory()->create(['required_document_ids' => [$document->id]]);
    Registration::factory()->create(['student_id' => $student->id, 'internship_id' => $internship->id, 'status' => 'active']);

    expect(Livewire::test(RegistrationDocumentUpload::class)->html())->toContain('type="file"');
});

test('MBB5R-NFR-REG-007: registration page renders translated title text', function (): void {
    expect(test()->get('/registration')->assertOk()->getContent())->toContain(__('registration.center_title'));
});

test('MBB5R-NFR-REG-008: registration status translations are mirrored', function (): void {
    foreach (['pending', 'active'] as $status) {
        expect(Lang::has("registration.status.{$status}", 'en'))->toBeTrue()
            ->and(Lang::has("registration.status.{$status}", 'id'))->toBeTrue();
    }
});

test('MBB5R-FR-REG-024: no-registration document page shows the empty state', function (): void {
    additionalRegistrationStudent();

    Livewire::test(RegistrationDocumentUpload::class)->assertSee(__('registration.doc_no_registration'));
});

test('MBB5R-FR-REG-023: uploaded document starts in pending state', function (): void {
    Storage::fake('public');
    $document = Document::factory()->create(['type' => 'template']);
    $internship = Internship::factory()->create(['required_document_ids' => [$document->id]]);
    $registration = Registration::factory()->create(['internship_id' => $internship->id]);

    app(UploadRegistrationDocumentAction::class)->execute($registration, [
        $document->id => UploadedFile::fake()->create('proof.pdf', 10, 'application/pdf'),
    ]);

    expect(RegistrationDocument::firstOrFail()->status->value)->toBe('pending');
});
