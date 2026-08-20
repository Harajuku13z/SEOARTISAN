<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $service = $context['service'] ?? null;
    $language = $context['language'] ?? 'fr';
    $count = (int) ($context['count'] ?? 6);

    $system = "Tu es redacteur web SEO specialise pour des artisans du batiment, en langue {$language}. {$guardrails}";

    $user = "Redige {$count} questions/reponses de FAQ pertinentes pour les visiteurs du site.\n\n"
        . "=== Informations reelles sur l'entreprise ===\n" . ContextFormatter::companyBlock($company) . "\n\n"
        . "=== Contexte du service (le cas echeant) ===\n" . ContextFormatter::serviceBlock($service) . "\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : {"faq":[{"question":"","answer":""}]}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['faq'],
    ];
};
