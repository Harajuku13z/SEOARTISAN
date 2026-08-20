<?php

declare(strict_types=1);

namespace App\Controllers\Installer;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Company;
use App\Services\Auth\AuthService;
use App\Services\Media\MediaUploadService;
use Throwable;

final class BrandingController
{
    private const STYLES = [
        'classique' => 'Professionnel classique',
        'moderne' => 'Moderne',
        'premium' => 'Premium',
        'artisanal' => 'Artisanal',
        'ecologique' => 'Ecologique',
        'industriel' => 'Industriel',
    ];

    private const FONTS = ['Manrope', 'Public Sans', 'Inter', 'Poppins', 'Merriweather', 'Roboto Slab'];

    public function __construct(
        private MediaUploadService $uploads,
        private AuthService $auth
    ) {
    }

    public function show(Request $request): Response
    {
        return Response::html(view_layout('installer.layout', 'installer.branding', [
            'stepKey' => 'branding',
            'company' => Company::current(),
            'styles' => self::STYLES,
            'fonts' => self::FONTS,
        ]));
    }

    public function store(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/install/company');
        }

        $uploadedBy = $this->auth->user()?->id();
        // [db column => [request field name, media 'type' enum value]]
        $fields = [
            'logo_main_media_id' => ['logo_main', 'logo'],
            'logo_light_media_id' => ['logo_light', 'logo_light'],
            'logo_dark_media_id' => ['logo_dark', 'logo_dark'],
            'favicon_media_id' => ['favicon', 'favicon'],
            'hero_media_id' => ['hero_image', 'hero'],
            'og_media_id' => ['og_image', 'og'],
        ];

        $updates = [];
        foreach ($fields as $column => [$inputName, $mediaType]) {
            $file = $request->file($inputName);
            if ($file === null) {
                continue;
            }
            try {
                $media = $this->uploads->store($file, $mediaType, $uploadedBy);
                $updates[$column] = $media->id();
            } catch (Throwable $e) {
                Session::flash('_errors', ['form' => $e->getMessage()]);

                return Response::redirect('/install/branding');
            }
        }

        $updates['primary_color'] = (string) $request->input('primary_color', '#1f2430');
        $updates['secondary_color'] = (string) $request->input('secondary_color', '#2f6fed');
        $updates['accent_color'] = (string) $request->input('accent_color', '#e8a53d');
        $updates['button_style'] = (string) $request->input('button_style', 'rounded');
        $updates['font_primary'] = (string) $request->input('font_primary', 'Manrope');
        $updates['font_secondary'] = (string) $request->input('font_secondary', 'Public Sans');
        $updates['theme_style'] = (string) $request->input('theme_style', 'moderne');

        $company->fill($updates);
        $company->save();

        return Response::redirect('/install/business');
    }
}
