<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Notification extends Model
{
    //

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

    protected static function boot()
    {
        parent::boot();
        // Do this when creating...
        // The key item below is finding matching the uuid for the document time to a documenttype id
        static::creating(function ($model) {
            $model->uuid                    = Str::uuid();
        });
    }

    protected $fillable = [
        'type',
        'ref',
        'status',
        'label',
        'message'
    ];
}
