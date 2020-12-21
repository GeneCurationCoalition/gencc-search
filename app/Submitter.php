<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTransform;
use App\Traits\DisplayTransform;

class Submitter extends Model
{
    //
    use ModelTransform;
    use DisplayTransform;

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function submission_files()
    {
        return $this->hasMany('App\SubmissionFile');
    }

    public function submissions()
    {
        return $this->hasMany('App\Submission')->where('status', '=', 1)->orderBy('classification_id')->orderBy('submitted_as_date');
    }

    protected $fillable = [
        'title',
        'website',
        'path_logo',
        'description',
        'text_descriptions',
        'text_contact',
        'text_assertions',
        'text_disclaimer',
        'status',
        'downloadable'
    ];
}