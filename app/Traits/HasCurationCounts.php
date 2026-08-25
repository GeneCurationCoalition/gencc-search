<?php

namespace App\Traits;

/**
 * Trait for models that have curation count accessors.
 *
 * Classification IDs: 1=Definitive, 2=Strong, 3=Moderate, 4=Supportive,
 * 5=Limited, 6=Disputed, 7=Refuted, 8=Animal Model, 9=No Known Disease
 */
trait HasCurationCounts
{
    /**
     * Curation type to classification ID mapping.
     */
    protected static array $curationClassificationMap = [
        'definitive' => 1,
        'strong' => 2,
        'moderate' => 3,
        'supportive' => 4,
        'limited' => 5,
        'disputed' => 6,
        'refuted' => 7,
        'animal' => 8,
        'noknown' => 9,
    ];

    protected static function curationClassificationMap(): array
    {
        return static::$curationClassificationMap;
    }

    protected static array $curationClassificationNames = [
        'definitive' => ['Definitive'],
        'strong' => ['Strong'],
        'moderate' => ['Moderate'],
        'supportive' => ['Supportive'],
        'limited' => ['Limited'],
        'disputed' => ['Disputed Evidence', 'Disputed'],
        'refuted' => ['Refuted Evidence', 'Refuted'],
        'animal' => ['Animal Model Only', 'Animal Model'],
        'noknown' => ['No Known Disease Relationship', 'No Known'],
    ];

    protected static function curationClassificationNamesFor(string $type): array
    {
        return static::$curationClassificationNames[$type] ?? [];
    }

    /**
     * Get a curation count by type.
     *
     * Checks in order:
     * 1. Release JSON counts.by_classification entries
     * 2. Flat JSON count keys
     * 3. Loaded or queryable submissions relationship
     *
     * @param string $type The curation type (e.g., 'definitive', 'strong')
     * @return int
     */
    protected function getCurationCount(string $type): int
    {
        $column = 'curations_' . $type;

        if (isset($this->counts['by_classification'])) {
            foreach (static::curationClassificationNamesFor($type) as $classificationName) {
                if (isset($this->counts['by_classification'][$classificationName]['count'])) {
                    return (int) $this->counts['by_classification'][$classificationName]['count'];
                }
            }
        }

        if (array_key_exists($column, $this->counts ?? [])) {
            return (int) $this->counts[$column];
        }

        if (array_key_exists($type, $this->counts ?? [])) {
            return (int) $this->counts[$type];
        }

        // Compute from relationship if available
        $classificationId = static::$curationClassificationMap[$type] ?? null;
        if ($classificationId === null) {
            return 0;
        }

        return $this->relationLoaded('submissions')
            ? $this->submissions->where('classification_id', $classificationId)->count()
            : $this->submissions()->where('classification_id', $classificationId)->count();
    }

    /**
     * Get curations with null classification.
     */
    protected function getNullCurationCount(): int
    {
        if (array_key_exists('curations_nul', $this->counts ?? [])) {
            return (int) $this->counts['curations_nul'];
        }

        if (array_key_exists('nul', $this->counts ?? [])) {
            return (int) $this->counts['nul'];
        }

        return $this->relationLoaded('submissions')
            ? $this->submissions->whereNull('classification_id')->count()
            : $this->submissions()->whereNull('classification_id')->count();
    }

    // =========================================================================
    // Curation Count Accessors
    // =========================================================================

    public function getCurationsDefinitiveAttribute(): int
    {
        return $this->getCurationCount('definitive');
    }

    public function getCurationsStrongAttribute(): int
    {
        return $this->getCurationCount('strong');
    }

    public function getCurationsModerateAttribute(): int
    {
        return $this->getCurationCount('moderate');
    }

    public function getCurationsSupportiveAttribute(): int
    {
        return $this->getCurationCount('supportive');
    }

    public function getCurationsLimitedAttribute(): int
    {
        return $this->getCurationCount('limited');
    }

    public function getCurationsDisputedAttribute(): int
    {
        return $this->getCurationCount('disputed');
    }

    public function getCurationsRefutedAttribute(): int
    {
        return $this->getCurationCount('refuted');
    }

    public function getCurationsAnimalAttribute(): int
    {
        return $this->getCurationCount('animal');
    }

    public function getCurationsNoknownAttribute(): int
    {
        return $this->getCurationCount('noknown');
    }

    public function getCurationsNulAttribute(): int
    {
        return $this->getNullCurationCount();
    }
}
