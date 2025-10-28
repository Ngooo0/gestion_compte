<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que l'utilisateur a les permissions (scopes) nécessaires
     * pour accéder à la ressource demandée
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     * @param  string  $requiredScope  Scope requis pour accéder à la ressource
     */
    public function handle(Request $request, Closure $next, string $requiredScope): Response
    {
        $user = Auth::guard('api')->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Vous devez être connecté pour accéder à cette ressource'
                ]
            ], 401);
        }

        // Vérifier si l'utilisateur a le scope requis
        $token = $user->token();
        if (!$token || !$token->can($requiredScope)) {
            return response()->json([
                'success' => false,
                'message' => 'Accès refusé - Permissions insuffisantes',
                'error' => [
                    'code' => 'INSUFFICIENT_PERMISSIONS',
                    'message' => "Le scope '{$requiredScope}' est requis pour accéder à cette ressource",
                    'required_scope' => $requiredScope,
                    'user_scopes' => $token ? $token->scopes : []
                ]
            ], 403);
        }

        return $next($request);
    }
}
