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
     * The application vocabulary for GenCC classifications, strongest first.
     *
     * CURIEs, unlike database IDs, retain their meaning when rows are imported
     * in a different order. Keep filtering, presentation, URL generation, and
     * strongest-bucket ranking together here so those concerns cannot drift.
     */
    const VOCABULARY = [
        'GENCC:100001' => [
            'title' => 'Definitive',
            'property' => 'curations_definitive',
            'query' => 'definitive',
            'slug' => 'definitive',
            'css_class' => 'gencc-definitive',
            'priority' => 1,
            'conflict_bucket' => 'strong',
        ],
        'GENCC:100002' => [
            'title' => 'Strong',
            'property' => 'curations_strong',
            'query' => 'strong',
            'slug' => 'strong',
            'css_class' => 'gencc-strong',
            'priority' => 2,
            'conflict_bucket' => 'strong',
        ],
        'GENCC:100003' => [
            'title' => 'Moderate',
            'property' => 'curations_moderate',
            'query' => 'moderate',
            'slug' => 'moderate',
            'css_class' => 'gencc-moderate',
            'priority' => 3,
            'conflict_bucket' => 'strong',
        ],
        'GENCC:100009' => [
            'title' => 'Supportive',
            'property' => 'curations_supportive',
            'query' => 'supportive',
            'slug' => 'supportive',
            'css_class' => 'gencc-supportive',
            'priority' => 4,
            'conflict_bucket' => 'supportive',
        ],
        'GENCC:100004' => [
            'title' => 'Limited',
            'property' => 'curations_limited',
            'query' => 'limited',
            'slug' => 'limited',
            'css_class' => 'gencc-limited',
            'priority' => 5,
            'conflict_bucket' => 'limited',
        ],
        'GENCC:100005' => [
            'title' => 'Disputed Evidence',
            'property' => 'curations_disputed',
            'query' => 'disputed',
            'slug' => 'disputed',
            'css_class' => 'gencc-disputedevidence',
            'priority' => 6,
            'conflict_bucket' => 'contradictory',
        ],
        'GENCC:100007' => [
            'title' => 'Animal Model Only',
            'property' => 'curations_animal',
            'query' => 'animal',
            'slug' => 'animal-model-only',
            'css_class' => 'gencc-animalmodelonly',
            'priority' => 7,
            'conflict_bucket' => 'contradictory',
        ],
        'GENCC:100006' => [
            'title' => 'Refuted Evidence',
            'property' => 'curations_refuted',
            'query' => 'refuted',
            'slug' => 'refuted',
            'css_class' => 'gencc-refutedevidence',
            'priority' => 8,
            'conflict_bucket' => 'contradictory',
        ],
        'GENCC:100008' => [
            'title' => 'No Known Disease Relationship',
            'property' => 'curations_noknown',
            'query' => 'noknown',
            'slug' => 'no-known',
            'css_class' => 'gencc-noknowndiseaserelationship',
            'priority' => 9,
            'conflict_bucket' => 'contradictory',
        ],
    ];

    public static function filterProperties(): array
    {
        return array_column(self::VOCABULARY, 'property');
    }

    public static function filterParams(): array
    {
        return array_column(self::VOCABULARY, 'query');
    }

    public static function validityRanks(): array
    {
        $ranks = [];

        foreach (self::VOCABULARY as $curie => $metadata) {
            $ranks[$curie] = $metadata['priority'];
        }

        return $ranks;
    }

    /**
     * Return the conflict-viewer bucket for a known classification CURIE.
     */
    public static function conflictBucket(string $curie): ?string
    {
        return self::VOCABULARY[$curie]['conflict_bucket'] ?? null;
    }

    /** Return the canonical display priority for a known CURIE. */
    public static function priority(string $curie): ?int
    {
        return self::VOCABULARY[$curie]['priority'] ?? null;
    }

    public static function queryStringBindings(): array
    {
        $bindings = [];

        foreach (self::VOCABULARY as $metadata) {
            $bindings[$metadata['property']] = [
                'except' => '1',
                'as' => $metadata['query'],
            ];
        }

        return $bindings;
    }

    /**
     * Return known classification models in canonical vocabulary order.
     */
    public static function orderCollection($classifications)
    {
        $byCurie = $classifications->keyBy('curie');

        return collect(array_keys(self::VOCABULARY))
            ->map(fn ($curie) => $byCurie->get($curie))
            ->filter()
            ->values();
    }

    public function vocabularyMetadata(): ?array
    {
        return self::VOCABULARY[$this->curie] ?? null;
    }

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
     * Get slug attribute for color lookups.
     */
    public function getSlugAttribute()
    {
        return $this->vocabularyMetadata()['slug']
            ?? ($this->attributes['slug'] ?? '');
    }

    /**
     * Get the legacy long filter property name.
     */
    public function getHrefAttribute()
    {
        return $this->vocabularyMetadata()['property']
            ?? ($this->attributes['href'] ?? '');
    }

    /**
     * Get filter_param attribute - the name this classification's toggle goes by
     * in the genes listing query string.
     *
     * The href attribute above is the legacy 'curations_definitive' spelling,
     * which the listing no longer binds to; this is the short alias it does.
     */
    public function getFilterParamAttribute()
    {
        return $this->vocabularyMetadata()['query'] ?? '';
    }

    /**
     * Get only_filter_query attribute - a genes listing query string selecting
     * this classification and nothing else.
     *
     * All nine toggles default to on, so naming only this one ('?definitive=1')
     * would leave the other eight enabled and filter nothing at all. The other
     * eight have to be switched off explicitly.
     */
    public function getOnlyFilterQueryAttribute()
    {
        if (!$this->vocabularyMetadata()) {
            return '';
        }

        $pairs = [];

        foreach (self::VOCABULARY as $curie => $metadata) {
            $pairs[] = $metadata['query'] . '=' . ($curie === $this->curie ? '1' : '0');
        }

        return implode('&', $pairs);
    }

    /**
     * Get css_class attribute for color bars.
     */
    public function getCssClassAttribute()
    {
        return $this->vocabularyMetadata()['css_class']
            ?? ($this->attributes['css_class'] ?? 'gencc-nul');
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
