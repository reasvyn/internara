<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Contracts;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Defines a standard contract for repository-like query classes.
 *
 * This interface provides a consistent API for performing common Eloquent operations,
 * including filtering, pagination, creation, and caching.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface EloquentQuery
{
    /**
     * Retrieves paginated records, applying optional filters.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return LengthAwarePaginator<TModel>
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
    ): LengthAwarePaginator;

    /**
     * Retrieves all records, optionally limited to specific columns.
     *
     * @param list<string> $columns
     *
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Retrieves records based on a set of filters.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return Collection<int, TModel>
     */
    public function get(array $filters = [], array $columns = ['*']): Collection;

    /**
     * Retrieves the first record matching the given filters.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return TModel|null
     */
    public function first(array $filters = [], array $columns = ['*']): ?Model;

    /**
     * Retrieves the first record matching the filters or fails.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<TModel>
     *
     * @return TModel
     */
    public function firstOrFail(array $filters = [], array $columns = ['*']): Model;

    /**
     * Finds a record by its primary key.
     *
     * @param list<string> $columns
     *
     * @return TModel|null
     */
    public function find(mixed $id, array $columns = ['*']): ?Model;

    /**
     * Checks if any records exist that match the given filters.
     *
     * @param array<string, mixed> $filters
     */
    public function exists(array $filters = []): bool;

    /**
     * Creates a new record with the given data.
     *
     * @param array<string, mixed> $data
     *
     * @return TModel
     */
    public function create(array $data): Model;

    /**
     * Updates a record by its primary key with the given data.
     *
     * @param array<string, mixed> $data
     *
     * @return TModel
     */
    public function update(mixed $id, array $data): Model;

    /**
     * Updates an existing record or creates a new one.
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     *
     * @return TModel
     */
    public function save(array $attributes, array $values = []): Model;

    /**
     * Deletes a record by its primary key.
     *
     * @param bool $force Permanently delete if true.
     */
    public function delete(mixed $id, bool $force = false): bool;

    /**
     * Performs a bulk insert operation.
     *
     * @param list<array<string, mixed>> $data
     */
    public function insert(array $data): bool;

    /**
     * Performs a bulk "upsert" operation.
     *
     * @param list<array<string, mixed>> $values
     * @param list<string>|string $uniqueBy
     * @param list<string>|null $update
     */
    public function upsert(array $values, array|string $uniqueBy, ?array $update = null): int;

    /**
     * Destroys multiple records by their primary keys.
     *
     * @param Collection<int, mixed>|list<mixed>|mixed $ids
     * @param bool $force Permanently delete if true.
     */
    public function destroy(mixed $ids, bool $force = false): int;

    /**
     * Retrieves filtered records as a plain array.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $filters = [], array $columns = ['*']): array;

    /**
     * Gets a new query builder instance with optional filters applied.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return Builder<TModel>
     */
    public function query(array $filters = [], array $columns = ['*']): Builder;

    /**
     * Retrieves an item from cache, or executes the callback and caches the result.
     *
     * @param \DateTimeInterface|\DateInterval|int $ttl
     * @param Closure(static): mixed $callback
     * @param bool $skipCache If true, bypasses the cache and executes the callback directly.
     */
    public function remember(
        string $cacheKey,
        mixed $ttl,
        Closure $callback,
        bool $skipCache = false,
    ): mixed;

    /**
     * Sets the Eloquent model instance for the query.
     *
     * @param TModel $model
     *
     * @return static
     */
    public function setModel(Model $model): self;

    /**
     * Sets a base query to build upon.
     *
     * @param Builder<TModel> $query
     *
     * @return static
     */
    public function setBaseQuery(Builder $query): self;

    /**
     * Defines the columns that are searchable.
     *
     * @param list<string> $columns
     *
     * @return static
     */
    public function setSearchable(array $columns = []): self;

    /**
     * Defines the columns that are sortable.
     *
     * @param list<string> $columns
     *
     * @return static
     */
    public function setSortable(array $columns = []): self;

    /**
     * Get a new factory instance for the model.
     *
     *
     * @throws \RuntimeException If the model does not have a factory.
     */
    public function factory(): \Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * Define a polymorphic-style inverse one-to-one or many relationship from the service's model perspective.
     * This acts as a wrapper for $relatedModel->belongsTo(ServiceBoundModel::class).
     *
     * @param Model $related The model instance that "belongs to" the service's model.
     * @param string|null $foreignKey The foreign key on the related model.
     * @param string|null $ownerKey The owner key on the service's model.
     * @param string|null $relation The relation name.
     */
    public function defineBelongsTo(
        Model $related,
        ?string $foreignKey = null,
        ?string $ownerKey = null,
        ?string $relation = null,
    ): \Illuminate\Database\Eloquent\Relations\BelongsTo;

    /**
     * Define a one-to-many relationship from the service's model perspective.
     * This acts as a wrapper for $relatedModel->hasMany(ServiceBoundModel::class).
     * Note: This is usually inverted logic. Typically services manage the parent.
     *
     * @param Model $related The model instance that "has many" of the service's model.
     */
    public function defineHasMany(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): \Illuminate\Database\Eloquent\Relations\HasMany;

    /**
     * Define a one-to-one relationship from the service's model perspective.
     * This acts as a wrapper for $relatedModel->hasOne(ServiceBoundModel::class).
     *
     * @param Model $related The model instance that "has one" of the service's model.
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): \Illuminate\Database\Eloquent\Relations\HasOne;
}
