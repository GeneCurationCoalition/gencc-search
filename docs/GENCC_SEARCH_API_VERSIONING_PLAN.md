# GenCC Search API - Versioning Support Plan

> **Status**: gencc-sub implementation COMPLETE (January 9, 2026)
>
> The gencc-sub payload format changes documented here are complete. The gencc-search implementation is **IN PROGRESS**.

This document outlines the required changes for the gencc-search PublishController API to support the new submission versioning model from gencc-sub.

## Current Implementation Status

### What's Already Implemented (Partial)

The gencc-search application has partial versioning support using a different column naming scheme:

| Plan Specifies | Current Implementation | Status |
|----------------|------------------------|--------|
| `is_live` | `is_current` | ❌ Needs rename |
| `is_most_recent` | Not implemented | ❌ Needs addition |
| `released_at` | Not implemented | ❌ Needs addition |
| `version_number` | ✅ Implemented | ✅ Complete |
| `unpublished_at` | ✅ Implemented | ⚠️ Keep for compatibility |

### What Needs to Change

1. **Database Schema**: Add `is_live`, `is_most_recent`, `released_at` columns; rename/replace `is_current`
2. **API Payload**: Switch from `action` field to `is_removed` boolean; use `release_date` instead of `publish_date`
3. **Unpublish Logic**: Create new version record instead of updating existing record
4. **Model Scopes**: Update all queries to use `is_live` instead of `is_current`
5. **Relationships**: Update all model relationships to filter by `is_live`
6. **Views/Exports**: Update all display logic for new column names

## Overview

The gencc-sub application now uses a versioning model where:
- Each submission (SGC ID) can have multiple version records
- `is_live` indicates if a version is publicly accessible
- `is_most_recent` indicates if this is the latest version of the submission
- Each version has a `version_number` (1, 2, 3...)
- Display IDs follow the format `SGC-100001.2` (sid + version_number)

## Key Concepts

### Version States

| State | `is_live` | `is_most_recent` | Description |
|-------|-----------|------------------|-------------|
| Live Current | true | true | Current publicly visible version |
| Archived | false | false | Historical version, not publicly visible |
| Unpublished | true | true | SGC ID has been unpublished (hidden state) |

### Status Values

gencc-sub uses string-based status constants:
- `draft_new` - New submission in draft
- `submitted_new` - New submission submitted for publishing
- `published` - Successfully published
- `draft_republish` - Updated submission in draft
- `submitted_republish` - Updated submission submitted for publishing
- `draft_unpublish` - Submission marked for unpublishing in draft
- `submitted_unpublish` - Submission submitted for unpublishing
- `unpublished` - Successfully unpublished

### Timestamp Fields

- `submitted_at` - When submission was submitted for processing
- `released_at` - When submission version became the "live" authoritative state (for both publish and unpublish operations)

**Note**: `unpublished_at` is deprecated. We now use `released_at` for both publish and unpublish operations. The submission's status (`published` vs `unpublished`) indicates what the release means. This simplifies the data model and makes `released_at` the single source of truth for "when did this version become current."

## API Payload Structure

> **Note**: See [RELEASE_PROCESS_REFACTORING_PLAN.md](RELEASE_PROCESS_REFACTORING_PLAN.md) for the updated release process design.

gencc-sub sends the following payload to gencc-search:

```json
{
    "date": "<precise timestamp when request is created/sent>",
    "release_date": "<static release snapshot timestamp>",
    "token": "...",
    "job": "J-100001",
    "data": {
        "type": "Submission",
        "submission_id": "SGC-100001",
        "version_number": 2,
        "is_removed": false,
        "submission_label": "BRCA1-breast cancer",
        "local_key": "clingen-12345",
        "submitted": "2024-01-14T08:00:00Z",
        "status": "submitted_republish",
        "gene": { "id": "HGNC:1100", "symbol": "BRCA1" },
        "disease": { "id": "MONDO:0007254", "name": "breast cancer" },
        "original_disease": { "id": "...", "name": "..." },  // Only if disease changed
        "moi": { "id": "HP:0000006", "name": "Autosomal dominant" },
        "workflow": { ... },  // TODO: Investigate if needed
        "report": { "display_date": "2024/01/15", "ext_url": "..." },
        "classification": { "id": "GENCC:100001", "name": "Definitive" },
        "mechanism": { "id": "...", "name": "...", "comments": "..." },
        "criteria": { "name": "...", "url": "..." },
        "evidence": [{ "pmid": "12345" }, { "pmid": "67890" }],
        "notes": { "display": "...", "private": "..." },
        "version": {
            "display": "2.0",
            "internal": "v2",
            "reasons": ["classification_change", "disease_update"],
            "description": "Updated classification based on new evidence"
        },
        "submitter": { "id": "GENCC:000001", "name": "ClinGen" },
        "contributors": { "primary": { "id": "...", "name": "..." } },
        "additional_information": [...]
    }
}
```

### Payload Field Changes (from previous version)

| Removed | Replaced By | Rationale |
|---------|-------------|-----------|
| `action` | `is_removed` (in data) | Boolean flag is cleaner than string action type |
| `publish_date` | `release_date` | Unified timestamp for entire release batch |
| `unpublished_at` | `release_date` | No longer needed; `released_at` + status is sufficient |
| `is_most_recent` | (removed) | Always true for submitted items |

**Note**: The `status` field is still sent in the payload data for reference/audit purposes. While gencc_search can derive the final state from `is_removed`, the original gencc-sub status (`submitted_new`, `submitted_republish`, `submitted_unpublish`) provides useful context.

### Key Fields

- **`date`**: Precise timestamp when this specific request is created and sent
- **`release_date`**: Static snapshot timestamp captured at start of release process. All submissions in the same release share this timestamp.
- **`is_removed`**: Boolean indicating unpublish intent. When `true`, gencc_search should archive all versions and create an unpublished record.

## gencc-search Requirements

### 1. Data Model Changes

gencc-search needs to store submission versions:

```sql
-- Submissions table modifications
ALTER TABLE submissions ADD COLUMN version_number INT DEFAULT 1;
ALTER TABLE submissions ADD COLUMN is_live BOOLEAN DEFAULT FALSE;
ALTER TABLE submissions ADD COLUMN is_most_recent BOOLEAN DEFAULT FALSE;
ALTER TABLE submissions ADD COLUMN released_at TIMESTAMP NULL;
-- NOTE: unpublished_at is NOT needed; released_at + status is sufficient

-- Index for efficient version lookups
CREATE INDEX idx_submissions_sid_version ON submissions(sid, version_number);
CREATE INDEX idx_submissions_is_live ON submissions(is_live);
```

### 2. API Endpoint Behavior

The API endpoint behavior is determined by the `is_removed` flag in the payload data.

#### When `is_removed = false` (Publish/Republish)

**For New Submissions (version_number = 1):**

1. Create new submission record with:
   - `version_number = 1`
   - `is_live = true`
   - `is_most_recent = true`
   - `released_at = release_date` (from payload header)

**For Republish (version_number > 1):**

1. Mark ALL existing versions of this SGC ID as:
   - `is_live = false`
   - `is_most_recent = false`
2. Create new submission record with:
   - `version_number` = provided value
   - `is_live = true`
   - `is_most_recent = true`
   - `released_at = release_date` (from payload header)

#### When `is_removed = true` (Unpublish)

1. Mark ALL existing versions of this SGC ID as:
   - `is_live = false`
   - `is_most_recent = false`
2. Create new "unpublished" version record with:
   - `version_number` = provided value
   - `is_live = true` (the hidden state IS the current state)
   - `is_most_recent = true`
   - `released_at = release_date` (from payload header)
   - Derive status as 'unpublished' internally

### 3. Public API/Query Behavior

#### Default Queries
- Only return submissions where `is_live = true` AND `status != 'unpublished'`
- This returns the current publicly visible version of each SGC ID

#### Version-Specific Queries
Support URL patterns for historical version access:
- `/submission/SGC-100001` → Returns current live version
- `/submission/SGC-100001.1` → Returns version 1 specifically
- `/submission/SGC-100001.2` → Returns version 2 specifically

Historical versions (where `is_live = false`) should:
- Be accessible via version-specific URLs
- Show clear indication that this is an archived version
- Link to the current version if one exists

#### Unpublished SGC IDs
- Do NOT return in default search results
- Do NOT return at `/submission/SGC-100001` (return 404 or "not found")
- Historical versions SHOULD still be accessible via `/submission/SGC-100001.1`

### 4. Validation (Simplified)

Since gencc-sub performs rigorous validation, gencc-search needs only basic checks:

**Required Validations:**
- `submission_id` format is valid (`SGC-XXXXXX`)
- `version_number` is a positive integer
- `is_removed` is a boolean
- Required fields are present (gene.id, disease.id, classification.id)

**Optional Validations (can trust gencc-sub):**
- Gene exists in local database (lookup only, no reject)
- Disease exists in local database (lookup only, no reject)
- Classification is valid GENCC ID

**Error Responses:**
Return detailed error messages that gencc-sub can parse:
```json
{
    "status_code": 400,
    "error": "validation_error",
    "message": "Disease not found: MONDO:9999999",
    "field": "disease_id"
}
```

### 5. Version History Tracking

Store complete version history for audit:

```json
// Store in submission record or separate history table
{
    "version_history": [
        {
            "version_number": 1,
            "action": "publish",
            "timestamp": "2023-06-15T10:00:00Z",
            "job": "J-100001"
        },
        {
            "version_number": 2,
            "action": "publish",
            "timestamp": "2024-01-15T10:30:00Z",
            "job": "J-100050",
            "reasons": ["classification_change"]
        }
    ]
}
```

### 6. Display ID Resolution

Support looking up submissions by display_id format:

```
SGC-100001    → Latest live version
SGC-100001.1  → Version 1 specifically
SGC-100001.2  → Version 2 specifically
```

Parse display_id to extract:
- `sid`: `SGC-100001`
- `version_number`: `2` (or null for latest)

## Implementation Checklist

### Database

- [x] Add `version_number` column (default 1) - **DONE** (migration `2025_12_15_011840`)
- [ ] Add `is_live` column (default false) - **PENDING** (currently using `is_current`)
- [ ] Add `is_most_recent` column (default false) - **PENDING**
- [ ] Add `released_at` column (nullable timestamp) - **PENDING**
- [x] ~~Add `unpublished_at` column~~ - **DONE** (keeping for display of removal date)
- [x] Create indexes for efficient version queries - **DONE** (migration `2026_01_05_135225`)

### PublishController

- [x] Handle `version_number` from payload - **DONE** (auto-increments)
- [ ] Handle `is_removed` from payload - **PENDING** (currently uses `action` field)
- [ ] Handle `release_date` from payload - **PENDING** (currently uses `publish_date`)
- [x] Implement publish action (new + republish logic) - **DONE**
- [ ] Implement unpublish action (create new version) - **PENDING** (currently updates existing)
- [x] Update existing records when new version published - **DONE**
- [x] Return appropriate error responses with field information - **DONE**

### Public API

- [x] Modify default queries to filter by `is_current = true` - **DONE** (needs rename to `is_live`)
- [x] Exclude unpublished submissions from default results - **DONE**
- [x] Add version-specific URL routing (`/submission/SGC-100001.2`) - **DONE**
- [x] Add display_id parsing utility (`scopeByDisplayId`) - **DONE**
- [x] Show "archived" indicator for non-current versions - **DONE**

### Search/Export

- [x] Update search to only include current submissions - **DONE** (needs rename to `is_live`)
- [x] Update exports to only include current submissions - **DONE** (needs rename to `is_live`)
- [ ] Optional: Add filter for historical versions - **NOT STARTED**

### Views/Display

- [x] Previous version banner (yellow) - **DONE**
- [x] Unpublished submission banner (red) - **DONE**
- [x] Hide details for unpublished submissions - **DONE**
- [ ] Update logic to use `is_live` and `is_most_recent` - **PENDING**

## Migration Strategy

For existing data:
1. Set `version_number = 1` for all existing records
2. Set `is_live = true` for all published records
3. Set `is_most_recent = true` for all records (assuming single versions)
4. Set `released_at` from existing publish timestamp if available

## Notes

- **No origin tracking needed**: gencc-sub no longer sends origin_snapshot or original_submission_data for reverting. Each version is a complete, independent record.
- **Trust gencc-sub validation**: The submitting application has already validated genes, diseases, classifications, etc. gencc-search can do lookups but should not reject valid submissions from gencc-sub.
- **Version numbers are sequential**: Version 1, 2, 3... No gaps expected.
- **Unpublished is a state, not a deletion**: When a submission is unpublished, it creates a new version record with unpublished status. Previous versions remain accessible by version number.

---

## Real-World API Payload Examples

The following examples show the actual JSON payloads that gencc-sub would send to gencc-search for three different scenarios using real submission data.

> **Note**: These examples use the new payload format with `release_date` and `is_removed` instead of `action` and `publish_date`.

### Example 1: New Submission (SGC-174953.1)

**Scenario**: A brand new submission being published for the first time.

- **gencc-sub Status**: `submitted_new`
- **is_removed**: `false`
- **Version**: 1

```json
{
    "date": "2026-01-09T16:00:01.234+00:00",
    "release_date": "2026-01-09T16:00:00.000+00:00",
    "token": "[API_TOKEN]",
    "job": "J-100022",
    "data": {
        "type": "Submission",
        "submission_id": "SGC-174953",
        "version_number": 1,
        "is_removed": false,
        "submission_label": "5fb5d9ff-c73f-4362-8e95-fde7f7500595",
        "local_key": "5fb5d9ff-c73f-4362-8e95-fde7f7500595",
        "submitted": "2026-01-09T15:49:45+00:00",
        "status": "submitted_new",
        "gene": {
            "id": "HGNC:9065",
            "symbol": "PLCG1"
        },
        "disease": {
            "id": "MONDO:0957790",
            "name": "immune dysregulation, autoimmunity, and autoinflammation"
        },
        "moi": {
            "id": "HP:0000006",
            "name": "Autosomal dominant"
        },
        "workflow": {},
        "report": {
            "display_date": "2025-11-18T00:00:00.000000Z",
            "ext_url": "https://search.clinicalgenome.org/kb/gene-validity/CGGV:assertion_5fb5d9ff-c73f-4362-8e95-fde7f7500595-2025-11-18T20:00:00.000Z"
        },
        "classification": {
            "id": "GENCC:100003",
            "name": "Moderate"
        },
        "mechanism": {
            "id": null,
            "name": null,
            "comments": null
        },
        "criteria": {
            "name": "",
            "url": "https://clinicalgenome.org/docs/gene-disease-validity-standard-operating-procedures-version-11/"
        },
        "evidence": [
            { "pmid": "1281218" },
            { "pmid": "15372077" },
            { "pmid": "15845450" },
            { "pmid": "20123962" },
            { "pmid": "22837484" },
            { "pmid": "37422272" },
            { "pmid": "40796680" },
            { "pmid": "40862571" }
        ],
        "notes": {
            "display": "A monogenic association between PLCG1 and human disease was first reported in 2023...",
            "private": "File changed_submissions.xlsx Row 3366 "
        },
        "version": {
            "display": "1.0",
            "internal": "1.0.0",
            "reasons": ["NEW_CURATION"],
            "description": ""
        },
        "submitter": {
            "id": "GENCC:000102",
            "name": "ClinGen"
        },
        "contributors": {
            "primary": {
                "id": "",
                "name": ""
            }
        },
        "additional_information": [{ "key": "values" }]
    }
}
```

**Expected gencc-search behavior**:

1. Create new submission record for SGC-174953
2. Set `version_number = 1`, `is_live = true`, `is_most_recent = true`
3. Set `released_at = release_date` from payload header
4. Return success response

---

### Example 2: Republish Submission (SGC-106683.2)

**Scenario**: An existing published submission being updated with new data (version 2).

- **gencc-sub Status**: `submitted_republish`
- **is_removed**: `false`
- **Version**: 2

```json
{
    "date": "2026-01-09T16:00:02.567+00:00",
    "release_date": "2026-01-09T16:00:00.000+00:00",
    "token": "[API_TOKEN]",
    "job": "J-100022",
    "data": {
        "type": "Submission",
        "submission_id": "SGC-106683",
        "version_number": 2,
        "is_removed": false,
        "submission_label": "d4c35c20-6e59-46cf-9347-852b80af8dcc",
        "local_key": "d4c35c20-6e59-46cf-9347-852b80af8dcc",
        "submitted": "2026-01-09T15:49:44+00:00",
        "status": "submitted_republish",
        "gene": {
            "id": "HGNC:10586",
            "symbol": "SCN1B"
        },
        "disease": {
            "id": "MONDO:0100062",
            "name": "genetic developmental and epileptic encephalopathy"
        },
        "original_disease": {
            "id": "MONDO:0100062",
            "name": "genetic developmental and epileptic encephalopathy"
        },
        "moi": {
            "id": "HP:0000007",
            "name": "Autosomal recessive"
        },
        "workflow": {},
        "report": {
            "display_date": "2022-01-04T00:00:00.000000Z",
            "ext_url": "https://search.clinicalgenome.org/kb/gene-validity/CGGV:assertion_d4c35c20-6e59-46cf-9347-852b80af8dcc-2022-01-04T21:10:51.949Z"
        },
        "classification": {
            "id": "GENCC:100001",
            "name": "Definitive"
        },
        "mechanism": {
            "id": null,
            "name": null,
            "comments": null
        },
        "criteria": {
            "name": "",
            "url": "https://clinicalgenome.org/docs/summary-of-updates-to-the-clingen-gene-clinical-validity-curation-sop-version-8/"
        },
        "evidence": [
            { "pmid": "15102918" },
            { "pmid": "19710327" },
            { "pmid": "23148524" },
            { "pmid": "28218389" },
            { "pmid": "31709768" },
            { "pmid": "33901312" }
        ],
        "notes": {
            "display": "SCN1B was first reported in relation to autosomal recessive Developmental and Epileptic Encephalopathy in 2009...",
            "private": "File changed_submissions.xlsx Row 3352 "
        },
        "version": {
            "display": "1.0",
            "internal": "1.0.0",
            "reasons": ["NEW_CURATION"],
            "description": ""
        },
        "submitter": {
            "id": "GENCC:000102",
            "name": "ClinGen"
        },
        "contributors": {
            "primary": {
                "id": "",
                "name": ""
            }
        },
        "additional_information": [{ "key": "values" }]
    }
}
```

**Expected gencc-search behavior**:

1. Find existing SGC-106683 records
2. Mark ALL existing versions as `is_live = false`, `is_most_recent = false` (archives version 1)
3. Create new submission record for version 2
4. Set `version_number = 2`, `is_live = true`, `is_most_recent = true`
5. Set `released_at = release_date` from payload header
6. Return success response

**Note**: The `original_disease` field is included when the disease may have changed from the previously published version. gencc-search can use this for display purposes (e.g., "Disease changed from X to Y").

---

### Example 3: Unpublish Submission (SGC-107435.2)

**Scenario**: A published submission being unpublished (removed from public view).

- **gencc-sub Status**: `submitted_unpublish`
- **is_removed**: `true`
- **Version**: 2

```json
{
    "date": "2026-01-09T16:00:03.890+00:00",
    "release_date": "2026-01-09T16:00:00.000+00:00",
    "token": "[API_TOKEN]",
    "job": "J-100022",
    "data": {
        "type": "Submission",
        "submission_id": "SGC-107435",
        "version_number": 2,
        "is_removed": true,
        "submission_label": "GENCC_000102-HGNC_29622-MONDO_0957935-HP_0000007-GENCC_100004",
        "local_key": "427c818a-1705-4013-a5ba-d977540535d5",
        "submitted": "2026-01-09T18:09:23+00:00",
        "status": "submitted_unpublish",
        "gene": {
            "id": "HGNC:29622",
            "symbol": "MCAT"
        },
        "disease": {
            "id": "MONDO:0957935",
            "name": "optic atrophy 15"
        },
        "moi": {
            "id": "HP:0000007",
            "name": "Autosomal recessive"
        },
        "workflow": {
            "publish_date": "2025-12-05T00:00:00.000000Z",
            "evaluation_date": "2024-05-16T00:00:00.000000Z"
        },
        "report": {
            "display_date": "2024-05-16 00:00:00",
            "ext_url": "https://search.clinicalgenome.org/kb/gene-validity/CGGV:assertion_427c818a-1705-4013-a5ba-d977540535d5-2024-05-16T16:00:00.000Z"
        },
        "classification": {
            "id": "GENCC:100004",
            "name": "Limited"
        },
        "mechanism": {
            "id": null,
            "name": null,
            "comments": null
        },
        "criteria": {
            "name": "",
            "url": "https://clinicalgenome.org/docs/gene-disease-validity-standard-operating-procedures-version-10/"
        },
        "evidence": [
            { "pmid": "31915829" },
            { "pmid": "33918393" }
        ],
        "notes": {
            "display": "MCAT was first reported in relation to autosomal recessive optic atrophy 15...",
            "private": ""
        },
        "version": {
            "display": "1.0",
            "internal": "1.0.0.0",
            "reasons": ["LEGACY_IMPORT"],
            "description": "Imported from gencc-search"
        },
        "submitter": {
            "id": "GENCC:000102",
            "name": "ClinGen"
        },
        "contributors": {
            "primary": {
                "id": "",
                "name": ""
            }
        },
        "additional_information": {
            "submitter_curie": "GENCC:000102",
            "submitter_title": "ClinGen",
            "submitted_as_submission_id": "427c818a-1705-4013-a5ba-d977540535d5"
        }
    }
}
```

**Expected gencc-search behavior**:

1. Find existing SGC-107435 records
2. Mark ALL existing versions as `is_live = false`, `is_most_recent = false` (archives version 1)
3. Create new "unpublished" version record
4. Set `version_number = 2`, `is_live = true`, `is_most_recent = true`
5. Set `released_at = release_date` from payload header
6. Derive status as 'unpublished' internally (based on `is_removed = true`)
7. Return success response

**Critical behavior for unpublished submissions**:

- The SGC ID should NO LONGER appear in default search results
- Direct URL `/submission/SGC-107435` should return 404 or "submission not found"
- Historical version `/submission/SGC-107435.1` SHOULD still be accessible (marked as archived)
- The unpublished version record (`SGC-107435.2`) represents the current state but is hidden from public view

---

## Summary of Release Processing

| gencc-sub Status | `is_removed` | Version | gencc-search Behavior |
|------------------|--------------|---------|----------------------|
| `submitted_new` | `false` | 1 | Create new, set live |
| `submitted_republish` | `false` | 2+ | Archive old, create new live |
| `submitted_unpublish` | `true` | 2+ | Archive old, create hidden record |
