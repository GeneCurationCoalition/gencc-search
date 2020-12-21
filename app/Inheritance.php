<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\ModelTransform;

class Inheritance extends Model
{
    //
    use ModelTransform;

    public function submissions()
    {
        return $this->hasMany('App\Submission')->where('status', '=', 1);
    }

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

    protected $fillable = [
        'uuid',
        'curie',
        'title',
        'description',
        'abbreviation',
        'hex_color',
        'css_class'
    ];
}
