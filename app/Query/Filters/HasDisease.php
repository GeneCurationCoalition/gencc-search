<?php

namespace App\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

class HasDisease implements Filter
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
    //return $builder->where('title', 'LIKE', '%' . $value . '%');
    if(!empty($value)){
      return $builder->whereHas('submissions', function (Builder $builder) use($value) {
        //dd($value);
        $builder->where('submitted_as_disease_name', 'like', '%' . $value .'%');
      });
    } else {
      return $builder;
    }

    // $products = Product::whereHas('natures', function ($q) use ($catname) {
    //   $q->where('nature_slug', '=', $catname);
    // });
  }
}
