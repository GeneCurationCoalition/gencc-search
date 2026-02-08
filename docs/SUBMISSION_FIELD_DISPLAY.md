# Submission Field Display Behavior

## Overview

This document explains how submission fields are displayed in the GenCC application, with particular focus on the handling of submitted values versus normalized/primary values.

## Disease Field: Special Dual Display Behavior

### Database Architecture

The `submissions` table has two disease-related foreign keys:

- **`disease_id`** - References the current/normalized disease (typically MONDO)
- **`disease_original_id`** - References the originally submitted disease (may be OMIM, Orphanet, or MONDO)

These are defined as Eloquent relationships in `app/Submission.php`:

```php
public function disease()
{
    return $this->belongsTo('App\Disease');
}

public function disease_original()
{
    return $this->belongsTo('App\Disease', 'disease_original_id');
}
```

### Display Logic

Disease information is displayed using this pattern:

1. **Primary Display (Always Shown):**
   - Disease title: `$submission->disease->title`
   - Disease CURIE: `$submission->disease->curie`
   - Clickable link to appropriate database (MONDO -> Monarch Initiative)

2. **Secondary Display (Conditionally Shown):**
   - **Only displays** when `$submission->disease->id != $submission->disease_original->id`
   - Labeled as **"Submitted as:"**
   - Disease title: `$submission->disease_original->title`
   - Disease CURIE: `$submission->disease_original->curie`
   - Clickable link to appropriate database (OMIM -> omim.org, Orphanet -> orpha.net)

### View Implementation

Example from `resources/views/partials/genes/submission-row-common.blade.php:18-26`:

```blade
<div class="flex-initial break-words">
  <div class="list-text-label"> {{ $item->disease->title}}</div>
  <div class="text-sm text-gray-600">{!! $item->displayLinkToDisease($item->disease->curie, $item->disease->curie) !!}</div>
  @if($item->disease->id != $item->disease_original->id)
    <div class="mt-1 text-sm text-gray-600 break-words"> Submitted as: {!! $item->displayLinkToDisease($item->disease_original->curie, $item->disease_original->curie) !!}</div>
  @endif
</div>
```

### Display Example

If a submitter publishes with `OMIM:615123 - "Metabolic Disease"` but the system normalizes it to `MONDO:0005066 - "Metabolic Disorder"`, the display would be:

```text
Metabolic Disorder
MONDO:0005066 [link to Monarch Initiative]

Submitted as: OMIM:615123 [link to OMIM]
```

Note: The original OMIM name "Metabolic Disease" is shown via `$submission->disease_original->title`, which comes from the Disease record with the OMIM CURIE.

## Other Fields: No Dual Display

### Summary Table

| Field Category | Foreign Key (Normalized) | Accessor (from original_submission_data) | Displayed Side-by-Side? |
| -------------- | ------------------------ | ----------------------------------------- | ----------------------- |
| **Disease** | `disease_id`, `original_disease_id` | `submitted_as_disease_id`, `submitted_as_disease_name` | YES |
| **Gene** | `gene_id` | `submitted_as_hgnc_id`, `submitted_as_hgnc_symbol` | No |
| **Classification** | `classification_id` | `submitted_as_classification_id`, `submitted_as_classification_name` | No |
| **Mode of Inheritance** | `inheritance_id` | `submitted_as_moi_id`, `submitted_as_moi_name` | No |
| **Submitter** | `submitter_id` | `submitted_as_submitter_id`, `submitted_as_submitter_name` | No |

### Submitted-As Accessors

The `submitted_as_*` values are **not separate database columns**. They are Eloquent accessors in `app/Submission.php` that extract values from the `original_submission_data` JSON column:

```php
// Example accessor from Submission.php
public function getSubmittedAsHgncIdAttribute()
{
    $data = $this->original_submission_data;
    return $data['gene']['id'] ?? null;
}
```

Available accessors:

- `submitted_as_hgnc_id` - from `original_submission_data.gene.id`
- `submitted_as_hgnc_symbol` - from `original_submission_data.gene.symbol`
- `submitted_as_disease_id` - from `original_submission_data.disease.id`
- `submitted_as_disease_name` - from `original_submission_data.disease.name`
- `submitted_as_moi_id` - from `original_submission_data.moi.id`
- `submitted_as_moi_name` - from `original_submission_data.moi.name`
- `submitted_as_classification_id` - from `original_submission_data.classification.id`
- `submitted_as_classification_name` - from `original_submission_data.classification.name`
- `submitted_as_submitter_id` - from `original_submission_data.additional_information.submitter_curie`
- `submitted_as_submitter_name` - from `original_submission_data.additional_information.submitter_title`
- `submitted_as_public_report_url` - from `original_submission_data.report.ext_url`
- `submitted_as_notes` - from `original_submission_data.notes.display`
- `submitted_as_pmids` - from `normalized_pmids` column (with formatting)
- `submitted_as_date` - from `report_date` column
- `submitted_run_date` - from `publish_date` column (date only)

### Purpose of original_submission_data

The `original_submission_data` JSON column stores:

- The complete original submission as received
- Preserves exact values before normalization
- Used for audit trails and historical record keeping
- Enables debugging submission processing issues

These values are **not** rendered in the public views but are accessible via the accessors for data exports and administrative purposes.

## The `displayLinkToDisease()` Method

This helper method (`app/Traits/DisplayTransform.php:65-86`) creates appropriate external links based on the disease ontology prefix:

```php
public function displayLinkToDisease($text, $href, $css = null, $target = "_blank", $options = null)
{
    $ontology = explode(":", $href);

    switch ($ontology[0]) {
        case "MONDO":
            $href = 'https://monarchinitiative.org/disease/' . $href;
            break;
        case "OMIM":
            $href = str_replace("OMIM:", "", $href);
            $href = 'https://omim.org/entry/'. $href;
            break;
        case "Orphanet":
            $href = str_replace("Orphanet:", "Orpha:", $href);
        case "Orpha:":
            $href = str_replace("Orpha:", "", $href);
            $href = 'https://www.orpha.net/consor/cgi-bin/OC_Exp.php?lng=EN&Expert='. $href;
            break;
    }

    return "<a class='{$css} text-gray-600' target='{$target}' href='{$href}'>{$text} <i class='fas fa-external-link-alt'></i></a>";
}
```

### Supported External Links

- **MONDO** -> <https://monarchinitiative.org/disease/>
- **OMIM** -> <https://omim.org/entry/>
- **Orphanet/Orpha** -> <https://www.orpha.net/consor/cgi-bin/OC_Exp.php>

## Why Disease is Unique

Disease is the only field with this dual display behavior for several important reasons:

1. **Complex Ontology Mapping**: Disease terminology involves mapping between multiple ontology systems (OMIM, Orphanet, MONDO), which is more complex than other fields.

2. **User Transparency**: Users need to see both the normalized MONDO term and what was originally submitted to understand any transformations.

3. **Data Quality**: Showing both versions helps users verify that the ontology mapping was correct.

4. **Historical Context**: The `disease_original_id` field was added in a separate migration on 2020-12-04, suggesting this was a deliberate enhancement specifically for disease handling after the initial table creation.

## Related Files

### Models

- `app/Submission.php` - Submission model with disease relationships
- `app/Disease.php` - Disease model

### Views

- `resources/views/partials/genes/submission-row-common.blade.php` - Public submission row display

### Traits

- `app/Traits/DisplayTransform.php` - Contains `displayLinkToDisease()` and other display helper methods

## Future Considerations

If other fields (gene, MOI, classification) require similar dual display behavior in the future:

1. Add corresponding `*_original_id` foreign key columns
2. Create Eloquent relationships for the original values
3. Update views to conditionally display both values when different
4. Follow the same pattern established for disease display
