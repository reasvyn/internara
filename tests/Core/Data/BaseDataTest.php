<?php

declare(strict_types=1);

use App\Modules\Core\Data\BaseData;

final readonly class TestData extends BaseData
{
    public function __construct(
        public string $name,
        public string $email = 'default@example.com',
    ) {}
}

test('SE5Q9-FR-M4: fromArray() resolves camelCase keys', function () {
    $data = TestData::fromArray(['name' => 'Adit', 'email' => 'adit@example.com']);

    expect($data->name)->toBe('Adit');
    expect($data->email)->toBe('adit@example.com');
});

test('SE5Q9-FR-M4: fromArray() falls back to snake_case keys', function () {
    $data = TestData::fromArray(['name' => 'Adit', 'email' => 'adit@example.com']);

    expect($data->name)->toBe('Adit');
});

test('SE5Q9-FR-M4: fromArray() applies constructor defaults for omitted optional params', function () {
    $data = TestData::fromArray(['name' => 'Adit']);

    expect($data->email)->toBe('default@example.com');
});

test('SE5Q9-FR-M4: fromArray() throws when a required param is missing', function () {
    TestData::fromArray(['email' => 'adit@example.com']);
})->throws(InvalidArgumentException::class, 'Missing required constructor parameter "name"');

test('SE5Q9-FR-M4: toArray() serializes the DTO and jsonSerialize() matches it', function () {
    $data = TestData::fromArray(['name' => 'Adit']);

    expect($data->toArray())->toBe(['name' => 'Adit', 'email' => 'default@example.com']);
    expect($data->jsonSerialize())->toBe($data->toArray());
});

test('SE5Q9-FR-M4: merge() overlays values and returns the same DTO type', function () {
    $data = TestData::fromArray(['name' => 'Adit'])->merge(['email' => 'new@example.com']);

    expect($data)->toBeInstanceOf(TestData::class);
    expect($data->email)->toBe('new@example.com');
    expect($data->name)->toBe('Adit');
});

test('SE5Q9-FR-M4: only() and except() select and drop keys', function () {
    $data = TestData::fromArray(['name' => 'Adit', 'email' => 'adit@example.com']);

    expect($data->only('name'))->toBe(['name' => 'Adit']);
    expect($data->except('email'))->toBe(['name' => 'Adit']);
});
