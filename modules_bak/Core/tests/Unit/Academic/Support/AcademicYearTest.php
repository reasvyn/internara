<?php

declare(strict_types=1);

namespace Modules\Core\Tests\Unit\Academic\Support;

use Illuminate\Support\Carbon;
use Modules\Core\Academic\Support\AcademicYear;

describe('AcademicYear', function () {
    afterEach(function () {
        Carbon::setTestNow();
    });

    test('it generates correct academic year for January to June period', function () {
        Carbon::setTestNow('2026-02-10');
        expect(AcademicYear::current())->toBe('2025/2026');
    });

    test('it generates correct academic year for July to December period', function () {
        Carbon::setTestNow('2026-08-15');
        expect(AcademicYear::current())->toBe('2026/2027');
    });

    test('it generates correct academic year for boundary month June', function () {
        Carbon::setTestNow('2026-06-30');
        expect(AcademicYear::current())->toBe('2025/2026');
    });

    test('it generates correct academic year for boundary month July', function () {
        Carbon::setTestNow('2026-07-01');
        expect(AcademicYear::current())->toBe('2026/2027');
    });

    test('it handles leap year correctly', function () {
        Carbon::setTestNow('2024-03-15');
        expect(AcademicYear::current())->toBe('2023/2024');
    });

    test('it generates correct academic year format', function () {
        expect(AcademicYear::current())->toMatch('/^\d{4}\/\d{4}$/');
    });
});
