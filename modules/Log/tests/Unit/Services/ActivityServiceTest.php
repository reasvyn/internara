<?php

declare(strict_types=1);

namespace Modules\Log\Tests\Unit\Services;


use Modules\Log\Models\Activity;
use Modules\Log\Services\ActivityService;



test('it can query activities with filters', function () {
    Activity::create([
        'description' => 'Test Activity 1',
        'log_name' => 'default',
    ]);

    Activity::create([
        'description' => 'Test Activity 2',
        'log_name' => 'security',
    ]);

    $service = new ActivityService(new Activity);

    expect($service->query(['log_name' => 'security'])->count())->toBe(1);
});

test('it calculates engagement stats', function () {
    $service = new ActivityService(new Activity);

    Activity::create([
        'description' => 'Created registration',
        'log_name' => 'internship',
        'subject_type' => 'Modules\Internship\Models\InternshipRegistration',
        'subject_id' => 'reg-123',
    ]);

    $stats = $service->getEngagementStats(['reg-123']);

    expect($stats['activity_count'])->toBe(1);
});
