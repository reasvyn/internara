<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Livewire;

use App\Core\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Pagination\LengthAwarePaginator;
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
