<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Client;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();

        // Créer un client OAuth pour les tests
        Client::create([
            'id' => 1,
            'name' => 'Test Client',
            'secret' => 'test-secret',
            'redirect' => 'http://localhost',
            'personal_access_client' => false,
            'password_client' => true,
            'revoked' => false,
        ]);

        // Créer un utilisateur de test
        User::create([
            'id' => $this->faker->uuid(),
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'is_active' => true,
        ]);
    }

    /**
     * Test de connexion réussie
     */
    public function test_user_can_login_successfully()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'message',
                'data' => [
                    'user',
                    'access_token',
                    'refresh_token',
                    'token_type',
                    'expires_in',
                    'scopes',
                ],
            ])
            ->assertJson([
                'success' => true,
                'data' => [
                    'token_type' => 'Bearer',
                    'expires_in' => 3600,
                ],
            ]);

        // Vérifier que les cookies sont définis
        $response->assertCookie('access_token');
        $response->assertCookie('refresh_token');
    }

    /**
     * Test de connexion avec identifiants invalides
     */
    public function test_user_cannot_login_with_invalid_credentials()
    {
        $response = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'wrongpassword',
        ]);

        $response->assertStatus(422)
            ->assertJson([
                'success' => false,
                'message' => 'Les identifiants fournis sont incorrects.',
            ]);
    }

    /**
     * Test de connexion avec données manquantes
     */
    public function test_login_requires_email_and_password()
    {
        $response = $this->postJson('/api/v1/auth/login', []);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['email', 'password']);
    }

    /**
     * Test de déconnexion
     */
    public function test_user_can_logout()
    {
        // Se connecter d'abord
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Se déconnecter
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->postJson('/api/v1/auth/logout');

        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Déconnexion réussie',
            ]);

        // Vérifier que les cookies sont supprimés
        $response->assertCookieExpired('access_token');
        $response->assertCookieExpired('refresh_token');
    }

    /**
     * Test d'accès à une route protégée sans authentification
     */
    public function test_protected_route_requires_authentication()
    {
        $response = $this->getJson('/api/v1/comptes');

        $response->assertStatus(401)
            ->assertJson([
                'success' => false,
                'message' => 'Non authentifié - Token d\'accès requis',
            ]);
    }

    /**
     * Test d'accès à une route protégée avec token valide
     */
    public function test_protected_route_works_with_valid_token()
    {
        // Se connecter
        $loginResponse = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $accessToken = $loginResponse->json('data.access_token');

        // Accéder à une route protégée
        $response = $this->withHeaders([
            'Authorization' => 'Bearer ' . $accessToken,
        ])->getJson('/api/v1/comptes');

        // Devrait réussir (même si pas de comptes, c'est l'auth qui compte)
        $response->assertStatus(200);
    }

    /**
     * Test des scopes utilisateur selon le rôle
     */
    public function test_user_scopes_based_on_role()
    {
        // Test admin
        $adminLogin = $this->postJson('/api/v1/auth/login', [
            'email' => 'test@example.com',
            'password' => 'password',
        ]);

        $adminLogin->assertJson([
            'success' => true,
            'data' => [
                'scopes' => [
                    'read-comptes',
                    'write-comptes',
                    'read-transactions',
                    'write-transactions',
                    'read-clients',
                    'write-clients',
                    'admin-access',
                ],
            ],
        ]);

        // Créer un utilisateur client
        User::create([
            'id' => $this->faker->uuid(),
            'name' => 'Client User',
            'email' => 'client@example.com',
            'password' => Hash::make('password'),
            'role' => 'client',
            'is_active' => true,
        ]);

        // Test client
        $clientLogin = $this->postJson('/api/v1/auth/login', [
            'email' => 'client@example.com',
            'password' => 'password',
        ]);

        $clientLogin->assertJson([
            'success' => true,
            'data' => [
                'scopes' => [
                    'read-comptes',
                    'read-transactions',
                    'read-clients',
                ],
            ],
        ]);
    }
}
