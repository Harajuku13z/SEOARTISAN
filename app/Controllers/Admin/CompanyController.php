<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Company;
use App\Models\Media;
use App\Models\Setting;
use App\Services\Media\MediaUploadService;
use Throwable;

final class CompanyController extends AdminController
{
    private const DAYS = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];

    private const EDITORIAL_FIELDS = [
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

    public function __construct(\App\Services\Auth\AuthService $auth, private MediaUploadService $uploads)
    {
        parent::__construct($auth);
    }

    public function show(Request $request): Response
    {
        $company = Company::current();
        $partnerIds = json_decode((string)(Setting::first(['key' => 'branding.partner_logo_ids'])?->getAttribute('value') ?? '[]'), true) ?: [];
        $visualMedia = [];
        foreach (['logo_main_media_id','logo_light_media_id','logo_dark_media_id','favicon_media_id','hero_media_id','hero_mobile_media_id','og_media_id'] as $field) {
            $id = $company?->getAttribute($field);
            $visualMedia[$field] = $id ? Media::find((int)$id) : null;
        }
        $mailHtmlSetting = Setting::first(['key' => 'mail.notification_html']);
        $mailHtml = $mailHtmlSetting?->getAttribute('value') ?? '<!doctype html><html><body style="margin:0;background:#f3f6f8;font-family:Arial,sans-serif;color:#172833"><div style="max-width:680px;margin:30px auto;background:#fff;border-radius:16px;overflow:hidden"><div style="padding:26px;background:#0f4c5c;color:#fff"><h1 style="margin:0;font-size:24px">Nouvelle demande {{type}}</h1><p style="margin:8px 0 0">Prospect n°{{lead_id}} reçu depuis le site</p></div><table role="presentation" style="width:100%;border-collapse:collapse">{{table}}</table><div style="padding:22px"><a href="{{admin_link}}" style="display:inline-block;padding:13px 20px;background:#f59e0b;color:#0f4c5c;text-decoration:none;border-radius:8px;font-weight:bold">Voir dans l’administration</a></div></div></body></html>';
        return $this->render('admin.company', [
            'company' => $company,
            'days' => self::DAYS,
            'editorialFields' => self::EDITORIAL_FIELDS,
            'partnerLogos' => array_values(array_filter(array_map(static fn ($id) => Media::find((int)$id), $partnerIds))),
            'visualMedia' => $visualMedia,
            'mailHtml' => $mailHtml,
        ], 'company');
    }

    public function update(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/admin/company');
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

        $editorial = [];
        foreach (array_keys(self::EDITORIAL_FIELDS) as $field) {
            $editorial[$field] = trim((string) $request->input($field, '')) ?: null;
        }

        $company->fill(array_merge([
            'trade_name' => trim((string) $request->input('trade_name', $company->getAttribute('trade_name'))),
            'legal_name' => trim((string) $request->input('legal_name', '')) ?: null,
            'slogan' => trim((string) $request->input('slogan', '')) ?: null,
            'short_description' => trim((string) $request->input('short_description', '')) ?: null,
            'long_description' => trim((string) $request->input('long_description', '')) ?: null,
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
            'certifications' => array_values(array_filter(array_map('trim', explode(',', (string) $request->input('certifications', ''))))),
            'opening_hours' => $hours,
            'social_links' => $social,
            'gbp_url' => trim((string) $request->input('gbp_url', '')) ?: null,
            'legal_info' => trim((string) $request->input('legal_info', '')) ?: null,
            'service_radius_km' => $request->input('service_radius_km', '') !== '' ? (int) $request->input('service_radius_km') : null,
            'offers_emergency' => $request->input('offers_emergency') !== null,
            'offers_free_quote' => $request->input('offers_free_quote') !== null,
            'primary_color' => (string) $request->input('primary_color', $company->getAttribute('primary_color')),
            'secondary_color' => (string) $request->input('secondary_color', $company->getAttribute('secondary_color')),
            'accent_color' => (string) $request->input('accent_color', $company->getAttribute('accent_color')),
            'button_style' => (string) $request->input('button_style', $company->getAttribute('button_style')),
            'font_primary' => (string) $request->input('font_primary', $company->getAttribute('font_primary')),
            'font_secondary' => (string) $request->input('font_secondary', $company->getAttribute('font_secondary')),
            'theme_style' => (string) $request->input('theme_style', $company->getAttribute('theme_style')),
        ], $editorial));

        $uploadedBy = $this->auth->user()?->id();
        foreach ([
            'logo_main_media_id' => ['logo_main', 'logo'],
            'logo_light_media_id' => ['logo_light', 'logo_light'],
            'logo_dark_media_id' => ['logo_dark', 'logo_dark'],
            'favicon_media_id' => ['favicon', 'favicon'],
            'hero_media_id' => ['hero_image', 'hero'],
            'hero_mobile_media_id' => ['hero_mobile_image', 'hero_mobile'],
            'og_media_id' => ['og_image', 'og'],
        ] as $column => [$inputName, $mediaType]) {
            $file = $request->file($inputName);
            if ($file === null) {
                continue;
            }
            try {
                $media = $this->uploads->store($file, $mediaType, $uploadedBy);
                $company->setAttribute($column, $media->id());
            } catch (Throwable $e) {
                Session::flash('_errors', ['form' => $e->getMessage()]);

                return Response::redirect('/admin/company');
            }
        }

        $partnerIds = json_decode((string)(Setting::first(['key' => 'branding.partner_logo_ids'])?->getAttribute('value') ?? '[]'), true) ?: [];
        foreach (range(1, 6) as $slot) {
            $file = $request->file('partner_logo_' . $slot);
            if ($file === null) continue;
            try {
                $media = $this->uploads->store($file, 'other', $uploadedBy);
                $partnerIds[$slot - 1] = $media->id();
            } catch (Throwable $e) {
                Session::flash('_errors', ['form' => $e->getMessage()]);
                return Response::redirect('/admin/company');
            }
        }
        $partnerIds = array_values(array_filter($partnerIds));
        $partnerSetting = Setting::first(['key' => 'branding.partner_logo_ids']) ?? new Setting();
        $partnerSetting->fill(['key' => 'branding.partner_logo_ids', 'value' => json_encode($partnerIds), 'autoload' => 1])->save();

        $mailHtmlInput = trim((string) $request->input('mail_notification_html', ''));
        if ($mailHtmlInput !== '') {
            $mailHtmlSetting = Setting::first(['key' => 'mail.notification_html']) ?? new Setting();
            $mailHtmlSetting->fill(['key' => 'mail.notification_html', 'value' => $mailHtmlInput, 'autoload' => 0])->save();
        }

        $company->save();
        Cache::flush();
        $this->log('company.update', 'Company', $company->id(), "Mise a jour des informations de l'entreprise");
        Session::flash('success', 'Informations enregistrees.');

        return Response::redirect('/admin/company');
    }

    public function testEmail(Request $request): Response
    {
        $company = Company::current();
        $recipient = $company?->getAttribute('public_email') ?: config('mail.from.address', '');
        
        $mailHtmlSetting = trim((string) $request->input('mail_notification_html', ''));
        if (!$mailHtmlSetting) {
            $mailHtmlSetting = Setting::first(['key' => 'mail.notification_html'])?->getAttribute('value');
        }
        if (!$mailHtmlSetting) {
            $mailHtmlSetting = '<!doctype html><html lang="fr"><body style="font-family:Arial,sans-serif;background:#f4f7f6;color:#27313a"><div style="max-width:640px;margin:30px auto;background:#fff;padding:32px"><h1>Nouvelle demande {{type}}</h1><p>Prospect n°{{lead_id}} reçu depuis le site web.</p><table style="width:100%;border-collapse:collapse">{{table}}</table><p><a href="{{admin_link}}">Voir dans l’administration</a></p><p>Cet e-mail a été envoyé automatiquement par {{company_name}}.</p></div></body></html>';
        }
        
        $html = str_replace(
            ['{{type}}', '{{lead_id}}', '{{table}}', '{{admin_link}}', '{{company_name}}'],
            ['TEST', '9999', '<tr><th style="padding:10px;text-align:left;background:#f3f6f8;border-bottom:1px solid #dde3e8;width:180px">Nom</th><td style="padding:10px;border-bottom:1px solid #dde3e8;">Test Client</td></tr><tr><th style="padding:10px;text-align:left;background:#f3f6f8;">Message</th><td style="padding:10px;">Ceci est un e-mail de test.</td></tr>', rtrim((string)config('app.url'), '/').'/admin/leads', (string)($company?->getAttribute('trade_name')?:config('app.name'))],
            $mailHtmlSetting
        );

        try {
            (new \App\Services\Mail\SmtpMailer())->sendHtml((string)$recipient, 'Test de notification - '.(string)config('app.name'), $html);
            Session::flash('success', 'E-mail de test envoyé à ' . $recipient . ' !');
        } catch (\Throwable $e) {
            Session::flash('_errors', ['form' => 'Erreur lors de l\'envoi du test : ' . $e->getMessage()]);
        }
        
        return Response::redirect('/admin/company#emails');
    }
}
