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
        'ident', 'type', 'hgnc_id', 'symbol', 'name', 'description',
        'alias_symbols', 'previous_symbols', 'alias_names', 'previous_names',
        'date_symbol_changed', 'date_name_changed', 'locus_group', 'locus_type',
        'gene_group_id', 'gene_group', 'location', 'coordinates', 'xrefs',
        'scores', 'counts', 'activity', 'events', 'notes', 'status',
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
     * Derived from gencc-sub's canonical previous_symbols JSON column.
     */
    public function getPrevSymbolAttribute()
    {
        return $this->previous_symbols ?? [];
    }

    /**
     * Get alias_symbol - returns array of alias symbols.
     * Derived from gencc-sub's canonical alias_symbols JSON column.
     */
    public function getAliasSymbolAttribute()
    {
        return $this->alias_symbols ?? [];
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
     * Get the legacy uuid alias from gencc-sub's canonical ident column.
     */
    public function getUuidAttribute()
    {
        return $this->attributes['ident'] ?? null;
    }

    // Curation count accessors provided by HasCurationCounts trait

    /**
     * Get count_submissions - total submissions count.
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
     * Get count_unique_submitters - count unique submitters.
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
     * Get count_unique_diseases - count unique diseases (MONDO equivalents).
     */
    public function getCountUniqueDiseasesAttribute()
    {
        if (isset($this->counts['count_unique_diseases'])) {
            return (int) $this->counts['count_unique_diseases'];
        }
        if (isset($this->counts['unique_diseases'])) {
            return (int) $this->counts['unique_diseases'];
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

}
