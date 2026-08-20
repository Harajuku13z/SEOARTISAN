<?php

declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Core\Cache;
use App\Models\Company;
use App\Models\CompanyService;
use App\Models\Faq;
use App\Models\Media;
use App\Models\Page;
use App\Models\Setting;
use App\Repositories\BusinessCategoryRepository;
use App\Repositories\CompanyServiceRepository;
use App\Services\AI\AIContentServiceFactory;
use App\Services\Auth\AuthService;
use App\Services\Content\PageContentBuilder;
use App\Services\Content\MenuService;
use App\Services\Media\MediaUploadService;
use App\Support\Str;
use Throwable;

final class ServicesController extends AdminController
{
    public function __construct(
        AuthService $auth,
        private CompanyServiceRepository $companyServices,
        private BusinessCategoryRepository $categories,
        private MediaUploadService $uploads,
        private PageContentBuilder $pageBuilder
    ) {
        parent::__construct($auth);
    }

    public function index(Request $request): Response
    {
        $company = Company::current();
        $services = $company !== null ? $this->companyServices->forCompany((int) $company->id()) : [];

        [$groups, $serviceGroups] = $this->serviceGroups($services);
        $featuredMap=json_decode((string)(Setting::first(['key'=>'branding.category_featured_image_map'])?->getAttribute('value')??'{}'),true)?:[];
        $groupImages=[];foreach($featuredMap as $slug=>$id)if($media=Media::find((int)$id))$groupImages[$slug]=$media;
        return $this->render('admin.services.index', ['services'=>$services,'groups'=>$groups,'serviceGroups'=>$serviceGroups,'groupImages'=>$groupImages], 'services');
    }

    public function updateGroupImage(Request $request): Response
    {
        $slug=trim((string)$request->input('group_slug',''));
        $file=$request->file('group_featured_image');
        if($slug===''||$file===null){Session::flash('_errors',['form'=>'Choisissez une photo.']);return Response::redirect('/admin/services');}
        try{$media=$this->uploads->store($file,'service',$this->auth->user()?->id());$setting=Setting::first(['key'=>'branding.category_featured_image_map'])??new Setting();$map=json_decode((string)($setting->getAttribute('value')??'{}'),true)?:[];$map[$slug]=$media->id();$setting->fill(['key'=>'branding.category_featured_image_map','value'=>json_encode($map),'autoload'=>1])->save();Cache::flush();Session::flash('success','Photo du groupe mise à jour.');}catch(Throwable $e){Session::flash('_errors',['form'=>$e->getMessage()]);}
        return Response::redirect('/admin/services');
    }

    public function create(Request $request): Response
    {
        $company = Company::current();
        $suggested = [];
        if ($company !== null && $company->getAttribute('business_category_id')) {
            $existingServiceIds = array_column($this->companyServices->forCompany((int) $company->id()), 'service_id');
            $all = $this->categories->suggestedServicesFor((int) $company->getAttribute('business_category_id'));
            $suggested = array_values(array_filter($all, static fn ($s) => !in_array((int) $s['id'], array_map('intval', array_filter($existingServiceIds)), true)));
        }

        [$groups] = $this->serviceGroups([]);
        return $this->render('admin.services.create', ['suggested' => $suggested, 'groups' => $groups], 'services');
    }

    public function store(Request $request): Response
    {
        $company = Company::current();
        if ($company === null) {
            return Response::redirect('/admin/services');
        }

        $publicName = trim((string) $request->input('public_name', ''));
        if ($publicName === '') {
            Session::flash('_errors', ['form' => 'Le nom du service est obligatoire.']);

            return Response::redirect('/admin/services/create');
        }

        $serviceId = $request->input('service_id') !== '' ? (int) $request->input('service_id') : null;

        $companyService = CompanyService::create([
            'company_id' => $company->id(),
            'service_id' => $serviceId,
            'public_name' => $publicName,
            'slug' => Str::slug($publicName),
            'description' => trim((string) $request->input('description', '')) ?: null,
            'is_custom' => $serviceId === null,
            'show_in_menu' => true,
            'is_featured' => false,
            'is_emergency' => false,
            'show_starting_price' => false,
            'sort_order' => CompanyService::count(['company_id' => $company->id()]) + 1,
            'is_active' => true,
        ]);

        $selectedGroup=trim((string)$request->input('service_group',''));
        $items=MenuService::items();$parentId='';
        foreach($items as $item)if(empty($item['parent_id'])&&trim((string)parse_url((string)($item['url']??''),PHP_URL_PATH),'/')===$selectedGroup){$parentId=(string)$item['id'];break;}
        $items[]=['id'=>'service-'.$companyService->id(), 'label'=>$companyService->getAttribute('public_name'), 'url'=>'/'.$companyService->getAttribute('slug'), 'parent_id'=>$parentId, 'sort_order'=>count($items)+1, 'active'=>true];
        MenuService::save($items);

        $this->log('company_service.create', 'CompanyService', $companyService->id());
        Session::flash('success', 'Service ajoute.');

        return Response::redirect('/admin/services/' . $companyService->id());
    }

    public function edit(Request $request, array $params): Response
    {
        $service = CompanyService::find((int) $params['id']);
        if ($service === null) {
            return Response::redirect('/admin/services');
        }

        $page = Page::first(['company_service_id' => $service->id()]);

        $partnerIds=json_decode((string)(Setting::first(['key'=>'branding.partner_logo_ids'])?->getAttribute('value')??'[]'),true)?:[];
        $partnerMap=json_decode((string)(Setting::first(['key'=>'branding.service_partner_map'])?->getAttribute('value')??'{}'),true)?:[];
        [$groups, $serviceGroups] = $this->serviceGroups([$service->toArray()]);
        $currentGroup=$serviceGroups[(int)$service->id()]??'';
        $copyMap=json_decode((string)(Setting::first(['key'=>'content.service_copy_map'])?->getAttribute('value')??'{}'),true)?:[];
        $serviceCopy=array_merge($this->defaultCopy($service),(array)($copyMap[(string)$service->id()]??[]));
        return $this->render('admin.services.edit', ['service'=>$service,'page'=>$page,'serviceCopy'=>$serviceCopy,'partnerLogos'=>array_values(array_filter(array_map(static fn($id)=>Media::find((int)$id),$partnerIds))),'selectedPartnerIds'=>array_map('intval',(array)($partnerMap[(string)$service->id()]??$partnerIds)),'groups'=>$groups,'currentGroup'=>$currentGroup], 'services');
    }

    public function update(Request $request, array $params): Response
    {
        $service = CompanyService::find((int) $params['id']);
        if ($service === null) {
            return Response::redirect('/admin/services');
        }

        $oldSlug = (string)$service->getAttribute('slug');
        $publicName = trim((string) $request->input('public_name', $service->getAttribute('public_name')));

        $service->fill([
            'public_name' => $publicName,
            'slug' => trim((string) $request->input('slug', '')) ?: Str::slug($publicName),
            'description' => trim((string) $request->input('description', '')) ?: null,
            'show_in_menu' => $request->input('show_in_menu') !== null,
            'is_featured' => $request->input('is_featured') !== null,
            'is_emergency' => $request->input('is_emergency') !== null,
            'show_starting_price' => $request->input('show_starting_price') !== null,
            'starting_price' => $request->input('starting_price', '') !== '' ? (float) $request->input('starting_price') : null,
            'sort_order' => (int) $request->input('sort_order', $service->getAttribute('sort_order')),
            'meta_title' => trim((string) $request->input('meta_title', '')) ?: null,
            'meta_description' => trim((string) $request->input('meta_description', '')) ?: null,
            'is_active' => $request->input('is_active') !== null,
        ]);

        $file = $request->file('image');
        if($file===null&&!$service->getAttribute('image_media_id')){Session::flash('_errors',['form'=>'Chaque sous-service doit avoir sa propre photo.']);return Response::redirect('/admin/services/'.$service->id());}
        if ($file !== null) {
            try {
                $media = $this->uploads->store($file, 'service', $this->auth->user()?->id());
                $service->setAttribute('image_media_id', $media->id());
            } catch (Throwable $e) {
                Session::flash('_errors', ['form' => $e->getMessage()]);

                return Response::redirect('/admin/services/' . $service->id());
            }
        }

        $service->save();

        $selectedGroup = trim((string)$request->input('service_group', ''));
        $items = MenuService::items();
        $parentId = '';
        foreach ($items as $item) if (trim((string)parse_url((string)($item['url']??''), PHP_URL_PATH), '/') === $selectedGroup && empty($item['parent_id'])) { $parentId=(string)$item['id']; break; }
        $menuItemFound=false;
        foreach ($items as &$item) if (($item['url']??'') === '/'.ltrim($oldSlug,'/')) { $item['url']='/'.ltrim((string)$service->getAttribute('slug'),'/'); $item['label']=$service->getAttribute('public_name'); $item['parent_id']=$parentId; $item['active']=$service->getAttribute('show_in_menu'); $menuItemFound=true; }
        unset($item);
        if(!$menuItemFound)$items[]=['id'=>'service-'.$service->id(),'label'=>$service->getAttribute('public_name'),'url'=>'/'.ltrim((string)$service->getAttribute('slug'),'/'),'parent_id'=>$parentId,'sort_order'=>count($items)+1,'active'=>$service->getAttribute('show_in_menu')];
        MenuService::save($items);

        $partnerMapSetting=Setting::first(['key'=>'branding.service_partner_map'])??new Setting();
        $partnerMap=json_decode((string)($partnerMapSetting->getAttribute('value')??'{}'),true)?:[];
        $partnerMap[(string)$service->id()]=array_values(array_unique(array_map('intval',(array)$request->input('partner_logo_ids',[]))));
        $partnerMapSetting->fill(['key'=>'branding.service_partner_map','value'=>json_encode($partnerMap),'autoload'=>1])->save();

        $copySetting=Setting::first(['key'=>'content.service_copy_map'])??new Setting();
        $copyMap=json_decode((string)($copySetting->getAttribute('value')??'{}'),true)?:[];
        $copyMap[(string)$service->id()]=['hero_eyebrow'=>trim((string)$request->input('hero_eyebrow','')),'hero_intro'=>trim((string)$request->input('hero_intro','')),'about_title'=>trim((string)$request->input('about_title','')),'about_text'=>trim((string)$request->input('about_text',''))];
        $copySetting->fill(['key'=>'content.service_copy_map','value'=>json_encode($copyMap,JSON_UNESCAPED_UNICODE),'autoload'=>1])->save();

        // Keep the associated page's slug in sync if it changed.
        $page = Page::first(['company_service_id' => $service->id()]);
        if ($page !== null && $page->getAttribute('slug') !== $service->getAttribute('slug')) {
            $page->setAttribute('slug', $service->getAttribute('slug'));
            $page->save();
        }
        if($page!==null){$page->setAttribute('h1',trim((string)$request->input('page_h1',''))?:$publicName);$page->save();}

        $this->log('company_service.update', 'CompanyService', $service->id());
        Cache::flush();
        Session::flash('success', 'Service mis a jour.');

        return Response::redirect('/admin/services/' . $service->id());
    }

    public function regenerate(Request $request, array $params): Response
    {
        $service = CompanyService::find((int) $params['id']);
        $company = Company::current();
        if ($service === null || $company === null) {
            return Response::redirect('/admin/services');
        }

        $page = Page::first(['company_service_id' => $service->id()]) ?? new Page();
        $page->fill([
            'type' => 'service',
            'company_service_id' => $service->id(),
            'slug' => $service->getAttribute('slug'),
            'title' => $service->getAttribute('public_name'),
        ]);
        $page->save();

        $ai = AIContentServiceFactory::fromActiveProvider();
        $outcome = $ai->generate('service', [
            'company' => $company->toArray(),
            'service' => $service->toArray(),
            'tone' => \App\Models\AiProvider::active()?->getAttribute('tone') ?? config('ai.default_tone'),
            'language' => \App\Models\AiProvider::active()?->getAttribute('language') ?? config('ai.default_language'),
            'keywords_primary' => [$service->getAttribute('public_name')],
        ], $this->auth->user()?->id(), (int) $page->id());

        if ($outcome->success) {
            $this->pageBuilder->applySuccess($page, $outcome);
            Session::flash('success', 'Contenu regenere avec succes.');
        } else {
            $this->pageBuilder->applyPlaceholder($page, $outcome->errorMessage ?? 'echec IA');
            Session::flash('_errors', ['form' => 'La generation a echoue : ' . ($outcome->errorMessage ?? 'erreur inconnue') . '. Un brouillon a ete cree.']);
        }

        $this->log('company_service.regenerate', 'CompanyService', $service->id());

        return Response::redirect('/admin/services/' . $service->id());
    }

    public function addFaq(Request $request, array $params): Response
    {
        $service = CompanyService::find((int) $params['id']);
        if ($service === null) {
            return Response::redirect('/admin/services');
        }

        $questions = (array) $request->input('questions', []);
        $answers = (array) $request->input('answers', []);
        $nextOrder = Faq::count(['faqable_type' => 'CompanyService', 'faqable_id' => $service->id()]) + 1;

        foreach ($questions as $i => $question) {
            $question = trim((string) $question);
            $answer = trim((string) ($answers[$i] ?? ''));
            if ($question === '' || $answer === '') {
                continue;
            }
            Faq::create([
                'faqable_type' => 'CompanyService',
                'faqable_id' => $service->id(),
                'question' => $question,
                'answer' => $answer,
                'sort_order' => $nextOrder++,
            ]);
        }

        return Response::redirect('/admin/services/' . $service->id());
    }

    public function deleteFaq(Request $request, array $params): Response
    {
        $faq = Faq::find((int) $params['faqId']);
        $serviceId = $faq?->getAttribute('faqable_id');
        $faq?->delete();

        return Response::redirect('/admin/services/' . $serviceId);
    }

    /** @param array<int,array<string,mixed>> $services @return array{0:array<string,string>,1:array<int,string>} */
    private function serviceGroups(array $services): array
    {
        $groups=[];$slugGroups=[];
        foreach(MenuService::tree() as $root){if(empty($root['children']))continue;$slug=trim((string)parse_url((string)($root['url']??''),PHP_URL_PATH),'/');if($slug==='')continue;$groups[$slug]=(string)($root['label']??$slug);foreach($root['children'] as $child){$childSlug=trim((string)parse_url((string)($child['url']??''),PHP_URL_PATH),'/');if($childSlug!=='')$slugGroups[$childSlug]=$slug;}}
        $map=[];foreach($services as $service)$map[(int)$service['id']]=$slugGroups[(string)$service['slug']]??'';
        return [$groups,$map];
    }

    /** @return array{hero_eyebrow:string,hero_intro:string,about_title:string,about_text:string} */
    private function defaultCopy(CompanyService $service): array
    {
        $name=(string)$service->getAttribute('public_name');$slug=(string)$service->getAttribute('slug');
        $action=str_contains($slug,'entretien')?'entretien':(str_contains($slug,'depannage')?'dépannage':(str_contains($slug,'remplacement')?'remplacement':'installation'));
        $equipment=trim((string)preg_replace('/^(installation|entretien|dépannage|depannage|remplacement)\s+(de |du |d’|d\')?/iu','',$name))?:$name;
        $city=(string)(Company::current()?->getAttribute('city')?:'votre secteur');
        $heroEyebrow=ucfirst($action).' · '.$city;
        return match($action){
            'entretien'=>['hero_eyebrow'=>$heroEyebrow,'hero_intro'=>'Un équipement fiable et performant, saison après saison.','about_title'=>'Un équipement fiable et performant, saison après saison.','about_text'=>'Nous assurons l’entretien de votre '.$equipment.' afin de préserver ses performances, limiter les risques de panne et prolonger sa durée de vie. Notre intervention comprend les contrôles essentiels et des conseils adaptés à votre installation.'],
            'dépannage'=>['hero_eyebrow'=>$heroEyebrow,'hero_intro'=>'Retrouvez rapidement le confort de votre logement.','about_title'=>'Retrouvez rapidement le confort de votre logement.','about_text'=>'Notre équipe intervient pour diagnostiquer la panne de votre '.$equipment.', identifier son origine et vous proposer une solution claire. Chaque intervention privilégie la sécurité, la fiabilité et la remise en service durable.'],
            'remplacement'=>['hero_eyebrow'=>$heroEyebrow,'hero_intro'=>'Modernisez votre installation en toute sérénité.','about_title'=>'Modernisez votre installation en toute sérénité.','about_text'=>'Nous étudions votre équipement actuel et vos besoins afin de remplacer votre '.$equipment.' par une solution correctement dimensionnée, performante et adaptée à votre logement.'],
            default=>['hero_eyebrow'=>$heroEyebrow,'hero_intro'=>'Une installation pensée pour votre confort et vos économies.','about_title'=>'Une installation pensée pour votre confort et vos économies.','about_text'=>'Nous vous accompagnons de l’étude du projet jusqu’à la mise en service de votre '.$equipment.'. Le choix de l’équipement, son dimensionnement et son implantation sont étudiés selon votre logement et vos habitudes.'],
        };
    }
}
