<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class Stats200Test extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testStatisticsReturnsSuccess()
    {
        $response = $this->get('/statistics');

        $response->assertStatus(200);
    }
}
