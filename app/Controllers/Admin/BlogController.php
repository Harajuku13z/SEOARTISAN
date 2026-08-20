<?php
declare(strict_types=1);
namespace App\Controllers\Admin;

use App\Core\Cache;
use App\Core\Request;
use App\Core\Response;
use App\Core\Session;
use App\Services\Auth\AuthService;
use App\Services\Content\WordPressBlogService;
use Throwable;

final class BlogController extends AdminController
{
    public function __construct(AuthService $auth, private WordPressBlogService $blog){ parent::__construct($auth); }
    public function show(Request $request): Response
    {
        $posts = []; $connectionError = null;
        if ($this->blog->url() !== '') {
            try { $posts = $this->blog->posts(1, 20); } catch (Throwable $e) { $connectionError = $e->getMessage(); }
        }
        return $this->render('admin.blog', ['wordpressUrl'=>$this->blog->url(), 'posts'=>$posts, 'connectionError'=>$connectionError], 'blog');
    }
    public function save(Request $request): Response { $url=trim((string)$request->input('wordpress_url','')); if($url!==''&&!preg_match('#^https?://#i',$url))$url='https://'.$url; $this->blog->saveUrl($url); Cache::flush(); Session::flash('success','Connexion WordPress enregistrée.'); return Response::redirect('/admin/blog'); }
    public function test(Request $request): Response { try{$this->blog->test();Session::flash('success','WordPress connecté : les articles publiés sont accessibles.');}catch(Throwable $e){Session::flash('_errors',['form'=>$e->getMessage()]);} return Response::redirect('/admin/blog'); }
}
