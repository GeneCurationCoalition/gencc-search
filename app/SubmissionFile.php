<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;

class SubmissionFile extends Model
{
    use HasFactory;

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function submitter()
    {
        return $this->belongsTo('App\Submitter');
    }
    public function user()
    {
        return $this->belongsTo('App\User');
    }
    public function created_by()
    {
        return $this->belongsTo('App\User', 'created_by_user');
    }

    protected static function boot()
    {
        parent::boot();
        // Do this when creating...
        // The key item below is finding matching the uuid for the document time to a documenttype id
        static::creating(function ($model) {
            $model->uuid                    = Str::uuid();
        });

        static::addGlobalScope('order', function (Builder $builder) {
            $builder->orderBy('status', 'desc')->orderBy('created_at', 'desc');
        });
    }

    protected $fillable = [
        'uuid',
        'path',
        'file_name',
        'file_type',
        'status',
        'file_size'
    ];
}