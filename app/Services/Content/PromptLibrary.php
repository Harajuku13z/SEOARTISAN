<?php

declare(strict_types=1);

namespace App\Services\Content;

use RuntimeException;

/**
 * Loads resources/prompts/{type}.php - each file returns a closure
 * building [system, user, expected_keys] from a context array. Keeps the
 * prompt text itself out of PHP business logic, same spirit as views.
 */
final class PromptLibrary
{
    /** @param array<string,string> $config */
    public function __construct(private array $config)
    {
    }

    /**
     * @param array<string,mixed> $context
     * @return array{system:string,user:string,expected_keys:array<int,string>}
     */
    public function build(string $type, array $context): array
    {
        $path = resource_path("prompts/{$type}.php");
        if (!is_file($path)) {
            throw new RuntimeException("Prompt inconnu : {$type}");
        }

        /** @var callable $factory */
        $factory = require $path;

        $result = $factory($context, $this->guardrails());

        return [
            'system' => (string) $result['system'],
            'user' => (string) $result['user'],
            'expected_keys' => (array) ($result['expected_keys'] ?? []),
        ];
    }

    /**
     * The fixed anti-hallucination rule set (prompt.md section 10),
     * embedded verbatim in every system prompt - not per-template.
     */
    public function guardrails(): string
    {
        $forbidden = implode(', ', (array) ($this->config['forbidden_inventions'] ?? []));

        return "Regles strictes : tu n'utilises QUE les informations reelles fournies dans le contexte ci-dessous. "
            . "Tu n'inventes JAMAIS les elements suivants s'ils ne sont pas explicitement fournis : {$forbidden}. "
            . "Si une information necessaire n'est pas fournie, reste general ou indique qu'une validation humaine est requise, "
            . "mais ne complete jamais avec des donnees plausibles mais non verifiees. "
            . 'Tu reponds uniquement avec un objet JSON valide, sans texte avant ni apres, sans balises markdown.';
    }
}
