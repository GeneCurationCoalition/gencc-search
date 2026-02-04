<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Traits\ModelTransform;
use App\Traits\DisplayTransform;

class Submitter extends Model
{
    use HasFactory;
    use ModelTransform;
    use DisplayTransform;

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
     * Get text_assertions attribute (returns 'assertion' column).
     */
    public function getTextAssertionsAttribute()
    {
        return $this->attributes['assertion'] ?? null;
    }

    /**
     * Get text_contact attribute (returns from 'contacts' JSON).
     */
    public function getTextContactAttribute()
    {
        $contacts = $this->attributes['contacts'] ?? null;
        if (is_string($contacts)) {
            $contacts = json_decode($contacts, true);
        }
        return is_array($contacts) ? ($contacts['text'] ?? null) : null;
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

    // =========================================================================
    // Curations count accessors - compute from submissions relationship
    // Classification IDs: 1=Definitive, 2=Strong, 3=Moderate, 4=Supportive,
    // 5=Limited, 6=Disputed, 7=Refuted, 8=Animal Model, 9=No Known Disease
    // =========================================================================

    /**
     * Get curations_definitive - count submissions with classification_id = 1.
     */
    public function getCurationsDefinitiveAttribute()
    {
        if (!empty($this->counts['definitive'])) {
            return $this->counts['definitive'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 1)->count()
            : $this->submissions()->where('classification_id', 1)->count();
    }

    /**
     * Get curations_strong - count submissions with classification_id = 2.
     */
    public function getCurationsStrongAttribute()
    {
        if (!empty($this->counts['strong'])) {
            return $this->counts['strong'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 2)->count()
            : $this->submissions()->where('classification_id', 2)->count();
    }

    /**
     * Get curations_moderate - count submissions with classification_id = 3.
     */
    public function getCurationsModerateAttribute()
    {
        if (!empty($this->counts['moderate'])) {
            return $this->counts['moderate'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 3)->count()
            : $this->submissions()->where('classification_id', 3)->count();
    }

    /**
     * Get curations_supportive - count submissions with classification_id = 4.
     */
    public function getCurationsSupportiveAttribute()
    {
        if (!empty($this->counts['supportive'])) {
            return $this->counts['supportive'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 4)->count()
            : $this->submissions()->where('classification_id', 4)->count();
    }

    /**
     * Get curations_limited - count submissions with classification_id = 5.
     */
    public function getCurationsLimitedAttribute()
    {
        if (!empty($this->counts['limited'])) {
            return $this->counts['limited'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 5)->count()
            : $this->submissions()->where('classification_id', 5)->count();
    }

    /**
     * Get curations_disputed - count submissions with classification_id = 6.
     */
    public function getCurationsDisputedAttribute()
    {
        if (!empty($this->counts['disputed'])) {
            return $this->counts['disputed'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 6)->count()
            : $this->submissions()->where('classification_id', 6)->count();
    }

    /**
     * Get curations_refuted - count submissions with classification_id = 7.
     */
    public function getCurationsRefutedAttribute()
    {
        if (!empty($this->counts['refuted'])) {
            return $this->counts['refuted'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 7)->count()
            : $this->submissions()->where('classification_id', 7)->count();
    }

    /**
     * Get curations_animal - count submissions with classification_id = 8.
     */
    public function getCurationsAnimalAttribute()
    {
        if (!empty($this->counts['animal'])) {
            return $this->counts['animal'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 8)->count()
            : $this->submissions()->where('classification_id', 8)->count();
    }

    /**
     * Get curations_noknown - count submissions with classification_id = 9.
     */
    public function getCurationsNoknownAttribute()
    {
        if (!empty($this->counts['noknown'])) {
            return $this->counts['noknown'];
        }
        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', 9)->count()
            : $this->submissions()->where('classification_id', 9)->count();
    }

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
