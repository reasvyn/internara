<?php

declare(strict_types=1);

use App\Core\Models\BaseAuthenticatable;
use App\Core\Models\BaseModel;
use App\Core\Models\Concerns\HasCommonScopes;
use App\User\Models\User;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

final class TestCommonScopesModel extends BaseModel
{
    protected $table = 'users';
}

it('SE5Q9-FR-M1: BaseModel is abstract, extends Model, uses HasUuids + HasCommonScopes', function () {
    $reflection = new ReflectionClass(BaseModel::class);
    $traits = class_uses_recursive(BaseModel::class);

    expect($reflection->isAbstract())->toBeTrue();
    expect($reflection->getParentClass()->getName())->toBe(Model::class);
    expect($traits)->toContain(HasUuids::class);
    expect($traits)->toContain(HasCommonScopes::class);
});

it('SE5Q9-FR-M2: BaseAuthenticatable extends Authenticatable with UUID support', function () {
    $reflection = new ReflectionClass(BaseAuthenticatable::class);
    $traits = class_uses_recursive(BaseAuthenticatable::class);

    expect($reflection->isAbstract())->toBeTrue();
    expect($reflection->getParentClass()->getName())->toBe(Authenticatable::class);
    expect($traits)->toContain(HasUuids::class);
    expect($traits)->toContain(HasCommonScopes::class);
});

it('SE5Q9-FR-M1: models built on BaseAuthenticatable get UUID keys', function () {
    $user = User::factory()->create();

    expect(Str::isUuid($user->id))->toBeTrue();
});

it('SE5Q9-FR-M6: active() and inactive() scope on the is_active column', function () {
    User::factory()->create(['is_active' => true]);
    User::factory()->create(['is_active' => false]);

    expect(TestCommonScopesModel::active()->count())->toBe(1);
    expect(TestCommonScopesModel::inactive()->count())->toBe(1);
});

it('SE5Q9-FR-M6: recent() limits to the latest N records', function () {
    User::factory()->count(3)->create();

    expect(TestCommonScopesModel::recent(2)->get())->toHaveCount(2);
});

it('SE5Q9-FR-M6: createdAfter() and createdBefore() filter by created_at', function () {
    User::factory()->create(['created_at' => now()->subDays(2)]);
    User::factory()->create(['created_at' => now()->subDays(5)]);

    expect(TestCommonScopesModel::createdAfter(now()->subDays(3))->count())->toBe(1);
    expect(TestCommonScopesModel::createdBefore(now()->subDays(3))->count())->toBe(1);
});

it('SE5Q9-FR-M6: ordered() sorts by a column and direction', function () {
    $older = User::factory()->create(['created_at' => now()->subDays(10)]);
    $newer = User::factory()->create(['created_at' => now()->subDays(1)]);

    expect(TestCommonScopesModel::ordered()->get()->first()->id)->toBe($newer->id);
    expect(TestCommonScopesModel::ordered('created_at', 'asc')->get()->first()->id)->toBe($older->id);
});
