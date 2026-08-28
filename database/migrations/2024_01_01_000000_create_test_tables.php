<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Test-only SQLite representation of the gencc-sub tables read by this app.
 *
 * Production reads gencc-sub's MySQL database directly. Keep these definitions
 * aligned manually when a gencc-sub schema change affects gencc-search.
 */
return new class extends Migration
{
    public function up()
    {
        Schema::create('classifications', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique();
            $table->tinyInteger('type')->default(0);
            $table->string('curie');
            $table->string('name');
            $table->string('description');
            $table->string('abbreviation');
            $table->string('informational')->nullable();
            $table->string('style_class')->nullable();
            $table->string('hex_color')->nullable();
            $table->string('css_class')->nullable();
            $table->string('slug')->nullable();
            $table->string('href')->nullable();
            $table->integer('order')->default(0);
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('inheritances', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique();
            $table->tinyInteger('type')->default(0);
            $table->string('curie');
            $table->string('name');
            $table->string('description');
            $table->string('abbreviation');
            $table->string('informational')->nullable();
            $table->string('style_class')->nullable();
            $table->string('hex_color')->nullable();
            $table->string('css_class')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('genes', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique();
            $table->tinyInteger('type')->default(0);
            $table->string('hgnc_id');
            $table->string('symbol');
            $table->string('name');
            $table->text('description')->nullable();
            $table->json('alias_symbols')->nullable();
            $table->json('previous_symbols')->nullable();
            $table->json('alias_names')->nullable();
            $table->json('previous_names')->nullable();
            $table->timestamp('date_symbol_changed')->nullable();
            $table->timestamp('date_name_changed')->nullable();
            $table->string('locus_group');
            $table->string('locus_type');
            $table->integer('gene_group_id')->nullable();
            $table->string('gene_group')->nullable();
            $table->string('location');
            $table->json('coordinates')->default('[]');
            $table->json('xrefs')->default('[]');
            $table->json('scores')->default('[]');
            $table->json('counts')->default('[]');
            $table->json('activity')->default('[]');
            $table->json('events')->default('[]');
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('diseases', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mondo_id')->nullable();
            $table->string('ident')->unique();
            $table->tinyInteger('type')->default(0);
            $table->string('curie');
            $table->string('name');
            $table->string('deprecated_name')->nullable();
            $table->text('description')->nullable();
            $table->json('synonyms')->nullable();
            $table->json('xrefs')->default('[]');
            $table->json('scores')->default('[]');
            $table->json('counts')->default('[]');
            $table->json('activity')->default('[]');
            $table->json('events')->default('[]');
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('submitters', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique();
            $table->tinyInteger('type')->default(0);
            $table->string('curie');
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('logo')->nullable();
            $table->mediumText('logo_contents')->nullable();
            $table->string('logo_mime_type', 50)->nullable();
            $table->string('website')->nullable();
            $table->text('assertion')->nullable();
            $table->json('counts')->default('[]');
            $table->json('activity')->default('[]');
            $table->json('contacts')->default('[]');
            $table->text('notes')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->boolean('allow_submissions')->default(true);
            $table->boolean('downloadable')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('submissions', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique();
            $table->tinyInteger('type')->default(0);
            $table->string('sid')->nullable();
            $table->unsignedInteger('version_number')->default(1);
            $table->boolean('is_most_recent')->default(true);
            $table->boolean('is_live')->default(false);
            $table->string('local_key')->nullable();
            $table->string('friendly')->nullable();
            $table->unsignedBigInteger('job_id');
            $table->unsignedBigInteger('document_id')->nullable();
            $table->unsignedBigInteger('gene_id')->nullable();
            $table->unsignedBigInteger('disease_id')->nullable();
            $table->unsignedBigInteger('original_disease_id')->nullable();
            $table->unsignedBigInteger('inheritance_id')->nullable();
            $table->unsignedBigInteger('submitter_id');
            $table->unsignedBigInteger('classification_id')->nullable();
            $table->unsignedBigInteger('mechanism_id')->nullable();
            $table->unsignedBigInteger('user_id');
            $table->json('evidence')->nullable();
            $table->text('normalized_pmids')->nullable();
            $table->json('pmid_issues')->nullable();
            $table->timestamp('publish_date')->nullable();
            $table->timestamp('posted_date')->nullable();
            $table->timestamp('report_date')->nullable();
            $table->string('report_url')->nullable();
            $table->json('submission_data');
            $table->json('original_submission_data')->nullable();
            $table->json('submission_errors')->nullable();
            $table->json('history')->nullable();
            $table->json('tags')->nullable();
            $table->string('status', 50)->default('draft_new');
            $table->string('action', 20)->nullable();
            $table->string('origin_state', 50)->nullable();
            $table->timestamps();
            $table->timestamp('released_at')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->softDeletes();
            $table->unsignedBigInteger('last_edited_by')->nullable();
        });

        Schema::create('pubmeds', function (Blueprint $table) {
            $table->id();
            $table->string('ident')->unique();
            $table->string('pmid');
            $table->string('uid');
            $table->string('pubdate')->nullable();
            $table->string('epubdate')->nullable();
            $table->string('source')->nullable();
            $table->text('authors')->nullable();
            $table->string('lastauthor')->nullable();
            $table->text('title')->nullable();
            $table->text('sorttitle')->nullable();
            $table->string('volume')->nullable();
            $table->string('issue')->nullable();
            $table->string('pages')->nullable();
            $table->string('lang')->nullable();
            $table->string('nlmuniqueid')->nullable();
            $table->string('issn')->nullable();
            $table->string('essn')->nullable();
            $table->string('pubtype')->nullable();
            $table->string('recordstatus')->nullable();
            $table->string('pubstatus')->nullable();
            $table->text('articleids')->nullable();
            $table->text('history')->nullable();
            $table->text('references')->nullable();
            $table->string('attributes')->nullable();
            $table->string('pmcrefcount')->nullable();
            $table->string('fullfournalname')->nullable();
            $table->string('elocationid')->nullable();
            $table->string('doctype')->nullable();
            $table->text('srccontriblist')->nullable();
            $table->string('booktitle')->nullable();
            $table->string('medium')->nullable();
            $table->string('edition')->nullable();
            $table->string('publisherlocation')->nullable();
            $table->string('publishername')->nullable();
            $table->string('srcdate')->nullable();
            $table->string('reportnumber')->nullable();
            $table->string('availablefromurl')->nullable();
            $table->string('locationlabel')->nullable();
            $table->text('doccontriblist')->nullable();
            $table->string('docdate')->nullable();
            $table->string('bookname')->nullable();
            $table->string('chapter')->nullable();
            $table->string('sortpubdate')->nullable();
            $table->string('sortfirstauthor')->nullable();
            $table->text('vernaculartitle')->nullable();
            $table->text('other')->nullable();
            $table->mediumText('notes')->nullable();
            $table->tinyInteger('status')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        Schema::create('pubmed_submission', function (Blueprint $table) {
            $table->unsignedBigInteger('pubmed_id');
            $table->unsignedBigInteger('submission_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('pubmed_submission');
        Schema::dropIfExists('pubmeds');
        Schema::dropIfExists('submissions');
        Schema::dropIfExists('submitters');
        Schema::dropIfExists('diseases');
        Schema::dropIfExists('genes');
        Schema::dropIfExists('inheritances');
        Schema::dropIfExists('classifications');
    }
};
