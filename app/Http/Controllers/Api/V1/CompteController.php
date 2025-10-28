<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\CompteNotFoundException;
use App\Exceptions\UnauthorizedAccessException;
use App\Http\Controllers\Controller;
use App\Http\Resources\CompteResource;
use App\Models\Compte;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;


/**
 * @group Comptes
 *
 * APIs pour la gestion des comptes bancaires
 */
class CompteController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Get(
     *     path="/api/v1/comptes/{compteId}",
     *     summary="Récupérer un compte spécifique",
     *     description="Récupère les détails d'un compte spécifique selon les permissions de l'utilisateur. Recherche d'abord en local, puis en serverless si nécessaire.",
     *     operationId="getCompte",
     *     tags={"Comptes"},
     *     security={{"passport": {"read-comptes"}}},
     *     @OA\Parameter(
     *         name="compteId",
     *         in="path",
     *         description="ID du compte à récupérer",
     *         required=true,
     *         @OA\Schema(type="string", format="uuid", example="550e8400-e29b-41d4-a716-446655440000")
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Détails du compte récupérés avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Compte récupéré avec succès"),
     *             @OA\Property(property="data", ref="#/components/schemas/Compte")
     *         )
     *     ),
     *     @OA\Response(
     *         response=404,
     *         description="Compte non trouvé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *             @OA\Property(
     *                 property="error",
     *                 @OA\Property(property="code", type="string", example="COMPTE_NOT_FOUND"),
     *                 @OA\Property(property="message", type="string", example="Le compte avec l'ID spécifié n'existe pas"),
     *                 @OA\Property(
     *                     property="details",
     *                     @OA\Property(property="compteId", type="string", example="550e8400-e29b-41d4-a716-446655440000")
     *                 )
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
     */
    public function show(Compte $compte): JsonResponse
    {
        $user = $request->user();

        // Vérifier les permissions selon le rôle
        if ($user->role === 'client') {
            // Les clients ne peuvent voir que leurs propres comptes
            $clientIds = $user->clients->pluck('id');
            if (!in_array($compte->client_id, $clientIds->toArray())) {
                throw new UnauthorizedAccessException("Vous n'avez pas accès à ce compte.");
            }
        }
        // Les admins peuvent voir tous les comptes

        // Stratégie de recherche : local par défaut, serverless si nécessaire
        $compteData = null;
        $searchSource = 'local';

        // Recherche en local d'abord (comptes chèque ou épargne actifs)
        if ($compte->type === 'cheque' || ($compte->type === 'epargne' && $compte->statut === 'actif')) {
            $compteData = $compte;
        } else {
            // Recherche en serverless (simulée pour les comptes épargne archivés)
            $compteData = $this->searchInServerless($compte->id);
            $searchSource = 'serverless';
        }

        if (!$compteData) {
            throw new CompteNotFoundException($compte->id);
        }

        // Log de la stratégie de recherche utilisée
        Log::info('Recherche de compte effectuée', [
            'compte_id' => $compte->id,
            'user_id' => $user->id,
            'search_source' => $searchSource,
            'compte_type' => $compte->type,
            'compte_statut' => $compte->statut,
        ]);

        return $this->successResponse(
            new CompteResource($compteData),
            'Compte récupéré avec succès'
        );
    }

    /**
     * Recherche en serverless (simulée pour les comptes archivés)
     *
     * @param string $compteId
     * @return Compte|null
     */
    private function searchInServerless(string $compteId): ?Compte
    {
        // Simulation de recherche serverless
        // En production, ceci ferait appel à une API serverless (AWS Lambda, etc.)
        Log::info('Recherche serverless simulée', [
            'compte_id' => $compteId,
            'service' => 'AWS_Lambda',
            'endpoint' => 'https://lambda.us-east-1.amazonaws.com/accounts/search'
        ]);

        // Pour la simulation, on retourne le compte s'il existe
        // En réalité, ceci contacterait un service externe
        return Compte::find($compteId);
    }

    /**
     * @OA\Get(
     *     path="/api/v1/comptes",
     *     summary="Lister tous les comptes",
     *     description="Récupère la liste paginée des comptes selon les permissions de l'utilisateur. Admin voit tous les comptes, Client voit uniquement ses comptes.",
     *     operationId="getComptes",
     *     tags={"Comptes"},
     *     security={{"passport": {"read-comptes"}}},
     *     @OA\Parameter(
     *         name="page",
     *         in="query",
     *         description="Numéro de page",
     *         required=false,
     *         @OA\Schema(type="integer", default=1, minimum=1)
     *     ),
     *     @OA\Parameter(
     *         name="limit",
     *         in="query",
     *         description="Nombre d'éléments par page",
     *         required=false,
     *         @OA\Schema(type="integer", default=10, minimum=1, maximum=100)
     *     ),
     *     @OA\Parameter(
     *         name="type",
     *         in="query",
     *         description="Filtrer par type",
     *         required=false,
     *         @OA\Schema(type="string", enum={"epargne", "cheque"})
     *     ),
     *     @OA\Parameter(
     *         name="statut",
     *         in="query",
     *         description="Filtrer par statut",
     *         required=false,
     *         @OA\Schema(type="string", enum={"actif", "bloque", "ferme"})
     *     ),
     *     @OA\Parameter(
     *         name="search",
     *         in="query",
     *         description="Recherche par titulaire ou numéro",
     *         required=false,
     *         @OA\Schema(type="string")
     *     ),
     *     @OA\Parameter(
     *         name="sort",
     *         in="query",
     *         description="Tri",
     *         required=false,
     *         @OA\Schema(type="string", enum={"dateCreation", "solde", "titulaire"})
     *     ),
     *     @OA\Parameter(
     *         name="order",
     *         in="query",
     *         description="Ordre",
     *         required=false,
     *         @OA\Schema(type="string", enum={"asc", "desc"})
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Liste des comptes récupérée avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Comptes récupérés avec succès"),
     *             @OA\Property(
     *                 property="data",
     *                 type="array",
     *                 @OA\Items(ref="#/components/schemas/Compte")
     *             ),
     *             @OA\Property(
     *                 property="pagination",
     *                 @OA\Property(property="currentPage", type="integer", example=1),
     *                 @OA\Property(property="totalPages", type="integer", example=3),
     *                 @OA\Property(property="totalItems", type="integer", example=25),
     *                 @OA\Property(property="itemsPerPage", type="integer", example=10),
     *                 @OA\Property(property="hasNext", type="boolean", example=true),
     *                 @OA\Property(property="hasPrevious", type="boolean", example=false)
     *             ),
     *             @OA\Property(
     *                 property="links",
     *                 @OA\Property(property="self", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="next", type="string", example="/api/v1/comptes?page=2&limit=10"),
     *                 @OA\Property(property="first", type="string", example="/api/v1/comptes?page=1&limit=10"),
     *                 @OA\Property(property="last", type="string", example="/api/v1/comptes?page=3&limit=10")
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     ),
     *     @OA\Response(
     *         response=403,
     *         description="Accès refusé",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Accès non autorisé")
     *         )
     *     )
     * )
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
    public function showOld(string $id)
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
