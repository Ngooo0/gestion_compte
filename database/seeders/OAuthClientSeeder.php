<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class OAuthClientSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un client pour l'application web
        DB::table('oauth_clients')->updateOrInsert(
            ['id' => 1],
            [
                'name' => 'Banque Web Client',
                'secret' => 'JGaaxR1pPZsD9fSHTqOOm5eW7PjPbx1MKxymMHw8',
                'provider' => null,
                'redirect' => 'http://localhost:3000/callback',
                'personal_access_client' => true,
                'password_client' => false,
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        // Créer un client pour l'application mobile/API
        DB::table('oauth_clients')->updateOrInsert(
            ['id' => 2],
            [
                'name' => 'Banque API Client',
                'secret' => 'yqoDzeffa2u433FSNQpUzCUPgnlEw625VDjfJ6lS',
                'provider' => null,
                'redirect' => 'http://localhost:8000/callback',
                'personal_access_client' => false,
                'password_client' => true,
                'revoked' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        $this->command->info('OAuth clients créés avec succès');
    }
}
