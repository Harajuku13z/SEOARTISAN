<?php

declare(strict_types=1);

use App\Services\Content\ContextFormatter;

return function (array $context, string $guardrails): array {
    $company = $context['company'] ?? [];
    $pageTopic = $context['page_topic'] ?? '';
    $language = $context['language'] ?? 'fr';
    $keywords = implode(', ', (array) ($context['keywords_primary'] ?? []));

    $system = "Tu es specialiste SEO pour des artisans du batiment, en langue {$language}. {$guardrails}";

    $user = "Redige un titre SEO (balise title, 55-60 caracteres) et une meta description (140-155 caracteres) "
        . "pour une page sur : {$pageTopic}.\n\n"
        . "=== Informations reelles sur l'entreprise ===\n" . ContextFormatter::companyBlock($company) . "\n\n"
        . "Mots-cles principaux : {$keywords}\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : {"meta_title":"","meta_description":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['meta_title', 'meta_description'],
    ];
};
