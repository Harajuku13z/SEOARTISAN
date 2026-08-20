<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Models\Page;
use App\Models\PageBlock;
use App\Services\Content\MenuService;
use App\Models\Setting;
use App\Models\Media;
use App\Core\Cache;
use App\Services\Media\MediaUploadService;
use Throwable;

final class PagesController extends AdminController
{
    private const SIMPLE_BLOCK_TYPES = ['heading', 'text', 'list', 'button', 'cta'];

    private const ALL_BLOCK_TYPES = [
        'heading', 'text', 'image', 'gallery', 'list', 'button', 'faq', 'testimonials',
        'form', 'map', 'cta', 'services', 'projects', 'service_area',
    ];

    public function index(Request $request): Response
    {
        $groupSlugs=[];foreach(MenuService::tree() as $root)if(!empty($root['children'])){$slug=trim((string)parse_url((string)($root['url']??''),PHP_URL_PATH),'/');if($slug!=='')$groupSlugs[]=$slug;}
        $pages=array_values(array_filter(Page::all('type ASC, id ASC'),static fn($page)=>!in_array((string)$page->getAttribute('slug'),$groupSlugs,true)));
        return $this->render('admin.pages.index', [
            'pages' => $pages,
        ], 'pages');
    }

    public function edit(Request $request, array $params): Response
    {
        $page = Page::find((int) $params['id']);
        if ($page === null) {
            return Response::redirect('/admin/pages');
        }

        $homeCopy=[];$savImage=null;$zoneImage=null;
        if($page->getAttribute('type')==='home'){
            $homeCopy=array_merge($this->homeDefaults(),json_decode((string)(Setting::first(['key'=>'content.home_copy'])?->getAttribute('value')??'{}'),true)?:[]);
            $savId=(int)(Setting::first(['key'=>'content.home_sav_media_id'])?->getAttribute('value')??0);$savImage=$savId?Media::find($savId):null;
            $zoneId=(int)(Setting::first(['key'=>'content.home_zone_media_id'])?->getAttribute('value')??0);$zoneImage=$zoneId?Media::find($zoneId):null;
        }
        return $this->render('admin.pages.edit', [
            'page' => $page,
            'blocks' => PageBlock::where(['page_id' => $page->id()], 'position ASC'),
            'simpleTypes' => self::SIMPLE_BLOCK_TYPES,
            'allTypes' => self::ALL_BLOCK_TYPES,
            'homeCopy'=>$homeCopy,'savImage'=>$savImage,'zoneImage'=>$zoneImage,
        ], 'pages');
    }

    public function update(Request $request, array $params): Response
    {
        $page = Page::find((int) $params['id']);
        if ($page === null) {
            return Response::redirect('/admin/pages');
        }

        $page->fill([
            'title' => trim((string) $request->input('title', $page->getAttribute('title'))),
            'h1' => trim((string) $request->input('h1', '')) ?: null,
            'meta_title' => trim((string) $request->input('meta_title', '')) ?: null,
            'meta_description' => trim((string) $request->input('meta_description', '')) ?: null,
            'status' => in_array($request->input('status'), ['draft', 'published', 'archived'], true) ? $request->input('status') : $page->getAttribute('status'),
            'indexable' => $request->input('indexable') !== null,
        ]);
        if ($page->getAttribute('status') === 'published' && empty($page->getAttribute('published_at'))) {
            $page->setAttribute('published_at', date('Y-m-d H:i:s'));
        }
        $page->save();

        if($page->getAttribute('type')==='home'){
            $copy=[];foreach(array_keys($this->homeDefaults()) as $key)$copy[$key]=trim((string)$request->input('home_'.$key,$this->homeDefaults()[$key]));
            $setting=Setting::first(['key'=>'content.home_copy'])??new Setting();$setting->fill(['key'=>'content.home_copy','value'=>json_encode($copy,JSON_UNESCAPED_UNICODE),'autoload'=>1])->save();
            $file=$request->file('sav_image');if($file){try{$media=(new MediaUploadService((array)config('security')))->store($file,'other',$this->auth->user()?->id());$imageSetting=Setting::first(['key'=>'content.home_sav_media_id'])??new Setting();$imageSetting->fill(['key'=>'content.home_sav_media_id','value'=>(string)$media->id(),'autoload'=>1])->save();}catch(Throwable $e){Session::flash('_errors',['form'=>$e->getMessage()]);return Response::redirect('/admin/pages/'.$page->id());}}
            $zoneFile=$request->file('zone_image');if($zoneFile){try{$zoneMedia=(new MediaUploadService((array)config('security')))->store($zoneFile,'other',$this->auth->user()?->id());$zoneSetting=Setting::first(['key'=>'content.home_zone_media_id'])??new Setting();$zoneSetting->fill(['key'=>'content.home_zone_media_id','value'=>(string)$zoneMedia->id(),'autoload'=>1])->save();}catch(Throwable $e){Session::flash('_errors',['form'=>$e->getMessage()]);return Response::redirect('/admin/pages/'.$page->id());}}
            Cache::flush();
        }

        $this->log('page.update', 'Page', $page->id());
        Session::flash('success', 'Page mise a jour.');

        return Response::redirect('/admin/pages/' . $page->id());
    }

    public function addBlock(Request $request, array $params): Response
    {
        $page = Page::find((int) $params['id']);
        if ($page === null) {
            return Response::redirect('/admin/pages');
        }

        $type = (string) $request->input('type', 'text');
        if (!in_array($type, self::ALL_BLOCK_TYPES, true)) {
            return Response::redirect('/admin/pages/' . $page->id());
        }

        $maxPosition = 0;
        foreach (PageBlock::where(['page_id' => $page->id()]) as $b) {
            $maxPosition = max($maxPosition, (int) $b->getAttribute('position'));
        }

        PageBlock::create([
            'page_id' => $page->id(),
            'type' => $type,
            'position' => $maxPosition + 1,
            'data' => [],
            'is_active' => true,
        ]);

        return Response::redirect('/admin/pages/' . $page->id());
    }

    public function updateBlock(Request $request, array $params): Response
    {
        $block = PageBlock::find((int) $params['blockId']);
        if ($block === null) {
            return Response::redirect('/admin/pages');
        }

        $type = (string) $block->getAttribute('type');

        if (in_array($type, self::SIMPLE_BLOCK_TYPES, true)) {
            $data = (array) $request->input('data', []);
            if ($type === 'list') {
                $data['items'] = array_values(array_filter(array_map('trim', explode("\n", (string) ($data['items'] ?? '')))));
            }
        } else {
            $decoded = json_decode((string) $request->input('data_json', '{}'), true);
            $data = is_array($decoded) ? $decoded : [];
        }

        $block->fill([
            'data' => $data,
            'is_active' => $request->input('is_active') !== null,
        ]);
        $block->save();

        return Response::redirect('/admin/pages/' . $block->getAttribute('page_id'));
    }

    public function deleteBlock(Request $request, array $params): Response
    {
        $block = PageBlock::find((int) $params['blockId']);
        $pageId = $block?->getAttribute('page_id');
        $block?->delete();

        return Response::redirect('/admin/pages/' . $pageId);
    }

    public function moveBlock(Request $request, array $params): Response
    {
        $block = PageBlock::find((int) $params['blockId']);
        if ($block === null) {
            return Response::redirect('/admin/pages');
        }

        $direction = (string) $request->input('direction', 'down');
        $siblings = PageBlock::where(['page_id' => $block->getAttribute('page_id')], 'position ASC');

        $index = null;
        foreach ($siblings as $i => $sibling) {
            if ($sibling->id() === $block->id()) {
                $index = $i;
                break;
            }
        }

        $swapIndex = $direction === 'up' ? $index - 1 : $index + 1;
        if ($index !== null && isset($siblings[$swapIndex])) {
            $a = $siblings[$index];
            $b = $siblings[$swapIndex];
            $posA = $a->getAttribute('position');
            $posB = $b->getAttribute('position');
            $a->setAttribute('position', $posB);
            $b->setAttribute('position', $posA);
            $a->save();
            $b->save();
        }

        return Response::redirect('/admin/pages/' . $block->getAttribute('page_id'));
    }

    /** @return array<string,string> */
    private function homeDefaults(): array{return ['hero_eyebrow'=>'Artisan local','hero_title'=>'Votre projet entre de bonnes mains.','services_eyebrow'=>'Nos prestations','services_title'=>'Des services adaptés à vos besoins','about_eyebrow'=>'Qui sommes-nous','about_title'=>'Un savoir-faire de proximité','zone_eyebrow'=>'Zone d’intervention','zone_title'=>'Nous intervenons près de chez vous','zone_text'=>'Contactez-nous pour vérifier notre disponibilité dans votre commune.','projects_eyebrow'=>'Nos réalisations','projects_title'=>'Des chantiers menés avec soin','reviews_eyebrow'=>'Avis clients','reviews_title'=>'La satisfaction de nos clients','sav_eyebrow'=>'Suivi client','sav_title'=>'À vos côtés après l’intervention','sav_text'=>'Notre équipe reste disponible après le chantier pour répondre à vos questions et assurer le suivi prévu.','sav_point_1'=>'Un interlocuteur disponible','sav_point_2'=>'Un suivi adapté à la prestation','sav_point_3'=>'Des réponses claires à vos questions','partners_eyebrow'=>'Partenaires','partners_title'=>'Nos marques et partenaires','blog_eyebrow'=>'Conseils & expertise','blog_title'=>'Nos derniers articles'];}
}
