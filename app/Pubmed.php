<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Pubmed extends Model
{
    use SoftDeletes;

    protected $table = 'pubmeds';

    public function submissions()
    {
        return $this->belongsToMany('App\Submission', 'pubmed_submission');
    }
}
