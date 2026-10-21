<?php

declare(strict_types=1);

namespace Tests\Unit\UI;

use App\Modules\UI\Livewire\Concerns\WithSorting;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;

describe('8XMYS: WithSorting Concern Unit Tests', function (): void {
    test('8XMYS-UT-UI-006: applySorting orders by default column created_at in descending order', function (): void {
        $component = new class
        {
            use WithSorting;

            public function sort(Builder $query): Builder
            {
                return $this->applySorting($query);
            }
        };

        $builder = $component->sort(User::query());

        expect($builder->toSql())->toContain('order by')
            ->and($builder->toSql())->toContain('created_at')
            ->and($builder->toSql())->toContain('desc');
    });

    test('8XMYS-UT-UI-007: applySorting respects custom sort column and ascending direction', function (): void {
        $component = new class
        {
            use WithSorting;

            public function sort(Builder $query): Builder
            {
                return $this->applySorting($query);
            }
        };

        $component->sortBy = ['column' => 'name', 'direction' => 'asc'];
        $builder = $component->sort(User::query());

        expect($builder->toSql())->toContain('name')
            ->and($builder->toSql())->toContain('asc');
    });

    test('8XMYS-UT-UI-008: applySorting ignores unwhitelisted column and falls back safely', function (): void {
        $component = new class
        {
            use WithSorting;

            public function sort(Builder $query): Builder
            {
                return $this->applySorting($query);
            }
        };

        $component->sortBy = ['column' => 'dangerous_unwhitelisted_column', 'direction' => 'asc'];
        $builder = $component->sort(User::query());

        // Falls back to first allowed column ('id')
        expect($builder->toSql())->toContain('id')
            ->and($builder->toSql())->toContain('asc')
            ->and($builder->toSql())->not->toContain('dangerous_unwhitelisted_column');
    });
});
