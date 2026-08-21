<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Redirect;
use App\Models\SeoMetadata;
use App\Models\Setting;

final class SettingsController extends AdminController
{
    public function show(Request $request): Response
    {
        return $this->render('admin.settings.show', [
            'seo' => SeoMetadata::current(),
            'redirects' => Redirect::all('id DESC'),
            'tracking' => $this->trackingSettings(),
        ], 'settings');
    }

    public function update(Request $request): Response
    {
        $section=(string)$request->input('settings_section','seo');
        $seo = SeoMetadata::current() ?? new SeoMetadata();
        if($section==='seo'){$seo->fill([
            'site_name' => trim((string) $request->input('site_name', '')) ?: null,
            'default_title_pattern' => trim((string) $request->input('default_title_pattern', '')) ?: null,
            'default_meta_description' => trim((string) $request->input('default_meta_description', '')) ?: null,
            'gsc_verification_code' => trim((string) $request->input('gsc_verification_code', '')) ?: null,
        ]);
        $seo->save();}

        if($section==='tracking'){$trackingFields=['tracking.enabled'=>'tracking_enabled','tracking.google_tag_id'=>'google_tag_id','tracking.google_call_label'=>'google_call_label','tracking.google_form_label'=>'google_form_label','tracking.human_min_seconds'=>'human_min_seconds','tracking.human_min_interactions'=>'human_min_interactions'];
        foreach($trackingFields as $key=>$field){$value=$key==='tracking.enabled'?(bool)$request->input($field,false):trim((string)$request->input($field,''));if(str_contains($key,'human_min_'))$value=max(1,(int)$value);$setting=Setting::first(['key'=>$key]);$stored=json_encode($value,JSON_UNESCAPED_UNICODE);if($setting){$setting->setAttribute('value',$stored);$setting->save();}else Setting::create(['key'=>$key,'value'=>$stored,'autoload'=>1]);}
        Cache::flush();}

        $this->log('seo_settings.update');
        Session::flash('success', $section==='tracking'?'Réglages de conversion enregistrés.':'Réglages SEO enregistrés.');

        return Response::redirect('/admin/settings');
    }

    /** @return array<string,mixed> */
    private function trackingSettings(): array
    {
        $defaults=['enabled'=>false,'google_tag_id'=>'','google_call_label'=>'','google_form_label'=>'','human_min_seconds'=>4,'human_min_interactions'=>2];
        foreach(array_keys($defaults) as $name){$row=Setting::first(['key'=>'tracking.'.$name]);if($row)$defaults[$name]=json_decode((string)$row->getAttribute('value'),true)??$defaults[$name];}
        return $defaults;
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
