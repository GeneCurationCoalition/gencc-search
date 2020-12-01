<?php

namespace App\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

class OrCurationsDefinitive implements Filter
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
    if($value == 0) {
      //dd("hello");
      //$value = 1;
      return $builder;
    } else {
      //return $builder;
      return $builder->orwhere('curations_definitive', '>=', $value)->whereHas('submissions');
    }
  }
}
