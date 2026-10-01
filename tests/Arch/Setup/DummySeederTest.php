<?php

declare(strict_types=1);

describe('3UOZP: seeder entry contracts', function (): void {
    test('3UOZP-FR-SEED-001: DummySeeder is the single entry point for dummy data', function (): void {
        expect(file_exists(base_path('database/seeders/DummySeeder.php')))->toBeTrue();

        $seeder = file_get_contents(base_path('database/seeders/DummySeeder.php'));

        expect($seeder)->toContain('class DummySeeder');
    });

    test('3UOZP-FR-SEED-002: generation delegates to the Tests Support helper', function (): void {
        $seeder = file_get_contents(base_path('database/seeders/DummySeeder.php'));

        expect($seeder)->toContain('DummyData::make()->run()')
            ->and(file_exists(base_path('tests/Support/DummyData.php')))->toBeTrue();
    });

    test('3UOZP-FR-SEED-003: dummy seeder never runs as a provisioning side effect', function (): void {
        foreach (['database/seeders/DatabaseSeeder.php', 'database/seeders/SetupSeeder.php'] as $file) {
            expect(file_get_contents(base_path($file)))->not->toContain('DummySeeder');
        }
    });
});
