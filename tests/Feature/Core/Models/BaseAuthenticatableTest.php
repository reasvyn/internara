<?php

declare(strict_types=1);

namespace Tests\Feature\Core\Models;

use App\Core\Models\BaseAuthenticatable;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

uses(LazilyRefreshDatabase::class);

class TestAuthModel extends BaseAuthenticatable
{
    protected $table = 'test_auth_models';

    protected $fillable = ['name', 'email', 'password', 'is_active', 'created_at'];
}

function ensureTestAuthTable(): void
{
    if (! Schema::hasTable('test_auth_models')) {
        Schema::create('test_auth_models', function ($table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('email')->unique();
            $table->string('password')->default('secret');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }
}

describe('BaseAuthenticatable', function () {
    it('extends authenticatable', function () {
        $model = new TestAuthModel;

        expect($model)->toBeInstanceOf(Authenticatable::class);
    });

    it('auto-generates uuid primary key', function () {
        ensureTestAuthTable();

        $user = TestAuthModel::create([
            'name' => 'John',
            'email' => 'john_auth@example.com',
        ]);

        expect($user->id)->toBeString();
        expect(Str::isUuid($user->id))->toBeTrue();
    });

    it('scope active returns only active records', function () {
        ensureTestAuthTable();

        TestAuthModel::create(['name' => 'Active A', 'email' => 'a_active@test.com', 'is_active' => true]);
        TestAuthModel::create(['name' => 'Inactive B', 'email' => 'b_inactive@test.com', 'is_active' => false]);
        TestAuthModel::create(['name' => 'Active C', 'email' => 'c_active@test.com', 'is_active' => true]);

        $active = TestAuthModel::active()->get();

        expect($active)->toHaveCount(2);
        expect($active->pluck('name')->toArray())->toEqualCanonicalizing(['Active A', 'Active C']);
    });

    it('scope inactive returns only inactive records', function () {
        ensureTestAuthTable();

        TestAuthModel::create(['name' => 'Active A', 'email' => 'a_inact@test.com', 'is_active' => true]);
        TestAuthModel::create(['name' => 'Inactive B', 'email' => 'b_inact@test.com', 'is_active' => false]);

        $inactive = TestAuthModel::inactive()->get();

        expect($inactive)->toHaveCount(1);
        expect($inactive->first()->name)->toBe('Inactive B');
    });

    it('scope recent limits results', function () {
        ensureTestAuthTable();

        TestAuthModel::create(['name' => 'Old', 'email' => 'old_recent@test.com', 'created_at' => now()->subDays(10)]);
        TestAuthModel::create(['name' => 'Recent A', 'email' => 'a_recent@test.com']);
        TestAuthModel::create(['name' => 'Recent B', 'email' => 'b_recent@test.com']);

        $recent = TestAuthModel::recent(2)->get();

        expect($recent)->toHaveCount(2);
        expect($recent->pluck('name')->toArray())->toContain('Recent A', 'Recent B');
    });

    it('scope createdAfter filters by date', function () {
        ensureTestAuthTable();

        TestAuthModel::create(['name' => 'Before', 'email' => 'before_date@test.com', 'created_at' => now()->subDays(5)]);
        TestAuthModel::create(['name' => 'After', 'email' => 'after_date@test.com', 'created_at' => now()]);

        $result = TestAuthModel::createdAfter(now()->subDay()->toDateString())->get();

        expect($result)->toHaveCount(1);
        expect($result->first()->name)->toBe('After');
    });

    it('scope createdBefore filters by date', function () {
        ensureTestAuthTable();

        TestAuthModel::create(['name' => 'Before', 'email' => 'before_date2@test.com', 'created_at' => now()->subDays(5)]);
        TestAuthModel::create(['name' => 'After', 'email' => 'after_date2@test.com', 'created_at' => now()]);

        $result = TestAuthModel::createdBefore(now()->subDay()->toDateString())->get();

        expect($result)->toHaveCount(1);
        expect($result->first()->name)->toBe('Before');
    });

    it('scope ordered sorts by given column', function () {
        ensureTestAuthTable();

        TestAuthModel::create(['name' => 'B', 'email' => 'b_order@test.com']);
        TestAuthModel::create(['name' => 'A', 'email' => 'a_order@test.com']);
        TestAuthModel::create(['name' => 'C', 'email' => 'c_order@test.com']);

        $ordered = TestAuthModel::ordered('name', 'asc')->get();

        expect($ordered->pluck('name')->toArray())->toBe(['A', 'B', 'C']);
    });

    it('scope ordered defaults to created_at desc', function () {
        ensureTestAuthTable();

        TestAuthModel::create(['name' => 'First', 'email' => 'first_order@test.com', 'created_at' => now()->subHour()]);
        TestAuthModel::create(['name' => 'Second', 'email' => 'second_order@test.com', 'created_at' => now()]);

        $ordered = TestAuthModel::ordered()->get();

        expect($ordered->first()->name)->toBe('Second');
    });
});
