<?php

declare(strict_types=1);

namespace Tests\Unit\UI;

use App\Modules\UI\Livewire\Concerns\WithRecordSelection;

describe('8XMYS: WithRecordSelection Concern Unit Tests', function (): void {
    test('8XMYS-UT-UI-003: initial selection is empty and selected_count is zero', function (): void {
        $component = new class
        {
            use WithRecordSelection;
        };

        expect($component->selectedIds)->toBeEmpty()
            ->and($component->selected_count())->toBe(0);
    });

    test('8XMYS-UT-UI-004: selectAll stores provided IDs and computes count correctly', function (): void {
        $component = new class
        {
            use WithRecordSelection;
        };

        $component->selectAll([101, 102, 103]);

        expect($component->selectedIds)->toBe([101, 102, 103])
            ->and($component->selected_count())->toBe(3);
    });

    test('8XMYS-UT-UI-005: clearSelection resets selectedIds to empty array', function (): void {
        $component = new class
        {
            use WithRecordSelection;
        };

        $component->selectAll(['uuid-1', 'uuid-2']);
        expect($component->selected_count())->toBe(2);

        $component->clearSelection();

        expect($component->selectedIds)->toBeEmpty()
            ->and($component->selected_count())->toBe(0);
    });
});
