<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaticPagesFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function about_page_returns_200()
    {
        $response = $this->get('/about');

        $response->assertStatus(200);
        $response->assertViewIs('general.about');
    }

    /**
     * @test
     *
     * The ClinGen SC asked for this specifically to correct a misreading of
     * GenCC's relationship to ClinGen (#211), so the disclaimer is the point of
     * the section, not incidental copy.
     */
    public function about_page_shows_clingen_funding_and_review_disclaimer()
    {
        $response = $this->get('/about');

        $response->assertSee('Funding');
        $response->assertSee('U24HG006834');
        $response->assertSee('does not review for accuracy or modify submitted content');
    }

    /** @test */
    public function privacy_page_returns_200()
    {
        $response = $this->get('/privacy');

        $response->assertStatus(200);
        $response->assertViewIs('general.privacy');
    }

    /** @test */
    public function terms_page_returns_200()
    {
        $response = $this->get('/terms');

        $response->assertStatus(200);
        $response->assertViewIs('general.terms');
    }

    /** @test */
    public function faq_page_returns_200()
    {
        $response = $this->get('/faq');

        $response->assertStatus(200);
        $response->assertViewIs('general.faq');
    }

    /**
     * @test
     *
     * The four FAQ revisions agreed on a GenCC call (#208).
     */
    public function faq_page_reflects_the_agreed_revisions()
    {
        $response = $this->get('/faq');

        // 1) The marker paper is referenced.
        $response->assertSee('marker paper', false);
        $response->assertSee('35507016');

        // 2) The Delphi survey description and its image are gone, and the
        // section is retitled. The anchor deliberately keeps its old value.
        $response->assertDontSee('Delphi');
        $response->assertDontSee('img/faq/delphi.png', false);
        $response->assertSee('Standardized Clinical Validity terms');

        // 3) Supportive joins the term definitions.
        $response->assertSee('Supportive');
        $response->assertSee('did not curate to the same level of granularity');

        // 4) A publications section, listed in the section index.
        $response->assertSee('GenCC Publications');
        $response->assertSee('#gencc-publications', false);
        $response->assertSee('S1098-3600(23)01045-6', false);
        $response->assertSee('The Gene Curation Coalition: A global effort to harmonize gene-disease evidence resources');
        $response->assertDontSee('DiStefano MT et al.', false);
        $response->assertSee('PMID 35507016');
        $response->assertSee('10.1016/j.gim.2022.04.017');
        $response->assertSee('Toward robust clinical genome interpretation: Developing a consistent terminology to characterize Mendelian disease-gene relationships', false);
        $response->assertDontSee('Roberts AM et al.', false);
        $response->assertSee('2024');
        $response->assertSee('PMID 37982373');
        $response->assertSee('10.1016/j.gim.2023.101029');
    }

    /**
     * @test
     *
     * The retitled section keeps its original anchor because the genes listing
     * links straight to it (livewire/genes/listing.blade.php), as do external
     * bookmarks.
     */
    public function faq_validity_terms_anchor_is_unchanged()
    {
        $this->get('/faq')->assertSee('id="validity-termsdelphi-survey"', false);
    }

    /**
     * @test
     * @group skip-ci
     */
    public function reports_page_returns_200()
    {
        $this->markTestSkipped('Reports route does not exist in current codebase - moved to gencc-sub');
    }

    /** @test */
    public function home_page_returns_200()
    {
        $response = $this->get('/home');

        $response->assertStatus(200);
    }
}
