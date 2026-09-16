<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

use App\Modules\Document\Domain\OfficialDocument\Actions\GenerateDocumentAction;
use App\Modules\Document\Models\Document;
use App\Modules\Enrollment\Domain\Registration\Actions\UploadRegistrationDocumentAction;
use App\Modules\Enrollment\Domain\Registration\Enums\RegistrationDocumentStatus;
use App\Modules\Enrollment\Domain\Registration\Livewire\RegistrationDocumentUpload;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Enrollment\Domain\Registration\Models\RegistrationDocument;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

describe('7H5D6: parent consent upload and checklist', function () {
    test('7H5D6-UC-OFFD-002: signed consent upload lands pending with the scan attached', function (): void {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create();
        $student->assignRole('student');
        $consent = Document::factory()->create(['title' => 'Parent Consent Form']);
        $internship = Internship::factory()->create(['required_document_ids' => [$consent->id]]);
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
        ]);

        $file = UploadedFile::fake()->create('consent.pdf', 120, 'application/pdf');
        app(UploadRegistrationDocumentAction::class)->execute($registration, [$consent->id => $file]);

        $record = RegistrationDocument::where('registration_id', $registration->id)
            ->where('document_id', $consent->id)
            ->firstOrFail();

        expect($record->status)->toBe(RegistrationDocumentStatus::PENDING)
            ->and($record->getFirstMedia('file'))->not->toBeNull()
            ->and($record->getFirstMedia('file')->getPathRelativeToRoot())->not->toBe('consent.pdf')
            ->and(Storage::disk('public')->exists($record->getFirstMedia('file')->getPathRelativeToRoot()))->toBeTrue();

        $this->assertDatabaseHas('activity_log', ['description' => 'registration_documents_uploaded']);
    });

    test('7H5D6-UC-OFFD-002: admin verification records the verifier and timestamp', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $record = RegistrationDocument::factory()->create(['status' => RegistrationDocumentStatus::PENDING->value]);

        expect($record->status->canTransitionTo(RegistrationDocumentStatus::VERIFIED))->toBeTrue();

        $record->update([
            'status' => RegistrationDocumentStatus::VERIFIED->value,
            'verified_by' => $admin->id,
            'verified_at' => now(),
            'admin_notes' => 'Legible and complete.',
        ]);

        $this->assertDatabaseHas('registration_documents', [
            'id' => $record->id,
            'status' => RegistrationDocumentStatus::VERIFIED->value,
            'verified_by' => $admin->id,
        ]);
        expect($record->fresh()->verified_at)->not->toBeNull()
            ->and($record->fresh()->status->isTerminal())->toBeTrue();
    });

    test('7H5D6-FR-OFFD-015: registration exposes per-document states with missing required papers detectable', function (): void {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create();
        $student->assignRole('student');
        $consent = Document::factory()->create(['title' => 'Parent Consent Form']);
        $acceptance = Document::factory()->create(['title' => 'Acceptance Letter']);
        $internship = Internship::factory()->create(['required_document_ids' => [$consent->id, $acceptance->id]]);
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
        ]);

        app(UploadRegistrationDocumentAction::class)->execute($registration, [
            $consent->id => UploadedFile::fake()->create('consent.pdf', 120, 'application/pdf'),
        ]);

        $states = $registration->fresh()->documents()->with('document')->get()
            ->mapWithKeys(fn (RegistrationDocument $row) => [$row->document_id => $row->status->value]);

        expect($states[$consent->id])->toBe(RegistrationDocumentStatus::PENDING->value);

        $recorded = $registration->fresh()->documents()->pluck('document_id')->all();
        $missing = array_values(array_diff($internship->required_document_ids, $recorded));

        expect($missing)->toBe([$acceptance->id]);
    });

    test('7H5D6-FR-OFFD-017: uploads outside the required set are ignored, never recorded', function (): void {
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $student = User::factory()->create();
        $student->assignRole('student');
        $consent = Document::factory()->create(['title' => 'Parent Consent Form']);
        $stray = Document::factory()->create(['title' => 'Unrelated Flyer']);
        $internship = Internship::factory()->create(['required_document_ids' => [$consent->id]]);
        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
        ]);

        app(UploadRegistrationDocumentAction::class)->execute($registration, [
            $consent->id => UploadedFile::fake()->create('consent.pdf', 120, 'application/pdf'),
            $stray->id => UploadedFile::fake()->create('flyer.pdf', 120, 'application/pdf'),
        ]);

        expect(RegistrationDocument::where('registration_id', $registration->id)->count())->toBe(1)
            ->and(RegistrationDocument::where('registration_id', $registration->id)->where('document_id', $stray->id)->exists())->toBeFalse();
    });

    test('7H5D6-FR-OFFD-024: consent upload rejects disallowed MIME types and oversize files', function (): void {
        Storage::fake('public');
        Storage::fake('local');

        $student = User::factory()->create();
        $student->assignRole('student');
        $consent = Document::factory()->create(['title' => 'Parent Consent Form']);
        $internship = Internship::factory()->create(['required_document_ids' => [$consent->id]]);
        $this->actingAs($student);

        $component = Livewire::test(RegistrationDocumentUpload::class);

        $registration = Registration::factory()->create([
            'student_id' => $student->id,
            'internship_id' => $internship->id,
        ]);

        $component->set('registration', $registration)
            ->set("uploads.{$consent->id}", UploadedFile::fake()->create('consent.txt', 50, 'text/plain'))
            ->call('upload')
            ->assertHasErrors(["uploads.{$consent->id}"]);

        expect(RegistrationDocument::where('registration_id', $registration->id)->exists())->toBeFalse();

        Livewire::test(RegistrationDocumentUpload::class)
            ->set('registration', $registration)
            ->set("uploads.{$consent->id}", UploadedFile::fake()->create('huge.pdf', 6000, 'application/pdf'))
            ->call('upload')
            ->assertHasErrors(["uploads.{$consent->id}"]);

        expect(RegistrationDocument::where('registration_id', $registration->id)->exists())->toBeFalse();
    });

    test('7H5D6-NFR-OFFD-005: generated letters never persist under publicly reachable paths', function (): void {
        Storage::fake('local');
        Storage::fake('public');
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $template = Document::factory()->create(['content' => '<p>Official {{ $target->name }}</p>']);
        $rendered = app(GenerateDocumentAction::class)
            ->execute($template, (object) ['name' => 'Indra Saputra']);

        expect(str_starts_with($rendered->file_path, 'generated-documents/'))->toBeTrue()
            ->and(Storage::disk('local')->exists($rendered->file_path))->toBeTrue()
            ->and(Storage::disk('public')->exists($rendered->file_path))->toBeFalse();
    });
});
