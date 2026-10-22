<?php

declare(strict_types=1);

namespace Tests\Unit\Core;

use App\Modules\Core\Exceptions\ValidationFailedException;
use App\Modules\Core\Http\Controllers\BaseController;
use App\Modules\Core\Http\Requests\BaseFormRequest;
use App\Modules\Core\Policies\Concerns\AuthorizesOwnership;
use App\Modules\Core\Policies\Concerns\AuthorizesRoles;
use App\Modules\Core\Services\AppIntegrity;
use App\Modules\User\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;

uses(LazilyRefreshDatabase::class);

class DummyConcreteFormRequest extends BaseFormRequest
{
    public function rules(): array
    {
        return ['name' => 'required|string'];
    }
}

class DummyConcreteController extends BaseController
{
    public function testSuccess(): Response
    {
        return $this->jsonSuccess(['id' => 10], 'Success Message', Response::HTTP_OK, ['meta_key' => 'meta_val']);
    }

    public function testCreated(): Response
    {
        return $this->jsonCreated(['id' => 20]);
    }

    public function testDeleted(): Response
    {
        return $this->jsonDeleted();
    }

    public function testPaginated(LengthAwarePaginator $paginator): Response
    {
        return $this->jsonPaginated($paginator);
    }

    public function testError(): Response
    {
        return $this->jsonError('Bad request', Response::HTTP_BAD_REQUEST, ['detail' => 'invalid']);
    }

    public function testValidationError(): Response
    {
        return $this->jsonValidationError(['field' => ['The field is required.']]);
    }

    public function testNotFound(): Response
    {
        return $this->jsonNotFound();
    }

    public function testForbidden(): Response
    {
        return $this->jsonForbidden();
    }
}

class DummyConcretePolicy
{
    use AuthorizesOwnership;
    use AuthorizesRoles;

    public function checkOwner(Model $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwner($user, $model, $foreignKey);
    }

    public function checkRelatedThrough(Model $user, Model $model, string $relation, string $foreignKey = 'id'): bool
    {
        return $this->isRelatedThrough($user, $model, $relation, $foreignKey);
    }

    public function checkOwnerOrAdmin(Model $user, Model $model, string $foreignKey = 'user_id'): bool
    {
        return $this->isOwnerOrAdmin($user, $model, $foreignKey);
    }

    public function checkAdmin(Model $user): bool
    {
        return $this->isAdmin($user);
    }

    public function checkRoles(Model $user, array $roles): bool
    {
        return $this->hasAnyOfRoles($user, $roles);
    }

    public function checkManageAnyRole(Model $user): bool
    {
        return $this->canManageAnyRole($user);
    }
}

describe('SE5Q9: Core BaseController, BaseFormRequest, and Policy Concerns', function (): void {
    test('SE5Q9-UT-CORE-010: BaseFormRequest throws ValidationFailedException on failed validation', function (): void {
        $request = new DummyConcreteFormRequest;
        $validator = Validator::make([], $request->rules());

        expect($request->authorize())->toBeTrue()
            ->and($request->rules())->toHaveKey('name');

        $ref = new \ReflectionMethod(BaseFormRequest::class, 'failedValidation');
        $ref->setAccessible(true);

        expect(fn () => $ref->invoke($request, $validator))->toThrow(ValidationFailedException::class);
    });

    test('SE5Q9-UT-CORE-011: BaseController returns standard API JSON envelopes across all response types', function (): void {
        $controller = new DummyConcreteController;

        // jsonSuccess
        $success = $controller->testSuccess();
        $data = json_decode((string) $success->getContent(), true);
        expect($success->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($data['success'])->toBeTrue()
            ->and($data['data']['id'])->toBe(10)
            ->and($data['meta_key'])->toBe('meta_val');

        // jsonCreated
        $created = $controller->testCreated();
        expect($created->getStatusCode())->toBe(Response::HTTP_CREATED);

        // jsonDeleted
        $deleted = $controller->testDeleted();
        expect($deleted->getStatusCode())->toBe(Response::HTTP_OK);

        // jsonPaginated
        $paginator = new LengthAwarePaginator(['item1', 'item2'], 2, 10, 1);
        $paginated = $controller->testPaginated($paginator);
        $pData = json_decode((string) $paginated->getContent(), true);
        expect($paginated->getStatusCode())->toBe(Response::HTTP_OK)
            ->and($pData['data'])->toBe(['item1', 'item2'])
            ->and($pData['meta']['total'])->toBe(2);

        // jsonError
        $error = $controller->testError();
        expect($error->getStatusCode())->toBe(Response::HTTP_BAD_REQUEST);

        // jsonValidationError
        $valError = $controller->testValidationError();
        expect($valError->getStatusCode())->toBe(Response::HTTP_UNPROCESSABLE_ENTITY);

        // jsonNotFound
        $notFound = $controller->testNotFound();
        expect($notFound->getStatusCode())->toBe(Response::HTTP_NOT_FOUND);

        // jsonForbidden
        $forbidden = $controller->testForbidden();
        expect($forbidden->getStatusCode())->toBe(Response::HTTP_FORBIDDEN);
    });

    test('SE5Q9-UT-CORE-012: AuthorizesOwnership and AuthorizesRoles correctly evaluate permissions', function (): void {
        $policy = new DummyConcretePolicy;

        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $student = User::factory()->create();
        $student->assignRole('student');

        $otherStudent = User::factory()->create();
        $otherStudent->assignRole('student');

        // Owner check
        $dummyModel = new class extends Model
        {
            public string $user_id = '';
        };
        $dummyModel->user_id = (string) $student->id;

        expect($policy->checkOwner($student, $dummyModel))->toBeTrue()
            ->and($policy->checkOwner($otherStudent, $dummyModel))->toBeFalse();

        // Owner or Admin check
        expect($policy->checkOwnerOrAdmin($student, $dummyModel))->toBeTrue()
            ->and($policy->checkOwnerOrAdmin($admin, $dummyModel))->toBeTrue()
            ->and($policy->checkOwnerOrAdmin($otherStudent, $dummyModel))->toBeFalse();

        // Admin and Role checks
        expect($policy->checkAdmin($admin))->toBeTrue()
            ->and($policy->checkAdmin($student))->toBeFalse()
            ->and($policy->checkManageAnyRole($admin))->toBeTrue()
            ->and($policy->checkRoles($student, ['student', 'teacher']))->toBeTrue()
            ->and($policy->checkRoles($student, ['teacher']))->toBeFalse();
    });

    test('SE5Q9-UT-CORE-013: AppIntegrity verifies system metadata without throwing in test environment', function (): void {
        // In local/testing environment, AppIntegrity should execute cleanly
        expect(fn () => AppIntegrity::verify())->not->toThrow(\Throwable::class);
    });
});
