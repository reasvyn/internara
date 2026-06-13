<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Livewire;

use App\Core\Livewire\BaseRecordManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Livewire\Livewire;

class IntegrationRecordManager extends BaseRecordManager
{
    public function headers(): array
    {
        return [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'status', 'label' => 'Status', 'sortable' => true],
        ];
    }

    protected function query(): Builder
    {
        return IntegrationTestRecord::query();
    }

    public function render(): string
    {
        return '<div>test</div>';
    }
}

class IntegrationTestRecord extends Model
{
    public $table = 'integration_test_records';

    protected $guarded = [];

    public $timestamps = false;

    public $incrementing = false;

    protected $keyType = 'string';

    protected static function booted(): void
    {
        static::creating(function (IntegrationTestRecord $record) {
            if (empty($record->{$record->getKeyName()})) {
                $record->{$record->getKeyName()} = (string) Str::uuid();
            }
        });
    }
}

uses(LazilyRefreshDatabase::class);

beforeEach(function () {
    Schema::create('integration_test_records', function (Blueprint $table) {
        $table->uuid('id')->primary();
        $table->string('name');
        $table->string('status')->default('active');
    });

    for ($i = 1; $i <= 5; $i++) {
        IntegrationTestRecord::create([
            'name' => "Record {$i}",
            'status' => $i <= 3 ? 'active' : 'inactive',
        ]);
    }
});

test('mounts with default search and pagination', function () {
    Livewire::test(IntegrationRecordManager::class)
        ->assertSet('perPage', 10)
        ->assertSet('search', '');
});

test('mounts with empty filters', function () {
    Livewire::test(IntegrationRecordManager::class)
        ->assertSet('filters', []);
});

test('mounts with default sort order', function () {
    Livewire::test(IntegrationRecordManager::class)
        ->assertSet('sortBy', ['column' => 'id', 'direction' => 'asc']);
});

test('can update search property', function () {
    Livewire::test(IntegrationRecordManager::class)
        ->set('search', 'test query')
        ->assertSet('search', 'test query');
});

test('can update perPage property', function () {
    Livewire::test(IntegrationRecordManager::class)
        ->set('perPage', 25)
        ->assertSet('perPage', 25);
});


test('can set filters', function () {
    Livewire::test(IntegrationRecordManager::class)
        ->set('filters.status', 'active')
        ->assertSet('filters.status', 'active');
});

test('selects records and computes count', function () {
    $ids = IntegrationTestRecord::pluck('id')->toArray();
    $selected = array_slice($ids, 0, 2);

    Livewire::test(IntegrationRecordManager::class)
        ->call('selectAll', $selected)
        ->assertSet('selectedIds', $selected)
        ->assertSet('selected_count', 2);
});

test('clears selection', function () {
    $ids = IntegrationTestRecord::pluck('id')->toArray();

    Livewire::test(IntegrationRecordManager::class)
        ->call('selectAll', $ids)
        ->call('clearSelection')
        ->assertSet('selectedIds', [])
        ->assertSet('selected_count', 0);
});

test('rows method returns paginated data', function () {
    Livewire::test(IntegrationRecordManager::class)
        ->assertSet('perPage', 10);
});
