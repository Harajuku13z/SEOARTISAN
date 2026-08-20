<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $service = $context['service'] ?? null;
    $city = $context['city'] ?? null;
    $language = $context['language'] ?? 'fr';
    $rawNotes = $context['project_notes'] ?? '';

    $system = "Tu rediges de courtes descriptions de realisations (chantiers) pour des artisans du batiment, en langue {$language}. "
        . "{$guardrails} Tu ne decris que ce qui est fourni dans les notes du chantier - tu n'inventes ni delai, ni prix, ni details techniques non mentionnes.";

    $user = "Redige une description courte (2-4 phrases) de cette realisation, a partir des notes fournies par l'entreprise.\n\n"
        . "=== Notes du chantier (fournies par l'entreprise) ===\n{$rawNotes}\n\n"
        . "=== Contexte ===\n" . ContextFormatter::companyBlock($company) . "\n"
        . ContextFormatter::serviceBlock($service) . "\n"
        . ContextFormatter::cityBlock($city) . "\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : {"description":"","alt_text":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['description', 'alt_text'],
    ];
};
