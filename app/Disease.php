<?php

namespace App;

use App\Traits\DisplayTransform;
use App\Traits\HasCurationCounts;
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
    use HasCurationCounts;
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
     * gencc-sub stores aggregate counts in the counts JSON column.
     *
     * @var array
     */
    protected $casts = [
        'synonyms' => 'array',
        'xrefs' => 'array',
        'scores' => 'array',
        'counts' => 'array',
        'activity' => 'array',
        'events' => 'array',
    ];

    /**
     * The attributes that are mass assignable.
     * Updated to match gencc-sub column names.
     *
     * @var array
     */
    protected $fillable = [
        'mondo_id', 'ident', 'type', 'curie', 'name', 'deprecated_name',
        'description', 'synonyms', 'xrefs', 'scores', 'counts', 'activity',
        'events', 'notes', 'status',
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
     * gencc-sub stores the display name in the canonical name column.
     *
     * @return string|null
     */
    public function getTitleAttribute()
    {
        return $this->attributes['name'] ?? null;
    }

    /**
     * Get the legacy uuid alias from gencc-sub's canonical ident column.
     */
    public function getUuidAttribute()
    {
        return $this->attributes['ident'] ?? null;
    }

    /**
     * Get the total submission count from the canonical counts JSON.
     */
    public function getCountSubmissionsAttribute()
    {
        if (isset($this->counts['total'])) {
            return (int) $this->counts['total'];
        }
        if (isset($this->counts['count_submissions'])) {
            return (int) $this->counts['count_submissions'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->count()
            : $this->submissions()->count();
    }

    /**
     * Get the unique submitter count from the canonical counts JSON.
     */
    public function getCountUniqueSubmittersAttribute()
    {
        if (isset($this->counts['count_unique_submitters'])) {
            return (int) $this->counts['count_unique_submitters'];
        }
        if (isset($this->counts['unique_submitters'])) {
            return (int) $this->counts['unique_submitters'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->pluck('submitter_id')->unique()->count()
            : $this->submissions()->distinct('submitter_id')->count('submitter_id');
    }

    /**
     * Get the unique gene count from the canonical counts JSON.
     */
    public function getCountUniqueGenesAttribute()
    {
        if (isset($this->counts['count_unique_genes'])) {
            return (int) $this->counts['count_unique_genes'];
        }
        if (isset($this->counts['unique_genes'])) {
            return (int) $this->counts['unique_genes'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->pluck('gene_id')->unique()->count()
            : $this->submissions()->distinct('gene_id')->count('gene_id');
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
