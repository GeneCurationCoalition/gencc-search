<?php

namespace Tests\Feature;

use App\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Faker\Factory;
use Tests\TestCase;

class DashboardAsUser200Test extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testDashboardReturnsSuccess()
    {
        $user = User::factory()->make();

        $response = $this->actingAs($user)
            ->get('/dashboard');

        $response->assertStatus(200);
    }
}