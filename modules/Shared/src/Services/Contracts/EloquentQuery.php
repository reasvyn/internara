<?php

declare(strict_types=1);

namespace Modules\Shared\Services\Contracts;

use Closure;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

/**
 * Defines the standard contract for domain-specific query and persistence logic.
 *
 * This interface acts as the foundation for the Service Layer, ensuring that
 * data access follows consistent patterns across all modules. It facilitates
 * the enforcement of SLRI (Software-Level Referential Integrity) by
 * centralizing validation and persistence rules within the Service boundary.
 *
 * @template TModel of \Illuminate\Database\Eloquent\Model
 */
interface EloquentQuery
{
    /**
     * Default number of items per page for institutional data delivery.
     */
    public const DEFAULT_PER_PAGE = 15;

    /**
     * Retrieves a paginated segment of records with active filtering.
     *
     * Facilitates the Mobile-First mandate by delivering lightweight data
     * chunks suitable for responsive UI lists.
     *
     * @param array<string, mixed> $filters Contextual criteria (search, sort, scopes).
     * @param list<string> $columns Specific attributes required by the consumer.
     *
     * @return LengthAwarePaginator<TModel> The paginated domain collection.
     */
    public function paginate(
        array $filters = [],
        int $perPage = self::DEFAULT_PER_PAGE,
        array $columns = ['*'],
    ): LengthAwarePaginator;

    /**
     * Retrieves the entire collection of domain records.
     *
     * Note: Use sparingly for high-volume entities to prevent memory heap
     * exhaustion. Prefer pagination for user-facing lists.
     *
     * @param list<string> $columns
     *
     * @return Collection<int, TModel>
     */
    public function all(array $columns = ['*']): Collection;

    /**
     * Retrieves a filtered collection of records without pagination.
     *
     * Commonly used for internal service-to-service data synchronization
     * or populating institutional selectors.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return Collection<int, TModel>
     */
    public function get(array $filters = [], array $columns = ['*']): Collection;

    /**
     * Retrieves the singular leading record matching the criteria.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return TModel|null The leading entity or null if zero-match.
     */
    public function first(array $filters = [], array $columns = ['*']): ?Model;

    /**
     * Retrieves the leading record or terminates with a secure exception.
     *
     * Ensures that business logic does not proceed with missing data,
     * satisfying the "Fail Securely" invariant.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @throws \Illuminate\Database\Eloquent\ModelNotFoundException<TModel>
     */
    public function firstOrFail(array $filters = [], array $columns = ['*']): Model;

    /**
     * Finds a specific entity by its authoritative primary key.
     *
     * This is the primary method for cross-module identity lookup via
     * Service Contracts.
     *
     * @param list<string> $columns
     *
     * @return TModel|null
     */
    public function find(mixed $id, array $columns = ['*']): ?Model;

    /**
     * Verifies the physical existence of records matching the criteria.
     *
     * Acts as the PEP (Policy Enforcement Point) for verifying foreign
     * identities across module boundaries without direct model access.
     *
     * @param array<string, mixed> $filters
     */
    public function exists(array $filters = []): bool;

    /**
     * Persists a new domain entity into the database registry.
     *
     * Orchestrates the transition from raw input to a formal domain entity,
     * applying UUID generation and default status assignments.
     *
     * @param array<string, mixed> $data Validated input attributes.
     *
     * @return TModel The newly persisted identity.
     */
    public function create(array $data): Model;

    /**
     * Modifies an existing domain entity identified by its primary key.
     *
     * @param array<string, mixed> $data The updated attribute set.
     *
     * @return TModel The updated entity state.
     */
    public function update(mixed $id, array $data): Model;

    /**
     * Atomically updates an existing record or creates a new baseline.
     *
     * Implements the "Idempotent Persistence" pattern to ensure system
     * stability during repeated onboarding or setup cycles.
     *
     * @param array<string, mixed> $attributes Unique identifiers for lookup.
     * @param array<string, mixed> $values Attributes to be merged/updated.
     *
     * @return TModel The resulting domain entity.
     */
    public function save(array $attributes, array $values = []): Model;

    /**
     * Removes an entity from the active system registry.
     *
     * This is a destructive operation. Implementation MUST verify that
     * no cross-module dependencies prevent this deletion (SLRI).
     *
     * @param bool $force If true, executes a hard-delete bypassing soft-deletion.
     */
    public function delete(mixed $id, bool $force = false): bool;

    /**
     * Executes a high-performance bulk record insertion.
     *
     * Warning: This method may bypass certain model-level events. Use only
     * for high-volume technical data transitions.
     *
     * @param list<array<string, mixed>> $data Array of attribute sets.
     */
    public function insert(array $data): bool;

    /**
     * Performs an atomic bulk "upsert" across multiple entities.
     *
     * @param list<array<string, mixed>> $values
     * @param list<string>|string $uniqueBy Identifiers for existence check.
     * @param list<string>|null $update Attributes to refresh on match.
     */
    public function upsert(array $values, array|string $uniqueBy, ?array $update = null): int;

    /**
     * Destroys a set of entities identified by their primary keys.
     *
     * @param Collection<int, mixed>|list<mixed>|mixed $ids
     * @param bool $force Permanently delete if true.
     */
    public function destroy(mixed $ids, bool $force = false): int;

    /**
     * Converts a filtered collection into a projects standard array.
     *
     * Useful for delivering data to external APIs or lightweight
     * front-end components.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(array $filters = [], array $columns = ['*']): array;

    /**
     * Retrieves a scoped Query Builder instance for advanced orchestration.
     *
     * Allows consumers to build complex queries while adhering to the
     * service's baseline filtering and authorization scopes.
     *
     * @param array<string, mixed> $filters
     * @param list<string> $columns
     *
     * @return Builder<TModel>
     */
    public function query(array $filters = [], array $columns = ['*']): Builder;

    /**
     * Orchestrates cached data delivery with authoritative fallback.
     *
     * Implements the "Read-Through" caching pattern to enhance system
     * performance for institutional static data.
     *
     * @param \DateTimeInterface|\DateInterval|int $ttl Time-to-live.
     * @param Closure(static): mixed $callback Logic to execute on cache-miss.
     * @param bool $skipCache If true, bypasses the persistence cache layer.
     */
    public function remember(
        string $cacheKey,
        mixed $ttl,
        Closure $callback,
        bool $skipCache = false,
    ): mixed;

    /**
     * Binds a specific Eloquent model instance to the query orchestrator.
     *
     * @param TModel $model
     *
     * @return static
     */
    public function setModel(Model $model): self;

    /**
     * Configures the orchestrator to include or exclude soft-deleted records.
     *
     * @return static
     */
    public function withTrashed(bool $value = true): self;

    /**
     * Sets the foundational query baseline for the orchestrator.
     *
     * @param Builder<TModel> $query
     *
     * @return static
     */
    public function setBaseQuery(Builder $query): self;

    /**
     * Formalizes the list of technical identifiers that support searching.
     *
     * @param list<string> $columns
     *
     * @return static
     */
    public function setSearchable(array $columns = []): self;

    /**
     * Formalizes the list of technical identifiers that support sorting.
     *
     * @param list<string> $columns
     *
     * @return static
     */
    public function setSortable(array $columns = []): self;

    /**
     * Retrieves an executable Factory instance for the bound model.
     *
     * Used exclusively within verification suites to ensure consistent
     * state generation.
     *
     * @throws \RuntimeException If the entity does not support automated generation.
     */
    public function factory(): \Illuminate\Database\Eloquent\Factories\Factory;

    /**
     * Defines a semantic 'Belongs To' relationship bridge.
     */
    public function defineBelongsTo(
        Model $related,
        ?string $foreignKey = null,
        ?string $ownerKey = null,
        ?string $relation = null,
    ): \Illuminate\Database\Eloquent\Relations\BelongsTo;

    /**
     * Defines a semantic 'Has Many' relationship bridge.
     */
    public function defineHasMany(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): \Illuminate\Database\Eloquent\Relations\HasMany;

    /**
     * Defines a semantic 'Has One' relationship bridge.
     */
    public function defineHasOne(
        Model $related,
        ?string $foreignKey = null,
        ?string $localKey = null,
    ): \Illuminate\Database\Eloquent\Relations\HasOne;
}
