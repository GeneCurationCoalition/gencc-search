<?php

namespace App;

use App\Traits\DisplayTransform;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTransform;
use League\CommonMark\CommonMarkConverter;

class Submission extends Model
{
    use HasFactory;
    use ModelTransform;
    use DisplayTransform;

    public function gene()
    {
        return $this->belongsTo('App\Gene');
    }

    public function inheritance()
    {
        return $this->belongsTo('App\Inheritance', 'moi_id');
    }

    public function classification()
    {
        return $this->belongsTo('App\Classification');
    }

    public function disease()
    {
        return $this->belongsTo('App\Disease');
    }

    public function disease_original()
    {
        return $this->belongsTo('App\Disease', 'disease_original_id');
    }

    public function submitter()
    {
        return $this->belongsTo('App\Submitter');
    }

    public function trio()
    {
        return $this->belongsTo('App\Submitter');
    }

    public function diseases()
    {
        return $this->belongsToMany('App\Disease', 'disease_submission')->withTimestamps()->withPivot('type');
    }

    public function disease_normalized()
    {
        return $this->belongsTo('App\Disease');
    }

    /**
     * Status constants
     */
    const STATUS_PUBLISHED = 'published';
    const STATUS_UNPUBLISHED = 'unpublished';

    public function scopeCurie($query, $id)
    {
        // is_live = most recent version, status = 'published' for publicly visible
        return $query->where('curie', '=', $id)
            ->where('is_live', '=', true)
            ->where('status', '=', self::STATUS_PUBLISHED)
            ->orderBy('updated_at', 'asc');
    }

    public function scopeUuid($query, $id)
    {
        // is_live = most recent version, status = 'published' for publicly visible
        return $query->where('uuid', '=', $id)
            ->where('is_live', '=', true)
            ->where('status', '=', self::STATUS_PUBLISHED)
            ->orderBy('updated_at', 'asc');
    }

    /**
     * Scope to find a submission by display ID (uuid.version format).
     * Supports both "SGC-107616.2" and "SGC-107616" formats.
     * If no version is specified, returns the most recent version (is_live=true).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $displayId The display ID (e.g., "SGC-107616.2" or "SGC-107616")
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByDisplayId($query, $displayId)
    {
        // Check if the display ID contains a version number
        if (preg_match('/^(.+)\.(\d+)$/', $displayId, $matches)) {
            // Has version number - look up specific version
            $uuid = $matches[1];
            $version = (int) $matches[2];
            return $query->where('uuid', '=', $uuid)->where('version_number', '=', $version);
        }

        // No version number - return the most recent version (is_live=true)
        return $query->where('uuid', '=', $displayId)->where('is_live', '=', true);
    }

    /**
     * Scope to only include live published submissions (publicly visible).
     * These are the most recent versions (is_live=true) with status='published'.
     * Use this scope for counting and public display.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeLive($query)
    {
        return $query->where('is_live', '=', true)
            ->where('status', '=', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to only include published submissions.
     * Alias for scopeLive() for semantic clarity.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $this->scopeLive($query);
    }

    /**
     * Scope to only include current (active) submissions.
     * @deprecated Use scopeLive() instead. This scope is kept for backwards compatibility.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCurrent($query)
    {
        return $query->where('is_live', '=', true)
            ->where('status', '=', self::STATUS_PUBLISHED);
    }

    /**
     * Scope to only include the most recent version of each SGC ID.
     * This includes both published and unpublished submissions (any status where is_live=true).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeMostRecent($query)
    {
        return $query->where('is_live', '=', true);
    }

    /**
     * Scope to get all versions of a specific SGC ID.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $uuid The SGC ID (uuid)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllVersions($query, $uuid)
    {
        return $query->where('uuid', '=', $uuid)->orderBy('version_number', 'desc');
    }

    /**
     * Check if this submission version is unpublished (removed).
     * A submission is unpublished if it is the most recent version (is_live=true) with status='unpublished'.
     *
     * @return bool
     */
    public function isUnpublished(): bool
    {
        return $this->is_live && $this->status === self::STATUS_UNPUBLISHED;
    }

    /**
     * Check if this submission version is published.
     *
     * @return bool
     */
    public function isPublished(): bool
    {
        return $this->is_live && $this->status === self::STATUS_PUBLISHED;
    }

    /**
     * Check if this submission version is historical (superseded by a newer version).
     *
     * @return bool
     */
    public function isHistorical(): bool
    {
        return !$this->is_live;
    }

    /**
     * Get the display ID (SGC ID with version number).
     * Format: SGC-XXXXXX.N (e.g., SGC-100001.2)
     *
     * @return string
     */
    public function getDisplayIdAttribute(): string
    {
        return $this->uuid . '.' . ($this->version_number ?? 1);
    }


    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->curie} {$this->title}";
    }

    /**
     * Render markdown text to HTML.
     *
     * @param string|null $text
     * @return string
     */
    public function renderMarkdown($text)
    {
        if (empty($text)) {
            return '';
        }

        $converter = new CommonMarkConverter([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
        ]);

        return $converter->convert($text);
    }

    /**
     * Get PubMed articles for this submission's PMIDs from gencc-sub API
     *
     * @return array|null Array of PubMed articles or null if no PMIDs or API error
     */
    public function getPubmedArticles()
    {
        if (empty($this->submitted_as_pmids)) {
            return null;
        }

        // Extract PMIDs from the stored format
        $pmids = preg_split('/\D+/', $this->submitted_as_pmids, -1, PREG_SPLIT_NO_EMPTY);

        if (empty($pmids)) {
            return null;
        }

        // Get API URL from config
        $apiUrl = env('GENCC_SUB_API_URL', 'http://localhost:8001/api');

        try {
            // Make API request with comma-separated PMIDs
            $url = $apiUrl . '/pubmed/' . implode(',', $pmids);
            $response = file_get_contents($url);

            if ($response === false) {
                return null;
            }

            $data = json_decode($response, true);

            // Handle single vs multiple results
            if (isset($data['articles'])) {
                return $data['articles'];
            } elseif (isset($data['pmid'])) {
                return [$data];
            }

            return null;
        } catch (\Exception $e) {
            \Log::warning('Failed to fetch PubMed data from gencc-sub API', [
                'pmids' => implode(',', $pmids),
                'error' => $e->getMessage()
            ]);
            return null;
        }
    }

    protected $casts = [
        'date' => 'date:Y-m-d',
        'released_at' => 'datetime',
        'is_current' => 'boolean',       // @deprecated - use is_live + status instead
        'is_live' => 'boolean',
        'version_number' => 'integer'
    ];







    protected $with = ['gene', 'disease', 'disease_original', 'classification', 'inheritance', 'submitter'];

    protected $fillable = [
        'uuid',
        'version_number',
        'is_current',        // @deprecated - use is_live + status instead
        'is_live',
        'status',
        'released_at',
        'order',
        'submitted_as_submission_id' ,
        'submitted_as_hgnc_id',
        'submitted_as_hgnc_symbol',
        'submitted_as_disease_id',
        'submitted_as_disease_name',
        'submitted_as_moi_id',
        'submitted_as_moi_name',
        'submitted_as_submitter_id',
        'submitted_as_submitter_name',
        'submitted_as_classification_id',
        'submitted_as_classification_name',
        'submitted_as_public_report_url',
        'submitted_as_notes',
        'submitted_as_date',
        'submitted_as_pmids',
        'submitted_as_assertion_criteria_url',
        'submitted_run_date',
        'from_submission_file_name',
        'from_submission_file_id',
        'private_notes'
    ];
}
