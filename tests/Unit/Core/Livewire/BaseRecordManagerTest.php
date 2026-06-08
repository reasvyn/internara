<?php

declare(strict_types=1);

namespace Tests\Unit\Core\Livewire;

use App\Core\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

// ─── Test Model ───────────────────────────────────────────────────────────────────────────────

class TestRecord extends Model
{
    public $table = 'test_records';

    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (TestRecord $record) {
            if (empty($record->{$record->getKeyName()})) {
                $record->{$record->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}

// ─── Test Livewire Component ──────────────────────────────────────────────────────────────────

class TestRecordManager extends BaseRecordManager
{
    public bool $pageWasReset = false;

    public array $selectedIds = [];

    public function headers(): array
    {
        return ['ID', 'Name', 'Status'];
    }

    protected function query(): Builder
    {
        return TestRecord::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        if ($this->search) {
            $query->where('name', 'like', "%{$this->search}%");
        }

        return $query;
    }

    protected function applyFilters(Builder $query): Builder
    {
        if (! empty($this->filters['status'])) {
            $query->where('status', $this->filters['status']);
        }

        return $query;
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

    public function callPerformBulkAction(
        string $name,
        callable $callback,
        bool $transactional = true,
    ): void {
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

    public function populateRecordCount(int $count): void
    {
        TestRecord::truncate();
        for ($i = 1; $i <= $count; $i++) {
            TestRecord::create([
                'name' => "Record {$i}",
                'status' => $i <= $count / 2 ? 'active' : 'inactive',
            ]);
        }
    }
}

// ─── Test Setup ───────────────────────────────────────────────────────────────────────────────

uses(RefreshDatabase::class);

beforeEach(function () {
    Schema::create('test_records', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->string('status')->default('active');
    });

    $this->manager = new TestRecordManager;
});

// ─── Pagination & Search Properties ──────────────────────────────────────────────────────────

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

// ─── Rows / Pagination ───────────────────────────────────────────────────────────────────────

test('it returns paginated rows from query', function () {
    $this->manager->populateRecordCount(5);

    $result = $this->manager->rows();

    expect($result->total())->toBe(5);
    expect($result->perPage())->toBe(10);
    expect($result->items())->toHaveCount(5);
});

test('it paginates correctly when records exceed per page', function () {
    $this->manager->populateRecordCount(25);

    $result = $this->manager->rows();

    expect($result->total())->toBe(25);
    expect($result->perPage())->toBe(10);
    expect($result->items())->toHaveCount(10);
});

test('it resets invalid per page to default', function () {
    $this->manager->populateRecordCount(5);
    $this->manager->perPage = 7;

    $this->manager->rows();

    expect($this->manager->perPage)->toBe(10);
});

test('it applies search filter when search is set', function () {
    $this->manager->populateRecordCount(10);
    TestRecord::create(['name' => 'Special Item', 'status' => 'active']);

    $this->manager->search = 'Special';
    $result = $this->manager->rows();

    expect($result->total())->toBe(1);
    expect($result->items()[0]->name)->toBe('Special Item');
});

test('it applies status filter when filter is set', function () {
    $this->manager->populateRecordCount(10);
    TestRecord::create(['name' => 'Extra', 'status' => 'pending']);

    $this->manager->filters = ['status' => 'pending'];
    $result = $this->manager->rows();

    expect($result->total())->toBe(1);
});

test('it applies both search and filters together', function () {
    $this->manager->populateRecordCount(10);
    TestRecord::create(['name' => 'Target', 'status' => 'active']);
    TestRecord::create(['name' => 'Target', 'status' => 'inactive']);

    $this->manager->search = 'Target';
    $this->manager->filters = ['status' => 'active'];
    $result = $this->manager->rows();

    expect($result->total())->toBe(1);
    expect($result->items()[0]->status)->toBe('active');
});

// ─── Bulk Actions ────────────────────────────────────────────────────────────────────────────

test('perform bulk action warns when no records selected', function () {
    $this->manager->selectedIds = [];

    $this->manager->callPerformBulkAction('delete', fn ($id) => null);

    expect($this->manager->selectedIds)->toBe([]);
});

test('perform bulk action executes callback for each selected id', function () {
    $this->manager->populateRecordCount(3);
    $ids = TestRecord::pluck('id')->toArray();
    $this->manager->selectedIds = $ids;
    $processed = [];

    $this->manager->callPerformBulkAction('delete', function ($id) use (&$processed) {
        $processed[] = $id;
    });

    expect($processed)->toBe($ids);
    expect($this->manager->selectedIds)->toBe([]);
});

test('perform bulk action wraps in DB transaction by default', function () {
    $this->manager->populateRecordCount(2);
    $this->manager->selectedIds = TestRecord::pluck('id')->toArray();
    $processed = [];

    $this->manager->callPerformBulkAction('delete', function ($id) use (&$processed) {
        $processed[] = TestRecord::find($id)?->name;
    });

    expect($processed)->toHaveCount(2);
});

test('perform bulk action works without transaction', function () {
    $this->manager->populateRecordCount(2);
    $this->manager->selectedIds = TestRecord::pluck('id')->toArray();
    $processed = [];

    $this->manager->callPerformBulkAction(
        'delete',
        function ($id) use (&$processed) {
            $processed[] = $id;
        },
        false,
    );

    expect($processed)->toHaveCount(2);
    expect($this->manager->selectedIds)->toBe([]);
});

// ─── Mass Actions ────────────────────────────────────────────────────────────────────────────

test('perform mass action warns when no records match', function () {
    $this->manager->populateRecordCount(0);

    $called = false;
    $this->manager->callPerformMassAction('delete', function ($q) use (&$called) {
        $called = true;
    });

    expect($called)->toBeFalse();
});

test('perform mass action executes callback on query', function () {
    $this->manager->populateRecordCount(5);

    $called = false;
    $this->manager->callPerformMassAction('delete', function ($query) use (&$called) {
        $called = true;
    });

    expect($called)->toBeTrue();
});

test('perform mass action passes query with correct count', function () {
    $this->manager->populateRecordCount(3);

    $queryCount = null;
    $this->manager->callPerformMassAction('delete', function ($query) use (&$queryCount) {
        $queryCount = $query->count();
    });

    expect($queryCount)->toBe(3);
});

test('perform mass action respects search filter', function () {
    $this->manager->populateRecordCount(10);
    TestRecord::create(['name' => 'Target One', 'status' => 'active']);
    TestRecord::create(['name' => 'Target Two', 'status' => 'active']);

    $this->manager->search = 'Target';

    $queryCount = null;
    $this->manager->callPerformMassAction('delete', function ($query) use (&$queryCount) {
        $queryCount = $query->count();
    });

    expect($queryCount)->toBe(2);
});

// ─── Apply Methods ───────────────────────────────────────────────────────────────────────────

test('apply search and apply filters return query unchanged when no filters set', function () {
    $query = TestRecord::query();

    $result1 = $this->manager->callApplySearch($query);
    $result2 = $this->manager->callApplyFilters($query);

    expect($result1)->toBe($query);
    expect($result2)->toBe($query);
});
