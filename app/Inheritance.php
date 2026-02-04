<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTransform;

class Inheritance extends Model
{
    use HasFactory;
    use ModelTransform;

    /**
     * Get all the live published (publicly visible) submissions for this inheritance mode.
     * Filters by is_live=true (most recent version) AND status='published'.
     * Note: Uses 'inheritance_id' foreign key column in gencc-sub.
     */
    public function submissions()
    {
        return $this->hasMany('App\Submission', 'moi_id')
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED);
    }

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    // =========================================================================
    // Accessors - gencc-sub uses 'name' and 'ident' columns
    // =========================================================================

    /**
     * Get title attribute (returns 'name' column for backward compatibility).
     */
    public function getTitleAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * Get uuid attribute (returns 'ident' column for backward compatibility).
     */
    public function getUuidAttribute()
    {
        return $this->attributes['ident'] ?? null;
    }

    protected $fillable = [
        'curie',
        'uuid',
        'title',
        'description',
        'abbreviation',
        'hex_color',
        'css_class',
        'info_text',
        'status'
    ];
}
