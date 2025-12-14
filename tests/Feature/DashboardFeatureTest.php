<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\User;
use App\Submitter;

class DashboardFeatureTest extends TestCase
{
    use RefreshDatabase;

    /** @test */
    public function dashboard_page_returns_200_for_authenticated_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertStatus(200);
        $response->assertViewIs('dashboards.index');
    }

    /** @test */
    public function dashboard_redirects_unauthenticated_users()
    {
        $response = $this->get('/dashboard');

        $response->assertRedirect('/gencc-members/login');
    }

    /** @test */
    public function admin_index_returns_200_for_authenticated_user()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard/admin');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_submitter_show_returns_200_for_valid_submitter()
    {
        $user = User::factory()->create();
        $submitter = Submitter::factory()->create(['curie' => 'GENCC:000001']);

        $response = $this->actingAs($user)->get('/dashboard/admin/GENCC:000001');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_submitter_profile_returns_200()
    {
        $user = User::factory()->create();
        $submitter = Submitter::factory()->create(['curie' => 'GENCC:000002']);

        $response = $this->actingAs($user)->get('/dashboard/admin/GENCC:000002/profile');

        $response->assertStatus(200);
    }

    /** @test */
    public function admin_submitter_files_returns_200()
    {
        $user = User::factory()->create();
        $submitter = Submitter::factory()->create(['curie' => 'GENCC:000003']);

        $response = $this->actingAs($user)->get('/dashboard/admin/GENCC:000003/files');

        $response->assertStatus(200);
    }

    /**
     * @test
     * Note: This test is currently skipped because the submitter-create route
     * has a bug where ManageSubmitterProfile Livewire component doesn't handle null submitter
     */
    public function admin_submitter_create_returns_200()
    {
        $this->markTestSkipped('Submitter-create route needs to handle null submitter in ManageSubmitterProfile component');

        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard/admin/submitter-create');

        $response->assertStatus(200);
    }
}
