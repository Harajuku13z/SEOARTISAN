<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $service = $context['service'] ?? null;
    $tone = $context['tone'] ?? 'professionnel et rassurant';
    $language = $context['language'] ?? 'fr';
    $keywords = implode(', ', (array) ($context['keywords_primary'] ?? []));

    $system = "Tu es redacteur web SEO specialise pour des artisans du batiment, en langue {$language}. "
        . "Ton redactionnel : {$tone}. {$guardrails}";

    $user = "Redige le contenu de la page de service '" . ($service['public_name'] ?? '') . "'.\n\n"
        . "=== Informations reelles sur l'entreprise ===\n" . ContextFormatter::companyBlock($company) . "\n\n"
        . "=== Service ===\n" . ContextFormatter::serviceBlock($service) . "\n\n"
        . "Mots-cles principaux : {$keywords}\n\n"
        . "Structure attendue : introduction, explication du service, problemes auxquels il repond, "
        . "principales etapes de l'intervention, avantages, situations necessitant l'intervention, engagements reels, FAQ specifique.\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : '
        . '{"title":"","slug":"","h1":"","meta_title":"","meta_description":"","introduction":"",'
        . '"sections":[{"heading":"","content":""}],"faq":[{"question":"","answer":""}],"cta_title":"","cta_text":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['title', 'slug', 'h1', 'meta_title', 'meta_description', 'introduction', 'sections', 'faq', 'cta_title', 'cta_text'],
    ];
};
