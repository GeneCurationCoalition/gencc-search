<?php

namespace App;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Trio extends Model
{
    use HasFactory;

    public function submissions()
    {
        return $this->hasMany('App\Submission');
    }

    public function gene()
    {
        return $this->belongsTo('App\Gene');
    }

    public function classification()
    {
        return $this->belongsTo('App\Classification');
    }

    public function disease()
    {
        return $this->belongsTo('App\Disease');
    }

    public function inheritance()
    {
        return $this->belongsTo('App\Inheritance', 'moi_id');
    }

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
    }

  protected $casts = [
        'date' => 'date:Y-m-d'
    ];

    protected $with = ['gene', 'disease', 'inheritance', 'submissions.classification'];

    protected $fillable = [
        'uuid',
        'title',
        'name',
        'gene_id',
        'disease_id',
        'moi_id',
        'description',
        'status',
        'uuid'
    ];
}
