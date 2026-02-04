<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use Uuid;

use App\Traits\ModelTransform;
use App\Traits\DisplayTransform;
use App\Traits\HasCurationCounts;

/**
 *
 * Complete representation of a Gene.
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
 * */
class Gene extends Model
{
    use HasFactory;
    use ModelTransform;
    use DisplayTransform;
    use HasCurationCounts;

    /**
     * Map the json attributes to associative arrays.
     * gencc-sub uses JSON for counts, alias_symbols, previous_symbols, coordinates, xrefs, scores.
     *
     * @var array
     */
    protected $casts = [
        'is_acmgsf3' => 'boolean',
        'is_morbid' => 'boolean',
        'counts' => 'array',
        'alias_symbols' => 'array',
        'previous_symbols' => 'array',
        'alias_names' => 'array',
        'previous_names' => 'array',
        'coordinates' => 'array',
        'xrefs' => 'array',
        'scores' => 'array',
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
        'curie', 'type', 'title', 'description', 'status', 'date_modified', 'ident', 'uuid',
        'hgnc_id', 'hgnc_uuid', 'symbol', 'name', 'location', 'locus_group', 'locus_type',
        'date_symbol_changed', 'lsdb', 'curation_status',
        'function', 'notes', 'date_last_curated', 'is_acmgsf3', 'is_morbid',
        'prev_symbol', 'alias_symbol', 'chr', 'grch37', 'grch38', 'chm13',
        'mane_select', 'mane_plus', 'date_approved_reserved',
        'ensembl_gene_id', 'entrez_id', 'ucsc_id', 'omim_id', 'gene_group',
        'pli', 'loeuf', 'hi', 'haplo', 'triplo', 'nstatus',
        'count_submissions', 'count_unique_submitters', 'count_unique_diseases',
        'curations_definitive', 'curations_strong', 'curations_moderate', 'curations_limited',
        'curations_disputed', 'curations_refuted', 'curations_animal', 'curations_noknown',
        'curations_supportive', 'curations_nul'
    ];

    public const STATUS_INITIALIZED = 0;

    /**
     * Type identifiers
     */
    public const TYPE_NAME = 1;
    public const TYPE_PREV = 2;
    public const TYPE_ALIAS = 3;


    /**
     * Automatically assign an ident on instantiation
     *
     * @param	array	$attributes
     * @return 	void
     */
    public function __construct(array $attributes = array())
    {
       $this->attributes['ident'] = (string) Uuid::generate(4);
       parent::__construct($attributes);
    }


    /**
     * Get all the live published (publicly visible) submissions associated with this gene.
     * Filters by is_live=true (most recent version) AND status='published'.
     */
    public function submissions()
    {
        return $this->hasMany('App\Submission')
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED);
    }

    // =========================================================================
    // Accessors for backward compatibility with gencc-sub field names
    // =========================================================================

    /**
     * Get prev_symbol - returns array of previous symbols.
     * First checks JSON array column (gencc-sub), falls back to parsing text column.
     */
    public function getPrevSymbolAttribute()
    {
        // First check for JSON array column (gencc-sub schema)
        if (!empty($this->previous_symbols)) {
            return $this->previous_symbols;
        }
        // Fall back to parsing text column (test/legacy schema)
        $text = $this->attributes['prev_symbol'] ?? null;
        if (empty($text)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $text)));
    }

    /**
     * Get alias_symbol - returns array of alias symbols.
     * First checks JSON array column (gencc-sub), falls back to parsing text column.
     */
    public function getAliasSymbolAttribute()
    {
        // First check for JSON array column (gencc-sub schema)
        if (!empty($this->alias_symbols)) {
            return $this->alias_symbols;
        }
        // Fall back to parsing text column (test/legacy schema)
        $text = $this->attributes['alias_symbol'] ?? null;
        if (empty($text)) {
            return [];
        }
        return array_filter(array_map('trim', explode(',', $text)));
    }

    /**
     * Get title - returns symbol for backward compatibility.
     * gencc-sub uses 'symbol' column, views may reference 'title'.
     */
    public function getTitleAttribute()
    {
        return $this->attributes['symbol'] ?? null;
    }

    /**
     * Get curie - returns hgnc_id for backward compatibility.
     * gencc-sub uses 'hgnc_id' column, views may reference 'curie'.
     */
    public function getCurieAttribute()
    {
        return $this->attributes['hgnc_id'] ?? null;
    }

    /**
     * Get uuid attribute - actual column in gencc-sub.
     */
    public function getUuidAttribute()
    {
        return $this->attributes['uuid'] ?? $this->attributes['ident'] ?? null;
    }

    // Curation count accessors provided by HasCurationCounts trait

    /**
     * Get count_submissions - total submissions count.
     */
    public function getCountSubmissionsAttribute()
    {
        if (isset($this->attributes['count_submissions'])) {
            return (int) $this->attributes['count_submissions'];
        }
        if (!empty($this->counts['submissions'])) {
            return $this->counts['submissions'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->count()
            : $this->submissions()->count();
    }

    /**
     * Get count_unique_submitters - count unique submitters.
     */
    public function getCountUniqueSubmittersAttribute()
    {
        if (isset($this->attributes['count_unique_submitters'])) {
            return (int) $this->attributes['count_unique_submitters'];
        }
        if (!empty($this->counts['unique_submitters'])) {
            return $this->counts['unique_submitters'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->pluck('submitter_id')->unique()->count()
            : $this->submissions()->distinct('submitter_id')->count('submitter_id');
    }

    /**
     * Get count_unique_diseases - count unique diseases (MONDO equivalents).
     */
    public function getCountUniqueDiseasesAttribute()
    {
        if (isset($this->attributes['count_unique_diseases'])) {
            return (int) $this->attributes['count_unique_diseases'];
        }
        if (!empty($this->counts['unique_diseases'])) {
            return $this->counts['unique_diseases'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->pluck('disease_id')->unique()->count()
            : $this->submissions()->distinct('disease_id')->count('disease_id');
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    /**
     * Scope a query by hgnc_id (HGNC:XXXX format).
     * gencc-sub uses hgnc_id instead of curie for genes.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurie($query, $id)
    {
        return $query->where('hgnc_id', '=', $id)->orderBy('updated_at', 'asc');
    }


    /**
     * Scope a query by ident (replaces uuid scope for gencc-sub compatibility).
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeIdent($query, $ident)
    {
       return $query->where('ident', $ident);
    }

    /**
     * Scope a query by symbol.
     *
     * @param  \Illuminate\Database\Eloquent\Builder  $query
     * @param  string  $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSymbol($query, $id)
    {
        return $query->where('symbol', '=', $id)->orderBy('updated_at', 'asc');
    }


    /**
    * Query scope by symbol name
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeName($query, $name)
    {
       return $query->where('name', $name);
    }


    /**
    * Query scope by hgncid name
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
     public function scopeHgnc($query, $id)
    {
       return $query->where('hgnc_id', $id);
    }


    /**
    * Query scope by ACMG SF 3 flag
    *
    * @@param	string	$ident
    * @return Illuminate\Database\Eloquent\Collection
    */
    public function scopeAcmg($query)
    {
       return $query->where('acmg59', 1);
    }
}
