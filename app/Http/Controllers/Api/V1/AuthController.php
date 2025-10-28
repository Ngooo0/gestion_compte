<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cookie;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

/**
 * @group Authentification
 *
 * APIs pour l'authentification OAuth2 avec Laravel Passport
 */
class AuthController extends Controller
{
    use ApiResponseTrait;

    /**
     * @OA\Post(
     *     path="/api/v1/auth/login",
     *     summary="Connexion utilisateur",
     *     description="Authentifie un utilisateur et retourne les tokens d'accès et de rafraîchissement",
     *     operationId="login",
     *     tags={"Authentification"},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"email", "password"},
     *             @OA\Property(property="email", type="string", format="email", example="admin@banque.com"),
     *             @OA\Property(property="password", type="string", format="password", example="password123")
     *         )
     *     ),
     *     @OA\Response(
     *         response=200,
     *         description="Connexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Connexion réussie"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="user", type="object",
     *                     @OA\Property(property="id", type="string", format="uuid"),
     *                     @OA\Property(property="name", type="string"),
     *                     @OA\Property(property="email", type="string", format="email"),
     *                     @OA\Property(property="role", type="string", enum={"admin", "client"}),
     *                     @OA\Property(property="is_active", type="boolean")
     *                 ),
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="refresh_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Identifiants invalides",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Identifiants invalides")
     *         )
     *     )
     * )
     */
    public function login(Request $request): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            Log::warning('Tentative de connexion échouée', [
                'email' => $request->email,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);

            throw ValidationException::withMessages([
                'email' => ['Les identifiants fournis sont incorrects.'],
            ]);
        }

        // Créer le token d'accès avec scopes basés sur le rôle
        $scopes = $this->getScopesForRole($user->role);
        $token = $user->createToken('API Access', $scopes);

        // Créer le refresh token
        $refreshToken = $user->createToken('Refresh Token', ['refresh-token']);

        // Stocker les tokens dans les cookies
        $accessCookie = Cookie::make('access_token', $token->accessToken, 60, null, null, true, true); // 1 heure
        $refreshCookie = Cookie::make('refresh_token', $refreshToken->accessToken, 60 * 24 * 7, null, null, true, true); // 7 jours

        Log::info('Connexion utilisateur réussie', [
            'user_id' => $user->id,
            'email' => $user->email,
            'role' => $user->role,
            'ip' => $request->ip(),
        ]);

        $response = $this->successResponse([
            'user' => $user,
            'access_token' => $token->accessToken,
            'refresh_token' => $refreshToken->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scopes' => $scopes,
        ], 'Connexion réussie');

        return $response->withCookie($accessCookie)->withCookie($refreshCookie);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/refresh",
     *     summary="Rafraîchir le token d'accès",
     *     description="Utilise le refresh token pour générer un nouveau token d'accès",
     *     operationId="refresh",
     *     tags={"Authentification"},
     *     security={{"passport": {"refresh-token"}}},
     *     @OA\Response(
     *         response=200,
     *         description="Token rafraîchi avec succès",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Token rafraîchi avec succès"),
     *             @OA\Property(property="data", type="object",
     *                 @OA\Property(property="access_token", type="string", example="eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9..."),
     *                 @OA\Property(property="token_type", type="string", example="Bearer"),
     *                 @OA\Property(property="expires_in", type="integer", example=3600)
     *             )
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Token de rafraîchissement invalide",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Token de rafraîchissement invalide")
     *         )
     *     )
     * )
     */
    public function refresh(Request $request): JsonResponse
    {
        $user = $request->user();

        // Révoquer l'ancien token d'accès
        $request->user()->token()->revoke();

        // Créer un nouveau token d'accès
        $scopes = $this->getScopesForRole($user->role);
        $token = $user->createToken('API Access', $scopes);

        // Stocker le nouveau token dans les cookies
        $accessCookie = Cookie::make('access_token', $token->accessToken, 60, null, null, true, true);

        Log::info('Token rafraîchi', [
            'user_id' => $user->id,
            'new_token_id' => $token->token->id,
        ]);

        $response = $this->successResponse([
            'access_token' => $token->accessToken,
            'token_type' => 'Bearer',
            'expires_in' => 3600,
            'scopes' => $scopes,
        ], 'Token rafraîchi avec succès');

        return $response->withCookie($accessCookie);
    }

    /**
     * @OA\Post(
     *     path="/api/v1/auth/logout",
     *     summary="Déconnexion utilisateur",
     *     description="Invalide le token d'accès actuel de l'utilisateur",
     *     operationId="logout",
     *     tags={"Authentification"},
     *     security={{"passport": {"*"}}},
     *     @OA\Response(
     *         response=200,
     *         description="Déconnexion réussie",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=true),
     *             @OA\Property(property="message", type="string", example="Déconnexion réussie")
     *         )
     *     ),
     *     @OA\Response(
     *         response=401,
     *         description="Non authentifié",
     *         @OA\JsonContent(
     *             @OA\Property(property="success", type="boolean", example=false),
     *             @OA\Property(property="message", type="string", example="Non authentifié")
     *         )
     *     )
     * )
     */
    public function logout(Request $request): JsonResponse
    {
        $user = $request->user();

        // Révoquer le token actuel
        $request->user()->token()->revoke();

        // Supprimer les cookies
        $accessCookie = Cookie::forget('access_token');
        $refreshCookie = Cookie::forget('refresh_token');

        Log::info('Déconnexion utilisateur', [
            'user_id' => $user->id,
            'email' => $user->email,
            'ip' => $request->ip(),
        ]);

        $response = $this->successResponse(null, 'Déconnexion réussie');
        return $response->withCookie($accessCookie)->withCookie($refreshCookie);
    }

    /**
     * Détermine les scopes selon le rôle de l'utilisateur
     *
     * @param string $role
     * @return array
     */
    private function getScopesForRole(string $role): array
    {
        return match ($role) {
            'admin' => [
                'read-comptes',
                'write-comptes',
                'read-transactions',
                'write-transactions',
                'read-clients',
                'write-clients',
                'admin-access',
            ],
            'client' => [
                'read-comptes',
                'read-transactions',
                'read-clients',
            ],
            default => [],
        };
    }
}
