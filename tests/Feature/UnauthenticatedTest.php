<?php

namespace Tests\Feature;

use Tests\TestCase;

class UnauthenticatedTest extends TestCase
{
    /** @test */
    public function api_returns_401_when_unauthenticated()
    {
        $this->getJson('/api/user')
            ->assertStatus(401)
            ->assertJson(['message' => 'Unauthenticated.'])
            ->assertJsonFragment(['error' => 'unauthenticated']);
    }

    /** @test */
    public function web_request_redirects_to_login_when_unauthenticated()
    {
        $response = $this->get('/api/user');

        $response->assertStatus(302);
        $response->assertRedirect('/login');
    }
}
