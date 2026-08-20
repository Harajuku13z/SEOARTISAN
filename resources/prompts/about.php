<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $tone = $context['tone'] ?? 'professionnel et rassurant';
    $language = $context['language'] ?? 'fr';

    $system = "Tu es redacteur web SEO specialise pour des artisans du batiment, en langue {$language}. "
        . "Ton redactionnel : {$tone}. {$guardrails}";

    $user = "Redige le contenu de la PAGE A PROPOS de cette entreprise.\n\n"
        . "=== Informations reelles sur l'entreprise ===\n" . ContextFormatter::companyBlock($company) . "\n\n"
        . "Structure attendue : histoire, experience, valeurs, methode de travail, engagements, zone d'intervention. "
        . "N'invente ni equipe ni collaborateurs si non fournis.\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : '
        . '{"title":"","slug":"","h1":"","meta_title":"","meta_description":"","introduction":"",'
        . '"sections":[{"heading":"","content":""}],"faq":[{"question":"","answer":""}],"cta_title":"","cta_text":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['title', 'slug', 'h1', 'meta_title', 'meta_description', 'introduction', 'sections', 'faq', 'cta_title', 'cta_text'],
    ];
};
