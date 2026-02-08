# GenCC-Sub Database Schema

This document provides the complete database schema for the `gencc_sub` database used by the GenCC submission management system.

## Table of Contents

- [Core Data Tables](#core-data-tables)
  - [genes](#genes)
  - [diseases](#diseases)
  - [classifications](#classifications)
  - [inheritances](#inheritances)
  - [mechanisms](#mechanisms)
  - [submissions](#submissions)
  - [submitters](#submitters)
  - [pubmeds](#pubmeds)
- [User & Authentication Tables](#user--authentication-tables)
  - [users](#users)
  - [teams](#teams)
  - [submitter_user](#submitter_user)
  - [team_user](#team_user)
  - [team_invitations](#team_invitations)
  - [sessions](#sessions)
  - [password_reset_tokens](#password_reset_tokens)
  - [personal_access_tokens](#personal_access_tokens)
- [Role & Permission Tables](#role--permission-tables)
  - [roles](#roles)
  - [permissions](#permissions)
  - [model_has_roles](#model_has_roles)
  - [model_has_permissions](#model_has_permissions)
  - [role_has_permissions](#role_has_permissions)
- [Workflow & Processing Tables](#workflow--processing-tables)
  - [jobs](#jobs)
  - [documents](#documents)
  - [actions](#actions)
  - [releases](#releases)
  - [workers](#workers)
  - [failed_jobs](#failed_jobs)
- [Supporting Tables](#supporting-tables)
  - [aliases](#aliases)
  - [notifications](#notifications)
  - [metrics](#metrics)
  - [admin_logs](#admin_logs)
  - [settings](#settings)
  - [sgc_sequences](#sgc_sequences)
  - [static_file_headers](#static_file_headers)
  - [user_information](#user_information)
  - [migrations](#migrations)

---

## Core Data Tables

### genes

Stores gene information from HGNC with curation statistics.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Gene type |
| hgnc_id | varchar(255) | NO | MUL | NULL | HGNC ID (e.g., "HGNC:1234") |
| symbol | varchar(255) | NO | MUL | NULL | Gene symbol (e.g., "BRCA1") |
| name | varchar(255) | NO | | NULL | Gene name |
| description | text | YES | | NULL | Gene description |
| alias_symbols | json | YES | | NULL | Alternative symbols |
| previous_symbols | json | YES | | NULL | Historical symbols |
| alias_names | json | YES | | NULL | Alternative names |
| previous_names | json | YES | | NULL | Historical names |
| date_symbol_changed | timestamp | YES | | NULL | Symbol change date |
| date_name_changed | timestamp | YES | | NULL | Name change date |
| locus_group | varchar(255) | NO | | NULL | Locus group |
| locus_type | varchar(255) | NO | | NULL | Locus type |
| gene_group_id | int | YES | | NULL | Gene group ID |
| gene_group | varchar(255) | YES | | NULL | Gene group name |
| location | varchar(255) | NO | | NULL | Chromosomal location |
| coordinates | json | NO | | json_array() | Genomic coordinates |
| xrefs | json | NO | | json_array() | External references (OMIM, Ensembl, etc.) |
| scores | json | NO | | json_array() | Gene scores |
| counts | json | NO | | json_array() | Curation counts by classification |
| activity | json | NO | | json_array() | Activity history |
| events | json | NO | | json_array() | Event log |
| notes | text | YES | | NULL | Internal notes |
| status | tinyint | NO | | 0 | Status flag |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### diseases

Stores disease information from MONDO, OMIM, and Orphanet.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| mondo_id | bigint unsigned | YES | MUL | NULL | Reference to parent MONDO disease |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Disease type (1=MONDO, 2=OMIM, 3=ORPHA) |
| curie | varchar(255) | NO | MUL | NULL | CURIE (e.g., "MONDO:0000001") |
| name | varchar(255) | NO | | NULL | Disease name |
| deprecated_name | varchar(255) | YES | | NULL | Previous name if deprecated |
| description | text | YES | | NULL | Disease description |
| synonyms | json | YES | | NULL | Disease synonyms |
| xrefs | json | NO | | json_array() | Cross-references (omim_id, orpha_id arrays) |
| scores | json | NO | | json_array() | Disease scores |
| counts | json | NO | | json_array() | Curation counts |
| activity | json | NO | | json_array() | Activity history |
| events | json | NO | | json_array() | Event log |
| notes | text | YES | | NULL | Internal notes |
| status | tinyint | NO | | 0 | Status (0=active, 8=deprecated) |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### classifications

Stores gene-disease classification categories (Definitive, Strong, etc.).

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Classification type |
| curie | varchar(255) | NO | MUL | NULL | CURIE (e.g., "GENCC:100001") |
| name | varchar(255) | NO | | NULL | Full name (e.g., "Definitive") |
| description | varchar(255) | NO | | NULL | Description |
| abbreviation | varchar(255) | NO | | NULL | Short form |
| informational | varchar(255) | YES | | NULL | Informational text |
| style_class | varchar(255) | YES | | NULL | CSS style class |
| hex_color | varchar(255) | YES | | NULL | Hex color code |
| css_class | varchar(255) | YES | | NULL | CSS class name |
| slug | varchar(255) | YES | | NULL | URL-friendly slug |
| href | varchar(255) | YES | | NULL | Link URL |
| order | int | NO | | 0 | Display order |
| status | tinyint | NO | | 0 | Status flag |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### inheritances

Stores mode of inheritance types (Autosomal dominant, Autosomal recessive, etc.).

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Inheritance type |
| curie | varchar(255) | NO | MUL | NULL | CURIE (HP ontology) |
| name | varchar(255) | NO | | NULL | Full name |
| description | varchar(255) | NO | | NULL | Description |
| abbreviation | varchar(255) | NO | | NULL | Short form (AD, AR, etc.) |
| informational | varchar(255) | YES | | NULL | Informational text |
| style_class | varchar(255) | YES | | NULL | CSS style class |
| hex_color | varchar(255) | YES | | NULL | Hex color code |
| css_class | varchar(255) | YES | | NULL | CSS class name |
| status | tinyint | NO | | 0 | Status flag |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### mechanisms

Stores mechanism of disease types (Loss of function, Gain of function, etc.).

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| type | tinyint | NO | | 1 | Mechanism type |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| curie | varchar(255) | NO | | NULL | CURIE |
| name | varchar(255) | NO | | NULL | Full name |
| description | varchar(255) | NO | | NULL | Description |
| abbreviation | varchar(255) | NO | | NULL | Short form |
| informational | varchar(255) | YES | | NULL | Informational text |
| style_class | varchar(255) | YES | | NULL | CSS style class |
| status | tinyint | NO | | 0 | Status flag |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### submissions

Core table storing gene-disease curation submissions with versioning support.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Submission type |
| sid | varchar(255) | YES | MUL | NULL | SGC ID (e.g., "SGC-123456") - shared across versions |
| version_number | int unsigned | NO | | 1 | Version number within SGC ID |
| local_key | varchar(255) | YES | MUL | NULL | Submitter's local key |
| friendly | varchar(255) | YES | | NULL | Friendly display ID |
| job_id | bigint unsigned | NO | MUL | NULL | Parent job reference |
| document_id | bigint unsigned | YES | MUL | NULL | Source document reference |
| gene_id | bigint unsigned | YES | | NULL | Gene reference |
| disease_id | bigint unsigned | YES | | NULL | MONDO disease reference |
| original_disease_id | bigint unsigned | YES | MUL | NULL | Original submitted disease reference |
| inheritance_id | bigint unsigned | YES | | NULL | Inheritance reference |
| submitter_id | bigint unsigned | NO | MUL | NULL | Submitter reference |
| classification_id | bigint unsigned | YES | MUL | NULL | Classification reference |
| mechanism_id | bigint unsigned | YES | | NULL | Mechanism reference |
| user_id | bigint unsigned | NO | | NULL | User who created |
| evidence | json | YES | | NULL | Evidence data |
| normalized_pmids | text | YES | | NULL | Normalized PubMed IDs |
| pmid_issues | json | YES | | NULL | PMID validation issues |
| publish_date | timestamp | YES | | NULL | Publication date |
| posted_date | timestamp | YES | | NULL | Posted date |
| report_date | timestamp | YES | | NULL | Report date |
| report_url | varchar(255) | YES | | NULL | Report URL |
| submission_data | json | NO | | NULL | Raw submission data |
| original_submission_data | json | YES | | NULL | Original unmodified data |
| submission_errors | json | YES | | NULL | Validation errors |
| history | json | YES | | NULL | Change history |
| tags | json | YES | | NULL | Tags |
| is_most_recent | tinyint(1) | NO | | 1 | Is most recent version for this SGC ID |
| is_live | tinyint(1) | NO | MUL | 0 | Is publicly visible (published) |
| status | varchar(50) | YES | MUL | NULL | Status (draft, pending, published, unpublished) |
| action | varchar(20) | YES | MUL | NULL | Action type (new, republish, unpublish) |
| unpublished_at | timestamp | YES | | NULL | Unpublish timestamp |
| origin_state | varchar(50) | YES | | NULL | Original state before changes |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| released_at | timestamp | YES | | NULL | Release timestamp |
| submitted_at | timestamp | YES | | NULL | Submission timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |
| last_edited_by | bigint unsigned | YES | MUL | NULL | Last editor user ID |

### submitters

Stores submitting organization information.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Submitter type |
| curie | varchar(255) | NO | MUL | NULL | CURIE (e.g., "GENCC:000101") |
| name | varchar(255) | NO | | NULL | Organization name |
| description | text | YES | | NULL | Organization description |
| logo | varchar(255) | YES | | NULL | Logo path (legacy) |
| logo_contents | mediumtext | YES | | NULL | Base64-encoded logo image |
| logo_mime_type | varchar(50) | YES | | NULL | Logo MIME type |
| website | varchar(255) | YES | | NULL | Organization website |
| assertion | text | YES | | NULL | Assertion methodology text |
| counts | json | NO | | json_array() | Curation counts |
| activity | json | NO | | json_array() | Activity history |
| contacts | json | NO | | json_array() | Contact information (see below) |
| notes | text | YES | | NULL | Internal notes |
| status | tinyint | NO | MUL | 0 | Status flag |
| member | tinyint(1) | NO | | 0 | Is GenCC member |
| downloadable | tinyint(1) | NO | | 0 | Allows data download |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

**contacts JSON structure:**
```json
{
  "coordinator": "Contact Person Name",
  "title": "Job Title",
  "phone": "123-456-7890",
  "email": "contact@example.org"
}
```

### pubmeds

Stores PubMed article metadata for evidence citations.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| pmid | varchar(255) | NO | MUL | NULL | PubMed ID |
| uid | varchar(255) | NO | | NULL | UID |
| pubdate | varchar(255) | YES | | NULL | Publication date |
| epubdate | varchar(255) | YES | | NULL | Electronic pub date |
| source | varchar(255) | YES | | NULL | Source journal |
| authors | text | YES | | NULL | Author list |
| lastauthor | varchar(255) | YES | | NULL | Last author |
| title | text | YES | | NULL | Article title |
| sorttitle | text | YES | | NULL | Sortable title |
| volume | varchar(255) | YES | | NULL | Volume |
| issue | varchar(255) | YES | | NULL | Issue |
| pages | varchar(255) | YES | | NULL | Pages |
| lang | varchar(255) | YES | | NULL | Language |
| nlmuniqueid | varchar(255) | YES | | NULL | NLM unique ID |
| issn | varchar(255) | YES | | NULL | ISSN |
| essn | varchar(255) | YES | | NULL | Electronic ISSN |
| pubtype | varchar(255) | YES | | NULL | Publication type |
| recordstatus | varchar(255) | YES | | NULL | Record status |
| pubstatus | varchar(255) | YES | | NULL | Publication status |
| articleids | text | YES | | NULL | Article IDs |
| history | text | YES | | NULL | History |
| references | text | YES | | NULL | References |
| attributes | varchar(255) | YES | | NULL | Attributes |
| pmcrefcount | varchar(255) | YES | | NULL | PMC reference count |
| fullfournalname | varchar(255) | YES | | NULL | Full journal name |
| elocationid | varchar(255) | YES | | NULL | Electronic location ID |
| doctype | varchar(255) | YES | | NULL | Document type |
| srccontriblist | text | YES | | NULL | Source contributor list |
| booktitle | varchar(255) | YES | | NULL | Book title |
| medium | varchar(255) | YES | | NULL | Medium |
| edition | varchar(255) | YES | | NULL | Edition |
| publisherlocation | varchar(255) | YES | | NULL | Publisher location |
| publishername | varchar(255) | YES | | NULL | Publisher name |
| srcdate | varchar(255) | YES | | NULL | Source date |
| reportnumber | varchar(255) | YES | | NULL | Report number |
| availablefromurl | varchar(255) | YES | | NULL | Available from URL |
| locationlabel | varchar(255) | YES | | NULL | Location label |
| doccontriblist | text | YES | | NULL | Document contributor list |
| docdate | varchar(255) | YES | | NULL | Document date |
| bookname | varchar(255) | YES | | NULL | Book name |
| chapter | varchar(255) | YES | | NULL | Chapter |
| sortpubdate | varchar(255) | YES | | NULL | Sortable pub date |
| sortfirstauthor | varchar(255) | YES | | NULL | Sortable first author |
| vernaculartitle | text | YES | | NULL | Vernacular title |
| other | text | YES | | NULL | Other data |
| notes | mediumtext | YES | | NULL | Notes |
| status | tinyint | NO | MUL | 0 | Status |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### pubmed_submission

Pivot table linking pubmeds to submissions.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| pubmed_id | bigint unsigned | NO | | NULL | PubMed reference |
| submission_id | bigint unsigned | NO | | NULL | Submission reference |

---

## User & Authentication Tables

### users

Stores user account information.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | User type |
| name | varchar(255) | NO | | NULL | Display name |
| first_name | varchar(255) | YES | | NULL | First name |
| last_name | varchar(255) | YES | | NULL | Last name |
| title | varchar(100) | YES | | NULL | Job title |
| phone | varchar(50) | YES | | NULL | Phone number |
| email | varchar(255) | NO | UNI | NULL | Email address |
| email_verified_at | timestamp | YES | | NULL | Email verification timestamp |
| profile | json | YES | | NULL | Profile data |
| preferences | json | YES | | NULL | User preferences |
| submitter_id | bigint unsigned | YES | | NULL | Primary submitter reference |
| team_id | bigint unsigned | YES | | NULL | Primary team reference |
| clingen_id | varchar(255) | YES | MUL | NULL | ClinGen SSO ID |
| password | varchar(255) | NO | | NULL | Hashed password |
| must_change_password | tinyint(1) | NO | | 0 | Force password change |
| two_factor_secret | text | YES | | NULL | 2FA secret |
| two_factor_recovery_codes | text | YES | | NULL | 2FA recovery codes |
| two_factor_confirmed_at | timestamp | YES | | NULL | 2FA confirmation timestamp |
| remember_token | varchar(100) | YES | | NULL | Remember me token |
| api_token | varchar(255) | YES | MUL | NULL | API token |
| api_token_renewed_at | timestamp | YES | | NULL | API token renewal timestamp |
| device_token | varchar(255) | YES | | NULL | Device token |
| activation_token | varchar(255) | YES | | NULL | Account activation token |
| role | tinyint | NO | | 0 | Legacy role field |
| status | tinyint | NO | | 0 | Account status |
| current_team_id | bigint unsigned | YES | | NULL | Current active team |
| profile_photo_path | varchar(2048) | YES | | NULL | Profile photo path |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### teams

Stores team/organization groupings for users.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| user_id | bigint unsigned | NO | MUL | NULL | Team owner |
| submitter_id | bigint unsigned | YES | MUL | NULL | Associated submitter |
| name | varchar(255) | NO | | NULL | Team name |
| personal_team | tinyint(1) | NO | | NULL | Is personal team |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### submitter_user

Pivot table linking users to submitters with contact flag.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| submitter_id | bigint unsigned | NO | MUL | NULL | Submitter reference |
| user_id | bigint unsigned | NO | MUL | NULL | User reference |
| is_contact | tinyint(1) | NO | | 0 | Is contact person for submitter |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### team_user

Pivot table linking users to teams with role.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| team_id | bigint unsigned | NO | MUL | NULL | Team reference |
| user_id | bigint unsigned | NO | | NULL | User reference |
| role | varchar(255) | YES | | NULL | Role within team |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### team_invitations

Stores pending team invitations.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| team_id | bigint unsigned | NO | MUL | NULL | Team reference |
| email | varchar(255) | NO | | NULL | Invitee email |
| role | varchar(255) | YES | | NULL | Invited role |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### sessions

Stores user session data.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | varchar(255) | NO | PRI | NULL | Session ID |
| user_id | bigint unsigned | YES | MUL | NULL | User reference |
| ip_address | varchar(45) | YES | | NULL | Client IP address |
| user_agent | text | YES | | NULL | Client user agent |
| payload | longtext | NO | | NULL | Session payload |
| last_activity | int | NO | MUL | NULL | Last activity timestamp |

### password_reset_tokens

Stores password reset tokens.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| email | varchar(255) | NO | PRI | NULL | User email |
| token | varchar(255) | NO | | NULL | Reset token |
| created_at | timestamp | YES | | NULL | Creation timestamp |

### personal_access_tokens

Stores Laravel Sanctum personal access tokens.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| tokenable_type | varchar(255) | NO | MUL | NULL | Model type |
| tokenable_id | bigint unsigned | NO | | NULL | Model ID |
| name | varchar(255) | NO | | NULL | Token name |
| token | varchar(64) | NO | UNI | NULL | Hashed token |
| abilities | text | YES | | NULL | Token abilities |
| last_used_at | timestamp | YES | | NULL | Last used timestamp |
| expires_at | timestamp | YES | | NULL | Expiration timestamp |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

---

## Role & Permission Tables

### roles

Stores Spatie permission roles.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| name | varchar(255) | NO | MUL | NULL | Role name |
| guard_name | varchar(255) | NO | | NULL | Guard name |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### permissions

Stores Spatie permission entries.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| name | varchar(255) | NO | MUL | NULL | Permission name |
| guard_name | varchar(255) | NO | | NULL | Guard name |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### model_has_roles

Pivot table assigning roles to models (users).

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| role_id | bigint unsigned | NO | PRI | NULL | Role reference |
| model_type | varchar(255) | NO | PRI | NULL | Model class |
| model_id | bigint unsigned | NO | PRI | NULL | Model ID |

### model_has_permissions

Pivot table assigning permissions directly to models.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| permission_id | bigint unsigned | NO | PRI | NULL | Permission reference |
| model_type | varchar(255) | NO | PRI | NULL | Model class |
| model_id | bigint unsigned | NO | PRI | NULL | Model ID |

### role_has_permissions

Pivot table assigning permissions to roles.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| permission_id | bigint unsigned | NO | PRI | NULL | Permission reference |
| role_id | bigint unsigned | NO | PRI | NULL | Role reference |

---

## Workflow & Processing Tables

### jobs

Stores submission batch jobs for processing.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| slug | varchar(255) | YES | | NULL | URL-friendly slug |
| friendly | varchar(255) | YES | | NULL | Friendly display ID |
| type | tinyint | NO | | 0 | Job type |
| user_id | bigint unsigned | NO | | NULL | Creating user |
| submitter_id | bigint unsigned | YES | | NULL | Submitter reference |
| submission_data | json | NO | | json_array() | Job submission data |
| processed_submission_ids | json | YES | | NULL | Processed submission IDs |
| activity | json | NO | | json_array() | Activity log |
| is_processing | tinyint(1) | NO | | 0 | Currently processing |
| status | varchar(50) | NO | MUL | draft | Status (draft, pending, processing, published) |
| is_most_recent | tinyint(1) | NO | | 1 | Is most recent job |
| is_publishing | tinyint(1) | NO | | 0 | Currently publishing |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| released_at | timestamp | YES | | NULL | Release timestamp |
| submitted_at | timestamp | YES | | NULL | Submission timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### documents

Stores uploaded documents (spreadsheets, etc.).

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Document type |
| user_id | bigint unsigned | NO | | NULL | Uploading user |
| submitter_id | bigint unsigned | NO | | NULL | Submitter reference |
| job_id | bigint unsigned | NO | | NULL | Associated job |
| file_name | varchar(255) | NO | | NULL | Original filename |
| extension | varchar(255) | YES | | NULL | File extension |
| mime_type | varchar(255) | NO | | NULL | MIME type |
| size | int | NO | | NULL | File size in bytes |
| original_path | varchar(255) | YES | | NULL | Original path |
| local_path | varchar(255) | YES | | NULL | Local storage path |
| file_contents | longtext | YES | | NULL | Base64 file contents |
| disk | varchar(255) | NO | | local | Storage disk |
| status | tinyint | NO | | 1 | Status |
| processing_errors | json | YES | | NULL | Processing errors |
| upload_state | varchar(255) | YES | | NULL | Upload state |
| processed_submissions | int | YES | | NULL | Processed count |
| total_submissions | int | YES | | NULL | Total submissions |
| upload_started_at | timestamp | YES | | NULL | Upload start time |
| upload_completed_at | timestamp | YES | | NULL | Upload completion time |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### actions

Stores action queue entries for async processing.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 0 | Action type |
| user_id | bigint unsigned | NO | | NULL | User reference |
| submitter_id | bigint unsigned | NO | | NULL | Submitter reference |
| job_id | bigint unsigned | YES | | NULL | Job reference |
| submission_id | bigint unsigned | YES | | NULL | Submission reference |
| local_key | varchar(255) | YES | | NULL | Local key |
| command | json | NO | | NULL | Command data |
| status | tinyint | NO | MUL | 1 | Status |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### releases

Stores release/publication batches.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| slug | varchar(255) | NO | UNI | NULL | Release slug |
| released_at | datetime | NO | | NULL | Release timestamp |
| release_notes_file | varchar(255) | YES | | NULL | Release notes file |
| submissions_csv_file | varchar(255) | YES | | NULL | Submissions CSV file |
| user_id | bigint unsigned | YES | MUL | NULL | Publishing user |
| new_count | int | NO | | 0 | New submissions count |
| republish_count | int | NO | | 0 | Republished count |
| unpublish_count | int | NO | | 0 | Unpublished count |
| failed_count | int | NO | | 0 | Failed count |
| total_count | int | NO | | 0 | Total count |
| jobs_processed | json | YES | | NULL | Processed job IDs |
| errors | json | YES | | NULL | Processing errors |
| by_submitter | json | YES | | NULL | Stats by submitter |
| cumulative_stats | json | YES | | NULL | Cumulative statistics |
| duration_seconds | int | YES | | NULL | Processing duration |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### workers

Laravel queue worker jobs table.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| queue | varchar(255) | NO | MUL | NULL | Queue name |
| payload | longtext | NO | | NULL | Job payload |
| attempts | tinyint unsigned | NO | | NULL | Attempt count |
| reserved_at | int unsigned | YES | | NULL | Reserved timestamp |
| available_at | int unsigned | NO | | NULL | Available timestamp |
| created_at | int unsigned | NO | | NULL | Creation timestamp |

### failed_jobs

Stores failed Laravel queue jobs.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| uuid | varchar(255) | NO | UNI | NULL | Job UUID |
| connection | text | NO | | NULL | Queue connection |
| queue | text | NO | | NULL | Queue name |
| payload | longtext | NO | | NULL | Job payload |
| exception | longtext | NO | | NULL | Exception trace |
| failed_at | timestamp | NO | | CURRENT_TIMESTAMP | Failure timestamp |

---

## Supporting Tables

### aliases

Stores value aliases for normalization (gene symbols, disease names, etc.).

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 1 | Alias type |
| subtype | tinyint | NO | | 0 | Alias subtype |
| submitter_id | bigint unsigned | NO | | NULL | Submitter reference |
| user_id | bigint unsigned | NO | | NULL | Creating user |
| key | varchar(255) | NO | | NULL | Alias key (input value) |
| value | varchar(255) | YES | | NULL | Canonical value |
| shared | tinyint | NO | | 0 | Shared across submitters |
| status | tinyint | NO | | 0 | Status |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### notifications

Stores user notifications.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| user_id | bigint unsigned | YES | MUL | NULL | User reference |
| submitter_id | bigint unsigned | YES | MUL | NULL | Submitter reference |
| ref | varchar(255) | YES | | NULL | Reference |
| uuid | varchar(255) | YES | | NULL | UUID |
| label | varchar(255) | YES | | NULL | Label |
| message | text | YES | | NULL | Message content |
| meta | text | YES | | NULL | Metadata |
| count | int | NO | | 0 | Count |
| type | int | NO | | 0 | Notification type |
| running | int | NO | | 0 | Running flag |
| output | int | NO | | 0 | Output flag |
| status | int | NO | | 0 | Status |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### metrics

Stores system metrics and statistics.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| ident | varchar(255) | NO | UNI | NULL | Unique identifier (UUID) |
| type | tinyint | NO | | 1 | Metric type |
| jobs_queued | json | YES | | NULL | Jobs queued stats |
| jobs_processing | json | YES | | NULL | Jobs processing stats |
| jobs_errors | json | YES | | NULL | Jobs errors stats |
| jobs_window | json | YES | | NULL | Jobs window stats |
| jobs_complete | json | YES | | NULL | Jobs complete stats |
| jobs_removed | json | YES | | NULL | Jobs removed stats |
| submissions_queued | json | YES | | NULL | Submissions queued stats |
| submissions_processing | json | YES | | NULL | Submissions processing stats |
| submissions_errors | json | YES | | NULL | Submissions errors stats |
| submissions_window | json | YES | | NULL | Submissions window stats |
| submissions_published | json | YES | | NULL | Submissions published stats |
| submissions_removed | json | YES | | NULL | Submissions removed stats |
| classifications_definitive | json | YES | | NULL | Definitive classification count |
| classifications_strong | json | YES | | NULL | Strong classification count |
| classifications_moderate | json | YES | | NULL | Moderate classification count |
| classifications_supportive | json | YES | | NULL | Supportive classification count |
| classifications_limited | json | YES | | NULL | Limited classification count |
| classifications_disputed | json | YES | | NULL | Disputed classification count |
| classifications_refuted | json | YES | | NULL | Refuted classification count |
| classifications_animal | json | YES | | NULL | Animal model classification count |
| classifications_nodisease | json | YES | | NULL | No known disease classification count |
| status | tinyint | NO | | 1 | Status |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |
| deleted_at | timestamp | YES | | NULL | Soft delete timestamp |

### admin_logs

Stores admin operation logs.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| operation | varchar(255) | NO | MUL | NULL | Operation name |
| user_id | bigint unsigned | NO | MUL | NULL | User reference |
| success | tinyint(1) | NO | | 0 | Success flag |
| exit_code | int | YES | | NULL | Exit code |
| output | text | YES | | NULL | Command output |
| summary | text | YES | | NULL | Operation summary |
| executed_at | timestamp | NO | | NULL | Execution timestamp |
| duration_seconds | int | YES | | NULL | Execution duration |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### settings

Stores application settings as key-value pairs.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | int unsigned | NO | PRI | NULL | Primary key |
| key | varchar(255) | NO | MUL | NULL | Setting key |
| value | text | NO | | NULL | Setting value |

### sgc_sequences

Stores SGC ID sequence numbers.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Auto-increment sequence |
| created_at | timestamp | NO | | CURRENT_TIMESTAMP | Creation timestamp |

### static_file_headers

Caches HTTP headers for static files.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| file_identifier | varchar(255) | NO | MUL | NULL | File identifier |
| content_length | varchar(255) | YES | | NULL | Content-Length header |
| last_modified | varchar(255) | YES | | NULL | Last-Modified header |
| etag | varchar(255) | YES | | NULL | ETag header |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### user_information

Stores additional user information (legacy).

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | bigint unsigned | NO | PRI | NULL | Primary key |
| first_name | varchar(50) | NO | | NULL | First name |
| last_name | varchar(50) | NO | | NULL | Last name |
| address | varchar(250) | NO | | NULL | Street address |
| city | varchar(50) | NO | | NULL | City |
| state | varchar(50) | NO | | NULL | State |
| zip | varchar(20) | NO | | NULL | ZIP code |
| created_at | timestamp | YES | | NULL | Creation timestamp |
| updated_at | timestamp | YES | | NULL | Update timestamp |

### migrations

Laravel migration tracking table.

| Column | Type | Null | Key | Default | Description |
|--------|------|------|-----|---------|-------------|
| id | int unsigned | NO | PRI | NULL | Primary key |
| migration | varchar(255) | NO | | NULL | Migration name |
| batch | int | NO | | NULL | Batch number |

---

## Key Relationships

```
submitters 1--* submissions
submitters 1--* jobs
submitters *--* users (via submitter_user)

genes 1--* submissions
diseases 1--* submissions (disease_id - MONDO normalized)
diseases 1--* submissions (original_disease_id - as submitted)
classifications 1--* submissions
inheritances 1--* submissions
mechanisms 1--* submissions

jobs 1--* submissions
jobs 1--* documents
jobs 1--* actions

users 1--* jobs
users 1--* submissions
users *--* teams (via team_user)
users *--* roles (via model_has_roles)
users *--* permissions (via model_has_permissions)

teams *--1 submitters
roles *--* permissions (via role_has_permissions)

submissions *--* pubmeds (via pubmed_submission)
```

---

## Status Codes

### Submission Status
- `draft` - Initial state, not yet submitted
- `pending` - Submitted, awaiting processing
- `published` - Published and publicly visible
- `unpublished` - Explicitly unpublished/retracted

### Disease Status
- `0` - Active
- `8` - Deprecated (STATUS_DEPRECATED)

### Disease Type
- `1` - MONDO (TYPE_MONDO)
- `2` - OMIM (TYPE_OMIM)
- `3` - Orphanet (TYPE_ORPHA)
