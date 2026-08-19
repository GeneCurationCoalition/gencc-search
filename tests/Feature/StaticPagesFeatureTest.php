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
