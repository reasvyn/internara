<?php

declare(strict_types=1);

use Livewire\Livewire;
use Modules\Admin\Livewire\Dashboard;
use Modules\User\Models\User;

test('admin dashboard renders correctly', function () {
    $admin = User::factory()->create()->assignRole('admin');

    // Mock analytics aggregator
    $analytics = mock(\Modules\Core\Services\Contracts\AnalyticsAggregator::class);
    $analytics->shouldReceive('getInstitutionalSummary')->andReturn([
        'total_interns' => 10,
        'active_partners' => 5,
        'placement_rate' => 100,
    ]);
    $analytics->shouldReceive('getAtRiskStudents')->andReturn([]);
    app()->instance(\Modules\Core\Services\Contracts\AnalyticsAggregator::class, $analytics);

    Livewire::actingAs($admin)
        ->test(Dashboard::class)
        ->assertSee(__('admin::ui.dashboard.title'))
        ->assertSee('10');
});
