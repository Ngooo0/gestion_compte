<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class AuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Vérifie que l'utilisateur est authentifié via Passport
     * Supporte l'authentification via token Bearer ou cookie
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Vérifier si l'utilisateur est authentifié
        if (!Auth::guard('api')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Non authentifié - Token d\'accès requis',
                'error' => [
                    'code' => 'UNAUTHENTICATED',
                    'message' => 'Vous devez être connecté pour accéder à cette ressource'
                ]
            ], 401);
        }

        return $next($request);
    }
}
