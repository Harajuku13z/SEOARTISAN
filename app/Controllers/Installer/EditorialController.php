<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Models\Company;

final class EditorialController
{
    private const FIELDS = [
        'editorial_presentation' => 'Presentation de l\'entreprise',
        'editorial_history' => 'Histoire',
        'editorial_experience' => 'Experience',
        'editorial_values' => 'Valeurs',
        'editorial_work_method' => 'Methodes de travail',
        'editorial_advantages' => 'Avantages',
        'editorial_guarantees' => 'Garanties',
        'editorial_client_types' => 'Types de clients',
        'editorial_achievements' => 'Principales realisations',
        'editorial_brands_used' => 'Marques utilisees',
        'editorial_typical_delays' => 'Delais habituels',
        'editorial_commitments' => 'Engagements',
        'editorial_differentiators' => 'Elements differenciants',
        'editorial_priority_areas' => "Zones d'intervention prioritaires",
    ];

    public function show(Request $request): Response
    {
        return Response::html(view_layout('installer.layout', 'installer.editorial', [
            'stepKey' => 'editorial',
            'fields' => self::FIELDS,
            'company' => Company::current(),
        ]));
    }

    public function store(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/install/company');
        }

        $updates = [];
        foreach (array_keys(self::FIELDS) as $field) {
            $updates[$field] = trim((string) $request->input($field, '')) ?: null;
        }

        $company->fill($updates);
        $company->save();

        return Response::redirect('/install/generate');
    }
}
