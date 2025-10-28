<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Créer un administrateur
        \App\Models\User::factory()->create([
            'name' => 'Admin User',
            'email' => 'admin@gestion-compte.com',
            'role' => 'admin',
        ]);

        // Créer des agents
        \App\Models\User::factory()->create([
            'name' => 'Agent Dupont',
            'email' => 'agent1@gestion-compte.com',
            'role' => 'agent',
        ]);

        \App\Models\User::factory()->create([
            'name' => 'Agent Martin',
            'email' => 'agent2@gestion-compte.com',
            'role' => 'agent',
        ]);

        // Créer des utilisateurs clients
        \App\Models\User::factory(7)->create([
            'role' => 'client',
        ]);
    }
}
