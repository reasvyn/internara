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
    $query = SortableModel::query()->orderBy('name', 'desc');

    $this->component->setSortBy(['column' => 'name', 'direction' => 'desc']);
    $result = $this->component->callApplySorting($query);

    expect($result)->toBe($query);
});

test('with sorting falls back to id for invalid column', function () {
    $query = SortableModel::query();

    $this->component->setSortBy(['column' => 'invalid_column', 'direction' => 'asc']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'id',
        'direction' => 'asc',
    ]);
});

test('with sorting falls back to asc for invalid direction', function () {
    $query = SortableModel::query();

    $this->component->setSortBy(['column' => 'name', 'direction' => 'invalid']);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'name',
        'direction' => 'asc',
    ]);
});

test('with sorting uses default column when sort by is empty', function () {
    $query = SortableModel::query();

    $this->component->setSortBy([]);
    $result = $this->component->callApplySorting($query);

    expect($result->getQuery()->orders)->toHaveCount(1);
    expect($result->getQuery()->orders[0])->toMatchArray([
        'column' => 'id',
        'direction' => 'asc',
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
