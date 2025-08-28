# GenCC Database Entity Relationship Diagram

## Visual Database Schema

### Core Entity Relationships
```
                          ┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┐
                          │                                                    SUBMISSIONS                                                      │
                          │                                               (Central Hub Table)                                                │
                          │                                                                                                                     │
                          │  ┌─────────────────────────────────────────────────────────────────────────────────────────────────────────────┐  │
                          │  │  id (PK)                    status                    submitted_as_submission_id                             │  │
                          │  │  uuid                       workspace                 submitted_as_hgnc_id                                    │  │
                          │  │  gene_id (FK)               order                     submitted_as_hgnc_symbol                                │  │
                          │  │  disease_id (FK)            submitted_run_date        submitted_as_disease_id                                 │  │
                          │  │  disease_original_id (FK)   from_submission_file_name submitted_as_disease_name                              │  │
                          │  │  classification_id (FK)     from_submission_file_id   submitted_as_moi_id                                     │  │
                          │  │  moi_id (FK)                private_notes             submitted_as_moi_name                                   │  │
                          │  │  submitter_id (FK)          created_at                submitted_as_submitter_id                               │  │
                          │  │  trio_id (FK)               updated_at                submitted_as_submitter_name                             │  │
                          │  │                                                       submitted_as_classification_id                         │  │
                          │  │                                                       submitted_as_classification_name                       │  │
                          │  │                                                       submitted_as_public_report_url                         │  │
                          │  │                                                       submitted_as_notes                                      │  │
                          │  │                                                       submitted_as_date                                       │  │
                          │  │                                                       submitted_as_pmids                                      │  │
                          │  │                                                       submitted_as_assertion_criteria_url                    │  │
                          │  └─────────────────────────────────────────────────────────────────────────────────────────────────────────────┘  │
                          └─────────────────────────────────────────────────────────────────────────────────────────────────────────────────────┘
                                                                                    │
                                                                                    │
                ┌─────────────────────────────────────────────────┬─────────────────────────────────────────────────────────────────────┐
                │                                                 │                                                                     │
                │                                                 │                                                                     │
                ▼                                                 ▼                                                                     ▼
    ┌─────────────────────────────────────────────┐  ┌─────────────────────────────────────────────┐  ┌─────────────────────────────────────────────┐
    │                 GENES                       │  │                DISEASES                     │  │              SUBMITTERS                     │
    │                                             │  │                                             │  │                                             │
    │  ┌───────────────────────────────────────┐  │  │  ┌───────────────────────────────────────┐  │  │  ┌───────────────────────────────────────┐  │
    │  │  id (PK)                              │  │  │  │  id (PK)                              │  │  │  │  id (PK)                              │  │
    │  │  uuid                                 │  │  │  │  uuid                                 │  │  │  │  uuid                                 │  │
    │  │  curie                                │  │  │  │  curie                                │  │  │  │  curie                                │  │
    │  │  title                                │  │  │  │  title                                │  │  │  │  title                                │  │
    │  │  hgnc_id                              │  │  │  │  description                          │  │  │  │  website                              │  │
    │  │  symbol                               │  │  │  │  type                                 │  │  │  │  path_logo                            │  │
    │  │  name                                 │  │  │  │  status                               │  │  │  │  text_descriptions                    │  │
    │  │  location                             │  │  │  │  synonyms_exact                       │  │  │  │  text_contact                         │  │
    │  │  chr                                  │  │  │  │  synonyms_related                     │  │  │  │  text_assertions                      │  │
    │  │  grch37 (JSON)                       │  │  │  │  xrefs                                │  │  │  │  text_disclaimer                      │  │
    │  │  grch38 (JSON)                       │  │  │  │  meta_parents                         │  │  │  │  downloadable                         │  │
    │  │  chm13 (JSON)                        │  │  │  │  curations_definitive                 │  │  │  │  status                               │  │
    │  │  prev_symbol (JSON)                  │  │  │  │  curations_strong                     │  │  │  │  curations_definitive                 │  │
    │  │  alias_symbol (JSON)                 │  │  │  │  curations_moderate                   │  │  │  │  curations_strong                     │  │
    │  │  omim_id (JSON)                      │  │  │  │  curations_supportive                 │  │  │  │  curations_moderate                   │  │
    │  │  mane_select (JSON)                  │  │  │  │  curations_limited                    │  │  │  │  curations_supportive                 │  │
    │  │  mane_plus (JSON)                    │  │  │  │  curations_disputed                   │  │  │  │  curations_limited                    │  │
    │  │  loeuf                               │  │  │  │  curations_refuted                    │  │  │  │  curations_disputed                   │  │
    │  │  pli                                 │  │  │  │  curations_animal                     │  │  │  │  curations_refuted                    │  │
    │  │  hi                                  │  │  │  │  curations_noknown                    │  │  │  │  curations_animal                     │  │
    │  │  haplo                               │  │  │  │  count_submissions                    │  │  │  │  curations_noknown                    │  │
    │  │  triplo                              │  │  │  │  count_unique_genes                   │  │  │  │  count_submissions                    │  │
    │  │  entrez_id                           │  │  │  │  count_unique_submitters              │  │  │  │  count_unique_diseases                │  │
    │  │  uniprot_id                          │  │  │  │  created_at                           │  │  │  │  count_unique_genes                   │  │
    │  │  ensembl_gene_id                     │  │  │  │  updated_at                           │  │  │  │  created_at                           │  │
    │  │  ucsc_id                             │  │  │  │                                       │  │  │  │  updated_at                           │  │
    │  │  is_morbid                           │  │  │  │                                       │  │  │  │                                       │  │
    │  │  is_acmgsf3                          │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_definitive                │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_strong                    │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_moderate                  │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_supportive                │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_limited                   │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_disputed                  │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_refuted                   │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_animal                    │  │  │  │                                       │  │  │  │                                       │  │
    │  │  curations_noknown                   │  │  │  │                                       │  │  │  │                                       │  │
    │  │  count_submissions                   │  │  │  │                                       │  │  │  │                                       │  │
    │  │  count_unique_diseases               │  │  │  │                                       │  │  │  │                                       │  │
    │  │  count_unique_submitters             │  │  │  │                                       │  │  │  │                                       │  │
    │  │  notes                               │  │  │  │                                       │  │  │  │                                       │  │
    │  │  created_at                          │  │  │  │                                       │  │  │  │                                       │  │
    │  │  updated_at                          │  │  │  │                                       │  │  │  │                                       │  │
    │  │  deleted_at                          │  │  │  │                                       │  │  │  │                                       │  │
    │  └───────────────────────────────────────┘  │  │  └───────────────────────────────────────┘  │  │  └───────────────────────────────────────┘  │
    │                                             │  │                                             │  │                                             │
    │          hasMany                            │  │          belongsToMany                      │  │          hasMany                            │
    │         submissions                         │  │         submissions                         │  │         submissions                         │
    │                                             │  │                                             │  │         submission_files                    │
    └─────────────────────────────────────────────┘  └─────────────────────────────────────────────┘  └─────────────────────────────────────────────┘
                                                                                │
                                                                                │
                                                                                │ (Self-referential relationships)
                                                                                │
                                                                                ▼
                                                                  ┌─────────────────────────────────────────────┐
                                                                  │            DISEASE_DISEASE                  │
                                                                  │         (Pivot Table)                      │
                                                                  │                                             │
                                                                  │  ┌───────────────────────────────────────┐  │
                                                                  │  │  parent_id (FK → diseases.id)        │  │
                                                                  │  │  child_id (FK → diseases.id)         │  │
                                                                  │  │  disease_id (FK → diseases.id)       │  │
                                                                  │  │  xref_id (FK → diseases.id)          │  │
                                                                  │  │  synonym_id (FK → diseases.id)       │  │
                                                                  │  │  equiv_id (FK → diseases.id)         │  │
                                                                  │  │  type                                 │  │
                                                                  │  │  predicate                            │  │
                                                                  │  │  ontology                             │  │
                                                                  │  │  created_at                           │  │
                                                                  │  │  updated_at                           │  │
                                                                  │  └───────────────────────────────────────┘  │
                                                                  └─────────────────────────────────────────────┘


        ┌─────────────────────────────────────────────┐         ┌─────────────────────────────────────────────┐
        │            CLASSIFICATIONS                  │         │            INHERITANCES                     │
        │                                             │         │                                             │
        │  ┌───────────────────────────────────────┐  │         │  ┌───────────────────────────────────────┐  │
        │  │  id (PK)                              │  │         │  │  id (PK)                              │  │
        │  │  uuid                                 │  │         │  │  uuid                                 │  │
        │  │  curie                                │  │         │  │  curie                                │  │
        │  │  title                                │  │         │  │  title                                │  │
        │  │  description                          │  │         │  │  description                          │  │
        │  │  info_text                            │  │         │  │  info_text                            │  │
        │  │  abbreviation                         │  │         │  │  abbreviation                         │  │
        │  │  hex_color                            │  │         │  │  hex_color                            │  │
        │  │  css_class                            │  │         │  │  css_class                            │  │
        │  │  slug                                 │  │         │  │  status                               │  │
        │  │  href                                 │  │         │  │  created_at                           │  │
        │  │  order                                │  │         │  │  updated_at                           │  │
        │  │  status                               │  │         │  │                                       │  │
        │  │  created_at                           │  │         │  │                                       │  │
        │  │  updated_at                           │  │         │  │                                       │  │
        │  └───────────────────────────────────────┘  │         │  └───────────────────────────────────────┘  │
        │                                             │         │                                             │
        │          hasMany                            │         │          hasMany                            │
        │         submissions                         │         │         submissions                         │
        │                                             │         │                                             │
        └─────────────────────────────────────────────┘         └─────────────────────────────────────────────┘
                                │                                                         │
                                │                                                         │
                                └─────────────────────────┬───────────────────────────────┘
                                                          │
                                                          ▼
                                                    (Related to SUBMISSIONS)


        ┌─────────────────────────────────────────────┐         ┌─────────────────────────────────────────────┐
        │            SUBMISSION_FILES                 │         │                TRIOS                        │
        │                                             │         │                                             │
        │  ┌───────────────────────────────────────┐  │         │  ┌───────────────────────────────────────┐  │
        │  │  id (PK)                              │  │         │  │  id (PK)                              │  │
        │  │  uuid                                 │  │         │  │  uuid                                 │  │
        │  │  submitter_id (FK)                    │  │         │  │  gene_id (FK)                         │  │
        │  │  user_id (FK)                         │  │         │  │  disease_id (FK)                      │  │
        │  │  created_by_user (FK)                 │  │         │  │  moi_id (FK)                          │  │
        │  │  name                                 │  │         │  │  classification_id (FK)               │  │
        │  │  body                                 │  │         │  │  title                                │  │
        │  │  path                                 │  │         │  │  status                               │  │
        │  │  file_name                            │  │         │  │  created_at                           │  │
        │  │  file_name_original                   │  │         │  │  updated_at                           │  │
        │  │  file_type                            │  │         │  │                                       │  │
        │  │  file_type_original                   │  │         │  │                                       │  │
        │  │  file_size                            │  │         │  │                                       │  │
        │  │  file_size_human                      │  │         │  │                                       │  │
        │  │  log                                  │  │         │  │                                       │  │
        │  │  status                               │  │         │  │                                       │  │
        │  │  submitted_run_date                   │  │         │  │                                       │  │
        │  │  processed_last_at                    │  │         │  │                                       │  │
        │  │  private_notes                        │  │         │  │                                       │  │
        │  │  created_at                           │  │         │  │                                       │  │
        │  │  updated_at                           │  │         │  │                                       │  │
        │  │  deleted_at                           │  │         │  │                                       │  │
        │  └───────────────────────────────────────┘  │         │  └───────────────────────────────────────┘  │
        │                                             │         │                                             │
        │          belongsTo                          │         │          belongsTo                          │
        │         submitter                           │         │         gene, disease,                      │
        │         user                                │         │         classification,                     │
        │         created_by                          │         │         inheritance                         │
        │                                             │         │                                             │
        └─────────────────────────────────────────────┘         └─────────────────────────────────────────────┘


        ┌─────────────────────────────────────────────┐         ┌─────────────────────────────────────────────┐
        │               CONFLICTS                     │         │               PUBLICATIONS                  │
        │                                             │         │                                             │
        │  ┌───────────────────────────────────────┐  │         │  ┌───────────────────────────────────────┐  │
        │  │  id (PK)                              │  │         │  │  id (PK)                              │  │
        │  │  ident                                │  │         │  │  uuid                                 │  │
        │  │  hgnc_id                              │  │         │  │  pubmedid                             │  │
        │  │  gene_symbol                          │  │         │  │  title                                │  │
        │  │  mondo_id                             │  │         │  │  description                          │  │
        │  │  disease                              │  │         │  │  created_at                           │  │
        │  │  moi                                  │  │         │  │  updated_at                           │  │
        │  │  weak                                 │  │         │  │                                       │  │
        │  │  strong                               │  │         │  │                                       │  │
        │  │  submitters (JSON)                    │  │         │  │                                       │  │
        │  │  created_at                           │  │         │  │                                       │  │
        │  │  updated_at                           │  │         │  │                                       │  │
        │  └───────────────────────────────────────┘  │         │  └───────────────────────────────────────┘  │
        │                                             │         │                                             │
        │          (Track conflicts between           │         │          belongsToMany                     │
        │           submitters for same               │         │         submissions                         │
        │           gene-disease pairs)               │         │                                             │
        │                                             │         │                                             │
        └─────────────────────────────────────────────┘         └─────────────────────────────────────────────┘
                                                                                         │
                                                                                         │
                                                                                         ▼
                                                                 ┌─────────────────────────────────────────────┐
                                                                 │         PUBLICATION_SUBMISSION              │
                                                                 │              (Pivot Table)                  │
                                                                 │                                             │
                                                                 │  ┌───────────────────────────────────────┐  │
                                                                 │  │  publication_id (FK)                  │  │
                                                                 │  │  submission_id (FK)                   │  │
                                                                 │  │  created_at                           │  │
                                                                 │  │  updated_at                           │  │
                                                                 │  └───────────────────────────────────────┘  │
                                                                 └─────────────────────────────────────────────┘


        ┌─────────────────────────────────────────────┐         ┌─────────────────────────────────────────────┐
        │                USERS                        │         │            NOTIFICATIONS                    │
        │                                             │         │                                             │
        │  ┌───────────────────────────────────────┐  │         │  ┌───────────────────────────────────────┐  │
        │  │  id (PK)                              │  │         │  │  id (PK)                              │  │
        │  │  name                                 │  │         │  │  user_id (FK)                         │  │
        │  │  email                                │  │         │  │  submitter_id (FK)                    │  │
        │  │  email_verified_at                    │  │         │  │  ref                                  │  │
        │  │  password                             │  │         │  │  uuid                                 │  │
        │  │  uuid                                 │  │         │  │  label                                │  │
        │  │  handle                               │  │         │  │  message                              │  │
        │  │  admin                                │  │         │  │  meta                                 │  │
        │  │  type                                 │  │         │  │  count                                │  │
        │  │  status                               │  │         │  │  type                                 │  │
        │  │  remember_token                       │  │         │  │  running                              │  │
        │  │  created_at                           │  │         │  │  output                               │  │
        │  │  updated_at                           │  │         │  │  status                               │  │
        │  └───────────────────────────────────────┘  │         │  │  created_at                           │  │
        │                                             │         │  │  updated_at                           │  │
        │          (Referenced by                     │         │  └───────────────────────────────────────┘  │
        │           submission_files)                 │         │                                             │
        │                                             │         │          belongsTo                          │
        │                                             │         │         user, submitter                     │
        │                                             │         │                                             │
        └─────────────────────────────────────────────┘         └─────────────────────────────────────────────┘


        ┌─────────────────────────────────────────────┐         ┌─────────────────────────────────────────────┐
        │                TERMS                        │         │              MORBIDS                        │
        │                                             │         │                                             │
        │  ┌───────────────────────────────────────┐  │         │  ┌───────────────────────────────────────┐  │
        │  │  id (PK)                              │  │         │  │  id (PK)                              │  │
        │  │  ident                                │  │         │  │  ident                                │  │
        │  │  type                                 │  │         │  │  phenotype                            │  │
        │  │  name                                 │  │         │  │  secondary                            │  │
        │  │  value                                │  │         │  │  pheno_omim                           │  │
        │  │  alias                                │  │         │  │  mim                                  │  │
        │  │  weight                               │  │         │  │  mapkey                               │  │
        │  │  curated                              │  │         │  │  disputing                            │  │
        │  │  status                               │  │         │  │  nondisease                           │  │
        │  │  created_at                           │  │         │  │  mutations                            │  │
        │  │  updated_at                           │  │         │  │  genes (JSON)                         │  │
        │  │  deleted_at                           │  │         │  │  cyto                                 │  │
        │  └───────────────────────────────────────┘  │         │  │  type                                 │  │
        │                                             │         │  │  status                               │  │
        │          (Generic term storage)             │         │  │  created_at                           │  │
        │                                             │         │  │  updated_at                           │  │
        │                                             │         │  │  deleted_at                           │  │
        │                                             │         │  └───────────────────────────────────────┘  │
        │                                             │         │                                             │
        │                                             │         │          (OMIM morbid map data)            │
        │                                             │         │                                             │
        └─────────────────────────────────────────────┘         └─────────────────────────────────────────────┘


        ┌─────────────────────────────────────────────┐         ┌─────────────────────────────────────────────┐
        │               SETTINGS                      │         │          SYSTEM TABLES                      │
        │                                             │         │                                             │
        │  ┌───────────────────────────────────────┐  │         │  ┌───────────────────────────────────────┐  │
        │  │  id (PK)                              │  │         │  │  password_resets                      │  │
        │  │  {key_column}                         │  │         │  │  - email                              │  │
        │  │  {value_column}                       │  │         │  │  - token                              │  │
        │  └───────────────────────────────────────┘  │         │  │  - created_at                         │  │
        │                                             │         │  │                                       │  │
        │          (Dynamic configuration)            │         │  │  failed_jobs                          │  │
        │                                             │         │  │  - id, connection, queue              │  │
        │                                             │         │  │  - payload, exception                 │  │
        │                                             │         │  │  - failed_at                          │  │
        │                                             │         │  │                                       │  │
        │                                             │         │  │  import_genes                         │  │
        │                                             │         │  │  - id, created_at, updated_at         │  │
        │                                             │         │  └───────────────────────────────────────┘  │
        │                                             │         │                                             │
        └─────────────────────────────────────────────┘         └─────────────────────────────────────────────┘
```

## Pivot Table Details

### disease_submission
```
┌─────────────────────────────────────────────────┐
│              DISEASE_SUBMISSION                 │
│               (Pivot Table)                     │
│                                                 │
│  ┌───────────────────────────────────────────┐  │
│  │  disease_id (FK → diseases.id)            │  │
│  │  submission_id (FK → submissions.id)      │  │
│  │  type                                     │  │
│  │  ontology                                 │  │
│  │  created_at                               │  │
│  │  updated_at                               │  │
│  └───────────────────────────────────────────┘  │
│                                                 │
│  (Links diseases to submissions with metadata) │
└─────────────────────────────────────────────────┘
```

### submission_trio
```
┌─────────────────────────────────────────────────┐
│              SUBMISSION_TRIO                    │
│               (Pivot Table)                     │
│                                                 │
│  ┌───────────────────────────────────────────┐  │
│  │  submission_id (FK → submissions.id)      │  │
│  │  trio_id (FK → trios.id)                  │  │
│  │  created_at                               │  │
│  │  updated_at                               │  │
│  └───────────────────────────────────────────┘  │
│                                                 │
│  (Links submissions to validated trios)        │
└─────────────────────────────────────────────────┘
```

## Key Relationship Patterns

### 1. Hub-and-Spoke Pattern
- **SUBMISSIONS** acts as the central hub
- Connected to all major entities: genes, diseases, submitters, classifications, inheritances
- Preserves audit trail with "submitted_as_" fields

### 2. Hierarchical Self-References
- **DISEASES** has complex self-referential relationships
- Supports parent-child, synonyms, cross-references, equivalents
- Rich metadata in pivot table (type, predicate, ontology)

### 3. Many-to-Many Relationships
- **Disease ↔ Submission**: Multiple diseases per submission
- **Publication ↔ Submission**: Literature evidence linking
- **Disease ↔ Disease**: Complex ontology relationships

### 4. Reference Data Pattern
- **CLASSIFICATIONS**: 10 standardized evidence levels
- **INHERITANCES**: HP ontology-based inheritance patterns
- **SUBMITTERS**: Organizations contributing data

### 5. File Management Pattern
- **SUBMISSION_FILES**: Bulk file uploads
- Links to submitters and users
- Tracks processing status and metadata

### 6. Conflict Resolution Pattern
- **CONFLICTS**: Tracks disagreements between submitters
- **TRIOS**: Validated gene-disease-inheritance combinations
- Supports evidence-based curation workflow

## Data Flow Summary

1. **Submitters** upload **SubmissionFiles**
2. **Submissions** are created linking **Genes** to **Diseases**
3. **Classifications** and **Inheritances** provide evidence context
4. **Publications** provide literature support
5. **Trios** represent validated relationships
6. **Conflicts** track disagreements for resolution
7. **Diseases** are linked hierarchically through self-references
8. **Users** manage the system with role-based access
9. **Notifications** track system processes

This schema supports a comprehensive genetic curation platform with sophisticated relationship management, conflict resolution, and audit trail capabilities.