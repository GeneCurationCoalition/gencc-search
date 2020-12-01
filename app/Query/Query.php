<?php

namespace App\Query;

use App\Gene;
use Illuminate\Http\Request;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class Query
{

  public static function apply($filters)
  {
    //$query = static::applyDecoratorsFromQuery($filters, (new Gene)->has('submissions')->newQuery());
    $query = static::applyDecoratorsFromQuery($filters, (new Gene)->newQuery());
    return static::getResults($query);
  }

  public static function applyRequest(Request $filters)
  {
    $query = static::applyDecoratorsFromRequest($filters, (new Gene)->has('submissions')->newQuery());
    return static::getResults($query);
  }



  /**
   * Enables the decorators to be apprlied from a query
   *
   * @param [type] $request
   * @param Builder $query
   * @return void
   */
  private static function applyDecoratorsFromQuery($request, Builder $query)
  {
    //dd($request);
    foreach ($request as $filterName => $value) {

      $decorator = static::createFilterDecorator($filterName);

      if (static::isValidDecorator($decorator)) {
        $query = $decorator::apply($query, $value);
      }
    }
    return $query;
  }


  /**
   * Enables the decorators to be be applied from a request
   *
   * @param Request $request
   * @param Builder $query
   * @return void
   */
  private static function applyDecoratorsFromRequest(Request $request, Builder $query)
  {
    foreach ($request->all() as $filterName => $value) {

      $decorator = static::createFilterDecorator($filterName);

      if (static::isValidDecorator($decorator)) {
        $query = $decorator::apply($query, $value);
      }
    }
    return $query;
  }


  private static function createFilterDecorator($name)
  {
    return __NAMESPACE__ . '\\Filters\\' . Str::studly($name);
  }


  private static function isValidDecorator($decorator)
  {
    return class_exists($decorator);
  }


  private static function getResults(Builder $query)
  {
    $collection = $query;
    return $collection;
  }
}
