<?php

declare(strict_types=1);

use App\Core\Models\Concerns\HasCommonScopes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;

uses(LazilyRefreshDatabase::class);

class HasCommonScopesTestModel extends Model
{
    use HasCommonScopes;

    protected $table = 'test_common_scopes';

    protected $fillable = ['is_active'];

    public $timestamps = true;
}

beforeEach(function () {
    Schema::create('test_common_scopes', function ($table) {
        $table->id();
        $table->boolean('is_active')->default(true);
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('test_common_scopes');
});

test('scopeActive filters active records', function () {
    $active = HasCommonScopesTestModel::create(['is_active' => true]);
    HasCommonScopesTestModel::create(['is_active' => false]);

    $results = HasCommonScopesTestModel::query()->active()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($active->id);
});

test('scopeInactive filters inactive records', function () {
    HasCommonScopesTestModel::create(['is_active' => true]);
    $inactive = HasCommonScopesTestModel::create(['is_active' => false]);

    $results = HasCommonScopesTestModel::query()->inactive()->get();

    expect($results)->toHaveCount(1);
    expect($results->first()->id)->toBe($inactive->id);
});

test('scopeRecent limits records', function () {
    HasCommonScopesTestModel::create(['is_active' => true]);
    HasCommonScopesTestModel::create(['is_active' => true]);
    HasCommonScopesTestModel::create(['is_active' => true]);

    $results = HasCommonScopesTestModel::query()->recent(2)->get();

    expect($results)->toHaveCount(2);
});

test('scopeCreatedAfter filters by date', function () {
    HasCommonScopesTestModel::create(['is_active' => true]);
    HasCommonScopesTestModel::create(['is_active' => true]);

    $results = HasCommonScopesTestModel::query()->createdAfter(now()->subMinute()->toDateTimeString())->get();

    expect($results)->toHaveCount(2);
});

test('scopeCreatedBefore filters by date', function () {
    HasCommonScopesTestModel::create(['is_active' => true]);

    $results = HasCommonScopesTestModel::query()->createdBefore(now()->addMinute()->toDateTimeString())->get();

    expect($results)->toHaveCount(1);
});

test('scopeOrdered orders by column', function () {
    $ascSql = HasCommonScopesTestModel::query()->ordered('created_at', 'asc')->toSql();
    $descSql = HasCommonScopesTestModel::query()->ordered('created_at', 'desc')->toSql();

    expect($ascSql)->toContain('order by "created_at" asc');
    expect($descSql)->toContain('order by "created_at" desc');
});

test('scopeOrdered defaults to created_at desc', function () {
    HasCommonScopesTestModel::create(['is_active' => true]);
    $second = HasCommonScopesTestModel::create(['is_active' => true]);

    $results = HasCommonScopesTestModel::query()->ordered()->get();
    $orderSql = HasCommonScopesTestModel::query()->ordered()->toSql();

    expect($orderSql)->toContain('order by "created_at" desc');
    expect($results)->toHaveCount(2);
});
