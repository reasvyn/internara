<?php

declare(strict_types=1);

use App\Modules\Core\Actions\BaseCommandAction;
use App\Modules\Core\Actions\BaseProcessAction;
use App\Modules\Core\Actions\BaseReadAction;
use App\Modules\Core\Data\ActionResponse;
use App\Modules\Core\Data\BaseData;
use App\Modules\Core\Enums\CsvRowResult;
use App\Modules\Core\Exceptions\ActionFailedException;
use App\Modules\Core\Exceptions\AppException;
use App\Modules\Core\Exceptions\InfrastructureException;
use App\Modules\Core\Exceptions\ModuleException;
use App\Modules\Core\Exceptions\PresentationException;
use App\Modules\Core\Exceptions\RejectedException;
use App\Modules\Core\Exceptions\UnauthorizedException;
use App\Modules\Core\Exceptions\ValidationFailedException;
use App\Modules\Core\Http\Controllers\BaseController;
use App\Modules\Core\Http\Requests\BaseFormRequest;
use App\Modules\Core\Livewire\BaseWizard;
use App\Modules\Core\Livewire\Concerns\WithRecordSelection;
use App\Modules\Core\Livewire\Concerns\WithSorting;
use App\Modules\SysAdmin\Domain\Backup\Enums\BackupStatus;
use App\Modules\User\Enums\AccountStatus;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Notifications\Notification as BaseNotification;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Component;

final class Se5q9CmdProbe extends BaseCommandAction
{
    public function execute(array $data = []): ActionResponse
    {
        return $this->respond($data, 'probe-ok');
    }

    public function pubRespond(mixed $data, ?string $message = null, bool $created = false): ActionResponse
    {
        return $this->respond($data, $message, $created);
    }

    public function pubRespondDeleted(?string $message = null): ActionResponse
    {
        return $this->respondDeleted($message);
    }

    public function pubRespondError(string $message, array $errors = []): ActionResponse
    {
        return $this->respondError($message, $errors);
    }

    public function pubValidate(array $data, array $rules): array
    {
        return $this->validate($data, $rules);
    }

    public function pubAuthorize(string $ability, mixed $arguments = []): void
    {
        $this->authorize($ability, $arguments);
    }

    public function pubFlash(string $message, string $type = 'success'): void
    {
        $this->flash($message, $type);
    }

    public function pubFail(string $message, array $context = []): never
    {
        $this->fail($message, $context);
    }

    public function pubTx(callable $callback, int $attempts = 3): mixed
    {
        return $this->transaction($callback, $attempts);
    }

    public function pubHandle(callable $callback, string $context): mixed
    {
        return $this->withErrorHandling($callback, $context);
    }
}

final class Se5q9ProcessProbe extends BaseProcessAction
{
    public function execute(): ActionResponse
    {
        return $this->respond('unused');
    }

    private function respond(mixed $data): ActionResponse
    {
        return ActionResponse::ok($data);
    }

    public function pubStep(string $name, callable $callback): mixed
    {
        return $this->step($name, $callback);
    }

    public function pubTrack(float $percent, ?string $message = null): void
    {
        $this->trackProgress($percent, $message);
    }

    public function pubProgress(): array
    {
        return $this->getProgress();
    }

    public function pubResults(): array
    {
        return $this->getResults();
    }

    public function pubAllSucceeded(): bool
    {
        return $this->allStepsSucceeded();
    }

    public function pubNotify(mixed $notifiables, BaseNotification $notification): void
    {
        $this->notify($notifiables, $notification);
    }
}

final class Se5q9ReadProbe extends BaseReadAction
{
    public function execute(): array
    {
        return $this->format(['a', 'b']);
    }

    public function pubRemember(string $key, callable $callback, int $ttl = 300): mixed
    {
        return $this->remember($key, $callback, $ttl);
    }

    public function pubRememberForever(string $key, callable $callback): mixed
    {
        return $this->rememberForever($key, $callback);
    }

    public function pubForget(string $key): void
    {
        $this->forget($key);
    }

    public function pubCacheKey(string $purpose, string ...$qualifiers): string
    {
        return $this->cacheKey($purpose, ...$qualifiers);
    }

    public function pubMask(array $data, array $fields = []): array
    {
        return $this->mask($data, $fields);
    }

    public function pubFormat(mixed $data, ?int $total = null, int $perPage = 15): array
    {
        return $this->format($data, $total, $perPage);
    }
}

final readonly class Se5q9Payload extends BaseData
{
    public function __construct(
        public string $userName,
        public string $email,
    ) {}
}

final class Se5q9ArrayStartAction extends BaseCommandAction
{
    public function execute(array $data): ActionResponse
    {
        $validated = $this->validate($data, ['name' => 'required|string']);

        return $this->respond($validated, 'stored');
    }
}

final class Se5q9UnionAction extends BaseCommandAction
{
    public function execute(Se5q9Payload|array $data): ActionResponse
    {
        $dto = $data instanceof Se5q9Payload ? $data : Se5q9Payload::fromArray($data);

        return $this->respond($dto->toArray(), 'stored');
    }
}

final class Se5q9DtoFinalAction extends BaseCommandAction
{
    public function execute(Se5q9Payload $data): ActionResponse
    {
        return $this->respond($data->toArray(), 'stored');
    }
}

final class Se5q9ControllerProbe extends BaseController
{
    public function ok(mixed $data = null, string $message = 'Success', int $code = 200, array $extra = []): JsonResponse
    {
        return $this->jsonSuccess($data, $message, $code, $extra);
    }

    public function created(mixed $data = null): JsonResponse
    {
        return $this->jsonCreated($data);
    }

    public function err(string $message = 'Error', int $code = 400, mixed $errors = null, array $extra = []): JsonResponse
    {
        return $this->jsonError($message, $code, $errors, $extra);
    }

    public function paged(LengthAwarePaginator $paginator): JsonResponse
    {
        return $this->jsonPaginated($paginator);
    }
}

final class Se5q9FormRequestProbe extends BaseFormRequest
{
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }

    public function runFailedValidation(Illuminate\Contracts\Validation\Validator $validator): void
    {
        $this->failedValidation($validator);
    }
}

final class Se5q9SortProbe extends Component
{
    use WithSorting;

    public function runSort(Builder $query): Builder
    {
        return $this->applySorting($query);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

final class Se5q9SelectionProbe extends Component
{
    use WithRecordSelection;

    public function render(): string
    {
        return '<div></div>';
    }
}

final class Se5q9WizardProbe extends BaseWizard
{
    protected function steps(): array
    {
        return ['alpha', 'beta', 'gamma'];
    }

    public function runStepError(callable $callback): void
    {
        $this->handleStepError($callback);
    }

    public function render(): string
    {
        return '<div></div>';
    }
}

final class Se5q9PingNotification extends BaseNotification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }
}

describe('SE5Q9: action triad and bases', function (): void {
    test('SE5Q9-FR-BASE-002: respond factories shape ok, created, deleted, and error envelopes', function (): void {
        $probe = new Se5q9CmdProbe;

        $ok = $probe->pubRespond(['id' => 7], 'Placed');
        expect($ok->success)->toBeTrue()->and($ok->data)->toBe(['id' => 7])->and($ok->message)->toBe('Placed');

        $created = $probe->pubRespond(['id' => 8], null, true);
        expect($created->success)->toBeTrue()->and($created->data)->toBe(['id' => 8]);

        $deleted = $probe->pubRespondDeleted();
        expect($deleted->success)->toBeTrue();

        $error = $probe->pubRespondError('Quota full', ['company_id' => ['Full']]);
        expect($error->success)->toBeFalse()
            ->and($error->failed())->toBeTrue()
            ->and($error->errors)->toBe(['company_id' => ['Full']]);
    });

    test('SE5Q9-FR-BASE-002: validate returns clean data and throws field errors on bad input', function (): void {
        $probe = new Se5q9CmdProbe;

        expect($probe->pubValidate(['name' => 'RPL'], ['name' => 'required|string']))->toBe(['name' => 'RPL']);

        try {
            $probe->pubValidate(['name' => ''], ['name' => 'required|string']);
            expect(false)->toBeTrue('expected ValidationException');
        } catch (ValidationException $e) {
            expect($e->errors())->toHaveKey('name');
        }
    });

    test('SE5Q9-FR-BASE-002: flash stores a toast payload in the session', function (): void {
        (new Se5q9CmdProbe)->pubFlash('Saved', 'success');

        $toast = session()->get('ts-ui:toast');

        expect($toast['title'])->toBe('Saved')->and($toast['type'])->toBe('success');
    });

    test('SE5Q9-FR-BASE-004: step records success and returns the callback value', function (): void {
        $probe = new Se5q9ProcessProbe;

        expect($probe->pubStep('finalize', fn () => 42))->toBe(42);
        expect($probe->pubResults())->toBe(['finalize' => ['success' => true]]);
        expect($probe->pubAllSucceeded())->toBeTrue();
    });

    test('SE5Q9-FR-BASE-004: failing step records the error message and rethrows', function (): void {
        $probe = new Se5q9ProcessProbe;
        $probe->pubStep('prepare', fn () => true);

        try {
            $probe->pubStep('issue', fn () => throw new RuntimeException('printer jam'));
            expect(false)->toBeTrue('expected rethrow');
        } catch (RuntimeException $e) {
            expect($e->getMessage())->toBe('printer jam');
        }

        $results = $probe->pubResults();
        expect($results['issue']['success'])->toBeFalse();
        expect($results['issue']['error'])->toBe('printer jam');
        expect($probe->pubAllSucceeded())->toBeFalse();
    });

    test('SE5Q9-FR-BASE-004: trackProgress clamps into 0..100 and keeps its message', function (): void {
        $probe = new Se5q9ProcessProbe;

        $probe->pubTrack(25.0, 'quarter done');
        expect($probe->pubProgress())->toBe(['percent' => 25.0, 'message' => 'quarter done']);

        $probe->pubTrack(140.0);
        expect($probe->pubProgress()['percent'])->toBe(100.0);

        $probe->pubTrack(-5.0);
        expect($probe->pubProgress()['percent'])->toBe(0.0);
    });

    test('SE5Q9-FR-BASE-004: notify dispatches through the notification channel', function (): void {
        Notification::fake();

        $user = User::factory()->make(['id' => (string) Str::uuid()]);
        (new Se5q9ProcessProbe)->pubNotify($user, new Se5q9PingNotification);

        Notification::assertSentTimes(Se5q9PingNotification::class, 1);
    });

    test('SE5Q9-NFR-BASE-001: deadlock failures retry up to three attempts', function (): void {
        $probe = new Se5q9CmdProbe;
        $attempts = 0;

        $result = $probe->pubTx(function () use (&$attempts) {
            $attempts++;

            if ($attempts < 3) {
                throw new RuntimeException('Deadlock found when trying to get lock; try restarting transaction');
            }

            return 'landed';
        });

        expect($result)->toBe('landed');
        expect($attempts)->toBe(3);
    });

    test('SE5Q9-FR-BASE-008: command execute returns a success ActionResponse', function (): void {
        $response = (new Se5q9CmdProbe)->execute(['id' => 3]);

        expect($response)->toBeInstanceOf(ActionResponse::class);
        expect($response->success)->toBeTrue();
        expect($response->data)->toBe(['id' => 3]);
    });

    test('SE5Q9-FR-BASE-008: read execute returns value data shaped by format', function (): void {
        $result = (new Se5q9ReadProbe)->execute();

        expect($result['data'])->toBe(['a', 'b']);
        expect($result['meta'])->toBe(['total' => 2, 'per_page' => 15]);
    });
});

describe('SE5Q9: read base behavior', function (): void {
    test('SE5Q9-FR-BASE-003: remember caches the callback result and forget clears it', function (): void {
        Cache::flush();
        $probe = new Se5q9ReadProbe;
        $calls = 0;

        $first = $probe->pubRemember('se5q9-demo', function () use (&$calls) {
            $calls++;

            return 'built';
        });
        $second = $probe->pubRemember('se5q9-demo', function () use (&$calls) {
            $calls++;

            return 'rebuilt';
        });

        expect($first)->toBe('built')->and($second)->toBe('built')->and($calls)->toBe(1);

        $probe->pubForget('se5q9-demo');
        expect(Cache::has('se5q9-demo'))->toBeFalse();
    });

    test('SE5Q9-FR-BASE-003: rememberForever persists until forget', function (): void {
        Cache::flush();
        $probe = new Se5q9ReadProbe;

        expect($probe->pubRememberForever('se5q9-ever', fn () => 'kept'))->toBe('kept');
        expect(Cache::has('se5q9-ever'))->toBeTrue();

        $probe->pubForget('se5q9-ever');
        expect(Cache::has('se5q9-ever'))->toBeFalse();
    });

    test('SE5Q9-FR-BASE-003: mask hides PII fields but keeps ordinary data', function (): void {
        $probe = new Se5q9ReadProbe;

        $masked = $probe->pubMask(['password' => 'secret', 'nickname' => 'sinta']);

        expect($masked['password'])->toBe('***')->and($masked['nickname'])->toBe('sinta');

        $scoped = $probe->pubMask(['password' => 'secret', 'nickname' => 'sinta'], ['password']);

        expect($scoped['password'])->toBe('***')->and($scoped['nickname'])->toBe('sinta');
    });

    test('SE5Q9-FR-BASE-003: format wraps data with total and per_page meta', function (): void {
        $probe = new Se5q9ReadProbe;

        expect($probe->pubFormat(['x'], null, 10))->toBe([
            'data' => ['x'],
            'meta' => ['total' => 1, 'per_page' => 10],
        ]);
        expect($probe->pubFormat('scalar', 99)['meta'])->toBe(['total' => 99, 'per_page' => 15]);
    });

    test('SE5Q9-FR-BASE-043: cacheKey builds module-scoped keys usable by remember', function (): void {
        Cache::flush();
        $probe = new Se5q9ReadProbe;

        $key = $probe->pubCacheKey('stats', '2026');

        expect($key)->toBeString()->and($key)->toContain('stats')->and($key)->toContain('2026');

        $probe->pubRemember($key, fn () => 'cached');
        expect(Cache::has($key))->toBeTrue();
        expect($probe->pubRemember($key, fn () => 'other'))->toBe('cached');
    });
});

describe('SE5Q9: DTO migration phases', function (): void {
    test('SE5Q9-FR-BASE-039: array-start action validates a raw array payload', function (): void {
        $response = (new Se5q9ArrayStartAction)->execute(['name' => 'RPL']);

        expect($response->success)->toBeTrue()->and($response->data)->toBe(['name' => 'RPL']);
    });

    test('SE5Q9-FR-BASE-040: union action accepts arrays and DTOs with legacy keys', function (): void {
        $action = new Se5q9UnionAction;

        $fromSnake = $action->execute(['user_name' => 'sinta', 'email' => 'sinta@smk.id']);
        expect($fromSnake->data)->toBe(['userName' => 'sinta', 'email' => 'sinta@smk.id']);

        $dto = Se5q9Payload::fromArray(['userName' => 'budi', 'email' => 'budi@smk.id']);
        $fromDto = $action->execute($dto);
        expect($fromDto->data)->toBe(['userName' => 'budi', 'email' => 'budi@smk.id']);
    });

    test('SE5Q9-FR-BASE-041: DTO-final action contracts on the DTO and rejects raw arrays', function (): void {
        $dto = Se5q9Payload::fromArray(['userName' => 'sinta', 'email' => 'sinta@smk.id']);
        $response = (new Se5q9DtoFinalAction)->execute($dto);

        expect($response->success)->toBeTrue()->and($response->data['userName'])->toBe('sinta');
        expect(fn () => (new Se5q9DtoFinalAction)->execute(['userName' => 'x']))->toThrow(TypeError::class);
    });
});

describe('SE5Q9: exception hierarchy behavior', function (): void {
    test('SE5Q9-FR-BASE-028: AppException branch is catchable as infrastructure', function (): void {
        $caught = null;

        try {
            throw new ValidationFailedException('Bad payload', null, ['errors' => ['name' => ['Required']]]);
        } catch (AppException $e) {
            $caught = get_class($e).':'.$e->statusCode();
        }

        expect($caught)->toBe(ValidationFailedException::class.':422');
    });

    test('SE5Q9-FR-BASE-029: ModuleException never shares the AppException bloodline', function (): void {
        $caught = null;

        try {
            throw new RejectedException('Quota full');
        } catch (ModuleException $e) {
            $caught = 'module';
        } catch (AppException $e) {
            $caught = 'app';
        }

        expect($caught)->toBe('module');
        expect(new RejectedException('x'))->not->toBeInstanceOf(AppException::class);
        expect(new ActionFailedException('x'))->not->toBeInstanceOf(ModuleException::class);
    });

    test('SE5Q9-FR-BASE-030: fail throws a 400 RejectedException with context', function (): void {
        try {
            (new Se5q9CmdProbe)->pubFail('Placement quota is full', ['company_id' => 'c-1']);
            expect(false)->toBeTrue('expected RejectedException');
        } catch (RejectedException $e) {
            expect($e->getMessage())->toBe('Placement quota is full');
            expect($e->statusCode())->toBe(400);
            expect($e->getContext())->toBe(['company_id' => 'c-1']);
            expect($e->isUserFacing())->toBeTrue();
        }
    });

    test('SE5Q9-FR-BASE-031: ValidationFailedException renders 422 with field errors', function (): void {
        $e = new ValidationFailedException('Form invalid', null, ['errors' => ['company_id' => ['Full']]]);

        expect($e->statusCode())->toBe(422);
        expect($e->getContext())->toBe(['errors' => ['company_id' => ['Full']]]);
        expect($e->isUserFacing())->toBeTrue();
    });

    test('SE5Q9-FR-BASE-032: UnauthorizedException renders 403 with an actionable hint', function (): void {
        app()->setLocale('en');
        $e = new UnauthorizedException;

        expect($e->statusCode())->toBe(403);
        expect($e)->toBeInstanceOf(PresentationException::class);
        expect($e->getHint())->toBe('You are not authorized to perform this action.');
    });

    test('SE5Q9-FR-BASE-033: infrastructure failures log fully but never face the user', function (): void {
        $e = new ActionFailedException('Mount lost');

        expect($e->statusCode())->toBe(500);
        expect($e->isUserFacing())->toBeFalse();
        expect($e)->toBeInstanceOf(InfrastructureException::class);
    });

    test('SE5Q9-FR-BASE-034: exception context round-trips hint, masked context, and CLI output', function (): void {
        $e = (new RejectedException('Quota full'))
            ->withHint('Try another company')
            ->withContext(['company_id' => 'c-9', 'password' => 'secret']);

        expect($e->getHint())->toBe('Try another company');
        expect($e->getContext())->toBe(['company_id' => 'c-9', 'password' => 'secret']);
        expect($e->getSanitizedContext())->toBe(['company_id' => 'c-9', 'password' => '***']);

        $out = $e->toCliOutput();
        expect($out)->toContain('Quota full')
            ->and($out)->toContain('Hint: Try another company')
            ->and($out)->toContain('c-9')
            ->and($out)->not->toContain('secret');

        $chained = (new RejectedException('Outer'))->withContext([]);
        $chainedWithPrevious = new RejectedException('Outer', previous: $e);
        expect($chainedWithPrevious->toCliOutput())->toContain('Previous: Quota full');
        expect($chained->toCliOutput())->toBe('Outer');
    });

    test('SE5Q9-FR-BASE-035: unknown throwables are wrapped, known ones pass through', function (): void {
        $probe = new Se5q9CmdProbe;

        try {
            $probe->pubHandle(fn () => throw new RuntimeException('disk gone'), 'Placing student.');
            expect(false)->toBeTrue('expected wrapper');
        } catch (ActionFailedException $e) {
            expect($e->statusCode())->toBe(500);
            expect($e->getMessage())->toBe('Placing student.');
            expect($e->getContext())->toBe(['original_error' => 'disk gone']);
            expect($e->getHint())->not->toBeNull();
            expect($e->getPrevious())->toBeInstanceOf(RuntimeException::class);
        }

        $rejected = new RejectedException('rule');

        try {
            $probe->pubHandle(function () use ($rejected): void {
                throw $rejected;
            }, 'ctx');
            expect(false)->toBeTrue('expected RejectedException to pass through');
        } catch (RejectedException $e) {
            expect($e)->toBe($rejected);
        }

        $passthrough = $probe->pubHandle(fn () => 'kept', 'ctx');
        expect($passthrough)->toBe('kept');

        try {
            $probe->pubHandle(fn () => throw new ValidationFailedException('bad'), 'ctx');
            expect(false)->toBeTrue('expected passthrough throw');
        } catch (ValidationFailedException $e) {
            expect($e->getMessage())->toBe('bad');
        }
    });
});

describe('SE5Q9: enum contracts', function (): void {
    test('SE5Q9-FR-BASE-024: every CsvRowResult case renders a translated label', function (): void {
        app()->setLocale('en');
        $en = array_map(fn (CsvRowResult $c) => $c->label(), CsvRowResult::cases());

        app()->setLocale('id');
        $id = array_map(fn (CsvRowResult $c) => $c->label(), CsvRowResult::cases());

        expect($en)->toHaveCount(3)->and($id)->toHaveCount(3);
        foreach (array_merge($en, $id) as $label) {
            expect($label)->toBeString()->not->toBeEmpty();
        }
        expect($en)->not->toBe($id);
        expect($en[0])->toContain('Created');
    });

    test('SE5Q9-FR-BASE-025: BackupStatus enforces its transition table', function (): void {
        expect(BackupStatus::PENDING->isTerminal())->toBeFalse();
        expect(BackupStatus::COMPLETED->isTerminal())->toBeTrue();
        expect(BackupStatus::FAILED->isTerminal())->toBeTrue();

        expect(BackupStatus::PENDING->validTransitions())->toBe([BackupStatus::RUNNING, BackupStatus::FAILED]);
        expect(BackupStatus::COMPLETED->validTransitions())->toBe([]);

        expect(BackupStatus::PENDING->canTransitionTo(BackupStatus::RUNNING))->toBeTrue();
        expect(BackupStatus::RUNNING->canTransitionTo(BackupStatus::COMPLETED))->toBeTrue();
        expect(BackupStatus::COMPLETED->canTransitionTo(BackupStatus::PENDING))->toBeFalse();
        expect(BackupStatus::PENDING->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
    });

    test('SE5Q9-FR-BASE-025: AccountStatus terminal states reject every transition', function (): void {
        expect(AccountStatus::ARCHIVED->isTerminal())->toBeTrue();
        expect(AccountStatus::ACTIVATED->isTerminal())->toBeFalse();

        expect(AccountStatus::ARCHIVED->canTransitionTo(AccountStatus::ACTIVATED))->toBeFalse();
        expect(AccountStatus::ACTIVATED->canTransitionTo(AccountStatus::VERIFIED))->toBeTrue();
        expect(AccountStatus::ACTIVATED->canTransitionTo(AccountStatus::PROVISIONED))->toBeFalse();
    });

    test('SE5Q9-FR-BASE-026: AccountStatus pins badge colors per lifecycle state', function (): void {
        expect(AccountStatus::ACTIVATED->color())->toBe('info');
        expect(AccountStatus::VERIFIED->color())->toBe('success');
        expect(AccountStatus::SUSPENDED->color())->toBe('error');
        expect(AccountStatus::PROVISIONED->color())->toBe('warning');

        foreach (AccountStatus::cases() as $case) {
            expect($case->color())->toBeString()->not->toBeEmpty();
        }
    });
});

describe('SE5Q9: http and sorting bases', function (): void {
    test('SE5Q9-FR-BASE-020: jsonSuccess, jsonCreated, jsonError, and jsonPaginated envelopes', function (): void {
        $controller = new Se5q9ControllerProbe;

        $ok = $controller->ok(['id' => 1], 'Done');
        expect($ok->getStatusCode())->toBe(200);
        expect($ok->getData(true))->toBe(['success' => true, 'message' => 'Done', 'data' => ['id' => 1]]);

        $created = $controller->created(['id' => 2]);
        expect($created->getStatusCode())->toBe(201);
        expect($created->getData(true)['success'])->toBeTrue();

        $error = $controller->err('Quota full', 400, ['company_id' => ['Full']]);
        expect($error->getStatusCode())->toBe(400);
        expect($error->getData(true))->toBe([
            'success' => false,
            'message' => 'Quota full',
            'errors' => ['company_id' => ['Full']],
        ]);

        $paginator = new LengthAwarePaginator([['id' => 1]], 1, 10, 1);
        $paged = $controller->paged($paginator);
        $payload = $paged->getData(true);
        expect($paged->getStatusCode())->toBe(200);
        expect($payload['data'])->toBe([['id' => 1]]);
        expect($payload['meta'])->toMatchArray(['current_page' => 1, 'per_page' => 10, 'total' => 1]);
    });

    test('SE5Q9-FR-BASE-020: extra payload never overrides reserved envelope keys', function (): void {
        $controller = new Se5q9ControllerProbe;

        $response = $controller->ok(['id' => 1], 'Done', 200, ['success' => false, 'trace_id' => 't-1']);

        expect($response->getData(true))->toBe([
            'success' => true,
            'message' => 'Done',
            'data' => ['id' => 1],
            'trace_id' => 't-1',
        ]);
    });

    test('SE5Q9-FR-BASE-021: failed validation throws a 422 ValidationFailedException', function (): void {
        $request = new Se5q9FormRequestProbe;
        $validator = Validator::make(['name' => ''], $request->rules());

        expect($validator->fails())->toBeTrue();

        try {
            $request->runFailedValidation($validator);
            expect(false)->toBeTrue('expected ValidationFailedException');
        } catch (ValidationFailedException $e) {
            expect($e->statusCode())->toBe(422);
            expect($e->getContext()['errors'])->toHaveKey('name');
        }
    });

    test('SE5Q9-FR-BASE-022: applySorting honors the whitelist and rejects injection', function (): void {
        $probe = new Se5q9SortProbe;

        $probe->sortBy = ['column' => 'name', 'direction' => 'asc'];
        $sql = $probe->runSort(User::query())->toSql();
        expect($sql)->toContain('"name" asc');

        $probe->sortBy = ['column' => 'password; DROP TABLE users; --', 'direction' => 'sideways'];
        $fallback = $probe->runSort(User::query())->toSql();
        expect($fallback)->not->toContain('DROP TABLE');
        expect($fallback)->toContain('"id" desc');
    });

    test('SE5Q9-FR-BASE-023: selection tracks ids, counts, and clears', function (): void {
        $probe = new Se5q9SelectionProbe;

        expect($probe->selected_count())->toBe(0);

        $probe->selectAll(['a', 'b', 'c']);
        expect($probe->selectedIds)->toBe(['a', 'b', 'c']);
        expect($probe->selected_count())->toBe(3);

        $probe->clearSelection();
        expect($probe->selectedIds)->toBe([]);
        expect($probe->selected_count())->toBe(0);
    });

    test('SE5Q9-FR-BASE-019: wizard gates steps, tracks progress, and names the current key', function (): void {
        $wizard = new Se5q9WizardProbe;

        expect($wizard->progressPercent())->toBe(0);
        expect($wizard->currentStepKey())->toBe('alpha');
        expect($wizard->isStepAccessible(2))->toBeFalse();

        $wizard->goToStep(3);
        expect($wizard->currentStep)->toBe(1);

        $wizard->nextStep();
        expect($wizard->currentStep)->toBe(2);
        expect($wizard->isStepCompleted(1))->toBeTrue();
        expect($wizard->isCurrentStep(2))->toBeTrue();
        expect($wizard->progressPercent())->toBe(50);
        expect($wizard->currentStepKey())->toBe('beta');

        $wizard->nextStep();
        expect($wizard->currentStepKey())->toBe('gamma');
        expect($wizard->progressPercent())->toBe(100);

        $wizard->nextStep();
        expect($wizard->currentStep)->toBe(3);

        $wizard->prevStep();
        expect($wizard->currentStep)->toBe(2);

        $wizard->goToStep(1);
        expect($wizard->currentStep)->toBe(1);
    });

    test('SE5Q9-FR-BASE-019: wizard step errors surface without escaping', function (): void {
        $wizard = new Se5q9WizardProbe;

        $wizard->runStepError(fn () => throw new RejectedException('Step rule failed'));

        expect($wizard->currentStep)->toBe(1);
    });

    test('SE5Q9-NFR-BASE-006: base messages resolve in both en and id', function (): void {
        app()->setLocale('en');
        $enCreated = ActionResponse::created()->message;
        $enLabel = CsvRowResult::CREATED->label();

        app()->setLocale('id');
        $idCreated = ActionResponse::created()->message;
        $idLabel = CsvRowResult::CREATED->label();

        expect($enCreated)->toBe('Created successfully.');
        expect($idCreated)->toBe('Berhasil dibuat.');
        expect($enLabel)->not->toBe($idLabel);
    });
});
