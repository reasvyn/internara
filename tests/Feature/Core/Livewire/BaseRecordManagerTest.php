<?php

declare(strict_types=1);

use App\Domain\Core\Livewire\BaseRecordManager;
use App\Domain\Core\Models\ActivityLog;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

uses(RefreshDatabase::class);

class TestRecordManager extends BaseRecordManager
{
    public function headers(): array
    {
        return ['ID'];
    }

    protected function query(): Builder
    {
        return ActivityLog::query();
    }

    public function render()
    {
        return '<div>Test Component</div>';
    }
}

test('BaseRecordManager sanitizes invalid or dangerous perPage values', function () {
    $component = Livewire::test(TestRecordManager::class);

    // Set perPage to an invalid options value (DoS attempt or invalid value)
    $component->set('perPage', 1000000);
    expect($component->instance()->rows()->perPage())->toBe(10); // should fall back to 10

    $component->set('perPage', 0);
    expect($component->instance()->rows()->perPage())->toBe(10); // should fall back to 10

    $component->set('perPage', -5);
    expect($component->instance()->rows()->perPage())->toBe(10); // should fall back to 10
});

test('BaseRecordManager safely handles missing or malformed sortBy keys', function () {
    $component = Livewire::test(TestRecordManager::class);

    // Send malformed array to sortBy (e.g. missing column key) and call rows()
    $component->set('sortBy', ['direction' => 'desc']);
    $component->instance()->rows();

    // Send totally empty array
    $component->set('sortBy', []);
    $component->instance()->rows();

    expect(true)->toBeTrue();
});
