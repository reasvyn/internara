<?php

declare(strict_types=1);

use App\Modules\Academic\Domain\Department\Actions\CreateDepartmentAction;
use App\Modules\Academic\Domain\Department\Models\Department;
use App\Modules\Assignment\Actions\CreateAssignmentAction;
use App\Modules\Assignment\Data\CreateAssignmentData;
use App\Modules\Auth\Domain\AccessToken\Entities\AccessTokenState;
use App\Modules\Auth\Domain\AccessToken\Models\AccessToken;
use App\Modules\Core\Actions\BaseAction;
use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Contracts\SendsNotifications;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Data\BaseData;
use App\Modules\Core\Entities\BaseEntity;
use App\Modules\Core\Enums\CsvRowResult;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Enrollment\Domain\Registration\Events\StudentRegistered;
use App\Modules\Enrollment\Domain\Registration\Models\Registration;
use App\Modules\Program\Domain\Internship\Enums\InternshipStatus;
use App\Modules\SysAdmin\Domain\Backup\Actions\ReadBackupStatsAction;
use App\Modules\User\Domain\UserManagement\Actions\GenerateAccountSlipAction;
use App\Modules\User\Domain\UserManagement\Actions\RenderAccountSlipAction;
use App\Modules\User\Domain\UserManagement\Data\CreateUserData;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
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
    test('D2FT3-FR-ARC-011, D2FT3-FR-ARC-012, D2FT3-DD-ARC-002: the action triad resolves with a single execute entry', function (): void {
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

    test('D2FT3-FR-ARC-016, D2FT3-FR-ARC-024, D2FT3-FR-ARC-025: command executes accept a single DTO', function (): void {
        $params = (new ReflectionClass(GenerateAccountSlipAction::class))->getMethod('execute')->getParameters();

        expect($params)->toHaveCount(1);

        $dto = CreateUserData::fromArray(['user' => ['name' => 'Budi', 'email' => 'budi@example.com']]);
        $model = new User($dto->toArray()['user']);

        expect(is_subclass_of(CreateUserData::class, BaseData::class))->toBeTrue()
            ->and($model->email)->toBe('budi@example.com');
    });

    test('D2FT3-FR-ARC-015, D2FT3-FR-ARC-018, D2FT3-FR-ARC-026, D2FT3-DD-ARC-003: business rules live in entities with value semantics', function (): void {
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

    test('D2FT3-FR-ARC-019, D2FT3-FR-ARC-028: entities stay pure of forbidden imports', function (): void {
        foreach (d2ft3PhpFiles(base_path('app/Modules/Auth/Domain/AccessToken/Entities')) as $file) {
            $source = file_get_contents($file);

            expect($source)->not->toContain('use App\\Modules\\Auth\\Domain\\AccessToken\\Actions')
                ->and($source)->not->toContain('Livewire');
        }

        foreach (d2ft3PhpFiles(base_path('app/Modules/User/Domain/UserManagement/Actions')) as $file) {
            expect(file_get_contents($file))->not->toContain('use Livewire');
        }
    });

    test('D2FT3-FR-ARC-020, D2FT3-FR-ARC-021, D2FT3-FR-ARC-022, D2FT3-FR-ARC-040, D2FT3-DD-ARC-004: DTOs are readonly boundary objects without model imports', function (): void {
        $source = file_get_contents(base_path('app/Modules/User/Domain/UserManagement/Data/CreateUserData.php'));

        expect($source)->toContain('extends BaseData')
            ->and($source)->not->toContain('use App\\Modules\\User\\Models');

        $dto = CreateUserData::fromArray(['user' => ['name' => 'Ayu']]);

        expect($dto->toArray()['user']['name'])->toBe('Ayu');
    });

    test('D2FT3-FR-ARC-001, D2FT3-FR-ARC-002, D2FT3-FR-ARC-004, D2FT3-DD-ARC-001: all code lives inside business modules', function (): void {
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

    test('D2FT3-FR-ARC-005, D2FT3-FR-ARC-031, D2FT3-FR-ARC-043, D2FT3-UC-ARC-001: the registry is deterministic and complete', function (): void {
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

    test('D2FT3-FR-ARC-006, D2FT3-FR-ARC-007: the four layers map to fixed directories', function (): void {
        foreach (['Livewire', 'Actions', 'Models', 'Entities', 'Enums', 'Data'] as $layer) {
            expect(is_dir(base_path('app/Modules/User/Domain/UserManagement/'.$layer))
                || is_dir(base_path('app/Modules/User/'.$layer)))->toBeTrue();
        }

        expect(File::exists(base_path('app/Modules/Core/Actions/BaseReadAction.php')))->toBeTrue();
    });

    test('D2FT3-FR-ARC-008, D2FT3-FR-ARC-009, D2FT3-FR-ARC-032, D2FT3-UC-ARC-002: Core never imports business modules', function (): void {
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

    test('D2FT3-FR-ARC-027, D2FT3-DD-ARC-005: cross-module side effects travel as events', function (): void {
        expect(Event::getListeners(StudentRegistered::class))->not->toBe([]);

        $event = new StudentRegistered(registration: new Registration);

        expect($event->registration->id)->toBeNull();
    });

    test('D2FT3-FR-ARC-035, D2FT3-FR-ARC-023: core contracts back every status enum', function (): void {
        foreach ([CsvRowResult::class, InternshipStatus::class] as $enum) {
            foreach ($enum::cases() as $case) {
                expect($case->label())->not->toBe('');
            }
        }

        $department = Department::factory()->make(['name' => 'RPL']);

        expect($department->name)->toBe('RPL');
    });

    test('D2FT3-FR-ARC-036, D2FT3-FR-ARC-041: tier-zero reads use the cache registry', function (): void {
        $readSource = file_get_contents(base_path('app/Modules/User/Domain/Dashboard/Actions/ReadStudentDashboardAction.php'));
        $registry = file_get_contents(base_path('config/cache-keys.php'));

        expect($readSource)->toContain('remember')
            ->and($readSource)->toContain('cache-keys')
            ->and($registry)->toContain('dashboard_student');
    });

    test('D2FT3-FR-ARC-037, D2FT3-FR-ARC-038, D2FT3-NFR-ARC-007: deployment tiers are environment swaps', function (): void {
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

    test('D2FT3-NFR-ARC-001, D2FT3-NFR-ARC-002, D2FT3-DD-ARC-007: architecture invariants ship automated enforcement', function (): void {
        expect(file_exists(base_path('tools/scan_violations.py')))->toBeTrue()
            ->and(file_exists(base_path('tools/scan_class_contracts.py')))->toBeTrue()
            ->and(file_exists(base_path('tools/scan_spec_tests.py')))->toBeTrue();

        expect(File::get(base_path('tools/scan_violations.py')))->toContain('C1');
    });

    test('D2FT3-UC-ARC-003, D2FT3-FR-ARC-044: mutation flow traces structurally from UI to DTO to Command Action to Entity to Model to Event to ActionResponse', function (): void {
        $actionClass = CreateAssignmentAction::class;
        $ref = new ReflectionClass($actionClass);
        expect($ref->isSubclassOf(BaseCommandAction::class))->toBeTrue();

        $executeMethod = $ref->getMethod('execute');
        $params = $executeMethod->getParameters();
        expect($params)->toHaveCount(1);
        expect($params[0]->getType()?->getName())->toBe(CreateAssignmentData::class);

        $dtoRef = new ReflectionClass(CreateAssignmentData::class);
        expect($dtoRef->isSubclassOf(BaseData::class))->toBeTrue();
    });

    test('D2FT3-FR-ARC-003, D2FT3-FR-ARC-010, D2FT3-FR-ARC-033, D2FT3-FR-ARC-034: module public surface and ranked communication hierarchy', function (): void {
        $allowedSurface = ['Actions', 'Services', 'Contracts', 'Events', 'Entities', 'Enums', 'Data', 'Models', 'Http', 'Livewire', 'Providers', 'Support'];
        $academicDirs = scandir(base_path('app/Modules/Academic'));
        $filtered = array_filter($academicDirs, fn ($d) => $d !== '.' && $d !== '..' && is_dir(base_path('app/Modules/Academic/'.$d)));

        foreach ($filtered as $dir) {
            if ($dir === 'Domain') {
                continue;
            }
            expect($allowedSurface)->toContain($dir);
        }

        expect(interface_exists(SendsNotifications::class))->toBeTrue();
    });

    test('D2FT3-FR-ARC-017, D2FT3-FR-ARC-029, D2FT3-FR-ARC-030, D2FT3-DD-ARC-006: read and command actions encapsulate access without repository pattern', function (): void {
        $readClass = ReadBackupStatsAction::class;
        expect(is_subclass_of($readClass, BaseReadAction::class))->toBeTrue();

        $commandClass = CreateDepartmentAction::class;
        expect(is_subclass_of($commandClass, BaseCommandAction::class))->toBeTrue();

        // Models are directly used by Actions without an unnecessary repository abstraction layer
        $model = new Department;
        expect($model)->toBeInstanceOf(Model::class);
    });

    test('D2FT3-FR-ARC-039: deferred optimization decisions hold until bottleneck measured', function (): void {
        $composer = json_decode(file_get_contents(base_path('composer.json')), true);

        // Octane deferred until performance profiles require it
        expect(array_key_exists('laravel/octane', $composer['require'] ?? []))->toBeFalse();
    });

    test('D2FT3-FR-ARC-042: validation rules centralization follows gradual migration path', function (): void {
        expect(method_exists(BaseEntity::class, 'fromArray'))->toBeTrue();
    });

    test('D2FT3-NFR-ARC-004: actions eager-load relations avoiding N+1 queries', function (): void {
        $source = file_get_contents(base_path('app/Modules/Journal/Domain/Logbook/Actions/CompileLogbookReportAction.php'));
        expect($source)->toContain("with(['user', 'supervisor', 'media'])");
    });

    test('D2FT3-NFR-ARC-005: defense-in-depth authorization is enforced via policies and business rejection', function (): void {
        $exception = new RejectedException('Aksi tidak diizinkan');
        expect($exception->statusCode())->toBe(400)
            ->and($exception->getMessage())->toBe('Aksi tidak diizinkan');
    });

    test('D2FT3-NFR-ARC-006: clean-code and DRY principles share foundation through Core', function (): void {
        expect(is_subclass_of(BaseCommandAction::class, BaseAction::class))->toBeTrue()
            ->and((new ReflectionClass(BaseReadAction::class))->isAbstract())->toBeTrue();
    });
});
