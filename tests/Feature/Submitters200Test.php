<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;

class Submitters200Test extends TestCase
{
    /**
     * A basic feature test example.
     *
     * @return void
     */
    public function testSubmittersReturnsSuccess()
    {
        $response = $this->get('/submitters');

        $response->assertStatus(200);
    }
}
