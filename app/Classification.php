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

    public function scopeSlug($query, $id)
    {
        return $query->where('slug', '=', $id)->orderBy('updated_at', 'asc');
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

    /**
     * Get href attribute - computed from classification ID if not in database.
     * Maps classification IDs to filter parameter names.
     */
    public function getHrefAttribute()
    {
        // Return database value if exists
        if (!empty($this->attributes['href'])) {
            return $this->attributes['href'];
        }

        // Compute from classification ID
        $hrefMap = [
            1 => 'curations_definitive',
            2 => 'curations_strong',
            3 => 'curations_moderate',
            4 => 'curations_supportive',
            5 => 'curations_limited',
            6 => 'curations_disputed',
            7 => 'curations_refuted',
            8 => 'curations_animal',
            9 => 'curations_noknown',
        ];

        return $hrefMap[$this->id] ?? '';
    }

    /**
     * Get css_class attribute - computed from classification ID if not in database.
     * Maps classification IDs to CSS class names for color bars.
     */
    public function getCssClassAttribute()
    {
        // Return database value if exists
        if (!empty($this->attributes['css_class'])) {
            return $this->attributes['css_class'];
        }

        // Compute from classification ID
        $cssMap = [
            1 => 'gencc-definitive',
            2 => 'gencc-strong',
            3 => 'gencc-moderate',
            4 => 'gencc-supportive',
            5 => 'gencc-limited',
            6 => 'gencc-disputedevidence',
            7 => 'gencc-refutedevidence',
            8 => 'gencc-animalmodelonly',
            9 => 'gencc-noknowndiseaserelationship',
        ];

        return $cssMap[$this->id] ?? 'gencc-nul';
    }

    protected $fillable = [
        'curie',
        'uuid',
        'title',
        'description',
        'abbreviation',
        'hex_color',
        'css_class',
        'slug',
        'order',
        'href',
        'info_text',
        'status'
    ];
}
