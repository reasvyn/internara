<?php

declare(strict_types=1);

use App\Modules\Core\Support\CsvHandler;
use App\Modules\Program\Domain\Internship\Livewire\InternshipManager;
use App\Modules\Program\Domain\Internship\Models\Internship;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

describe('O2KCR: internship CSV filenames', function (): void {
    test('O2KCR-FR-CSV-035: internship export filenames follow the trio', function (): void {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        $this->actingAs($admin);

        $internship = Internship::factory()->create();

        expect((new InternshipManager)->export(new CsvHandler)->headers->get('Content-Disposition'))
            ->toBe('attachment; filename="internships.csv"');

        $manager = new InternshipManager;
        $manager->selectedIds = [$internship->id];

        expect($manager->exportSelected(new CsvHandler)->headers->get('Content-Disposition'))
            ->toBe('attachment; filename="internships-selected.csv"');

        expect((new InternshipManager)->downloadTemplate(new CsvHandler)->headers->get('Content-Disposition'))
            ->toBe('attachment; filename="internships-template.csv"');
    });
});
