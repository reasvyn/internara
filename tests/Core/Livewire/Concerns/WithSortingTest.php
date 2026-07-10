<?php

declare(strict_types=1);

use App\Core\Livewire\Concerns\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class SortableModel extends Model
{
    protected $table = 'sortable';
}

beforeEach(function () {
    $this->component = new class extends Component
    {
        use WithSorting;

        public array $sortBy = ['column' => 'created_at', 'direction' => 'desc'];

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

test('with sorting defaults to created_at desc', function () {
    expect($this->component->sortBy)->toBe(['column' => 'created_at', 'direction' => 'desc']);
});

test('with sorting applies valid column and direction', function () {
    $query = SortableModel::query()->orderBy('name', 'desc');

    $this->component->setSortBy(['column' => 'name', 'direction' => 'desc']);
    $result = $this->component->callApplySorting($query);

    expect($result)->toBe($query);
});

test('with sorting falls back to created_at for invalid column', function () {
    $query = SortableModel::query();

    $this->component->setSortBy(['column' => 'invalid_column', 'direction' => 'desc']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'created_at',
        'direction' => 'desc',
    ]);
});

test('with sorting falls back to desc for invalid direction', function () {
    $query = SortableModel::query();

    $this->component->setSortBy(['column' => 'name', 'direction' => 'invalid']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'name',
        'direction' => 'desc',
    ]);
});

test('with sorting uses default column when sort by is empty', function () {
    $query = SortableModel::query();

    $this->component->setSortBy([]);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'created_at',
        'direction' => 'desc',
    ]);
});

test('with sorting respects custom sortable columns', function () {
    $this->component->setSortableColumns(['email', 'status']);

    $query = SortableModel::query();

    $this->component->setSortBy(['column' => 'email', 'direction' => 'asc']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'email',
        'direction' => 'asc',
    ]);
});

test('with sorting handles null column gracefully', function () {
    $query = SortableModel::query();

    $this->component->setSortBy(['column' => null, 'direction' => 'desc']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'created_at',
        'direction' => 'desc',
    ]);
});

test('with sorting handles null direction gracefully', function () {
    $query = SortableModel::query();

    $this->component->setSortBy(['column' => 'name', 'direction' => null]);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'name',
        'direction' => 'desc',
    ]);
});

test('with sorting without sortBy key falls back to defaults', function () {
    $query = SortableModel::query();

    $this->component->setSortBy(['columnx' => 'name']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'created_at',
        'direction' => 'desc',
    ]);
});

test('with sorting rejects column outside sortable columns', function () {
    $this->component->setSortableColumns(['id', 'created_at']);

    $query = SortableModel::query();

    $this->component->setSortBy(['column' => 'name', 'direction' => 'desc']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'created_at',
        'direction' => 'desc',
    ]);
});
