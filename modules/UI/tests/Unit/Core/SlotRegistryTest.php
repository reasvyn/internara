<?php

declare(strict_types=1);

namespace Modules\UI\Tests\Unit\Core;

use Modules\UI\Core\SlotRegistry;

describe('UI Slot Registry (Modular Orchestration)', function () {
    test('it can register and retrieve components for a named slot', function () {
        $registry = new SlotRegistry;
        
        $registry->register('navbar.actions', 'ui::user-menu', ['order' => 10]);
        $registry->register('navbar.actions', 'ui::theme-toggle', ['order' => 5]);
        
        $slots = $registry->getSlotsFor('navbar.actions');
        
        expect($slots)->toHaveCount(2)
            ->and($slots[0]['view'])->toBe('ui::theme-toggle') // Verified sort order
            ->and($slots[1]['view'])->toBe('ui::user-menu');
    });

    test('it can check for existence of registered content in a slot', function () {
        $registry = new SlotRegistry;
        
        expect($registry->hasSlot('empty-slot'))->toBeFalse();
        
        $registry->register('active-slot', 'view-name');
        expect($registry->hasSlot('active-slot'))->toBeTrue();
    });
});
