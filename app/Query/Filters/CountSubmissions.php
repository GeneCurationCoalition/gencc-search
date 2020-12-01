<?php

namespace App\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

class CountSubmissions implements Filter
{

  /**
   * Apply a given search value to the builder instance.
   *
   * @param Builder $builder
   * @param mixed $value
   * @return Builder $builder
   */
  public static function apply(Builder $builder, $value)
  {
    //dd($value);
    if($value) {
      //$value = 1;
      return $builder->where('count_submissions','>=', $value);
    } else {
      return $builder;
    }
  }
}
