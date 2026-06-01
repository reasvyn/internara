<?php

declare(strict_types=1);

use App\Domain\Core\Livewire\Concerns\WithSorting;
use Livewire\Component;

class TestSortingComponent extends Component
{
    use WithSorting;

    public function render() {}
}

describe('WithSorting', function () {
    it('defaults to id asc', function () {
        $component = new TestSortingComponent;

        expect($component->sortBy)->toBe(['column' => 'id', 'direction' => 'asc']);
    });
});
