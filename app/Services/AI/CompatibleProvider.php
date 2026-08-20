<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIGenerationResult;

/**
 * Any provider exposing an OpenAI-compatible /chat/completions endpoint
 * (self-hosted models, third-party gateways...). Same request/response
 * shape as OpenAIProvider, but against a user-supplied base URL and
 * without assuming response_format is honored.
 */
final class CompatibleProvider implements AIProviderInterface
{
    public function __construct(
        private string $apiKey,
        private string $model,
        private string $baseUrl
    ) {
    }

    public function name(): string
    {
        return 'compatible';
    }

    public function generate(string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): AIGenerationResult
    {
        if (trim($this->baseUrl) === '') {
            return AIGenerationResult::fail("URL de l'API compatible non configuree.");
        }

        [$status, $body, $error] = $this->post('/chat/completions', [
            'model' => $this->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userPrompt],
            ],
            'temperature' => $temperature,
            'max_tokens' => $maxTokens,
        ]);

        if ($error !== null) {
            return AIGenerationResult::fail($error);
        }

        $decoded = json_decode((string) $body, true);
        if ($status < 200 || $status >= 300 || !is_array($decoded)) {
            return AIGenerationResult::fail("Le fournisseur compatible a repondu avec le statut {$status} : " . substr((string) $body, 0, 300));
        }

        $content = $decoded['choices'][0]['message']['content'] ?? null;
        if (!is_string($content)) {
            return AIGenerationResult::fail('Reponse du fournisseur compatible sans contenu exploitable.');
        }

        $tokens = (int) ($decoded['usage']['total_tokens'] ?? 0);

        return AIGenerationResult::ok($content, $tokens);
    }

    public function testConnection(): array
    {
        $result = $this->generate(
            'Tu reponds uniquement en JSON valide.',
            'Reponds avec exactement ce JSON : {"status":"ok"}',
            0,
            30
        );

        if (!$result->success) {
            return ['ok' => false, 'message' => $result->errorMessage ?? 'Echec inconnu.'];
        }

        return ['ok' => true, 'message' => 'Connexion au fournisseur compatible reussie.'];
    }

    /**
     * @param array<string,mixed> $payload
     * @return array{0:int,1:?string,2:?string}
     */
    private function post(string $path, array $payload): array
    {
        $ch = curl_init(rtrim($this->baseUrl, '/') . $path);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => array_filter([
                'Content-Type: application/json',
                $this->apiKey !== '' ? ('Authorization: Bearer ' . $this->apiKey) : null,
            ]),
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
        ]);

        $body = curl_exec($ch);
        $errno = curl_errno($ch);
        $curlError = curl_error($ch);
        $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($errno !== 0) {
            return [0, null, "Erreur reseau : {$curlError}"];
        }

        return [$status, is_string($body) ? $body : null, null];
    }
}
