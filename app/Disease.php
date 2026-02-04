<?php

namespace App;

use App\Traits\DisplayTransform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Str;
use App\Traits\ModelTransform;

/**
 *
 * @category   Model
 * @package    GenCC
 * @author     P. Weller <pweller1@geisinger.edu>
 * @license    http://www.php.net/license/3_01.txt  PHP License 3.01
 * @version    Release: @package_version@
 * @link       http://pear.php.net/package/PackageName
 * @see        NetOther, Net_Sample::Net_Sample()
 * @since      Class available since Release 1.0.0
 *
 * The diseases table holds all information about diseases for the portal, including
 * name, curie, cross-referencing, and status. Submissions typically join to this table for
 * the curated disease.
 *
 * */
class Disease extends Model
{
    use HasFactory;
    use ModelTransform;
    use DisplayTransform;
    use SoftDeletes;

    /**
     * Status constants
     */
    public const STATUS_INITIALIZING = 0;
    public const STATUS_ACTIVE = 1;
    public const STATUS_DEPRECATED = 8;
    public const STATUS_REMOVED = 9;

    // Legacy status constants for backward compatibility during migration
    public const STATUS_INITIALIZED = 0;
    public const STATUS_GG_DEPRECATED = 9;

    /**
     * Type constants
     */
    public const TYPE_UNKNOWN = 0;
    public const TYPE_MONDO = 1;
    public const TYPE_OMIM = 10;
    public const TYPE_OMIM_PLUS = 11;
    public const TYPE_OMIM_PERCENT = 12;
    public const TYPE_OMIM_CARET = 13;
    public const TYPE_OMIM_NUMBER = 14;
    public const TYPE_OMIM_GENE = 15;
    public const TYPE_ORPHANET = 20;

    /**
     * Status strings for display methods
     *
     * @var array
     */
    protected $status_strings = [
        0 => 'Initializing',
        1 => 'Active',
        8 => 'Deprecated',
        9 => 'Removed',
    ];

    /**
     * The attributes that should be cast to native types.
     * Note: gencc-sub uses individual columns instead of JSON for counts.
     *
     * @var array
     */
    protected $casts = [
        'synonyms' => 'object',
        'xrefs' => 'object',
        'scores' => 'object',
        'activity' => 'object',
        'events' => 'object',
    ];

    /**
     * The attributes that are mass assignable.
     * Updated to match gencc-sub column names.
     *
     * @var array
     */
    protected $fillable = [
        'ident', 'uuid',
        'type',
        'mondo_id',
        'curie',
        'name', 'title',
        'deprecated_name',
        'description',
        'synonyms', 'synonyms_exact', 'synonyms_related',
        'xrefs',
        'scores',
        'activity',
        'events',
        'notes',
        'status',
        'meta_parents', 'children_sync',
        'related_closeMatch', 'related_exactMatch',
        'count_submissions', 'count_unique_genes', 'count_unique_submitters',
        'count_child_submissions', 'count_clinical_above', 'count_clinical_below', 'count_clinical_neutral',
        'curations_definitive', 'curations_strong', 'curations_moderate', 'curations_limited',
        'curations_disputed', 'curations_refuted', 'curations_animal', 'curations_noknown',
        'curations_supportive', 'curations_nul',
    ];

    /**
     * Automatically assign an ident on instantiation
     *
     * @param array $attributes
     * @return void
     */
    public function __construct(array $attributes = array())
    {
        $this->attributes['ident'] = Str::uuid()->toString();
        parent::__construct($attributes);
    }

    /**
     * Get all the live published (publicly visible) submissions associated with this disease.
     * Filters by is_live=true (most recent version) AND status='published'.
     *
     * NOTE: Changed from belongsToMany (pivot table) to hasMany (direct FK) for gencc-sub compatibility.
     */
    public function submissions()
    {
        return $this->hasMany('App\Submission', 'disease_id')
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED);
    }

    /**
     * Get the canonical MONDO disease for this disease
     * (null for MONDO diseases themselves)
     */
    public function mondoDisease()
    {
        return $this->belongsTo('App\Disease', 'mondo_id');
    }

    /**
     * Get all OMIM/Orphanet diseases that map to this MONDO disease
     */
    public function equivalentDiseases()
    {
        return $this->hasMany('App\Disease', 'mondo_id');
    }

    // =========================================================================
    // Accessors for backward compatibility
    // =========================================================================

    /**
     * Get the title attribute.
     * Gencc-sub has both 'title' and 'name' columns.
     *
     * @return string|null
     */
    public function getTitleAttribute()
    {
        return $this->attributes['title'] ?? $this->attributes['name'] ?? null;
    }

    /**
     * Get uuid attribute - actual column in gencc-sub.
     */
    public function getUuidAttribute()
    {
        return $this->attributes['uuid'] ?? $this->attributes['ident'] ?? null;
    }

    // =========================================================================
    // Curations count accessors - these columns exist directly in the database
    // =========================================================================

    /**
     * Get curations_definitive (actual column name).
     */
    public function getCurationsDefinitiveAttribute()
    {
        return $this->attributes['curations_definitive'] ?? 0;
    }

    /**
     * Get curations_strong (actual column name).
     */
    public function getCurationsStrongAttribute()
    {
        return $this->attributes['curations_strong'] ?? 0;
    }

    /**
     * Get curations_moderate (actual column name).
     */
    public function getCurationsModerateAttribute()
    {
        return $this->attributes['curations_moderate'] ?? 0;
    }

    /**
     * Get curations_limited (actual column name).
     */
    public function getCurationsLimitedAttribute()
    {
        return $this->attributes['curations_limited'] ?? 0;
    }

    /**
     * Get curations_disputed (actual column name).
     */
    public function getCurationsDisputedAttribute()
    {
        return $this->attributes['curations_disputed'] ?? 0;
    }

    /**
     * Get curations_refuted (actual column name).
     */
    public function getCurationsRefutedAttribute()
    {
        return $this->attributes['curations_refuted'] ?? 0;
    }

    /**
     * Get curations_animal (actual column name).
     */
    public function getCurationsAnimalAttribute()
    {
        return $this->attributes['curations_animal'] ?? 0;
    }

    /**
     * Get curations_noknown (actual column name).
     */
    public function getCurationsNoknownAttribute()
    {
        return $this->attributes['curations_noknown'] ?? 0;
    }

    /**
     * Get curations_supportive (actual column name).
     */
    public function getCurationsSupportiveAttribute()
    {
        return $this->attributes['curations_supportive'] ?? 0;
    }

    /**
     * Get curations_nul (actual column name).
     */
    public function getCurationsNulAttribute()
    {
        return $this->attributes['curations_nul'] ?? 0;
    }

    /**
     * Get count_submissions (actual column name).
     */
    public function getCountSubmissionsAttribute()
    {
        return $this->attributes['count_submissions'] ?? 0;
    }

    /**
     * Get count_unique_submitters (actual column name).
     */
    public function getCountUniqueSubmittersAttribute()
    {
        return $this->attributes['count_unique_submitters'] ?? 0;
    }

    /**
     * Get count_unique_genes (actual column name).
     */
    public function getCountUniqueGenesAttribute()
    {
        return $this->attributes['count_unique_genes'] ?? 0;
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    /**
     * Query scope by curie
     *
     * @param string $curie
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurie($query, $curie)
    {
        return $query->where('curie', $curie);
    }

    /**
     * Query scope by ident
     *
     * @param string $ident
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIdent($query, $ident)
    {
        return $query->where('ident', $ident);
    }

    /**
     * Query scope by ontology type
     *
     * @param integer $type
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Query scope for MONDO types
     *
     * @param string $curie
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMondo($query, $curie)
    {
        return $query->where('type', self::TYPE_MONDO)->where('curie', $curie);
    }

    /**
     * Query scope for all OMIM types
     *
     * @param string $curie
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeOmim($query, $curie)
    {
        $types = [
            self::TYPE_OMIM,
            self::TYPE_OMIM_CARET,
            self::TYPE_OMIM_NUMBER,
            self::TYPE_OMIM_PERCENT,
            self::TYPE_OMIM_PLUS
        ];

        return $query->whereIn('type', $types)->where('curie', $curie);
    }
}
