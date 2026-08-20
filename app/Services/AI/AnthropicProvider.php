<?php

declare(strict_types=1);

namespace App\Services\AI;

use App\DTO\AIGenerationResult;

final class AnthropicProvider implements AIProviderInterface
{
    private const API_VERSION = '2023-06-01';

    public function __construct(
        private string $apiKey,
        private string $model,
        private string $baseUrl = 'https://api.anthropic.com/v1'
    ) {
    }

    public function name(): string
    {
        return 'anthropic';
    }

    public function generate(string $systemPrompt, string $userPrompt, float $temperature, int $maxTokens): AIGenerationResult
    {
        [$status, $body, $error] = $this->post('/messages', [
            'model' => $this->model,
            'system' => $systemPrompt,
            'messages' => [
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
            return AIGenerationResult::fail("Anthropic a repondu avec le statut {$status} : " . substr((string) $body, 0, 300));
        }

        $content = $decoded['content'][0]['text'] ?? null;
        if (!is_string($content)) {
            return AIGenerationResult::fail('Reponse Anthropic sans contenu exploitable.');
        }

        $tokens = (int) ($decoded['usage']['input_tokens'] ?? 0) + (int) ($decoded['usage']['output_tokens'] ?? 0);

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

        return ['ok' => true, 'message' => 'Connexion Anthropic reussie.'];
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
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'x-api-key: ' . $this->apiKey,
                'anthropic-version: ' . self::API_VERSION,
            ],
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
