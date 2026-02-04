<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTransform;
use App\Traits\DisplayTransform;
use App\Traits\HasCurationCounts;

class Submitter extends Model
{
    use HasFactory;
    use ModelTransform;
    use DisplayTransform;
    use HasCurationCounts;

    /**
     * The attributes that should be cast to native types.
     * Note: gencc-sub uses individual columns instead of JSON for counts.
     *
     * @var array
     */
    protected $casts = [
        'downloadable' => 'boolean',
        'member' => 'boolean',
        'counts' => 'array',
    ];

    public function scopeCurie($query, $id)
    {
        return $query->where('curie', '=', $id)->orderBy('updated_at', 'asc');
    }

    /**
     * Scope by ident (unique identifier field).
     * In gencc-sub, this column is named 'ident'.
     */
    public function scopeIdent($query, $id)
    {
        return $query->where('ident', '=', $id)->orderBy('updated_at', 'asc');
    }

    /**
     * Get all the live published (publicly visible) submissions for this submitter.
     * Filters by is_live=true (most recent version) AND status='published'.
     */
    public function submissions()
    {
        return $this->hasMany('App\Submission')
            ->where('is_live', '=', true)
            ->where('status', '=', Submission::STATUS_PUBLISHED)
            ->orderBy('classification_id')
            ->orderBy('report_date');
    }

    /**
     * Get all users associated with this submitter.
     */
    public function users()
    {
        return $this->belongsToMany('App\User', 'submitter_user')
            ->withPivot('is_contact')
            ->withTimestamps();
    }

    /**
     * Get the contact user for this submitter (where is_contact=1).
     */
    public function contactUser()
    {
        return $this->belongsToMany('App\User', 'submitter_user')
            ->withPivot('is_contact')
            ->wherePivot('is_contact', true)
            ->limit(1);
    }

    // =========================================================================
    // Accessors for backward compatibility
    // The gencc-sub database uses these column names:
    // - ident (not uuid)
    // - name (not title)
    // - counts (JSON) for curation counts
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
     * Get text_descriptions attribute (returns 'description' column).
     */
    public function getTextDescriptionsAttribute()
    {
        return $this->attributes['description'] ?? null;
    }

    /**
     * Get text_assertions attribute.
     * Returns unique assertion criteria URLs from live, published submissions.
     * Each URL is separated by <br> for display in the view.
     */
    public function getTextAssertionsAttribute()
    {
        // Get unique criteria URLs from live, published submissions
        $criteriaUrls = $this->submissions()
            ->pluck('submission_data')
            ->map(function ($data) {
                return $data['criteria']['url'] ?? null;
            })
            ->filter(function ($url) {
                // Filter out empty values and non-URL values like "PMID: 12345"
                return !empty($url) && (
                    str_starts_with($url, 'http://') ||
                    str_starts_with($url, 'https://')
                );
            })
            ->unique()
            ->values();

        if ($criteriaUrls->isEmpty()) {
            return null;
        }

        return $criteriaUrls->implode('<br>');
    }

    /**
     * Get text_contact attribute (formats contact info from contact user).
     * Returns HTML with contact user's name, title, phone, and email.
     * Returns placeholder text if no contact user is found.
     */
    public function getTextContactAttribute()
    {
        // Try to get contact from the associated user
        $contactUser = $this->relationLoaded('contactUser')
            ? $this->contactUser->first()
            : $this->contactUser()->first();

        if ($contactUser) {
            $lines = [];

            // Line 1: Name, Title
            if (!empty($contactUser->name)) {
                $nameLine = e($contactUser->name);
                if (!empty($contactUser->title)) {
                    $nameLine .= ', ' . e($contactUser->title);
                }
                $lines[] = $nameLine;
            }

            // Line 2: Phone (if exists)
            if (!empty($contactUser->phone)) {
                $lines[] = 'Phone: ' . e($contactUser->phone);
            }

            // Line 3: Email (if exists)
            if (!empty($contactUser->email)) {
                $lines[] = 'Email: ' . e($contactUser->email);
            }

            if (!empty($lines)) {
                return implode('<br>', $lines);
            }
        }

        // No contact user found - return placeholder message
        return '<span class="text-gray-400 italic">No contact designated</span>';
    }

    /**
     * Get logo attribute - returns data URI from logo_contents.
     * The logo is stored as base64-encoded binary in logo_contents with logo_mime_type.
     */
    public function getLogoAttribute()
    {
        $contents = $this->attributes['logo_contents'] ?? null;
        $mimeType = $this->attributes['logo_mime_type'] ?? 'image/png';

        if ($contents) {
            return 'data:' . $mimeType . ';base64,' . $contents;
        }

        // Fallback to legacy path field if no binary content
        return $this->attributes['logo'] ?? null;
    }

    // Curation count accessors provided by HasCurationCounts trait

    /**
     * Get count_submissions - total submissions count.
     */
    public function getCountSubmissionsAttribute()
    {
        if (!empty($this->counts['submissions'])) {
            return $this->counts['submissions'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->count()
            : $this->submissions()->count();
    }

    /**
     * Get count_unique_genes - count unique genes.
     */
    public function getCountUniqueGenesAttribute()
    {
        if (!empty($this->counts['unique_genes'])) {
            return $this->counts['unique_genes'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->pluck('gene_id')->unique()->count()
            : $this->submissions()->distinct('gene_id')->count('gene_id');
    }

    /**
     * Get count_unique_diseases - count unique diseases (MONDO equivalents).
     */
    public function getCountUniqueDiseasesAttribute()
    {
        if (!empty($this->counts['unique_diseases'])) {
            return $this->counts['unique_diseases'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->pluck('disease_id')->unique()->count()
            : $this->submissions()->distinct('disease_id')->count('disease_id');
    }

    protected $fillable = [
        'title',
        'uuid',
        'website',
        'curie',
        'path_logo',
        'text_descriptions',
        'text_assertions',
        'text_contact',
        'text_disclaimer',
        'status',
        'downloadable',
        'member',
        'count_submissions',
        'count_unique_genes',
        'count_unique_diseases',
        'curations_definitive',
        'curations_strong',
        'curations_moderate',
        'curations_limited',
        'curations_disputed',
        'curations_refuted',
        'curations_animal',
        'curations_noknown',
        'curations_supportive',
        'curations_nul'
    ];

}
