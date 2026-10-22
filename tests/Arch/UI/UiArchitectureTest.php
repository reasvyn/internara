<?php

declare(strict_types=1);

namespace Tests\Arch\UI;

use App\Modules\UI\Support\UiHelper;
use Illuminate\Support\Facades\File;
use Livewire\Component;
use Livewire\Form;
use ReflectionClass;

describe('8XMYS: UI Layer Architectural Invariants', function (): void {
    test('8XMYS-ARC-UI-001: Core module has zero Livewire components and zero views', function (): void {
        expect(is_dir(app_path('Modules/Core/Livewire')))->toBeFalse()
            ->and(is_dir(resource_path('views/core')))->toBeFalse();
    });

    test('8XMYS-ARC-UI-002: UiHelper is final and declares strict_types', function (): void {
        $reflection = new ReflectionClass(UiHelper::class);
        $content = File::get($reflection->getFileName());

        expect($reflection->isFinal())->toBeTrue()
            ->and($content)->toContain('declare(strict_types=1);');
    });

    test('8XMYS-ARC-UI-003: all classes in App\Modules\UI\Livewire are abstract or Livewire components', function (): void {
        $files = File::files(app_path('Modules/UI/Livewire'));

        foreach ($files as $file) {
            $className = 'App\\Modules\\UI\\Livewire\\'.$file->getFilenameWithoutExtension();
            if (! class_exists($className)) {
                continue;
            }

            $ref = new ReflectionClass($className);
            expect($ref->isSubclassOf(Component::class))->toBeTrue();
        }
    });

    test('8XMYS-ARC-UI-004: all items in App\Modules\UI\Livewire\Concerns are traits with strict_types', function (): void {
        $files = File::files(app_path('Modules/UI/Livewire/Concerns'));

        foreach ($files as $file) {
            $traitName = 'App\\Modules\\UI\\Livewire\\Concerns\\'.$file->getFilenameWithoutExtension();
            expect(trait_exists($traitName))->toBeTrue();

            $content = File::get($file->getPathname());
            expect($content)->toContain('declare(strict_types=1);');
        }
    });

    test('8XMYS-ARC-UI-005: resources/views/ui does not contain sharp corner classes', function (): void {
        $viewFiles = File::allFiles(resource_path('views/ui'));

        foreach ($viewFiles as $viewFile) {
            $content = File::get($viewFile->getPathname());
            expect($content)->not->toContain('rounded-none')
                ->and($content)->not->toContain('rounded-sm');
        }
    });

    test('8XMYS-ARC-UI-006: app/Modules/UI has no direct raw DB write operations', function (): void {
        $phpFiles = File::allFiles(app_path('Modules/UI'));

        foreach ($phpFiles as $phpFile) {
            $content = File::get($phpFile->getPathname());
            expect($content)->not->toContain('DB::raw("INSERT')
                ->and($content)->not->toContain('DB::raw("UPDATE')
                ->and($content)->not->toContain('DB::raw("DELETE');
        }
    });

    test('8XMYS-ARC-UI-007: all Livewire Form objects extend Livewire\Form and declare strict_types', function (): void {
        $formFiles = File::glob(app_path('Modules/*/Livewire/Forms/*Form.php'));
        $domainFormFiles = File::glob(app_path('Modules/*/Domain/*/Livewire/Forms/*Form.php'));
        $allForms = array_merge($formFiles, $domainFormFiles);

        expect(count($allForms))->toBeGreaterThanOrEqual(30);

        foreach ($allForms as $formPath) {
            $content = File::get($formPath);
            expect($content)->toContain('declare(strict_types=1);');

            preg_match('/namespace\s+([^;]+);/', $content, $ns);
            preg_match('/class\s+(\w+)/', $content, $cls);

            if (! empty($ns[1]) && ! empty($cls[1])) {
                $fqcn = $ns[1].'\\'.$cls[1];
                if (class_exists($fqcn)) {
                    $ref = new ReflectionClass($fqcn);
                    expect($ref->isSubclassOf(Form::class))->toBeTrue();
                }
            }
        }
    });
});
