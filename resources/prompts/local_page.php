<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

/**
 * Not called by any generator in this version (mass local-page generation
 * is phase 2 - prompt.md section 18). Kept ready so the future generator
 * only needs to call PromptLibrary::build('local_page', $context).
 */
return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $service = $context['service'] ?? null;
    $city = $context['city'] ?? null;
    $tone = $context['tone'] ?? 'professionnel et rassurant';
    $language = $context['language'] ?? 'fr';
    $localContext = (array) ($context['local_context'] ?? []);

    $system = "Tu es redacteur web SEO local specialise pour des artisans du batiment, en langue {$language}. "
        . "Ton redactionnel : {$tone}. {$guardrails} "
        . "Tu n'inventes jamais d'agence locale, d'adresse locale, de chantier precis, de delai d'intervention specifique, "
        . "de temoignage local ou de connaissance particuliere d'un quartier qui ne serait pas fournie explicitement.";

    $user = "Redige une page LOCALE combinant le service '" . ($service['public_name'] ?? '') . "' et la ville '" . ($city['name'] ?? '') . "'.\n\n"
        . "=== Informations reelles sur l'entreprise ===\n" . ContextFormatter::companyBlock($company) . "\n\n"
        . "=== Service ===\n" . ContextFormatter::serviceBlock($service) . "\n\n"
        . "=== Ville ===\n" . ContextFormatter::cityBlock($city) . "\n\n"
        . "=== Informations locales fournies et vérifiées ===\n"
        . ($localContext !== [] ? json_encode($localContext, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : "Aucune information locale complémentaire fournie.") . "\n\n"
        . "Cette page doit etre reellement differenciee des autres pages locales du meme service : angle redactionnel propre, "
        . "FAQ propre, informations geographiques exactes, contenu utile - jamais une simple duplication ou seul le nom de la ville change.\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : '
        . '{"title":"","slug":"","h1":"","meta_title":"","meta_description":"","introduction":"",'
        . '"sections":[{"heading":"","content":""}],"faq":[{"question":"","answer":""}],"cta_title":"","cta_text":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['title', 'slug', 'h1', 'meta_title', 'meta_description', 'introduction', 'sections', 'faq', 'cta_title', 'cta_text'],
    ];
};
