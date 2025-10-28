<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Compte",
 *     title="Compte",
 *     description="Représentation d'un compte bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="numeroCompte", type="string", example="C00123456"),
 *     @OA\Property(property="titulaire", type="string", example="Amadou Diallo"),
 *     @OA\Property(property="type", type="string", enum={"epargne", "cheque"}, example="epargne"),
 *     @OA\Property(property="solde", type="number", format="decimal", example=1250000),
 *     @OA\Property(property="devise", type="string", example="FCFA"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(property="statut", type="string", enum={"actif", "bloque", "ferme"}, example="bloque"),
 *     @OA\Property(property="motifBlocage", type="string", nullable=true, example="Inactivité de 30+ jours"),
 *     @OA\Property(
 *         property="metadata",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
 *         @OA\Property(property="version", type="integer", example=1)
 *     )
 * )
 */
class CompteResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'numeroCompte' => $this->numero,
            'titulaire' => $this->client->nom . ' ' . $this->client->prenom,
            'type' => $this->type,
            'solde' => $this->solde,
            'devise' => $this->devise,
            'dateCreation' => $this->created_at->toISOString(),
            'statut' => $this->statut,
            'motifBlocage' => $this->statut === 'bloque' ? 'Inactivité de 30+ jours' : null,
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'version' => 1
            ]
        ];
    }
}
