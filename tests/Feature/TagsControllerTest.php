<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;


class TagsControllerTest extends TestCase
{
   use LazilyRefreshDatabase;
    /**
     * A basic feature test example.
     */

    
/** @test */
    public function itListTags()
    {
        $response = $this->get('/api/tags');

        $response->assertStatus(200);
        $this->assertNotNull( $response->json('data')[0]['id']);
    }

}
