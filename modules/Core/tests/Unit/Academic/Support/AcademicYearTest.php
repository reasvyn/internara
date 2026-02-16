<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Academic\Support;

use Modules\Core\Academic\Support\AcademicYear;

describe('AcademicYear Support Utility', function () {
    test('it generates correct academic year for January-June period', function () {
        // Mock current year to 2026, month to February
        // We can use Carbon::setTestNow
        \Illuminate\Support\Carbon::setTestNow('2026-02-10');
        expect(AcademicYear::current())->toBe('2025/2026');
    });

    test('it generates correct academic year for July-December period', function () {
        \Illuminate\Support\Carbon::setTestNow('2026-08-15');
        expect(AcademicYear::current())->toBe('2026/2027');
    });
});
