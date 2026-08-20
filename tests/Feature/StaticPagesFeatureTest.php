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
