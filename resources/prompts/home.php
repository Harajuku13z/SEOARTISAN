<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

/**
 * @param array<string,mixed> $context
 * @return array{system:string,user:string,expected_keys:array<int,string>}
 */
return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $tone = $context['tone'] ?? 'professionnel et rassurant';
    $language = $context['language'] ?? 'fr';
    $keywords = implode(', ', (array) ($context['keywords_primary'] ?? []));

    $system = "Tu es redacteur web SEO specialise pour des artisans du batiment, en langue {$language}. "
        . "Ton redactionnel : {$tone}. {$guardrails}";

    $user = "Redige le contenu de la PAGE D'ACCUEIL du site de cette entreprise.\n\n"
        . "=== Informations reelles sur l'entreprise ===\n" . ContextFormatter::companyBlock($company) . "\n\n"
        . "Mots-cles principaux : {$keywords}\n\n"
        . "Objectif de la page : convertir un visiteur en demande de devis, rassurer sur le professionnalisme, "
        . "presenter clairement les services et la zone d'intervention.\n\n"
        . 'Reponds STRICTEMENT avec ce JSON (sections: 3 a 5 blocs) : '
        . '{"title":"","slug":"","h1":"","meta_title":"","meta_description":"","introduction":"",'
        . '"sections":[{"heading":"","content":""}],"faq":[{"question":"","answer":""}],"cta_title":"","cta_text":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['title', 'slug', 'h1', 'meta_title', 'meta_description', 'introduction', 'sections', 'faq', 'cta_title', 'cta_text'],
    ];
};
