<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class Genes200Test extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testGenesReturnsSuccess()
    {
        $response = $this->get('/genes');

        $response->assertStatus(200);
    }
}
