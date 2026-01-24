<?php

declare(strict_types=1);

namespace Modules\Shared\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/**
 * Trait HasAcademicYear
 *
 * Automatically scopes models by the active academic year and populates the field on creation.
 */
trait HasAcademicYear
{
    /**
     * Boot the trait.
     */
    public static function bootHasAcademicYear(): void
    {
        static::creating(function ($model) {
            if (empty($model->academic_year)) {
                $model->academic_year = setting('active_academic_year');
            }
        });

        static::addGlobalScope('academic_year', function (Builder $builder) {
            $builder->where(
                $builder->getQuery()->from.'.academic_year',
                setting('active_academic_year'),
            );
        });
    }

    /**
     * Scope a query to a specific academic year.
     */
    public function scopeForAcademicYear(Builder $query, string $year): Builder
    {
        return $query->withoutGlobalScope('academic_year')->where('academic_year', $year);
    }
}
