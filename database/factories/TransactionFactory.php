<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Transaction>
 */
class TransactionFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $type = $this->faker->randomElement(['depot', 'retrait', 'virement', 'transfert']);

        return [
            'id' => $this->faker->uuid(),
            'reference' => null, // Sera généré automatiquement par le mutator
            'type' => $type,
            'montant' => $this->faker->randomFloat(2, 100, 50000),
            'devise' => 'XOF',
            'description' => $this->faker->sentence(),
            'statut' => $this->faker->randomElement(['en_attente', 'validee', 'rejete']),
            'date_transaction' => $this->faker->dateTimeBetween('-1 month', 'now'),
            'compte_id' => \App\Models\Compte::factory(),
            'compte_destination_id' => $type === 'virement' ? \App\Models\Compte::factory() : null,
        ];
    }
}
