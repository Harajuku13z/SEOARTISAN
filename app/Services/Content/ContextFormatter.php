<?php

declare(strict_types=1);

namespace App\Services\Content;

/**
 * Renders the structured context arrays (company/service/city) into plain
 * text blocks for prompt bodies. Shared by every file in resources/prompts
 * so the "only real facts" framing stays consistent.
 */
final class ContextFormatter
{
    /** @param array<string,mixed> $company */
    public static function companyBlock(array $company): string
    {
        $lines = ["Nom commercial : " . ($company['trade_name'] ?? 'N/C')];

        $map = [
            'short_description' => 'Description courte',
            'long_description' => 'Description detaillee',
            'founded_year' => 'Annee de creation',
            'city' => 'Ville',
            'department' => 'Departement',
            'region' => 'Region',
            'service_radius_km' => 'Rayon de deplacement (km)',
            'offers_emergency' => 'Intervention urgente',
            'offers_free_quote' => 'Devis gratuit',
            'editorial_presentation' => 'Presentation',
            'editorial_history' => 'Histoire',
            'editorial_experience' => 'Experience',
            'editorial_values' => 'Valeurs',
            'editorial_work_method' => 'Methode de travail',
            'editorial_advantages' => 'Avantages',
            'editorial_guarantees' => 'Garanties reelles',
            'editorial_client_types' => 'Types de clients',
            'editorial_achievements' => 'Realisations principales',
            'editorial_brands_used' => 'Marques utilisees',
            'editorial_typical_delays' => 'Delais habituels',
            'editorial_commitments' => 'Engagements',
            'editorial_differentiators' => 'Elements differenciants',
            'editorial_priority_areas' => "Zones d'intervention prioritaires",
        ];

        foreach ($map as $key => $label) {
            $value = $company[$key] ?? null;
            if ($value === null || $value === '') {
                continue;
            }
            if (is_bool($value)) {
                $value = $value ? 'oui' : 'non';
            }
            $lines[] = "{$label} : {$value}";
        }

        if (!empty($company['certifications']) && is_array($company['certifications'])) {
            $lines[] = 'Certifications reelles : ' . implode(', ', $company['certifications']);
        }

        return implode("\n", $lines);
    }

    /** @param array<string,mixed>|null $service */
    public static function serviceBlock(?array $service): string
    {
        if ($service === null) {
            return 'Aucun service specifique (page generale).';
        }

        $lines = ['Nom du service : ' . ($service['public_name'] ?? $service['name'] ?? 'N/C')];
        if (!empty($service['description'])) {
            $lines[] = 'Description fournie : ' . $service['description'];
        }
        if (!empty($service['is_emergency'])) {
            $lines[] = 'Disponible en urgence : oui';
        }
        if (!empty($service['show_starting_price']) && !empty($service['starting_price'])) {
            $lines[] = 'Prix de depart affiche : ' . $service['starting_price'] . ' EUR';
        }

        return implode("\n", $lines);
    }

    /** @param array<string,mixed>|null $city */
    public static function cityBlock(?array $city): string
    {
        if ($city === null) {
            return 'Aucune ville specifique (page generale, non locale).';
        }

        $lines = ['Ville : ' . ($city['name'] ?? 'N/C')];
        if (!empty($city['department'])) {
            $lines[] = 'Departement : ' . $city['department'];
        }
        if (!empty($city['distance_km'])) {
            $lines[] = 'Distance depuis la ville principale : ' . $city['distance_km'] . ' km';
        }

        return implode("\n", $lines);
    }
}
