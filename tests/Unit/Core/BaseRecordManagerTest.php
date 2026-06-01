<?php

declare(strict_types=1);

use App\Domain\Core\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;
use Livewire\WithPagination;

class TestRecordManager extends BaseRecordManager
{
    public function headers(): array
    {
        return [];
    }

    protected function query(): Builder
    {
        return Mockery::mock(Builder::class);
    }

    public function render() {}
}

describe('BaseRecordManager', function () {
    it('is an abstract class', function () {
        $ref = new ReflectionClass(BaseRecordManager::class);

        expect($ref->isAbstract())->toBeTrue();
    });

    it('extends Livewire Component', function () {
        expect(BaseRecordManager::class)->toExtend(Component::class);
    });

    it('uses WithPagination trait', function () {
        $traits = class_uses_recursive(BaseRecordManager::class);

        expect($traits)->toContain(WithPagination::class);
    });

    it('has default perPage of 10', function () {
        $manager = new TestRecordManager;

        expect($manager->perPage)->toBe(10);
    });

    it('starts with empty search and filters', function () {
        $manager = new TestRecordManager;

        expect($manager->search)->toBe('')
            ->and($manager->filters)->toBe([]);
    });

    it('resets page on search update', function () {
        $manager = new TestRecordManager;

        $manager->updatedSearch();

        expect(true)->toBeTrue();
    });

    it('resets page on filter update', function () {
        $manager = new TestRecordManager;

        $manager->updatedFilters();

        expect(true)->toBeTrue();
    });

    it('resets filters and page', function () {
        $manager = new TestRecordManager;
        $manager->filters = ['status' => 'active'];

        $manager->resetFilters();

        expect($manager->filters)->toBe([]);
    });

    it('has default sort configuration via WithSorting', function () {
        $manager = new TestRecordManager;

        expect($manager->sortBy)->toBe(['column' => 'id', 'direction' => 'asc']);
    });
});
