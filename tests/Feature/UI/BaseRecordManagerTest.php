<?php

declare(strict_types=1);

namespace Tests\Feature\UI;

use App\Modules\UI\Livewire\BaseRecordManager;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

class ConcreteTestRecordManager extends BaseRecordManager
{
    public array $processedBulkIds = [];

    public int $massQueryCount = 0;

    public function headers(): array
    {
        return [
            ['index' => 'id', 'label' => 'ID', 'sortable' => true],
            ['index' => 'name', 'label' => 'Name', 'sortable' => true],
            ['index' => 'email', 'label' => 'Email', 'sortable' => true],
            ['index' => 'non_column_virtual', 'label' => 'Virtual', 'sortable' => true],
            ['index' => 'actions', 'label' => 'Actions', 'sortable' => false],
        ];
    }

    protected function query(): Builder
    {
        return User::query();
    }

    public function triggerBulkAction(): void
    {
        $this->performBulkAction('export', function ($id): void {
            $this->processedBulkIds[] = $id;
        });
    }

    public function triggerMassAction(): void
    {
        $this->performMassAction('archive', function ($query): void {
            $this->massQueryCount = $query->count();
        });
    }

    public function getSortableColumnsPublic(): array
    {
        return $this->resolveSortableColumns($this->query());
    }

    public function render(): string
    {
        return '<div>Concrete Record Manager</div>';
    }
}

describe('8XMYS: BaseRecordManager Feature Tests', function (): void {
    test('8XMYS-FR-UI-020-A: rows returns LengthAwarePaginator and respects perPage', function (): void {
        User::factory()->count(15)->create();

        $test = Livewire::test(ConcreteTestRecordManager::class);
        $rows = $test->instance()->rows();

        expect($rows)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($rows->perPage())->toBe(10)
            ->and($rows->total())->toBeGreaterThanOrEqual(15);
    });

    test('8XMYS-FR-UI-020-B: rows falls back to 10 if perPage is not an allowed option', function (): void {
        $test = Livewire::test(ConcreteTestRecordManager::class);
        $test->set('perPage', 999);

        $rows = $test->instance()->rows();

        expect($rows->perPage())->toBe(10)
            ->and($test->get('perPage'))->toBe(10);
    });

    test('8XMYS-FR-UI-020-C: updating search, filters, or perPage resets pagination page', function (): void {
        User::factory()->count(25)->create();

        $test = Livewire::test(ConcreteTestRecordManager::class);
        $test->call('setPage', 2);
        expect($test->instance()->getPage())->toBe(2);

        $test->set('search', 'alice');
        expect($test->instance()->getPage())->toBe(1);

        $test->call('setPage', 2);
        $test->set('filters', ['status' => 'active']);
        expect($test->instance()->getPage())->toBe(1);

        $test->call('setPage', 2);
        $test->set('perPage', 25);
        expect($test->instance()->getPage())->toBe(1);
    });

    test('8XMYS-FR-UI-020-D: resetFilters clears filter array and resets page', function (): void {
        $test = Livewire::test(ConcreteTestRecordManager::class);
        $test->set('filters', ['role' => 'student', 'status' => 'active']);
        $test->call('setPage', 3);

        $test->call('resetFilters');

        expect($test->get('filters'))->toBeEmpty()
            ->and($test->instance()->getPage())->toBe(1);
    });

    test('8XMYS-FR-UI-020-E: resolveSortableColumns filters out virtual/non-table columns and unsortable headers', function (): void {
        $test = Livewire::test(ConcreteTestRecordManager::class);
        $columns = $test->instance()->getSortableColumnsPublic();

        expect($columns)->toContain('id')
            ->and($columns)->toContain('name')
            ->and($columns)->toContain('email')
            ->and($columns)->not->toContain('non_column_virtual')
            ->and($columns)->not->toContain('actions');
    });

    test('8XMYS-FR-UI-020-F: performBulkAction warns when no records are selected', function (): void {
        $test = Livewire::test(ConcreteTestRecordManager::class);

        $test->call('triggerBulkAction');

        expect($test->instance()->processedBulkIds)->toBeEmpty();
    });

    test('8XMYS-FR-UI-020-G: performBulkAction executes callback for selected IDs and clears selection', function (): void {
        $users = User::factory()->count(3)->create();
        $ids = $users->pluck('id')->all();

        $test = Livewire::test(ConcreteTestRecordManager::class);
        $test->set('selectedIds', $ids);

        $test->call('triggerBulkAction');

        expect($test->instance()->processedBulkIds)->toBe($ids)
            ->and($test->get('selectedIds'))->toBeEmpty();
    });

    test('8XMYS-FR-UI-020-H: performMassAction executes callback on query and clears selection', function (): void {
        User::factory()->count(4)->create();

        $test = Livewire::test(ConcreteTestRecordManager::class);
        $test->set('selectedIds', ['dummy-id']);

        $test->call('triggerMassAction');

        expect($test->instance()->massQueryCount)->toBeGreaterThanOrEqual(4)
            ->and($test->get('selectedIds'))->toBeEmpty();
    });
});
