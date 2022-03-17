<?php

namespace App;

use App\Traits\DisplayTransform;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTransform;

/**
 *
 * @category   Model
 * @package    GenCC
 * @author     P. Weller <pweller1@geisinger.edu>
 * @copyright  2022 Geisinger
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.0.0
 *
 * */
class Disease extends Model
{
    use ModelTransform;
    use DisplayTransform;

    public const STATUS_INITIALIZED = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_GG_DEPRECATED = 9;
    public const STATUS_DEPRECATED = 10;

    // public function submissions()
    // {
    //     return $this->hasMany('App\Submission');
    // }
    // public function classification()
    // {
    //     return $this->belongsTo('App\Classification');
    // }

    public function submissions()
    {
        return $this->belongsToMany('App\Submission', 'disease_submission')->where('status', '=', 1)->withTimestamps()->withPivot('type');
    }
    public function xrefs()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'disease_id', 'xref_id')->withTimestamps()->withPivot('type', 'predicate', 'ontology');
    }
    public function synonyms()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'disease_id', 'synonym_id')->withTimestamps()->withPivot('type', 'predicate', 'ontology');
    }
    public function equivalents()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'disease_id', 'equiv_id')->withTimestamps()->withPivot('type', 'predicate', 'ontology');
    }
    public function synonym_parents()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'parent_id', 'child_id')->withTimestamps()->withPivot('type', 'predicate', 'ontology');
    }

    public function synonym_children()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'child_id', 'parent_id')->withTimestamps()->withPivot('type', 'predicate', 'ontology');
    }

    public function parents()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'parent_id', 'child_id')->withTimestamps()->withPivot('type', 'predicate', 'ontology');
    }

    public function children()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'child_id', 'parent_id')->withTimestamps()->withPivot('type', 'predicate', 'ontology');
    }

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }


    // protected $with = [
    //     'children',
    //     'parents'
    // ];

    protected $fillable = [
        'uuid',
        'curie',
        'type',
        'title',
        'description',
        'status',
        'children_sync',
        'xref',
        'related_exactMatch',
        'related_closeMatch',
        'synonyms_exact',
        'synonyms_related'
    ];
}
