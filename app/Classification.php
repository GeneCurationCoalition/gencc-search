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
     * Clinical validity ranking, keyed by classification ID, lowest value being
     * the strongest. Used to place a gene in a single bucket — the one for its
     * strongest assertion — for the by-gene statistics chart (#210).
     *
     * Keyed by ID rather than the `order` column because IDs are what the rest of
     * the codebase already pins these terms to (see getHrefAttribute and
     * getCssClassAttribute), and `order` disagrees between fixtures and
     * production.
     *
     * The sequence follows the term order published in the FAQ, with one
     * deliberate exception: Supportive (4) sorts last, so a gene only lands in
     * the Supportive bucket when Supportive is its sole assertion. Supportive is
     * a granularity fallback used by resources such as OMIM and Orphanet rather
     * than a validity level in its own right.
     */
    const VALIDITY_RANK = [
        1 => 1, // Definitive
        2 => 2, // Strong
        3 => 3, // Moderate
        5 => 4, // Limited
        6 => 5, // Disputed
        8 => 6, // Animal Model Only
        7 => 7, // Refuted
        9 => 8, // No known disease relationship
        4 => 9, // Supportive — only wins when nothing else is present
    ];

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
     * Get slug attribute - computed from classification ID if not in database.
     * Maps classification IDs to slug names for color lookups.
     */
    public function getSlugAttribute()
    {
        // Return database value if exists
        if (!empty($this->attributes['slug'])) {
            return $this->attributes['slug'];
        }

        // Compute from classification ID
        $slugMap = [
            1 => 'definitive',
            2 => 'strong',
            3 => 'moderate',
            4 => 'supportive',
            5 => 'limited',
            6 => 'disputed',
            7 => 'refuted',
            8 => 'animal-model-only',
            9 => 'no-known',
        ];

        return $slugMap[$this->id] ?? '';
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
