<?php

declare(strict_types=1);

use App\Livewire\Setup\SetupWizard;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

beforeEach(function () {
    // Pastikan status "belum terinstal" sebelum setiap tes
    if (File::exists(storage_path('app/.installed'))) {
        File::delete(storage_path('app/.installed'));
    }
});

test('it persists form data to session on update', function () {
    Livewire::test(SetupWizard::class)
        ->set('schoolName', 'Test School')
        ->set('adminEmail', 'admin@example.com');

    expect(session('setup.form_data.schoolName'))->toBe('Test School');
    expect(session('setup.form_data.adminEmail'))->toBe('admin@example.com');
});

test('it restores form data from session on mount', function () {
    session()->put('setup.form_data', [
        'schoolName' => 'Restored School',
        'adminEmail' => 'restored@example.com',
    ]);

    Livewire::test(SetupWizard::class)
        ->assertSet('schoolName', 'Restored School')
        ->assertSet('adminEmail', 'restored@example.com');
});
