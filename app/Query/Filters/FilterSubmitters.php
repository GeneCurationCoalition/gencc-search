<?php

namespace App\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

interface FilterSubmitters
{
  /**
   * Apply a given search value to the builder instance.
   *
   * @param Builder $builder
   * @param mixed $value
   * @return Builder $builder
   */
  public static function apply(Builder $builder, $value);
}
