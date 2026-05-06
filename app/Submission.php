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

    /**
     * Inheritance (Mode of Inheritance) relationship.
     * Uses inheritance_id column which references the inheritances table.
     */
    public function inheritance()
    {
        return $this->belongsTo('App\Inheritance', 'inheritance_id');
    }

    public function classification()
    {
        return $this->belongsTo('App\Classification');
    }

    public function disease()
    {
        return $this->belongsTo('App\Disease');
    }

    /**
     * Original disease (as submitted).
     * NOTE: gencc-sub uses original_disease_id instead of disease_original_id
     */
    public function disease_original()
    {
        return $this->belongsTo('App\Disease', 'original_disease_id');
    }

    public function submitter()
    {
        return $this->belongsTo('App\Submitter');
    }

    public function pubmeds()
    {
        return $this->belongsToMany('App\Pubmed', 'pubmed_submission');
    }

    /**
     * Get PubMed article metadata for this submission.
     * Returns an array of articles with pmid, firstAuthor, year, and title.
     * Column mapping from pubmeds table:
     *   - sortfirstauthor -> firstAuthor
     *   - pubdate/sortpubdate -> year (extracted)
     *   - title -> title
     *
     * @return array
     */
    public function getPubmedArticles(): array
    {
        $articles = [];
        foreach ($this->pubmeds as $pubmed) {
            // Extract year from pubdate (format: "2014 Jun 18" or similar)
            $year = null;
            if (!empty($pubmed->sortpubdate)) {
                // sortpubdate format is typically YYYY/MM/DD
                $year = substr($pubmed->sortpubdate, 0, 4);
            } elseif (!empty($pubmed->pubdate)) {
                // pubdate might be "2014 Jun 18" format
                preg_match('/(\d{4})/', $pubmed->pubdate, $matches);
                $year = $matches[1] ?? null;
            }

            $articles[] = [
                'pmid' => $pubmed->pmid ?? $pubmed->uid ?? $pubmed->id,
                'firstAuthor' => $pubmed->sortfirstauthor ?? null,
                'year' => $year,
                'title' => $pubmed->title ?? null,
            ];
        }
        return $articles;
    }

    /**
     * Status constants
     */
    const STATUS_PUBLISHED = 'published';
    const STATUS_UNPUBLISHED = 'unpublished';

    // =========================================================================
    // Accessors for backward compatibility with gencc-sub field names
    // =========================================================================

    /**
     * Get uuid accessor (returns the uuid column value).
     */
    public function getUuidAttribute()
    {
        return $this->attributes['uuid'] ?? null;
    }

    /**
     * Get sid accessor (returns the sid column value).
     */
    public function getSidAttribute()
    {
        return $this->attributes['sid'] ?? null;
    }

    /**
     * Get disease_original_id (maps to gencc-sub's original_disease_id)
     */
    public function getDiseaseOriginalIdAttribute()
    {
        return $this->original_disease_id ?? $this->attributes['disease_original_id'] ?? null;
    }

    /**
     * Get submitted_as_date (maps to gencc-sub's report_date)
     */
    public function getSubmittedAsDateAttribute()
    {
        return $this->report_date ?? $this->attributes['submitted_as_date'] ?? null;
    }

    /**
     * Get submitted_as_hgnc_id from original_submission_data.gene.id
     */
    public function getSubmittedAsHgncIdAttribute()
    {
        $data = $this->original_submission_data;
        return $data['gene']['id'] ?? null;
    }

    /**
     * Get submitted_as_hgnc_symbol from original_submission_data.gene.symbol
     */
    public function getSubmittedAsHgncSymbolAttribute()
    {
        $data = $this->original_submission_data;
        return $data['gene']['symbol'] ?? null;
    }

    /**
     * Get submitted_as_disease_id from original_submission_data.disease.id
     */
    public function getSubmittedAsDiseaseIdAttribute()
    {
        $data = $this->original_submission_data;
        return $data['disease']['id'] ?? null;
    }

    /**
     * Get submitted_as_disease_name from original_submission_data.disease.name
     */
    public function getSubmittedAsDiseaseNameAttribute()
    {
        $data = $this->original_submission_data;
        return $data['disease']['name'] ?? null;
    }

    /**
     * Get submitted_as_moi_id from original_submission_data.moi.id
     */
    public function getSubmittedAsMoiIdAttribute()
    {
        $data = $this->original_submission_data;
        return $data['moi']['id'] ?? null;
    }

    /**
     * Get submitted_as_moi_name from original_submission_data.moi.name
     */
    public function getSubmittedAsMoiNameAttribute()
    {
        $data = $this->original_submission_data;
        return $data['moi']['name'] ?? null;
    }

    /**
     * Get submitted_as_submitter_id from original_submission_data.additional_information.submitter_curie
     */
    public function getSubmittedAsSubmitterIdAttribute()
    {
        $data = $this->original_submission_data;
        return $data['additional_information']['submitter_curie'] ?? null;
    }

    /**
     * Get submitted_as_submitter_name from original_submission_data.additional_information.submitter_title
     */
    public function getSubmittedAsSubmitterNameAttribute()
    {
        $data = $this->original_submission_data;
        return $data['additional_information']['submitter_title'] ?? null;
    }

    /**
     * Get submitted_as_classification_id from original_submission_data.classification.id
     */
    public function getSubmittedAsClassificationIdAttribute()
    {
        $data = $this->original_submission_data;
        return $data['classification']['id'] ?? null;
    }

    /**
     * Get submitted_as_classification_name from original_submission_data.classification.name
     */
    public function getSubmittedAsClassificationNameAttribute()
    {
        $data = $this->original_submission_data;
        return $data['classification']['name'] ?? null;
    }

    /**
     * Get submitted_as_public_report_url from original_submission_data.report.ext_url
     */
    public function getSubmittedAsPublicReportUrlAttribute()
    {
        $data = $this->original_submission_data;
        return $data['report']['ext_url'] ?? null;
    }

    /**
     * Get submitted_as_notes from original_submission_data.notes.display
     */
    public function getSubmittedAsNotesAttribute()
    {
        $data = $this->original_submission_data;
        return $data['notes']['display'] ?? null;
    }

    /**
     * Get submitted_as_pmids from normalized_pmids (with space after commas)
     */
    public function getSubmittedAsPmidsAttribute()
    {
        $pmids = $this->attributes['normalized_pmids'] ?? null;
        if ($pmids) {
            // Add space after commas for readability
            return str_replace(',', ', ', $pmids);
        }
        return null;
    }

    /**
     * Get submitted_as_assertion_criteria_url from original_submission_data.criteria.url
     */
    public function getSubmittedAsAssertionCriteriaUrlAttribute()
    {
        $data = $this->original_submission_data;
        return $data['criteria']['url'] ?? null;
    }

    /**
     * Get submitted_as_submission_id from original_submission_data.additional_information.submitted_as_submission_id
     */
    public function getSubmittedAsSubmissionIdAttribute()
    {
        $data = $this->original_submission_data;
        return $data['additional_information']['submitted_as_submission_id'] ?? null;
    }

    /**
     * Get submitted_run_date from submitted_at (DATE only, no time)
     */
    public function getSubmittedRunDateAttribute()
    {
        $submittedAt = $this->attributes['submitted_at'] ?? null;
        if ($submittedAt) {
            // Return just the date portion (YYYY-MM-DD)
            return substr($submittedAt, 0, 10);
        }
        return null;
    }

    // =========================================================================
    // Query Scopes
    // =========================================================================

    public function scopeCurie($query, $id)
    {
        // is_live = most recent version, status = 'published' for publicly visible
        return $query->where('curie', '=', $id)
            ->where('is_live', '=', true)
            ->where('status', '=', self::STATUS_PUBLISHED)
            ->orderBy('updated_at', 'asc');
    }

    /**
     * Scope by sid (submission ID field).
     * Note: The actual database column is 'sid' in gencc-sub.
     */
    public function scopeSid($query, $id)
    {
        return $query->where('sid', '=', $id)
            ->where('is_live', '=', true)
            ->where('status', '=', self::STATUS_PUBLISHED)
            ->orderBy('updated_at', 'asc');
    }

    /**
     * Scope to find a submission by display ID (sid.version format).
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
            $sid = $matches[1];
            $version = (int) $matches[2];
            return $query->where('sid', '=', $sid)->where('version_number', '=', $version);
        }

        // No version number - return the most recent published version only
        // Unpublished submissions can only be accessed via explicit versioned URLs
        return $query->where('sid', '=', $displayId)
            ->where('is_live', '=', true)
            ->where('status', '=', self::STATUS_PUBLISHED);
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
     * @param string $sid The SGC ID (sid)
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeAllVersions($query, $sid)
    {
        return $query->where('sid', '=', $sid)->orderBy('version_number', 'desc');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

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
        $id = $this->attributes['sid'] ?? $this->attributes['uuid'] ?? '';
        return $id . '.' . ($this->version_number ?? 1);
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

    protected $casts = [
        'date' => 'date:Y-m-d',
        'released_at' => 'datetime',
        'is_live' => 'boolean',
        'version_number' => 'integer',
        'submission_data' => 'array',
        'original_submission_data' => 'array',
        'evidence' => 'array',
    ];

    protected $with = ['gene', 'disease', 'disease_original', 'classification', 'inheritance', 'submitter'];

    protected $fillable = [
        'uuid',
        'sid',
        'version_number',
        'is_live',
        'status',
        'released_at',
        'order',
        'submission_data',
        'evidence',
        'report_date',
        'report_url',
        'original_disease_id',
        'inheritance_id',
        'private_notes'
    ];
}
