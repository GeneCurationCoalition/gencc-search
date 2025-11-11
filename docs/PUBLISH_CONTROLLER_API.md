# PublishController API Documentation

## Overview

The [PublishController.php](../app/Http/Controllers/PublishController.php) is an API endpoint that receives requests from the GenCC submission portal (gencc-sub) to manage gene-disease submissions. This document provides a comprehensive guide to all available actions and how they work.

## Table of Contents

- [Authentication & Authorization](#authentication--authorization)
- [Available Actions](#available-actions)
  - [init](#1-init-action)
  - [publish](#2-publish-action)
  - [unpublish](#3-unpublish-action)
  - [sgc_id](#4-sgc_id-action)
  - [end](#5-end-action)
  - [Unimplemented Actions](#6-unimplemented-actions)
- [Error Status Codes](#error-status-codes)
- [Key Design Patterns](#key-design-patterns)

---

## Authentication & Authorization

Before any action is processed, the controller validates the following (in order):

### 1. JSON Parsing Fallback (lines 42-61)
Manually parses JSON request body if Laravel fails to auto-parse it. This handles various Content-Type header variations.

### 2. Posts Allowed Check (lines 64-73)
Checks if the `allow_posts` setting is set to "yes".

**Rejection Response:**
```json
{
  "success": "false",
  "status_code": 9002,
  "message": "Service not available"
}
```
**HTTP Status:** 501

### 3. Token Validation (lines 76-118)
Validates the request token against the stored token in settings.

**Rejection Response:**
```json
{
  "success": "false",
  "status_code": 9001,
  "message": "No auth"
}
```
**HTTP Status:** 501

**Logging:** Extensive error logging includes:
- Action, SGC ID, local_key
- Provided token (truncated for security)
- IP address, HTTP method, URL
- Content-Type, Content-Length
- Raw body preview (first 500 chars)

---

## Available Actions

### 1. `init` Action

**Purpose:** Test connection and verify authentication

**Lines:** 122-129

**What it does:**
- Simply validates the token (done in the authorization step)
- Responds with ready status

**Success Response:**
```json
{
  "success": "true",
  "status_code": 200,
  "action": "init",
  "message": "Ready for jobs"
}
```
**HTTP Status:** 200

---

### 2. `publish` Action

**Purpose:** Create or update a gene-disease submission

**Lines:** 130-156 (main), 236-410 (process_submission method)

**What it does:**

1. **Validates Required Entities** - Confirms existence of:
   - Gene (by CURIE)
   - Disease (by CURIE)
   - Disease Original (from original_data if provided, falls back to normalized disease)
   - Classification (by CURIE)
   - Mode of Inheritance/MOI (by CURIE)
   - Submitter (by CURIE)

2. **Extracts Evidence**
   - Collects PMIDs from evidence array
   - Stores as comma-separated list

3. **Handles Original vs Normalized Data**
   - Uses `original_data` for "submitted_as_*" fields when available
   - Falls back to normalized data if original_data not provided
   - This preserves what the submitter originally submitted before normalization

4. **Creates or Updates Submission**
   - Searches for existing submission by `uuid` (SGC-ID) regardless of status
   - **Updates** existing submission if found (including soft-deleted ones)
   - **Creates** new submission only if uuid doesn't exist
   - **Prevents duplicate records** when re-publishing previously unpublished submissions

5. **Associates Related Models**
   - Links to gene, disease, disease_original, submitter
   - Associates classification and inheritance (MOI)
   - Syncs disease equivalents with pivot data

6. **Sets Update Flag**
   - Sets `update_counts` setting to 1
   - Triggers count recalculation later

**Key Fields Stored:**

```php
[
    'uuid'                                   => $data->submission_id,  // SGC-ID
    'order'                                  => $classification->order,
    'submitted_run_date'                     => $publish_date,
    'submitted_as_hgnc_id'                   => $original->gene->id,
    'submitted_as_disease_id'                => $original->disease->id,
    'submitted_as_moi_id'                    => $original->moi->id,
    'submitted_as_submitter_id'              => $original->submitter->id,
    'submitted_as_submission_id'             => $data->local_key,
    'submitted_as_hgnc_symbol'               => $original->gene->symbol,
    'submitted_as_disease_name'              => $original->disease->name,
    'submitted_as_moi_name'                  => $original->moi->name,
    'submitted_as_submitter_name'            => $original->submitter->name,
    'submitted_as_classification_id'         => $original->classification->id,
    'submitted_as_classification_name'       => $original->classification->name,
    'submitted_as_date'                      => $data->report->display_date,
    'submitted_as_public_report_url'         => $data->report->ext_url,
    'submitted_as_notes'                     => $data->notes->display,
    'submitted_as_pmids'                     => implode(',', $evidences),
    'submitted_as_assertion_criteria_url'    => $data->criteria->url,
    'status'                                 => 1  // Active/published
]
```

**Success Response:**
```json
{
  "success": "true",
  "status_code": 200,
  "sid": "local_key_value",
  "message": "Submission accepted"
}
```
**HTTP Status:** 200

**Failure Response:**
```json
{
  "success": "false",
  "status_code": 9007,
  "sid": "local_key_value",
  "message": "Submission failed: <error details>"
}
```
**HTTP Status:** 501

**Error Cases:**
- Gene not found
- Disease not found
- Original disease not found (warning only, continues with normalized disease)
- Classification not found
- Inheritance (MOI) not found
- Submitter not found
- Exception during processing

---

### 3. `unpublish` Action

**Purpose:** Soft-delete a submission by marking it as unpublished

**Lines:** 157-183 (main), 419-446 (unpublish_submission method)

**What it does:**

1. **Validates Submitter** - Confirms submitter exists by CURIE

2. **Finds Submission (Two-Step Lookup):**
   - **First attempt:** Finds by `uuid` (SGC-ID) with `status=1`
   - **Fallback:** If not found, tries `submitted_as_submission_id` (local_key) with `status=1`

3. **Soft Delete:**
   - Updates submission's `status` field from `1` (active) to `0` (unpublished)
   - **Does NOT delete the record** - it remains in the database
   - Submission will be filtered out by scope queries (scopeCurie, scopeUuid)

4. **Sets Update Flag:**
   - Sets `update_counts` setting to 1
   - Triggers count recalculation later

**How Soft Delete Works:**
```
Active Submission (status=1) → Unpublished (status=0)

✓ Record remains in database
✓ Filtered out by scopeCurie() and scopeUuid()
✓ Won't appear in searches or exports
✓ Can be re-published by changing status back to 1
✓ Maintains audit trail and history
```

**Success Response:**
```json
{
  "success": "true",
  "status_code": 200,
  "sid": "local_key_value",
  "message": "Submission unpublished"
}
```
**HTTP Status:** 200

**Failure Response:**
```json
{
  "success": "false",
  "status_code": 9008,
  "sid": "local_key_value",
  "message": "Submission remove failed: <error details>"
}
```
**HTTP Status:** 501

**Error Cases:**
- Submitter not found
- Submission not found (by uuid or local_key)
- Database update failed

---

### 4. `sgc_id` Action

**Purpose:** Update the SGC-ID (uuid) of an existing submission

**Lines:** 184-203 (main), 454-468 (update_sgc_id method)

**Use Case:**
When submissions are initially created in the search system and then get an SGC-ID assigned in the submission portal, this action updates the search database with the new SGC-ID.

**What it does:**

1. **Finds Submission by Row ID**
   - Uses `search_row_id` (database primary key) for exact match
   - This is the most reliable identifier when uuid might not be set yet

2. **Updates UUID Field**
   - Sets `uuid` to the new SGC-ID from submission portal
   - This links the search record to the portal record

3. **Logs Update**
   - Records the local_key and new sgc_id for audit trail

**Success Response:**
```json
{
  "success": "true",
  "status_code": 200,
  "sid": "local_key_value",
  "message": "SGC ID updated"
}
```
**HTTP Status:** 200

**Failure Response:**
```json
{
  "success": "false",
  "status_code": 9009,
  "message": "SGC ID update failed: <error details>"
}
```
**HTTP Status:** 501

**Error Cases:**
- Submission not found by search_row_id
- Database save failed

---

### 5. `end` Action

**Purpose:** Signal that a batch synchronization session is complete

**Lines:** 205-215

**What it does:**
- Logs "Remote Session completed"
- Previously would trigger `gencc:update-counts` (now commented out)
- Count updates now happen automatically via the `update_counts` flag set during publish/unpublish

**Success Response:**
```json
{
  "success": "true",
  "status_code": 200,
  "message": "Session complete"
}
```
**HTTP Status:** 200

---

### 6. Unimplemented Actions

**Lines:** 216-220

The following action names are recognized but not implemented:
- `addsubmitter` - Would add a new submitter organization
- `delsubmitter` - Would remove a submitter organization
- `modsubmitter` - Would modify submitter information

**Response for all unimplemented actions:**
```json
{
  "success": "false",
  "status_code": 9011,
  "message": "Unknown command"
}
```
**HTTP Status:** 200

---

## Error Status Codes

| Code | HTTP Status | Meaning |
|------|-------------|---------|
| 200  | 200 | Success |
| 9001 | 501 | Authentication failed (invalid token) |
| 9002 | 501 | Service not available (posts disabled) |
| 9007 | 501 | Publish submission failed |
| 9008 | 501 | Unpublish submission failed |
| 9009 | 501 | SGC ID update failed |
| 9011 | 200 | Unknown command |

---

## Key Design Patterns

### 1. Dual Identifier System
Uses both identifiers for maximum flexibility:
- `uuid` - SGC-ID from submission portal (may not exist initially)
- `submitted_as_submission_id` - local_key from submitter's system (always exists)

### 2. Soft Deletes via Status Field
- `status = 1` - Active/published submission
- `status = 0` - Unpublished/inactive submission
- Records never physically deleted, maintaining audit trail

### 3. Original vs Normalized Data
Stores both versions:
- **Normalized fields** (gene_id, disease_id, etc.) - For querying and relationships
- **submitted_as_* fields** - Original values before normalization, for display and audit

### 4. Idempotent Updates
- Publishing the same submission twice **updates** rather than duplicates
- Lookup by uuid ensures only one active record per SGC-ID

### 5. Comprehensive Error Logging
Every failure path logs:
- SGC-ID and local_key for identification
- Full exception details with stack traces
- Request metadata (IP, headers, body preview)

### 6. Graceful Fallbacks
- Manual JSON parsing if auto-parse fails
- Original disease fallback to normalized disease
- Two-step lookup for unpublish (uuid then local_key)

---

## Workflow Example

### Typical Synchronization Flow

1. **Session Start**
   ```
   POST /publish
   { "action": "init", "token": "..." }
   → Response: Ready for jobs
   ```

2. **Publish Multiple Submissions**
   ```
   POST /publish
   { "action": "publish", "token": "...", "data": {...}, "original_data": {...} }
   → Response: Submission accepted (sid: local_key_1)

   POST /publish
   { "action": "publish", "token": "...", "data": {...}, "original_data": {...} }
   → Response: Submission accepted (sid: local_key_2)
   ```

3. **Update SGC-ID for New Submission**
   ```
   POST /publish
   { "action": "sgc_id", "token": "...", "data": { "search_row_id": 123, "submission_id": "SGC-UUID-456" } }
   → Response: SGC ID updated
   ```

4. **Unpublish a Submission**
   ```
   POST /publish
   { "action": "unpublish", "token": "...", "data": { "submission_id": "SGC-UUID-123", "local_key": "...", "submitter": {...} } }
   → Response: Submission unpublished
   ```

5. **Session End**
   ```
   POST /publish
   { "action": "end", "token": "..." }
   → Response: Session complete
   ```

---

## Related Files

- **Controller:** [app/Http/Controllers/PublishController.php](../app/Http/Controllers/PublishController.php)
- **Model:** [app/Submission.php](../app/Submission.php)
- **Related Models:** Gene, Disease, Classification, Inheritance, Submitter
- **Settings:** Uses Laravel Settings package for `allow_posts` and `token_posts`

---

## Configuration

### Enabling/Disabling Posts

Use the artisan command:
```bash
# Enable posts
php artisan gencc:allow-posts yes

# Disable posts
php artisan gencc:allow-posts no
```

### Token Configuration

The API token is stored in the `settings` table with key `token_posts`. This should be configured during deployment and shared securely with the submission portal.

---

## Verified Workflows

The following workflows have been tested and verified in production:

### ✅ New Submission Workflow
```
gencc-sub: submitted_new → sends action: "publish"
gencc-search: Creates new record with status=1
Result: Publicly visible submission
```

### ✅ Republish/Update Workflow
```
gencc-sub: submitted_republish → sends action: "publish"
gencc-search: Updates existing record, maintains status=1
Result: Updated publicly visible submission (no duplicates)
```

### ✅ Unpublish Workflow
```
gencc-sub: submitted_unpublish → sends action: "unpublish"
gencc-search: Soft-deletes record (status=0)
Result: Hidden from public queries, data preserved
```

### ✅ Re-publish After Unpublish
```
gencc-sub: submitted_republish → sends action: "publish"
gencc-search: Updates existing record, sets status=1
Result: Submission visible again (no duplicate created)
```

**Verification Details:** See [VERIFICATION_UNPUBLISH_WORKFLOW.md](./VERIFICATION_UNPUBLISH_WORKFLOW.md)

---

*Last Updated: 2025-11-08*
*Based on: app/Http/Controllers/PublishController.php*
