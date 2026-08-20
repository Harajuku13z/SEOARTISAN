<?php

declare(strict_types=1);

return [
    // Providers available in the installer / admin. The actual active
    // configuration (key, model, temperature...) lives in the ai_providers
    // table - this file only holds static, non-secret provider metadata.
    'providers' => [
        'openai' => [
            'label' => 'OpenAI',
            'default_base_url' => 'https://api.openai.com/v1',
            'default_model' => 'gpt-4.1-mini',
        ],
        'anthropic' => [
            'label' => 'Anthropic Claude',
            'default_base_url' => 'https://api.anthropic.com/v1',
            'default_model' => 'claude-sonnet-5',
        ],
        'gemini' => [
            'label' => 'Gemini AI Studio (offre gratuite)',
            'default_base_url' => 'https://generativelanguage.googleapis.com/v1beta',
            'default_model' => 'gemini-2.5-flash',
        ],
        'compatible' => [
            'label' => 'Fournisseur compatible API OpenAI',
            'default_base_url' => '',
            'default_model' => '',
        ],
        'none' => [
            'label' => 'Aucun fournisseur (redaction manuelle)',
            'default_base_url' => '',
            'default_model' => '',
        ],
    ],

    'default_temperature' => 0.6,
    'default_max_tokens' => 2000,
    'default_language' => 'fr',
    'default_tone' => 'professionnel et rassurant',

    // Rough $ per 1M tokens (input/output), used only to show an ESTIMATE
    // in the admin. Not billing-accurate - update as pricing changes.
    'estimated_cost_per_million_tokens' => [
        'gpt-4.1-mini' => ['input' => 0.40, 'output' => 1.60],
        'gpt-4.1' => ['input' => 2.00, 'output' => 8.00],
        'claude-sonnet-5' => ['input' => 3.00, 'output' => 15.00],
        'claude-haiku-4-5' => ['input' => 0.80, 'output' => 4.00],
        'gemini-2.5-flash' => ['input' => 0.30, 'output' => 2.50],
    ],

    // Facts the AI is never allowed to invent (prompt.md section 10).
    // Embedded verbatim in every system prompt by AIContentService.
    'forbidden_inventions' => [
        'certifications',
        'labels',
        'garanties',
        'prix',
        'annees d\'experience',
        'avis clients',
        'statistiques',
        'adresses',
        'noms de collaborateurs',
        'partenariats',
        'delais',
        'marques utilisees',
    ],
];
