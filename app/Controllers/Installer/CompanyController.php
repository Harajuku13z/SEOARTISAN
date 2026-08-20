<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Company;

final class CompanyController
{
    private const DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

    public function show(Request $request): Response
    {
        $company = Company::current();

        return Response::html(view_layout('installer.layout', 'installer.company', [
            'stepKey' => 'company',
            'company' => $company,
            'days' => self::DAYS,
        ]));
    }

    public function store(Request $request): Response
    {
        $tradeName = trim((string) $request->input('trade_name', ''));
        if ($tradeName === '') {
            Session::flash('_errors', ['form' => "Le nom commercial est obligatoire."]);

            return Response::redirect('/install/company');
        }

        $hours = [];
        foreach (self::DAYS as $day) {
            $value = trim((string) $request->input('hours_' . $day, ''));
            if ($value !== '') {
                $hours[$day] = $value;
            }
        }

        $social = array_filter([
            'facebook' => trim((string) $request->input('social_facebook', '')),
            'instagram' => trim((string) $request->input('social_instagram', '')),
            'linkedin' => trim((string) $request->input('social_linkedin', '')),
        ]);

        $certifications = array_values(array_filter(array_map('trim', explode(',', (string) $request->input('certifications', '')))));

        $company = Company::current() ?? new Company();
        $company->fill([
            'trade_name' => $tradeName,
            'legal_name' => trim((string) $request->input('legal_name', '')) ?: null,
            'slogan' => trim((string) $request->input('slogan', '')) ?: null,
            'short_description' => trim((string) $request->input('short_description', '')) ?: null,
            'long_description' => trim((string) $request->input('long_description', '')) ?: null,
            'founded_year' => $request->input('founded_year', '') !== '' ? (int) $request->input('founded_year') : null,
            'phone' => trim((string) $request->input('phone', '')) ?: null,
            'whatsapp' => trim((string) $request->input('whatsapp', '')) ?: null,
            'public_email' => trim((string) $request->input('public_email', '')) ?: null,
            'leads_email' => trim((string) $request->input('leads_email', '')) ?: null,
            'address' => trim((string) $request->input('address', '')) ?: null,
            'postal_code' => trim((string) $request->input('postal_code', '')) ?: null,
            'city' => trim((string) $request->input('city', '')) ?: null,
            'department' => trim((string) $request->input('department', '')) ?: null,
            'region' => trim((string) $request->input('region', '')) ?: null,
            'siret' => trim((string) $request->input('siret', '')) ?: null,
            'certifications' => $certifications,
            'opening_hours' => $hours,
            'social_links' => $social,
            'gbp_url' => trim((string) $request->input('gbp_url', '')) ?: null,
            'legal_info' => trim((string) $request->input('legal_info', '')) ?: null,
            'service_radius_km' => $request->input('service_radius_km', '') !== '' ? (int) $request->input('service_radius_km') : null,
            'offers_emergency' => $request->input('offers_emergency') !== null,
            'offers_free_quote' => $request->input('offers_free_quote') !== null,
        ]);
        $company->save();

        return Response::redirect('/install/branding');
    }
}
