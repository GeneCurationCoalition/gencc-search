# GenCC Database Schema Documentation

## Overview

The Gene Curation Coalition (GenCC) database is a comprehensive system for managing gene-disease relationships, evidence classifications, and submissions from multiple organizations. This schema supports a collaborative curation platform where genetic testing labs and research organizations contribute evidence for gene-disease associations.

## Core Entity Relationships

### Primary Data Flow
```
Submitters → SubmissionFiles → Submissions → [Gene + Disease + Classification + Inheritance]
     ↓              ↓              ↓
  Trios  ←  Conflicts  ←  Publications
```

## Table Structures

### 1. Core Entity Tables

#### **genes** (Primary genetic entities)
- **Purpose**: Central repository for gene information with extensive external ID mappings
- **Key Fields**:
  - `id` (PK), `uuid`, `curie`, `hgnc_id`, `symbol`, `name`
  - `location` (cytogenetic band), `chr`, genomic coordinates for multiple builds
  - `grch37`, `grch38`, `chm13` (JSON) - Genomic coordinates
  - `prev_symbol`, `alias_symbol`, `omim_id` (JSON arrays)
  - `mane_select`, `mane_plus` (JSON) - MANE transcript data
  - `loeuf`, `pli`, `hi`, `haplo`, `triplo` - Constraint scores
  - Multiple curation counters by classification level
  - `count_submissions`, `count_unique_diseases`, `count_unique_submitters`
- **Relationships**: One-to-many with submissions
- **Features**: Soft deletes, extensive indexing, JSON casting

#### **diseases** (Disease entities with ontology support)
- **Purpose**: Manages disease information with hierarchical relationships
- **Key Fields**:
  - `id` (PK), `uuid`, `curie`, `title`, `description`
  - `type` (ontology source), `synonyms_exact`, `synonyms_related`
  - `xrefs` (cross-references), `meta_parents`
  - Multiple curation counters by classification level
  - `count_submissions`, `count_unique_genes`, `count_unique_submitters`
- **Relationships**: Complex many-to-many with self and submissions
- **Features**: Supports MONDO, OMIM, Orphanet ontologies

#### **submissions** (Central curation entities)
- **Purpose**: Links genes to diseases with evidence levels and submitter information
- **Key Fields**:
  - `id` (PK), `uuid`, `status`, `workspace`, `order`
  - Foreign keys: `gene_id`, `disease_id`, `disease_original_id`, `classification_id`, `moi_id`, `submitter_id`, `trio_id`
  - Original submission data: `submitted_as_*` fields (preserves raw submission data)
  - `submitted_run_date`, `from_submission_file_name`, `private_notes`
- **Relationships**: Belongs to gene, disease, classification, inheritance, submitter
- **Features**: Eager loading, audit trail through "submitted_as" fields

#### **submitters** (Organizations submitting curations)
- **Purpose**: Manages submitting organizations and their metadata
- **Key Fields**:
  - `id` (PK), `uuid`, `curie`, `title`, `website`, `path_logo`
  - `text_descriptions`, `text_contact`, `text_assertions`, `text_disclaimer`
  - `downloadable` (data sharing permissions)
  - Multiple curation counters by classification level
- **Relationships**: One-to-many with submissions and submission_files
- **Features**: Rich metadata for organization profiles

### 2. Reference Data Tables

#### **classifications** (Evidence strength levels)
- **Purpose**: Standardized classification system for gene-disease relationships
- **Key Fields**:
  - `id` (PK), `uuid`, `curie`, `title`, `description`
  - `abbreviation`, `hex_color`, `css_class`, `slug`, `href`
  - `order` (display hierarchy)
- **Standard Values**: Definitive, Strong, Moderate, Supportive, Limited, Disputed, Refuted, Animal Model Only, No Known Disease
- **Features**: UI-focused with styling and ordering

#### **inheritances** (Mode of inheritance patterns)
- **Purpose**: Standardized inheritance patterns based on Human Phenotype Ontology
- **Key Fields**:
  - `id` (PK), `uuid`, `curie`, `title`, `description`
  - `abbreviation`, `hex_color`, `css_class`
- **Standard Values**: Autosomal dominant/recessive, X-linked, mitochondrial, multifactorial, etc.
- **Features**: HP ontology-based standardization

### 3. Junction/Pivot Tables

#### **disease_disease** (Disease relationships)
- **Purpose**: Complex disease ontology relationships
- **Key Fields**:
  - `parent_id`, `child_id` (hierarchical relationships)
  - `disease_id`, `xref_id` (cross-references)
  - `synonym_id`, `equiv_id` (synonyms and equivalents)
  - `type`, `predicate`, `ontology` (relationship metadata)
- **Features**: Supports multiple relationship types with rich metadata

#### **disease_submission** (Disease-submission links)
- **Purpose**: Many-to-many relationship between diseases and submissions
- **Key Fields**: `disease_id`, `submission_id`, `type`, `ontology`
- **Features**: Supports multiple diseases per submission

#### **publication_submission** (Literature evidence)
- **Purpose**: Links publications to submissions for evidence tracking
- **Key Fields**: `publication_id`, `submission_id`

#### **submission_trio** (Trio relationships)
- **Purpose**: Links submissions to validated gene-disease-inheritance trios
- **Key Fields**: `submission_id`, `trio_id`

### 4. File Management Tables

#### **submission_files** (File uploads)
- **Purpose**: Manages bulk submission files from organizations
- **Key Fields**:
  - `id` (PK), `uuid`, `submitter_id`, `user_id`, `created_by_user`
  - `name`, `body`, `path`, file metadata fields
  - `submitted_run_date`, `processed_last_at`, `private_notes`
  - `status` (processing state)
- **Relationships**: Belongs to submitter and user
- **Features**: Soft deletes, processing workflow tracking

### 5. Specialized Tables

#### **trios** (Validated gene-disease-inheritance combinations)
- **Purpose**: Represents curated and validated gene-disease-inheritance relationships
- **Key Fields**:
  - `id` (PK), `uuid`, `title`, `status`
  - Foreign keys: `gene_id`, `disease_id`, `moi_id`, `classification_id`
- **Features**: Eager loading, represents consensus curations

#### **conflicts** (Conflicting curations)
- **Purpose**: Tracks disagreements between submitters for same gene-disease pairs
- **Key Fields**:
  - `id` (PK), `ident`, `hgnc_id`, `gene_symbol`, `mondo_id`, `disease`, `moi`
  - `weak`, `strong` (evidence strength counts)
  - `submitters` (JSON array of conflicting submitters)
- **Features**: Conflict detection and resolution support

#### **morbids** (OMIM morbid map data)
- **Purpose**: Stores OMIM morbid map information for gene-disease relationships
- **Key Fields**:
  - `id` (PK), `ident`, `phenotype`, `secondary`, `pheno_omim`, `mim`
  - `genes` (JSON array), `cyto`, `type`, `status`
- **Features**: Soft deletes, JSON casting for gene arrays

### 6. System Tables

#### **users** (System authentication)
- **Purpose**: User accounts for submitters and administrators
- **Key Fields**:
  - `id` (PK), `name`, `email`, `password`, `uuid`, `handle`
  - `admin`, `type`, `status` (role and permission flags)
- **Features**: Standard Laravel authentication with roles

#### **notifications** (System monitoring)
- **Purpose**: Tracks system processes and notifications
- **Key Fields**:
  - `id` (PK), `user_id`, `submitter_id`, `ref`, `uuid`
  - `label`, `message`, `meta`, `count`, `type`, `status`
- **Features**: Process monitoring and user notifications

#### **terms** (Generic terminology)
- **Purpose**: Flexible term storage for various taxonomies
- **Key Fields**:
  - `id` (PK), `ident`, `type`, `name`, `value`, `alias`
  - `weight`, `curated`, `status`
- **Features**: Soft deletes, weighted ordering

#### **settings** (Application configuration)
- **Purpose**: Dynamic application settings storage
- **Key Fields**: Configurable key-value pairs
- **Features**: Flexible configuration management

### 7. Standard System Tables
- **password_resets**: Laravel password reset tokens
- **failed_jobs**: Laravel failed job queue
- **import_genes**: Temporary import processing table

## Key Data Patterns

### 1. **Identifier Standardization**
- **UUID**: Unique identifiers for all entities
- **CURIE**: Compact URI format for external references
- **External IDs**: Extensive mapping to external databases (HGNC, OMIM, Ensembl, etc.)

### 2. **Status-Based Filtering**
- Most relationships filter by `status = 1` (published/active)
- Supports draft/published workflow
- Soft deletes preserve historical data

### 3. **Curation Counting**
- All major entities track counts by classification level
- Real-time aggregation of submission statistics
- Supports dashboard reporting and analytics

### 4. **Audit Trail**
- Submissions preserve original data in `submitted_as_*` fields
- Tracks file sources and processing dates
- Maintains data lineage and provenance

### 5. **JSON Storage**
- Extensive use of JSON for complex data structures
- Gene aliases, genomic coordinates, metadata
- Flexible schema evolution

### 6. **Hierarchical Relationships**
- Disease ontology hierarchies through self-referential relationships
- Multiple relationship types (parent-child, synonyms, cross-references)
- Supports complex ontology structures

## Database Constraints and Indexes

### Primary Constraints
- All tables have primary keys (mostly `id` bigIncrements)
- Foreign key constraints with cascade deletes where appropriate
- Unique constraints on UUIDs and CURIEs

### Strategic Indexing
- Heavy indexing on frequently queried fields
- Identifier columns (uuid, curie, hgnc_id, symbol, name)
- Status and classification columns
- Curation counter columns for aggregation queries

### JSON Column Usage
- Gene coordinates across genome builds
- External ID arrays and mappings
- Metadata and configuration data
- Flexible data structures without schema changes

## Data Volume and Performance Considerations

### High-Volume Tables
- **submissions**: Central table with extensive relationships
- **genes**: Heavily indexed with complex search requirements
- **diseases**: Complex self-referential relationships
- **submission_files**: Large file processing and storage

### Performance Optimizations
- Eager loading relationships to reduce N+1 queries
- Strategic indexing on search and filter columns
- JSON column optimization for array searches
- Soft deletes to preserve data while maintaining performance

## Security and Access Control

### Data Protection
- User authentication through Laravel's system
- Role-based access (admin, member, blocked)
- Submitter-specific data access controls
- Private notes and internal processing fields

### Data Sharing
- Configurable download permissions per submitter
- Public vs. private data segregation
- Export functionality with access controls

This schema represents a sophisticated genetic curation platform supporting collaborative evidence collection, conflict resolution, and comprehensive gene-disease relationship management.