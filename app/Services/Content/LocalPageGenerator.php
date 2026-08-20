<?php

declare(strict_types=1);

namespace App\Services\Content;

use App\DTO\AIContentOutcome;
use App\Models\AiProvider;
use App\Models\City;
use App\Models\Company;
use App\Models\CompanyService;
use App\Models\LocalPage;
use App\Models\Page;
use App\Services\AI\AIContentServiceFactory;
use App\Support\Str;
use RuntimeException;
use Throwable;

final class LocalPageGenerator
{
    public function __construct(private PageContentBuilder $pages) {}

    public function generate(LocalPage $localPage): Page
    {
        $localPage->fill(['generation_status'=>'generating','error_message'=>null])->save();

        try {
            $company=Company::current();
            $service=CompanyService::find((int)$localPage->getAttribute('company_service_id'));
            $city=City::find((int)$localPage->getAttribute('city_id'));
            if(!$company||!$service||!$city)throw new RuntimeException('Entreprise, service ou ville introuvable.');

            $slug=Str::slug((string)$service->getAttribute('slug').'-'.(string)$city->getAttribute('slug'));
            $page=$localPage->getAttribute('page_id')?Page::find((int)$localPage->getAttribute('page_id')):Page::findBySlug($slug);
            $page=$page??new Page();
            $page->fill(['type'=>'local','company_service_id'=>$service->id(),'slug'=>$slug,'title'=>$service->getAttribute('public_name').' à '.$city->getAttribute('name')]);
            $page->save();

            if($localPage->getAttribute('content_mode')==='manual'){
                $payload=$this->normalizePayload((array)$localPage->getAttribute('content_payload'),$service,$city);
                $outcome=new AIContentOutcome(true,$payload,json_encode($payload,JSON_UNESCAPED_UNICODE)?:'{}',null,null,null,false);
            }else{
                $provider=AiProvider::active();
                $outcome=AIContentServiceFactory::fromActiveProvider()->generate('local_page',[
                    'company'=>$company->toArray(),'service'=>$service->toArray(),'city'=>$city->toArray(),
                    'local_context'=>(array)$localPage->getAttribute('content_payload'),
                    'tone'=>$provider?->getAttribute('tone')??config('ai.default_tone'),
                    'language'=>$provider?->getAttribute('language')??config('ai.default_language'),
                ],null,(int)$page->id());
                if(!$outcome->success)throw new RuntimeException($outcome->errorMessage??'Échec de la génération IA.');
            }

            $this->pages->applySuccess($page,$outcome);
            $page->fill(['status'=>$localPage->getAttribute('status')==='published'?'published':'draft','indexable'=>(bool)$localPage->getAttribute('is_active')]);
            if($localPage->getAttribute('status')==='published')$page->setAttribute('published_at',date('Y-m-d H:i:s'));
            $page->save();
            $localPage->fill(['page_id'=>$page->id(),'generation_status'=>'generated','error_message'=>null,'last_generated_at'=>date('Y-m-d H:i:s'),'published_at'=>$localPage->getAttribute('status')==='published'?date('Y-m-d H:i:s'):null])->save();
            return $page;
        }catch(Throwable $exception){
            $localPage->fill(['generation_status'=>'failed','error_message'=>mb_substr($exception->getMessage(),0,1000)])->save();
            throw $exception;
        }
    }

    private function normalizePayload(array $payload,CompanyService $service,City $city):array
    {
        $name=(string)$service->getAttribute('public_name');$place=(string)$city->getAttribute('name');
        $introduction=trim((string)($payload['introduction']??$payload['description']??''));
        if($introduction==='')throw new RuntimeException('Le contenu manuel doit contenir une introduction.');
        return [
            'title'=>(string)($payload['title']??$name.' à '.$place),
            'slug'=>(string)($payload['slug']??Str::slug($service->getAttribute('slug').'-'.$city->getAttribute('slug'))),
            'h1'=>(string)($payload['h1']??$name.' à '.$place),
            'meta_title'=>(string)($payload['meta_title']??$name.' à '.$place),
            'meta_description'=>(string)($payload['meta_description']??mb_substr(strip_tags($introduction),0,155)),
            'introduction'=>$introduction,
            'sections'=>array_values((array)($payload['sections']??[])),
            'faq'=>array_values((array)($payload['faq']??[])),
            'cta_title'=>(string)($payload['cta_title']??'Parlons de votre projet'),
            'cta_text'=>(string)($payload['cta_text']??'Contactez-nous pour obtenir une proposition adaptée.'),
        ];
    }
}
