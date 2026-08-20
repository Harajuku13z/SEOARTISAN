<?php

declare(strict_types=1);

return function (array $context, string $guardrails): array {
    $description = $context['image_description'] ?? '';
    $language = $context['language'] ?? 'fr';

    $system = "Tu rediges des textes alternatifs (attribut alt) concis et descriptifs pour l'accessibilite et le SEO, "
        . "en langue {$language}. {$guardrails}";

    $user = "Redige un texte alternatif (max 125 caracteres, descriptif, sans 'image de' ni 'photo de') pour cette image : "
        . "{$description}\n\n"
        . 'Reponds STRICTEMENT avec ce JSON : {"alt_text":""}';

    return [
        'system' => $system,
        'user' => $user,
        'expected_keys' => ['alt_text'],
    ];
};
