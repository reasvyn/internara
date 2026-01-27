<?php

declare(strict_types=1);

namespace Modules\Shared\Services;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Modules\Shared\Services\Contracts\EloquentQuery as EloquentQueryContract;

/**
 * Provides a base implementation for Eloquent query classes.
 *
 * This abstract class offers a reusable, standardized way to query Eloquent models,
 * with built-in support for filtering, sorting, searching, and caching.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 *
 * @implements EloquentQueryContract<TModel>
 */
abstract class EloquentQuery implements EloquentQueryContract
{
    /**
     * The Eloquent model instance.
     *
     * @var TModel
     */
    protected Model $model;

    /**
     * Columns that can be used for text-based searching.
     *
     * @var list<string>
     */
    protected array $searchable = [];

    /**
     * Columns that can be used for sorting.
     *
     * @var list<string>
     */
    protected array $sortable = [];

    /**
     * A base query builder instance to extend from.
     *
     * @var Builder<TModel>|null
     */
    protected ?Builder $baseQuery = null;

    /**
     * {@inheritdoc}
     */
    public function setModel(Model $model): self
    {
        $this->model = $model;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setBaseQuery(Builder $query): self
    {
        $this->baseQuery = $query;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSearchable(array $columns = []): self
    {
        $this->searchable = $columns;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setSortable(array $columns = []): self
    {
        $this->sortable = $columns;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function paginate(
        array $filters = [],
        int $perPage = 15,
        array $columns = ['*'],
    ): LengthAwarePaginator {
        return $this->query($filters, $columns)->paginate($perPage, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function all(array $columns = ['*']): Collection
    {
        return $this->model->newQuery()->get($columns);
    }

    /**
     * {@inheritdoc}
     */
    public function get(array $filters = [], array $columns = ['*']): Collection
    {
        return $this->query($filters, $columns)->get();
    }

    /**
     * {@inheritdoc}
     */
    public function first(array $filters = [], array $columns = ['*']): ?Model
    {
        return $this->query($filters, $columns)->first();
    }

    /**
     * {@inheritdoc}
     */
    public function firstOrFail(array $filters = [], array $columns = ['*']): Model
    {
        return $this->query($filters, $columns)->firstOrFail();
    }

    /**
     * {@inheritdoc}
     */
    public function find(mixed $id, array $columns = ['*']): ?Model
    {
        return $this->query()->find($id, $columns);
    }

    /**
     * {@inheritdoc}
     */
    public function exists(array $filters = []): bool
    {
        return $this->query($filters)->exists();
    }

    /**
     * {@inheritdoc}
     */
    public function create(array $data): Model
    {
        $filteredData = $this->filterFillable($data);

        try {
            return $this->model->newQuery()->create($filteredData);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->handleQueryException($e, 'creation_failed');
        }
    }

    /**
     * {@inheritdoc}
     */
    public function update(mixed $id, array $data): Model
    {
        $model = $this->find($id);

        if (! $model) {
            throw (new ModelNotFoundException)->setModel(get_class($this->model), [$id]);
        }

        $filteredData = $this->filterFillable($data);

        try {
            $model->update($filteredData);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->handleQueryException($e, 'update_failed');
        }

        return $model;
    }

    /**
     * {@inheritdoc}
     */
    public function save(array $attributes, array $values = []): Model
    {
        $filteredValues = $this->filterFillable($values);

        try {
            return $this->query()->updateOrCreate($attributes, $filteredValues);
        } catch (\Illuminate\Database\QueryException $e) {
            $this->handleQueryException($e, 'save_failed');
        }
    }

    /**
     * Handle QueryException and wrap it in AppException.
     *
     * @throws \Modules\Exception\AppException
     */
    protected function handleQueryException(\Illuminate\Database\QueryException $e, string $defaultKey): never
    {
        $recordName = property_exists($this, 'recordName') ? $this->recordName : 'record';
        $userMessage = 'shared::exceptions.'.($e->getCode() === '23000' ? 'unique_violation' : $defaultKey);

        throw new \Modules\Exception\AppException(
            userMessage: $userMessage,
            replace: ['record' => $recordName, 'column' => 'data'],
            logMessage: $e->getMessage(),
            code: $e->getCode() === '23000' ? 409 : 500,
            previous: $e,
        );
    }

    /**
     * {@inheritdoc}
     */
    public function delete(mixed $id, bool $force = false): bool
    {
        $model = $this->find($id);
        if (! $model) {
            return false;
        }

        return $force ? $model->forceDelete() : $model->delete();
    }

    /**
     * {@inheritdoc}
     */
    public function insert(array $data): bool
    {
        return $this->model->newQuery()->insert($data);
    }

    /**
     * {@inheritdoc}
     */
    public function upsert(array $values, array|string $uniqueBy, ?array $update = null): int
    {
        return $this->model->newQuery()->upsert($values, $uniqueBy, $update);
    }

    /**
     * {@inheritdoc}
     */
    public function destroy(mixed $ids, bool $force = false): int
    {
        $ids = Arr::wrap($ids);

        if ($force) {
            return $this->model
                ->newQuery()
                ->whereIn($this->model->getKeyName(), $ids)
                ->get()
                ->each(fn (Model $model) => $model->forceDelete())
                ->count();
        }

        return $this->model->destroy($ids);
    }

    /**
     * {@inheritdoc}
     */
    public function toArray(array $filters = [], array $columns = ['*']): array
    {
        return $this->query($filters, $columns)->get()->toArray();
    }

    /**
     * {@inheritdoc}
     */
    public function query(array $filters = [], array $columns = ['*']): Builder
    {
        $query = $this->baseQuery ? clone $this->baseQuery : $this->model->newQuery();

        $query->select($columns);

        $this->applyFilters($query, $filters);

        return $query;
    }

    /**
     * {@inheritdoc}
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

        return Cache::remember($cacheKey, $ttl, fn () => $callback($this));
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
        if (isset($filters['sort_by']) && in_array($filters['sort_by'], $this->sortable)) {
            $sortBy = $filters['sort_by'];
            $sortDir = strtolower($filters['sort_dir'] ?? 'asc');

            if (in_array($sortDir, ['asc', 'desc'])) {
                $query->orderBy($sortBy, $sortDir);
            }
            unset($filters['sort_by'], $filters['sort_dir']);
        }

        // Search logic
        if (! empty($this->searchable) && isset($filters['search'])) {
            $search = $filters['search'];
            $query->where(function (Builder $q) use ($search) {
                foreach ($this->searchable as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
            unset($filters['search']);
        }

        // Apply remaining filters
        if (! empty($filters)) {
            foreach ($filters as $key => $value) {
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
     * Filters an array to include only fillable model attributes.
     *
     * @param array<string, mixed> $data
     *
     * @return array<string, mixed>
     */
    protected function filterFillable(array $data): array
    {
        return Arr::only($data, $this->model->getFillable());
    }

    /**
     * {@inheritdoc}
     */
    public function factory(): \Illuminate\Database\Eloquent\Factories\Factory
    {
        $modelClass = get_class($this->model);

        if (method_exists($modelClass, 'factory')) {
            return $modelClass::factory();
        }

        throw new \RuntimeException(
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
    ): \Illuminate\Database\Eloquent\Relations\BelongsTo {
        return $related->belongsTo(get_class($this->model), $foreignKey, $ownerKey, $relation);
    }

    /**
     * {@inheritdoc}
     */
    public function defineHasMany(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): \Illuminate\Database\Eloquent\Relations\HasMany {
        return $related->hasMany(get_class($this->model), $foreignKey, $localKey);
    }

    /**
     * {@inheritdoc}
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): \Illuminate\Database\Eloquent\Relations\HasOne {
        return $related->hasOne(get_class($this->model), $foreignKey, $localKey);
    }
}
