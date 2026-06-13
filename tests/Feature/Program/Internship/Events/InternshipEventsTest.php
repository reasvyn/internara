<?php

declare(strict_types=1);

use App\Academics\AcademicYear\Models\AcademicYear;
use App\Program\Internship\Actions\CreateInternshipAction;
use App\Program\Internship\Events\InternshipCreated;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Event;

uses(LazilyRefreshDatabase::class);

test('internship created event is dispatched via action', function () {
    Event::fake([InternshipCreated::class]);
    $year = AcademicYear::factory()->create();

    app(CreateInternshipAction::class)->execute([
        'name' => 'PKL 2025',
        'academic_year_id' => $year->id,
        'start_date' => '2025-07-01',
        'end_date' => '2025-12-31',
    ]);

    Event::assertDispatched(InternshipCreated::class);
});
