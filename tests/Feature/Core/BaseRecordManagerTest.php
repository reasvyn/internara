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
    it('is abstract and extends Livewire Component', function () {
        $ref = new ReflectionClass(BaseRecordManager::class);

        expect($ref->isAbstract())->toBeTrue()
            ->and(BaseRecordManager::class)->toExtend(Component::class);
    });

    it('uses WithPagination trait', function () {
        expect(class_uses_recursive(BaseRecordManager::class))->toContain(WithPagination::class);
    });

    it('has default perPage of 10 with empty search and filters', function () {
        $manager = new TestRecordManager;

        expect($manager->perPage)->toBe(10)
            ->and($manager->search)->toBe('')
            ->and($manager->filters)->toBe([]);
    });

    it('resets filters', function () {
        $manager = new TestRecordManager;
        $manager->filters = ['status' => 'active'];

        $manager->resetFilters();

        expect($manager->filters)->toBe([]);
    });
});
