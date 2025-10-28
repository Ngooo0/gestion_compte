<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class ApiLoggingMiddleware
{
    /**
     * Handle an incoming request.
     *
     * Journalise toutes les opérations effectuées sur les ressources de l'API
     * pour audit et traçabilité
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $user = Auth::guard('api')->user();

        // Log de la requête entrante
        Log::info('API Request Started', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'user_id' => $user ? $user->id : null,
            'user_email' => $user ? $user->email : null,
            'user_role' => $user ? $user->role : null,
            'route' => $request->route() ? $request->route()->getName() : null,
            'parameters' => $this->sanitizeParameters($request->all()),
            'headers' => $this->sanitizeHeaders($request->headers->all()),
        ]);

        $response = $next($request);

        $endTime = microtime(true);
        $duration = round(($endTime - $startTime) * 1000, 2); // en millisecondes

        // Log de la réponse
        $logLevel = $this->getLogLevel($response->getStatusCode());

        Log::$logLevel('API Request Completed', [
            'method' => $request->method(),
            'url' => $request->fullUrl(),
            'status_code' => $response->getStatusCode(),
            'duration_ms' => $duration,
            'user_id' => $user ? $user->id : null,
            'response_size' => strlen($response->getContent()),
            'memory_usage' => memory_get_peak_usage(true),
        ]);

        return $response;
    }

    /**
     * Sanitize les paramètres sensibles de la requête
     */
    private function sanitizeParameters(array $parameters): array
    {
        $sensitiveKeys = ['password', 'password_confirmation', 'token', 'secret', 'key'];

        $sanitized = [];
        foreach ($parameters as $key => $value) {
            if (in_array(strtolower($key), $sensitiveKeys)) {
                $sanitized[$key] = '[REDACTED]';
            } elseif (is_array($value)) {
                $sanitized[$key] = $this->sanitizeParameters($value);
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Sanitize les headers sensibles
     */
    private function sanitizeHeaders(array $headers): array
    {
        $sensitiveHeaders = ['authorization', 'cookie', 'x-api-key'];

        $sanitized = [];
        foreach ($headers as $key => $value) {
            if (in_array(strtolower($key), $sensitiveHeaders)) {
                $sanitized[$key] = ['[REDACTED]'];
            } else {
                $sanitized[$key] = $value;
            }
        }

        return $sanitized;
    }

    /**
     * Détermine le niveau de log selon le code de statut HTTP
     */
    private function getLogLevel(int $statusCode): string
    {
        if ($statusCode >= 500) {
            return 'error';
        } elseif ($statusCode >= 400) {
            return 'warning';
        } elseif ($statusCode >= 300) {
            return 'info';
        } else {
            return 'info';
        }
    }
}
