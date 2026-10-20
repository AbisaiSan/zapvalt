<?php

namespace App\Http\Controllers\Api;

use App\Archive\BackupWriter;
use App\Http\Controllers\Controller;
use App\Import\ImportService;
use App\Models\Setting;
use App\Support\TextNormalizer;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SettingsController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json($this->payload());
    }

    public function update(Request $request, ImportService $imports): JsonResponse
    {
        $data = $request->validate([
            'user_names' => ['sometimes', 'array', 'max:20'],
            'user_names.*' => ['nullable', 'string', 'max:120'],
            'theme' => ['sometimes', Rule::in(['light', 'dark'])],
        ], [
            'theme.in' => 'Tema inválido.',
            'user_names.*.max' => 'Use no máximo 120 caracteres em cada nome.',
        ]);

        if (array_key_exists('user_names', $data)) {
            $names = [];
            foreach ($data['user_names'] as $name) {
                $name = trim((string) $name);
                $key = TextNormalizer::normalize($name);
                if ($name !== '' && ! isset($names[$key])) {
                    $names[$key] = $name;
                }
            }
            Setting::put('user_names', array_values($names));
            $imports->refreshIsMe();
        }

        if (array_key_exists('theme', $data)) {
            Setting::put('theme', $data['theme']);
        }

        return response()->json($this->payload());
    }

    /** @return array<string, mixed> */
    private function payload(): array
    {
        return [
            'user_names' => Setting::userNames(),
            'theme' => Setting::get('theme', 'light'),
            'data_path' => config('zapvault.data_path'),
            'has_password' => Setting::get('password_hash') !== null,
            'last_backup' => Setting::get('last_backup'),
            'current_backup' => BackupWriter::active()?->toApi(),
        ];
    }
}
