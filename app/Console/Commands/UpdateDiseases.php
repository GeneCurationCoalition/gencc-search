<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Disease;
use App\Submission;
use App\Console\Traits\CachesFileHeaders;

use Setting;

class UpdateDiseases extends Command
{
    use CachesFileHeaders;

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:diseases';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update disease information from MONDO, OMIM, Orphanet with comprehensive reconciliation';

    /**
     * Track diseases seen in this update run (by curie)
     */
    protected $seenMondoIds = [];
    protected $seenOmimIds = [];
    protected $seenOrphanetIds = [];

    /**
     * Track exact_match relationships from MONDO
     */
    protected $mondoExactMatchOmim = [];  // ['OMIM:123' => 'MONDO:456']
    protected $mondoExactMatchOrphanet = [];  // ['Orphanet:123' => 'MONDO:456']

    /**
     * Track xref relationships from MONDO (non-exact_match)
     */
    protected $mondoXrefOmim = [];  // ['OMIM:123' => ['MONDO:456', 'MONDO:789']]
    protected $mondoXrefOrphanet = [];  // ['Orphanet:123' => ['MONDO:456']]

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Updating disease information with comprehensive reconciliation');

        // MONDO must go first - it determines the canonical disease set and mappings
        // Each method returns true if updates were made, false if skipped
        $mondoUpdated = $this->mondo();

        // OMIM and Orphanet updates using MONDO mappings
        $omimUpdated = $this->omim();
        $orphanetUpdated = $this->orphanet();

        // Only run post-processing if at least one source was updated
        if ($mondoUpdated || $omimUpdated || $orphanetUpdated) {
            // Step 3: Assign mondo_id to OMIM diseases using Orphanet equivalence
            $this->assignMondoIdViaOrphanet();

            // Reconcile all existing diseases not seen in this update
            $this->reconcileUnseenDiseases();
        } else {
            $this->info('All source files unchanged - skipping post-processing');
        }

        $this->info('Disease update complete');

        return 0;
    }


    /**
     * Update disease information from MONDO
     *
     * This extracts:
     * 1. All MONDO diseases
     * 2. exact_match relationships (from basicPropertyValues)
     * 3. xref relationships (from xrefs)
     */
    protected function mondo()
    {
        $this->info('...retrieving data from MONDO');

        $url = 'http://purl.obolibrary.org/obo/mondo/mondo-with-equivalents.json';
        $fileIdentifier = "mondo_with_equivalents";
        $cacheFilename = "mondo-with-equivalents.json";

        // Check if file needs updating
        if (!$this->shouldUpdateFile($fileIdentifier, $url, 'diseases')) {
            $this->info('...MONDO update skipped (file unchanged)');

            // Still need to load mappings from existing MONDO diseases for OMIM/Orphanet phases
            $this->loadExistingMondoMappings();

            return false;
        }

        // Download and cache the file
        $data = $this->downloadAndCacheFile($url, $cacheFilename);

        if ($data === null) {
            // Try to use cached file
            $data = $this->getCachedFile($cacheFilename);

            if ($data === null) {
                $this->error('......FAILED to retrieve data from MONDO');
                return false;
            }
        }

        // Decode the JSON data
        $json = json_decode($data);

        if ($json === null) {
            $this->error('......FAILED to decode MONDO JSON');
            return false;
        }

        $this->info('...processing MONDO diseases and extracting mappings');

        $deprecatedCount = 0;

        // Loop through the nodes
        foreach ($json->graphs[0]->nodes as $node)
        {
            // MONDO uses underscore instead of colon
            $term = str_replace('_', ':', basename($node->id));

            if (strpos($term, 'MONDO') !== 0)
                continue;

            // Track this MONDO ID as seen
            $this->seenMondoIds[] = $term;

            // Check if deprecated
            $is_deprecated = $node->meta->deprecated ?? false;
            if ($is_deprecated) {
                $deprecatedCount++;
            }

            // Extract exact_match and xref mappings
            $this->extractMondoMappings($term, $node->meta ?? null);

            // Get existing disease record if it exists
            $existingDisease = Disease::where('curie', $term)->first();

            // Prepare disease data - preserve count columns
            $d = [
                'type' => Disease::TYPE_MONDO,
                'mondo_id' => null,  // MONDO diseases don't have mondo_id
                'curie' => $term,
                'xrefs' => $this->x_mondo_xrefs($node->meta ?? null),
            ];

            // Handle name based on deprecation status
            if ($is_deprecated) {
                // For deprecated diseases, always set deprecated_name to the exact incoming name (with "obsolete " prefix)
                $d['deprecated_name'] = $node->lbl ?? "";

                // For new deprecated diseases, set initial name (without "obsolete " prefix)
                if (!$existingDisease) {
                    $d['name'] = $this->x_mondo_label($node->lbl ?? null);
                }
                // Don't update name, description, or synonyms for existing deprecated diseases (preserve historical data)
            } else {
                // For active diseases, update name, description, and synonyms normally
                $d['name'] = $node->lbl ?? "";
                $d['description'] = $node->meta->definition->val ?? '';
                $d['synonyms'] = $this->x_mondo_synonym($node->meta->synonyms ?? []);
            }

            $d['status'] = ($is_deprecated ? Disease::STATUS_DEPRECATED : Disease::STATUS_ACTIVE);

            $disease = Disease::updateOrCreate(['curie' => $term], $d);
        }

        // Update cached headers after successful processing
        $this->updateCachedHeaders($fileIdentifier, $url);

        $this->info('...MONDO update complete (' . count($this->seenMondoIds) . ' diseases processed)');
        if ($deprecatedCount > 0) {
            $this->info('...found ' . $deprecatedCount . ' deprecated MONDO diseases (deprecated: true)');
        }
        $this->info('...found ' . count($this->mondoExactMatchOmim) . ' OMIM exact_match relationships');
        $this->info('...found ' . count($this->mondoExactMatchOrphanet) . ' Orphanet exact_match relationships');

        return true;
    }


    /**
     * Extract exact_match and xref mappings from MONDO metadata
     */
    protected function extractMondoMappings($mondoCurie, $meta)
    {
        if ($meta === null)
            return;

        // Extract exact_match from basicPropertyValues
        foreach (($meta->basicPropertyValues ?? []) as $property) {
            if ($property->pred === 'http://www.w3.org/2004/02/skos/core#exactMatch') {
                $val = $property->val;

                // Check for OMIM exact_match (NOT OMIMPS)
                if (strpos($val, '/omim.org/entry/') !== false) {
                    $omimId = basename($val);
                    $omimCurie = 'OMIM:' . $omimId;

                    // Validate uniqueness
                    if (isset($this->mondoExactMatchOmim[$omimCurie]) &&
                        $this->mondoExactMatchOmim[$omimCurie] !== $mondoCurie) {
                        $this->warn(
                            "OMIM {$omimCurie} has multiple MONDO exact_match: " .
                            "{$this->mondoExactMatchOmim[$omimCurie]} and {$mondoCurie}"
                        );
                        continue;
                    }

                    $this->mondoExactMatchOmim[$omimCurie] = $mondoCurie;
                }

                // Check for Orphanet exact_match
                if (strpos($val, 'orpha.net') !== false || strpos($val, 'Orphanet') !== false) {
                    // Extract Orphanet ID from various URL formats
                    if (preg_match('/Orphanet[:\/_](\d+)/', $val, $matches)) {
                        $orphanetId = $matches[1];
                        $orphanetCurie = 'Orphanet:' . $orphanetId;

                        // Validate uniqueness
                        if (isset($this->mondoExactMatchOrphanet[$orphanetCurie]) &&
                            $this->mondoExactMatchOrphanet[$orphanetCurie] !== $mondoCurie) {
                            $this->warn(
                                "Orphanet {$orphanetCurie} has multiple MONDO exact_match: " .
                                "{$this->mondoExactMatchOrphanet[$orphanetCurie]} and {$mondoCurie}"
                            );
                            continue;
                        }

                        $this->mondoExactMatchOrphanet[$orphanetCurie] = $mondoCurie;
                    }
                }
            }
        }

        // Extract xrefs (these are NOT exact_match)
        foreach (($meta->xrefs ?? []) as $property) {
            $val = explode(':', $property->val);

            switch ($val[0]) {
                case 'OMIM':  // Regular OMIM xref (NOT OMIMPS)
                    $omimCurie = 'OMIM:' . $val[1];
                    // Skip if already an exact_match
                    if (!isset($this->mondoExactMatchOmim[$omimCurie])) {
                        if (!isset($this->mondoXrefOmim[$omimCurie])) {
                            $this->mondoXrefOmim[$omimCurie] = [];
                        }
                        $this->mondoXrefOmim[$omimCurie][] = $mondoCurie;
                    }
                    break;

                case 'Orphanet':
                    $orphanetCurie = 'Orphanet:' . $val[1];
                    // Skip if already an exact_match
                    if (!isset($this->mondoExactMatchOrphanet[$orphanetCurie])) {
                        if (!isset($this->mondoXrefOrphanet[$orphanetCurie])) {
                            $this->mondoXrefOrphanet[$orphanetCurie] = [];
                        }
                        $this->mondoXrefOrphanet[$orphanetCurie][] = $mondoCurie;
                    }
                    break;
            }
        }
    }


    /**
     * Load existing MONDO mappings when MONDO update is skipped
     *
     * Note: This reads from the processed xrefs format stored in the database,
     * not the raw MONDO JSON format. The database stores xrefs as an object with
     * keys like omim_id, orpha_id, etc.
     */
    protected function loadExistingMondoMappings()
    {
        $this->info('......loading existing MONDO mappings from database');

        $mondoDiseases = Disease::where('type', Disease::TYPE_MONDO)
            ->where('status', Disease::STATUS_ACTIVE)
            ->get();

        foreach ($mondoDiseases as $disease) {
            $this->seenMondoIds[] = $disease->curie;

            $xrefs = $disease->xrefs;
            if ($xrefs === null) {
                continue;
            }

            // Process OMIM mappings (stored as array in omim_id)
            $omimIds = $xrefs->omim_id ?? [];
            if (!is_array($omimIds)) {
                $omimIds = [$omimIds];
            }
            foreach ($omimIds as $omimId) {
                if ($omimId) {
                    $omimCurie = 'OMIM:' . $omimId;
                    $this->mondoExactMatchOmim[$omimCurie] = $disease->curie;
                }
            }

            // Process Orphanet mappings (stored as single value in orpha_id)
            $orphaId = $xrefs->orpha_id ?? null;
            if ($orphaId) {
                $orphaCurie = 'Orphanet:' . $orphaId;
                $this->mondoExactMatchOrphanet[$orphaCurie] = $disease->curie;
            }
        }
    }


    /**
     * Update disease information from OMIM
     *
     * Uses mimTitles.txt and MONDO mappings to create/update OMIM diseases
     */
    protected function omim()
    {
        $this->info('...retrieving data from OMIM');

        $key = Setting::get('omim', false);
        if (!$key)
        {
            $this->error('...ERROR, no OMIM key');
            return false;
        }

        $url = "https://data.omim.org/downloads/" . $key . "/mimTitles.txt";
        $fileIdentifier = "omim_mimTitles";
        $cacheFilename = "mimTitles.txt";

        // Check if file needs updating
        if (!$this->shouldUpdateFile($fileIdentifier, $url)) {
            $this->info('...OMIM update skipped (file unchanged)');

            // Still populate seenOmimIds for reconciliation
            $this->seenOmimIds = Disease::whereIn('type', [
                Disease::TYPE_OMIM,
                Disease::TYPE_OMIM_PLUS,
                Disease::TYPE_OMIM_NUMBER,
                Disease::TYPE_OMIM_CARET,
                Disease::TYPE_OMIM_PERCENT
            ])
            ->pluck('curie')
            ->toArray();

            return false;
        }

        // Download and cache the file
        $data = $this->downloadAndCacheFile($url, $cacheFilename);

        if ($data === null) {
            $data = $this->getCachedFile($cacheFilename);
            if ($data === null) {
                $this->error('......FAILED to retrieve data from OMIM');
                return false;
            }
        }

        $this->info('...processing OMIM diseases');

        $deprecatedCount = 0;

        // Skip copyright line
        $line = strtok($data, "\n");

        // Parse the rest
        while (($line = strtok("\n")) !== false)
        {
            $value = explode("\t", $line);

            // Ignore comments
            if (strpos($value[0], '#') === 0)
                continue;

            // Set type based on OMIM prefix
            $isDeprecated = false;
            switch ($value[0])
            {
                case 'Plus':
                    $type = Disease::TYPE_OMIM_PLUS;
                    break;
                case "Number Sign":
                    $type = Disease::TYPE_OMIM_NUMBER;
                    break;
                case "Caret":  // MOVED, MERGED, or REMOVED
                    $type = Disease::TYPE_OMIM_CARET;
                    $isDeprecated = true;
                    $deprecatedCount++;
                    break;
                case "Percent":
                    $type = Disease::TYPE_OMIM_PERCENT;
                    break;
                case "Asterisk":  // Gene - skip
                    continue 2;
                case "NULL":
                default:
                    $type = Disease::TYPE_OMIM;
                    break;
            }

            $omimId = $value[1];
            $curie = 'OMIM:' . $omimId;
            $newName = $value[2];

            // Track as seen
            $this->seenOmimIds[] = $curie;

            // Determine mondo_id using exact_match priority, then xrefs
            $mondoId = $this->determineMondoIdForOmim($curie);

            // Get existing disease
            $existingDisease = Disease::where('curie', $curie)->first();

            // Prepare data - preserve count columns
            $d = [
                'type' => $type,
                'mondo_id' => $mondoId,
                'curie' => $curie,
                'synonyms' => (empty($value[3]) ? [] : [$value[3]]),
                'xrefs' => ['include_titles' => $value[4] ?? null],
            ];

            // Handle name based on deprecation status
            if ($isDeprecated) {
                // For deprecated diseases, always set deprecated_name to the exact incoming name
                $d['deprecated_name'] = $newName;

                // For new deprecated diseases, set initial name (same as deprecated_name for OMIM)
                if (!$existingDisease) {
                    $d['name'] = $newName;
                    $d['description'] = null;
                }
                // Don't update name for existing deprecated diseases (preserve historical data)
            } else {
                // For active diseases, update name normally
                $d['name'] = $newName;
                $d['description'] = null;
            }

            $d['status'] = ($isDeprecated ? Disease::STATUS_DEPRECATED : Disease::STATUS_ACTIVE);

            $disease = Disease::updateOrCreate(['curie' => $curie], $d);
        }

        // Update cached headers
        $this->updateCachedHeaders($fileIdentifier, $url);

        $this->info('...OMIM update complete (' . count($this->seenOmimIds) . ' diseases processed)');
        if ($deprecatedCount > 0) {
            $this->info('...found ' . $deprecatedCount . ' deprecated/removed OMIM diseases (Caret prefix)');
        }

        return true;
    }


    /**
     * Determine the MONDO ID for an OMIM disease using priority:
     * 1. exact_match
     * 2. First xref found
     */
    protected function determineMondoIdForOmim($omimCurie)
    {
        // Priority 1: exact_match
        if (isset($this->mondoExactMatchOmim[$omimCurie])) {
            $mondoCurie = $this->mondoExactMatchOmim[$omimCurie];
            $mondoDisease = Disease::where('curie', $mondoCurie)->first();
            return $mondoDisease ? $mondoDisease->id : null;
        }

        // Priority 2: First xref
        if (isset($this->mondoXrefOmim[$omimCurie]) && count($this->mondoXrefOmim[$omimCurie]) > 0) {
            $mondoCurie = $this->mondoXrefOmim[$omimCurie][0];  // Use first one
            $mondoDisease = Disease::where('curie', $mondoCurie)->first();
            return $mondoDisease ? $mondoDisease->id : null;
        }

        return null;
    }


    /**
     * Update disease information from Orphanet
     */
    protected function orphanet()
    {
        $this->info('...retrieving data from Orphanet');

        $url = 'https://www.orphadata.com/data/xml/en_product1.xml';
        $fileIdentifier = 'orphanet_product1';
        $cacheFilename = 'en_product1.xml';

        // Check if file needs updating
        if (!$this->shouldUpdateFile($fileIdentifier, $url, 'diseases')) {
            $this->info('...Orphanet update skipped (file unchanged)');

            // Still need to track seen IDs from existing Orphanet diseases
            $this->seenOrphanetIds = Disease::where('type', Disease::TYPE_ORPHANET)
                ->where('status', Disease::STATUS_ACTIVE)
                ->pluck('curie')
                ->toArray();

            return false;
        }

        // Download and cache the file
        $data = $this->downloadAndCacheFile($url, $cacheFilename);

        if ($data === null) {
            $data = $this->getCachedFile($cacheFilename);
            if ($data === null) {
                $this->error('......FAILED to retrieve data from Orphanet');
                return false;
            }
        }

        // Parse XML
        $xml = simplexml_load_string($data);

        if ($xml === false) {
            $this->error('......FAILED to parse Orphanet XML');
            return false;
        }

        $this->info('...processing Orphanet diseases');

        $deprecatedCount = 0;

        foreach ($xml->DisorderList->Disorder as $node)
        {
            $orphanetId = (string)$node->OrphaCode;
            $curie = 'Orphanet:' . $orphanetId;
            $newName = (string)$node->Name;

            // Track as seen
            $this->seenOrphanetIds[] = $curie;

            // Check if this is an inactive/obsolete disease
            // DisorderFlag id="495" with Value="8192" indicates inactive
            $isDeprecated = false;
            if (isset($node->DisorderFlagList->DisorderFlag)) {
                foreach ($node->DisorderFlagList->DisorderFlag as $flag) {
                    $flagId = (string)$flag['id'];
                    $flagValue = (string)$flag->Value;

                    if ($flagId === '495' && $flagValue === '8192') {
                        $isDeprecated = true;
                        $deprecatedCount++;
                        break;
                    }
                }
            }

            // Determine mondo_id
            $mondoId = $this->determineMondoIdForOrphanet($curie);

            // Get existing disease
            $existingDisease = Disease::where('curie', $curie)->first();

            // Prepare data - preserve count columns
            $d = [
                'type' => Disease::TYPE_ORPHANET,
                'mondo_id' => $mondoId,
                'curie' => $curie,
                'synonyms' => $this->x_orphanet_synonyms_xml($node->SynonymList ?? null),
                'xrefs' => $this->x_orphanet_xrefs_xml($node->ExternalReferenceList ?? null),
            ];

            // Handle names based on deprecation status
            if ($isDeprecated) {
                // For deprecated diseases, always set deprecated_name to the exact incoming name (with OBSOLETE: prefix)
                $d['deprecated_name'] = $newName;

                // For new deprecated diseases, set initial name (remove OBSOLETE: prefix)
                if (!$existingDisease) {
                    $d['name'] = $this->x_orphanet_label($newName);
                }
                // Don't update name or description for existing deprecated diseases (preserve historical data)
            } else {
                // For active diseases, update name and description normally (remove OBSOLETE: prefix if present)
                $d['name'] = $this->x_orphanet_label($newName);
                // Get description from SummaryInformationList
                $description = '';
                if (isset($node->SummaryInformationList->SummaryInformation)) {
                    foreach ($node->SummaryInformationList->SummaryInformation as $sumInfo) {
                        if (isset($sumInfo->TextSectionList->TextSection)) {
                            foreach ($sumInfo->TextSectionList->TextSection as $section) {
                                if (isset($section->Contents)) {
                                    $description = (string)$section->Contents;
                                    break 2;
                                }
                            }
                        }
                    }
                }
                $d['description'] = $description ?: null;
            }

            $d['status'] = ($isDeprecated ? Disease::STATUS_DEPRECATED : Disease::STATUS_ACTIVE);

            $disease = Disease::updateOrCreate(['curie' => $curie], $d);
        }

        // Update cached headers after successful processing
        $this->updateCachedHeaders($fileIdentifier, $url);

        $this->info('...Orphanet update complete (' . count($this->seenOrphanetIds) . ' diseases processed)');
        if ($deprecatedCount > 0) {
            $this->info('...found ' . $deprecatedCount . ' deprecated/inactive Orphanet diseases (DisorderFlag 495:8192)');
        }

        return true;
    }


    /**
     * Determine the MONDO ID for an Orphanet disease
     */
    protected function determineMondoIdForOrphanet($orphanetCurie)
    {
        // Priority 1: exact_match
        if (isset($this->mondoExactMatchOrphanet[$orphanetCurie])) {
            $mondoCurie = $this->mondoExactMatchOrphanet[$orphanetCurie];
            $mondoDisease = Disease::where('curie', $mondoCurie)->first();
            return $mondoDisease ? $mondoDisease->id : null;
        }

        // Priority 2: First xref
        if (isset($this->mondoXrefOrphanet[$orphanetCurie]) && count($this->mondoXrefOrphanet[$orphanetCurie]) > 0) {
            $mondoCurie = $this->mondoXrefOrphanet[$orphanetCurie][0];
            $mondoDisease = Disease::where('curie', $mondoCurie)->first();
            return $mondoDisease ? $mondoDisease->id : null;
        }

        return null;
    }


    /**
     * Step 3: Assign mondo_id to OMIM diseases using Orphanet equivalence
     *
     * If an OMIM disease doesn't have a mondo_id but is referenced by an Orphanet
     * disease that does have a mondo_id, use the Orphanet disease's mondo_id.
     */
    protected function assignMondoIdViaOrphanet()
    {
        $this->info('...assigning mondo_id to OMIM diseases via Orphanet equivalence');

        // Find all OMIM diseases without mondo_id
        $omimWithoutMondo = Disease::whereIn('type', [
                Disease::TYPE_OMIM,
                Disease::TYPE_OMIM_PLUS,
                Disease::TYPE_OMIM_NUMBER,
                Disease::TYPE_OMIM_CARET,
                Disease::TYPE_OMIM_PERCENT
            ])
            ->whereNull('mondo_id')
            ->get();

        $assignedCount = 0;

        foreach ($omimWithoutMondo as $omimDisease) {
            // Extract OMIM ID from curie (e.g., "OMIM:615221" -> "615221")
            $omimId = str_replace('OMIM:', '', $omimDisease->curie);

            // Find Orphanet diseases that reference this OMIM ID in their xrefs
            $orphanetDiseases = Disease::where('type', Disease::TYPE_ORPHANET)
                ->whereNotNull('mondo_id')
                ->whereRaw("JSON_EXTRACT(xrefs, '$.omim_id') = ?", [$omimId])
                ->get();

            if ($orphanetDiseases->count() > 0) {
                // Use the first Orphanet disease's mondo_id
                $orphanetDisease = $orphanetDiseases->first();
                $omimDisease->update(['mondo_id' => $orphanetDisease->mondo_id]);
                $assignedCount++;

                $this->info("......assigned mondo_id to {$omimDisease->curie} via Orphanet:{$orphanetDisease->curie}");
            }
        }

        $this->info("...assigned {$assignedCount} mondo_id values via Orphanet equivalence");
    }


    /**
     * Reconcile diseases that weren't seen in this update
     * Treat them as deprecated/removed
     */
    protected function reconcileUnseenDiseases()
    {
        $this->info('...reconciling unseen diseases');

        // Find MONDO diseases not seen
        $unseenMondo = Disease::where('type', Disease::TYPE_MONDO)
            ->whereNotIn('curie', $this->seenMondoIds)
            ->get();

        foreach ($unseenMondo as $disease) {
            $this->markAsRemovedOrDeprecated($disease);
        }

        // Find OMIM diseases not seen
        $unseenOmim = Disease::whereIn('type', [
                Disease::TYPE_OMIM,
                Disease::TYPE_OMIM_PLUS,
                Disease::TYPE_OMIM_NUMBER,
                Disease::TYPE_OMIM_CARET,
                Disease::TYPE_OMIM_PERCENT
            ])
            ->whereNotIn('curie', $this->seenOmimIds)
            ->get();

        foreach ($unseenOmim as $disease) {
            $this->markAsRemovedOrDeprecated($disease);
        }

        // Find Orphanet diseases not seen
        $unseenOrphanet = Disease::where('type', Disease::TYPE_ORPHANET)
            ->whereNotIn('curie', $this->seenOrphanetIds)
            ->get();

        foreach ($unseenOrphanet as $disease) {
            $this->markAsRemovedOrDeprecated($disease);
        }

        $totalUnseen = $unseenMondo->count() + $unseenOmim->count() + $unseenOrphanet->count();
        $this->info("...reconciliation complete ({$totalUnseen} diseases marked as removed/deprecated)");
    }


    /**
     * Mark a disease as removed/deprecated
     * Preserve mondo_id, set deprecated_name with REMOVED- prefix
     */
    protected function markAsRemovedOrDeprecated(Disease $disease)
    {
        // Check for submission references
        $hasReferences = Submission::where('disease_id', $disease->id)
            ->orWhere('disease_original_id', $disease->id)
            ->exists();

        // Only update if status is currently ACTIVE
        if ($disease->status !== Disease::STATUS_ACTIVE) {
            return;
        }

        // Preserve current name, set deprecated_name with REMOVED- prefix
        $updates = [
            'status' => Disease::STATUS_DEPRECATED,
            'deprecated_name' => 'REMOVED- ' . $disease->name,
            // DON'T update mondo_id - preserve it
        ];

        $disease->update($updates);

        if ($hasReferences) {
            $this->warn("......DEPRECATED (has refs): {$disease->curie}");
        } else {
            $this->info("......DEPRECATED (no refs): {$disease->curie}");
        }
    }


    /**
     * Transform MONDO synonyms
     */
    protected function x_mondo_synonym($synonyms)
    {
        $cleansed = [];

        foreach ($synonyms as $synonym)
            if ($synonym->pred = "hasExactSynonym")
                $cleansed[] = $synonym->val;

        return $cleansed;
    }


    /**
     * Remove obsolete prefix from MONDO label
     */
    protected function x_mondo_label($label)
    {
        if ($label === null)
            return "";

        if (strpos($label, 'obsolete ') === 0)
            return substr($label, 9);

        return $label;
    }


    /**
     * Remove OBSOLETE prefix from Orphanet label
     */
    protected function x_orphanet_label($label)
    {
        if ($label === null)
            return "";

        // Remove "OBSOLETE: " prefix (case insensitive)
        if (stripos($label, 'OBSOLETE: ') === 0)
            return substr($label, 10);

        return $label;
    }


    /**
     * Parse MONDO xrefs
     */
    protected function x_mondo_xrefs($meta)
    {
        $cleansed = [
            'omim_id' => [], 'omim_label' => null,
            'orpha_id' =>  null, 'orpha_label' => null, 'ogms' => null,
            'do_id' => null, 'medgen_id' => null, 'mesh' => null,
            'gard_id' => null, 'umls_id' => null, 'ncit' => null
        ];

        if ($meta === null)
            return $cleansed;

        // Get OMIM from basicPropertyValues (could be exact_match or other)
        foreach (($meta->basicPropertyValues ?? []) as $property)
            if (($n = strpos($property->val, '/omim.org/entry/')) > 0)
                $cleansed['omim_id'][] = substr($property->val, $n + 16);

        // Get the rest from xrefs
        foreach (($meta->xrefs ?? []) as $property)
        {
            $val = explode(':', $property->val);

            switch ($val[0])
            {
                case 'DOID':
                    $cleansed['do_id'] = $val[1];
                    break;
                case 'OMIM':
                    $cleansed['omim_id'][] = $val[1];
                    break;
                case 'Orphanet':
                    $cleansed['orpha_id'] = $val[1];
                    break;
                case 'GARD':
                    $cleansed['gard_id'] = $val[1];
                    break;
                case 'UMLS':
                    $cleansed['umls_id'] = $val[1];
                    break;
                case 'MESH':
                    $cleansed['mesh'] = $val[1];
                    break;
                case 'NCIT':
                    $cleansed['ncit'] = $val[1];
                    break;
                case 'OGMS':
                    $cleansed['ogms'] = $val[1];
                    break;
            }
        }

        $cleansed['omim_id'] = array_values(array_unique($cleansed['omim_id']));

        return $cleansed;
    }


    /**
     * Transform Orphanet synonyms from XML
     */
    protected function x_orphanet_synonyms_xml($synonymList)
    {
        $cleansed = [];

        if ($synonymList === null || !isset($synonymList->Synonym))
            return $cleansed;

        foreach ($synonymList->Synonym as $synonym) {
            $cleansed[] = (string)$synonym;
        }

        return $cleansed;
    }


    /**
     * Parse Orphanet xrefs from XML
     */
    protected function x_orphanet_xrefs_xml($externalRefList)
    {
        $cleansed = [];

        if ($externalRefList === null || !isset($externalRefList->ExternalReference))
            return $cleansed;

        foreach ($externalRefList->ExternalReference as $external)
        {
            $source = (string)$external->Source;
            $reference = (string)$external->Reference;

            switch ($source)
            {
                case 'OMIM':
                    $cleansed['omim_id'] = $reference;
                    break;
                case 'UMLS':
                    $cleansed['umls_id'] = $reference;
                    break;
            }
        }

        return $cleansed;
    }
}
