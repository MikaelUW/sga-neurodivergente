<?php

use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Auth\AuthenticationException;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        api: __DIR__.'/../routes/api.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->alias([
            'perfil' => \App\Http\Middleware\CheckPerfil::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions) {
        // Respostas JSON padronizadas para a API (sem vazar stack trace).
        $exceptions->render(function (\Throwable $e, Request $request) {
            if (! $request->is('api/*') && ! $request->expectsJson()) {
                return null; // deixa o comportamento padrão para rotas web
            }

            if ($e instanceof ValidationException) {
                return response()->json([
                    'message' => 'Os dados informados são inválidos.',
                    'errors' => $e->errors(),
                ], 422);
            }

            if ($e instanceof AuthenticationException) {
                return response()->json(['message' => 'Não autenticado.'], 401);
            }

            if ($e instanceof AuthorizationException) {
                return response()->json(['message' => 'Acesso negado.'], 403);
            }

            if ($e instanceof ModelNotFoundException || $e instanceof NotFoundHttpException) {
                return response()->json(['message' => 'Recurso não encontrado.'], 404);
            }

            if ($e instanceof HttpExceptionInterface) {
                return response()->json([
                    'message' => $e->getMessage() ?: 'Erro na requisição.',
                ], $e->getStatusCode());
            }

            // Erro inesperado: detalhes só quando APP_DEBUG=true.
            $payload = ['message' => 'Erro interno do servidor.'];
            if (config('app.debug')) {
                $payload['exception'] = get_class($e);
                $payload['detail'] = $e->getMessage();
            }
            return response()->json($payload, 500);
        });
    })
    ->booted(function () {
        // Rate limiters nomeados (registrados após o boot, quando os
        // facades já estão disponíveis).
        RateLimiter::for('login', function (Request $request) {
            // 10 tentativas por minuto por IP + login informado.
            $chave = \Illuminate\Support\Str::lower((string) $request->input('login')).'|'.$request->ip();
            return [Limit::perMinute(10)->by($chave)];
        });

        RateLimiter::for('api', function (Request $request) {
            // 120 req/min por usuário autenticado (ou IP, se anônimo).
            return [Limit::perMinute(120)->by(optional($request->user())->id ?: $request->ip())];
        });
    })->create();
