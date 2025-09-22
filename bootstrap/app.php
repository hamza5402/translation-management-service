<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->group('api', [
            \App\Http\Middleware\ForceJsonResponse::class,
        ]);
    })

    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->render(function (\Throwable $e, $request) {
            
            if (! $request->is('api/*')) {
                return;
            }

            if ($request->is('api/v1/login') || $request->is('api/v1/register')) {
               
            } else {
                
                if (! auth('sanctum')->check()) {
                    return response()->json([
                        'message' => 'You are not logged in or unauthorized request.'
                    ], 401);
                }
            }

            if ($e instanceof \Illuminate\Validation\ValidationException) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'The given data was invalid.',
                    'errors'  => $e->errors(),
                ], 422);
            }

            if ($e instanceof \Illuminate\Auth\AuthenticationException) {
                return response()->json([
                    'message' => 'You are not logged in or unauthorized request.'
                ], 401);
            }

            
            if ($e instanceof \Illuminate\Auth\Access\AuthorizationException) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'This action is unauthorized.'
                ], 403);
            }

            
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\MethodNotAllowedHttpException) {
                return response()->json([
                    'message' => 'Method not allowed.'
                ], 405);
            }

            
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\NotFoundHttpException) {
                return response()->json([
                    'message' => 'Endpoint not found.'
                ], 404);
            }

            
            if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'HTTP error.'
                ], $e->getStatusCode());
            }

            
            if (method_exists($e, 'getStatusCode')) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Something went wrong.'
                ], $e->getStatusCode());
            }

            
            // return response()->json([
            //     'message' => 'Something went wrong.'
            // ], 500);
        });
    })





    ->create();
