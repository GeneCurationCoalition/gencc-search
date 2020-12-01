<?php

namespace App;

use App\Traits\DisplayTransform;
use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTransform;

class Classification extends Model
{
    //
    use ModelTransform;
    use DisplayTransform;

    public function submissions()
    {
        return $this->hasMany('App\Submission');
    }

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeSlug($query, $id)
    {
        return $query->where('slug', '=', $id)->orderBy('updated_at', 'asc');
    }

    protected $fillable = [
        'uuid',
        'curie',
        'title',
        'description',
        'abbreviation',
        'hex_color',
        'css_class',
        'slug'
    ];
}
