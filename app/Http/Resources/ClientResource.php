<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @OA\Schema(
 *     schema="Client",
 *     title="Client",
 *     description="Représentation d'un client bancaire",
 *     @OA\Property(property="id", type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000"),
 *     @OA\Property(property="nom", type="string", example="Diallo"),
 *     @OA\Property(property="prenom", type="string", example="Amadou"),
 *     @OA\Property(property="email", type="string", format="email", example="amadou.diallo@example.com"),
 *     @OA\Property(property="telephone", type="string", example="+221771234567"),
 *     @OA\Property(property="adresse", type="string", example="Dakar, Sénégal"),
 *     @OA\Property(property="dateNaissance", type="string", format="date", example="1990-05-15"),
 *     @OA\Property(property="dateCreation", type="string", format="date-time", example="2023-03-15T00:00:00Z"),
 *     @OA\Property(
 *         property="comptes",
 *         type="array",
 *         @OA\Items(ref="#/components/schemas/Compte")
 *     ),
 *     @OA\Property(
 *         property="metadata",
 *         @OA\Property(property="derniereModification", type="string", format="date-time", example="2023-06-10T14:30:00Z"),
 *         @OA\Property(property="nombreComptes", type="integer", example=2)
 *     )
 * )
 */
class ClientResource extends JsonResource
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
            'nom' => $this->nom,
            'prenom' => $this->prenom,
            'email' => $this->email,
            'telephone' => $this->telephone,
            'adresse' => $this->adresse,
            'dateNaissance' => $this->date_naissance?->format('Y-m-d'),
            'dateCreation' => $this->created_at->toISOString(),
            'comptes' => CompteResource::collection($this->whenLoaded('comptes')),
            'metadata' => [
                'derniereModification' => $this->updated_at->toISOString(),
                'nombreComptes' => $this->whenLoaded('comptes', fn() => $this->comptes->count(), 0)
            ]
        ];
    }
}