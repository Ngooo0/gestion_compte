<?php

namespace App\Http\Middleware;

use App\Traits\ApiResponseTrait;
use Illuminate\Auth\Middleware\Authenticate as Middleware;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class Authenticate extends Middleware
{
    use ApiResponseTrait;

    /**
     * Get the path the user should be redirected to when they are not authenticated.
     */
    protected function redirectTo(Request $request): ?string
    {
        return null;
    }

    /**
     * Handle an unauthenticated user for API requests.
     */
    protected function unauthenticated($request, array $guards): JsonResponse
    {
        return $this->errorResponse('Non authentifié - Token d\'accès requis', 401);
    }
}
