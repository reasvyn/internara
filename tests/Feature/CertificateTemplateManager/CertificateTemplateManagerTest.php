<?php

declare(strict_types=1);

use App\Livewire\Document\CertificateTemplateManager;
use App\Models\CertificateTemplate;
use App\Models\User;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;

beforeEach(function () {
    Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);

    $this->admin = User::factory()->create()->assignRole('admin');
    $this->actingAs($this->admin);
});

it('creates a certificate template', function () {
    Livewire::test(CertificateTemplateManager::class)
        ->call('create')
        ->set('formData.name', 'Sertifikat PKL Standar')
        ->set('formData.layout', 'portrait')
        ->set('formData.content_template', '<h1>Certificate for {student_name}</h1>')
        ->call('saveTemplate')
        ->assertHasNoErrors();

    expect(CertificateTemplate::where('name', 'Sertifikat PKL Standar')->exists())->toBeTrue();
});

it('lists certificate templates', function () {
    CertificateTemplate::create([
        'name' => 'Template A',
        'layout' => 'landscape',
        'content_template' => '<p>{student_name}</p>',
        'is_active' => true,
        'created_by' => $this->admin->id,
    ]);

    Livewire::test(CertificateTemplateManager::class)
        ->assertSuccessful()
        ->assertSee('Template A');
});
