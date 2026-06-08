<?php

declare(strict_types=1);

use App\Core\Livewire\Concerns\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

beforeEach(function () {
    $this->component = new class extends Component
    {
        use WithSorting;

        public array $sortBy = ['column' => 'id', 'direction' => 'asc'];

        public function render(): string
        {
            return '';
        }

        public function callApplySorting(Builder $query): Builder
        {
            return $this->applySorting($query);
        }

        public function setSortBy(array $sortBy): void
        {
            $this->sortBy = $sortBy;
        }

        public function setSortableColumns(array $columns): void
        {
            $this->sortableColumns = $columns;
        }
    };
});

test('with sorting defaults to id ascending', function () {
    expect($this->component->sortBy)->toBe(['column' => 'id', 'direction' => 'asc']);
});

test('with sorting applies valid column and direction', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'desc')->andReturnSelf();

    $this->component->setSortBy(['column' => 'name', 'direction' => 'desc']);
    $result = $this->component->callApplySorting($query);

    expect($result)->toBe($query);
});

test('with sorting falls back to id for invalid column', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('id', 'asc')->andReturnSelf();

    $this->component->setSortBy(['column' => 'invalid_column', 'direction' => 'asc']);
    $this->component->callApplySorting($query);
});

test('with sorting falls back to asc for invalid direction', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('name', 'asc')->andReturnSelf();

    $this->component->setSortBy(['column' => 'name', 'direction' => 'invalid']);
    $this->component->callApplySorting($query);
});

test('with sorting uses default column when sort by is empty', function () {
    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('id', 'asc')->andReturnSelf();

    $this->component->setSortBy([]);
    $this->component->callApplySorting($query);
});

test('with sorting respects custom sortable columns', function () {
    $this->component->setSortableColumns(['email', 'status']);

    $query = Mockery::mock(Builder::class);
    $query->shouldReceive('orderBy')->once()->with('email', 'asc')->andReturnSelf();

    $this->component->setSortBy(['column' => 'email', 'direction' => 'asc']);
    $this->component->callApplySorting($query);
});
