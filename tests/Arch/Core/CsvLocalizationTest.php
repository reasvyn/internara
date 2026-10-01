<?php

declare(strict_types=1);

describe('O2KCR: CSV translation contracts', function (): void {
    test('O2KCR-FR-CSV-015: every CSV flow string resolves through translation', function (): void {
        $keys = [
            'common.actions.import_invalid',
            'common.actions.import_summary',
            'common.actions.no_records_selected',
            'department.import_invalid',
            'department.import_summary',
            'department.template_example_name',
            'department.template_example_description',
            'internship.import_invalid',
            'internship.import_summary',
            'internship.template_example_name',
            'internship.template_example_description',
        ];

        foreach (['en', 'id'] as $locale) {
            app()->setLocale($locale);

            foreach ($keys as $key) {
                expect(__($key))->not->toBe($key);
            }
        }

        app()->setLocale(config('app.locale'));
    });

    test('O2KCR-NFR-CSV-016: CSV translation keys are mirrored in both locales', function (): void {
        $pairs = [
            [base_path('lang/en/common.php'), base_path('lang/id/common.php'), "'import_summary'"],
            [base_path('lang/en/department.php'), base_path('lang/id/department.php'), "'import_summary'"],
            [base_path('lang/en/internship.php'), base_path('lang/id/internship.php'), "'import_summary'"],
        ];

        foreach ($pairs as [$enFile, $idFile, $key]) {
            expect(file_get_contents($enFile))->toContain($key);
            expect(file_get_contents($idFile))->toContain($key);
        }
    });
});
