<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;

describe('Infrastructure: UI Accessibility (WCAG 2.1 AA)', function () {
    test('base layout must contain language and skip-link attributes', function () {
        $path = base_path('modules/UI/resources/views/components/layouts/base.blade.php');
        $content = File::get($path);
        
        expect($content)->toContain('<html lang=')
            ->and($content)->toContain('sr-only')
            ->and($content)->toContain('{{ __(\'ui::common.skip_to_content\') }}');
    });

    test('dashboard layout must contain main landmark and aria-labels', function () {
        $path = base_path('modules/UI/resources/views/components/layouts/dashboard.blade.php');
        $content = File::get($path);
        
        expect($content)->toContain('<main id="main-content"')
            ->and($content)->toContain('aria-label=')
            ->and($content)->toContain('<footer');
    });

    test('form components must support labels for accessibility', function () {
        $path = base_path('modules/UI/resources/views/components/input.blade.php');
        $content = File::get($path);
        
        expect($content)->toContain('label');
    });
});
