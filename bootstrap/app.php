<?php

use App\Http\Middleware\EnsureUnlocked;
use App\Http\Middleware\HandleInertiaRequests;
use App\Http\Middleware\SecurityHeaders;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Symfony\Component\HttpFoundation\Response;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        health: '/up',
    )
    ->withMiddleware(function (Middleware $middleware): void {
        $middleware->append(SecurityHeaders::class);

        $middleware->web(append: [
            HandleInertiaRequests::class,
            EnsureUnlocked::class,
        ]);

        // O bloqueio vem antes de resolver as rotas: bloqueado, nem o 404 de uma conversa aparece.
        $middleware->prependToPriorityList(SubstituteBindings::class, EnsureUnlocked::class);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*') || $request->expectsJson(),
        );

        // Mensagens padrão do framework (em inglês) viram texto em português para a tela.
        $exceptions->respond(function (Response $response) {
            if (! $response instanceof JsonResponse || config('app.debug') && $response->getStatusCode() >= 500) {
                return $response;
            }

            $data = $response->getData(true);
            $message = is_array($data) ? (string) ($data['message'] ?? '') : '';
            $english = $message === '' || preg_match('/^(Not Found|No query results|CSRF token mismatch|Server Error|Method Not Allowed|The \w+ method is not supported|Too Many Attempts|Service Unavailable|Unauthenticated|Forbidden|This action is unauthorized)/', $message);
            $portuguese = [
                403 => 'Acesso negado.',
                404 => 'Não encontrado. O item pode ter sido excluído do ZapVault.',
                405 => 'Operação não permitida.',
                419 => 'A sessão expirou. Recarregue a página e tente de novo.',
                429 => 'Muitos pedidos seguidos. Aguarde um instante e tente de novo.',
                500 => 'Algo deu errado no ZapVault. Tente de novo.',
                503 => 'O ZapVault está em manutenção. Tente de novo em instantes.',
            ][$response->getStatusCode()] ?? null;

            if ($english && $portuguese !== null) {
                $response->setData(['message' => $portuguese]);
            }

            return $response;
        });
    })->create();
