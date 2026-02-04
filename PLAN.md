# Plan: Replace gencc-search Database with gencc-sub Database

**STATUS: IMPLEMENTED** - All phases completed.

## Overview

Replace gencc-search's own database entirely with a direct connection to the gencc-sub database. gencc-search becomes a **read-only front-end** that queries gencc-sub tables directly. All API endpoints (used by gencc-sub to push data) are removed. Submitter logos are served from the `submitters.logo_contents` column instead of static files.

---

## Current State

### gencc-search Database (to be removed)
gencc-search maintains its own MySQL database with these tables:
- `genes` (53 cols) - gene data with HGNC fields, genomic coordinates, curation counts
- `diseases` (47 cols) - MONDO diseases with curation counts
- `submissions` (50+ cols) - published gene-disease submissions
- `submitters` (42 cols) - organizations with curation counts
- `classifications` (13 cols) - classification levels (Definitive, Strong, etc.)
- `inheritances` (11 cols) - modes of inheritance
- `publications` - PubMed references
- `disease_disease`, `disease_gene`, `disease_submission`, `publication_submission` - pivot tables
- `submission_files` - uploaded spreadsheet files
- `trios` - gene-disease-MOI triples
- `morbids` - OMIM data
- `terms` - lookup terms
- `conflicts` - gene-disease conflicts
- `settings` - app configuration (including `allow_posts`, `token_posts`)
- `static_file_headers` - HTTP header caching
- `notifications`, `users`, `password_resets`, `failed_jobs`

### gencc-sub Database (new source of truth)
gencc-sub has these tables that map to gencc-search needs:
- `genes` - gene data (HGNC, symbols, coordinates in JSON `coordinates` column, xrefs in JSON)
- `diseases` - diseases with MONDO normalization (`mondo_id` FK for OMIM/Orphanet → MONDO)
- `submissions` - full workflow submissions (drafts, published, unpublished)
- `submitters` - organizations with `logo_contents` (mediumText), `logo_mime_type`, `counts` (JSON)
- `classifications` - classification levels
- `inheritances` - modes of inheritance
- `mechanisms` - molecular mechanisms (new, not in gencc-search)
- `pubmeds` - full PubMed article metadata
- `pubmed_submission` - pivot table
- `jobs` - batch submission containers
- `documents` - file uploads
- `actions` - audit trail
- `releases` - publication tracking
- `metrics` - statistics
- `users`, `teams`, `submitter_user`, `sessions`, etc.

### API Logic to Remove
1. **`POST /api/release`** → `ReleaseController@process()` - Receives publish/unpublish from gencc-sub (no longer needed since we read directly)
2. **`GET /api/export/submissions`** → `DownloadController@export_XLSX` - Can move to web route
3. **`GET /api/export/submissions-with-rowid`** → `DownloadController@export_rowid_XLSX` - Can move to web route
4. **`Submission::getPubmedArticles()`** - HTTP call to gencc-sub API for PubMed data (can query directly now)
5. **`allowPosts` command** - Controls API access from gencc-sub (no longer needed)
6. **`ReleaseController.php`** - Entire controller removed
7. **Settings table entries** - `allow_posts`, `token_posts` no longer needed

---

## Table Mapping: gencc-sub → gencc-search Equivalent

### Direct Equivalents (query gencc-sub directly)

| gencc-search table | gencc-sub table | Key Differences |
|---|---|---|
| `genes` | `genes` | gencc-sub stores coordinates/xrefs as JSON (`coordinates`, `xrefs`) instead of flat columns (`start37`, `stop37`, `omim_id`, `ensembl_gene_id`, etc.). Counts stored in JSON `counts` instead of `curations_*` columns. |
| `diseases` | `diseases` | gencc-sub uses `mondo_id` FK for OMIM→MONDO normalization. Counts in JSON `counts` instead of `curations_*` columns. |
| `submissions` | `submissions` | gencc-sub has richer data: `submission_data` JSON, `evidence` JSON for PMIDs, `job_id`, `document_id`. Same `is_live`/`status` filtering applies. |
| `submitters` | `submitters` | gencc-sub has `logo_contents`/`logo_mime_type` instead of file-based logos. `name` instead of `title`. Counts in JSON `counts`. |
| `classifications` | `classifications` | gencc-sub uses `name` instead of `title`. Has `order` column. Missing `hex_color`, `css_class`, `slug`, `href`. |
| `inheritances` | `inheritances` | gencc-sub uses `name` instead of `title`. Has `abbreviation`. Missing `hex_color`, `css_class`. |
| `publications` | `pubmeds` | gencc-sub has full PubMed metadata (50+ fields). gencc-search only stored `pubmedid`, `title`, `description`. |
| `publication_submission` | `pubmed_submission` | Same structure, different table name. |

### gencc-search Tables — Disposition

| Table | Disposition |
|---|---|
| `disease_submission` | **Relationship rewrite** — gencc-sub uses direct FK (`submissions.disease_id`). See "Relationship Rewrite" section. |
| `publication_submission` | Replaced by gencc-sub's `pubmed_submission` pivot table |
| `static_file_headers` | Already exists in gencc-sub |
| `settings` | Remove — only used for `allow_posts`/`token_posts` (API removed) |
| `trios` | **Dead code — remove** (see Dead Code section) |
| `morbids` | **Dead code — remove** |
| `terms` | **Dead code — remove** |
| `conflicts` | **Dead code — remove** |
| `disease_disease` | **Dead code — remove** |
| `disease_gene` | **Dead code — remove** |
| `submission_files` | **Dead code — remove** (harvest files from production first) |
| `notifications` | **Dead code — remove** |

### gencc-sub Tables NOT Used by gencc-search

These exist in gencc-sub but gencc-search doesn't need them:
- `jobs` - Workflow management (gencc-sub internal)
- `documents` - File uploads (gencc-sub internal)
- `actions` - Audit trail (gencc-sub internal)
- `releases` - Publication tracking (gencc-sub internal)
- `metrics` - Internal statistics
- `aliases` - Custom field mappings
- `workers` - Job queue
- `sgc_sequences` - SID generation
- `admin_logs` - Admin audit
- `teams`, `team_user`, `team_invitations` - Team management
- `roles`, `permissions`, etc. - Permission system
- `sessions`, `personal_access_tokens` - Auth infrastructure

---

## Model Changes

### Existing Models → Rewired to gencc-sub

Each model gets `protected $connection = 'gencc_sub';` and updated field references.

#### 1. `Gene` Model

**Current:** Queries local `genes` table with flat columns for coordinates, external IDs, curation counts.

**Changes needed:**
- Add `protected $connection = 'gencc_sub';`
- Update JSON casts: `coordinates`, `xrefs`, `counts`, `scores` are JSON in gencc-sub
- **Accessors needed** (these fields are actively used in blade templates, Livewire, or filters):
  - `curations_definitive` etc. → `$this->counts['definitive'] ?? 0` (used extensively in Livewire filters and blade pills)
  - `count_submissions` → `$this->counts['submissions'] ?? 0` (used in blade templates and filters)
  - `alias_symbol` → `alias_symbols` (renamed in gencc-sub; not used in active blade templates but keeping for model consistency)
  - `prev_symbol` → `previous_symbols` (renamed in gencc-sub; same as above)
- **No accessors needed** (not used in any active code):
  - `omim_id`, `ensembl_gene_id`, `entrez_id`, `ucsc_id` — no active blade template, controller, or Livewire references these
  - `start37/stop37/seqid37`, `start38/stop38/seqid38` — only used by dead code `searchList()`
  - `hi`, `plof`, `pli` — never referenced in active code
- **Remove dead code:**
  - `searchList()`, `rosetta()` (see Dead Code section)
  - Scopes `omim()`, `ensembl()`, `entrez()`, `ucsc()`, `cytoband()`, `previous()`, `alias()` — only called from `rosetta()` which is dead code. No active controller, Livewire, or command calls any of these.
  - `getGrch37Attribute()`, `getGrch38Attribute()` — never called from any blade template or controller
  - `getDisplayOmimAttribute()`, `getDisplayAliasesAttribute()`, `getDisplayPreviousAttribute()` — never called from any active blade template
- **`location`** field exists as flat column in both databases — no change needed (used in `shared/gene-banner.blade.php`)

#### 2. `Disease` Model

**Current:** Queries local `diseases` table with flat curation count columns.

**Changes needed:**
- Add `protected $connection = 'gencc_sub';`
- **Accessor needed:** `title` → `$this->name` (used extensively in blade templates and `DiseaseController::index` `orderBy('title')`)
- **Accessors needed:** `curations_definitive` etc. → `$this->counts['definitive'] ?? 0` (used in blade curation pills)
- **Accessor needed:** `count_submissions` → `$this->counts['submissions'] ?? 0` (used in disease index)
- `synonyms`, `xrefs`, `scores`, `activity`, `events` already JSON in both
- Rewrite `submissions()` from `belongsToMany` to `hasMany` (see Relationship Rewrite section)
- **Remove:** `rosetta()` — dead code (see Dead Code section)
- **Remove:** Legacy relationships (`xrefs()`, `synonyms_rel()`, etc.) — dead code

#### 3. `Submission` Model

**Current:** Queries local `submissions` table populated by ReleaseController API.

**Changes needed:**
- Add `protected $connection = 'gencc_sub';`
- **Accessor needed:** `uuid` → `$this->sid` (used extensively in blade templates for links and display)
- **Accessor needed:** `disease_original_id` → `$this->original_disease_id` (used in submission detail view)
- **Accessor needed:** `moi_id` → `$this->inheritance_id` (used by `inheritance()` relationship)
- **Flat column mappings** (gencc-sub has these as real columns):
  - `submitted_as_date` → `report_date` (flat column in gencc-sub)
  - `submitted_as_public_report_url` → `report_url` (flat column in gencc-sub)
- **Accessors from `submission_data` JSON** (actively used in blade templates):
  - `submitted_as_assertion_criteria_url` → `$this->submission_data['criteria']['url']`
  - `submitted_as_notes` → `$this->submission_data['notes']['display']`
  - `submitted_as_disease_name` → `$this->submission_data['disease']['name']` (used in disease filter `HasDisease`)
  - `submitted_as_submission_id` → `$this->sid` (flat column, used in submission detail)
  - `submitted_as_hgnc_id` → `$this->submission_data['gene']['id']` (used in SubmissionsImport)
  - `submitted_as_disease_id` → `$this->submission_data['disease']['id']` (used in SubmissionsImport)
- **No accessor needed** (only referenced by dead code being removed):
  - `submitted_as_submitter_name` — only in SubmissionsImport (being removed with dashboard)
  - `submitted_as_moi_id`, `submitted_as_moi_name` — only in SubmissionsImport
  - `submitted_as_classification_id`, `submitted_as_classification_name` — only in SubmissionsImport
- `submitted_as_pmids` → `evidence` JSON array (flat array of PMID strings)
- `pubmeds()` relationship → already exists in gencc-sub with `pubmed_submission` pivot
- **Remove** `getPubmedArticles()` HTTP call — query `pubmeds` table directly via relationship (used in `submissions/show.blade.php`)
- **Remove** `diseases()` many-to-many (see Relationship Rewrite section)
- Scopes `live()`, `published()` remain the same (both databases use `is_live` and `status`)
- `byDisplayId()` scope → query `sid` instead of `uuid`

#### 4. `Submitter` Model

**Current:** Queries local `submitters` table. Logo from `/public/brand/submitters/{uuid}.png`.

**Changes needed:**
- Add `protected $connection = 'gencc_sub';`
- **Accessors needed** (actively used in blade templates):
  - `title` → `$this->name` (used extensively in blade templates and controllers)
  - `uuid` → `$this->ident` (used for links, logo paths, and Livewire filters)
  - `text_descriptions` → `$this->description` (used in `submitters/show.blade.php`)
  - `text_assertions` → `$this->assertion` (used in `submitters/show.blade.php`)
  - `text_contact` → derive from `contacts` JSON (used in `submitters/show.blade.php`)
  - `curations_definitive` etc. → `$this->counts['definitive'] ?? 0` (used in submitter grid)
  - `count_submissions` → `$this->counts['submissions'] ?? 0` (used in submitter show/grid)
- **No accessor needed:**
  - `path_logo` — logo now served from `logo_contents` via LogoController
- `member` and `downloadable` → prerequisite columns to add to gencc-sub (see Prerequisites)
- Logo serving: new route/controller to serve `logo_contents` with `logo_mime_type` header

#### 5. `Classification` Model

**Changes needed:**
- Add `protected $connection = 'gencc_sub';`
- **Accessor needed:** `title` → `$this->name`
- `hex_color`, `css_class`, `slug`, `href` → prerequisite columns to add to gencc-sub (see Prerequisites, Decisions #3)

#### 6. `Inheritance` Model

**Changes needed:**
- Add `protected $connection = 'gencc_sub';`
- **Accessor needed:** `title` → `$this->name`
- `hex_color`, `css_class` → prerequisite columns to add to gencc-sub (see Prerequisites, Decisions #3)

#### 7. `Pubmed` Model (replaces `Publication`)

**Changes needed:**
- Add `protected $connection = 'gencc_sub';`
- `$table = 'pubmeds';`
- `pubmedid` → `pmid` (renamed)
- Much richer data available (50+ fields from NCBI)

#### 8. Models to Remove (Dead Code)

- `SubmissionFile`, `Trio`, `Morbid`, `Conflict`, `Term`, `Notification` — all dead code (see Dead Code section)
- `StaticFileHeader` - exists in gencc-sub already

---

## Controller Changes

### Controllers to Modify

#### `GeneController`
- `show()`: Currently loads Classifications with their submissions filtered to the gene. The query logic stays the same but model field names change.
- `disease()`, `submitter()`: Same — field name updates only.
- Impact: Moderate — need to update any references to flat gene columns.

#### `DiseaseController`
- `index()`: `Disease::has('submissions')->paginate(25)` — works if `submissions()` relationship is correct on gencc-sub.
- `show()`: Loads classifications with submissions — same logic, field name changes.

#### `SubmissionController`
- `show()`: Looks up by display ID. Change `uuid` references to `sid`. Update version/unpublish logic.

#### `SubmitterController`
- `index()`: `Submitter::where('status', 1)->paginate(25)` — works on gencc-sub.
- `show()`: UUID lookup changes. Logo display changes.

#### `DownloadController`
- `index()`, export methods: Move export routes from `api.php` to `web.php`. Update SubmissionExport queries for new field names.

#### `StatController`
- Uses submitter curation counts → update to read from `counts` JSON.

### Controllers to Remove

#### `AdministratorController` / Dashboard Controllers
- **Remove entirely** — admin dashboard lives in gencc-sub only (see Decisions #4).

#### `ReleaseController`
- Entire file deleted. This was the API that received publish/unpublish requests from gencc-sub.

#### `ReportController`
- Dead code — remove (see Dead Code section).

### Routes to Remove

```php
// routes/api.php - REMOVE ENTIRELY or keep only export routes
Route::post('/release', 'ReleaseController@process');         // REMOVE
Route::get('/api/export/submissions', ...);                    // MOVE to web.php
Route::get('/api/export/submissions-with-rowid', ...);         // MOVE to web.php
Route::middleware('auth:api')->get('/user', ...);              // REMOVE
```

---

## Artisan Command Changes

### Commands to Remove

- `allowPosts` - No longer needed (API removed)
- `updateSubmissions` (`gencc:update-submissions`) - Submissions come from gencc-sub directly
- `updateConnections` (`update:connections`) - Dead code (see Dead Code section)
- `deleteSubmitterSubmissions` - Manage in gencc-sub
- `setSubmitterSubmissionStatuses` - Manage in gencc-sub
- `genccFixit` - Data fixes should happen in gencc-sub
- `UpdateMorbid` (`update:morbid`) - Dead code (see Dead Code section)
- `RunReport` (`run:report`) - Dead code (see Dead Code section)
- `updateGdms` - Dead code (see Dead Code section)
- `UpdateSources` - Only calls removed commands (`update:morbid`)

### Commands to Move to gencc-sub

- `updateCounts` (`gencc:update-counts`) — Move to gencc-sub (see Decisions #2). gencc-search is read-only.
- `updateHgnc` (`update:hgnc`) — gencc-sub already has its own `update:hgnc`. Remove from gencc-search.
- `UpdateMondo` (`update:mondo`) — gencc-sub already has `update:diseases`. Remove from gencc-search.

### Commands to Keep (with field updates)

- Export commands (just need model field updates)

---

## Livewire Component Changes

### `Genes/Listing.php` (Main Search)
- Queries `Gene` model with filters on `title`, classification counts, submitter
- **Changes:**
  - `title` field works (same in gencc-sub)
  - Classification count filters: `curations_definitive`, etc. → query JSON `counts` column
  - Submitter filter: `whereHas('submissions.submitter', ...)` → `uuid` becomes `ident` or `curie`
  - `submitted_as_disease_name` → from `submission_data` JSON
  - Natural sort `REGEXP_SUBSTR` should still work

### `Gene/ListingByClassification.php`, `Gene/ListingBySubmitter.php`, `Gene/ListingByDisease.php`
- Update field references for renamed columns

### `Submitter/ListingOfSubmissions.php`
- Update submission field references

### Dashboard Livewire Components
- **Remove entirely** — admin dashboard lives in gencc-sub only (see Decisions #4).

---

## Logo Serving from Database

### Current: File-Based
```blade
<img src="/brand/submitters/{{ $submitter->uuid }}.png">
```

### New: Database-Served
Create a new route and controller method to serve logos from `logo_contents`:

```php
// routes/web.php
Route::get('/brand/submitters/{identifier}.png', 'LogoController@show');
```

```php
// LogoController.php
public function show($identifier)
{
    // Transform GENCC_000101 back to find submitter
    $submitter = Submitter::where('curie', str_replace('_', ':', $identifier))
                          ->first();

    if (!$submitter || !$submitter->logo_contents) {
        return response()->file(public_path('brand/submitters/default.png'));
    }

    return response($submitter->logo_contents)
        ->header('Content-Type', $submitter->logo_mime_type ?? 'image/png')
        ->header('Cache-Control', 'public, max-age=86400');
}
```

**Alternative:** Add an accessor on the Submitter model that returns a data URI for inline `<img src>` tags. This avoids the extra HTTP request but increases HTML payload size.

**Recommended approach:** Use the route-based approach for backward compatibility — existing blade templates that reference `/brand/submitters/{uuid}.png` continue to work with minimal changes.

---

## View/Blade Template Changes

### Field Name Updates Needed Throughout
- `$submitter->title` → `$submitter->name`
- `$submitter->uuid` → `$submitter->ident` (or keep accessor)
- `$classification->title` → `$classification->name`
- `$inheritance->title` → `$inheritance->name`
- `$disease->title` → `$disease->name`
- `$submission->uuid` → `$submission->sid`
- `$gene->omim_id` → accessor from `xrefs` JSON
- `$gene->alias_symbol` → `$gene->alias_symbols`
- `$gene->prev_symbol` → `$gene->previous_symbols`

### Approach: Accessors vs. Direct Rename
**Recommended:** Add accessors on models for backward compatibility so blade templates need minimal changes:

```php
// On Submitter model
public function getTitleAttribute() { return $this->name; }
public function getUuidAttribute() { return $this->ident; }

// On Classification/Inheritance/Disease models
public function getTitleAttribute() { return $this->name; }
```

This minimizes the blast radius of changes in blade templates.

---

## Database Configuration

### Remove: gencc-search's own database
- No more local MySQL database for gencc-search
- Remove all migrations (or archive them)
- Remove seeders

### Add: gencc-sub connection as default

```php
// config/database.php
'default' => env('DB_CONNECTION', 'mysql'),

'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', '127.0.0.1'),
        'port' => env('DB_PORT', '3306'),
        'database' => env('DB_DATABASE', 'gencc_sub'),  // Point to gencc-sub DB
        'username' => env('DB_USERNAME', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        ...
    ],
],
```

```env
# .env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=gencc_sub
DB_USERNAME=gencc_search_reader
DB_PASSWORD=secret
```

**Important:** The database user should have **read-only** (`SELECT` only) access. gencc-search has zero write requirements (see Decisions #6).

---

## Implementation Steps

### Phase 1: Dead Code Removal

Remove all dead code first to reduce noise and avoid wasted effort on code that won't survive.

1. Remove dead models: `SubmissionFile`, `Trio`, `Morbid`, `Conflict`, `Term`, `Notification`
2. Remove dead commands: `updateGdms`, `UpdateMorbid`, `RunReport`, `updateConnections`, `updateSubmissions`, `UpdateSources`, `allowPosts`, `deleteSubmitterSubmissions`, `setSubmitterSubmissionStatuses`, `genccFixit`
3. Remove dead controllers: `ReportController`, `AdministratorController` (+ dashboard controllers)
4. Remove dead Gene methods/scopes: `searchList()`, `rosetta()`, scopes (`omim`, `ensembl`, `entrez`, `ucsc`, `cytoband`, `previous`, `alias`), accessors (`getGrch37Attribute`, `getGrch38Attribute`, `getDisplayOmimAttribute`, `getDisplayAliasesAttribute`, `getDisplayPreviousAttribute`)
5. Remove dead Disease methods: `rosetta()`, legacy relationships (`xrefs()`, `synonyms_rel()`, `equivalents()`, `synonym_parents()`, `synonym_children()`, `parents()`, `children()`)
6. Remove dashboard Livewire components (e.g. `SubmissionFileUpload`)
7. Remove dead routes: `/reports`, dashboard routes
8. Remove dead views: `resources/views/reports/`, dashboard blade templates
9. Remove `SubmissionsImport` references to `Term`
10. Remove `is_morbid` from `Gene.php` fillable array
11. Remove commented-out reports nav link in `header-nav.blade.php`

### Phase 2: Remove API Logic

1. Delete `ReleaseController.php`
2. Delete `POST /api/release` route
3. Move export routes from `api.php` to `web.php`
4. Remove or simplify `routes/api.php`
5. Remove `settings` table dependencies (`allow_posts`, `token_posts`)

### Phase 3: Database Connection & Core Models

1. Update `config/database.php` to point default connection at gencc-sub database
2. Update `.env` / `.env.example` with gencc-sub database credentials
3. Rewrite `Gene` model: connection, JSON casts, accessors for flat→JSON fields, updated scopes
4. Rewrite `Disease` model: connection, field renames, JSON counts, `submissions()` from `belongsToMany` to `hasMany`
5. Rewrite `Submission` model: connection, `sid` for `uuid`, `submission_data` JSON, `evidence` for PMIDs, remove `getPubmedArticles()` HTTP call, remove `diseases()` many-to-many
6. Rewrite `Submitter` model: connection, field renames, logo from DB, counts from JSON
7. Rewrite `Classification` model: connection, field renames, style mapping
8. Rewrite `Inheritance` model: connection, field renames
9. Create/update `Pubmed` model to use gencc-sub `pubmeds` table

### Phase 4: Logo Serving

1. Create `LogoController` with route to serve logos from `submitters.logo_contents`
2. Update blade templates if needed (or rely on same URL pattern with new controller)

### Phase 5: Controller & Livewire Updates

1. Update `GeneController` for new field names/JSON access
2. Update `DiseaseController` for new field names
3. Update `SubmissionController` — `uuid` → `sid`, version logic
4. Update `SubmitterController` — field renames, logo display
5. Update `StatController` — counts from JSON
6. Update `DownloadController` / Export classes for new field names
7. Update `Genes/Listing.php` — JSON counts filtering, field renames
8. Update other Livewire components for field renames
9. Update blade templates (or rely on model accessors)

### Phase 6: Cleanup

1. Remove commands being moved to gencc-sub (`updateCounts`, `updateHgnc`, `UpdateMondo`)
2. Archive/remove gencc-search migrations
3. Remove seeders
4. Remove static logo files from `/public/brand/submitters/`
5. Update tests

---

## Decisions (Resolved)

1. **`member` and `downloadable` fields** → Add these columns to gencc-sub's `submitters` table.

2. **`updateCounts` command** → Move to gencc-sub. gencc-search will be purely read-only.

3. **Classification/Inheritance display fields** → Add `hex_color`, `css_class`, `slug`, `href` columns to gencc-sub's `classifications` and `inheritances` tables.

4. **Admin Dashboard** → Remove entirely from gencc-search. All admin/dashboard functionality lives in gencc-sub only.

5. **Contact/Personnel display** → Use existing approach. gencc-sub has a `contacts` JSON column on `submitters` table. The current submitter detail page renders `text_contact` HTML for a "Personnel" section. The migration path: gencc-search reads `contacts` JSON from the submitter and renders it in the blade template the same way `text_contact` was rendered. If `contacts` JSON contains structured data like `[{name, title, email}, ...]`, the blade template iterates and renders HTML. No new tables or relationships needed in gencc-search.

6. **Read-only access** → gencc-search is strictly read-only. No writes to gencc-sub. All write operations (counts, settings, submissions, etc.) happen in gencc-sub.

---

## Dead Code & Unused Features — Remove

### Gene Region Search

`Gene::searchList()` is dead code. It exists in the model but is never called from any controller, route, Livewire component, or API endpoint. There is no UI in gencc-search that allows users to search by chromosome or genomic coordinates. The gene detail page displays the `location` string (e.g. "17q21.31") as read-only text but that is not a search feature.

**Action:** Delete `Gene::searchList()`, `Gene::rosetta()`, and their tests. Also remove related dead methods and scopes:
- Scopes: `omim()`, `ensembl()`, `entrez()`, `ucsc()`, `cytoband()`, `previous()`, `alias()` — only called from `rosetta()`, no other active callers
- Accessors: `getGrch37Attribute()`, `getGrch38Attribute()`, `getDisplayOmimAttribute()`, `getDisplayAliasesAttribute()`, `getDisplayPreviousAttribute()` — never called from any blade template or controller
- No flat coordinate columns need to be added to gencc-sub. The `coordinates` JSON column in gencc-sub is sufficient for display purposes.

### Gene & Disease rosetta() Methods

`Gene::rosetta()` is a multi-format gene ID resolver (HGNC, OMIM, Ensembl, Entrez, UCSC). It has unit tests but is never called from any controller, Livewire component, command, or route. Dead code.

`Disease::rosetta()` is only called by `ReleaseController` (the API being removed entirely). Once `ReleaseController` is deleted, `Disease::rosetta()` has no remaining callers. Its only other reference is a deprecation comment in `ModelTransform.php`.

**Action:** Remove `Gene::rosetta()`, `Disease::rosetta()`, and their tests.

### Reports Page & Trios

The `/reports` route and `ReportController` exist but the navigation link is **commented out** in `header-nav.blade.php`. The page is not accessible to users. It uses the `Trio` model which is populated by the `updateGdms` command.

**Action:** Remove all of the following:
- `app/Http/Controllers/ReportController.php`
- `app/Trio.php` model
- `app/Console/Commands/updateGdms.php` command
- `resources/views/reports/` directory
- `/reports` route from `routes/web.php`
- `trios` and `submission_trio` migrations (archived with other migrations)
- The commented-out reports nav link in `header-nav.blade.php`

### Morbid Data & OMIM Report

The `Morbid` model and `is_morbid` gene flag are purely backend data with no UI exposure. `RunReport` generates an internal TSV file at `/tmp/omimreport.tsv` using `Morbid` records — it is not user-facing. `UpdateMorbid` populates the `morbids` table from OMIM data. `UpdateSources` calls `update:morbid` as part of a scheduled sync.

**Action:** Remove all of the following:
- `app/Morbid.php` model
- `app/Console/Commands/UpdateMorbid.php` command
- `app/Console/Commands/RunReport.php` command
- `is_morbid` references in `Gene.php` fillable array
- `morbids` migration (archived with other migrations)

### Terms Lookup Table

The `terms` table is a gene symbol alias lookup populated by `updateHgnc` — it maps gene symbols, previous symbols, and alias symbols to HGNC IDs. However, it is **only written to, never read from** in any active code path. The only read reference (`Term::name()` in `SubmissionsImport`) is commented out. The Gene model already handles alias/previous symbol lookups via JSON columns and dedicated scopes.

**Action:** Remove all of the following:
- `app/Term.php` model
- `Term::updateOrCreate()` calls in `updateHgnc.php`
- `use App\Term` import in `SubmissionsImport.php` (and commented-out code referencing it)
- `terms` migration (archived with other migrations)

### Notifications Table

The `Notification` model is an internal processing audit log used by artisan commands (`updateCounts`, `updateSubmissions`, `findDuplicateSubmissions`) to record processing events via `Notification::create()`. No UI reads from it — no blade templates or controllers reference it. All commands that write to it are being removed or moved to gencc-sub.

**Action:** Remove all of the following:
- `app/Notification.php` model
- `Notification::create()` calls in commands being removed/moved
- `notifications` migration (archived with other migrations)

### disease_disease Pivot Table

The `disease_disease` pivot table stores disease-to-disease relationships (xrefs, synonyms, equivalents, parent/child hierarchy). The Disease model already marks these relationships as legacy with the comment: "Legacy relationships - kept for backward compatibility during migration. These can be removed once the disease_disease pivot table is dropped." No blade templates use these relationships. The only consumers are `updateConnections` and `UpdateMondo` commands which are being removed/moved. gencc-sub handles disease normalization via the `mondo_id` FK instead.

**Action:** Remove all of the following:
- Legacy relationships in `Disease.php`: `xrefs()`, `synonyms_rel()`, `equivalents()`, `synonym_parents()`, `synonym_children()`, `parents()`, `children()`
- `app/Console/Commands/updateConnections.php` command
- `disease_disease` migration (archived with other migrations)

### disease_gene Pivot Table

Only exists as a migration file. No model defines a relationship through it, no controller or command references it.

**Action:** Remove the `disease_gene` migration (archived with other migrations).

### Submission Files & Upload Workflow

The `SubmissionFile` model stores metadata for uploaded spreadsheet files used in the admin dashboard workflow. The `SubmissionFileUpload` Livewire component uploads files to Laravel's local storage disk (`storage/app/file/`), and the `updateSubmissions` command reads them back via `Storage::disk('local')->path($submission->path)` to parse and import submission data. All consumers are admin dashboard Livewire components and commands being removed.

> **⚠ Before removing:** Consider harvesting the legacy uploaded files from the production server. These files may be worth archiving alongside their corresponding submitter job records in gencc-sub for historical reference. Write a one-off script to pull the files from `storage/app/file/` on production before decommissioning.

**Action:** Remove all of the following:
- `app/SubmissionFile.php` model
- `app/Http/Livewire/SubmissionFileUpload.php` component (and related dashboard Livewire components)
- `app/Console/Commands/updateSubmissions.php` command
- `submission_files` migration (archived with other migrations)
- Any blade templates referencing submission file uploads

---

## Relationship Rewrite: disease_submission → Direct FK

The `disease_submission` pivot table is **actively used** by the disease pages but does not exist in gencc-sub. This requires a relationship rewrite.

### Current (gencc-search): Many-to-Many via Pivot

```php
// Disease.php
public function submissions()
{
    return $this->belongsToMany('App\Submission', 'disease_submission')
        ->where('is_live', '=', true)
        ->where('status', '=', Submission::STATUS_PUBLISHED)
        ->withTimestamps()
        ->withPivot('type');
}

// Submission.php
public function diseases()
{
    return $this->belongsToMany('App\Disease', 'disease_submission')
        ->withTimestamps()
        ->withPivot('type');
}
```

Used by:
- `DiseaseController@index`: `Disease::has('submissions')->orderBy('title')->paginate(25)` — disease listing page
- `DiseaseController@show`: `Disease::curie($id)->with('submissions.gene', 'submissions.disease')` — disease detail page
- `DiseaseFeatureTest`, `StatisticsFeatureTest`

### New (gencc-sub): Direct FK on submissions table

gencc-sub has `submissions.disease_id` as a direct FK to `diseases.id`. No pivot table.

```php
// Disease.php — rewrite
public function submissions()
{
    return $this->hasMany('App\Submission', 'disease_id')
        ->where('is_live', '=', true)
        ->where('status', '=', Submission::STATUS_PUBLISHED);
}

// Submission.php — rewrite (remove diseases() many-to-many, keep disease() belongsTo)
// The existing disease() belongsTo already works:
public function disease()
{
    return $this->belongsTo('App\Disease');
}
```

The `Disease::has('submissions')` and `with('submissions.gene')` calls in controllers work identically with `hasMany` — no controller changes needed. The `Submission::diseases()` many-to-many can be removed (its only blade reference is commented out).

**Action:**
- Rewrite `Disease::submissions()` from `belongsToMany` to `hasMany`
- Remove `Submission::diseases()` many-to-many relationship
- Remove `disease_submission` migration (archived with other migrations)
- Update tests that seed the pivot table to use direct FK instead

---

## Changes Required in gencc-sub (Prerequisites)

These changes must be made in the gencc-sub codebase **before** gencc-search can switch databases:

### Submitters Table Additions

```php
// Migration in gencc-sub
$table->boolean('member')->default(false);       // Member vs data submitter
$table->boolean('downloadable')->default(false);  // Downloadable flag
```

### Classifications Table Additions

```php
// Migration in gencc-sub
$table->string('hex_color')->nullable();   // e.g. "#4CAF50"
$table->string('css_class')->nullable();   // e.g. "bg-green-500"
$table->string('slug')->nullable();        // e.g. "definitive"
$table->string('href')->nullable();        // URL fragment
```

### Inheritances Table Additions

```php
// Migration in gencc-sub
$table->string('hex_color')->nullable();
$table->string('css_class')->nullable();
```

### updateCounts Command

Move from gencc-search to gencc-sub. Writes aggregated curation counts to the `counts` JSON column on `genes`, `diseases`, and `submitters` tables.

---

## Write Requirements Summary

**gencc-search needs ZERO write access.** All writes happen in gencc-sub:

| Write Operation | Currently In | Moves To |
|---|---|---|
| Publish/unpublish submissions | gencc-search (ReleaseController API) | Already in gencc-sub |
| Update curation counts | gencc-search (`gencc:update-counts`) | gencc-sub |
| Update HGNC gene data | gencc-search (`update:hgnc`) | gencc-sub (already has `update:hgnc`) |
| Update MONDO diseases | gencc-search (`update:mondo`) | gencc-sub (already has `update:diseases`) |
| Manage submitter profiles | gencc-search (admin dashboard) | gencc-sub (already has dashboard) |
| Settings (allow_posts, etc.) | gencc-search (`settings` table) | Removed (no API to gate) |

gencc-search database user: `SELECT` privileges only on the gencc-sub database.
