<?php

namespace Tests\Feature;

// use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertStatus(200);
        $response->assertSee('id="hospitals"', false);
        $response->assertSee('id="laboratories"', false);
        $response->assertSee('id="corporate"', false);
        $response->assertSee('id="custody"', false);
        $response->assertSee('Corporate Screening Console');
        $response->assertSee('Specimen Logistics & Security', false);
    }
}
