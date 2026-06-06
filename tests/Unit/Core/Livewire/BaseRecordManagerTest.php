<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Livewire;

use App\Core\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Mockery;

class MockRecordManager extends BaseRecordManager
{
    public bool $pageWasReset = false;

    public array $selectedIds = [];

    private ?Builder $mockBuilder = null;

    public function headers(): array
    {
        return ['ID'];
    }

    public function setMockBuilder(Builder $builder): void
    {
        $this->mockBuilder = $builder;
    }

    protected function query(): Builder
    {
        return $this->mockBuilder ?? Mockery::mock(Builder::class);
    }

    public function resetPage($pageName = 'page'): void
    {
        $this->pageWasReset = true;
    }

    public function clearSelection(): void
    {
        $this->selectedIds = [];
    }

    public function callPerPageOptions(): array
    {
        return $this->perPageOptions();
    }

    public function callPerformBulkAction(string $name, callable $callback, bool $transactional = true): void
    {
        $this->performBulkAction($name, $callback, $transactional);
    }

    public function callPerformMassAction(string $name, callable $callback): void
    {
        $this->performMassAction($name, $callback);
    }

    public function callApplySearch(Builder $query): Builder
    {
        return $this->applySearch($query);
    }

    public function callApplyFilters(Builder $query): Builder
    {
        return $this->applyFilters($query);
    }

    public function setWith(array $relations): void
    {
        $this->with = $relations;
    }
}

beforeEach(function () {
    $this->manager = new MockRecordManager;
});

test('it resets pagination page when search is updated', function () {
    $this->manager->search = 'test';
    $this->manager->updatedSearch();

    expect($this->manager->pageWasReset)->toBeTrue();
});

test('it resets pagination page when filters are updated', function () {
    $this->manager->filters = ['status' => 'active'];
    $this->manager->updatedFilters();

    expect($this->manager->pageWasReset)->toBeTrue();
});

test('it resets pagination page when perPage is updated', function () {
    $this->manager->perPage = 25;
    $this->manager->updatedPerPage();

    expect($this->manager->pageWasReset)->toBeTrue();
});

test('it resets filters and page on resetFilters', function () {
    $this->manager->filters = ['status' => 'active'];
    $this->manager->resetFilters();

    expect($this->manager->filters)->toBeEmpty();
    expect($this->manager->pageWasReset)->toBeTrue();
});

test('it returns per page options', function () {
    $options = $this->manager->callPerPageOptions();

    expect($options)->toBe([10, 25, 50, 100]);
});

test('it returns paginated rows from query', function () {
    $paginator = Mockery::mock(LengthAwarePaginator::class);
    $builder = Mockery::mock(Builder::class);

    $manager = Mockery::mock(MockRecordManager::class)->makePartial();
    $manager->search = 'test';
    $manager->shouldAllowMockingProtectedMethods();
    $manager->shouldReceive('query')->once()->andReturn($builder);
    $manager->shouldReceive('applySearch')->once()->with($builder)->andReturn($builder);
    $manager->shouldReceive('applyFilters')->once()->with($builder)->andReturn($builder);
    $manager->shouldReceive('applySorting')->once()->with($builder)->andReturn($builder);

    $builder->shouldReceive('paginate')
        ->once()
        ->with(10)
        ->andReturn($paginator);

    $result = $manager->rows();

    expect($result)->toBe($paginator);
});

test('it resets invalid per page to default', function () {
    $paginator = Mockery::mock(LengthAwarePaginator::class);
    $builder = Mockery::mock(Builder::class);

    $manager = Mockery::mock(MockRecordManager::class)->makePartial();
    $manager->perPage = 7;
    $manager->search = 'test';
    $manager->shouldAllowMockingProtectedMethods();
    $manager->shouldReceive('query')->once()->andReturn($builder);
    $manager->shouldReceive('applySearch')->once()->with($builder)->andReturn($builder);
    $manager->shouldReceive('applyFilters')->once()->with($builder)->andReturn($builder);
    $manager->shouldReceive('applySorting')->once()->with($builder)->andReturn($builder);

    $builder->shouldReceive('paginate')
        ->once()
        ->with(10)
        ->andReturn($paginator);

    $manager->rows();

    expect($manager->perPage)->toBe(10);
});

test('it loads eager relations in rows when with is set', function () {
    $paginator = Mockery::mock(LengthAwarePaginator::class);
    $builder = Mockery::mock(Builder::class);

    $manager = Mockery::mock(MockRecordManager::class)->makePartial();
    $manager->setWith(['relation1', 'relation2']);
    $manager->shouldAllowMockingProtectedMethods();
    $manager->shouldReceive('query')->once()->andReturn($builder);
    $manager->shouldReceive('applySearch')->never();
    $manager->shouldReceive('applyFilters')->once()->with($builder)->andReturn($builder);
    $manager->shouldReceive('applySorting')->once()->with($builder)->andReturn($builder);

    $builder->shouldReceive('with')
        ->once()
        ->with(['relation1', 'relation2'])
        ->andReturnSelf();

    $builder->shouldReceive('paginate')
        ->once()
        ->with(10)
        ->andReturn($paginator);

    $manager->rows();
});

test('perform bulk action warns when no records selected', function () {
    $this->manager->selectedIds = [];

    $this->manager->callPerformBulkAction('delete', fn ($id) => null);

    expect($this->manager->selectedIds)->toBe([]);
});

test('perform bulk action executes callback for each selected id', function () {
    $this->manager->selectedIds = [1, 2, 3];
    $processed = [];

    DB::shouldReceive('transaction')
        ->once()
        ->with(Mockery::on(fn ($callback) => true))
        ->andReturnUsing(fn ($callback) => $callback());

    $this->manager->callPerformBulkAction('delete', function ($id) use (&$processed) {
        $processed[] = $id;
    });

    expect($processed)->toBe([1, 2, 3]);
    expect($this->manager->selectedIds)->toBe([]);
});

test('perform bulk action works without transaction', function () {
    $this->manager->selectedIds = [1, 2];
    $processed = [];

    $this->manager->callPerformBulkAction('delete', function ($id) use (&$processed) {
        $processed[] = $id;
    }, false);

    expect($processed)->toBe([1, 2]);
});

test('perform mass action warns when no records match', function () {
    $builder = Mockery::mock(Builder::class);
    $this->manager->setMockBuilder($builder);

    $builder->shouldReceive('count')->once()->andReturn(0);

    $this->manager->callPerformMassAction('delete', fn ($q) => null);
});

test('perform mass action executes callback on query', function () {
    $builder = Mockery::mock(Builder::class);
    $this->manager->setMockBuilder($builder);

    $builder->shouldReceive('count')->once()->andReturn(5);

    $called = false;
    $this->manager->callPerformMassAction('delete', function ($query) use (&$called) {
        $called = true;
    });

    expect($called)->toBeTrue();
});

test('perform mass action loads with relations on query', function () {
    $builder = Mockery::mock(Builder::class);
    $this->manager->setMockBuilder($builder);
    $this->manager->setWith(['relation']);

    $builder->shouldReceive('with')
        ->once()
        ->with(['relation'])
        ->andReturnSelf();
    $builder->shouldReceive('count')->once()->andReturn(5);

    $this->manager->callPerformMassAction('delete', fn ($q) => null);
});

test('apply search and apply filters return query unchanged by default', function () {
    $builder = Mockery::mock(Builder::class);

    $result1 = $this->manager->callApplySearch($builder);
    $result2 = $this->manager->callApplyFilters($builder);

    expect($result1)->toBe($builder);
    expect($result2)->toBe($builder);
});
