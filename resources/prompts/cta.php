<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $service = $context['service'] ?? null;
    $language = $context['language'] ?? 'fr';
    $goal = $context['page_goal'] ?? 'demande de devis';

    $system = "Tu rediges des appels a l'action courts et efficaces pour des artisans du batiment, en langue {$language}. {$guardrails}";

    $user = "Redige un titre d'appel a l'action et un court texte d'accompagnement pour un objectif de : {$goal}.\n\n"
        . "=== Informations reelles sur l'entreprise ===\n" . ContextFormatter::companyBlock($company) . "\n\n"
        . "=== Service concerne (le cas echeant) ===\n" . ContextFormatter::serviceBlock($service) . "\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : {"cta_title":"","cta_text":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['cta_title', 'cta_text'],
    ];
};
