<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Support\AppLock;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

/**
 * Senha opcional: ativar, trocar, desativar, desbloquear e "Bloquear agora".
 */
class LockController extends Controller
{
    public function setPassword(Request $request): Response
    {
        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'max:200', 'confirmed'],
        ], [
            'password.required' => 'Digite a nova senha.',
            'password.min' => 'Use pelo menos 6 caracteres.',
            'password.max' => 'Use no máximo 200 caracteres.',
            'password.confirmed' => 'As senhas não coincidem.',
        ]);

        AppLock::setPassword($data['password'], $request->session());

        return response()->noContent();
    }

    public function removePassword(Request $request): Response|JsonResponse
    {
        if (! AppLock::enabled()) {
            return response()->noContent();
        }

        $refused = $this->checkPassword($request);
        if ($refused !== null) {
            return $refused;
        }

        AppLock::removePassword();

        return response()->noContent();
    }

    public function unlock(Request $request): Response|JsonResponse
    {
        if (! AppLock::enabled()) {
            return response()->noContent();
        }

        $refused = $this->checkPassword($request);
        if ($refused !== null) {
            return $refused;
        }

        AppLock::unlock($request->session());

        return response()->noContent();
    }

    public function lock(Request $request): Response
    {
        AppLock::lock($request->session());

        return response()->noContent();
    }

    private function checkPassword(Request $request): ?JsonResponse
    {
        $request->validate(['password' => ['required', 'string', 'max:200']], ['password.required' => 'Digite a senha.']);

        $result = AppLock::check((string) $request->input('password'));

        if ($result['ok']) {
            return null;
        }

        $wait = $result['retry_after'];
        $message = $wait > 0
            ? "Muitas tentativas erradas. Aguarde {$wait} segundos para tentar de novo."
            : 'Senha incorreta.';

        return response()->json([
            'message' => $message,
            'errors' => ['password' => [$message]],
            'retry_after' => $wait,
        ], 422);
    }
}
