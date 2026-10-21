<?php

declare(strict_types=1);

namespace Tests\Unit\UI;

use App\Modules\UI\Support\UiHelper;

describe('8XMYS: UI Helper Unit Tests', function (): void {
    test('8XMYS-UT-UI-001: semanticColors returns expected semantic color list', function (): void {
        $colors = UiHelper::semanticColors();

        expect($colors)->toBeArray()
            ->and($colors)->toContain('primary')
            ->and($colors)->toContain('secondary')
            ->and($colors)->toContain('accent')
            ->and($colors)->toContain('neutral')
            ->and($colors)->toContain('base-100')
            ->and($colors)->toContain('base-200')
            ->and($colors)->toContain('base-300')
            ->and($colors)->toContain('info')
            ->and($colors)->toContain('success')
            ->and($colors)->toContain('warning')
            ->and($colors)->toContain('error');
    });

    test('8XMYS-UT-UI-002: isUiView correctly identifies views with ui:: prefix', function (): void {
        expect(UiHelper::isUiView('ui::components.button'))->toBeTrue()
            ->and(UiHelper::isUiView('ui::layouts.app'))->toBeTrue()
            ->and(UiHelper::isUiView('ui::widgets.stat-card'))->toBeTrue()
            ->and(UiHelper::isUiView('core::about'))->toBeFalse()
            ->and(UiHelper::isUiView('user::dashboard'))->toBeFalse()
            ->and(UiHelper::isUiView('layouts.app'))->toBeFalse();
    });
});
