<?php

namespace Tests\Feature;

use Tests\TestCase;

class UnauthenticatedTest extends TestCase
{
    /** @test */
    public function api_returns_401_when_unauthenticated()
    {
        $this->getJson('/api/v1/comptes')
            ->assertStatus(401);
    }

    /** @test */
    public function web_request_redirects_to_login_when_unauthenticated()
    {
        $response = $this->get('/login');

        $response->assertStatus(200);
    }
}
