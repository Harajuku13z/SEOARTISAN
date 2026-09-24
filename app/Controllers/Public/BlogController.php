<?php
declare(strict_types=1);
namespace App\Controllers\Public;

use App\Core\Request;
use App\Core\Response;
use App\Services\Content\PublicViewData;
use App\Services\Content\WordPressBlogService;
use App\Services\Content\LocalPageLinker;
use Throwable;

final class BlogController
{
    public function __construct(private PublicViewData $views, private WordPressBlogService $blog,private LocalPageLinker $localLinks){}
    public function index(Request $request): Response { $posts=[];$error=null;try{$posts=$this->blog->posts();}catch(Throwable $e){$error=$e->getMessage();}$data=array_merge($this->views->base(),['pageTitle'=>'Blog','metaDescription'=>'Conseils, actualités et informations utiles autour de nos services.','canonicalUrl'=>rtrim((string)config('app.url'),'/').'/blog','posts'=>$posts,'blogError'=>$error]);return Response::html(view_layout('public.layouts.main','public.pages.blog',$data)); }
    public function show(Request $request,array $params): Response
    {
        try{$post=$this->blog->post((string)($params['slug']??''));}catch(Throwable){$post=null;}
        if(!$post)return Response::html('Article introuvable',404);
        $plain=static fn(string $v):string=>trim(html_entity_decode(strip_tags($v),ENT_QUOTES|ENT_HTML5,'UTF-8'));
        $base=$this->views->base();$company=$base['company']??null;
        $siteUrl=rtrim((string)config('app.url'),'/');$url=$siteUrl.'/blog/'.($post['slug']??'');
        $title=$plain((string)($post['title']['rendered']??''));$description=$plain((string)($post['excerpt']['rendered']??''));
        $image=$post['_embedded']['wp:featuredmedia'][0]['source_url']??null;
        $content=(string)($post['content']['rendered']??'');
        $faq=[];
        if(preg_match_all('#<details[^>]*>\s*<summary[^>]*>(.*?)</summary>(.*?)</details>#si',$content,$m,PREG_SET_ORDER)){
            foreach($m as $row)$faq[]=['@type'=>'Question','name'=>$plain($row[1]),'acceptedAnswer'=>['@type'=>'Answer','text'=>$plain($row[2])]];
        }
        $name=(string)($company?->getAttribute('trade_name')?:config('app.name'));
        $graph=[
            ['@type'=>'BlogPosting','@id'=>$url.'#article','headline'=>$title,'description'=>$description,'image'=>$image?[$image]:null,'datePublished'=>$post['date_gmt']??$post['date']??null,'dateModified'=>$post['modified_gmt']??$post['modified']??null,'mainEntityOfPage'=>$url,'inLanguage'=>'fr-FR',
             'author'=>['@type'=>'Person','name'=>'Guy Hart','jobTitle'=>'Couvreur, fondateur de '.$name],
             'publisher'=>['@type'=>'Organization','name'=>$name,'url'=>$siteUrl,'logo'=>['@type'=>'ImageObject','url'=>$siteUrl.'/assets/images/hart/logo-hart.png']]],
            ['@type'=>'BreadcrumbList','itemListElement'=>[['@type'=>'ListItem','position'=>1,'name'=>'Accueil','item'=>$siteUrl.'/'],['@type'=>'ListItem','position'=>2,'name'=>'Blog','item'=>$siteUrl.'/blog'],['@type'=>'ListItem','position'=>3,'name'=>$title,'item'=>$url]]],
        ];
        if($faq)$graph[]=['@type'=>'FAQPage','mainEntity'=>$faq];
        $data=array_merge($base,['pageTitle'=>$title,'metaDescription'=>$description,'canonicalUrl'=>$url,'ogImageUrl'=>$image,'jsonLd'=>['@context'=>'https://schema.org','@graph'=>$graph],'post'=>$post,'localLinks'=>$this->localLinks->forWordPressPost($post)]);
        return Response::html(view_layout('public.layouts.main','public.pages.blog_single',$data));
    }
}
