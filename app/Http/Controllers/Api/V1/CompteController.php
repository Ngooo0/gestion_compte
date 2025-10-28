<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\UnauthorizedAccessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * @group Comptes
 *
 * APIs pour la gestion des comptes bancaires
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * Lister tous les comptes
     *
     * Récupère la liste paginée des comptes selon les permissions de l'utilisateur.
     * - Admin : voit tous les comptes
     * - Client : voit uniquement ses comptes
     *
     * @queryParam page int Numéro de page (default: 1)
     * @queryParam limit int Nombre d'éléments par page (default: 10, max: 100)
     * @queryParam type string Filtrer par type (epargne, cheque)
     * @queryParam statut string Filtrer par statut (actif, bloque, ferme)
     * @queryParam search string Recherche par titulaire ou numéro
     * @queryParam sort string Tri (dateCreation, solde, titulaire)
     * @queryParam order string Ordre (asc, desc)
     *
     * @response 200 {
     *   "success": true,
     *   "data": [...],
     *   "pagination": {...},
     *   "links": {...}
     * }
     */
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();

        // Validation des paramètres
        $validated = $request->validate([
            'page' => 'integer|min:1',
            'limit' => 'integer|min:1|max:100',
            'type' => 'string|in:epargne,cheque',
            'statut' => 'string|in:actif,bloque,ferme',
            'search' => 'string|nullable',
            'sort' => 'string|in:dateCreation,solde,titulaire',
            'order' => 'string|in:asc,desc',
        ]);

        $query = Compte::with('client');

        // Filtrage selon le rôle de l'utilisateur
        if ($user->role === 'client') {
            // Les clients ne voient que leurs comptes
            $clientIds = $user->clients->pluck('id');
            $query->whereIn('client_id', $clientIds);
        }
        // Les admins voient tous les comptes (pas de filtrage supplémentaire)

        // Application des filtres
        if (!empty($validated['type'])) {
            $query->where('type', $validated['type']);
        }

        if (!empty($validated['statut'])) {
            $query->where('statut', $validated['statut']);
        }

        if (!empty($validated['search'])) {
            $search = $validated['search'];
            $query->where(function ($q) use ($search) {
                $q->where('numero', 'like', "%{$search}%")
                  ->orWhereHas('client', function ($clientQuery) use ($search) {
                      $clientQuery->where('nom', 'like', "%{$search}%")
                                  ->orWhere('prenom', 'like', "%{$search}%");
                  });
            });
        }

        // Tri
        $sortField = match($validated['sort'] ?? 'dateCreation') {
            'dateCreation' => 'created_at',
            'solde' => 'solde',
            'titulaire' => 'clients.nom',
            default => 'created_at'
        };

        $order = $validated['order'] ?? 'desc';

        if ($sortField === 'clients.nom') {
            $query->join('clients', 'comptes.client_id', '=', 'clients.id')
                  ->orderBy('clients.nom', $order)
                  ->select('comptes.*');
        } else {
            $query->orderBy($sortField, $order);
        }

        // Pagination
        $perPage = $validated['limit'] ?? 10;
        $comptes = $query->paginate($perPage);

        return $this->paginatedResponse($comptes, CompteResource::class);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
