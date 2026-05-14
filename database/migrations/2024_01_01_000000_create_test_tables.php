<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test-only migration for SQLite in-memory database.
 * Creates tables matching the gencc-sub schema for unit testing.
 * This migration is NOT used in production (MySQL reads from gencc-sub directly).
 */
return new class extends Migration
{
    public function up()
    {
        // Classifications table (matches gencc-sub schema)
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->string('curie')->unique();
            $table->string('uuid')->nullable();
            $table->string('ident')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('abbreviation')->nullable();
            $table->string('slug')->nullable();
            $table->integer('order')->default(0);
            $table->string('hex_color')->nullable();
            $table->string('css_class')->nullable();
            $table->text('description')->nullable();
            $table->text('info_text')->nullable();
            $table->string('href')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        // Inheritances table (matches gencc-sub schema)
        Schema::create('inheritances', function (Blueprint $table) {
            $table->id();
            $table->string('curie')->unique();
            $table->string('uuid')->nullable();
            $table->string('ident')->unique()->nullable();
            $table->string('name')->nullable();
            $table->string('title')->nullable();
            $table->string('abbreviation')->nullable();
            $table->text('description')->nullable();
            $table->string('hex_color')->nullable();
            $table->string('css_class')->nullable();
            $table->text('info_text')->nullable();
            $table->integer('status')->default(1);
            $table->timestamps();
        });

        // Genes table (matches gencc-sub schema)
        Schema::create('genes', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique()->nullable();
            $table->string('uuid')->nullable();
            $table->string('curie')->unique();
            $table->string('type')->default('gene');
            $table->string('title');
            $table->string('name')->nullable();
            $table->string('symbol')->nullable();
            $table->string('hgnc_id')->nullable();
            $table->string('hgnc_uuid')->nullable();
            $table->string('location')->nullable();
            $table->string('locus_group')->nullable();
            $table->string('locus_type')->nullable();
            $table->string('entrez_id')->nullable();
            $table->string('ensembl_gene_id')->nullable();
            $table->string('ucsc_id')->nullable();
            $table->string('omim_id')->nullable();
            $table->text('alias_symbol')->nullable();
            $table->text('prev_symbol')->nullable();
            $table->string('chr')->nullable();
            $table->text('grch37')->nullable();
            $table->text('grch38')->nullable();
            $table->text('chm13')->nullable();
            $table->string('mane_select')->nullable();
            $table->string('mane_plus')->nullable();
            $table->string('gene_group')->nullable();
            $table->float('pli')->nullable();
            $table->float('loeuf')->nullable();
            $table->string('hi')->nullable();
            $table->string('haplo')->nullable();
            $table->string('triplo')->nullable();
            $table->boolean('is_acmgsf3')->default(false);
            $table->boolean('is_morbid')->default(false);
            $table->string('curation_status')->nullable();
            $table->string('nstatus')->nullable();
            $table->text('function')->nullable();
            $table->text('notes')->nullable();
            $table->date('date_last_curated')->nullable();
            $table->date('date_symbol_changed')->nullable();
            $table->date('date_approved_reserved')->nullable();
            $table->string('lsdb')->nullable();
            $table->text('description')->nullable();
            $table->date('date_modified')->nullable();
            // Individual count columns (not JSON)
            $table->integer('count_submissions')->default(0);
            $table->integer('count_unique_submitters')->default(0);
            $table->integer('count_unique_diseases')->default(0);
            $table->integer('curations_definitive')->default(0);
            $table->integer('curations_strong')->default(0);
            $table->integer('curations_moderate')->default(0);
            $table->integer('curations_limited')->default(0);
            $table->integer('curations_disputed')->default(0);
            $table->integer('curations_refuted')->default(0);
            $table->integer('curations_animal')->default(0);
            $table->integer('curations_noknown')->default(0);
            $table->integer('curations_supportive')->default(0);
            $table->integer('curations_nul')->default(0);
            $table->string('status')->default('0');
            $table->timestamps();
            $table->softDeletes();
        });

        // Diseases table (matches gencc-sub schema)
        Schema::create('diseases', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique()->nullable();
            $table->string('uuid')->nullable();
            $table->string('curie')->unique();
            $table->string('type')->default('MONDO');
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->text('description')->nullable();
            $table->string('deprecated_name')->nullable();
            $table->unsignedBigInteger('mondo_id')->nullable();
            $table->unsignedBigInteger('mondo_parent_id')->nullable();
            $table->json('synonyms')->nullable();
            $table->text('synonyms_exact')->nullable();
            $table->text('synonyms_related')->nullable();
            $table->json('xrefs')->nullable();
            $table->json('scores')->nullable();
            $table->json('activity')->nullable();
            $table->json('events')->nullable();
            $table->text('notes')->nullable();
            $table->text('meta_parents')->nullable();
            $table->text('children_sync')->nullable();
            $table->text('related_closeMatch')->nullable();
            $table->text('related_exactMatch')->nullable();
            // Individual count columns (not JSON)
            $table->integer('count_submissions')->default(0);
            $table->integer('count_unique_genes')->default(0);
            $table->integer('count_unique_submitters')->default(0);
            $table->integer('count_child_submissions')->default(0);
            $table->integer('count_clinical_above')->default(0);
            $table->integer('count_clinical_below')->default(0);
            $table->integer('count_clinical_neutral')->default(0);
            $table->integer('curations_definitive')->default(0);
            $table->integer('curations_strong')->default(0);
            $table->integer('curations_moderate')->default(0);
            $table->integer('curations_limited')->default(0);
            $table->integer('curations_disputed')->default(0);
            $table->integer('curations_refuted')->default(0);
            $table->integer('curations_animal')->default(0);
            $table->integer('curations_noknown')->default(0);
            $table->integer('curations_supportive')->default(0);
            $table->integer('curations_nul')->default(0);
            $table->string('status')->default('active');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('mondo_id')->references('id')->on('diseases')->nullOnDelete();
            $table->foreign('mondo_parent_id')->references('id')->on('diseases')->nullOnDelete();
        });

        // Submitters table (matches gencc-sub schema)
        Schema::create('submitters', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique()->nullable();
            $table->string('curie')->unique();
            $table->string('uuid')->nullable();
            $table->string('title')->nullable();
            $table->string('name')->nullable();
            $table->string('website')->nullable();
            $table->text('description')->nullable();
            $table->text('assertion')->nullable();
            $table->text('text_descriptions')->nullable();
            $table->text('text_assertions')->nullable();
            $table->text('text_contact')->nullable();
            $table->text('text_disclaimer')->nullable();
            $table->string('path_logo')->nullable();
            $table->integer('status')->default(1);
            $table->boolean('downloadable')->default(true);
            $table->boolean('member')->default(true);
            // Individual count columns (not JSON)
            $table->integer('count_submissions')->default(0);
            $table->integer('count_unique_genes')->default(0);
            $table->integer('count_unique_diseases')->default(0);
            $table->integer('curations_definitive')->default(0);
            $table->integer('curations_strong')->default(0);
            $table->integer('curations_moderate')->default(0);
            $table->integer('curations_limited')->default(0);
            $table->integer('curations_disputed')->default(0);
            $table->integer('curations_refuted')->default(0);
            $table->integer('curations_animal')->default(0);
            $table->integer('curations_noknown')->default(0);
            $table->integer('curations_supportive')->default(0);
            $table->integer('curations_nul')->default(0);
            $table->json('counts')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });

        // Pubmeds table
        Schema::create('pubmeds', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->nullable();
            $table->string('pmid')->nullable();
            $table->string('uid')->nullable();
            $table->string('pubdate')->nullable();
            $table->string('sortpubdate')->nullable();
            $table->string('sortfirstauthor')->nullable();
            $table->text('title')->nullable();
            $table->text('authors')->nullable();
            $table->string('source')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // Submissions table (matches gencc-sub schema)
        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('uuid')->nullable();
            $table->string('sid')->nullable();
            $table->string('curie')->nullable();
            $table->integer('order')->default(0);
            $table->unsignedBigInteger('gene_id');
            $table->unsignedBigInteger('disease_id');
            $table->unsignedBigInteger('original_disease_id')->nullable();
            $table->unsignedBigInteger('classification_id');
            $table->unsignedBigInteger('submitter_id');
            $table->unsignedBigInteger('inheritance_id')->nullable();
            $table->date('report_date')->nullable();
            $table->string('report_url')->nullable();
            $table->json('evidence')->nullable();
            $table->json('submission_data')->nullable();
            $table->json('original_submission_data')->nullable();
            $table->text('private_notes')->nullable();
            $table->string('normalized_pmids')->nullable();
            $table->dateTime('publish_date')->nullable();
            $table->dateTime('submitted_run_date')->nullable();
            $table->integer('version_number')->default(1);
            $table->boolean('is_live')->default(true);
            $table->string('status')->default('published');
            $table->dateTime('released_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('gene_id')->references('id')->on('genes')->cascadeOnDelete();
            $table->foreign('disease_id')->references('id')->on('diseases')->cascadeOnDelete();
            $table->foreign('original_disease_id')->references('id')->on('diseases')->nullOnDelete();
            $table->foreign('classification_id')->references('id')->on('classifications')->cascadeOnDelete();
            $table->foreign('submitter_id')->references('id')->on('submitters')->cascadeOnDelete();
            $table->foreign('inheritance_id')->references('id')->on('inheritances')->nullOnDelete();
        });

        // Pubmed-Submission pivot table
        Schema::create('pubmed_submission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pubmed_id');
            $table->unsignedBigInteger('submission_id');
            $table->timestamps();

            $table->foreign('pubmed_id')->references('id')->on('pubmeds')->cascadeOnDelete();
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
        });

        // Disease-Submission pivot table (for original pivot relationship)
        Schema::create('disease_submission', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('disease_id');
            $table->unsignedBigInteger('submission_id');
            $table->string('type')->default('current');
            $table->timestamps();

            $table->foreign('disease_id')->references('id')->on('diseases')->cascadeOnDelete();
            $table->foreign('submission_id')->references('id')->on('submissions')->cascadeOnDelete();
        });
    }

    public function down()
    {
        Schema::dropIfExists('disease_submission');
        Schema::dropIfExists('pubmed_submission');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('pubmeds');
        Schema::dropIfExists('submitters');
        Schema::dropIfExists('diseases');
        Schema::dropIfExists('genes');
        Schema::dropIfExists('inheritances');
        Schema::dropIfExists('classifications');
    }
};
