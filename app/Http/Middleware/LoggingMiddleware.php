<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware pour logger les opérations importantes
 * Enregistre la date, heure, host, nom de l'opération et ressource
 */
class LoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);

        // Log de la requête entrante
        $this->logRequest($request);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log de la réponse
        $this->logResponse($request, $response, $duration);

        return $response;
    }

    /**
     * Log les informations de la requête
     *
     * @param Request $request
     * @return void
     */
    private function logRequest(Request $request): void
    {
        $operation = $this->getOperationName($request);

        Log::info('API Request', [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'method' => $request->getMethod(),
            'operation' => $operation,
            'endpoint' => $request->path(),
            'user_agent' => $request->userAgent(),
            'ip' => $request->ip(),
            'user_id' => $request->user()?->id,
            'resource' => $this->getResourceFromRequest($request),
        ]);
    }

    /**
     * Log les informations de la réponse
     *
     * @param Request $request
     * @param Response $response
     * @param float $duration
     * @return void
     */
    private function logResponse(Request $request, Response $response, float $duration): void
    {
        $operation = $this->getOperationName($request);

        Log::info('API Response', [
            'timestamp' => now()->toISOString(),
            'host' => $request->getHost(),
            'operation' => $operation,
            'endpoint' => $request->path(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $request->user()?->id,
            'resource' => $this->getResourceFromRequest($request),
        ]);
    }

    /**
     * Détermine le nom de l'opération à partir de la requête
     *
     * @param Request $request
     * @return string
     */
    private function getOperationName(Request $request): string
    {
        $method = $request->getMethod();
        $path = $request->path();

        // Extraction du nom de l'opération basé sur la route
        if (str_contains($path, 'comptes')) {
            switch ($method) {
                case 'GET':
                    return str_contains($path, 'comptes/') && !str_ends_with($path, 'comptes')
                        ? 'CONSULTATION_COMPTE'
                        : 'LISTE_COMPTES';
                case 'POST':
                    return 'CREATION_COMPTE';
                case 'PUT':
                case 'PATCH':
                    return 'MODIFICATION_COMPTE';
                case 'DELETE':
                    return 'SUPPRESSION_COMPTE';
            }
        }

        return strtoupper($method) . '_' . strtoupper(str_replace('/', '_', $path));
    }

    /**
     * Extrait l'identifiant de la ressource depuis la requête
     *
     * @param Request $request
     * @return string|null
     */
    private function getResourceFromRequest(Request $request): ?string
    {
        $path = $request->path();

        // Pour les routes avec ID (ex: /api/v1/comptes/{id})
        if (preg_match('/\/(\d+|[a-f0-9\-]{36})$/', $path, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
