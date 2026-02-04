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
    if(!empty($value)){
      // Filter by disease name via the disease relationship
      // gencc-sub stores disease name in diseases.name column, not as submission column
      return $builder->whereHas('submissions.disease', function (Builder $builder) use($value) {
        $builder->where('name', 'like', '%' . $value .'%');
      });
    } else {
      return $builder;
    }

    // $products = Product::whereHas('natures', function ($q) use ($catname) {
    //   $q->where('nature_slug', '=', $catname);
    // });
  }
}
