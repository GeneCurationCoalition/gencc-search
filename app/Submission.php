<?php

namespace App;

use App\Traits\DisplayTransform;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTransform;

class Submission extends Model
{
    use ModelTransform;
    use DisplayTransform;

    public function gene()
    {
        return $this->belongsTo('App\Gene');
    }

    public function inheritance()
    {
        return $this->belongsTo('App\Inheritance', 'moi_id');
    }

    public function classification()
    {
        return $this->belongsTo('App\Classification');
    }

    public function disease()
    {
        return $this->belongsTo('App\Disease');
    }

    public function disease_original()
    {
        return $this->belongsTo('App\Disease', 'disease_original_id');
    }

    public function submitter()
    {
        return $this->belongsTo('App\Submitter');
    }

    public function diseases()
    {
        return $this->belongsToMany('App\Disease', 'disease_submission')->withTimestamps()->withPivot('type');
    }

    public function disease_normalized()
    {
        return $this->belongsTo('App\Disease');
    }

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->where('status', '=', 1)->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->where('status', '=', 1)->orderBy('updated_at', 'asc');
    }


    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->curie} {$this->title}";
    }

    protected $casts = [
        'date' => 'date:Y-m-d'
    ];







    protected $with = ['gene', 'disease', 'disease_original', 'classification', 'inheritance', 'submitter'];

    protected $fillable = [
        'uuid',
        'order',
        'submitted_as_submission_id' ,
        'submitted_as_hgnc_id',
        'submitted_as_hgnc_symbol',
        'submitted_as_disease_id',
        'submitted_as_disease_name',
        'submitted_as_moi_id',
        'submitted_as_moi_name',
        'submitted_as_submitter_id',
        'submitted_as_submitter_name',
        'submitted_as_classification_id',
        'submitted_as_classification_name',
        'submitted_as_public_report_url',
        'submitted_as_notes',
        'submitted_as_date',
        'submitted_as_pmids',
        'submitted_as_assertion_criteria_url',
        'submitted_run_date'
    ];
}
