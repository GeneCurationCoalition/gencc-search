<?php

namespace Tests\Feature;

use Tests\TestCase;

class RemovedDiseaseRoutesTest extends TestCase
{
    /** @test */
    public function unused_disease_pages_are_not_available()
    {
        $this->get('/disease')->assertNotFound();
        $this->get('/disease/MONDO:0000001')->assertNotFound();
    }
}
