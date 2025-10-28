<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Compte>
 */
class CompteFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'id' => $this->faker->uuid(),
            'numero' => null, // Sera généré automatiquement par le mutator
            'type' => $this->faker->randomElement(['courant', 'epargne']),
            'solde' => $this->faker->randomFloat(2, 0, 100000),
            'devise' => 'XOF',
            'statut' => 'actif',
            'client_id' => \App\Models\Client::factory(),
        ];
    }
}
