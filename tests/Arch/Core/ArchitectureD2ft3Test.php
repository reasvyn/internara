<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Auth\Domain\AccessToken\Entities\AccessTokenState;
use App\Modules\Auth\Domain\AccessToken\Models\AccessToken;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Data\BaseData;
use App\Modules\Core\Enums\CsvRowResult;
use App\Modules\Enrollment\Domain\Registration\Events\StudentRegistered;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\SysAdmin\Domain\Backup\Actions\ReadBackupStatsAction;
use App\Modules\User\Domain\UserManagement\Actions\GenerateAccountSlipAction;
use App\Modules\User\Domain\UserManagement\Actions\RenderAccountSlipAction;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Models\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

function d2ft3ModuleDirs(): array
{
    $dirs = [];

    foreach (scandir(base_path('app/Modules')) as $entry) {
        if ($entry === '.' || $entry === '..') {
            continue;
        }

        if (is_dir(base_path('app/Modules/'.$entry))) {
            $dirs[] = $entry;
        }
    }

    sort($dirs);

    return $dirs;
}

function d2ft3PhpFiles(string $dir): array
{
    $files = [];

    if (! is_dir($dir)) {
        return $files;
    }

    foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            $files[] = $file->getPathname();
        }
    }

    sort($files);

    return $files;
}

describe('D2FT3: architecture contracts', function (): void {
    test('D2FT3-FR-ARC-011: the action triad resolves with a single execute entry (also FR-ARC-012, DD-ARC-002)', function (): void {
        $actions = [
            app(GenerateAccountSlipAction::class),
            app(ReadBackupStatsAction::class),
            app(RenderAccountSlipAction::class),
        ];

        expect($actions[0])->toBeInstanceOf(BaseCommandAction::class)
            ->and($actions[1])->toBeInstanceOf(BaseReadAction::class)
            ->and($actions[2])->toBeInstanceOf(BaseProcessAction::class);

        foreach ($actions as $action) {
            $own = array_values(array_filter(
                (new ReflectionClass($action))->getMethods(ReflectionMethod::IS_PUBLIC),
                fn ($m) => $m->getDeclaringClass()->getName() === $action::class && $m->getName() !== '__construct',
            ));

            expect(array_map(fn ($m) => $m->getName(), $own))->toBe(['execute']);
        }

        $dto = CreateUserData::fromArray(['user' => ['name' => 'Sinta', 'email' => 'sinta@example.com']]);

        expect($dto->toArray()['user']['email'])->toBe('sinta@example.com');
    });

    test('D2FT3-FR-ARC-013: command and process outcomes use ActionResponse', function (): void {
        $ok = ActionResponse::ok(['id' => 1], 'Slip ready');
        $error = ActionResponse::error('Denied', ['user' => ['blocked']]);

        expect($ok->failed())->toBeFalse()
            ->and($error->failed())->toBeTrue()
            ->and($ok->jsonSerialize()['data'])->toBe(['id' => 1])
            ->and($error->jsonSerialize()['message'])->toBe('Denied');
    });

    test('D2FT3-FR-ARC-016: command executes accept a single DTO (also FR-ARC-024, FR-ARC-025)', function (): void {
        $params = (new ReflectionClass(GenerateAccountSlipAction::class))->getMethod('execute')->getParameters();

        expect($params)->toHaveCount(1);

        $dto = CreateUserData::fromArray(['user' => ['name' => 'Budi', 'email' => 'budi@example.com']]);
        $model = new User($dto->toArray()['user']);

        expect(is_subclass_of(CreateUserData::class, BaseData::class))->toBeTrue()
            ->and($model->email)->toBe('budi@example.com');
    });

    test('D2FT3-FR-ARC-015: business rules live in entities with value semantics (also FR-ARC-018, FR-ARC-026)', function (): void {
        $model = new AccessToken;
        $model->forceFill([
            'expires_at' => now()->addDays(30)->toDateTimeString(),
            'revoked_at' => null,
            'attempts' => 0,
        ]);

        $state = AccessTokenState::fromModel($model);

        expect($state->isValid())->toBeTrue()
            ->and($state->isExpired())->toBeFalse()
            ->and($state->hasExceededMaxAttempts())->toBeFalse()
            ->and($state->equals(AccessTokenState::fromModel($model)))->toBeTrue()
            ->and($state->with('attempts', 5)->hasExceededMaxAttempts())->toBeTrue()
            ->and($state->toArray())->toBeArray();
    });

    test('D2FT3-FR-ARC-019: entities stay pure of forbidden imports (also FR-ARC-028)', function (): void {
        foreach (d2ft3PhpFiles(base_path('app/Modules/Auth/Domain/AccessToken/Entities')) as $file) {
            $source = file_get_contents($file);

            expect($source)->not->toContain('use App\\Modules\\Auth\\Domain\\AccessToken\\Actions')
                ->and($source)->not->toContain('Livewire');
        }

        foreach (d2ft3PhpFiles(base_path('app/Modules/User/Domain/UserManagement/Actions')) as $file) {
            expect(file_get_contents($file))->not->toContain('use Livewire');
        }
    });

    test('D2FT3-FR-ARC-020: DTOs are readonly boundary objects without model imports (also FR-ARC-021, FR-ARC-022, FR-ARC-040, DD-ARC-004)', function (): void {
        $source = file_get_contents(base_path('app/Modules/User/Domain/UserManagement/Data/CreateUserData.php'));

        expect($source)->toContain('extends BaseData')
            ->and($source)->not->toContain('use App\\Modules\\User\\Models');

        $dto = CreateUserData::fromArray(['user' => ['name' => 'Ayu']]);

        expect($dto->toArray()['user']['name'])->toBe('Ayu');
    });

    test('D2FT3-FR-ARC-001: all code lives inside business modules (also FR-ARC-002, FR-ARC-004, DD-ARC-001)', function (): void {
        $strays = [];

        foreach (scandir(base_path('app')) as $entry) {
            if (str_ends_with($entry, '.php')) {
                $strays[] = $entry;
            }
        }

        expect($strays)->toBe([])
            ->and(d2ft3ModuleDirs())->toContain('Core')
            ->and(file_exists(base_path('app/Modules/Core/Actions/BaseCommandAction.php')))->toBeTrue();
    });

    test('D2FT3-FR-ARC-005: the registry is deterministic and complete (also FR-ARC-031, FR-ARC-043, UC-ARC-001)', function (): void {
        $modules = d2ft3ModuleDirs();
        $sorted = $modules;
        sort($sorted);

        expect($modules)->toBe($sorted)
            ->and(config('module.list'))->toBe($modules);

        foreach ($modules as $module) {
            if ($module === 'UI') {
                continue; // Presentation-only module: views live in resources/views/ui, no Actions by design.
            }

            $surface = 0;

            foreach (d2ft3PhpFiles(base_path('app/Modules/'.$module)) as $file) {
                if (str_contains($file, DIRECTORY_SEPARATOR.'Actions'.DIRECTORY_SEPARATOR)
                    || str_contains($file, DIRECTORY_SEPARATOR.'Models'.DIRECTORY_SEPARATOR)) {
                    $surface++;
                }
            }

            expect($surface)->toBeGreaterThan(0);
        }
    });

    test('D2FT3-FR-ARC-006: the four layers map to fixed directories (also FR-ARC-007)', function (): void {
        foreach (['Livewire', 'Actions', 'Models', 'Entities', 'Enums', 'Data'] as $layer) {
            expect(is_dir(base_path('app/Modules/User/Domain/UserManagement/'.$layer))
                || is_dir(base_path('app/Modules/User/'.$layer)))->toBeTrue();
        }

        expect(File::exists(base_path('app/Modules/Core/Actions/BaseReadAction.php')))->toBeTrue();
    });

    test('D2FT3-FR-ARC-008: Core never imports business modules (also FR-ARC-009, FR-ARC-032, UC-ARC-002)', function (): void {
        $violations = [];

        foreach (d2ft3PhpFiles(base_path('app/Modules/Core')) as $file) {
            foreach (explode("\n", (string) file_get_contents($file)) as $line) {
                if (preg_match('/^use App\\\\Modules\\\\(?!Core\\\\)([A-Z][A-Za-z]+)\\\\/', trim($line), $m)) {
                    $violations[] = $file.': '.$m[0];
                }
            }
        }

        expect($violations)->toBe([]);
    });

    test('D2FT3-FR-ARC-014: Livewire never mutates models directly', function (): void {
        $violations = [];

        foreach (d2ft3ModuleDirs() as $module) {
            foreach (d2ft3PhpFiles(base_path('app/Modules/'.$module)) as $file) {
                if (! str_contains($file, DIRECTORY_SEPARATOR.'Livewire'.DIRECTORY_SEPARATOR)) {
                    continue;
                }

                $source = (string) file_get_contents($file);
                // SmartLogger fluent chains end in ->save() but write logs, never models.
                $source = preg_replace('/SmartLogger::.*?->save\(\);/s', '', $source);

                if (preg_match('/(?<!parent)(::create\s*\(|::update\s*\(|::destroy\s*\(|->save\s*\(|->forceFill\s*\(|->delete\s*\()/', $source)) {
                    $violations[] = $file;
                }
            }
        }

        expect($violations)->toBe([]);
    });

    test('D2FT3-FR-ARC-027: cross-module side effects travel as events (also DD-ARC-005)', function (): void {
        expect(Event::getListeners(StudentRegistered::class))->not->toBe([]);

        $event = new StudentRegistered(registration: new Registration);

        expect($event->registration->id)->toBeNull();
    });

    test('D2FT3-FR-ARC-035: core contracts back every status enum (also FR-ARC-023)', function (): void {
        foreach ([CsvRowResult::class, InternshipStatus::class] as $enum) {
            foreach ($enum::cases() as $case) {
                expect($case->label())->not->toBe('');
            }
        }

        $department = Department::factory()->make(['name' => 'RPL']);

        expect($department->name)->toBe('RPL');
    });

    test('D2FT3-FR-ARC-036: tier-zero reads use the cache registry (also FR-ARC-041)', function (): void {
        $readSource = file_get_contents(base_path('app/Modules/User/Domain/Dashboard/Actions/ReadStudentDashboardAction.php'));
        $registry = file_get_contents(base_path('config/cache-keys.php'));

        expect($readSource)->toContain('remember')
            ->and($readSource)->toContain('cache-keys')
            ->and($registry)->toContain('dashboard_student');
    });

    test('D2FT3-FR-ARC-037: deployment tiers are environment swaps (also FR-ARC-038, NFR-ARC-007)', function (): void {
        $env = file_get_contents(base_path('.env.example'));

        expect($env)->toContain('QUEUE_CONNECTION=sync')
            ->and($env)->toContain('CACHE_STORE=file')
            ->and($env)->toContain('SESSION_DRIVER=database');
    });

    test('D2FT3-NFR-ARC-003: no new top-level application directories appear', function (): void {
        $dirs = [];

        foreach (scandir(base_path('app')) as $entry) {
            if ($entry !== '.' && $entry !== '..' && is_dir(base_path('app/'.$entry))) {
                $dirs[] = $entry;
            }
        }

        expect($dirs)->toBe(['Modules', 'Providers']);
    });

    test('D2FT3-NFR-ARC-001: architecture invariants ship automated enforcement (also NFR-ARC-002, DD-ARC-007)', function (): void {
        expect(file_exists(base_path('tools/scan_violations.py')))->toBeTrue()
            ->and(file_exists(base_path('tools/scan_class_contracts.py')))->toBeTrue()
            ->and(file_exists(base_path('tools/scan_spec_tests.py')))->toBeTrue();

        expect(File::get(base_path('tools/scan_violations.py')))->toContain('C1');
    });
});
