<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Closure;
use Exception;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\QueryException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Modules\Exception\RecordNotFoundException;
use Modules\Shared\Services\Contracts\EloquentQuery as EloquentQueryContract;
use RuntimeException;

/**
 * Provides a base implementation for Eloquent query classes.
 *
 * This abstract class offers a reusable, standardized way to query Eloquent models,
 * adhering to the "Service-Oriented Logic Execution" philosophy. It centralizes
 * built-in support for filtering, sorting, searching, and caching to ensure
 * single-responsibility and testable domain logic.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @implements EloquentQueryContract<TModel>
 */
abstract class EloquentQuery extends BaseService implements EloquentQueryContract
{
    /**
     * Sort direction constants.
     */
    public const SORT_ASC = 'asc';

    public const SORT_DESC = 'desc';

    /**
     * SQL state code for unique constraint violation (Standardized ISO/IEC 9075).
     */
    protected const SQL_STATE_UNIQUE_VIOLATION = '23000';

    /**
     * The Eloquent model instance that this service orchestrates.
     *
     * @var TModel
     */
    protected Model $model;

    /**
     * Columns that are authorized for text-based searching.
     *
     * @var list<string>
     */
    protected array $searchable = [];

    /**
     * Columns that are authorized for sorting operations.
     *
     * @var list<string>
     */
    protected array $sortable = [];

    /**
     * A base query builder instance to extend from, allowing for complex scoping.
     *
     * @var Builder<TModel>|null
     */
    protected ?Builder $baseQuery = null;

    /**
     * Whether to include soft-deleted records in the resulting query.
     */
    protected bool $withTrashed = false;

    /**
     * Whether to skip authorization checks for the current operation.
     */
    protected bool $skipAuthorization = false;

    /**
     * Set the primary model instance for the service.
     *
     * @param TModel $model
     *
     * @return $this
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * Temporarily disable authorization checks for the next operation.
     *
     * @return $this
     */
    public function withoutAuthorization(): self
    {
        $this->skipAuthorization = true;

        return $this;
    }

    /**
     * Configure the service to include or exclude soft-deleted records.
     *
     * @return $this
     */
    public function withTrashed(bool $value = true): self
    {
        $this->withTrashed = $value;

        return $this;
    }

    /**
     * Define a base query to be used for all subsequent operations.
     *
     * @param Builder<TModel> $query
     *
     * @return $this
     */
    public function setBaseQuery(Builder $query): self
    {
        $this->baseQuery = $query;

        return $this;
    }

    /**
     * Define the set of columns that can be searched via the 'search' filter.
     *
     * @param list<string> $columns
     *
     * @return $this
     */
    public function setSearchable(array $columns = []): self
    {
        $this->searchable = $columns;

        return $this;
    }

    /**
     * Define the set of columns that are authorized for sorting.
     *
     * @param list<string> $columns
     *
     * @return $this
     */
    public function setSortable(array $columns = []): self
    {
        $this->sortable = $columns;

        return $this;
    }

    /**
     * Retrieve all records for the model without any filtering.
     *
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*'], array $with = []): Collection
    {
        return $this->model->newQuery()->with($with)->get($columns);
    }

    /**
     * Retrieve a collection of records matching the provided filters.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @return Collection<int, TModel>
     */
    public function get(array $filters = [], array $columns = ['*'], array $with = []): Collection
    {
        return $this->query($filters, $columns, $with)->get();
    }

    /**
     * Persist or update a record based on matching attributes.
     *
     * @param array<string, mixed> $attributes
     * @param array<string, mixed> $values
     *
     * @throws RuntimeException
     * @throws AuthorizationException
     *
     * @return TModel
     */
    public function save(array $attributes, array $values = []): Model
    {
        // Try to find the existing record to authorize properly
        $searchAttributes = array_filter($attributes, fn($val) => !empty($val));
        $model = !empty($searchAttributes)
            ? $this->model->newQuery()->where($searchAttributes)->first()
            : null;

        if ($model) {
            Gate::authorize('update', $model);
        } else {
            Gate::authorize('create', $this->model);
        }

        $filteredValues = $this->filterFillable($values);

        try {
            return $this->query()->updateOrCreate($attributes, $filteredValues);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'save_failed');
        }
    }

    /**
     * Handle Database exceptions and encapsulate them in a localized AppException.
     *
     *
     * @throws RuntimeException
     */
    protected function handleQueryException(QueryException $e, string $defaultKey): never
    {
        $recordName = property_exists($this, 'recordName') ? $this->recordName : 'record';

        // Use a generic RuntimeException for infrastructure layer
        // The Exception handler will be responsible for mapping this if needed.
        throw new RuntimeException(
            message: "Database error during {$recordName} operation. SQL State: {$e->getCode()}",
            code: $e->getCode() === self::SQL_STATE_UNIQUE_VIOLATION ? 409 : 500,
            previous: $e,
        );
    }

    /**
     * Retrieve the first record matching the filters.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @return TModel|null
     */
    public function first(array $filters = [], array $columns = ['*'], array $with = []): ?Model
    {
        return $this->query($filters, $columns, $with)->first();
    }

    /**
     * Retrieve the first record matching the filters or throw an exception.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @throws RecordNotFoundException
     *
     * @return TModel
     */
    public function firstOrFail(
        array $filters = [],
        array $columns = ['*'],
        array $with = [],
    ): Model
    {
        $model = $this->first($filters, $columns, $with);

        if (!$model) {
            throw new RecordNotFoundException(class_basename($this->model), 0);
        }

        return $model;
    }

    /**
     * Find a record by its primary key.
     *
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @return TModel|null
     */
    public function find(mixed $id, array $columns = ['*'], array $with = []): ?Model
    {
        if (!$this->skipAuthorization) {
            // Authorization will be checked when needed
        }

        return $this->query([], $columns, $with)->find($id);
    }

    /**
     * Find a record by its primary key or throw an exception.
     *
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @throws RecordNotFoundException
     *
     * @return TModel
     */
    public function findOrFail(mixed $id, array $columns = ['*'], array $with = []): Model
    {
        $model = $this->find($id, $columns, $with);

        if (!$model) {
            throw new RecordNotFoundException(class_basename($this->model), $id);
        }

        if (!$this->skipAuthorization) {
            Gate::authorize('view', $model);
        }

        $this->skipAuthorization = false;

        return $model;
    }

    /**
     * Check if records exist matching the filters.
     *
     * @param array<string, mixed> $filters
     */
    public function exists(array $filters = []): bool
    {
        return $this->query($filters)->exists();
    }

    /**
     * Create a new record with the given data.
     *
     * @param array<string, mixed> $data
     *
     * @throws AuthorizationException
     * @throws RuntimeException
     *
     * @return TModel
     */
    public function create(array $data): Model
    {
        if (!$this->skipAuthorization) {
            Gate::authorize('create', $this->model);
        }

        $this->skipAuthorization = false;

        $filteredData = $this->filterFillable($data);

        try {
            $model = $this->model->newInstance($filteredData);
            $model->save();

            return $model;
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'create_failed');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(Model $model, array $data): void
    {
        if (!$this->skipAuthorization) {
            Gate::authorize('update', $model);
        }

        $this->skipAuthorization = false;

        $filteredData = $this->filterFillable($data);

        try {
            $model->update($filteredData);
        } catch (QueryException $e) {
            $this->handleQueryException($e, 'update_failed');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function delete(Model $model): bool
    {
        if (!$this->skipAuthorization) {
            Gate::authorize('delete', $model);
        }

        $this->skipAuthorization = false;

        return (bool) $model->delete();
    }

    /**
     * Perform a low-level bulk insertion of data.
     *
     * @param list<array<string, mixed>> $data
     */
    public function insert(array $data): bool
    {
        return $this->model->newQuery()->insert($data);
    }

    /**
     * Perform a bulk update-or-insert operation.
     *
     * @param list<array<string, mixed>> $values
     * @param list<string>|string $uniqueBy
     * @param list<string>|null $update
     */
    public function upsert(array $values, array|string $uniqueBy, ?array $update = null): int
    {
        return $this->model->newQuery()->upsert($values, $uniqueBy, $update);
    }

    /**
     * Remove multiple records by their identities.
     *
     * @param list<mixed>|mixed $ids
     *
     * @throws AuthorizationException
     */
    public function destroy(mixed $ids, bool $force = false): int
    {
        $ids = Arr::wrap($ids);

        $query = $this->model->newQuery()->whereIn($this->model->getKeyName(), $ids);

        // Security check for each record if authorization is not skipped
        if (!$this->skipAuthorization) {
            $records = $query->get();
            foreach ($records as $record) {
                Gate::authorize('delete', $record);
            }
        }

        $this->skipAuthorization = false;

        if ($force) {
            return $this->model
                ->newQuery()
                ->whereIn($this->model->getKeyName(), $ids)
                ->forceDelete();
        }

        return $this->model->destroy($ids);
    }

    /**
     * Convert the filtered collection of records to a plain array.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $filters = [], array $columns = ['*'], array $with = []): array
    {
        return $this->query($filters, $columns, $with)->get()->toArray();
    }

    /**
     * Construct a fresh query builder instance with applied filters and scoping.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     * @param list<string> $with Relationships to eager load.
     *
     * @return Builder<TModel>
     */
    /**
     * Shorthand to get the total count of records.
     *
     * Equivalent to $this->query()->count().
     */
    public function count(): int
    {
        return $this->query()->count();
    }

    public function query(array $filters = [], array $columns = ['*'], array $with = []): Builder
    {
        $query = $this->baseQuery ? clone $this->baseQuery : $this->model->newQuery();

        if ($this->withTrashed && method_exists($query, 'withTrashed')) {
            $query->withTrashed();
        }

        $query->select($columns)->with($with);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * Execute the callback within a cached context to optimize performance.
     *
     * @param \DateTimeInterface|\DateInterval|int|null $ttl
     * @param Closure($this): mixed $callback
     */
    public function remember(
        string $cacheKey,
        mixed $ttl,
        Closure $callback,
        bool $skipCache = false,
    ): mixed {
        if ($skipCache) {
            Cache::forget($cacheKey);

            return $callback($this);
        }

        return Cache::remember($cacheKey, $ttl, fn() => $callback($this));
    }

    /**
     * Applies registered filters to the query builder.
     *
     * @param Builder<TModel> $query
     * @param array<string, mixed> $filters
     */
    protected function applyFilters(Builder &$query, array &$filters): void
    {
        // Sorting logic
        if (isset($filters['sort_by'])) {
            if (in_array($filters['sort_by'], $this->sortable)) {
                $sortBy = $filters['sort_by'];
                $sortDir = strtolower($filters['sort_dir'] ?? self::SORT_ASC);

                if (in_array($sortDir, [self::SORT_ASC, self::SORT_DESC])) {
                    $query->orderBy($sortBy, $sortDir);
                }
            }
            unset($filters['sort_by'], $filters['sort_dir']);
        }

        // Search logic
        if (!empty($this->searchable) && isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchable as $column) {
                    if (str_contains($column, '.')) {
                        $segments = explode('.', $column);
                        $col = array_pop($segments);
                        $relation = implode('.', $segments);
                        $q->orWhereRelation($relation, $col, 'like', "%{$search}%");
                    } else {
                        $q->orWhere($column, 'like', "%{$search}%");
                    }
                }
            });
            unset($filters['search']);
        }

        // Apply remaining filters
        if (!empty($filters)) {
            foreach ($filters as $key => $value) {
                if ($value === '' || $value === null || (is_array($value) && empty($value))) {
                    continue;
                }

                if (is_string($key) && str_contains($key, '.')) {
                    $segments = explode('.', $key);
                    $column = array_pop($segments);
                    $relation = implode('.', $segments);
                    $query->whereRelation($relation, $column, $value);
                } else {
                    $query->where($key, $value);
                }
            }
        }
    }

    /**
     * Filters the given data to include only fillable attributes.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function filterFillable(array $data): array
    {
        $primaryKey = $this->model->getKeyName();

        return array_filter(
            $data,
            fn($key) => $this->model->isFillable($key) || $key === $primaryKey,
            ARRAY_FILTER_USE_KEY,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function factory(): Factory
    {
        $modelClass = get_class($this->model);

        if (method_exists($modelClass, 'factory')) {
            return $modelClass::factory();
        }

        throw new RuntimeException(
            "Model [{$modelClass}] does not support factories (Missing HasFactory trait).",
        );
    }

    /**
     * {@inheritdoc}
     */
    public function defineBelongsTo(
        Model $related,
        ?string $foreignKey = null,
        ?string $ownerKey = null,
        ?string $relation = null,
    ): BelongsTo {
        return $related->belongsTo(get_class($this->model), $foreignKey, $ownerKey, $relation);
    }

    /**
     * {@inheritdoc}
     */
    public function defineHasMany(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasMany {
        return $related->hasMany(get_class($this->model), $foreignKey, $localKey);
    }

    /**
     * {@inheritdoc}
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): HasOne {
        return $related->hasOne(get_class($this->model), $foreignKey, $localKey);
    }

    /**
     * {@inheritdoc}
     */
    public function import(array $rows): int
    {
        $count = 0;
        foreach ($rows as $row) {
            try {
                $this->create($row);
                $count++;
            } catch (Exception $e) {
                Log::error(
                    'Bulk import failed for row in ' .
                        get_class($this->model) .
                        ': PII REDACTED. Error: ' .
                        $e->getMessage(),
                );
            }
        }

        return $count;
    }
}
