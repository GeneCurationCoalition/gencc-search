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

    public function submissionCountSummary()
    {
        return $this->releaseSubmissionCountSummary();
    }

    public static function submissionCountSummariesFor($submitters)
    {
        $summaries = collect();

        foreach ($submitters as $submitter) {
            $summaries->put($submitter->id, $submitter->releaseSubmissionCountSummary());
        }

        return $summaries;
    }

    public static function emptySubmissionCountSummary()
    {
        return [
            'total' => 0,
            'classificationCounts' => static::emptyClassificationCounts(),
            'displayCounts' => static::emptyDisplayCounts(),
        ];
    }

    public function releaseSubmissionCountSummary()
    {
        $counts = $this->counts;

        // This accepts only the precomputed release summary shape:
        // ['total' => int, 'by_classification' => [...]].
        if (!is_array($counts)) {
            return null;
        }

        if (empty($counts)) {
            return static::emptySubmissionCountSummary();
        }

        if (!array_key_exists('total', $counts) || !is_numeric($counts['total'])) {
            return null;
        }

        if (!array_key_exists('by_classification', $counts) || !is_array($counts['by_classification'])) {
            return null;
        }

        $classificationCounts = collect();
        $displayCounts = static::emptyDisplayCounts();
        $byClassification = $counts['by_classification'];
        $total = (int) $counts['total'];

        if ($total > 0 && empty($byClassification)) {
            return null;
        }

        foreach (static::curationClassificationMap() as $type => $classificationId) {
            $count = 0;

            foreach (static::curationClassificationNamesFor($type) as $name) {
                if (isset($byClassification[$name]['count'])) {
                    if (!is_numeric($byClassification[$name]['count'])) {
                        return null;
                    }

                    $count = (int) $byClassification[$name]['count'];
                    break;
                }
            }

            $classificationCounts->put($classificationId, $count);
            $displayCounts[$type] = $count;
        }

        return [
            'total' => $total,
            'classificationCounts' => $classificationCounts,
            'displayCounts' => $displayCounts,
        ];
    }

    protected static function emptyDisplayCounts()
    {
        return collect(static::curationClassificationMap())
            ->mapWithKeys(function ($classificationId, $type) {
                return [$type => 0];
            })
            ->all();
    }

    protected static function emptyClassificationCounts()
    {
        return collect(static::curationClassificationMap())
            ->mapWithKeys(function ($classificationId) {
                return [$classificationId => 0];
            });
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
     * Get text_contact attribute (formats contact info from contact user).
     * Returns HTML with contact user's name, title, phone, and email.
     * Returns placeholder text if no contact user is found.
     */
    public function getTextContactAttribute()
    {
        try {
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
        } catch (\Exception $e) {
            // Database error (e.g., users table doesn't exist in test environment)
            // Fall through to return placeholder
        }

        // No contact user found - return placeholder message
        return '<span class="text-gray-400 italic">No contact designated</span>';
    }

    /**
     * Get logo attribute - returns data URI from logo_contents.
     * The logo is stored as base64-encoded binary in logo_contents with logo_mime_type.
     * Returns a placeholder SVG if no logo is available.
     */
    public function getLogoAttribute()
    {
        $contents = $this->attributes['logo_contents'] ?? null;
        $mimeType = $this->attributes['logo_mime_type'] ?? 'image/png';

        if ($contents) {
            return 'data:' . $mimeType . ';base64,' . $contents;
        }

        // Fallback to legacy path field if no binary content
        $legacyLogo = $this->attributes['logo'] ?? null;
        if ($legacyLogo) {
            return $legacyLogo;
        }

        // Return placeholder SVG when no logo exists
        return $this->getNoLogoPlaceholder();
    }

    /**
     * Check if submitter has a real logo (not placeholder).
     */
    public function getHasLogoAttribute(): bool
    {
        $contents = $this->attributes['logo_contents'] ?? null;
        $legacyLogo = $this->attributes['logo'] ?? null;

        return !empty($contents) || !empty($legacyLogo);
    }

    /**
     * Generate a simple SVG placeholder for submitters without logos.
     * Uses the submitter's name as the placeholder text.
     */
    protected function getNoLogoPlaceholder(): string
    {
        $name = htmlspecialchars($this->attributes['name'] ?? 'Unknown', ENT_XML1, 'UTF-8');

        $svg = '<svg xmlns="http://www.w3.org/2000/svg" width="400" height="200" viewBox="0 0 400 200">'
            . '<rect width="400" height="200" fill="#f3f4f6"/>'
            . '<text x="200" y="100" font-family="Arial, sans-serif" font-size="20" fill="#6b7280" text-anchor="middle" dominant-baseline="middle">' . $name . '</text>'
            . '</svg>';

        return 'data:image/svg+xml;base64,' . base64_encode($svg);
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
        if (!empty($this->counts['submissions'])) {
            return (int) $this->counts['submissions'];
        }
        if (isset($this->attributes['count_submissions'])) {
            return (int) $this->attributes['count_submissions'];
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
        'allow_submissions',
        'downloadable',
        'member',
        'counts',
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
