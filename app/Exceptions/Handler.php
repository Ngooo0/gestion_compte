<?php

namespace App\Exceptions;

use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Support\Facades\Route;
use Throwable;

class Handler extends ExceptionHandler
{
    /**
     * The list of the inputs that are never flashed to the session on validation exceptions.
     *
     * @var array<int, string>
     */
    protected $dontFlash = [
        'current_password',
        'password',
        'password_confirmation',
    ];

    /**
     * Register the exception handling callbacks for the application.
     */
    public function register(): void
    {
        $this->reportable(function (Throwable $e) {
            //
        });

        $this->renderable(function (\Illuminate\Auth\AuthenticationException $e, $request) {
            if ($request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated.'], 401);
            }
        });
    }

    /**
     * Convert an authentication exception into an unauthenticated response.
     */
    protected function unauthenticated($request, AuthenticationException $exception)
    {
        // If the client expects JSON, return a JSON response.
        if ($request->expectsJson()) {
            return response()->json([
                'message' => $exception->getMessage() ?: 'Unauthenticated.',
                'error' => 'unauthenticated'
            ], 401);
        }

        // For web requests, redirect to the named login route if it exists,
        // otherwise fall back to a hard-coded /login URL to avoid throwing
        // a RouteNotFoundException when the 'login' route is not defined.
        $loginUrl = Route::has('login') ? route('login') : url('/login');

        return redirect()->guest($loginUrl);
    }
}
