<?php

declare(strict_types=1);

use App\Domain\Core\Livewire\Concerns\WithSorting;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Component;

class TestWithSortingComponent extends Component
{
    use WithSorting;

    public function render() {}
}

describe('WithSorting', function () {
    it('has default sort configuration', function () {
        $component = new TestWithSortingComponent;

        expect($component->sortBy)->toBe(['column' => 'id', 'direction' => 'asc']);
    });

    it('applies sorting to query', function () {
        $component = new TestWithSortingComponent;
        $component->sortBy = ['column' => 'name', 'direction' => 'desc'];

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('orderBy')->with('name', 'desc')->andReturnSelf();

        $ref = new ReflectionMethod($component, 'applySorting');
        $ref->setAccessible(true);
        $result = $ref->invoke($component, $query);

        expect($result)->toBe($query);
    });

    it('falls back to id when column not in whitelist', function () {
        $component = new TestWithSortingComponent;
        $component->sortBy = ['column' => 'hacked_column', 'direction' => 'desc'];

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('orderBy')->with('id', 'desc')->andReturnSelf();

        $ref = new ReflectionMethod($component, 'applySorting');
        $ref->setAccessible(true);
        $result = $ref->invoke($component, $query);

        expect($result)->toBe($query);
    });

    it('falls back to asc when direction is invalid', function () {
        $component = new TestWithSortingComponent;
        $component->sortBy = ['column' => 'name', 'direction' => 'invalid'];

        $query = Mockery::mock(Builder::class);
        $query->shouldReceive('orderBy')->with('name', 'asc')->andReturnSelf();

        $ref = new ReflectionMethod($component, 'applySorting');
        $ref->setAccessible(true);
        $result = $ref->invoke($component, $query);

        expect($result)->toBe($query);
    });
});
