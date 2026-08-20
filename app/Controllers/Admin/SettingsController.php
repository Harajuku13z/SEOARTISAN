<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Redirect;
use App\Models\SeoMetadata;

final class SettingsController extends AdminController
{
    public function show(Request $request): Response
    {
        return $this->render('admin.settings.show', [
            'seo' => SeoMetadata::current(),
            'redirects' => Redirect::all('id DESC'),
        ], 'settings');
    }

    public function update(Request $request): Response
    {
        $seo = SeoMetadata::current() ?? new SeoMetadata();
        $seo->fill([
            'site_name' => trim((string) $request->input('site_name', '')) ?: null,
            'default_title_pattern' => trim((string) $request->input('default_title_pattern', '')) ?: null,
            'default_meta_description' => trim((string) $request->input('default_meta_description', '')) ?: null,
            'gsc_verification_code' => trim((string) $request->input('gsc_verification_code', '')) ?: null,
        ]);
        $seo->save();

        $this->log('seo_settings.update');
        Session::flash('success', 'Reglages SEO enregistres.');

        return Response::redirect('/admin/settings');
    }

    public function storeRedirect(Request $request): Response
    {
        $from = trim((string) $request->input('from_path', ''));
        $to = trim((string) $request->input('to_path', ''));
        if ($from !== '' && $to !== '') {
            Redirect::create([
                'from_path' => $from,
                'to_path' => $to,
                'status_code' => (int) $request->input('status_code', 301),
                'is_active' => true,
            ]);
        }

        return Response::redirect('/admin/settings');
    }

    public function destroyRedirect(Request $request, array $params): Response
    {
        Redirect::find((int) $params['id'])?->delete();

        return Response::redirect('/admin/settings');
    }

    public function purgeCache(Request $request): Response
    {
        Cache::flush();
        $this->log('cache.purge');
        Session::flash('success', 'Cache du site vide.');

        return Response::redirect('/admin/settings');
    }
}
