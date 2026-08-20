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
    public function show(Request $request,array $params): Response { try{$post=$this->blog->post((string)($params['slug']??''));}catch(Throwable){$post=null;}if(!$post)return Response::html('Article introuvable',404);$title=html_entity_decode(strip_tags((string)($post['title']['rendered']??'')),ENT_QUOTES|ENT_HTML5,'UTF-8');$data=array_merge($this->views->base(),['pageTitle'=>$title,'metaDescription'=>html_entity_decode(strip_tags((string)($post['excerpt']['rendered']??'')),ENT_QUOTES|ENT_HTML5,'UTF-8'),'canonicalUrl'=>rtrim((string)config('app.url'),'/').'/blog/'.($post['slug']??''),'post'=>$post,'localLinks'=>$this->localLinks->forWordPressPost($post)]);return Response::html(view_layout('public.layouts.main','public.pages.blog_single',$data)); }
}
