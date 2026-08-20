<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Models\AiProvider;
use App\Services\AI\AIProviderFactory;
use App\Support\Crypto;

final class AiController
{
    public function show(Request $request): Response
    {
        return Response::html(view_layout('installer.layout', 'installer.ai', [
            'stepKey' => 'ai',
            'providers' => config('ai.providers'),
            'aiProvider' => AiProvider::active(),
        ]));
    }

    public function test(Request $request): Response
    {
        $provider = (string) $request->input('provider', 'none');
        if ($provider === 'none') {
            return Response::json(['ok' => true, 'message' => 'Mode redaction manuelle - aucune connexion necessaire.']);
        }

        $apiKey = trim((string) $request->input('api_key', ''));
        if ($apiKey === '') {
            $encrypted = (string) (AiProvider::active()?->getAttribute('api_key_encrypted') ?? '');
            $appKey = (string) config('app.key', '');
            if ($encrypted !== '' && $appKey !== '') {
                $apiKey = Crypto::decrypt($encrypted, $appKey) ?? '';
            }
        }
        if ($apiKey === '') {
            return Response::json(['ok' => false, 'message' => 'Aucune clé API disponible. Saisissez votre clé Gemini AI Studio puis enregistrez-la.']);
        }

        $ai = AIProviderFactory::fromRaw(
            $provider,
            $apiKey,
            (string) $request->input('model', ''),
            (string) $request->input('base_url', '')
        );

        return Response::json($ai->testConnection());
    }

    public function store(Request $request): Response
    {
        $provider = (string) $request->input('provider', 'none');
        $provider = AIProviderFactory::normalizeProvider(
            $provider,
            (string) $request->input('model', ''),
            (string) $request->input('base_url', '')
        );
        $apiKey = trim((string) $request->input('api_key', ''));

        $existing = AiProvider::active();
        $model = $existing ?? new AiProvider();

        $encrypted = $existing?->getAttribute('api_key_encrypted');
        if ($apiKey !== '') {
            $appKey = (string) config('app.key', '');
            $encrypted = $appKey !== '' ? Crypto::encrypt($apiKey, $appKey) : null;
        }

        $model->fill([
            'provider' => $provider,
            'api_key_encrypted' => $encrypted,
            'model' => trim((string) $request->input('model', '')) ?: null,
            'base_url' => trim((string) $request->input('base_url', '')) ?: null,
            'temperature' => (float) $request->input('temperature', config('ai.default_temperature')),
            'max_tokens' => (int) $request->input('max_tokens', config('ai.default_max_tokens')),
            'language' => trim((string) $request->input('language', 'fr')) ?: 'fr',
            'tone' => trim((string) $request->input('tone', '')) ?: (string) config('ai.default_tone'),
            'is_active' => true,
        ]);
        $model->save();

        return Response::redirect('/install/editorial');
    }
}
