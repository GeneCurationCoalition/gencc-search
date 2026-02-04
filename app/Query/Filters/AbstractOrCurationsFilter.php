<?php

namespace App\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

abstract class AbstractOrCurationsFilter implements Filter
{
    /**
     * The database column name for this curation type.
     */
    abstract protected static function column(): string;

    /**
     * Apply a given search value to the builder instance.
     *
     * @param Builder $builder
     * @param mixed $value
     * @return Builder
     */
    public static function apply(Builder $builder, $value): Builder
    {
        if ($value == 0) {
            return $builder;
        }

        return $builder->orWhere(static::column(), '>=', $value)->whereHas('submissions');
    }
}
