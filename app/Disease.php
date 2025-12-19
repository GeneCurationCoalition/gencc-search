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
 * @copyright  2022 Geisinger
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
     *
     * @var array
     */
    protected $fillable = [
        'ident',
        'type',
        'mondo_id',
        'curie',
        'name',
        'deprecated_name',
        'description',
        'synonyms',
        'xrefs',
        'scores',
        'activity',
        'events',
        'notes',
        'status',
        // Denormalized count columns (preserved from original design)
        'count_child_submissions',
        'curations_definitive',
        'curations_strong',
        'curations_moderate',
        'curations_supportive',
        'curations_limited',
        'curations_disputed',
        'curations_refuted',
        'curations_animal',
        'curations_noknown',
        'curations_nul',
        'count_clinical_above',
        'count_clinical_neutral',
        'count_clinical_below',
        'count_submissions',
        'count_unique_genes',
        'count_unique_submitters',
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
     * Get all the submissions associated with this disease
     */
    public function submissions()
    {
        return $this->belongsToMany('App\Submission', 'disease_submission')
            ->where('status', '=', 1)
            ->withTimestamps()
            ->withPivot('type');
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
    // Legacy relationships - kept for backward compatibility during migration
    // These can be removed once the disease_disease pivot table is dropped
    // =========================================================================

    public function xrefs()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'disease_id', 'xref_id')
            ->withTimestamps()
            ->withPivot('type', 'predicate', 'ontology');
    }

    public function synonyms_rel()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'disease_id', 'synonym_id')
            ->withTimestamps()
            ->withPivot('type', 'predicate', 'ontology');
    }

    public function equivalents()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'disease_id', 'equiv_id')
            ->withTimestamps()
            ->withPivot('type', 'predicate', 'ontology');
    }

    public function synonym_parents()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'parent_id', 'child_id')
            ->withTimestamps()
            ->withPivot('type', 'predicate', 'ontology');
    }

    public function synonym_children()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'child_id', 'parent_id')
            ->withTimestamps()
            ->withPivot('type', 'predicate', 'ontology');
    }

    public function parents()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'parent_id', 'child_id')
            ->withTimestamps()
            ->withPivot('type', 'predicate', 'ontology');
    }

    public function children()
    {
        return $this->belongsToMany('App\Disease', 'disease_disease', 'child_id', 'parent_id')
            ->withTimestamps()
            ->withPivot('type', 'predicate', 'ontology');
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
     * Query scope by UUID (legacy - use scopeIdent instead)
     *
     * @param string $id
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeUuid($query, $id)
    {
        return $query->where('uuid', '=', $id)->orderBy('updated_at', 'asc');
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

    // =========================================================================
    // Rosetta Stone - Disease ID Resolution
    // =========================================================================

    /**
     * Map various disease ontology references to the canonical MONDO disease record.
     *
     * This method uses the mondo_id foreign key for fast equivalence lookups.
     * Returns ACTIVE and DEPRECATED disease records (REMOVED diseases are excluded).
     *
     * For OMIM/Orphanet IDs:
     * 1. First looks for a direct OMIM/Orphanet record with that curie
     * 2. If found, returns its linked MONDO disease (via mondo_id)
     * 3. If not found, looks for MONDO disease with this ID in xrefs (legacy fallback)
     *
     * @param string $id The disease identifier (with or without prefix)
     * @return Disease|null The MONDO disease record (active or deprecated), or null if not found/removed
     */
    public static function rosetta($id)
    {
        // Return null if id is not set
        if (empty($id)) {
            return null;
        }

        // Separate out prefix and identifier
        $parts = explode(':', basename(trim($id)));

        // If a prefix is omitted, assume it is a MIM number
        if (!isset($parts[1])) {
            if (is_numeric($id)) {
                $curie = 'OMIM:' . $id;
                $record = self::rosettaOmim($curie);
            } else {
                $record = null;
            }
        } else {
            $numericId = $parts[1];
            $curie = strtoupper($parts[0]) . ':' . $numericId;

            switch (strtoupper($parts[0])) {
                case 'OMIM':
                case 'OMIMPS':
                    $record = self::rosettaOmim($curie);
                    break;

                case 'ORPHANET':
                case 'ORPHA':
                    $curie = 'Orphanet:' . $numericId;  // Normalize to Orphanet prefix
                    $record = self::rosettaOrphanet($curie);
                    break;

                case 'MONDO':
                    $record = self::rosettaMondo('MONDO:' . $numericId);
                    break;

                case 'DOID':
                    // For non-MONDO/OMIM/Orphanet ontologies, search xrefs (exclude REMOVED)
                    $record = self::where('type', self::TYPE_MONDO)
                        ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_DEPRECATED])
                        ->where('xrefs->do_id', $numericId)
                        ->first();
                    break;

                case 'GARD':
                    $record = self::where('type', self::TYPE_MONDO)
                        ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_DEPRECATED])
                        ->where('xrefs->gard_id', $numericId)
                        ->first();
                    break;

                case 'MEDGEN':
                    $record = self::where('type', self::TYPE_MONDO)
                        ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_DEPRECATED])
                        ->where('xrefs->medgen_id', $numericId)
                        ->first();
                    break;

                case 'UMLS':
                    $record = self::where('type', self::TYPE_MONDO)
                        ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_DEPRECATED])
                        ->where('xrefs->umls_id', $numericId)
                        ->first();
                    break;

                default:
                    $record = null;
            }
        }

        return $record;
    }

    /**
     * Resolve an OMIM ID to its canonical MONDO disease
     *
     * @param string $curie OMIM CURIE (e.g., "OMIM:615438")
     * @return Disease|null The MONDO disease (active or deprecated), or null if not found/removed
     */
    protected static function rosettaOmim($curie)
    {
        // Strategy 1: Look for direct OMIM record with mondo_id
        $omimDisease = self::omim($curie)->first();

        if ($omimDisease && $omimDisease->mondo_id) {
            // Prioritize ACTIVE MONDO disease over deprecated
            $mondoDisease = self::where('id', $omimDisease->mondo_id)
                ->where('status', self::STATUS_ACTIVE)
                ->first();

            if ($mondoDisease) {
                return $mondoDisease;
            }

            // Fallback to deprecated if no active found
            $mondoDisease = self::where('id', $omimDisease->mondo_id)
                ->where('status', self::STATUS_DEPRECATED)
                ->first();

            if ($mondoDisease) {
                return $mondoDisease;
            }
        }

        // Strategy 2: Legacy fallback - search MONDO xrefs
        $omimId = str_replace('OMIM:', '', $curie);

        // Prioritize active MONDO terms
        $mondoDisease = self::where('type', self::TYPE_MONDO)
            ->where('status', self::STATUS_ACTIVE)
            ->whereJsonContains('xrefs->omim_id', $omimId)
            ->first();

        if ($mondoDisease) {
            return $mondoDisease;
        }

        // Fallback to deprecated MONDO terms
        $mondoDisease = self::where('type', self::TYPE_MONDO)
            ->where('status', self::STATUS_DEPRECATED)
            ->whereJsonContains('xrefs->omim_id', $omimId)
            ->first();

        return $mondoDisease;
    }

    /**
     * Resolve an Orphanet ID to its canonical MONDO disease
     *
     * @param string $curie Orphanet CURIE (e.g., "Orphanet:464724")
     * @return Disease|null The MONDO disease (active or deprecated), or null if not found/removed
     */
    protected static function rosettaOrphanet($curie)
    {
        // Strategy 1: Look for direct Orphanet record with mondo_id
        $orphanetDisease = self::where('type', self::TYPE_ORPHANET)
            ->where('curie', $curie)
            ->first();

        if ($orphanetDisease && $orphanetDisease->mondo_id) {
            // Prioritize ACTIVE MONDO disease over deprecated
            $mondoDisease = self::where('id', $orphanetDisease->mondo_id)
                ->where('status', self::STATUS_ACTIVE)
                ->first();

            if ($mondoDisease) {
                return $mondoDisease;
            }

            // Fallback to deprecated if no active found
            $mondoDisease = self::where('id', $orphanetDisease->mondo_id)
                ->where('status', self::STATUS_DEPRECATED)
                ->first();

            if ($mondoDisease) {
                return $mondoDisease;
            }
        }

        // Strategy 2: Legacy fallback - search MONDO xrefs
        $orphanetId = str_replace('Orphanet:', '', $curie);

        // Prioritize active MONDO terms
        $mondoDisease = self::where('type', self::TYPE_MONDO)
            ->where('status', self::STATUS_ACTIVE)
            ->where('xrefs->orpha_id', $orphanetId)
            ->first();

        if ($mondoDisease) {
            return $mondoDisease;
        }

        // Fallback to deprecated MONDO terms
        $mondoDisease = self::where('type', self::TYPE_MONDO)
            ->where('status', self::STATUS_DEPRECATED)
            ->where('xrefs->orpha_id', $orphanetId)
            ->first();

        return $mondoDisease;
    }

    /**
     * Resolve a MONDO ID to its disease record
     *
     * @param string $curie MONDO CURIE (e.g., "MONDO:0000001")
     * @return Disease|null The MONDO disease (active or deprecated), or null if not found/removed
     */
    protected static function rosettaMondo($curie)
    {
        // Return MONDO disease if active or deprecated (not removed)
        $mondoDisease = self::curie($curie)
            ->whereIn('status', [self::STATUS_ACTIVE, self::STATUS_DEPRECATED])
            ->first();

        return $mondoDisease;
    }

    // =========================================================================
    // Accessor for backward compatibility
    // =========================================================================

    /**
     * Get the title attribute (backward compatibility)
     * Maps to the new 'name' field
     *
     * @return string|null
     */
    public function getTitleAttribute()
    {
        return $this->name ?? $this->attributes['title'] ?? null;
    }
}
