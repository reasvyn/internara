<?php

declare(strict_types=1);

use App\Livewire\Internship\RequirementManager;
use App\Models\Document;
use App\Models\Internship;
use App\Models\InternshipDocumentRequirement;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($this->admin);

    $this->internship = Internship::factory()->create();
    $this->document = Document::factory()->create(['name' => 'Surat Kesehatan']);
});

it('shows empty state when no requirements', function () {
    Livewire::test(RequirementManager::class, ['internshipId' => $this->internship->id])
        ->assertSuccessful()
        ->assertSee('No requirements configured yet');
});

it('lists existing requirements', function () {
    InternshipDocumentRequirement::factory()->create([
        'internship_id' => $this->internship->id,
        'document_id' => $this->document->id,
    ]);

    Livewire::test(RequirementManager::class, ['internshipId' => $this->internship->id])
        ->assertSuccessful()
        ->assertSee('Surat Kesehatan');
});

it('adds a new requirement', function () {
    Livewire::test(RequirementManager::class, ['internshipId' => $this->internship->id])
        ->call('add')
        ->set('formData.document_id', $this->document->id)
        ->set('formData.is_mandatory', true)
        ->call('save')
        ->assertHasNoErrors();

    expect(InternshipDocumentRequirement::where('internship_id', $this->internship->id)->count())->toBe(1);
});

it('prevents duplicate requirement', function () {
    InternshipDocumentRequirement::factory()->create([
        'internship_id' => $this->internship->id,
        'document_id' => $this->document->id,
    ]);

    Livewire::test(RequirementManager::class, ['internshipId' => $this->internship->id])
        ->call('add')
        ->set('formData.document_id', $this->document->id)
        ->call('save')
        ->assertHasNoErrors();
});

it('removes a requirement', function () {
    $requirement = InternshipDocumentRequirement::factory()->create([
        'internship_id' => $this->internship->id,
        'document_id' => $this->document->id,
    ]);

    Livewire::test(RequirementManager::class, ['internshipId' => $this->internship->id])
        ->call('remove', $requirement->id);

    expect(InternshipDocumentRequirement::find($requirement->id))->toBeNull();
});

it('edits an existing requirement', function () {
    $requirement = InternshipDocumentRequirement::factory()->create([
        'internship_id' => $this->internship->id,
        'document_id' => $this->document->id,
        'is_mandatory' => true,
    ]);

    $otherDoc = Document::factory()->create(['name' => 'Surat Pernyataan']);

    Livewire::test(RequirementManager::class, ['internshipId' => $this->internship->id])
        ->call('edit', $requirement->id)
        ->set('formData.document_id', $otherDoc->id)
        ->set('formData.is_mandatory', false)
        ->call('save')
        ->assertHasNoErrors();

    expect($requirement->fresh()->document_id)->toBe($otherDoc->id);
    expect($requirement->fresh()->is_mandatory)->toBeFalse();
});

it('shows available document templates', function () {
    Document::factory()->count(3)->create();

    Livewire::test(RequirementManager::class, ['internshipId' => $this->internship->id])
        ->call('add')
        ->assertSet('formData.document_id', '');
});

it('cascades delete when internship is deleted', function () {
    $requirement = InternshipDocumentRequirement::factory()->create([
        'internship_id' => $this->internship->id,
        'document_id' => $this->document->id,
    ]);

    $this->internship->delete();

    expect(InternshipDocumentRequirement::find($requirement->id))->toBeNull();
});

it('cascades delete when document is deleted', function () {
    $requirement = InternshipDocumentRequirement::factory()->create([
        'internship_id' => $this->internship->id,
        'document_id' => $this->document->id,
    ]);

    $this->document->delete();

    expect(InternshipDocumentRequirement::find($requirement->id))->toBeNull();
});
