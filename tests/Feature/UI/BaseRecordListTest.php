<?php

declare(strict_types=1);

namespace Tests\Feature\UI;

use App\Modules\UI\Livewire\BaseRecordList;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Livewire\Livewire;

uses(LazilyRefreshDatabase::class);

class ConcreteTestRecordList extends BaseRecordList
{
    protected array $with = ['roles'];

    protected function query(): Builder
    {
        return User::query();
    }

    protected function applySearch(Builder $query): Builder
    {
        return $query->where('name', 'like', "%{$this->search}%");
    }

    public function render(): string
    {
        return '<div>Concrete Record List</div>';
    }
}

describe('8XMYS: BaseRecordList Feature Tests', function (): void {
    test('8XMYS-FR-UI-036-A: rows returns LengthAwarePaginator and eagerly loads relationships', function (): void {
        User::factory()->count(12)->create();

        $test = Livewire::test(ConcreteTestRecordList::class);
        $rows = $test->instance()->rows();

        expect($rows)->toBeInstanceOf(LengthAwarePaginator::class)
            ->and($rows->perPage())->toBe(10)
            ->and($rows->total())->toBeGreaterThanOrEqual(12)
            ->and($rows->first()->relationLoaded('roles'))->toBeTrue();
    });

    test('8XMYS-FR-UI-036-B: rows falls back to 10 if perPage is not an allowed option', function (): void {
        $test = Livewire::test(ConcreteTestRecordList::class);
        $test->set('perPage', 555);

        $rows = $test->instance()->rows();

        expect($rows->perPage())->toBe(10)
            ->and($test->get('perPage'))->toBe(10);
    });

    test('8XMYS-FR-UI-036-C: updating search resets pagination page', function (): void {
        User::factory()->count(25)->create();

        $test = Livewire::test(ConcreteTestRecordList::class);
        $test->call('setPage', 2);
        expect($test->instance()->getPage())->toBe(2);

        $test->set('search', 'budi');
        expect($test->instance()->getPage())->toBe(1);
    });

    test('8XMYS-FR-UI-036-D: updating perPage resets pagination page', function (): void {
        User::factory()->count(25)->create();

        $test = Livewire::test(ConcreteTestRecordList::class);
        $test->call('setPage', 2);
        expect($test->instance()->getPage())->toBe(2);

        $test->set('perPage', 25);
        expect($test->instance()->getPage())->toBe(1);
    });

    test('8XMYS-FR-UI-036-E: search filtering narrows down rows result', function (): void {
        User::factory()->create(['name' => 'UniqueZetaName']);
        User::factory()->count(5)->create();

        $test = Livewire::test(ConcreteTestRecordList::class);
        $test->set('search', 'UniqueZetaName');

        $rows = $test->instance()->rows();

        expect($rows->total())->toBe(1)
            ->and($rows->first()->name)->toBe('UniqueZetaName');
    });
});
