<?php

declare(strict_types=1);

use App\Modules\Core\Models\BaseModel;
use App\Modules\User\Models\User;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

final class TestCommonScopesModel extends BaseModel
{
    protected $table = 'users';
}

test('SE5Q9-FR-M1: models built on BaseModel get UUID keys', function () {
    $user = User::factory()->create();

    expect(Str::isUuid($user->id))->toBeTrue();
});

test('SE5Q9-FR-M6: active() and inactive() scope on the is_active column', function () {
    User::factory()->create(['is_active' => true]);
    User::factory()->create(['is_active' => false]);

    expect(TestCommonScopesModel::active()->count())->toBe(1);
    expect(TestCommonScopesModel::inactive()->count())->toBe(1);
});

test('SE5Q9-FR-M6: recent() limits to the latest N records', function () {
    User::factory()->count(3)->create();

    expect(TestCommonScopesModel::recent(2)->get())->toHaveCount(2);
});

test('SE5Q9-FR-M6: createdAfter() and createdBefore() filter by created_at', function () {
    User::factory()->create(['created_at' => now()->subDays(2)]);
    User::factory()->create(['created_at' => now()->subDays(5)]);

    expect(TestCommonScopesModel::createdAfter(now()->subDays(3))->count())->toBe(1);
    expect(TestCommonScopesModel::createdBefore(now()->subDays(3))->count())->toBe(1);
});

test('SE5Q9-FR-M6: ordered() sorts by a column and direction', function () {
    $older = User::factory()->create(['created_at' => now()->subDays(10)]);
    $newer = User::factory()->create(['created_at' => now()->subDays(1)]);

    expect(TestCommonScopesModel::ordered()->get()->first()->id)->toBe($newer->id);
    expect(TestCommonScopesModel::ordered('created_at', 'asc')->get()->first()->id)->toBe($older->id);
});
