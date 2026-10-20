<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

/**
 * Senha, bloqueio e backup chegam na tarefa 09; até lá as rotas existem e respondem 501.
 */
class PendingController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json(['message' => 'Recurso ainda não disponível nesta versão.'], 501);
    }
}
