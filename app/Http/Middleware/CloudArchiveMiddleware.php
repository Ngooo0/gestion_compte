<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour gérer l'accès aux comptes archivés depuis le cloud
 * La consultation de compte Epargne archiver se fait a partir du cloud
 */
class CloudArchiveMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $type = $request->query('type');
        $statut = $request->query('statut');

        // Vérifier si la requête concerne des comptes épargne archivés
        if ($type === 'epargne' && $statut === 'ferme') {
            // Log l'accès aux archives cloud
            Log::info('Accès aux comptes épargne archivés (Cloud)', [
                'user_id' => $user ? $user->id : null,
                'user_role' => $user ? $user->role : 'guest',
                'endpoint' => $request->path(),
                'ip' => $request->ip(),
                'timestamp' => now()->toISOString(),
                'cloud_provider' => 'AWS_S3', // Simulation
                'archive_location' => 's3://banque-archives/epargne/',
            ]);

            // Ici, on pourrait implémenter la logique pour récupérer depuis le cloud
            // Pour l'instant, on simule avec un message dans les logs
            Log::info('Récupération des données depuis le cloud simulée');
        }

        return $next($request);
    }
}
