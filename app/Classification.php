<?php

namespace App;

use App\Traits\DisplayTransform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTransform;

class Classification extends Model
{
    use HasFactory;
    use ModelTransform;
    use DisplayTransform;

    /**
     * Get all the live published (publicly visible) submissions for this classification.
     * Filters by is_live=true (most recent version) AND status='published'.
     */
    public function submissions()
    {
        return $this->hasMany('App\Submission')
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED);
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
