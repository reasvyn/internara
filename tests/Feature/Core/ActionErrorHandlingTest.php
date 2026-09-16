<?php

declare(strict_types=1);

use App\Modules\Auth\Domain\Probe\Actions\SraProbeActionB;
use App\Modules\Core\Exceptions\ActionFailedException;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Journal\Domain\Probe\Actions\SraProbeActionA;
use App\Modules\User\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

uses(LazilyRefreshDatabase::class);

if (! class_exists(SraProbeActionA::class)) {
    eval('
        namespace App\Modules\Journal\Domain\Probe\Actions;
        final class SraProbeActionA extends \App\Modules\Core\Actions\BaseAction
        {
            public function run(callable $callback, string $context): mixed
            {
                return $this->withErrorHandling($callback, $context);
            }
            public function boom(string $message, array $context = []): never
            {
                $this->fail($message, $context);
            }
            public function write(string $action): void
            {
                $this->log($action);
            }
        }
    ');
}

if (! class_exists(SraProbeActionB::class)) {
    eval('
        namespace App\Modules\Auth\Domain\Probe\Actions;
        final class SraProbeActionB extends \App\Modules\Core\Actions\BaseAction
        {
            public function run(callable $callback, string $context): mixed
            {
                return $this->withErrorHandling($callback, $context);
            }
            public function boom(string $message, array $context = []): never
            {
                $this->fail($message, $context);
            }
            public function write(string $action): void
            {
                $this->log($action);
            }
        }
    ');
}

describe('89SRA: Action-layer error handling', function (): void {
    test('89SRA-FR-LOG-040: the wrapper returns the callback value on success', function (): void {
        $probe = new SraProbeActionA;

        expect($probe->run(fn (): string => 'attendance saved', 'attendance probe 89sra'))->toBe('attendance saved');
    });

    test('89SRA-FR-LOG-041: known exception types re-throw untouched and unlogged', function (): void {
        $captured = captureLogs();
        $probe = new SraProbeActionA;

        $known = [
            'module' => new RejectedException('Quota full'),
            'app' => new ActionFailedException('Disk gone'),
            'validation' => ValidationException::withMessages(['date' => 'The date field is required.']),
            'authorization' => new AuthorizationException('Not allowed here.'),
            'not-found' => (new ModelNotFoundException)->setModel(User::class),
            'http-not-found' => new NotFoundHttpException('No such page.'),
        ];

        foreach ($known as $label => $original) {
            $caught = null;

            try {
                $probe->run(function () use ($original): never {
                    throw $original;
                }, "known passthrough {$label} 89sra");
            } catch (Throwable $caught) {
            }

            expect($caught)->toBe($original);
        }

        expect($captured)->toHaveCount(0);
    });

    test('89SRA-FR-LOG-042: unknown throwables land system-only with error context', function (): void {
        $captured = captureLogs();
        $probe = new SraProbeActionA;

        try {
            $probe->run(function (): never {
                throw new Error('gateway exploded');
            }, 'gateway probe 89sra');
        } catch (ActionFailedException) {
        }

        $record = $captured->firstWhere('message', 'gateway probe 89sra');

        expect($record)->not->toBeNull()
            ->and($record->level)->toBe('error')
            ->and($record->context['payload']['error'])->toBe('gateway exploded')
            ->and($record->context['payload'])->toHaveKey('original_file')
            ->and($record->context['payload'])->toHaveKey('original_line')
            ->and(DB::table('activity_log')->where('description', 'gateway probe 89sra')->count())->toBe(0);
    });

    test('89SRA-FR-LOG-043: the wrapped re-throw keeps the original as previous', function (): void {
        $probe = new SraProbeActionA;
        $original = new Error('deadlock victim');
        $caught = null;

        try {
            $probe->run(function () use ($original): never {
                throw $original;
            }, 'causality probe 89sra');
        } catch (RuntimeException $caught) {
        }

        expect($caught)->toBeInstanceOf(RuntimeException::class)
            ->and($caught->getPrevious())->toBe($original)
            ->and($caught->getMessage())->toBe('causality probe 89sra.');
    });

    test('89SRA-FR-LOG-044: fail signals business rules with RejectedException', function (): void {
        $probe = new SraProbeActionA;

        try {
            $probe->boom('Already clocked in today', ['student_id' => 'stu-1']);
            $this->fail('fail() did not throw');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe('Already clocked in today')
                ->and($e->statusCode())->toBe(400)
                ->and($e->getContext())->toBe(['student_id' => 'stu-1']);
        }
    });

    test('89SRA-FR-LOG-045: the module name is derived from the Action namespace', function (): void {
        $user = User::factory()->create();
        $this->actingAs($user);

        (new SraProbeActionA)->write('89sra module probe alpha');
        (new SraProbeActionB)->write('89sra module probe beta');

        $alpha = DB::table('activity_log')->where('description', '89sra module probe alpha')->first();
        $beta = DB::table('activity_log')->where('description', '89sra module probe beta')->first();

        expect($alpha->log_name)->toBe('Journal')
            ->and($beta->log_name)->toBe('Auth');
    });
});
