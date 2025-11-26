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
   - Clickable link to appropriate database (MONDO → Monarch Initiative)

2. **Secondary Display (Conditionally Shown):**
   - **Only displays** when `$submission->disease->id != $submission->disease_original->id`
   - Labeled as **"Submitted as:"**
   - Disease title: `$submission->disease_original->title`
   - Disease CURIE: `$submission->disease_original->curie`
   - Clickable link to appropriate database (OMIM → omim.org, Orphanet → orpha.net)

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

```
Metabolic Disorder
MONDO:0005066 [link to Monarch Initiative]

Submitted as: OMIM:615123 [link to OMIM]
```

Note: The original OMIM name "Metabolic Disease" is shown via `$submission->disease_original->title`, which comes from the Disease record with the OMIM CURIE.

## Other Fields: No Dual Display

### Summary Table

| Field Category | Foreign Key (Normalized) | String Fields (Raw Submitted) | Displayed Side-by-Side? |
|---------------|-------------------------|-------------------------------|------------------------|
| **Disease** | `disease_id`, `disease_original_id` | `submitted_as_disease_id`, `submitted_as_disease_name` | ✅ **YES** |
| **Gene** | `gene_id` | `submitted_as_hgnc_id`, `submitted_as_hgnc_symbol` | ❌ No |
| **Classification** | `classification_id` | `submitted_as_classification_id`, `submitted_as_classification_name` | ❌ No |
| **Mode of Inheritance** | `moi_id` | `submitted_as_moi_id`, `submitted_as_moi_name` | ❌ No |
| **Submitter** | `submitter_id` | `submitted_as_submitter_id`, `submitted_as_submitter_name` | ❌ No |

### Submitted-As String Fields

The submissions table stores `submitted_as_*` string values for all major fields:

- `submitted_as_hgnc_id`, `submitted_as_hgnc_symbol`
- `submitted_as_disease_id`, `submitted_as_disease_name`
- `submitted_as_moi_id`, `submitted_as_moi_name`
- `submitted_as_classification_id`, `submitted_as_classification_name`
- `submitted_as_submitter_id`, `submitted_as_submitter_name`

**Important:** These string fields are stored for audit/tracking purposes but are **NOT displayed** in the UI to show comparisons with normalized values.

### Purpose of String Fields

These fields are used for:
- Import processing and validation
- Audit trails and historical record keeping
- Data integrity checks
- Debugging submission processing issues

They are **not** rendered in the public or administrative views.

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

- **MONDO** → https://monarchinitiative.org/disease/
- **OMIM** → https://omim.org/entry/
- **Orphanet/Orpha** → https://www.orpha.net/consor/cgi-bin/OC_Exp.php

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
- `resources/views/partials/dashboard/manage-submission-row-common.blade.php` - Admin submission row display
- `resources/views/livewire/dashboard/submitter/submitter-submission-manage.blade.php` - Detailed submission management view

### Traits
- `app/Traits/DisplayTransform.php` - Contains `displayLinkToDisease()` and other display helper methods

### Migrations
- `database/migrations/2020_03_04_015442_create_submissions_table.php` - Initial submissions table
- `database/migrations/2020_12_04_011611_add_disease_original_to_submissions_table.php` - Added disease_original_id field

## Future Considerations

If other fields (gene, MOI, classification) require similar dual display behavior in the future:

1. Add corresponding `*_original_id` foreign key columns
2. Create Eloquent relationships for the original values
3. Update views to conditionally display both values when different
4. Follow the same pattern established for disease display
