<?php

namespace App\Query\Filters;

use Illuminate\Database\Eloquent\Builder;

class OrCurationsFromSubmitters implements FilterSubmitters
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

    // $records = Disease::whereHas('submissions', function ($query) use ($item) {
    //   return $query->where('gene_id', '=', $item->id);
    // })->where('type', 'MONDO')->get();

    //dd($value);
    if (!empty($value)) {
      //dd($value);
      //return $builder;
      // $result = Gene::whereHas('submissions.submitter', function ($query) use ($value) {
      //   return $query->whereIn('uuid', $value);
      // })->get();
      return $builder->whereHas('submissions.submitter', function ($query) use ($value) {
        //dd($value);
        return $query->whereIn('uuid', $value);
      });
    } else {
      return $builder->whereHas('submissions');
    }
  }
}
