<?php

namespace Edenlife\Superban\Tests;

// use Illuminate\Foundation\Testing\RefreshDatabase;

class SuperbanFeatureTest extends BaseTestCase
{
    /**
     * A basic test example.
     */
    public function test_the_application_returns_a_successful_response(): void
    {
        $response = $this->get('/');

        $response->assertOk();
    }
}
