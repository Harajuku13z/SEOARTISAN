<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIGenerationResult;

final class GeminiProvider implements AIProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model,
        private string $baseUrl = 'https://generativelanguage.googleapis.com/v1beta'
    ) {
    }

    public function name(): string
    {
        return 'gemini';
    }

    public function generate(string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): AIGenerationResult
    {
        $path = '/models/' . rawurlencode($this->model) . ':generateContent';
        [$status, $body, $error] = $this->post($path, [
            'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
            'contents' => [[
                'role' => 'user',
                'parts' => [['text' => $userPrompt]],
            ]],
            'generationConfig' => [
                'temperature' => $temperature,
                'maxOutputTokens' => $maxTokens,
                'responseMimeType' => 'application/json',
            ],
        ]);

        if ($error !== null) {
            return AIGenerationResult::fail($error);
        }

        $decoded = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            $apiMessage = is_array($decoded) ? ($decoded['error']['message'] ?? null) : null;
            return AIGenerationResult::fail('Gemini a répondu avec le statut ' . $status . ' : ' . ($apiMessage ?: substr((string) $body, 0, 300)));
        }

        $content = $decoded['candidates'][0]['content']['parts'][0]['text'] ?? null;
        if (!is_string($content)) {
            return AIGenerationResult::fail('Réponse Gemini sans contenu exploitable.');
        }

        return AIGenerationResult::ok($content, (int) ($decoded['usageMetadata']['totalTokenCount'] ?? 0));
    }

    public function testConnection(): array
    {
        $result = $this->generate(
            'Tu réponds uniquement en JSON valide.',
            'Réponds avec exactement ce JSON : {"status":"ok"}',
            0,
            30
        );

        return $result->success
            ? ['ok' => true, 'message' => 'Connexion Gemini AI Studio réussie.']
            : ['ok' => false, 'message' => $result->errorMessage ?? 'Échec inconnu.'];
    }

    /** @param array<string,mixed> $payload @return array{0:int,1:?string,2:?string} */
    private function post(string $path, array $payload): array
    {
        $ch = curl_init(rtrim($this->baseUrl, '/') . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-goog-api-key: ' . $this->apiKey,
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return [0, null, "Erreur réseau : {$curlError}"];
        }

        return [$status, is_string($body) ? $body : null, null];
    }
}
